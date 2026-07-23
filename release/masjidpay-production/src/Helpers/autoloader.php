<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'Controllers\\' => __DIR__ . '/../Controllers/',
        'Models\\' => __DIR__ . '/../Models/',
        'Services\\' => __DIR__ . '/../Services/',
        'Middleware\\' => __DIR__ . '/../Middleware/',
        'Helpers\\' => __DIR__ . '/../Helpers/',
        'Components\\' => __DIR__ . '/../Components/'
    ];
    foreach ($prefixes as $prefix => $base_dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $base_dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});