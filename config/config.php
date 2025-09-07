<?php
declare(strict_types=1);

/**
 * Configuration globale (mode dev, session, autoload, helpers, DB, BASE_URL)
 */

/* -------------------- Mode dev & erreurs -------------------- */
const IN_DEV = true; // passe à false en prod
ini_set('display_errors', IN_DEV ? '1' : '0');
error_reporting(IN_DEV ? E_ALL : (E_ALL & ~E_NOTICE & ~E_DEPRECATED));
date_default_timezone_set('Europe/Paris');

/* -------------------- Session -------------------- */
session_name('idiricoaching_sess');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------- BASE_URL dynamique --------------------
   Exemple:
   - XAMPP (dossier public) => /idiricoaching__mvc/public/
   - VirtualHost (DocumentRoot=public) => /
-------------------------------------------------------------- */
$__scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($__scriptDir === '' || $__scriptDir === '.') {
    $__scriptDir = '/';
} else {
    $__scriptDir .= '/';
}
define('BASE_URL', $__scriptDir);

/* -------------------- Connexion DB -------------------- */
const DB_HOST    = '127.0.0.1';
const DB_PORT    = '3306';
const DB_NAME    = 'coaching_db';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

/* -------------------- Autoload très simple -------------------- */
/* Mappe le namespace App\ vers le dossier app/ */
spl_autoload_register(function (string $class): void {
    // équivalent de str_starts_with($class, 'App\\') compatible <8.0 :
    if (strncmp($class, 'App\\', 4) === 0) {
        $path = __DIR__ . '/../' . str_replace('App\\', 'app/', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (is_file($path)) {
            require $path;
        }
    }
});

/* -------------------- Helpers vue/CSRF/flash -------------------- */
/** Échappement HTML sécurisé */
function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** CSRF token (création/lecture) */
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Champ hidden à insérer dans les formulaires POST */
function csrf_input(): string {
    return '<input type="hidden" name="_token" value="'.e(csrf_token()).'">';
}

/** Vérification du token CSRF reçu en POST */
function csrf_verify(?string $token): bool {
    return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

/** Stocker un message flash (success|error|info|warning) */
function flash(string $type, string $msg): void {
    $_SESSION['flash'][$type][] = $msg;
}

/** Récupérer + vider tous les messages flash */
function flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
