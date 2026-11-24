<?php

declare(strict_types=1);

$manage = can_manage();
?>
<div class="page-head">
    <div>
        <p class="page-lead">Centralizá contactos y datos de tus proveedores para agilizar la reposición.</p>
    </div>
    <div class="page-actions">
        <?php if ($manage): ?>
            <a class="btn btn-primary" href="<?= e(url('/proveedores/nuevo')) ?>">
                <?= icon('plus') ?><span>Nuevo proveedor</span>
            </a>
        <?php else: ?>
            <span class="badge badge-muted"><?= icon('eye') ?> Modo consulta</span>
        <?php endif; ?>
    </div>
</div>

<section class="card">
    <?php if ($proveedores === []): ?>
        <div class="empty-state">
            <span class="empty-icon"><?= icon('truck') ?></span>
            <h3>Todavía no hay proveedores</h3>
            <p>Registrá el primero para asociarlo a tus artículos.</p>
            <?php if ($manage): ?>
                <a class="btn btn-primary" href="<?= e(url('/proveedores/nuevo')) ?>">
                    <?= icon('plus') ?><span>Crear proveedor</span>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Teléfono</th>
                        <th>Notas</th>
                        <th class="num">Artículos</th>
                        <th>Estado</th>
                        <?php if ($manage): ?><th class="th-actions">Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proveedores as $proveedor): ?>
                        <?php $activo = (int) $proveedor['activo'] === 1; ?>
                        <tr class="<?= $activo ? '' : 'row-inactive' ?>">
                            <td>
                                <div class="cell-title"><?= e($proveedor['nombre']) ?></div>
                                <div class="cell-sub">
                                    <?= $proveedor['contacto'] ? e($proveedor['contacto']) : 'Sin contacto registrado' ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($proveedor['telefono']): ?>
                                    <a class="text-link" href="tel:<?= e($proveedor['telefono']) ?>"><?= e($proveedor['telefono']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-wrap">
                                <?php if ($proveedor['notas']): ?>
                                    <?php $notas = (string) $proveedor['notas']; ?>
                                    <?= e(mb_strlen($notas) > 70 ? mb_substr($notas, 0, 70) . '…' : $notas) ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin notas</span>
                                <?php endif; ?>
                            </td>
                            <td class="num"><span class="count-pill"><?= e((string) $proveedor['total_articulos']) ?></span></td>
                            <td>
                                <span class="badge badge-<?= $activo ? 'success' : 'muted' ?>">
                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <?php if ($manage): ?>
                                <td class="td-actions">
                                    <a class="icon-btn" href="<?= e(url('/proveedores/' . $proveedor['id'] . '/editar')) ?>" title="Editar">
                                        <?= icon('edit') ?>
                                    </a>
                                    <?php if ($activo): ?>
                                        <form method="post" action="<?= e(url('/proveedores/' . $proveedor['id'] . '/desactivar')) ?>" data-confirm="¿Desactivar el proveedor «<?= e($proveedor['nombre']) ?>»? Los artículos conservan su historial.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="icon-btn icon-btn-danger" title="Desactivar">
                                                <?= icon('power') ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/proveedores/' . $proveedor['id'] . '/activar')) ?>">
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
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
