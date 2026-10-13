<?php
declare(strict_types=1);
require __DIR__ . '/autoload.php';
require __DIR__ . '/helpers.php';
$config = require __DIR__ . '/../app/config.php';
date_default_timezone_set($config['timezone']);
// Composition root: instantiate dependencies once and inject them explicitly.
$db = new App\Core\Database($config);
$session = new App\Core\Session();
$users = new App\Models\User($db);
$tasks = new App\Models\Task($db);
$updates = new App\Models\DailyUpdate($db);
$reports = new App\Models\Report($db);
$authService = new App\Services\AuthService($db,$users,new App\Models\LoginAttempt($db));
$taskService = new App\Services\TaskService($db,$tasks,$users);
if (PHP_SAPI !== 'cli') {
    $session->start((bool)$config['secure_cookie']);
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}
return new App\Core\Router(
    $session,$users,new App\Core\View(),
    new App\Controllers\AuthController($authService,$session),
    new App\Controllers\DashboardController($tasks),
    new App\Controllers\TaskController($tasks,$users,$taskService,$session),
    new App\Controllers\DailyUpdateController($updates,$session),
    new App\Controllers\ReportController($reports,new App\Services\PerformanceService())
);
