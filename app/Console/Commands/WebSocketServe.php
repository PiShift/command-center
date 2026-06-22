<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\PiShiftWebSocketServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;

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
            $loop = Factory::create();
            $wsServer = new PiShiftWebSocketServer();

            // Store in container for broadcasting
            app()->instance(PiShiftWebSocketServer::class, $wsServer);

            // Create HTTP server that handles WebSocket upgrades
            $httpServer = new HttpServer(
                new RequestBodyBufferMiddleware(),
                new RequestBodyParserMiddleware(),
                function ($request) use ($wsServer) {
                    return $wsServer->handleRequest($request);
                }
            );

            $socket = new SocketServer("{$host}:{$port}", [], $loop);
            $httpServer->listen($socket);

            $this->line("<fg=green>✓</> Server listening on ws://{$host}:{$port}");
            $this->line('Press Ctrl+C to stop');

            $loop->run();
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return;
        }
    }
}
