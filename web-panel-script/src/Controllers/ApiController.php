<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\Config;
use App\Lib\Db;

final class ApiController extends BaseController
{
    public function ingest(): void
    {
        $apiKey = (string)Config::get('api_key', '');
        $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if ($apiKey === '' || $provided !== $apiKey) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $player = $payload['player'] ?? [];
        $log = $payload['log'] ?? [];

        $name = isset($player['name']) ? trim((string)$player['name']) : null;
        $license = isset($player['license']) ? trim((string)$player['license']) : null;
        $citizenId = isset($player['citizenid']) ? trim((string)$player['citizenid']) : null;
        $discord = isset($player['discord']) ? trim((string)$player['discord']) : null;

        $type = isset($log['type']) ? trim((string)$log['type']) : '';
        $message = isset($log['message']) ? trim((string)$log['message']) : '';
        $meta = $log['meta'] ?? null;

        $serverIdRaw = $player['server_id']
            ?? ($player['serverid'] ?? ($player['serverId'] ?? null));

        if ($serverIdRaw === null) {
            $serverIdRaw = $player['sid'] ?? ($player['source'] ?? ($player['id'] ?? null));
        }

        if ($serverIdRaw === null && is_array($meta)) {
            $serverIdRaw = $meta['server_id']
                ?? ($meta['serverid'] ?? ($meta['serverId'] ?? ($meta['sid'] ?? ($meta['source'] ?? null))));
        }

        $serverId = ($serverIdRaw === null || $serverIdRaw === '') ? null : (int)$serverIdRaw;
        $online = $type === 'leave' ? 0 : 1;

        if ($type === '' || $message === '') {
            $this->json(['error' => 'Missing log type or message'], 400);
            return;
        }

        $db = Db::pdo();
        $playerId = null;

        if ($license) {
            $stmt = $db->prepare('SELECT id FROM players WHERE license = :license');
            $stmt->execute(['license' => $license]);
            $playerId = $stmt->fetchColumn();
        }

        if (!$playerId && $citizenId) {
            $stmt = $db->prepare('SELECT id FROM players WHERE citizenid = :citizenid');
            $stmt->execute(['citizenid' => $citizenId]);
            $playerId = $stmt->fetchColumn();
        }

        if ($playerId) {
            $update = $db->prepare(
                'UPDATE players SET name = :name, license = :license, citizenid = :citizenid, discord = :discord, '
                . 'server_id = COALESCE(:server_id, server_id), online = :online, last_seen = NOW() '
                . 'WHERE id = :id'
            );
            $update->execute([
                'name' => $name,
                'license' => $license,
                'citizenid' => $citizenId,
                'discord' => $discord,
                'server_id' => $serverId,
                'online' => $online,
                'id' => $playerId,
            ]);
        } else {
            $insert = $db->prepare(
                'INSERT INTO players (name, license, citizenid, discord, server_id, online, last_seen, created_at) '
                . 'VALUES (:name, :license, :citizenid, :discord, :server_id, :online, NOW(), NOW())'
            );
            $insert->execute([
                'name' => $name,
                'license' => $license,
                'citizenid' => $citizenId,
                'discord' => $discord,
                'server_id' => $serverId,
                'online' => $online,
            ]);
            $playerId = (int)$db->lastInsertId();
        }

        $insertLog = $db->prepare(
            'INSERT INTO logs (player_id, type, message, meta_json, created_at) '
            . 'VALUES (:player_id, :type, :message, :meta_json, NOW())'
        );

        $metaForDb = $meta;
        if ($serverId === null) {
            if (is_array($metaForDb)) {
                $metaForDb['_player'] = $player;
            } else {
                $metaForDb = [
                    '_meta' => $metaForDb,
                    '_player' => $player,
                ];
            }
        }

        $insertLog->execute([
            'player_id' => $playerId ?: null,
            'type' => $type,
            'message' => $message,
            'meta_json' => $metaForDb !== null ? json_encode($metaForDb) : null,
        ]);

        $this->json(['status' => 'ok']);
    }

    public function pullActions(): void
    {
        $apiKey = (string)Config::get('api_key', '');
        $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if ($apiKey === '' || $provided !== $apiKey) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $serverId = isset($payload['server_id']) ? (int)$payload['server_id'] : null;
        if (!$serverId) {
            $this->json(['error' => 'Missing server_id'], 400);
            return;
        }

        $db = Db::pdo();
        $stmt = $db->prepare(
            'SELECT a.id, a.action, a.reason, p.server_id, p.id AS player_id '
            . 'FROM admin_actions a INNER JOIN players p ON p.id = a.player_id '
            . 'WHERE a.status = "pending" AND p.server_id = :server_id '
            . 'ORDER BY a.created_at ASC'
        );
        $stmt->execute(['server_id' => $serverId]);
        $actions = $stmt->fetchAll();

        $this->json(['actions' => $actions]);
    }

    public function completeAction(): void
    {
        $apiKey = (string)Config::get('api_key', '');
        $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if ($apiKey === '' || $provided !== $apiKey) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $actionId = isset($payload['action_id']) ? (int)$payload['action_id'] : 0;
        $status = isset($payload['status']) ? trim((string)$payload['status']) : 'done';

        if ($actionId <= 0) {
            $this->json(['error' => 'Missing action_id'], 400);
            return;
        }

        $db = Db::pdo();
        $stmt = $db->prepare('UPDATE admin_actions SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status !== '' ? $status : 'done',
            'id' => $actionId,
        ]);

        $this->json(['status' => 'ok']);
    }

    public function banCheck(): void
    {
        $apiKey = (string)Config::get('api_key', '');
        $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if ($apiKey === '' || $provided !== $apiKey) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $this->json(['error' => 'Invalid JSON'], 400);
            return;
        }

        $license = isset($payload['license']) ? trim((string)$payload['license']) : null;
        $citizenId = isset($payload['citizenid']) ? trim((string)$payload['citizenid']) : null;
        $discord = isset($payload['discord']) ? trim((string)$payload['discord']) : null;

        if (!$license && !$citizenId && !$discord) {
            $this->json(['banned' => false]);
            return;
        }

        $db = Db::pdo();
        $stmt = $db->prepare(
            'SELECT banned, ban_reason FROM players WHERE banned = 1 AND banned_at IS NOT NULL AND '
            . '(license = :license OR citizenid = :citizenid OR discord = :discord) LIMIT 1'
        );
        $stmt->execute([
            'license' => $license,
            'citizenid' => $citizenId,
            'discord' => $discord,
        ]);

        $row = $stmt->fetch();
        if ($row) {
            $this->json(['banned' => true, 'reason' => $row['ban_reason'] ?? '']);
            return;
        }

        $this->json(['banned' => false]);
    }
}
