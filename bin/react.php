<?php
/**
 * User: Andreas Warnaar
 * Date: 11-2-18
 * Time: 20:48
 */

include __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$server = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) {
    return new \React\Http\Response(
        200,
        array(
            'Content-Type' => 'text/plain'
        ),
        "Hello World!\n"
    );
});

$socket = new React\Socket\Server( '0.0.0.0:'.$_SERVER['PORT'], $loop);
$server->listen($socket);

$loop->run();