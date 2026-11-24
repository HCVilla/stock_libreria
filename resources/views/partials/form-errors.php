<?php

declare(strict_types=1);

if (empty($errors)) {
    return;
}
?>
<div class="alert alert-danger" role="alert">
    <?= icon('alert') ?>
    <div>
        <strong>Revisá los datos del formulario</strong>
        <ul class="alert-list">
            <?php foreach ($errors as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
