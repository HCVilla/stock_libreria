<?php

declare(strict_types=1);

use App\Core\View;

$isEdit = $mode === 'edit';
$action = $isEdit ? url('/proveedores/' . $id) : url('/proveedores');
$activo = (int) ($proveedor['activo'] ?? 1) === 1;
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/proveedores')) ?>"><?= icon('arrow-left') ?> Volver a proveedores</a>
        <p class="page-lead"><?= $isEdit ? 'Actualizá los datos de contacto y las notas internas.' : 'Registrá un proveedor para asociarlo a tus artículos.' ?></p>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<form method="post" action="<?= e($action) ?>" class="form-layout">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Datos del proveedor</h2>
                    <p>El nombre debe ser único. Los datos de contacto son opcionales.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field field-span-2<?= field_class($errors, 'nombre') ?>">
                    <label class="label" for="nombre">Nombre o razón social</label>
                    <input class="input" id="nombre" type="text" name="nombre" value="<?= e($proveedor['nombre'] ?? '') ?>" maxlength="150" required autofocus placeholder="Ej. Distribuidora Escolar S.A.">
                    <?php if ($error = field_error($errors, 'nombre')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'contacto') ?>">
                    <label class="label" for="contacto">Persona de contacto</label>
                    <input class="input" id="contacto" type="text" name="contacto" value="<?= e($proveedor['contacto'] ?? '') ?>" maxlength="120" placeholder="Ej. Laura Gómez">
                    <?php if ($error = field_error($errors, 'contacto')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'telefono') ?>">
                    <label class="label" for="telefono">Teléfono</label>
                    <input class="input" id="telefono" type="tel" name="telefono" value="<?= e($proveedor['telefono'] ?? '') ?>" maxlength="30" placeholder="Ej. 11 5555-1234">
                    <?php if ($error = field_error($errors, 'telefono')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field field-span-2<?= field_class($errors, 'notas') ?>">
                    <label class="label" for="notas">Notas internas <span class="label-optional">(opcional)</span></label>
                    <textarea class="input" id="notas" name="notas" rows="4" maxlength="2000" placeholder="Condiciones de pago, días de entrega, observaciones…"><?= e($proveedor['notas'] ?? '') ?></textarea>
                    <?php if ($error = field_error($errors, 'notas')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field field-switch field-span-2">
                    <span class="label">Estado</span>
                    <label class="switch">
                        <input type="checkbox" name="activo" value="1"<?= checked($activo) ?>>
                        <span class="switch-track" aria-hidden="true"></span>
                        <span class="switch-copy">
                            <strong>Activo</strong>
                            <small>Disponible para asignar a nuevos artículos</small>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <div class="form-actions form-actions-inline">
            <button type="submit" class="btn btn-primary">
                <?= icon('save') ?><span><?= $isEdit ? 'Guardar cambios' : 'Crear proveedor' ?></span>
            </button>
            <a class="btn btn-secondary" href="<?= e(url('/proveedores')) ?>">Cancelar</a>
        </div>
    </div>

    <aside class="form-aside">
        <div class="card tip-card">
            <span class="tip-icon"><?= icon('truck') ?></span>
            <div>
                <strong>Proveedor y artículos</strong>
                <p>Un proveedor puede estar asociado a muchos artículos. Si lo desactivás, las asociaciones se mantienen.</p>
            </div>
        </div>
    </aside>
</form>
