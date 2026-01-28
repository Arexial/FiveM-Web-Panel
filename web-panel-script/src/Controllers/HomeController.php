<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Db;

final class HomeController extends BaseController
{
    public function index(): void
    {
        Auth::requireLogin();

        $db = Db::pdo();
        $playerCount = (int)$db->query('SELECT COUNT(*) FROM players')->fetchColumn();
        $logCount = (int)$db->query('SELECT COUNT(*) FROM logs')->fetchColumn();
        $recentLogs = $db->query(
            'SELECT logs.id, logs.type, logs.message, logs.created_at, players.name AS player_name '
            . 'FROM logs LEFT JOIN players ON players.id = logs.player_id '
            . 'ORDER BY logs.created_at DESC LIMIT 10'
        )->fetchAll();
        $lastLogAt = $db->query('SELECT created_at FROM logs ORDER BY created_at DESC LIMIT 1')->fetchColumn();
        $lastPlayer = $db->query(
            'SELECT name, last_seen FROM players ORDER BY last_seen DESC, created_at DESC LIMIT 1'
        )->fetch();
        $activePlayerCount = (int)$db->query('SELECT COUNT(*) FROM players WHERE online = 1')->fetchColumn();

        $this->render('dashboard', [
            'playerCount' => $playerCount,
            'activePlayerCount' => $activePlayerCount,
            'logCount' => $logCount,
            'recentLogs' => $recentLogs,
            'lastLogAt' => $lastLogAt ?: 'No data',
            'lastPlayer' => $lastPlayer ?: ['name' => 'No data', 'last_seen' => null],
        ]);
    }
}
