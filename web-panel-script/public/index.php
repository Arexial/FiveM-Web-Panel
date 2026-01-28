<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$router = new App\Lib\Router();

$router->get('/', [App\Controllers\HomeController::class, 'index']);
$router->get('/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->get('/auth/discord', [App\Controllers\AuthController::class, 'login']);
$router->get('/auth/callback', [App\Controllers\AuthController::class, 'callback']);
$router->post('/logout', [App\Controllers\AuthController::class, 'logout']);
$router->get('/profile-stats', [App\Controllers\AuthController::class, 'profileStats']);
$router->post('/player-action', [App\Controllers\AdminController::class, 'playerAction']);
$router->get('/players', [App\Controllers\PlayersController::class, 'index']);
$router->get('/database', [App\Controllers\PlayersController::class, 'all']);
$router->get('/logs', [App\Controllers\LogsController::class, 'index']);
$router->post('/api/logs/ingest', [App\Controllers\ApiController::class, 'ingest']);
$router->post('/api/actions/pull', [App\Controllers\ApiController::class, 'pullActions']);
$router->post('/api/actions/complete', [App\Controllers\ApiController::class, 'completeAction']);
$router->post('/api/ban-check', [App\Controllers\ApiController::class, 'banCheck']);

$router->dispatch();
