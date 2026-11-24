<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;

$user = Auth::user();
$current = Request::path();
$appName = (string) App\Core\Config::get('app.name', 'Librería Stock');
$cssVersion = @filemtime(BASE_PATH . '/public/assets/css/app.css') ?: 1;
$jsVersion = @filemtime(BASE_PATH . '/public/assets/js/app.js') ?: 1;

$navItems = [
    ['path' => '/', 'label' => 'Panel', 'icon' => 'dashboard'],
    ['path' => '/articulos', 'label' => 'Artículos', 'icon' => 'box'],
    ['path' => '/movimientos', 'label' => 'Movimientos', 'icon' => 'swap'],
    ['path' => '/reposicion', 'label' => 'Reposición', 'icon' => 'target'],
    ['path' => '/reportes', 'label' => 'Reportes', 'icon' => 'report'],
    ['path' => '/tipos', 'label' => 'Tipos', 'icon' => 'tag'],
    ['path' => '/proveedores', 'label' => 'Proveedores', 'icon' => 'truck'],
];

if ($user !== null && (string) $user['rol'] === 'admin') {
    $navItems[] = ['path' => '/usuarios', 'label' => 'Usuarios', 'icon' => 'users'];
}

$isActive = static function (string $path) use ($current): bool {
    if ($path === '/') {
        return $current === '/';
    }

    return str_starts_with($current, $path);
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title ?? $appName) ?> · <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) $cssVersion) ?>">
</head>
<body class="app-body" data-login-url="<?= e(url('/login')) ?>">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark"><?= icon('book') ?></span>
            <span class="brand-copy">
                <strong>Librería</strong>
                <small>Control de stock</small>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="Navegación principal">
            <span class="sidebar-label">Gestión</span>
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link<?= $isActive($item['path']) ? ' is-active' : '' ?>" href="<?= e(url($item['path'])) ?>">
                    <?= icon($item['icon']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-foot">
            <?php if ($user !== null): ?>
                <div class="user-card">
                    <span class="avatar"><?= e(initials((string) $user['nombre'])) ?></span>
                    <span class="user-copy">
                        <strong><?= e($user['nombre']) ?></strong>
                        <small><?= e(role_label((string) $user['rol'])) ?></small>
                    </span>
                    <a class="icon-btn icon-btn-dark" href="<?= e(url('/cuenta/password')) ?>" title="Cambiar contraseña" aria-label="Cambiar contraseña">
                        <?= icon('key') ?>
                    </a>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="icon-btn icon-btn-dark" title="Cerrar sesión" aria-label="Cerrar sesión">
                            <?= icon('logout') ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <div class="sidebar-overlay" data-sidebar-close></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="icon-btn menu-toggle" data-sidebar-toggle aria-label="Abrir menú">
                <?= icon('menu') ?>
            </button>

            <div class="topbar-title">
                <span class="eyebrow"><?= e($subtitle ?? 'Inventario') ?></span>
                <h1><?= e($title ?? 'Panel') ?></h1>
            </div>

            <div class="topbar-meta">
                <span class="date-chip"><?= icon('clock') ?><span><?= e(date('d/m/Y')) ?></span></span>
                <?php if ($user !== null): ?>
                    <span class="topbar-user" title="<?= e($user['nombre']) ?>">
                        <span class="avatar avatar-sm"><?= e(initials((string) $user['nombre'])) ?></span>
                        <span class="topbar-user-copy">
                            <strong><?= e($user['nombre']) ?></strong>
                            <small><?= e(role_label((string) $user['rol'])) ?></small>
                        </span>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <main class="content">
            <?= View::partial('partials.flash') ?>
            <?= $content ?>
        </main>
    </div>
</div>

<?= View::partial('partials.barcode-modal') ?>

<script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) $jsVersion) ?>"></script>
</body>
</html>
