<?php
declare(strict_types=1);
namespace App\Core;
abstract class Controller
{
    protected function redirect(string $page = 'dashboard'): never
    {
        header('Location: ?page=' . $page);
        exit;
    }
}
