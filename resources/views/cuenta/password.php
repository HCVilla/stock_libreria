<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\View;

$user = Auth::user();
$actual = (string) ($data['actual'] ?? '');
$errors = $errors ?? [];
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/')) ?>"><?= icon('arrow-left') ?> Volver al panel</a>
        <p class="page-lead">
            Cambiá la contraseña de tu cuenta, <?= e($user['nombre'] ?? '') ?>. Se guarda hasheada y se aplica en el próximo inicio de sesión.
        </p>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<form method="post" action="<?= e(url('/cuenta/password')) ?>" class="form-layout form-layout-narrow">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Contraseña</h2>
                    <p>Usá al menos 8 caracteres y evitá contraseñas usadas en otros sistemas.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field field-span-2<?= field_class($errors, 'actual') ?>">
                    <label class="label" for="actual">Contraseña actual</label>
                    <div class="input-with-icon">
                        <?= icon('lock') ?>
                        <input class="input" id="actual" type="password" name="actual" autocomplete="current-password" required autofocus data-password-input>
                        <button type="button" class="field-action" data-password-toggle aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </div>
                    <?php if ($error = field_error($errors, 'actual')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'password') ?>">
                    <label class="label" for="password">Nueva contraseña</label>
                    <div class="input-with-icon">
                        <?= icon('key') ?>
                        <input class="input" id="password" type="password" name="password" minlength="8" autocomplete="new-password" required data-password-input>
                        <button type="button" class="field-action" data-password-toggle aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </div>
                    <?php if ($error = field_error($errors, 'password')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'password_confirm') ?>">
                    <label class="label" for="password_confirm">Confirmar nueva contraseña</label>
                    <div class="input-with-icon">
                        <?= icon('key') ?>
                        <input class="input" id="password_confirm" type="password" name="password_confirm" minlength="8" autocomplete="new-password" required data-password-input>
                        <button type="button" class="field-action" data-password-toggle aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </div>
                    <?php if ($error = field_error($errors, 'password_confirm')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <aside class="form-aside">
        <div class="card form-summary">
            <h2>Cuenta</h2>
            <p>Tu sesión actual sigue activa después del cambio.</p>

            <dl class="summary-list">
                <div><dt>Usuario</dt><dd>@<?= e($user['usuario'] ?? '') ?></dd></div>
                <div><dt>Rol</dt><dd><?= e(role_label((string) ($user['rol'] ?? ''))) ?></dd></div>
                <div><dt>Último acceso</dt><dd><?= e(format_datetime($user['ultimo_acceso'] ?? null)) ?></dd></div>
            </dl>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <?= icon('save') ?><span>Actualizar contraseña</span>
                </button>
                <a class="btn btn-secondary btn-block" href="<?= e(url('/')) ?>">Cancelar</a>
            </div>
        </div>

        <div class="card tip-card">
            <span class="tip-icon"><?= icon('lock') ?></span>
            <div>
                <strong>Protección</strong>
                <p>Si sospechás que alguien conoce tu contraseña, cambiala ahora y avisá al administrador.</p>
            </div>
        </div>
    </aside>
</form>
