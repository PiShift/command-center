<?php

namespace App\WebSocket;

use Psr\Http\Message\RequestInterface;
use Ratchet\RFC6455\Messaging\Frame;
use React\Socket\ConnectionInterface;
use App\Models\PersonalAccessToken;
use App\Models\TaskToken;
use App\Models\User;

class PiShiftWebSocketServer
{
    // connId => ['conn' => ConnectionInterface, 'meta' => [...]]
    protected array $connections = [];
    // workspace_id => [connId, ...]
    protected array $workspaceSubscriptions = [];
    // runtime_id => connId
    protected array $daemonConnections = [];
    // Buffer for RESP protocol parsing
    protected string $redisBuffer = '';

    /**
     * Register a newly upgraded WebSocket connection.
     */
    public function registerConnection(string $connId, ConnectionInterface $conn, RequestInterface $request): void
    {
        $path = $request->getUri()->getPath();
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        $meta = [
            'authenticated' => false,
            'user_id'       => null,
            'workspace_id'  => null,
            'type'          => 'browser',
            'request'       => $request,
        ];

        // Daemon connections authenticate immediately via Bearer token
        if ($path === '/api/daemon/ws' || isset($query['runtime_ids'])) {
            $meta['type'] = 'daemon';
            $authHeader = $request->getHeaderLine('Authorization');

            if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                $user = $this->resolveToken(trim($matches[1]));

                if ($user) {
                    $meta['authenticated'] = true;
                    $meta['user_id']       = $user->id;

                    foreach (explode(',', $query['runtime_ids'] ?? '') as $runtimeId) {
                        $runtimeId = trim($runtimeId);
                        if ($runtimeId !== '') {
                            $this->daemonConnections[$runtimeId] = $connId;
                        }
                    }
                } else {
                    $this->sendFrame($conn, json_encode(['error' => 'invalid token']));
                    $conn->close();
                    return;
                }
            } else {
                $this->sendFrame($conn, json_encode(['error' => 'missing bearer token']));
                $conn->close();
                return;
            }
        }

