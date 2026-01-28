<?php
return [
    'server_name' => 'SERVER NAME',
    'app_env' => 'dev',
    'app_url' => 'WEBSITE URL',
    'session_name' => 'fivem_panel',
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=db_name_arexial;charset=utf8mb4',
        'user' => 'arexial_user',
        'pass' => '1234',
    ],
    'discord' => [
        'client_id' => 'DISCORD BOT CLIENT ID',
        'client_secret' => 'DISCORD BOT SECRET ID',
        'redirect_uri' => 'https://WEBSITE_URL/auth/callback',
        'scopes' => 'identify',
        'guild_id' => 'SERVER ID',
        'bot_token' => 'BOT TOKEN',
        'allowed_role_ids' => [
            'owner' => ['OWNER ROLE ID'],
            'admin' => ['ADMIN ROLE ID'],
            'staff' => ['STAFF ROLE ID'],
        ],
    ],
    'api_key' => 'API KEY',
];
