<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Db;

final class AdminController extends BaseController
{
    public function playerAction(): void
    {
        Auth::requireLogin();

        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $this->json(['error' => 'Invalid payload'], 400);
            return;
        }

        $playerId = isset($payload['player_id']) ? (int)$payload['player_id'] : 0;
        $action = isset($payload['action']) ? trim((string)$payload['action']) : '';
        $reason = isset($payload['reason']) ? trim((string)$payload['reason']) : null;

        if ($playerId <= 0 || $action === '') {
            $this->json(['error' => 'Missing player_id or action'], 400);
            return;
        }

        $db = Db::pdo();
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        switch ($action) {
            case 'kick':
                Auth::requireRole('admin');
                $this->queueAction($db, $playerId, 'kick', $reason, $userId);
                $this->json(['status' => 'queued']);
                return;
            case 'ban':
                Auth::requireRole('admin');
                $this->setBan($db, $playerId, 1, $reason, $userId);
                $this->queueAction($db, $playerId, 'ban', $reason, $userId);
                $this->json(['status' => 'banned']);
                return;
            case 'unban':
                Auth::requireRole('admin');
                $this->setBan($db, $playerId, 0, $reason, $userId);
                $this->queueAction($db, $playerId, 'unban', $reason, $userId);
                $this->json(['status' => 'unbanned']);
                return;            
        }
    }

    private function setBan($db, int $playerId, int $banned, ?string $reason, int $userId): void
    {
        $stmt = $db->prepare(
            'UPDATE players SET banned = :banned, ban_reason = :reason, banned_by = :banned_by, '
            . 'banned_at = IF(:banned = 1, NOW(), NULL) WHERE id = :id'
        );
        $stmt->execute([
            'banned' => $banned,
            'reason' => $reason,
            'banned_by' => $banned ? $userId : null,
            'id' => $playerId,
        ]);
    }

    private function queueAction($db, int $playerId, string $action, ?string $reason, int $userId): void
    {
        $stmt = $db->prepare(
            'INSERT INTO admin_actions (player_id, action, reason, created_by, status, created_at) '
            . 'VALUES (:player_id, :action, :reason, :created_by, "pending", NOW())'
        );
        $stmt->execute([
            'player_id' => $playerId,
            'action' => $action,
            'reason' => $reason,
            'created_by' => $userId,
        ]);
    }
}
