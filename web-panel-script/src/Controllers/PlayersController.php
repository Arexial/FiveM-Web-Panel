<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Db;

final class PlayersController extends BaseController
{
    private function logSearch(string $query): void
    {
        $db = Db::pdo();
        $user = Auth::user();
        $stmt = $db->prepare(
            'INSERT INTO logs (type, message, player_id, created_at) VALUES (:type, :message, :player_id, NOW())'
        );
        $stmt->execute([
            'type' => 'search',
            'message' => $query,
            'player_id' => $user['id'],
        ]);
        $db->exec(
            'DELETE FROM logs WHERE type = "search" AND id NOT IN ('
            . 'SELECT id FROM (SELECT id FROM logs WHERE type = "search" ORDER BY created_at DESC LIMIT 200) AS t'
            . ')'
        );
    }

    public function index(): void
    {
        Auth::requireLogin();

        $db = Db::pdo();
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $by = isset($_GET['by']) ? trim((string)$_GET['by']) : 'all';
        $params = [];
        $sql = 'SELECT id, name, license, citizenid, discord, server_id, online, banned, last_seen, created_at '
            . 'FROM players WHERE online = 1';

        if ($q !== '') {
            if ($by === 'sid') {
                $sql .= ' AND TRIM(CAST(server_id AS CHAR)) = :sid_str';
                $params['sid_str'] = $q;
            } elseif ($by === 'name') {
                $sql .= ' AND name LIKE :q';
                $params['q'] = '%' . $q . '%';
            } elseif ($by === 'license') {
                $sql .= ' AND license LIKE :q';
                $params['q'] = '%' . $q . '%';
            } elseif ($by === 'discord') {
                $sql .= ' AND discord LIKE :q';
                $params['q'] = '%' . $q . '%';
            } elseif ($by === 'citizenid') {
                $sql .= ' AND citizenid LIKE :q';
                $params['q'] = '%' . $q . '%';
            } else {
                $sql .= ' AND (name LIKE :q OR license LIKE :q OR discord LIKE :q OR citizenid LIKE :q '
                    . 'OR CAST(server_id AS CHAR) LIKE :q)';
                $params['q'] = '%' . $q . '%';
            }
            $this->logSearch($q);
        }

        $sql .= ' ORDER BY last_seen DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $players = $stmt->fetchAll();

        $this->render('players', [
            'players' => $players,
            'query' => $q,
            'by' => $by,
        ]);
    }

    public function all(): void
    {
        Auth::requireLogin();

        $db = Db::pdo();
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $by = isset($_GET['by']) ? trim((string)$_GET['by']) : 'all';
        $params = [];
        $sql = 'SELECT id, name, license, citizenid, discord, server_id, online, banned, last_seen, created_at '
            . 'FROM players';

        if ($q !== '') {
            if ($by === 'sid') {
                $sql .= ' WHERE TRIM(CAST(server_id AS CHAR)) = :sid_str';
                $params['sid_str'] = $q;
            } elseif ($by === 'name') {
                $sql .= ' WHERE name LIKE :q';
                $params['q'] = '%' . $q . '%';
            } elseif ($by === 'license') {
                $sql .= ' WHERE license LIKE :q';
                $params['q'] = '%' . $q . '%';
            } elseif ($by === 'discord') {
                $sql .= ' WHERE discord LIKE :q';
                $params['q'] = '%' . $q . '%';
            } elseif ($by === 'citizenid') {
                $sql .= ' WHERE citizenid LIKE :q';
                $params['q'] = '%' . $q . '%';
            } else {
                $sql .= ' WHERE name LIKE :q OR license LIKE :q OR discord LIKE :q OR citizenid LIKE :q';
                $params['q'] = '%' . $q . '%';
            }
            $this->logSearch($q);
        }

        $sql .= ' ORDER BY last_seen DESC, created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $players = $stmt->fetchAll();

        $this->render('database', [
            'players' => $players,
            'query' => $q,
            'by' => $by,
        ]);
    }
}
