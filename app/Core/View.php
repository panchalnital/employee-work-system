<?php
declare(strict_types=1);
namespace App\Core;
final class View
{
    private const PAGES = ['login', 'register', 'dashboard', 'tasks', 'updates', 'reports'];
    public function render(string $page, array $data): void
    {
        if (!in_array($page, self::PAGES, true)) throw new \LogicException('Unknown view.');
        extract($data, EXTR_SKIP);
        $template = __DIR__ . '/../Views/' . $page . '.php';
        require __DIR__ . '/../Views/layout.php';
    }
}
