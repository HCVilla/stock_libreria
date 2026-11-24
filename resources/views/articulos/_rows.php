<?php

declare(strict_types=1);

$manage = can_manage();
$colspan = $manage ? 7 : 6;
?>
<?php if ($articulos === []): ?>
    <tr>
        <td colspan="<?= $colspan ?>">
            <div class="empty-state">
                <span class="empty-icon"><?= icon('search') ?></span>
                <h3>No encontramos artículos</h3>
                <p>Probá con otro nombre o código<?= $manage ? ', o creá el primero.' : '.' ?></p>
                <?php if ($manage): ?>
                    <a class="btn btn-primary" href="<?= e(url('/articulos/nuevo')) ?>">
                        <?= icon('plus') ?><span>Nuevo artículo</span>
                    </a>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($articulos as $articulo): ?>
        <?php $state = stock_state($articulo); ?>
        <tr class="<?= (int) $articulo['activo'] === 1 ? '' : 'row-inactive' ?>">
            <td><code class="code"><?= e($articulo['codigo']) ?></code></td>
            <td>
                <div class="cell-title"><?= e($articulo['nombre']) ?></div>
                <div class="cell-sub">
                    <?= e($articulo['tipo_nombre']) ?>
                    <?php if (!empty($articulo['proveedor_nombre'])): ?>
                        · <?= e($articulo['proveedor_nombre']) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td class="num"><?= e(money($articulo['precio_costo'])) ?></td>
            <td class="num"><?= e(money($articulo['precio_venta'])) ?></td>
            <td class="num">
                <strong><?= e(qty($articulo['stock_actual'])) ?></strong>
                <span class="unit"><?= e($articulo['unidad_medida']) ?></span>
                <div class="cell-sub">mín. <?= e(qty($articulo['stock_minimo'])) ?></div>
            </td>
            <td>
                <span class="badge badge-<?= e($state['class']) ?>"><?= e($state['label']) ?></span>
            </td>
            <?php if ($manage): ?>
                <td class="td-actions">
                    <a class="icon-btn" href="<?= e(url('/articulos/' . $articulo['id'] . '/editar')) ?>" title="Editar">
                        <?= icon('edit') ?>
                    </a>
                    <?php if ((int) $articulo['activo'] === 1): ?>
                        <form method="post" action="<?= e(url('/articulos/' . $articulo['id'] . '/desactivar')) ?>" data-confirm="¿Desactivar «<?= e($articulo['nombre']) ?>»? El historial se conserva.">
                            <?= csrf_field() ?>
                            <button type="submit" class="icon-btn icon-btn-danger" title="Desactivar">
                                <?= icon('power') ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('/articulos/' . $articulo['id'] . '/activar')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="icon-btn icon-btn-success" title="Reactivar">
                                <?= icon('check') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
