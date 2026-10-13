<?php
declare(strict_types=1);
// Front controller: bootstrap the application and delegate the request.
set_exception_handler(static function (Throwable $error): void {
    error_log((string)$error);
    http_response_code(500);
    echo 'Unable to process request. Please try again.';
});
$router = require __DIR__ . '/../app/bootstrap.php';
$router->dispatch(new App\Core\Request($_GET,$_POST,$_SERVER['REQUEST_METHOD']));
