<?php

declare(strict_types=1);

use App\Core\View;

$isEdit = $mode === 'edit';
$action = $isEdit ? url('/articulos/' . $id) : url('/articulos');
$activo = (int) ($articulo['activo'] ?? 1) === 1;
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/articulos')) ?>"><?= icon('arrow-left') ?> Volver a artículos</a>
        <p class="page-lead"><?= $isEdit ? 'Modificá los datos del artículo. Los cambios de stock quedan auditados.' : 'Cargá los datos básicos, precios y stock inicial del artículo.' ?></p>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<form method="post" action="<?= e($action) ?>" class="form-layout">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Identificación</h2>
                    <p>Código interno o de barras, nombre y clasificación.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field<?= field_class($errors, 'codigo') ?>">
                    <div class="label-row">
                        <label class="label" for="codigo">Código / SKU / código de barras</label>
                        <button type="button" class="btn btn-secondary btn-sm" data-barcode-scan data-barcode-target="#codigo">
                            <?= icon('camera') ?><span>Escanear</span>
                        </button>
                    </div>
                    <input class="input" id="codigo" type="text" name="codigo" value="<?= e($articulo['codigo'] ?? '') ?>" maxlength="64" required autofocus placeholder="Ej. LAP-001 o 7791234567890">
                    <?php if ($error = field_error($errors, 'codigo')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">Debe ser único. Se usa para buscar y para el lector de códigos de barras.</span>
                </div>

                <div class="field field-span-2<?= field_class($errors, 'nombre') ?>">
                    <label class="label" for="nombre">Nombre del artículo</label>
                    <input class="input" id="nombre" type="text" name="nombre" value="<?= e($articulo['nombre'] ?? '') ?>" maxlength="200" required placeholder="Ej. Cuaderno universitario 100 hojas">
                    <?php if ($error = field_error($errors, 'nombre')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'tipo_id') ?>">
                    <label class="label" for="tipo_id">Tipo de artículo</label>
                    <select class="input" id="tipo_id" name="tipo_id" required>
                        <option value="">Seleccioná un tipo</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?= e((string) $tipo['id']) ?>"<?= selected($articulo['tipo_id'] ?? '', $tipo['id']) ?>>
                                <?= e($tipo['nombre']) ?><?= (int) $tipo['activo'] === 1 ? '' : ' (inactivo)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error($errors, 'tipo_id')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'proveedor_id') ?>">
                    <label class="label" for="proveedor_id">Proveedor <span class="label-optional">(opcional)</span></label>
                    <select class="input" id="proveedor_id" name="proveedor_id">
                        <option value="">Sin proveedor asignado</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= e((string) $proveedor['id']) ?>"<?= selected($articulo['proveedor_id'] ?? '', $proveedor['id']) ?>>
                                <?= e($proveedor['nombre']) ?><?= (int) $proveedor['activo'] === 1 ? '' : ' (inactivo)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error($errors, 'proveedor_id')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Precios</h2>
                    <p>Los importes se guardan con dos decimales y nunca pueden ser negativos.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field<?= field_class($errors, 'precio_costo') ?>">
                    <label class="label" for="precio_costo">Precio de costo</label>
                    <div class="input-money">
                        <span>$</span>
                        <input class="input" id="precio_costo" type="number" name="precio_costo" value="<?= e($articulo['precio_costo'] ?? '0.00') ?>" min="0" step="0.01" required data-price-cost>
                    </div>
                    <?php if ($error = field_error($errors, 'precio_costo')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field<?= field_class($errors, 'precio_venta') ?>">
                    <label class="label" for="precio_venta">Precio de venta</label>
                    <div class="input-money">
                        <span>$</span>
                        <input class="input" id="precio_venta" type="number" name="precio_venta" value="<?= e($articulo['precio_venta'] ?? '0.00') ?>" min="0" step="0.01" required data-price-sale>
                    </div>
                    <?php if ($error = field_error($errors, 'precio_venta')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help" data-margin-hint>Margen: —</span>
                </div>
            </div>
        </section>

        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Inventario</h2>
                    <p>El stock no puede ser negativo. Los cambios se registran como movimientos.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field<?= field_class($errors, 'stock_actual') ?>">
                    <label class="label" for="stock_actual"><?= $isEdit ? 'Stock actual' : 'Stock inicial' ?></label>
                    <input class="input" id="stock_actual" type="number" name="stock_actual" value="<?= e($articulo['stock_actual'] ?? '0') ?>" min="0" step="0.01" required>
                    <?php if ($error = field_error($errors, 'stock_actual')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">
                        <?= $isEdit
                            ? 'Si modificás este valor se genera un ajuste de inventario auditado.'
                            : 'Se registra como movimiento de entrada con motivo «Stock inicial».' ?>
                    </span>
                </div>

                <div class="field<?= field_class($errors, 'stock_minimo') ?>">
                    <label class="label" for="stock_minimo">Stock mínimo</label>
                    <input class="input" id="stock_minimo" type="number" name="stock_minimo" value="<?= e($articulo['stock_minimo'] ?? '0') ?>" min="0" step="0.01" required>
                    <?php if ($error = field_error($errors, 'stock_minimo')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">Cuando el stock actual sea igual o menor, se marca «Bajo stock».</span>
                </div>

                <div class="field<?= field_class($errors, 'unidad_medida') ?>">
                    <label class="label" for="unidad_medida">Unidad de medida</label>
                    <select class="input" id="unidad_medida" name="unidad_medida" required>
                        <?php foreach ($unidades as $unidad): ?>
                            <option value="<?= e($unidad) ?>"<?= selected($articulo['unidad_medida'] ?? 'unidad', $unidad) ?>><?= e(ucfirst($unidad)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error($errors, 'unidad_medida')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field field-switch">
                    <span class="label">Estado del artículo</span>
                    <label class="switch">
                        <input type="checkbox" name="activo" value="1"<?= checked($activo) ?>>
                        <span class="switch-track" aria-hidden="true"></span>
                        <span class="switch-copy">
                            <strong>Activo</strong>
                            <small>Visible para operar y buscar</small>
                        </span>
                    </label>
                </div>
            </div>
        </section>
    </div>

    <aside class="form-aside">
        <div class="card form-summary">
            <h2>Resumen</h2>
            <p>Verificá los datos antes de guardar. Podés editar el artículo más adelante.</p>
            <dl class="summary-list">
                <div><dt>Código</dt><dd data-summary-code><?= e(($articulo['codigo'] ?? '') !== '' ? $articulo['codigo'] : '—') ?></dd></div>
                <div><dt>Stock</dt><dd data-summary-stock><?= e(qty($articulo['stock_actual'] ?? 0)) ?> <?= e($articulo['unidad_medida'] ?? 'unidad') ?></dd></div>
                <div><dt>Estado</dt><dd><?= $activo ? 'Activo' : 'Inactivo' ?></dd></div>
            </dl>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <?= icon('save') ?><span><?= $isEdit ? 'Guardar cambios' : 'Crear artículo' ?></span>
                </button>
                <a class="btn btn-secondary btn-block" href="<?= e(url('/articulos')) ?>">Cancelar</a>
            </div>
        </div>

        <div class="card tip-card">
            <span class="tip-icon"><?= icon('info') ?></span>
            <div>
                <strong>Stock y auditoría</strong>
                <p>El stock actual no se edita directamente en la base: cada cambio genera un movimiento firmado por tu usuario.</p>
            </div>
        </div>
    </aside>
</form>
