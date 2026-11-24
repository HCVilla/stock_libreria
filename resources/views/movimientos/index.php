<?php

declare(strict_types=1);

$manage = can_manage();
$total = (int) ($resumen['total'] ?? 0);
$entradas = (int) ($resumen['entradas'] ?? 0);
$salidas = (int) ($resumen['salidas'] ?? 0);
$ajustes = (int) ($resumen['ajustes'] ?? 0);
$unidadesEntrada = (float) ($resumen['unidades_entrada'] ?? 0);
$unidadesSalida = (float) ($resumen['unidades_salida'] ?? 0);
$hayFiltros = $q !== '' || $tipo !== '';
?>
<div class="page-head">
    <div>
        <p class="page-lead">Historial auditado de entradas, salidas y ajustes de inventario.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('/reposicion')) ?>">
            <?= icon('target') ?><span>Reposición pendiente</span>
        </a>
        <?php if ($manage): ?>
            <a class="btn btn-primary" href="<?= e(url('/movimientos/nuevo')) ?>">
                <?= icon('plus') ?><span>Nuevo movimiento</span>
            </a>
        <?php else: ?>
            <span class="badge badge-muted"><?= icon('eye') ?> Modo consulta</span>
        <?php endif; ?>
    </div>
</div>

<section class="stats-grid stats-grid-sm" aria-label="Resumen de movimientos">
    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-teal"><?= icon('swap') ?></span>
        <div class="stat-body">
            <span class="stat-label">Movimientos</span>
            <strong class="stat-value"><?= e((string) $total) ?></strong>
            <span class="stat-hint">Registros históricos</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-teal"><?= icon('login') ?></span>
        <div class="stat-body">
            <span class="stat-label">Entradas</span>
            <strong class="stat-value"><?= e((string) $entradas) ?></strong>
            <span class="stat-hint"><?= e(qty($unidadesEntrada)) ?> unidades ingresadas</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-amber"><?= icon('logout') ?></span>
        <div class="stat-body">
            <span class="stat-label">Salidas</span>
            <strong class="stat-value"><?= e((string) $salidas) ?></strong>
            <span class="stat-hint"><?= e(qty($unidadesSalida)) ?> unidades retiradas</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-blue"><?= icon('filter') ?></span>
        <div class="stat-body">
            <span class="stat-label">Ajustes</span>
            <strong class="stat-value"><?= e((string) $ajustes) ?></strong>
            <span class="stat-hint">Correcciones de inventario</span>
        </div>
    </article>
</section>

<section class="card">
    <div class="toolbar">
        <form class="filter-form" method="get" action="<?= e(url('/movimientos')) ?>" role="search">
            <div class="search-box">
                <?= icon('search') ?>
                <input
                    type="search"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="Buscar por artículo, código, motivo o referencia…"
                    autocomplete="off"
                >
            </div>

            <select class="input filter-select" name="tipo" aria-label="Filtrar por tipo">
                <option value="">Todos los tipos</option>
                <option value="entrada"<?= selected($tipo, 'entrada') ?>>Entradas</option>
                <option value="salida"<?= selected($tipo, 'salida') ?>>Salidas</option>
                <option value="ajuste"<?= selected($tipo, 'ajuste') ?>>Ajustes</option>
            </select>

            <button type="submit" class="btn btn-secondary"><?= icon('filter') ?><span>Filtrar</span></button>

            <?php if ($hayFiltros): ?>
                <a class="btn btn-secondary" href="<?= e(url('/movimientos')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="toolbar-meta">
            <span class="result-count">
                <?= e((string) count($movimientos)) ?> movimiento<?= count($movimientos) === 1 ? '' : 's' ?>
            </span>
        </div>
    </div>

    <?php if ($movimientos === []): ?>
        <div class="empty-state">
            <span class="empty-icon"><?= icon('swap') ?></span>
            <h3>Sin movimientos<?= $hayFiltros ? ' para este filtro' : '' ?></h3>
            <p>
                <?= $hayFiltros
                    ? 'Probá con otro texto o quitá los filtros.'
                    : 'Las entradas, salidas y ajustes de stock van a aparecer acá.' ?>
            </p>
            <?php if ($manage && !$hayFiltros): ?>
                <a class="btn btn-primary" href="<?= e(url('/movimientos/nuevo')) ?>">
                    <?= icon('plus') ?><span>Registrar movimiento</span>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-movements">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Artículo</th>
                        <th>Tipo</th>
                        <th class="num">Cantidad</th>
                        <th class="num">Stock resultante</th>
                        <th class="num">Valor</th>
                        <th>Detalle</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <?php
                        $meta = movement_meta((string) $movimiento['tipo_movimiento']);
                        $cantidad = (float) $movimiento['cantidad'];
                        $precio = (float) $movimiento['precio_unitario_momento'];
                        $importe = $cantidad * $precio;
                        ?>
                        <tr>
                            <td class="cell-nowrap"><?= e(format_datetime($movimiento['fecha_hora'])) ?></td>
                            <td>
                                <div class="cell-title"><?= e($movimiento['articulo_nombre']) ?></div>
                                <div class="cell-sub"><code><?= e($movimiento['codigo']) ?></code> · <?= e($movimiento['tipo_nombre']) ?></div>
                            </td>
                            <td><span class="badge badge-<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
                            <td class="num <?= $cantidad < 0 ? 'text-danger' : 'text-success' ?>">
                                <strong><?= e(($cantidad > 0 ? '+' : '') . qty($cantidad)) ?></strong>
                                <span class="unit"><?= e($movimiento['unidad_medida']) ?></span>
                            </td>
                            <td class="num"><strong><?= e(qty($movimiento['stock_resultante'])) ?></strong></td>
                            <td class="num">
                                <div><?= e(money($importe)) ?></div>
                                <div class="cell-sub"><?= e(money($precio)) ?> c/u</div>
                            </td>
                            <td class="cell-wrap">
                                <?php if (!empty($movimiento['motivo'])): ?>
                                    <div><?= e($movimiento['motivo']) ?></div>
                                <?php else: ?>
                                    <div class="text-muted">Sin motivo</div>
                                <?php endif; ?>
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
    <?php endif; ?>
</section>
