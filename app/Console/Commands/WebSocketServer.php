<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\Router;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use App\WebSocket\PiShiftWebSocketServer;
use React\EventLoop\Loop;

class WebSocketServer extends Command
{
    protected $signature   = 'websocket:serve {--port=6001}';
    protected $description = 'Start the PiShift WebSocket server';

    public function handle(): void
    {
        $port = (int) $this->option('port');
        $this->info("Starting WebSocket server on port {$port}...");

        $loop = Loop::get();
        $wsServer = new PiShiftWebSocketServer();

        $routes = new RouteCollection();
        $routes->add('ws', new Route('/ws', [
            '_controller' => new WsServer($wsServer),
        ]));
        $routes->add('daemon_ws', new Route('/api/daemon/ws', [
            '_controller' => new WsServer($wsServer),
        ]));

        $server = new IoServer(
            new HttpServer(new Router($routes)),
            new \React\Socket\SocketServer('0.0.0.0:' . $port, [], $loop),
            $loop
        );

        // Wire Redis subscriber into the same event loop
        $wsServer->subscribeToRedis($loop);

        $this->info("WebSocket server running on port {$port}");
        $this->info("Redis pub/sub active on pishift:ws:events and pishift:ws:daemon");

        $loop->run();
    }
}
