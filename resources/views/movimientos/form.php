<?php

declare(strict_types=1);

use App\Core\View;

$tipo = (string) ($data['tipo_movimiento'] ?? 'entrada');
$articuloSeleccionado = null;

foreach ($articulos as $articulo) {
    if ((string) $articulo['id'] === (string) ($data['articulo_id'] ?? '')) {
        $articuloSeleccionado = $articulo;
        break;
    }
}

$stockInicial = $articuloSeleccionado !== null ? (float) $articuloSeleccionado['stock_actual'] : null;
$resultadoInicial = $stockInicial;

if ($articuloSeleccionado !== null) {
    if ($tipo === 'entrada' && is_numeric($data['cantidad'] ?? null)) {
        $resultadoInicial = $stockInicial + (float) $data['cantidad'];
    } elseif ($tipo === 'salida' && is_numeric($data['cantidad'] ?? null)) {
        $resultadoInicial = $stockInicial - (float) $data['cantidad'];
    } elseif ($tipo === 'ajuste' && is_numeric($data['stock_real'] ?? null)) {
        $resultadoInicial = (float) $data['stock_real'];
    }
}

$estadoInicial = null;

if ($resultadoInicial !== null) {
    $estadoInicial = stock_state([
        'activo' => 1,
        'stock_actual' => $resultadoInicial,
        'stock_minimo' => $articuloSeleccionado['stock_minimo'] ?? 0,
    ]);
}
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/movimientos')) ?>"><?= icon('arrow-left') ?> Volver a movimientos</a>
        <p class="page-lead">
            Registrá una entrada, una salida o un ajuste. El stock y el historial se actualizan en una sola transacción.
        </p>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<form method="post" action="<?= e(url('/movimientos')) ?>" class="form-layout" data-movement-form>
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Tipo de movimiento</h2>
                    <p>Elegí qué operación vas a registrar.</p>
                </div>
            </header>

            <div class="type-grid">
                <?php
                $tiposDisponibles = [
                    'entrada' => ['icon' => 'login', 'class' => 'type-icon-success', 'label' => 'Entrada', 'hint' => 'Compra o reposición de proveedor'],
                    'salida' => ['icon' => 'logout', 'class' => 'type-icon-danger', 'label' => 'Salida', 'hint' => 'Venta o entrega de mercadería'],
                    'ajuste' => ['icon' => 'filter', 'class' => 'type-icon-info', 'label' => 'Ajuste', 'hint' => 'Corrección por inventario físico'],
                ];
                ?>

                <?php foreach ($tiposDisponibles as $valor => $metaTipo): ?>
                    <label class="type-option">
                        <input
                            type="radio"
                            name="tipo_movimiento"
                            value="<?= e($valor) ?>"
                            data-movement-type-option
                            <?= checked($tipo === $valor) ?>
                        >
                        <span class="type-card">
                            <span class="type-icon <?= e($metaTipo['class']) ?>"><?= icon($metaTipo['icon']) ?></span>
                            <span class="type-copy">
                                <strong><?= e($metaTipo['label']) ?></strong>
                                <small><?= e($metaTipo['hint']) ?></small>
                            </span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Artículo y cantidad</h2>
                    <p>El stock resultante nunca puede ser negativo.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field field-span-2<?= field_class($errors, 'articulo_id') ?>">
                    <div class="label-row">
                        <label class="label" for="articulo_id">Artículo</label>
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            data-barcode-scan
                            data-barcode-select="#articulo_id"
                        >
                            <?= icon('camera') ?><span>Escanear</span>
                        </button>
                    </div>
                    <select class="input" id="articulo_id" name="articulo_id" required data-movement-article>
                        <option value="">Seleccioná un artículo</option>
                        <?php foreach ($articulos as $articulo): ?>
                            <option
                                value="<?= e((string) $articulo['id']) ?>"
                                data-stock="<?= e((string) $articulo['stock_actual']) ?>"
                                data-min="<?= e((string) $articulo['stock_minimo']) ?>"
                                data-cost="<?= e((string) $articulo['precio_costo']) ?>"
                                data-sale="<?= e((string) $articulo['precio_venta']) ?>"
                                data-unit="<?= e((string) $articulo['unidad_medida']) ?>"
                                data-code="<?= e((string) $articulo['codigo']) ?>"
                                data-name="<?= e((string) $articulo['nombre']) ?>"
                                <?= selected($data['articulo_id'] ?? '', $articulo['id']) ?>
                            >
                                <?= e($articulo['codigo']) ?> · <?= e($articulo['nombre']) ?> — stock <?= e(qty($articulo['stock_actual'])) ?> <?= e($articulo['unidad_medida']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = field_error($errors, 'articulo_id')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">Solo se listan artículos activos.</span>
                </div>

                <div class="field<?= field_class($errors, 'cantidad') ?>" data-movement-quantity <?= $tipo === 'ajuste' ? 'hidden' : '' ?>>
                    <label class="label" for="cantidad" data-quantity-label>Cantidad</label>
                    <input
                        class="input"
                        id="cantidad"
                        type="number"
                        name="cantidad"
                        value="<?= e($data['cantidad'] ?? '') ?>"
                        min="0.01"
                        step="0.01"
                        <?= $tipo === 'ajuste' ? 'disabled' : '' ?>
                    >
                    <?php if ($error = field_error($errors, 'cantidad')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help" data-quantity-help>
                        <?= $tipo === 'ajuste'
                            ? ''
                            : 'Unidades que ingresan o egresan del inventario.' ?>
                    </span>
                </div>

                <div class="field<?= field_class($errors, 'stock_real') ?>" data-movement-adjust <?= $tipo === 'ajuste' ? '' : 'hidden' ?>>
                    <label class="label" for="stock_real">Stock real contado</label>
                    <input
                        class="input"
                        id="stock_real"
                        type="number"
                        name="stock_real"
                        value="<?= e($data['stock_real'] ?? '') ?>"
                        min="0"
                        step="0.01"
                        <?= $tipo === 'ajuste' ? '' : 'disabled' ?>
                    >
                    <?php if ($error = field_error($errors, 'stock_real')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">Ingresá el stock físico real. El sistema calcula y audita la diferencia contra el stock actual.</span>
                </div>

                <div class="field field-span-2">
                    <div class="field-note">
                        <?= icon('info') ?>
                        <span data-movement-mode-hint>
                            Se va a registrar una entrada de stock a favor del inventario.
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Detalle del movimiento</h2>
                    <p>El motivo es obligatorio para los ajustes.</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field<?= field_class($errors, 'precio_unitario') ?>">
                    <label class="label" for="precio_unitario">Precio unitario <span class="label-optional">(opcional)</span></label>
                    <div class="input-money">
                        <span>$</span>
                        <input
                            class="input"
                            id="precio_unitario"
                            type="number"
                            name="precio_unitario"
                            value="<?= e($data['precio_unitario'] ?? '') ?>"
                            min="0"
                            step="0.01"
                            placeholder="Automático"
                            data-movement-price
                        >
                    </div>
                    <?php if ($error = field_error($errors, 'precio_unitario')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help" data-movement-price-help>
                        Si lo dejás vacío se usa el precio de costo del artículo.
                    </span>
                </div>

                <div class="field<?= field_class($errors, 'referencia') ?>">
                    <label class="label" for="referencia">Referencia <span class="label-optional">(opcional)</span></label>
                    <input
                        class="input"
                        id="referencia"
                        type="text"
                        name="referencia"
                        value="<?= e($data['referencia'] ?? '') ?>"
                        maxlength="64"
                        placeholder="Factura, remito, ticket…"
                    >
                    <?php if ($error = field_error($errors, 'referencia')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                </div>

                <div class="field field-span-2<?= field_class($errors, 'motivo') ?>">
                    <label class="label" for="motivo">
                        Motivo
                        <span class="label-optional" data-motive-optional <?= $tipo === 'ajuste' ? 'hidden' : '' ?>>(opcional)</span>
                    </label>
                    <textarea
                        class="input"
                        id="motivo"
                        name="motivo"
                        rows="3"
                        maxlength="255"
                        placeholder="Ej. Compra a proveedor, venta mostrador, recuento físico…"
                        data-movement-motive
                        <?= $tipo === 'ajuste' ? 'required' : '' ?>
                    ><?= e($data['motivo'] ?? '') ?></textarea>
                    <?php if ($error = field_error($errors, 'motivo')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help" data-movement-motive-help>
                        <?= $tipo === 'ajuste'
                            ? 'Obligatorio: explicá por qué se corrige el stock.'
                            : 'Si lo dejás vacío se usa un motivo predeterminado.' ?>
                    </span>
                </div>
            </div>
        </section>
    </div>

    <aside class="form-aside">
        <div class="card form-summary movement-summary">
            <h2>Resumen</h2>
            <p>El stock se actualiza al confirmar, junto con el movimiento auditado.</p>

            <dl class="summary-list">
                <div>
                    <dt>Tipo</dt>
                    <dd data-summary-type><?= e(movement_meta($tipo)['label']) ?></dd>
                </div>
                <div>
                    <dt>Artículo</dt>
                    <dd data-summary-article><?= e($articuloSeleccionado['nombre'] ?? '—') ?></dd>
                </div>
                <div>
                    <dt>Stock actual</dt>
                    <dd data-summary-current>
                        <?= $articuloSeleccionado !== null ? e(qty($stockInicial) . ' ' . $articuloSeleccionado['unidad_medida']) : '—' ?>
                    </dd>
                </div>
                <div>
                    <dt>Stock mínimo</dt>
                    <dd data-summary-min>
                        <?= $articuloSeleccionado !== null ? e(qty($articuloSeleccionado['stock_minimo'])) : '—' ?>
                    </dd>
                </div>
                <div class="summary-result">
                    <dt>Resultado estimado</dt>
                    <dd data-summary-result>
                        <?= $resultadoInicial !== null ? e(qty($resultadoInicial)) : '—' ?>
                    </dd>
                </div>
            </dl>

            <div class="movement-state" data-summary-state>
                <?php if ($estadoInicial !== null): ?>
                    <span class="badge badge-<?= e($estadoInicial['class']) ?>"><?= e($estadoInicial['label']) ?></span>
                <?php else: ?>
                    <span class="badge badge-muted">Elegí un artículo</span>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <?= icon('save') ?><span>Registrar movimiento</span>
                </button>
                <a class="btn btn-secondary btn-block" href="<?= e(url('/movimientos')) ?>">Cancelar</a>
            </div>
        </div>

        <div class="card tip-card">
            <span class="tip-icon"><?= icon('lock') ?></span>
            <div>
                <strong>Operación segura</strong>
                <p>El artículo se bloquea durante la operación para evitar que dos usuarios dejen el stock inconsistente. Si hay un conflicto, se te avisa y no se guarda nada.</p>
            </div>
        </div>
    </aside>
</form>
