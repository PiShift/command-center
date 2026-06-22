<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\PiShiftWebSocketServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use React\Socket\ConnectionInterface;
use Ratchet\RFC6455\Handshake\ServerNegotiator;
use Ratchet\RFC6455\Handshake\RequestVerifier;
use Ratchet\RFC6455\Messaging\MessageBuffer;
use Ratchet\RFC6455\Messaging\CloseFrameChecker;
use Ratchet\RFC6455\Messaging\Frame;
use GuzzleHttp\Psr7\Message;

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
            $loop      = Factory::create();
            $wsServer  = new PiShiftWebSocketServer();
            $negotiator = new ServerNegotiator(new RequestVerifier());

            // Relax strict subprotocol check so clients that don't send a subprotocol still connect
            $negotiator->setStrictSubProtocolCheck(false);

            // Store in container so WebSocketBroadcaster can reach it
            app()->instance(PiShiftWebSocketServer::class, $wsServer);

            $socket = new SocketServer("{$host}:{$port}", [], $loop);

            $socket->on('connection', function (ConnectionInterface $tcpConn) use ($wsServer, $negotiator) {
                $httpBuffer = '';
                $connId     = spl_object_id($tcpConn);
                /** @var MessageBuffer|null $msgBuffer */
                $msgBuffer = null;

                $tcpConn->on('data', function (string $data) use (
                    $tcpConn, $wsServer, $negotiator, $connId, &$httpBuffer, &$msgBuffer
                ) {
                    // Already upgraded — pass data directly to the frame parser
                    if ($msgBuffer !== null) {
                        $msgBuffer->onData($data);
                        return;
                    }

                    // Buffer until we have the full HTTP request headers
                    $httpBuffer .= $data;
                    if (strpos($httpBuffer, "\r\n\r\n") === false) {
                        return;
                    }

                    // Parse the HTTP upgrade request
                    try {
                        $request = Message::parseRequest($httpBuffer);
                    } catch (\Throwable $e) {
                        $tcpConn->write("HTTP/1.1 400 Bad Request\r\n\r\n");
                        $tcpConn->close();
                        return;
                    }

                    // Perform RFC6455 WebSocket handshake
                    $response = $negotiator->handshake($request);

                    if ($response->getStatusCode() !== 101) {
                        $tcpConn->write(Message::toString($response));
                        $tcpConn->close();
                        return;
                    }

                    // Register the connection in the business-logic server
                    $wsServer->registerConnection((string) $connId, $tcpConn, $request);

                    // Send the 101 Switching Protocols response
                    $tcpConn->write(Message::toString($response));

                    // Wire up RFC6455 message buffer for ongoing framing
                    $msgBuffer = new MessageBuffer(
                        new CloseFrameChecker(),
                        // Complete text/binary message received
                        function ($msg) use ($connId, $wsServer) {
                            $wsServer->handleMessage((string) $connId, $msg->getPayload());
                        },
                        // Control frame received (ping / close)
                        function (Frame $frame) use ($tcpConn, $connId, $wsServer) {
                            if ($frame->getOpcode() === Frame::OP_PING) {
                                $pong = new Frame($frame->getPayload(), true, Frame::OP_PONG);
                                $tcpConn->write($pong->getContents());
                            } elseif ($frame->getOpcode() === Frame::OP_CLOSE) {
                                $tcpConn->close();
                            }
                        },
                        true,  // checkForMask — clients MUST mask frames
                        null   // no outbound data callback needed
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
