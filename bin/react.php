<?php
/**
 * User: Andreas Warnaar
 * Date: 11-2-18
 * Time: 20:48
 */

include __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$env = 'dev';//$_SERVER['APP_ENV'] ?? 'dev';
$debug = true; //$_SERVER['APP_DEBUG'] ?? ('prod' !== $env);

if ($debug) {
    umask(0000);

    Debug::enable();
}

$kernel = new Kernel($env, $debug);

$callback = function (\Psr\Http\Message\ServerRequestInterface $request) use ($kernel) {
    $method = $request->getMethod();
    $headers = $request->getHeaders();
    $query = $request->getQueryParams();
    $content = $request->getBody();
    $post = [];
    if (in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH']) &&
        isset($headers['Content-Type']) && (0 === strpos($headers['Content-Type'], 'application/x-www-form-urlencoded'))
    ) {
        parse_str($content, $post);
    }
    $sfRequest = new Symfony\Component\HttpFoundation\Request(
        $query,
        $post,
        [],
        [], // To get the cookies, we'll need to parse the headers
        $request->getUploadedFiles(),
        [], // Server is partially filled a few lines below
        $content
    );
    $sfRequest->setMethod($method);
    $sfRequest->headers->replace($headers);
    $sfRequest->server->set('REQUEST_URI', $request->getUri());
    echo '===========================================================================';
    echo '===========================================================================';
    echo '===========================================================================';
    echo $request->getRequestTarget() . PHP_EOL;
    echo '===========================================================================';


    if (isset($headers['Host'])) {
        $sfRequest->server->set('SERVER_NAME', $headers['Host'][0]);
    }
    //echo print_r($headers);
    $sfResponse = $kernel->handle($sfRequest);
    $response = new \React\Http\Response(
        $sfResponse->getStatusCode(),
        $sfResponse->headers->all(),
        $sfResponse->getContent()
    );

    $kernel->terminate($sfRequest, $sfResponse);

    return $response;
};

ini_set('display_errors', 1);
$loop = React\EventLoop\Factory::create();


$broadcast = new \React\Stream\ThroughStream();

$ws = new \Voryx\WebSocketMiddleware\WebSocketMiddleware(
    ['/ws'],
    function (\Voryx\WebSocketMiddleware\WebSocketConnection $conn, \Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) use ($broadcast, $loop) {
        static $user = 0;

        // do not send on the connection before the react http server has a chance to start listening
        // on the streams
        $loop->addTimer(0, function () use ($conn, $user, $broadcast) {
            $broadcast->write('user ' . $user . ' connected');
            $conn->send('Welcome. You are user ' . $user);
        });

        $broadcastHandler = function ($data) use ($conn) {
            $conn->send($data);
        };

        $broadcast->on('data', $broadcastHandler);

        $conn->on('message', function (\Ratchet\RFC6455\Messaging\Message $message) use ($broadcast, $conn, $user) {
            $broadcast->write('user ' . $user . ': ' . $message->getPayload());
        });

        $conn->on('error', function (Throwable $e) use ($broadcast, $user, $broadcastHandler) {
            $broadcast->removeListener('data', $broadcastHandler);
            $broadcast->write('user ' . $user . ' left because of error: ' . $e->getMessage());
        });

        $conn->on('close', function () use ($broadcast, $user, $broadcastHandler) {
            $broadcast->removeListener('data', $broadcastHandler);
            $broadcast->write('user ' . $user . ' closed their connection');
        });

        $user++;
    });

$server = new \React\Http\Server([function (\Psr\Http\Message\ServerRequestInterface $request, callable $next) use ($broadcast) {
    // lets let the people chatting see what requests are happening too.
    $broadcast->write('<i>Request: ' . $request->getUri()->getPath() . '</i>');

    return $next($request);
},
    $ws,
    function (\Psr\Http\Message\ServerRequestInterface $request, callable $next) {
        $request = $request->withHeader('Request-Time', time());

        return $next($request);
    },
    $callback]);
$socket = new React\Socket\Server('0.0.0.0:' . $_SERVER['PORT'], $loop);
$server->on('error', function (Exception $e) {
    echo 'Error: ' . $e->__toString() . PHP_EOL;
});
$server->listen($socket);

$loop->run();