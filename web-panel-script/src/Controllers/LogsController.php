<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Db;

final class LogsController extends BaseController
{
    public function index(): void
    {
        Auth::requireLogin();

        $db = Db::pdo();
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $params = [];
        $sql = 'SELECT logs.id, logs.type, logs.message, logs.meta_json, logs.created_at, '
            . 'players.name AS player_name, players.license, players.citizenid, players.discord, players.server_id '
            . 'FROM logs LEFT JOIN players ON players.id = logs.player_id '
            . 'WHERE logs.created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR) ';

        if ($q !== '') {
            $sql .= 'AND (players.license LIKE :q OR players.citizenid LIKE :q OR players.discord LIKE :q OR CAST(players.server_id AS CHAR) LIKE :q) ';
            $params['q'] = '%' . $q . '%';
        }

        $sql .= 'ORDER BY logs.created_at DESC LIMIT 200';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $this->render('logs', [
            'logs' => $logs,
            'query' => $q,
        ]);
    }
}
