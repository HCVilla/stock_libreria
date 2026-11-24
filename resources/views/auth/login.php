<?php

declare(strict_types=1);

use App\Core\Auth;
?>
<div class="auth-card">
    <span class="auth-kicker"><?= icon('lock') ?> Acceso al sistema</span>
    <h1>Iniciar sesión</h1>
    <p class="auth-subtitle">Ingresá con tu usuario para administrar artículos, stock y proveedores.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= icon('alert') ?>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" class="auth-form">
        <?= csrf_field() ?>

        <label class="field">
            <span class="label">Usuario</span>
            <span class="input-with-icon">
                <?= icon('user') ?>
                <input
                    type="text"
                    name="usuario"
                    value="<?= e($old['usuario'] ?? '') ?>"
                    autocomplete="username"
                    autofocus
                    required
                    placeholder="Tu usuario"
                >
            </span>
        </label>

        <label class="field">
            <span class="label">Contraseña</span>
            <span class="input-with-icon">
                <?= icon('lock') ?>
                <input
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    placeholder="Tu contraseña"
                    data-password-input
                >
                <button type="button" class="field-action" data-password-toggle aria-label="Mostrar contraseña">
                    <?= icon('eye') ?>
                </button>
            </span>
        </label>

        <button type="submit" class="btn btn-primary btn-block">
            <?= icon('login') ?>
            <span>Ingresar</span>
        </button>
    </form>

    <p class="auth-hint">
        <?= icon('info') ?>
        <span>Si no recordás tus credenciales, contactá al administrador del sistema.</span>
    </p>
</div>
