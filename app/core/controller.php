<?php
declare(strict_types=1);

namespace App\core;

abstract class Controller
{
    protected function render(string $view, array $params = []): void
    {
        extract($params, EXTR_OVERWRITE);
        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/main.php';
    }

    protected function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }

    protected function isLogged(): bool { return isset($_SESSION['user']); }
}
