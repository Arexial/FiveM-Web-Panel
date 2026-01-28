<?php
declare(strict_types=1);

namespace App\Lib;

use App\Lib\Db;
use App\Lib\HttpClient;

final class Auth
{
    private const ROLE_ORDER = [
        'staff' => 1,
        'admin' => 2,
        'owner' => 3,
    ];

    private static bool $validated = false;
    private static array $roleCache = [];
    private const CACHE_TTL = 200;

    public static function check(): bool
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }

        if (!self::$validated) {
            self::$validated = true;
            return self::validateCurrentPermissions();
        }

        return true;
    }

    private static function validateCurrentPermissions(): bool
    {
        $user = $_SESSION['user'];
        $discordId = $user['discord_id'] ?? null;
        
        if (!$discordId) {
            self::logout();
            return false;
        }

        $currentRole = self::resolveRoleForUser($discordId);
        
        if ($currentRole === null) {
            self::logout();
            return false;
        }

        if ($currentRole !== $user['role']) {
            $_SESSION['user']['role'] = $currentRole;

            $db = Db::pdo();
            $update = $db->prepare('UPDATE users SET role = :role, updated_at = NOW() WHERE discord_id = :discord_id');
            $update->execute([
                'role' => $currentRole,
                'discord_id' => $discordId,
            ]);
        }

        return true;
    }

    public static function resolveRoleForUser(string $discordId): ?string
    {
        $cacheKey = $discordId;
        if (isset(self::$roleCache[$cacheKey])) {
            $cached = self::$roleCache[$cacheKey];
            if (time() - $cached['timestamp'] < self::CACHE_TTL) {
                return $cached['role'];
            }
            unset(self::$roleCache[$cacheKey]);
        }

        $guildId = (string)Config::get('discord.guild_id', '');
        $botToken = (string)Config::get('discord.bot_token', '');
        $allowed = Config::get('discord.allowed_role_ids', []);

        if ($guildId === '' || $botToken === '' || !is_array($allowed)) {
            return null;
        }

        $memberResponse = HttpClient::get(
            'https://discord.com/api/guilds/' . $guildId . '/members/' . $discordId,
            ['Authorization: Bot ' . $botToken]
        );

        if ((int)$memberResponse['status'] !== 200) {
            return null;
        }

        $memberData = json_decode($memberResponse['body'], true);
        if (!is_array($memberData) || empty($memberData['roles']) || !is_array($memberData['roles'])) {
            return null;
        }

        $roles = $memberData['roles'];
        $order = ['owner', 'admin', 'staff'];

        foreach ($order as $key) {
            $roleIds = $allowed[$key] ?? [];
            if (!is_array($roleIds)) {
                continue;
            }
            foreach ($roleIds as $roleId) {
                if (in_array((string)$roleId, $roles, true)) {
                    self::$roleCache[$cacheKey] = [
                        'role' => $key,
                        'timestamp' => time()
                    ];
                    return $key;
                }
            }
        }
        self::$roleCache[$cacheKey] = [
            'role' => null,
            'timestamp' => time()
        ];
        return null;
    }

    public static function clearRoleCache(): void
    {
        self::$roleCache = [];
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function role(): string
    {
        $user = self::user();
        $role = is_array($user) ? ($user['role'] ?? 'staff') : 'staff';
        return is_string($role) ? $role : 'staff';
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireRole(string $minRole): void
    {
        $current = self::role();
        $currentRank = self::ROLE_ORDER[$current] ?? 0;
        $minRank = self::ROLE_ORDER[$minRole] ?? 0;

        if ($currentRank < $minRank) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}
