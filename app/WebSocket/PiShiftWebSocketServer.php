<?php

namespace App\WebSocket;

use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use App\Models\PersonalAccessToken;
use App\Models\TaskToken;
use App\Models\User;
use SplObjectStorage;

class PiShiftWebSocketServer
{
    protected SplObjectStorage $clients;
    // workspace_id => [connection, ...]
    protected array $workspaceSubscriptions = [];
    // runtime_id => connection (daemon connections)
    protected array $daemonConnections = [];
    // connection resource id => metadata
    protected array $connectionMeta = [];

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    /**
     * Handle HTTP upgrade requests to WebSocket
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath();
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);

        // Check if this is a WebSocket upgrade request
        $upgrade = strtolower($request->getHeaderLine('Upgrade') ?? '');
        if ($upgrade !== 'websocket') {
            return new Response(400, [], 'Bad Request');
        }

        $connectionId = spl_object_id($request->getServerParams()['client_stream'] ?? null);
        
        $this->connectionMeta[$connectionId] = [
            'authenticated' => false,
            'user_id'       => null,
            'workspace_id'  => null,
            'type'          => 'browser',
            'request'       => $request,
        ];

        // Determine connection type
        if ($path === '/api/daemon/ws' || isset($query['runtime_ids'])) {
            $this->connectionMeta[$connectionId]['type'] = 'daemon';
            
            // Authenticate daemon via Authorization header
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                $token = trim($matches[1]);
                $user = $this->resolveToken($token);
                
                if ($user) {
                    $this->connectionMeta[$connectionId]['authenticated'] = true;
                    $this->connectionMeta[$connectionId]['user_id'] = $user->id;
                    
                    // Register daemon runtime connections
                    if (isset($query['runtime_ids'])) {
                        $runtimeIds = explode(',', $query['runtime_ids']);
                        foreach ($runtimeIds as $runtimeId) {
                            $this->daemonConnections[trim($runtimeId)] = $connectionId;
                        }
                    }
                } else {
                    return new Response(401, [], json_encode(['error' => 'invalid token']));
                }
            } else {
                return new Response(401, [], json_encode(['error' => 'missing bearer token']));
            }
        }

        return new Response(101); // Switching Protocols
    }

    /**
     * Handle incoming WebSocket messages
     */
    public function handleMessage(string $connectionId, string $msg): void
    {
        $meta = $this->connectionMeta[$connectionId] ?? [];
        $data = json_decode($msg, true);

        if (!$data || !isset($data['type'])) return;

        $type = $data['type'];

        // Browser auth frame
        if ($type === 'auth') {
            $token = $data['payload']['token'] ?? null;
            if (!$token) {
                $this->send($connectionId, json_encode(['error' => 'missing token']));
                return;
            }

            $user = $this->resolveToken($token);
            if (!$user) {
                $this->send($connectionId, json_encode(['error' => 'invalid token']));
                return;
            }

            // Extract workspace_id from request query params
            $request = $meta['request'] ?? null;
            $workspaceId = null;
            
            if ($request) {
                $query = [];
                parse_str($request->getUri()->getQuery(), $query);
                $workspaceId = $query['workspace_id'] ?? null;
            }

            if ($workspaceId) {
                $team = \App\Models\Team::find($workspaceId);
                if (!$team || !$team->members()->where('users.id', $user->id)->exists()) {
                    $this->send($connectionId, json_encode(['error' => 'not a member of this workspace']));
                    return;
                }
            }

            $this->connectionMeta[$connectionId]['authenticated'] = true;
            $this->connectionMeta[$connectionId]['user_id'] = $user->id;
            $this->connectionMeta[$connectionId]['workspace_id'] = $workspaceId;

            $this->send($connectionId, json_encode(['type' => 'auth_ack']));
            return;
        }

        // Require auth for everything else
        if (!($meta['authenticated'] ?? false)) {
            $this->send($connectionId, json_encode(['error' => 'unauthorized']));
            return;
        }

        // Ping
        if ($type === 'ping') {
            $this->send($connectionId, json_encode(['type' => 'pong']));
            return;
        }

        // Subscribe
        if ($type === 'subscribe') {
            $scope = $data['payload']['scope'] ?? null;
            $id = $data['payload']['id'] ?? null;

            if ($scope === 'workspace' && $id) {
                if (!isset($this->workspaceSubscriptions[$id])) {
                    $this->workspaceSubscriptions[$id] = [];
                }
                $this->workspaceSubscriptions[$id][] = $connectionId;
                
                $this->send($connectionId, json_encode([
                    'type'    => 'subscribe_ack',
                    'payload' => ['scope' => $scope, 'id' => $id],
                ]));
            }
            return;
        }

        // Unsubscribe
        if ($type === 'unsubscribe') {
            $scope = $data['payload']['scope'] ?? null;
            $id = $data['payload']['id'] ?? null;

            if ($scope === 'workspace' && $id && isset($this->workspaceSubscriptions[$id])) {
                $this->workspaceSubscriptions[$id] = array_filter(
                    $this->workspaceSubscriptions[$id],
                    fn($cId) => $cId !== $connectionId
                );
                
                $this->send($connectionId, json_encode([
                    'type'    => 'unsubscribe_ack',
                    'payload' => ['scope' => $scope, 'id' => $id],
                ]));
            }
            return;
        }

        // Daemon heartbeat
        if ($type === 'daemon:heartbeat') {
            $runtimeId = $data['payload']['runtime_id'] ?? null;
            $this->send($connectionId, json_encode([
                'type'    => 'daemon:heartbeat_ack',
                'payload' => [
                    'runtime_id' => $runtimeId,
                    'status'     => 'ok',
                ],
            ]));
            return;
        }
    }

    /**
     * Handle connection close
     */
    public function handleClose(string $connectionId): void
    {
        unset($this->connectionMeta[$connectionId]);

        // Remove from workspace subscriptions
        foreach ($this->workspaceSubscriptions as $wsId => $connections) {
            $this->workspaceSubscriptions[$wsId] = array_filter(
                $connections,
                fn($cId) => $cId !== $connectionId
            );
        }

        // Remove daemon connections
        foreach ($this->daemonConnections as $runtimeId => $cId) {
            if ($cId === $connectionId) {
                unset($this->daemonConnections[$runtimeId]);
            }
        }

        $this->clients->detach($connectionId);
    }

    /**
     * Send message to a specific connection
     */
    protected function send(string $connectionId, string $message): void
    {
        // This will be called from the command handler
        // Store for later dispatch
    }

    /**
     * Broadcast an event to all connections subscribed to a workspace.
     */
    public function broadcastToWorkspace(string $workspaceId, array $payload): void
    {
        $message = json_encode($payload);
        foreach ($this->workspaceSubscriptions[$workspaceId] ?? [] as $connectionId) {
            $this->send($connectionId, $message);
        }
    }

    /**
     * Send task wakeup to a specific daemon runtime.
     */
    public function wakeupDaemon(string $runtimeId, string $taskId): void
    {
        $connectionId = $this->daemonConnections[$runtimeId] ?? null;
        if ($connectionId) {
            $this->send($connectionId, json_encode([
                'type'    => 'daemon:task_available',
                'payload' => [
                    'runtime_id' => $runtimeId,
                    'task_id'    => $taskId,
                ],
            ]));
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
