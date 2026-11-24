<?php

declare(strict_types=1);

use App\Core\View;

$modo = (string) ($data['modo'] ?? 'actualizar');
?>
<div class="page-head">
    <div>
        <a class="back-link" href="<?= e(url('/articulos')) ?>"><?= icon('arrow-left') ?> Volver a artículos</a>
        <p class="page-lead">
            Importá artículos desde un CSV. Las filas nuevas se crean con su stock inicial; las existentes se actualizan sin tocar el stock.
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= e(url('/articulos/plantilla')) ?>">
            <?= icon('download') ?><span>Descargar plantilla</span>
        </a>
        <a class="btn btn-secondary" href="<?= e(url('/articulos/exportar')) ?>">
            <?= icon('file') ?><span>Exportar actuales</span>
        </a>
    </div>
</div>

<?= View::partial('partials.form-errors', ['errors' => $errors ?? []]) ?>

<?php if ($resultado !== null): ?>
    <?php $errores = $resultado['errores']; ?>
    <section class="card import-result <?= $errores === [] ? 'import-result-success' : 'import-result-warning' ?>">
        <header class="card-header">
            <div>
                <h2><?= $errores === [] ? 'Importación completada' : 'Importación completada con observaciones' ?></h2>
                <p>
                    <?= e((string) $resultado['total']) ?> fila<?= (int) $resultado['total'] === 1 ? '' : 's' ?> procesada<?= (int) $resultado['total'] === 1 ? '' : 's' ?>.
                </p>
            </div>
            <span class="badge badge-<?= $errores === [] ? 'success' : 'warning' ?>">
                <?= $errores === [] ? icon('check') : icon('alert') ?>
                <?= $errores === [] ? 'Sin errores' : count($errores) . ' observación' . (count($errores) === 1 ? '' : 'es') ?>
            </span>
        </header>

        <div class="import-counts">
            <div class="import-count">
                <span class="import-count-value text-success"><?= e((string) $resultado['creados']) ?></span>
                <span class="import-count-label">Creados</span>
            </div>
            <div class="import-count">
                <span class="import-count-value"><?= e((string) $resultado['actualizados']) ?></span>
                <span class="import-count-label">Actualizados</span>
            </div>
            <div class="import-count">
                <span class="import-count-value"><?= e((string) $resultado['omitidos']) ?></span>
                <span class="import-count-label">Omitidos</span>
            </div>
            <div class="import-count">
                <span class="import-count-value <?= $errores === [] ? '' : 'text-danger' ?>"><?= e((string) count($errores)) ?></span>
                <span class="import-count-label">Con errores</span>
            </div>
        </div>

        <?php if ($errores !== []): ?>
            <div class="import-errors">
                <h3>Filas que no se importaron</h3>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="help">Corregí esas filas y volvé a subir el archivo. Las filas válidas ya se guardaron.</p>
            </div>
        <?php endif; ?>

        <div class="import-result-actions">
            <a class="btn btn-primary" href="<?= e(url('/articulos')) ?>"><?= icon('box') ?><span>Ver artículos</span></a>
            <a class="btn btn-secondary" href="<?= e(url('/articulos/importar')) ?>"><?= icon('upload') ?><span>Importar otro archivo</span></a>
        </div>
    </section>
<?php endif; ?>

