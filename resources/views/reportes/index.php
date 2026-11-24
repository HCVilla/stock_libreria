<?php

declare(strict_types=1);

$movQuery = http_build_query([
    'desde' => $desde,
    'hasta' => $hasta,
    'tipo' => $tipo,
]);
$totalMovimientos = (int) ($resumen['total'] ?? 0);
$totalEntradas = (int) ($resumen['entradas'] ?? 0);
$totalSalidas = (int) ($resumen['salidas'] ?? 0);
$totalAjustes = (int) ($resumen['ajustes'] ?? 0);
$unidadesEntrada = (float) ($resumen['unidades_entrada'] ?? 0);
$unidadesSalida = (float) ($resumen['unidades_salida'] ?? 0);
$importeNeto = (float) ($resumen['importe_neto'] ?? 0);
$bajoCantidad = (int) ($stockBajoTotales['articulos'] ?? 0);
$bajoFaltante = (float) ($stockBajoTotales['faltante'] ?? 0);
$bajoValor = (float) ($stockBajoTotales['valor'] ?? 0);
$valArticulos = (int) ($valorizacionTotales['articulos'] ?? 0);
$valUnidades = (float) ($valorizacionTotales['unidades'] ?? 0);
$valCosto = (float) ($valorizacionTotales['costo'] ?? 0);
$valVenta = (float) ($valorizacionTotales['venta'] ?? 0);
$valMargen = (float) ($valorizacionTotales['margen'] ?? 0);
?>
<div class="page-head">
    <div>
        <p class="page-lead">Reportes de stock bajo, valorización del inventario y movimientos por período, con exportación a CSV.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('/reportes/exportar/stock-bajo')) ?>">
            <?= icon('download') ?><span>Stock bajo CSV</span>
        </a>
        <a class="btn btn-secondary" href="<?= e(url('/reportes/exportar/valorizacion')) ?>">
            <?= icon('download') ?><span>Valorización CSV</span>
        </a>
        <a class="btn btn-primary" href="<?= e(url('/reportes/exportar/movimientos?' . $movQuery)) ?>">
            <?= icon('download') ?><span>Movimientos CSV</span>
        </a>
    </div>
</div>

<nav class="report-nav" aria-label="Secciones del reporte">
    <a href="#stock-bajo"><?= icon('alert') ?> Stock bajo</a>
    <a href="#valorizacion"><?= icon('coins') ?> Valorización</a>
    <a href="#movimientos"><?= icon('swap') ?> Movimientos</a>
</nav>

<section class="stats-grid stats-grid-sm" aria-label="Totales de valorización">
    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-teal"><?= icon('box') ?></span>
        <div class="stat-body">
            <span class="stat-label">Artículos valuados</span>
            <strong class="stat-value"><?= e((string) $valArticulos) ?></strong>
            <span class="stat-hint"><?= e(qty($valUnidades)) ?> unidades en stock</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-blue"><?= icon('coins') ?></span>
        <div class="stat-body">
            <span class="stat-label">Valor a costo</span>
            <strong class="stat-value"><?= e(money($valCosto)) ?></strong>
            <span class="stat-hint">Capital inmovilizado en inventario</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-violet"><?= icon('wallet') ?></span>
        <div class="stat-body">
            <span class="stat-label">Valor a venta</span>
            <strong class="stat-value"><?= e(money($valVenta)) ?></strong>
            <span class="stat-hint">Potencial de venta del stock actual</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-success"><?= icon('chart') ?></span>
        <div class="stat-body">
            <span class="stat-label">Margen potencial</span>
            <strong class="stat-value"><?= e(money($valMargen)) ?></strong>
            <span class="stat-hint">Diferencia entre venta y costo</span>
        </div>
    </article>
</section>