        $this->connections[$connId] = ['conn' => $conn, 'meta' => $meta];
    }

    /**
     * Handle incoming WebSocket messages
     */
    public function handleMessage(string $connId, string $msg): void
    {
        if (!isset($this->connections[$connId])) return;

        $meta = &$this->connections[$connId]['meta'];
        $conn =  $this->connections[$connId]['conn'];
        $data = json_decode($msg, true);

        if (!$data || !isset($data['type'])) return;

        $type = $data['type'];

        // Browser auth frame
        if ($type === 'auth') {
            $token = $data['payload']['token'] ?? null;
            if (!$token) {
                $this->send($connId, json_encode(['error' => 'missing token']));
                return;
            }

            $user = $this->resolveToken($token);
            if (!$user) {
                $this->send($connId, json_encode(['error' => 'invalid token']));
                return;
            }

            $request     = $meta['request'];
            $query       = [];
            parse_str($request->getUri()->getQuery(), $query);
            $workspaceId = $query['workspace_id'] ?? null;

            if ($workspaceId) {
                $team = \App\Models\Team::find($workspaceId);
                if (!$team || !$team->members()->where('users.id', $user->id)->exists()) {
                    $this->send($connId, json_encode(['error' => 'not a member of this workspace']));
                    return;
                }
            }

            $meta['authenticated'] = true;
            $meta['user_id']       = $user->id;
            $meta['workspace_id']  = $workspaceId;

            $this->send($connId, json_encode(['type' => 'auth_ack']));
            return;
        }

        // Require auth for everything else
        if (!($meta['authenticated'] ?? false)) {
            $this->send($connId, json_encode(['error' => 'unauthorized']));
            return;
        }

        // Ping
        if ($type === 'ping') {
            $this->send($connId, json_encode(['type' => 'pong']));
            return;
        }

        // Subscribe
        if ($type === 'subscribe') {
            $scope = $data['payload']['scope'] ?? null;
            $id    = $data['payload']['id'] ?? null;

            if ($scope === 'workspace' && $id) {
                $this->workspaceSubscriptions[$id][] = $connId;
                $this->send($connId, json_encode([
                    'type'    => 'subscribe_ack',
                    'payload' => ['scope' => $scope, 'id' => $id],
                ]));
            }
            return;
        }

        // Unsubscribe
        if ($type === 'unsubscribe') {
            $scope = $data['payload']['scope'] ?? null;
            $id    = $data['payload']['id'] ?? null;

            if ($scope === 'workspace' && $id && isset($this->workspaceSubscriptions[$id])) {
                $this->workspaceSubscriptions[$id] = array_values(array_filter(
                    $this->workspaceSubscriptions[$id],
                    fn($cId) => $cId !== $connId
                ));
                $this->send($connId, json_encode([
                    'type'    => 'unsubscribe_ack',
                    'payload' => ['scope' => $scope, 'id' => $id],
                ]));
            }
            return;
        }

        // Daemon heartbeat
        if ($type === 'daemon:heartbeat') {
            $runtimeId = $data['payload']['runtime_id'] ?? null;
            $this->send($connId, json_encode([
                'type'    => 'daemon:heartbeat_ack',
                'payload' => ['runtime_id' => $runtimeId, 'status' => 'ok'],
            ]));
            return;
        }
    }

    /**
     * Handle connection close
     */
    public function handleClose(string $connId): void
    {
        unset($this->connections[$connId]);

        foreach ($this->workspaceSubscriptions as $wsId => $connIds) {
            $this->workspaceSubscriptions[$wsId] = array_values(array_filter(
                $connIds,
                fn($cId) => $cId !== $connId
            ));
        }

        foreach ($this->daemonConnections as $runtimeId => $cId) {
            if ($cId === $connId) {
                unset($this->daemonConnections[$runtimeId]);
            }
        }
    }

    /**
     * Send a WebSocket text frame to a specific connection.
     */
    public function send(string $connId, string $message): void
    {
        $conn = $this->connections[$connId]['conn'] ?? null;
        if ($conn) {
            $this->sendFrame($conn, $message);
        }
    }

    /**
     * Write a properly framed WebSocket text frame to a raw socket connection.
     */
    protected function sendFrame(ConnectionInterface $conn, string $payload): void
    {
        try {
            // Server sends unmasked frames (mask = false)
            $frame = new Frame($payload, true, Frame::OP_TEXT);
            $conn->write($frame->getContents());
        } catch (\Throwable) {
            // Connection may have closed
        }
    }

    /**
     * Broadcast an event to all connections subscribed to a workspace.
     */
    public function broadcastToWorkspace(string $workspaceId, array $payload): void
    {
        $message = json_encode($payload);
        foreach ($this->workspaceSubscriptions[$workspaceId] ?? [] as $connId) {
            $this->send($connId, $message);
        }
    }

    /**
     * Send task wakeup to a specific daemon runtime.
     */
    public function wakeupDaemon(string $runtimeId, string $taskId): void
    {
        $connId = $this->daemonConnections[$runtimeId] ?? null;
        if ($connId) {
            $this->send($connId, json_encode([
                'type'    => 'daemon:task_available',
                'payload' => ['runtime_id' => $runtimeId, 'task_id' => $taskId],
            ]));
        }
    }

    /**
     * Subscribe to Redis pub/sub channels asynchronously using ReactPHP.
     * Called once from the artisan command after the server starts.
     */
    public function subscribeToRedis(\React\EventLoop\LoopInterface $loop): void
    {
        $host = config('database.redis.default.host', '127.0.0.1');
        $port = (int) config('database.redis.default.port', 6379);

        $connector = new \React\Socket\Connector($loop);
        $connector->connect("tcp://{$host}:{$port}")->then(
            function (\React\Socket\ConnectionInterface $conn) {
                // Subscribe to both channels using RESP protocol
                $conn->write("*2\r\n\$9\r\nSUBSCRIBE\r\n\$16\r\npishift:ws:events\r\n");
                $conn->write("*2\r\n\$9\r\nSUBSCRIBE\r\n\$15\r\npishift:ws:daemon\r\n");

                $conn->on('data', function (string $chunk) {
                    $this->redisBuffer .= $chunk;
                    $this->processRedisBuffer();
                });

                $conn->on('error', function (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Redis subscriber error: ' . $e->getMessage());
                });

                $conn->on('close', function () {
                    \Illuminate\Support\Facades\Log::warning('Redis subscriber connection closed');
                });
            },
            function (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Redis subscriber connect failed: ' . $e->getMessage());
            }
        );
    }

    /**
     * Process incoming Redis RESP protocol buffer and dispatch events.
     */
    private function processRedisBuffer(): void
    {
        // Parse Redis RESP protocol messages
        // A pub/sub message looks like:
        // *3\r\n$7\r\nmessage\r\n$<channel_len>\r\n<channel>\r\n$<payload_len>\r\n<payload>\r\n
        while (true) {
            // Must start with array marker
            if (!str_starts_with($this->redisBuffer, '*3')) break;

            $parts = [];
            $remaining = $this->redisBuffer;

            // Parse array header
            if (!preg_match('/^\*3\r\n/', $remaining)) break;
            $remaining = substr($remaining, 4);

            // Try to extract 3 bulk strings
            for ($i = 0; $i < 3; $i++) {
                if (!preg_match('/^\$(\d+)\r\n/', $remaining, $m)) return; // incomplete
                $len = (int) $m[1];
                $remaining = substr($remaining, strlen($m[0]));
                if (strlen($remaining) < $len + 2) return; // incomplete
                $parts[] = substr($remaining, 0, $len);
                $remaining = substr($remaining, $len + 2);
            }

            // Consumed a full message
            $this->redisBuffer = $remaining;

            [$type, $channel, $payload] = $parts;

            if ($type !== 'message') continue;

            $data = json_decode($payload, true);
            if (!$data) continue;

            if ($channel === 'pishift:ws:events') {
                $workspaceId = $data['workspace_id'] ?? null;
                $wsPayload   = $data['payload'] ?? null;
                if ($workspaceId && $wsPayload) {
                    $this->broadcastToWorkspace($workspaceId, $wsPayload);
                }
            }

            if ($channel === 'pishift:ws:daemon') {
                $runtimeId = $data['runtime_id'] ?? null;
                $taskId    = $data['task_id'] ?? null;
                if ($runtimeId && $taskId) {
                    $this->wakeupDaemon($runtimeId, $taskId);
                }
            }
        }
    }

    /**
     * Resolve token and return user
     */
    private function resolveToken(string $token): ?User
    {
        $hash = hash('sha256', $token);

        if (str_starts_with($token, 'mat_')) {
            $taskToken = TaskToken::where('token_hash', $hash)
                ->where('expires_at', '>', now())
                ->first();
            return $taskToken ? User::find($taskToken->user_id) : null;
        }

        if (str_starts_with($token, 'mul_')) {
            $pat = PersonalAccessToken::where('token_hash', $hash)->active()->first();
            return $pat ? User::find($pat->user_id) : null;
        }

        return null;
    }
}
