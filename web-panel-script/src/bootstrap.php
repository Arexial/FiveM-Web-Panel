<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = require __DIR__ . '/../config.php';

require __DIR__ . '/Lib/Config.php';
require __DIR__ . '/Lib/Session.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
        return;
    }

    $fallback = __DIR__ . '/' . strtolower(str_replace('\\', '/', $relative)) . '.php';
    if (file_exists($fallback)) {
        require $fallback;
    }
});

App\Lib\Config::init($config);
App\Lib\Session::start();