<section class="card report-section" id="stock-bajo">
    <header class="card-header">
        <div>
            <h2>Stock bajo</h2>
            <p>Artículos activos con stock actual igual o por debajo del mínimo.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(url('/reportes/exportar/stock-bajo')) ?>">
            <?= icon('download') ?><span>Exportar CSV</span>
        </a>
    </header>

    <?php if ($stockBajo === []): ?>
        <div class="empty-state compact">
            <span class="empty-icon empty-icon-success"><?= icon('check') ?></span>
            <h3>Sin stock bajo</h3>
            <p>Todos los artículos activos están por encima de su mínimo.</p>
        </div>
    <?php else: ?>
        <div class="report-summary">
            <span><strong><?= e((string) $bajoCantidad) ?></strong> artículos</span>
            <span>Faltante total: <strong><?= e(qty($bajoFaltante)) ?></strong> unidades</span>
            <span>Reposición estimada a costo: <strong><?= e(money($bajoValor)) ?></strong></span>
        </div>

        <div class="table-wrap">
            <table class="table table-reports">
                <thead>
                    <tr>
                        <th>Artículo</th>
                        <th>Tipo</th>
                        <th class="num">Stock</th>
                        <th class="num">Mínimo</th>
                        <th class="num">Faltante</th>
                        <th class="num">Valor a costo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stockBajo as $fila): ?>
                        <?php $estado = stock_state($fila); ?>
                        <tr>
                            <td>
                                <div class="cell-title"><?= e($fila['nombre']) ?></div>
                                <div class="cell-sub"><code><?= e($fila['codigo']) ?></code> · <?= e($fila['proveedor_nombre'] ?? 'Sin proveedor') ?></div>
                            </td>
                            <td><?= e($fila['tipo_nombre']) ?></td>
                            <td class="num">
                                <strong><?= e(qty($fila['stock_actual'])) ?></strong>
                                <span class="unit"><?= e($fila['unidad_medida']) ?></span>
                                <div><span class="badge badge-<?= e($estado['class']) ?>"><?= e($estado['label']) ?></span></div>
                            </td>
                            <td class="num"><?= e(qty($fila['stock_minimo'])) ?></td>
                            <td class="num text-warning-strong"><?= e(qty($fila['faltante'])) ?></td>
                            <td class="num"><?= e(money($fila['valor_faltante'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card report-section" id="valorizacion">
    <header class="card-header">
        <div>
            <h2>Valorización del inventario</h2>
            <p>Solo artículos activos. El margen potencial es la diferencia entre valor de venta y costo.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(url('/reportes/exportar/valorizacion')) ?>">
            <?= icon('download') ?><span>Exportar CSV</span>
        </a>
    </header>

    <?php if ($valorizacion === []): ?>
        <div class="empty-state compact">
            <span class="empty-icon"><?= icon('box') ?></span>
            <h3>Sin artículos activos</h3>
            <p>Creá artículos para poder valuar el inventario.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-reports">
                <thead>
                    <tr>
                        <th>Artículo</th>
                        <th>Tipo</th>
                        <th class="num">Stock</th>
                        <th class="num">Costo unit.</th>
                        <th class="num">Venta unit.</th>
                        <th class="num">Valor costo</th>
                        <th class="num">Valor venta</th>
                        <th class="num">Margen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($valorizacion as $fila): ?>
                        <tr>
                            <td>
                                <div class="cell-title"><?= e($fila['nombre']) ?></div>
                                <div class="cell-sub"><code><?= e($fila['codigo']) ?></code> · <?= e($fila['proveedor_nombre'] ?? 'Sin proveedor') ?></div>
                            </td>
                            <td><?= e($fila['tipo_nombre']) ?></td>
                            <td class="num"><?= e(qty($fila['stock_actual'])) ?> <span class="unit"><?= e($fila['unidad_medida']) ?></span></td>
                            <td class="num"><?= e(money($fila['precio_costo'])) ?></td>
                            <td class="num"><?= e(money($fila['precio_venta'])) ?></td>
                            <td class="num"><strong><?= e(money($fila['valor_costo'])) ?></strong></td>
                            <td class="num"><?= e(money($fila['valor_venta'])) ?></td>
                            <td class="num <?= (float) $fila['margen'] < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= e(money($fila['margen'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5">Totales</th>
                        <th class="num"><?= e(money($valCosto)) ?></th>
                        <th class="num"><?= e(money($valVenta)) ?></th>
                        <th class="num"><?= e(money($valMargen)) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card report-section" id="movimientos">
    <header class="card-header">
        <div>
            <h2>Movimientos por período</h2>
            <p>Entradas, salidas y ajustes registrados entre las fechas seleccionadas.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(url('/reportes/exportar/movimientos?' . $movQuery)) ?>">
            <?= icon('download') ?><span>Exportar CSV</span>
        </a>
    </header>

    <div class="report-filters">
        <form class="filter-form" method="get" action="<?= e(url('/reportes')) ?>#movimientos">
            <label class="inline-field" for="desde">
                <span>Desde</span>
                <input class="input" id="desde" type="date" name="desde" value="<?= e($desde) ?>" required>
            </label>

            <label class="inline-field" for="hasta">
                <span>Hasta</span>
                <input class="input" id="hasta" type="date" name="hasta" value="<?= e($hasta) ?>" required>
            </label>

            <label class="inline-field" for="tipo">
                <span>Tipo</span>
                <select class="input filter-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="entrada"<?= selected($tipo, 'entrada') ?>>Entradas</option>
                    <option value="salida"<?= selected($tipo, 'salida') ?>>Salidas</option>
                    <option value="ajuste"<?= selected($tipo, 'ajuste') ?>>Ajustes</option>
                </select>
            </label>

            <button type="submit" class="btn btn-secondary"><?= icon('filter') ?><span>Aplicar</span></button>
            <a class="btn btn-secondary" href="<?= e(url('/reportes')) ?>">Este mes</a>
        </form>
    </div>

    <div class="report-summary">
        <span><strong><?= e((string) $totalMovimientos) ?></strong> movimientos</span>
        <span>Entradas: <strong><?= e((string) $totalEntradas) ?></strong> (<?= e(qty($unidadesEntrada)) ?> u.)</span>
        <span>Salidas: <strong><?= e((string) $totalSalidas) ?></strong> (<?= e(qty($unidadesSalida)) ?> u.)</span>
        <span>Ajustes: <strong><?= e((string) $totalAjustes) ?></strong></span>
        <span>Importe neto: <strong><?= e(money($importeNeto)) ?></strong></span>
    </div>

    <?php if ($movimientos === []): ?>
        <div class="empty-state compact">
            <span class="empty-icon"><?= icon('clock') ?></span>
            <h3>Sin movimientos en el período</h3>
            <p>Probá con otro rango de fechas o quitá el filtro de tipo.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-reports table-movements">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Artículo</th>
                        <th class="num">Cantidad</th>
                        <th class="num">Precio</th>
                        <th class="num">Importe</th>
                        <th class="num">Stock res.</th>
                        <th>Detalle</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <?php
                        $meta = movement_meta((string) $movimiento['tipo_movimiento']);
                        $cantidad = (float) $movimiento['cantidad'];
                        ?>
                        <tr>
                            <td class="cell-nowrap"><?= e(format_datetime($movimiento['fecha_hora'])) ?></td>
                            <td><span class="badge badge-<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                            <td>
                                <div class="cell-title"><?= e($movimiento['articulo_nombre']) ?></div>
                                <div class="cell-sub"><code><?= e($movimiento['codigo']) ?></code> · <?= e($movimiento['tipo_nombre']) ?></div>
                            </td>
                            <td class="num <?= $cantidad < 0 ? 'text-danger' : 'text-success' ?>">
                                <strong><?= e(($cantidad > 0 ? '+' : '') . qty($cantidad)) ?></strong>
                                <span class="unit"><?= e($movimiento['unidad_medida']) ?></span>
                            </td>
                            <td class="num"><?= e(money($movimiento['precio_unitario_momento'])) ?></td>
                            <td class="num"><?= e(money($movimiento['importe'])) ?></td>
                            <td class="num"><?= e(qty($movimiento['stock_resultante'])) ?></td>
                            <td class="cell-wrap">
                                <?= e($movimiento['motivo'] ?: 'Sin motivo') ?>
                                <?php if (!empty($movimiento['referencia'])): ?>
                                    <div class="cell-sub">Ref. <?= e($movimiento['referencia']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($movimiento['usuario_nombre']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalMovimientos > count($movimientos)): ?>
            <div class="card-footnote">
                <?= icon('info') ?>
                <span>
                    Se muestran los primeros <?= e((string) count($movimientos)) ?> movimientos. El CSV incluye hasta 50.000 registros
                    del período seleccionado.
                </span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
