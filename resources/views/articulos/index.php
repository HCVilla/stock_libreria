<?php

declare(strict_types=1);

$manage = can_manage();
?>
<div class="page-head">
    <div>
        <p class="page-lead">Buscá por nombre o código y controlá el stock de cada artículo.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('/articulos/exportar')) ?>">
            <?= icon('download') ?><span>Exportar CSV</span>
        </a>
        <?php if ($manage): ?>
            <a class="btn btn-secondary" href="<?= e(url('/articulos/importar')) ?>">
                <?= icon('upload') ?><span>Importar CSV</span>
            </a>
            <a class="btn btn-primary" href="<?= e(url('/articulos/nuevo')) ?>">
                <?= icon('plus') ?><span>Nuevo artículo</span>
            </a>
        <?php else: ?>
            <span class="badge badge-muted"><?= icon('eye') ?> Modo consulta</span>
        <?php endif; ?>
    </div>
</div>

<section class="card">
    <div class="toolbar">
        <form class="search-box" method="get" action="<?= e(url('/articulos')) ?>" role="search">
            <?= icon('search') ?>
            <input
                type="search"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Buscar por nombre o código…"
                autocomplete="off"
                data-article-search
                data-endpoint="<?= e(url('/api/articulos')) ?>"
            >
            <span class="search-spinner" data-search-spinner hidden></span>
            <?php if ($q !== ''): ?>
                <a class="search-clear" href="<?= e(url('/articulos')) ?>" title="Limpiar búsqueda"><?= icon('close') ?></a>
            <?php endif; ?>
        </form>

        <button
            type="button"
            class="btn btn-secondary"
            data-barcode-scan
            data-barcode-target="[data-article-search]"
            title="Escanear código de barras"
        >
            <?= icon('camera') ?><span>Escanear</span>
        </button>

        <div class="toolbar-meta">
            <span class="result-count" data-result-count>
                <?= e((string) count($articulos)) ?> artículo<?= count($articulos) === 1 ? '' : 's' ?>
            </span>
            <span class="badge badge-warning"><?= icon('alert') ?> Bajo stock: actual ≤ mínimo</span>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th class="th-code">Código</th>
                    <th>Artículo</th>
                    <th class="num">Costo</th>
                    <th class="num">Venta</th>
                    <th class="num">Stock</th>
                    <th>Estado</th>
                    <?php if ($manage): ?><th class="th-actions">Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody data-article-rows>
                <?= App\Core\View::partial('articulos._rows', ['articulos' => $articulos]) ?>
            </tbody>
        </table>
    </div>
</section>
