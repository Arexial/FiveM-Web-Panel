<?php
use App\Lib\Config;
use App\Lib\Util;
?>
<section class="login-hero">
    <div class="card login-card">
        <div class="login-badge">Discord OAuth</div>
        <h1><?php echo Util::e((string)Config::get('server_name')); ?></h1>
        <p class="muted">Discord ile guvenli giris yap, oyuncu kayitlarini ve loglari takip et.</p>
        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo Util::e((string)$error); ?></div>
        <?php endif; ?>
        <div class="login-actions">
            <a class="button" href="/auth/discord">Discord ile giris</a>
        </div>
    </div>
</section>
