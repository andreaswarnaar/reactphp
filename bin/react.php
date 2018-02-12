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
    echo $request->getRequestTarget().PHP_EOL;
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

$server = new \React\Http\Server($callback);

$socket = new React\Socket\Server('0.0.0.0:' . $_SERVER['PORT'], $loop);
$server->on('error',function (Exception $e) {
    echo 'Error: ' . $e->__toString() . PHP_EOL;
});
$server->listen($socket);

$loop->run();