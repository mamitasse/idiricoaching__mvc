<?php
declare(strict_types=1);

/**
 * Autoloader PSR-4 simple 
 * Mappe les namespaces de l'app vers les dossiers correspondants.
 */
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\core\\'        => __DIR__ . '/../app/core/',
        'App\\controllers\\' => __DIR__ . '/../app/controllers/',
        'App\\entities\\'    => __DIR__ . '/../app/entities/',
        'App\\managers\\'    => __DIR__ . '/../app/managers/',
        'App\\views\\'       => __DIR__ . '/../app/views/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});
