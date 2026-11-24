<?php

declare(strict_types=1);

$manage = can_manage();
?>
<div class="page-head">
    <div>
        <p class="page-lead">Organizá el catálogo por familias: escolar, oficina, papelería, textos y más.</p>
    </div>
    <div class="page-actions">
        <?php if ($manage): ?>
            <a class="btn btn-primary" href="<?= e(url('/tipos/nuevo')) ?>">
                <?= icon('plus') ?><span>Nuevo tipo</span>
            </a>
        <?php else: ?>
            <span class="badge badge-muted"><?= icon('eye') ?> Modo consulta</span>
        <?php endif; ?>
    </div>
</div>

<section class="card">
    <?php if ($tipos === []): ?>
        <div class="empty-state">
            <span class="empty-icon"><?= icon('tag') ?></span>
            <h3>Todavía no hay tipos de artículo</h3>
            <p>Creá el primero para poder clasificar tus artículos.</p>
            <?php if ($manage): ?>
                <a class="btn btn-primary" href="<?= e(url('/tipos/nuevo')) ?>">
                    <?= icon('plus') ?><span>Crear tipo</span>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th class="num">Artículos</th>
                        <th>Estado</th>
                        <?php if ($manage): ?><th class="th-actions">Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                        <?php $activo = (int) $tipo['activo'] === 1; ?>
                        <tr class="<?= $activo ? '' : 'row-inactive' ?>">
                            <td>
                                <div class="cell-title"><?= e($tipo['nombre']) ?></div>
                                <div class="cell-sub">Creado el <?= e(date('d/m/Y', strtotime((string) $tipo['creado_en']))) ?></div>
                            </td>
                            <td class="cell-wrap"><?= $tipo['descripcion'] ? e($tipo['descripcion']) : '<span class="text-muted">Sin descripción</span>' ?></td>
                            <td class="num"><span class="count-pill"><?= e((string) $tipo['total_articulos']) ?></span></td>
                            <td>
                                <span class="badge badge-<?= $activo ? 'success' : 'muted' ?>">
                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <?php if ($manage): ?>
                                <td class="td-actions">
                                    <a class="icon-btn" href="<?= e(url('/tipos/' . $tipo['id'] . '/editar')) ?>" title="Editar">
                                        <?= icon('edit') ?>
                                    </a>
                                    <?php if ($activo): ?>
                                        <form method="post" action="<?= e(url('/tipos/' . $tipo['id'] . '/desactivar')) ?>" data-confirm="¿Desactivar el tipo «<?= e($tipo['nombre']) ?>»? Los artículos conservan su historial.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="icon-btn icon-btn-danger" title="Desactivar">
                                                <?= icon('power') ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('/tipos/' . $tipo['id'] . '/activar')) ?>">
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
