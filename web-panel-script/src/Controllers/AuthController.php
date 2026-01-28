<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Config;
use App\Lib\Db;
use App\Lib\HttpClient;

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('login', ['error' => $error]);
    }

    public function login(): void
    {
        $clientId = (string)Config::get('discord.client_id', '');
        $redirectUri = (string)Config::get('discord.redirect_uri', '');
        $scopes = (string)Config::get('discord.scopes', 'identify');

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopes,
            'state' => $state,
        ]);

        header('Location: https://discord.com/api/oauth2/authorize?' . $query);
        exit;
    }

    public function callback(): void
    {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $expectedState = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (!$code || !$state || !$expectedState || $state !== $expectedState) {
            http_response_code(400);
            echo 'Gecersiz OAuth durumu.';
            return;
        }

        $clientId = (string)Config::get('discord.client_id', '');
        $clientSecret = (string)Config::get('discord.client_secret', '');
        $redirectUri = (string)Config::get('discord.redirect_uri', '');

        $tokenResponse = HttpClient::postForm('https://discord.com/api/oauth2/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        $tokenData = json_decode($tokenResponse['body'], true);
        if (!is_array($tokenData) || empty($tokenData['access_token'])) {
            http_response_code(400);
            echo 'Token alinamadi.';
            return;
        }

        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? '';
        $expiresIn = (int)($tokenData['expires_in'] ?? 0);

        $userResponse = HttpClient::get('https://discord.com/api/users/@me', [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $userData = json_decode($userResponse['body'], true);
        if (!is_array($userData) || empty($userData['id'])) {
            http_response_code(400);
            echo 'Discord kullanicisi alinamadi.';
            return;
        }

        $db = Db::pdo();
        $discordId = $userData['id'];
        $username = $userData['username'] ?? 'Bilinmiyor';
        $avatar = $userData['avatar'] ?? null;

        $role = $this->resolveRoleForUser($discordId);
        if ($role === null) {
            $_SESSION['login_error'] = 'Yetkin yok. Discord rollerini kontrol et.';
            header('Location: /login');
            exit;
        }

        $existing = $db->prepare('SELECT id FROM users WHERE discord_id = :discord_id');
        $existing->execute(['discord_id' => $discordId]);
        $userId = $existing->fetchColumn();

        if ($userId) {
            $update = $db->prepare(
                'UPDATE users SET username = :username, avatar = :avatar, role = :role, access_token = :access_token, '
                . 'refresh_token = :refresh_token, token_expires_at = DATE_ADD(NOW(), INTERVAL :expires SECOND), '
                . 'updated_at = NOW() WHERE id = :id'
            );
            $update->execute([
                'username' => $username,
                'avatar' => $avatar,
                'role' => $role,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires' => $expiresIn,
                'id' => $userId,
            ]);
        } else {
            $insert = $db->prepare(
                'INSERT INTO users (discord_id, username, avatar, role, access_token, refresh_token, token_expires_at, created_at, updated_at) '
                . 'VALUES (:discord_id, :username, :avatar, :role, :access_token, :refresh_token, DATE_ADD(NOW(), INTERVAL :expires SECOND), NOW(), NOW())'
            );
            $insert->execute([
                'discord_id' => $discordId,
                'username' => $username,
                'avatar' => $avatar,
                'role' => $role,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires' => $expiresIn,
            ]);
            $userId = (int)$db->lastInsertId();
        }

        Auth::login([
            'id' => $userId,
            'discord_id' => $discordId,
            'username' => $username,
            'avatar' => $avatar,
            'role' => $role,
        ]);

        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
        exit;
    }

    public function profileStats(): void
    {
        Auth::requireLogin();

        $db = Db::pdo();
        $userId = Auth::user()['id'];

        $searchCountStmt = $db->prepare(
            'SELECT COUNT(*) FROM (SELECT id FROM logs WHERE type = "search" AND player_id = :player_id '
            . 'ORDER BY created_at DESC LIMIT 200) AS sub'
        );
        $searchCountStmt->execute(['player_id' => $userId]);
        $searchCount = (int)$searchCountStmt->fetchColumn();

        $lastSearchStmt = $db->prepare(
            'SELECT created_at FROM logs WHERE player_id = :player_id AND type = "search" ORDER BY created_at DESC LIMIT 1'
        );
        $lastSearchStmt->execute(['player_id' => $userId]);
        $lastSearch = $lastSearchStmt->fetchColumn();

        $loginAt = Auth::user()['login_at'] ?? date('Y-m-d H:i:s');
        $role = Auth::user()['role'] ?? 'staff';

        header('Content-Type: application/json');
        echo json_encode([
            'searchCount' => $searchCount,
            'lastSearch' => $lastSearch ?: 'Hic',
            'loginAt' => $loginAt,
            'role' => $role,
        ]);
    }

    private function resolveRoleForUser(string $discordId): ?string
    {
        return Auth::resolveRoleForUser($discordId);
    }
}
