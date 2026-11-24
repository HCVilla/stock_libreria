<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\View;

$cssVersion = @filemtime(BASE_PATH . '/public/assets/css/app.css') ?: 1;
$jsVersion = @filemtime(BASE_PATH . '/public/assets/js/app.js') ?: 1;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title ?? 'Acceso') ?> · Librería Stock</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) $cssVersion) ?>">
</head>
<body class="auth-body">
    <div class="auth-glow auth-glow-a"></div>
    <div class="auth-glow auth-glow-b"></div>

    <main class="auth-shell">
        <a class="auth-brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark brand-mark-lg"><?= icon('book') ?></span>
            <span>
                <strong>Librería</strong>
                <small>Control de stock</small>
            </span>
        </a>

        <?= View::partial('partials.flash') ?>
        <?= $content ?>

        <p class="auth-foot">
            <?php if (Auth::check()): ?>
                <a href="<?= e(url('/')) ?>">Volver al panel</a>
            <?php else: ?>
                Sistema interno · Acceso restringido
            <?php endif; ?>
        </p>
    </main>

    <script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) $jsVersion) ?>"></script>
</body>
</html>
