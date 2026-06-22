<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\PiShiftWebSocketServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use React\Socket\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageBuffer;
use Ratchet\RFC6455\Messaging\CloseFrameChecker;
use Ratchet\RFC6455\Messaging\Frame;
use GuzzleHttp\Psr7\Request;

class WebSocketServe extends Command
{
    protected $signature = 'websocket:serve
                          {--host=0.0.0.0 : The host to bind to}
                          {--port=6001 : The port to listen on}';

    protected $description = 'Start the PiShift WebSocket server for real-time updates';

    public function handle(): void
    {
        $host = $this->option('host');
        $port = (int) $this->option('port');

        $this->info("🚀 Starting PiShift WebSocket server on {$host}:{$port}");
        $this->line('Waiting for connections...');

        try {
            $loop     = Factory::create();
            $wsServer = new PiShiftWebSocketServer();

            app()->instance(PiShiftWebSocketServer::class, $wsServer);

            $socket = new SocketServer("{$host}:{$port}", [], $loop);

            $socket->on('connection', function (ConnectionInterface $tcpConn) use ($wsServer) {
                $httpBuffer = '';
                $connId     = spl_object_id($tcpConn);
                /** @var MessageBuffer|null $msgBuffer */
                $msgBuffer = null;

                $tcpConn->on('data', function (string $data) use (
                    $tcpConn, $wsServer, $connId, &$httpBuffer, &$msgBuffer
                ) {
                    // Already upgraded — pass to frame parser
                    if ($msgBuffer !== null) {
                        $msgBuffer->onData($data);
                        return;
                    }

                    // Buffer until we have the full HTTP headers
                    $httpBuffer .= $data;
                    if (strpos($httpBuffer, "\r\n\r\n") === false) {
                        return;
                    }

                    // Parse headers manually (avoids ratchet version differences)
                    $lines   = explode("\r\n", $httpBuffer);
                    $headers = [];
                    foreach (array_slice($lines, 1) as $line) {
                        if (str_contains($line, ':')) {
                            [$name, $value] = explode(':', $line, 2);
                            $headers[strtolower(trim($name))] = trim($value);
                        }
                    }

                    $upgrade    = strtolower($headers['upgrade'] ?? '');
                    $connection = strtolower($headers['connection'] ?? '');
                    $key        = $headers['sec-websocket-key'] ?? '';

                    if ($upgrade !== 'websocket' || !str_contains($connection, 'upgrade') || empty($key)) {
                        $tcpConn->write("HTTP/1.1 400 Bad Request\r\nContent-Length: 11\r\n\r\nBad Request");
                        $tcpConn->close();
                        return;
                    }

                    // RFC6455 handshake — compute Accept header
                    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

                    // Extract request path+query
                    preg_match('/^[A-Z]+\s+(\S+)\s+HTTP/i', $lines[0] ?? '', $m);
                    $requestUri = $m[1] ?? '/';
                    $request    = new Request('GET', $requestUri, $headers);

                    // Register connection before sending 101
                    $wsServer->registerConnection((string) $connId, $tcpConn, $request);

                    // Send 101 Switching Protocols
                    $tcpConn->write(
                        "HTTP/1.1 101 Switching Protocols\r\n" .
                        "Upgrade: websocket\r\n" .
                        "Connection: Upgrade\r\n" .
                        "Sec-WebSocket-Accept: {$accept}\r\n\r\n"
                    );

                    // Wire up RFC6455 message buffer for ongoing framing
                    $msgBuffer = new MessageBuffer(
                        new CloseFrameChecker(),
                        function ($msg) use ($connId, $wsServer) {
                            $wsServer->handleMessage((string) $connId, $msg->getPayload());
                        },
                        function (Frame $frame) use ($tcpConn) {
                            if ($frame->getOpcode() === Frame::OP_PING) {
                                $pong = new Frame($frame->getPayload(), true, Frame::OP_PONG);
                                $tcpConn->write($pong->getContents());
                            } elseif ($frame->getOpcode() === Frame::OP_CLOSE) {
                                $tcpConn->close();
                            }
                        },
                        true,
                        null
                    );

                    // Feed any bytes that arrived after the HTTP headers
                    $headerEnd = strpos($httpBuffer, "\r\n\r\n") + 4;
                    if (strlen($httpBuffer) > $headerEnd) {
                        $msgBuffer->onData(substr($httpBuffer, $headerEnd));
                    }
                });

                $tcpConn->on('close', function () use ($connId, $wsServer) {
                    $wsServer->handleClose((string) $connId);
                });

                $tcpConn->on('error', function (\Throwable $e) use ($tcpConn) {
                    $tcpConn->close();
                });
            });

            $this->line("<fg=green>✓</> Server listening on ws://{$host}:{$port}");
            $this->line('Press Ctrl+C to stop');

            $loop->run();
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
}
