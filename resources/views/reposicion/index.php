<?php

declare(strict_types=1);

$manage = can_manage();
$pendientes = count($filas);
$factorLabel = rtrim(rtrim(number_format((float) $factor, 2, ',', '.'), '0'), ',');
?>
<div class="page-head">
    <div>
        <p class="page-lead">
            Artículos con stock actual igual o por debajo del mínimo. Calculá cuánto falta para el mínimo y para un objetivo de reposición.
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('/movimientos')) ?>">
            <?= icon('swap') ?><span>Ver movimientos</span>
        </a>
        <?php if ($manage): ?>
            <a class="btn btn-primary" href="<?= e(url('/movimientos/nuevo')) ?>">
                <?= icon('plus') ?><span>Registrar entrada</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<section class="stats-grid stats-grid-sm" aria-label="Resumen de reposición">
    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-amber"><?= icon('alert') ?></span>
        <div class="stat-body">
            <span class="stat-label">Pendientes</span>
            <strong class="stat-value"><?= e((string) $pendientes) ?></strong>
            <span class="stat-hint">Artículos que requieren reposición</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-danger"><?= icon('box') ?></span>
        <div class="stat-body">
            <span class="stat-label">Sin stock</span>
            <strong class="stat-value"><?= e((string) $sinStock) ?></strong>
            <span class="stat-hint">No tienen unidades disponibles</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-blue"><?= icon('chart') ?></span>
        <div class="stat-body">
            <span class="stat-label">Falta para mínimo</span>
            <strong class="stat-value"><?= e(qty($totalFaltanteMinimo)) ?></strong>
            <span class="stat-hint">Suma de todas las unidades</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-teal"><?= icon('target') ?></span>
        <div class="stat-body">
            <span class="stat-label">Falta para objetivo</span>
            <strong class="stat-value"><?= e(qty($totalFaltanteObjetivo)) ?></strong>
            <span class="stat-hint">Objetivo actual: mínimo × <?= e($factorLabel) ?></span>
        </div>
    </article>
</section>

<section class="card">
    <header class="card-header">
        <div>
            <h2>Reposición pendiente</h2>
            <p>La regla de alerta es <strong>stock actual ≤ stock mínimo</strong>.</p>
        </div>

        <form class="inline-form" method="get" action="<?= e(url('/reposicion')) ?>">
            <label class="inline-field" for="factor">
                <span>Objetivo</span>
                <select class="input filter-select" id="factor" name="factor" data-auto-submit>
                    <?php foreach ($factores as $clave => $valor): ?>
                        <option value="<?= e($clave) ?>"<?= selected($factorKey, $clave) ?>>
                            mínimo × <?= e(rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',')) ?><?= $valor === 1.0 ? ' (solo mínimo)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-secondary"><?= icon('filter') ?><span>Aplicar</span></button>
        </form>
    </header>

    <?php if ($filas === []): ?>
        <div class="empty-state">
            <span class="empty-icon empty-icon-success"><?= icon('check') ?></span>
            <h3>No hay reposición pendiente</h3>
            <p>Todos los artículos activos están por encima de su stock mínimo configurado.</p>
            <?php if ($manage): ?>
                <a class="btn btn-primary" href="<?= e(url('/movimientos/nuevo')) ?>">
                    <?= icon('plus') ?><span>Registrar movimiento</span>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-reposicion">
                <thead>
                    <tr>
                        <th>Artículo</th>
                        <th>Proveedor</th>
                        <th class="num">Stock actual</th>
                        <th class="num">Mínimo</th>
                        <th class="num">Falta mínimo</th>
                        <th class="num">Objetivo</th>
                        <th class="num">Falta objetivo</th>
                        <?php if ($manage): ?><th class="th-actions">Acción</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila): ?>
                        <?php
                        $estado = stock_state($fila);
                        $unidad = (string) $fila['unidad_medida'];
                        $cantidadSugerida = (float) $fila['faltante_objetivo'];
                        $parametros = [
                            'articulo_id' => $fila['id'],
                            'tipo' => 'entrada',
                            'motivo' => 'Reposición de stock',
                        ];

                        if ($cantidadSugerida > 0) {
                            $parametros['cantidad'] = number_format($cantidadSugerida, 2, '.', '');
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="cell-title"><?= e($fila['nombre']) ?></div>
                                <div class="cell-sub"><code><?= e($fila['codigo']) ?></code> · <?= e($fila['tipo_nombre']) ?></div>
                            </td>
                            <td class="cell-wrap">
                                <?= !empty($fila['proveedor_nombre']) ? e($fila['proveedor_nombre']) : '<span class="text-muted">Sin proveedor</span>' ?>
                            </td>
                            <td class="num">
                                <div><strong><?= e(qty($fila['stock_actual'])) ?></strong> <span class="unit"><?= e($unidad) ?></span></div>
                                <span class="badge badge-<?= e($estado['class']) ?>"><?= e($estado['label']) ?></span>
                            </td>
                            <td class="num"><?= e(qty($fila['stock_minimo'])) ?> <span class="unit"><?= e($unidad) ?></span></td>
                            <td class="num <?= (float) $fila['faltante_minimo'] > 0 ? 'text-warning-strong' : '' ?>">
                                <?= e(qty($fila['faltante_minimo'])) ?>
                            </td>
                            <td class="num">
                                <span class="objective-chip"><?= e(qty($fila['objetivo'])) ?></span>
                                <div class="cell-sub">mín. × <?= e($factorLabel) ?></div>
                            </td>
                            <td class="num">
                                <strong class="<?= (float) $fila['faltante_objetivo'] > 0 ? 'text-warning-strong' : 'text-muted' ?>">
                                    <?= e(qty($fila['faltante_objetivo'])) ?>
                                </strong>
                            </td>
                            <?php if ($manage): ?>
                                <td class="td-actions">
                                    <a
                                        class="btn btn-secondary btn-sm"
                                        href="<?= e(url('/movimientos/nuevo?' . http_build_query($parametros))) ?>"
                                        title="Registrar entrada para reponer stock"
                                    >
                                        <?= icon('plus') ?><span>Reponer</span>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footnote">
            <?= icon('info') ?>
            <span>
                «Falta mínimo» es lo que falta para llegar al stock mínimo. «Falta objetivo» es lo que falta para llegar al objetivo
                (mínimo × <?= e($factorLabel) ?>). Podés ajustar el factor arriba o registrar la entrada directamente con el botón «Reponer».
            </span>
        </div>
    <?php endif; ?>
</section>
