<?php

declare(strict_types=1);

use App\Core\Auth;

$total = (int) ($stats['total'] ?? 0);
$activos = (int) ($stats['activos'] ?? 0);
$inactivos = (int) ($stats['inactivos'] ?? 0);
$sinStock = (int) ($stats['sin_stock'] ?? 0);
$bajoStock = (int) ($stats['bajo_stock'] ?? 0);
$valorCosto = (float) ($stats['valor_costo'] ?? 0);
$valorVenta = (float) ($stats['valor_venta'] ?? 0);
$user = Auth::user();

$movementMeta = [
    'entrada' => ['label' => 'Entrada', 'class' => 'success', 'sign' => '+'],
    'salida' => ['label' => 'Salida', 'class' => 'danger', 'sign' => ''],
    'ajuste' => ['label' => 'Ajuste', 'class' => 'info', 'sign' => ''],
];
?>
<div class="page-head">
    <div>
        <p class="page-lead">
            Hola, <?= e($user['nombre'] ?? '') ?>. Este es el estado actual del inventario.
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('/reportes')) ?>">
            <?= icon('report') ?><span>Reportes</span>
        </a>
        <?php if (can_manage()): ?>
            <a class="btn btn-secondary" href="<?= e(url('/tipos/nuevo')) ?>">
                <?= icon('plus') ?><span>Tipo</span>
            </a>
            <a class="btn btn-secondary" href="<?= e(url('/proveedores/nuevo')) ?>">
                <?= icon('plus') ?><span>Proveedor</span>
            </a>
            <a class="btn btn-secondary" href="<?= e(url('/movimientos/nuevo')) ?>">
                <?= icon('swap') ?><span>Movimiento</span>
            </a>
            <a class="btn btn-primary" href="<?= e(url('/articulos/nuevo')) ?>">
                <?= icon('plus') ?><span>Nuevo artículo</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<section class="stats-grid" aria-label="Indicadores del inventario">
    <article class="stat-card">
        <span class="stat-icon stat-icon-teal"><?= icon('box') ?></span>
        <div class="stat-body">
            <span class="stat-label">Artículos activos</span>
            <strong class="stat-value"><?= e((string) $activos) ?></strong>
            <span class="stat-hint"><?= e((string) $inactivos) ?> inactivo<?= $inactivos === 1 ? '' : 's' ?> · <?= e((string) $total) ?> en total</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon-amber"><?= icon('alert') ?></span>
        <div class="stat-body">
            <span class="stat-label">Bajo stock</span>
            <strong class="stat-value"><?= e((string) $bajoStock) ?></strong>
            <span class="stat-hint"><?= e((string) $sinStock) ?> sin stock · requieren reposición</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon-blue"><?= icon('coins') ?></span>
        <div class="stat-body">
            <span class="stat-label">Valor a costo</span>
            <strong class="stat-value stat-value-money"><?= e(money($valorCosto)) ?></strong>
            <span class="stat-hint">Inventario activo valuado al costo</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon-violet"><?= icon('wallet') ?></span>
        <div class="stat-body">
            <span class="stat-label">Valor a venta</span>
            <strong class="stat-value stat-value-money"><?= e(money($valorVenta)) ?></strong>
            <span class="stat-hint">Potencial de venta del stock actual</span>
        </div>
    </article>
</section>

<div class="dashboard-grid">
    <section class="card">
        <header class="card-header">
            <div>
                <h2>Reposición sugerida</h2>
                <p>Artículos con stock igual o por debajo del mínimo.</p>
            </div>
            <a class="text-link" href="<?= e(url('/reposicion')) ?>">Ver reposición <?= icon('arrow-left') ?></a>
        </header>

        <?php if ($lowStock === []): ?>
            <div class="empty-state compact">
                <span class="empty-icon empty-icon-success"><?= icon('check') ?></span>
                <h3>Stock saludable</h3>
                <p>Ningún artículo activo está por debajo de su mínimo configurado.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th>Artículo</th>
                            <th>Stock</th>
                            <th>Mínimo</th>
                            <th>Estado</th>
                            <?php if (can_manage()): ?><th class="th-actions">Acción</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStock as $item): ?>
                            <?php $state = stock_state($item); ?>
                            <tr>
                                <td>
                                    <div class="cell-title"><?= e($item['nombre']) ?></div>
                                    <div class="cell-sub"><code><?= e($item['codigo']) ?></code> · <?= e($item['tipo_nombre']) ?></div>
                                </td>
                                <td class="num"><strong><?= e(qty($item['stock_actual'])) ?></strong> <span class="unit"><?= e($item['unidad_medida']) ?></span></td>
                                <td class="num"><?= e(qty($item['stock_minimo'])) ?></td>
                                <td><span class="badge badge-<?= e($state['class']) ?>"><?= e($state['label']) ?></span></td>
                                <?php if (can_manage()): ?>
                                    <td class="td-actions">
                                        <a class="icon-btn" href="<?= e(url('/articulos/' . $item['id'] . '/editar')) ?>" title="Editar artículo">
                                            <?= icon('edit') ?>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <header class="card-header">
            <div>
                <h2>Últimos movimientos</h2>
                <p>Entradas, salidas y ajustes recientes.</p>
            </div>
            <a class="text-link" href="<?= e(url('/movimientos')) ?>">Ver todos <?= icon('arrow-left') ?></a>
        </header>

        <?php if ($recent === []): ?>
            <div class="empty-state compact">
                <span class="empty-icon"><?= icon('clock') ?></span>
                <h3>Sin movimientos</h3>
                <p>El stock inicial de cada artículo genera el primer movimiento.</p>
            </div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($recent as $movimiento): ?>
                    <?php $meta = $movementMeta[$movimiento['tipo_movimiento']] ?? $movementMeta['ajuste']; ?>
                    <li class="timeline-item">
                        <span class="timeline-dot badge-<?= e($meta['class']) ?>"></span>
                        <div class="timeline-body">
                            <div class="timeline-top">
                                <strong><?= e($movimiento['articulo_nombre']) ?></strong>
                                <span class="badge badge-<?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
                            </div>
                            <div class="timeline-meta">
                                <span><?= e($meta['sign'] . qty($movimiento['cantidad'])) ?> unidades</span>
                                <span>por <?= e($movimiento['usuario_nombre']) ?></span>
                                <span><?= e(format_datetime($movimiento['fecha_hora'])) ?></span>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section class="info-strip">
    <div class="info-strip-item">
        <span class="info-strip-icon"><?= icon('tag') ?></span>
        <span><strong><?= e((string) $tiposCount) ?></strong> tipos de artículo</span>
    </div>
    <div class="info-strip-item">
        <span class="info-strip-icon"><?= icon('truck') ?></span>
        <span><strong><?= e((string) $proveedoresCount) ?></strong> proveedores registrados</span>
    </div>
    <div class="info-strip-item">
        <span class="info-strip-icon"><?= icon('alert') ?></span>
        <span>Regla de alerta: <strong>stock actual ≤ stock mínimo</strong></span>
    </div>
</section>
