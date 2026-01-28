<?php
use App\Lib\Util;
?>
<section class="page-head">
    <div>
        <h1>Gosterge Paneli</h1>
        <p class="muted">Sunucu durumu, oyuncu ve log akisina buradan ulasirsin.</p>
    </div>
    <div class="badge">AKTIF</div>
</section>
<section class="grid">
    <div class="stat">
        <div class="stat-label">Aktif Oyuncular</div>
        <div class="stat-value"><?php echo Util::e((string)$activePlayerCount); ?></div>
    </div>
    <div class="stat">
        <div class="stat-label">Oyuncular</div>
        <div class="stat-value"><?php echo Util::e((string)$playerCount); ?></div>
    </div>
    <div class="stat">
        <div class="stat-label">Loglar</div>
        <div class="stat-value"><?php echo Util::e((string)$logCount); ?></div>
    </div>
</section>

<section class="card">
    <h2>Son Loglar</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Zaman</th>
                    <th>Oyuncu</th>
                    <th>Tip</th>
                    <th>Mesaj</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="4">Henuz log yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <?php $type = strtolower((string)$log['type']); ?>
                        <tr>
                            <td><?php echo Util::e((string)$log['created_at']); ?></td>
                            <td><?php echo Util::e((string)($log['player_name'] ?? '')); ?></td>
                            <td><span class="log-pill" data-type="<?php echo Util::e($type); ?>"><?php echo Util::e($type); ?></span></td>
                            <td><?php echo Util::e((string)$log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
