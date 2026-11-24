<?php

declare(strict_types=1);
?>
<div class="auth-card error-card">
    <span class="error-code"><?= e((string) ($code ?? 500)) ?></span>
    <h1><?= e($title ?? 'Ocurrió un problema') ?></h1>
    <p class="auth-subtitle"><?= e($message ?? 'No pudimos completar la operación.') ?></p>

    <a class="btn btn-primary btn-block" href="<?= e(url('/')) ?>">
        <?= icon('arrow-left') ?>
        <span>Volver al panel</span>
    </a>
</div>
