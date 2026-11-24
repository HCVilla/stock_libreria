<?php

declare(strict_types=1);

use App\Core\View;

$isEdit = $mode === 'edit';
$action = $isEdit ? url('/tipos/' . $id) : url('/tipos');
$activo = (int) ($tipo['activo'] ?? 1) === 1;
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/tipos')) ?>"><?= icon('arrow-left') ?> Volver a tipos</a>
        <p class="page-lead"><?= $isEdit ? 'Actualizá el nombre o la descripción del tipo.' : 'Agregá una nueva familia para clasificar artículos.' ?></p>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<form method="post" action="<?= e($action) ?>" class="form-layout form-layout-narrow">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Datos del tipo</h2>
                    <p>El nombre debe ser único dentro del catálogo.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field field-span-2<?= field_class($errors, 'nombre') ?>">
                    <label class="label" for="nombre">Nombre</label>
                    <input class="input" id="nombre" type="text" name="nombre" value="<?= e($tipo['nombre'] ?? '') ?>" maxlength="100" required autofocus placeholder="Ej. Cuadernos">
                    <?php if ($error = field_error($errors, 'nombre')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field field-span-2<?= field_class($errors, 'descripcion') ?>">
                    <label class="label" for="descripcion">Descripción <span class="label-optional">(opcional)</span></label>
                    <textarea class="input" id="descripcion" name="descripcion" rows="4" maxlength="255" placeholder="Qué artículos agrupa este tipo"><?= e($tipo['descripcion'] ?? '') ?></textarea>
                    <?php if ($error = field_error($errors, 'descripcion')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
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
                <?= icon('save') ?><span><?= $isEdit ? 'Guardar cambios' : 'Crear tipo' ?></span>
            </button>
            <a class="btn btn-secondary" href="<?= e(url('/tipos')) ?>">Cancelar</a>
        </div>
    </div>

    <aside class="form-aside">
        <div class="card tip-card">
            <span class="tip-icon"><?= icon('info') ?></span>
            <div>
                <strong>Desactivar no borra</strong>
                <p>Si un tipo deja de usarse, desactivalo. Los artículos y movimientos históricos se mantienen intactos.</p>
            </div>
        </div>
    </aside>
</form>