<form method="post" action="<?= e(url('/articulos/importar')) ?>" enctype="multipart/form-data" class="form-layout">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Archivo CSV</h2>
                    <p>Máximo 2 MB y 2.000 filas por importación. Se aceptan separadores «;» y «,».</p>
                </div>
            </header>

            <div class="form-grid">
                <div class="field field-span-2<?= field_class($errors, 'archivo') ?>">
                    <label class="label" for="archivo">Archivo</label>
                    <input class="input input-file" id="archivo" type="file" name="archivo" accept=".csv,.txt,text/csv,text/plain" required>
                    <?php if ($error = field_error($errors, 'archivo')): ?><span class="error-text"><?= e($error) ?></span><?php endif; ?>
                    <span class="help">Codificación UTF-8 o Windows-1252. La primera fila debe contener los encabezados.</span>
                </div>

                <div class="field field-span-2">
                    <span class="label">¿Qué hacer con los códigos existentes?</span>
                    <div class="import-modes">
                        <label class="import-mode">
                            <input type="radio" name="modo" value="actualizar"<?= checked($modo === 'actualizar') ?>>
                            <span>
                                <strong>Crear y actualizar</strong>
                                <small>Actualiza nombre, tipo, proveedor, precios, mínimo, unidad y estado. No modifica el stock de artículos existentes.</small>
                            </span>
                        </label>
                        <label class="import-mode">
                            <input type="radio" name="modo" value="solo_crear"<?= checked($modo === 'solo_crear') ?>>
                            <span>
                                <strong>Solo crear nuevos</strong>
                                <small>Omite las filas cuyo código ya existe. Útil para no pisar datos cargados.</small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <section class="card form-card">
            <header class="card-header">
                <div>
                    <h2>Formato esperado</h2>
                    <p>Descargá la plantilla para empezar con los encabezados correctos.</p>
                </div>
            </header>

            <div class="import-help">
                <table class="table table-compact">
                    <thead>
                        <tr><th>Columna</th><th>Obligatoria</th><th>Descripción</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>Codigo</code></td><td>Sí</td><td>SKU o código de barras único, hasta 64 caracteres.</td></tr>
                        <tr><td><code>Nombre</code></td><td>Sí</td><td>Nombre del artículo, hasta 200 caracteres.</td></tr>
                        <tr><td><code>Tipo</code></td><td>Sí</td><td>Nombre exacto de un tipo existente, por ejemplo «Cuadernos».</td></tr>
                        <tr><td><code>Proveedor</code></td><td>No</td><td>Nombre exacto de un proveedor existente. Vacío = sin proveedor.</td></tr>
                        <tr><td><code>Precio costo</code></td><td>No</td><td>Número no negativo. Vacío = 0.</td></tr>
                        <tr><td><code>Precio venta</code></td><td>No</td><td>Número no negativo. Vacío = 0.</td></tr>
                        <tr><td><code>Stock actual</code></td><td>No</td><td>Solo se usa al crear un artículo nuevo: genera un movimiento de entrada inicial.</td></tr>
                        <tr><td><code>Stock minimo</code></td><td>No</td><td>Umbral de alerta de reposición.</td></tr>
                        <tr><td><code>Unidad</code></td><td>No</td><td>unidad, paquete, caja, resma, metro, kg o litro. Vacío = unidad.</td></tr>
                        <tr><td><code>Activo</code></td><td>No</td><td>1/0, sí/no. Vacío = 1.</td></tr>
                    </tbody>
                </table>

                <div class="field-note">
                    <?= icon('info') ?>
                    <span>
                        Los tipos y proveedores deben existir antes de importar. El stock de artículos existentes
                        <strong>no se modifica</strong>: usá Movimientos para entradas, salidas o ajustes auditados.
                    </span>
                </div>
            </div>
        </section>
    </div>

    <aside class="form-aside">
        <div class="card form-summary">
            <h2>Importar</h2>
            <p>Se procesan las filas en orden. Las filas con errores se informan y no se guardan.</p>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <?= icon('upload') ?><span>Importar CSV</span>
                </button>
                <a class="btn btn-secondary btn-block" href="<?= e(url('/articulos')) ?>">Cancelar</a>
            </div>
        </div>

        <div class="card tip-card">
            <span class="tip-icon"><?= icon('scan') ?></span>
            <div>
                <strong>Códigos de barras</strong>
                <p>Podés usar el código EAN/UPC como <code>Codigo</code> y luego escanearlo desde Artículos o Movimientos.</p>
            </div>
        </div>
    </aside>
</form>
