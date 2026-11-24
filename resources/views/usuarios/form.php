<?php

declare(strict_types=1);

use App\Core\View;

$isEdit = $mode === 'edit';
$action = $isEdit ? url('/usuarios/' . $id) : url('/usuarios');
$activo = (int) ($usuario['activo'] ?? 1) === 1;
$rolActual = (string) ($usuario['rol'] ?? 'vendedor');
$descripciones = [
    'admin' => 'Acceso total: usuarios, catálogo, movimientos y reportes.',
    'encargado' => 'Catálogo, importación y registro de movimientos.',
    'vendedor' => 'Consulta el catálogo, movimientos y reposición.',
    'consulta' => 'Solo lectura de todas las secciones operativas.',
];
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/usuarios')) ?>"><?= icon('arrow-left') ?> Volver a usuarios</a>
        <p class="page-lead">
            <?= $isEdit ? 'Actualizá los datos, el rol o la contraseña del usuario.' : 'Creá un usuario y asignale el rol con el que va a operar.' ?>
        </p>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<form method="post" action="<?= e($action) ?>" class="form-layout">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Datos de acceso</h2>
                    <p>El nombre de usuario debe ser único y no distingue mayúsculas de minúsculas.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field field-span-2<?= field_class($errors, 'nombre') ?>">
                    <label class="label" for="nombre">Nombre completo</label>
                    <input class="input" id="nombre" type="text" name="nombre" value="<?= e($usuario['nombre'] ?? '') ?>" maxlength="120" required autofocus placeholder="Ej. María González">
                    <?php if ($error = field_error($errors, 'nombre')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'usuario') ?>">
                    <label class="label" for="usuario">Usuario</label>
                    <input class="input" id="usuario" type="text" name="usuario" value="<?= e($usuario['usuario'] ?? '') ?>" maxlength="60" required autocomplete="off" placeholder="Ej. maria.gonzalez">
                    <?php if ($error = field_error($errors, 'usuario')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">Entre 3 y 60 caracteres: letras, números, punto, guion o guion bajo.</span>
                </div>

                <div class="field<?= field_class($errors, 'rol') ?>">
                    <label class="label" for="rol">Rol</label>
                    <select class="input" id="rol" name="rol" data-user-role <?= $esPropio ? 'disabled' : '' ?>>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= e($rol) ?>"<?= selected($rolActual, $rol) ?>><?= e(role_label($rol)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($esPropio): ?><input type="hidden" name="rol" value="admin"><?php endif; ?>
                    <?php if ($error = field_error($errors, 'rol')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help" data-user-role-hint><?= e($descripciones[$rolActual] ?? '') ?></span>
                </div>

                <?php if ($esPropio): ?>
                    <div class="field field-span-2">
                        <div class="field-note">
                            <?= icon('lock') ?>
                            <span>Estás editando tu propia cuenta: no podés cambiarte el rol ni desactivarla. Sí podés actualizar tu nombre, usuario y contraseña.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2><?= $isEdit ? 'Restablecer contraseña' : 'Contraseña' ?></h2>
                    <p><?= $isEdit ? 'Dejá ambos campos vacíos para conservar la contraseña actual.' : 'Mínimo 8 caracteres. Guardá la contraseña en un lugar seguro.' ?></p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field<?= field_class($errors, 'password') ?>">
                    <label class="label" for="password">
                        <?= $isEdit ? 'Nueva contraseña' : 'Contraseña' ?>
                        <?php if ($isEdit): ?><span class="label-optional">(opcional)</span><?php endif; ?>
                    </label>
                    <div class="input-with-icon">
                        <?= icon('lock') ?>
                        <input class="input" id="password" type="password" name="password" minlength="8" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?> data-password-input>
                        <button type="button" class="field-action" data-password-toggle aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </div>
                    <?php if ($error = field_error($errors, 'password')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'password_confirm') ?>">
                    <label class="label" for="password_confirm">Confirmar contraseña</label>
                    <div class="input-with-icon">
                        <?= icon('lock') ?>
                        <input class="input" id="password_confirm" type="password" name="password_confirm" minlength="8" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?> data-password-input>
                        <button type="button" class="field-action" data-password-toggle aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </div>
                    <?php if ($error = field_error($errors, 'password_confirm')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field field-span-2">
                    <span class="label">Estado del usuario</span>
                    <label class="switch">
                        <input type="checkbox" name="activo" value="1"<?= checked($activo) ?><?= $esPropio ? ' disabled' : '' ?>>
                        <?php if ($esPropio): ?><input type="hidden" name="activo" value="1"><?php endif; ?>
                        <span class="switch-track" aria-hidden="true"></span>
                        <span class="switch-copy">
                            <strong>Activo</strong>
                            <small>Puede iniciar sesión y operar según su rol</small>
                        </span>
                    </label>
                </div>
            </div>
        </section>
    </div>

    <aside class="form-aside">
        <div class="card form-summary">
            <h2>Resumen</h2>
            <p>Verificá el rol antes de guardar: define las acciones permitidas.</p>

            <dl class="summary-list">
                <div><dt>Nombre</dt><dd data-summary-name><?= e(($usuario['nombre'] ?? '') !== '' ? $usuario['nombre'] : '—') ?></dd></div>
                <div><dt>Usuario</dt><dd data-summary-user><?= e(($usuario['usuario'] ?? '') !== '' ? '@' . $usuario['usuario'] : '—') ?></dd></div>
                <div><dt>Rol</dt><dd data-summary-role><?= e(role_label($rolActual)) ?></dd></div>
                <div><dt>Estado</dt><dd><?= $activo ? 'Activo' : 'Inactivo' ?></dd></div>
            </dl>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <?= icon('save') ?><span><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></span>
                </button>
                <a class="btn btn-secondary btn-block" href="<?= e(url('/usuarios')) ?>">Cancelar</a>
            </div>
        </div>

        <div class="card tip-card">
            <span class="tip-icon"><?= icon('key') ?></span>
            <div>
                <strong>Contraseñas seguras</strong>
                <p>Las contraseñas se guardan con hash bcrypt. Nadie, ni el administrador, puede ver la contraseña actual.</p>
            </div>
        </div>
    </aside>
</form>
