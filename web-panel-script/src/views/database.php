<?php
use App\Lib\Util;
?>
<section class="card">
    <div class="card-head">
        <div>
            <h1>Tum Oyuncular</h1>
            <p class="muted">Kayitli tum oyuncu verileri.</p>
        </div>
        <div class="card-actions">
            <form method="get" action="/database" class="search-form">
                <select name="by" aria-label="Filter">
                    <option value="all" <?php echo (($by ?? 'all') === 'all') ? 'selected' : ''; ?>>Hepsi</option>
                    <option value="name" <?php echo (($by ?? 'all') === 'name') ? 'selected' : ''; ?>>Isim</option>
                    <option value="license" <?php echo (($by ?? 'all') === 'license') ? 'selected' : ''; ?>>License</option>
                    <option value="discord" <?php echo (($by ?? 'all') === 'discord') ? 'selected' : ''; ?>>Discord</option>
                    <option value="citizenid" <?php echo (($by ?? 'all') === 'citizenid') ? 'selected' : ''; ?>>Citizen ID</option>
                </select>
                <input type="text" name="q" placeholder="Isim / License / Discord / Citizen ID" value="<?php echo Util::e((string)($query ?? '')); ?>">
            </form>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Oyuncu</th>
                    <th>License</th>
                    <th>Discord</th>
                    <th>Citizen ID</th>
                    <th>SID</th>
                    <th>Durum</th>
                    <th>Son Gorulme</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($players)): ?>
                    <tr><td colspan="7">Kayit yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($players as $player): ?>
                        <tr class="row-click"
                            data-id="<?php echo Util::e((string)($player['id'] ?? '')); ?>"
                            data-name="<?php echo Util::e((string)($player['name'] ?? '')); ?>"
                            data-license="<?php echo Util::e((string)($player['license'] ?? '')); ?>"
                            data-discord="<?php echo Util::e((string)($player['discord'] ?? '')); ?>"
                            data-citizenid="<?php echo Util::e((string)($player['citizenid'] ?? '')); ?>"
                            data-serverid="<?php echo Util::e((string)($player['server_id'] ?? '')); ?>"
                            data-online="<?php echo Util::e((string)($player['online'] ?? 0)); ?>"
                            data-banned="<?php echo Util::e((string)($player['banned'] ?? 0)); ?>"
                            data-lastseen="<?php echo Util::e((string)($player['last_seen'] ?? '')); ?>"
                            data-created="<?php echo Util::e((string)($player['created_at'] ?? '')); ?>">
                            <td><?php echo Util::e((string)($player['name'] ?? '')); ?></td>
                            <td><span class="mono"><?php echo Util::e((string)($player['license'] ?? '')); ?></span></td>
                            <td><span class="mono"><?php echo Util::e((string)($player['discord'] ?? '')); ?></span></td>
                            <td><span class="mono"><?php echo Util::e((string)($player['citizenid'] ?? '')); ?></span></td>
                            <td><span class="mono"><?php echo Util::e((string)($player['server_id'] ?? '')); ?></span></td>
                            <td>
                                <?php if (!empty($player['banned'])): ?>
                                    <span class="status status-banned">Yasakli</span>
                                <?php elseif (!empty($player['online'])): ?>
                                    <span class="status status-online">Cevrimici</span>
                                <?php else: ?>
                                    <span class="status status-offline">Cevrimdisi</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo Util::e((string)($player['last_seen'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
