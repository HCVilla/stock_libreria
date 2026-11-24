<?php

declare(strict_types=1);

use App\Core\Auth;

$total = (int) ($stats['total'] ?? 0);
$activos = (int) ($stats['activos'] ?? 0);
$inactivos = (int) ($stats['inactivos'] ?? 0);
$admins = (int) ($stats['admins'] ?? 0);
$hayFiltros = $q !== '' || $rol !== '';
$rolClasses = [
    'admin' => 'violet',
    'encargado' => 'teal',
    'vendedor' => 'blue',
    'consulta' => 'muted',
];
$actualId = Auth::id();
?>
<div class="page-head">
    <div>
        <p class="page-lead">Administrá quién puede acceder al sistema y qué rol tiene cada usuario.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/usuarios/nuevo')) ?>">
            <?= icon('plus') ?><span>Nuevo usuario</span>
        </a>
    </div>
</div>

<section class="stats-grid stats-grid-sm" aria-label="Resumen de usuarios">
    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-teal"><?= icon('users') ?></span>
        <div class="stat-body">
            <span class="stat-label">Usuarios</span>
            <strong class="stat-value"><?= e((string) $total) ?></strong>
            <span class="stat-hint">En el sistema</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-success"><?= icon('check') ?></span>
        <div class="stat-body">
            <span class="stat-label">Activos</span>
            <strong class="stat-value"><?= e((string) $activos) ?></strong>
            <span class="stat-hint">Pueden iniciar sesión</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-violet"><?= icon('key') ?></span>
        <div class="stat-body">
            <span class="stat-label">Administradores</span>
            <strong class="stat-value"><?= e((string) $admins) ?></strong>
            <span class="stat-hint">Con acceso total</span>
        </div>
    </article>

    <article class="stat-card stat-card-sm">
        <span class="stat-icon stat-icon-amber"><?= icon('power') ?></span>
        <div class="stat-body">
            <span class="stat-label">Inactivos</span>
            <strong class="stat-value"><?= e((string) $inactivos) ?></strong>
            <span class="stat-hint">Sin acceso al sistema</span>
        </div>
    </article>
</section>

<section class="card">
    <div class="toolbar">
        <form class="filter-form" method="get" action="<?= e(url('/usuarios')) ?>" role="search">
            <div class="search-box">
                <?= icon('search') ?>
                <input
                    type="search"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="Buscar por nombre o usuario…"
                    autocomplete="off"
                >
            </div>

            <select class="input filter-select" name="rol" aria-label="Filtrar por rol">
                <option value="">Todos los roles</option>
                <?php foreach ($roles as $rolItem): ?>
                    <option value="<?= e($rolItem) ?>"<?= selected($rol, $rolItem) ?>><?= e(role_label($rolItem)) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-secondary"><?= icon('filter') ?><span>Filtrar</span></button>

            <?php if ($hayFiltros): ?>
                <a class="btn btn-secondary" href="<?= e(url('/usuarios')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="toolbar-meta">
            <span class="result-count">
                <?= e((string) count($usuarios)) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?>
            </span>
        </div>
    </div>

    <?php if ($usuarios === []): ?>
        <div class="empty-state">
            <span class="empty-icon"><?= icon('users') ?></span>
            <h3>Sin usuarios<?= $hayFiltros ? ' para este filtro' : '' ?></h3>
            <p><?= $hayFiltros ? 'Probá con otro texto o rol.' : 'Creá el primer usuario para que pueda acceder al sistema.' ?></p>
            <?php if ($hayFiltros): ?>
                <a class="btn btn-secondary" href="<?= e(url('/usuarios')) ?>">Quitar filtros</a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= e(url('/usuarios/nuevo')) ?>">
                    <?= icon('plus') ?><span>Crear usuario</span>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-users">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th>Creado</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $item): ?>
                        <?php
                        $activo = (int) $item['activo'] === 1;
                        $esPropio = (int) $item['id'] === $actualId;
                        $claseRol = $rolClasses[(string) $item['rol']] ?? 'muted';
                        ?>
                        <tr class="<?= $activo ? '' : 'row-inactive' ?>">
                            <td>
                                <div class="cell-title">
                                    <?= e($item['nombre']) ?>
                                    <?php if ($esPropio): ?><span class="badge badge-teal badge-xs">Vos</span><?php endif; ?>
                                </div>
                                <div class="cell-sub"><code>@<?= e($item['usuario']) ?></code></div>
                            </td>
                            <td>
                                <span class="badge badge-<?= e($claseRol) ?>"><?= e(role_label((string) $item['rol'])) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $activo ? 'success' : 'muted' ?>">
                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td class="cell-nowrap"><?= e(format_datetime($item['ultimo_acceso'])) ?></td>
                            <td class="cell-nowrap"><?= e(format_date($item['creado_en'])) ?></td>
                            <td class="td-actions">
                                <a class="icon-btn" href="<?= e(url('/usuarios/' . $item['id'] . '/editar')) ?>" title="Editar usuario">
                                    <?= icon('edit') ?>
                                </a>
                                <?php if ($esPropio): ?>
                                    <span class="icon-btn is-disabled" title="No podés desactivar tu propia cuenta">
                                        <?= icon('lock') ?>
                                    </span>
                                <?php elseif ($activo): ?>
                                    <form method="post" action="<?= e(url('/usuarios/' . $item['id'] . '/desactivar')) ?>" data-confirm="¿Desactivar el usuario «<?= e($item['nombre']) ?>»? No podrá iniciar sesión.">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn icon-btn-danger" title="Desactivar">
                                            <?= icon('power') ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= e(url('/usuarios/' . $item['id'] . '/activar')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn icon-btn-success" title="Reactivar">
                                            <?= icon('check') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
