<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Chat\ChatServer;

try {
    require dirname(__DIR__) . '/vendor/autoload.php';


    $port = 8080;
    $interface = '0.0.0.0'; // Listen on all interfaces

    echo "Starting chat server...\n";
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new ChatServer()
            )
        ),
        $port,
        $interface
    );

    echo "Chat server running on {$interface}:{$port}\n";
    echo "Press Ctrl+C to stop the server\n";

    $server->run();
} catch (\Exception $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
