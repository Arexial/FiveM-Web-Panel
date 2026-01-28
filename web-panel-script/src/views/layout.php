<?php
use App\Lib\Auth;
use App\Lib\Config;
use App\Lib\Util;

$user = Auth::user();
$role = Auth::role();
$avatarUrl = null;
if (!empty($user['avatar']) && !empty($user['discord_id'])) {
    $avatarUrl = 'https://cdn.discordapp.com/avatars/' . $user['discord_id'] . '/' . $user['avatar'] . '.png?size=64';
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FiveM Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php $cssVersion = @filemtime(__DIR__ . '/../../public/assets/app.css') ?: time(); ?>
    <link rel="stylesheet" href="/assets/app.css?v=<?php echo (int)$cssVersion; ?>">
</head>
<body>
    <header class="topbar">
        <div class="brand"><?php echo Util::e((string)Config::get('server_name')); ?></div>
        <nav class="nav">
            <?php if (Auth::check()): ?>
                <a href="/">Gosterge Paneli</a>
                <a href="/players">Aktif Oyuncular</a>
                <a href="/database">Tum Oyuncular</a>
                <a href="/logs">Loglar</a>
                <form method="post" action="/logout" class="logout-form">
                    <button type="submit">Çıkış</button>
                </form>
                <div class="user-chip" id="profileChip" style="cursor:pointer;">
                    <?php if ($avatarUrl): ?>
                        <img class="avatar" src="<?php echo Util::e($avatarUrl); ?>" alt="User avatar">
                    <?php else: ?>
                        <div class="avatar fallback"><?php echo Util::e(substr((string)($user['username'] ?? 'U'), 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="user"><?php echo Util::e($user['username'] ?? ''); ?></div>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <?php echo $content ?? ''; ?>
    </main>

    <div class="modal" id="playerModal" aria-hidden="true">
        <div class="modal-card">
            <button class="modal-close" type="button" aria-label="Close">x</button>
            <h3>Oyuncu Detaylari</h3>
            <div class="modal-status">
                <span class="status status-online" data-field="status">Cevrimici</span>
            </div>
            <div class="modal-grid">
                <div class="modal-item">
                    <span class="detail-label">Isim</span>
                    <div class="modal-row">
                        <span data-field="name"></span>
                        <button class="copy-btn" data-copy="name" type="button">Kopyala</button>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">License</span>
                    <div class="modal-row">
                        <span data-field="license"></span>
                        <button class="copy-btn" data-copy="license" type="button">Kopyala</button>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Discord</span>
                    <div class="modal-row">
                        <span data-field="discord"></span>
                        <button class="copy-btn" data-copy="discord" type="button">Kopyala</button>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Citizen ID</span>
                    <div class="modal-row">
                        <span data-field="citizenid"></span>
                        <button class="copy-btn" data-copy="citizenid" type="button">Kopyala</button>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Server ID</span>
                    <div class="modal-row">
                        <span data-field="serverid"></span>
                        <button class="copy-btn" data-copy="serverid" type="button">Kopyala</button>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Son Gorulme</span>
                    <div class="modal-row">
                        <span data-field="lastseen"></span>
                        <button class="copy-btn" data-copy="lastseen" type="button">Kopyala</button>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Olusturma</span>
                    <div class="modal-row">
                        <span data-field="created"></span>
                        <button class="copy-btn" data-copy="created" type="button">Kopyala</button>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <div class="modal-inputs">
                    <input type="text" class="input" id="actionReason" placeholder="Sebep (opsiyonel)">
                </div>
                <div class="modal-buttons">
                    <?php if (in_array($role, ['admin', 'owner'], true)): ?>
                        <button class="button-outline" data-action="kick" type="button">At</button>
                        <button class="button-outline" data-action="ban" type="button">Ban</button>
                        <button class="button-outline" data-action="unban" type="button">Ban Kaldir</button>
                    <?php endif; ?>
                </div>
                <div class="modal-hint" id="actionResult"></div>
            </div>
        </div>
    </div>

    <div class="modal" id="profileModal" aria-hidden="true">
        <div class="modal-card">
            <button class="modal-close" type="button" aria-label="Close">x</button>
            <h3>Profil Istatistikleri</h3>
            <div class="modal-grid">
                <div class="modal-item">
                    <span class="detail-label">Arama Sayisi</span>
                    <div class="modal-row">
                        <span data-profile="searchCount">-</span>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Son Arama</span>
                    <div class="modal-row">
                        <span data-profile="lastSearch">-</span>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Giris Zamani</span>
                    <div class="modal-row">
                        <span data-profile="loginAt">-</span>
                    </div>
                </div>
                <div class="modal-item">
                    <span class="detail-label">Rol</span>
                    <div class="modal-row">
                        <span data-profile="role">Staff</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var modal = document.getElementById('playerModal');
        var modalClose = modal.querySelector('.modal-close');
        var fieldTargets = {};
        modal.querySelectorAll('[data-field]').forEach(function (el) {
            fieldTargets[el.getAttribute('data-field')] = el;
        });
        var actionResult = document.getElementById('actionResult');
        var actionReason = document.getElementById('actionReason');
        var actionNote = document.getElementById('actionNote');
        var activePlayerId = null;

        var profileModal = document.getElementById('profileModal');
        var profileModalClose = profileModal.querySelector('.modal-close');
        var profileTargets = {};
        profileModal.querySelectorAll('[data-profile]').forEach(function (el) {
            profileTargets[el.getAttribute('data-profile')] = el;
        });

        var profileChip = document.getElementById('profileChip');
        if (profileChip) {
            profileChip.addEventListener('click', function () {
                fetch('/profile-stats')
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        profileTargets.searchCount.textContent = data.searchCount || '0';
                        profileTargets.lastSearch.textContent = data.lastSearch || '-';
                        profileTargets.loginAt.textContent = data.loginAt || '-';
                        profileTargets.role.textContent = data.role || 'staff';
                        profileModal.classList.add('is-open');
                        profileModal.setAttribute('aria-hidden', 'false');
                    })
                    .catch(function () {
                        profileTargets.searchCount.textContent = 'Hata';
                        profileTargets.lastSearch.textContent = 'Hata';
                        profileTargets.loginAt.textContent = 'Hata';
                        profileTargets.role.textContent = 'Hata';
                        profileModal.classList.add('is-open');
                        profileModal.setAttribute('aria-hidden', 'false');
                    });
            });
        }

        profileModalClose.addEventListener('click', function () {
            profileModal.classList.remove('is-open');
            profileModal.setAttribute('aria-hidden', 'true');
        });

        profileModal.addEventListener('click', function (event) {
            if (event.target === profileModal) {
                profileModal.classList.remove('is-open');
                profileModal.setAttribute('aria-hidden', 'true');
            }
        });

        document.querySelectorAll('.row-click').forEach(function (row) {
            row.addEventListener('click', function () {
                var data = row.dataset;
                Object.keys(fieldTargets).forEach(function (key) {
                    fieldTargets[key].textContent = data[key] || '';
                });
                activePlayerId = data.id || null;
                if (data.banned === '1') {
                    fieldTargets.status.textContent = 'Yasakli';
                    fieldTargets.status.className = 'status status-banned';
                } else if (data.online === '1') {
                    fieldTargets.status.textContent = 'Cevrimici';
                    fieldTargets.status.className = 'status status-online';
                } else {
                    fieldTargets.status.textContent = 'Cevrimdisi';
                    fieldTargets.status.className = 'status status-offline';
                }
                actionResult.textContent = '';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        modalClose.addEventListener('click', function () {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }
        });

        document.querySelectorAll('.copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var key = btn.getAttribute('data-copy');
                var value = fieldTargets[key] ? fieldTargets[key].textContent : '';
                if (!value) {
                    return;
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value);
                } else {
                    var temp = document.createElement('textarea');
                    temp.value = value;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    temp.remove();
                }
            });
        });

        document.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!activePlayerId) {
                    return;
                }
                var action = btn.getAttribute('data-action');
                var payload = {
                    player_id: activePlayerId,
                    action: action,
                    reason: actionReason.value.trim() || null,
                    note: actionNote.value.trim() || null
                };

                fetch('/player-action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.error) {
                            actionResult.textContent = data.error;
                            actionResult.className = 'modal-hint error';
                        } else {
                            actionResult.textContent = 'Islem kaydedildi: ' + (data.status || 'ok');
                            actionResult.className = 'modal-hint success';
                        }
                    })
                    .catch(function () {
                        actionResult.textContent = 'Islem basarisiz.';
                        actionResult.className = 'modal-hint error';
                    });
            });
        });
    </script>
</body>
</html>
