<?php
declare(strict_types=1);

namespace App\controllers;

use App\core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home', ['title' => 'IdiriCoaching — Accueil']);
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->render('home', ['title' => 'Page introuvable']);
    }

    public function error(\Throwable $e): void
    {
        http_response_code(500);
        $this->render('home', ['title' => 'Erreur serveur']);
        if (IN_DEV) {
            echo '<pre style="color:#f88;">' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
        }
    }
}
