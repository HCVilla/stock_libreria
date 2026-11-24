<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\View;

$cssVersion = @filemtime(BASE_PATH . '/public/assets/css/app.css') ?: 1;
$jsVersion = @filemtime(BASE_PATH . '/public/assets/js/app.js') ?: 1;
$appVersion = (string) Config::get('app.version', '1.0.0');
$appAuthor = (string) Config::get('app.author', 'Ingeniero en Sistemas: Villanueva Cesar');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title ?? 'Acceso') ?> · <?= e((string) Config::get('app.name', 'Librería Stock')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) $cssVersion) ?>">
</head>
<body class="auth-body">
    <div class="auth-glow auth-glow-a"></div>
    <div class="auth-glow auth-glow-b"></div>

    <main class="auth-shell">
        <section class="auth-showcase">
            <div class="auth-showcase-brand">
                <span class="brand-mark brand-mark-lg"><?= icon('book') ?></span>
                <span class="auth-showcase-brand-text">
                    <strong>Stock Librería</strong>
                    <small>Control de stock e inventario</small>
                </span>
            </div>

            <div class="auth-showcase-body">
                <span class="auth-showcase-kicker">Sistema interno</span>
                <h1 class="auth-showcase-title">Stock<br>Librería</h1>
                <p class="auth-showcase-lead">
                    Administrá artículos, tipos, proveedores, precios y movimientos de stock
                    desde un único lugar, con reportes y alertas de reposición.
                </p>

                <ul class="auth-features">
                    <li><?= icon('check') ?><span>Catálogo con búsqueda y códigos de barras</span></li>
                    <li><?= icon('check') ?><span>Movimientos auditados y control de stock</span></li>
                    <li><?= icon('check') ?><span>Reportes y exportación a CSV</span></li>
                </ul>
            </div>

            <footer class="auth-showcase-foot">
                <div class="auth-credit">
                    <span class="auth-credit-label">Desarrollado por</span>
                    <strong><?= e($appAuthor) ?></strong>
                </div>
                <span class="auth-version">Versión <?= e($appVersion) ?></span>
            </footer>
        </section>

        <section class="auth-panel">
            <?= View::partial('partials.flash') ?>
            <?= $content ?>

            <p class="auth-foot">
                <?php if (Auth::check()): ?>
                    <a href="<?= e(url('/')) ?>">Volver al panel</a>
                <?php else: ?>
                    Sistema interno · Acceso restringido
                <?php endif; ?>
            </p>
        </section>
    </main>

    <script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) $jsVersion) ?>"></script>
</body>
</html>
