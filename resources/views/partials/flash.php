<?php

declare(strict_types=1);

use App\Core\Session;

$flashes = Session::flashes();

if ($flashes === []) {
    return;
}

$meta = [
    'success' => ['class' => 'success', 'icon' => 'check'],
    'error' => ['class' => 'danger', 'icon' => 'alert'],
    'danger' => ['class' => 'danger', 'icon' => 'alert'],
    'warning' => ['class' => 'warning', 'icon' => 'alert'],
    'info' => ['class' => 'info', 'icon' => 'info'],
];
?>
<div class="flash-stack">
    <?php foreach ($flashes as $flash): ?>
        <?php $type = $meta[$flash['type']] ?? $meta['info']; ?>
        <div class="alert alert-<?= e($type['class']) ?>" role="status">
            <?= icon($type['icon']) ?>
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="alert-close" data-dismiss-alert aria-label="Cerrar aviso">
                <?= icon('close') ?>
            </button>
        </div>
    <?php endforeach; ?>
</div>
