<?php
declare(strict_types=1);
namespace App\Core;
use DomainException;
final class Session
{
    public function start(bool $secure): void
    {
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax']);
        session_start();
        if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['last_seen'] = time();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }
    public function userId(): ?int { return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; }
    public function validateCsrf(mixed $token): void
    {
        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) throw new DomainException('Your session changed. Reload and try again.');
    }
    public function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $this->rotateToken();
    }
    public function logout(): void { $_SESSION = []; session_regenerate_id(true); }
    public function rotateToken(): void { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    public function flash(string $message): void { $_SESSION['flash'] = $message; }
    public function takeFlash(): ?string
    {
        $message = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $message;
    }
}
