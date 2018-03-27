<?php
/**
 * User: Andreas Warnaar
 * Date: 23-3-18
 * Time: 17:38
 */

include __DIR__ . '/../vendor/autoload.php';

$single_server = array(
    'host' => 'redis',
    'port' => 6379,
    'database' => 1,
);
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
require __DIR__ . '/../vendor/autoload.php';
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$factory->createClient('redis')->then(function (Client $client) {
    $client->psubscribe('*')->then(function () {
        echo 'Now subscribed to channel ' . PHP_EOL;
    });
    $client->on('message', function ($channel, $message) {
        echo 'Message on ' . $channel . ': ' . $message . PHP_EOL;
    });
});
$loop->run();