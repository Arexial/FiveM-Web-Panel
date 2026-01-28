<?php
use App\Lib\Util;
?>
<section class="card">
    <div class="card-head">
        <div>
            <h1>Loglar</h1>
            <p class="muted">Son 200 log kaydi.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Zaman</th>
                    <th>Oyuncu</th>
                    <th>License</th>
                    <th>Citizen ID</th>
                    <th>Tip</th>
                    <th>Mesaj</th>
                    <th>Meta</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7">Log yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php $type = strtolower((string)$log['type']); ?>
                        <tr>
                            <td><?php echo Util::e((string)$log['created_at']); ?></td>
                            <td><?php echo Util::e((string)($log['player_name'] ?? '')); ?></td>
                            <td><span class="mono"><?php echo Util::e((string)($log['license'] ?? '')); ?></span></td>
                            <td><span class="mono"><?php echo Util::e((string)($log['citizenid'] ?? '')); ?></span></td>
                            <td><span class="log-pill" data-type="<?php echo Util::e($type); ?>"><?php echo Util::e($type); ?></span></td>
                            <td><?php echo Util::e((string)$log['message']); ?></td>
                            <td class="meta-cell"><code class="meta-json"><?php echo Util::e((string)($log['meta_json'] ?? '')); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
