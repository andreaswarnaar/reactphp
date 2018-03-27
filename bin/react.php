<?php
/**
 * User: Andreas Warnaar
 * Date: 11-2-18
 * Time: 20:48
 */

include __DIR__ . '/../vendor/autoload.php';
ini_set('date.timezone','Europe/Amsterdam');
use App\Kernel;
use Symfony\Component\Debug\Debug;
$kernel = new Kernel($env??'dev', true);

$kernel->boot();

$loop = React\EventLoop\Factory::create();


$single_server = [
    'host'               => 'redis',
    'port'               => 6379,
    'database'           => 1,
    'read_write_timeout' => 0
];
$client = new Predis\Client($single_server);
$clientPublisher = new Predis\Client($single_server);

$factory = new \Clue\React\Redis\Factory($loop);
umask(0000);
ini_set('display_errors', 1);
Debug::enable();


$handler = new \App\Server\HttpSocketRequestHandler($kernel);

$connectionPool = new \App\Server\ConnectionPool($clientPublisher);

$ws = new \Voryx\WebSocketMiddleware\WebSocketMiddleware(['/ws'],
    new \App\Server\WebSocketConnectionHandler($connectionPool, $loop)
);
$server = new \React\Http\Server([$ws, $handler]);

$factoryRedis = new \Clue\React\Redis\Factory($loop);
$factoryRedis->createClient('redis')->then([$connectionPool,'handlePubSub']);

$socket = new React\Socket\Server('0.0.0.0:' . $_SERVER['PORT'], $loop);
$server->on('error', function (Exception $e) {
    echo 'Error: ' . $e->__toString() . PHP_EOL;
});
$server->listen($socket);

$loop->run();