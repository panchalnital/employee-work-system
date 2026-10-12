<?php

declare(strict_types=1);
$config = require __DIR__ . '/../app/config.php';
date_default_timezone_set($config['timezone']);
function db(): PDO
{
    global $config;
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement
{
    $q = db()->prepare($sql);
    $q->execute($params);
    return $q;
}
function e(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function field(string $key, int $max = 200, bool $required = true): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) throw new DomainException('Invalid input.');
    $v = trim($v);
    if (($required && $v === '') || strlen($v) > $max) throw new DomainException("Invalid $key (maximum $max bytes).");
    return $v;
}
function validDate(string $v): string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) throw new DomainException('Invalid date.');
    return $v;
}
function score(int $total, int $completed, int $onTime): ?float
{
    return $total === 0 ? null : round(100 * (0.6 * $completed + 0.4 * $onTime) / $total, 1);
}
function csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function redirect(string $page = 'dashboard'): never
{
    header('Location: ?page=' . $page);
    exit;
}
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => $config['secure_cookie'], 'samesite' => 'Lax']);
    session_start();
    if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) $_SESSION = [];
    $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}
