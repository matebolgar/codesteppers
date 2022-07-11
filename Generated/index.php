<?php

require '../../vendor/autoload.php';

use CodeSteppers\Generated\Route\Subscriber;
use CodeSteppers\Generated\Route\Codestepper;
use CodeSteppers\Generated\Route\Order;

use CodeSteppers\Generated\ValidationError;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Auth\AuthException;
use CodeSteppers\Generated\Auth\ExpiredException;
use CodeSteppers\Generated\Request;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header('Access-Control-Allow-Headers: Origin, Pragma, Cache-control, X-Requested-With, Content-Type, Accept, Authorization');



$conn = new mysqli(
    $_SERVER['MYSQL_SERVER'],
    $_SERVER['MYSQL_USERNAME'],
    $_SERVER['MYSQL_PASSWORD'],
    $_SERVER['MYSQL_DATABASENAME'],
    $_SERVER['MYSQL_SERVER_PORT']
);

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) use ($conn) {

    try {

      

     \CodeSteppers\Generated\Route\Auth\Auth::getRoutes($r, $conn);

     if (method_exists('\CodeSteppers\Router', 'registerRoutes')) {
        (new \CodeSteppers\Router())->registerRoutes($r, $conn);
    }

    } catch (Error $e) {
        var_dump($e->getMessage());
        http_response_code(500);
        header("Content-Type: application/json");
        echo '{"error": "server error"}';
        exit;
    }
});

switchRoute($dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), $conn);

function switchRoute(array $routeInfo, mysqli $conn)
{
    switch ($routeInfo[0]) {
        case Dispatcher::NOT_FOUND:
            if (method_exists('\CodeSteppers\Router', 'registerNotFoundRoute')) {
                (new \CodeSteppers\Router())->registerNotFoundRoute($conn);
            } else {
                echo json_encode(["error" => "not found"]);
            }
            break;
        case Dispatcher::METHOD_NOT_ALLOWED:
            echo json_encode(["error" => "method not allowed. allowed methods: " . implode(", ", $routeInfo[1])]);
            break;
        case Dispatcher::FOUND:
            parse_str($_SERVER['QUERY_STRING'], $query);
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $path = parse_url($_SERVER['REQUEST_URI'])['path'];
                $req = (new Request());
                $req->query = $query;
                $req->vars = $routeInfo[2];
                $req->body = $data ?? $_POST;
                $req->files = $_FILES;
                $req->connection = $conn;
                $req->path = $path;
                echo call_user_func($routeInfo[1], $req);
            } catch (ValidationError $err) {
                http_response_code(400);
                echo json_encode($err);
            } catch (OperationError $err) {
                http_response_code(400);
                echo json_encode($err);
            } catch (AuthException $err) {
                http_response_code(401);
                echo json_encode($err);
                exit;
            } catch (ExpiredException $err) {
                http_response_code(401);
                echo json_encode($err);
                exit;
            } finally {
                $conn->close();
            }
            break;
    }
}

