<?php

declare(strict_types=1);

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\ArticulosController;
use App\Http\Controllers\ArticulosCsvController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReposicionController;
use App\Http\Controllers\TiposController;
use App\Http\Controllers\UsuariosController;

return static function (App\Core\Router $router): void {
    $auth = static fn (): array => [AuthMiddleware::class];
    $manager = static fn (): array => [
        AuthMiddleware::class,
        new RoleMiddleware(['admin', 'encargado']),
    ];
    $managerPost = static fn (): array => [
        AuthMiddleware::class,
        new RoleMiddleware(['admin', 'encargado']),
        CsrfMiddleware::class,
    ];
    $admin = static fn (): array => [
        AuthMiddleware::class,
        new RoleMiddleware(['admin']),
    ];
    $adminPost = static fn (): array => [
        AuthMiddleware::class,
        new RoleMiddleware(['admin']),
        CsrfMiddleware::class,
    ];
    $authPost = static fn (): array => [
        AuthMiddleware::class,
        CsrfMiddleware::class,
    ];

    // Autenticación
    $router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
    $router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, CsrfMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Panel
    $router->get('/', [DashboardController::class, 'index'], $auth());

    // Artículos
    $router->get('/articulos', [ArticulosController::class, 'index'], $auth());
    $router->get('/api/articulos', [ArticulosController::class, 'search'], $auth());
    $router->get('/articulos/exportar', [ArticulosCsvController::class, 'export'], $auth());
    $router->get('/articulos/plantilla', [ArticulosCsvController::class, 'plantilla'], $manager());
    $router->get('/articulos/importar', [ArticulosCsvController::class, 'importForm'], $manager());
    $router->post('/articulos/importar', [ArticulosCsvController::class, 'import'], $managerPost());
    $router->get('/articulos/nuevo', [ArticulosController::class, 'create'], $manager());
    $router->post('/articulos', [ArticulosController::class, 'store'], $managerPost());
    $router->get('/articulos/{id}/editar', [ArticulosController::class, 'edit'], $manager());
    $router->post('/articulos/{id}', [ArticulosController::class, 'update'], $managerPost());
    $router->post('/articulos/{id}/desactivar', [ArticulosController::class, 'destroy'], $managerPost());
    $router->post('/articulos/{id}/activar', [ArticulosController::class, 'activate'], $managerPost());

    // Movimientos de stock
    $router->get('/movimientos', [MovimientosController::class, 'index'], $auth());
    $router->get('/movimientos/nuevo', [MovimientosController::class, 'create'], $manager());
    $router->post('/movimientos', [MovimientosController::class, 'store'], $managerPost());

    // Reposición pendiente
    $router->get('/reposicion', [ReposicionController::class, 'index'], $auth());

    // Reportes
    $router->get('/reportes', [ReportesController::class, 'index'], $auth());
    $router->get('/reportes/exportar/{tipo}', [ReportesController::class, 'export'], $auth());

    // Cuenta propia
    $router->get('/cuenta/password', [CuentaController::class, 'password'], $auth());
    $router->post('/cuenta/password', [CuentaController::class, 'updatePassword'], $authPost());

    // Usuarios (solo administradores)
    $router->get('/usuarios', [UsuariosController::class, 'index'], $admin());
    $router->get('/usuarios/nuevo', [UsuariosController::class, 'create'], $admin());
    $router->post('/usuarios', [UsuariosController::class, 'store'], $adminPost());
    $router->get('/usuarios/{id}/editar', [UsuariosController::class, 'edit'], $admin());
    $router->post('/usuarios/{id}', [UsuariosController::class, 'update'], $adminPost());
    $router->post('/usuarios/{id}/desactivar', [UsuariosController::class, 'destroy'], $adminPost());
    $router->post('/usuarios/{id}/activar', [UsuariosController::class, 'activate'], $adminPost());

    // Tipos de artículo
    $router->get('/tipos', [TiposController::class, 'index'], $auth());
    $router->get('/tipos/nuevo', [TiposController::class, 'create'], $manager());
    $router->post('/tipos', [TiposController::class, 'store'], $managerPost());
    $router->get('/tipos/{id}/editar', [TiposController::class, 'edit'], $manager());
    $router->post('/tipos/{id}', [TiposController::class, 'update'], $managerPost());
    $router->post('/tipos/{id}/desactivar', [TiposController::class, 'destroy'], $managerPost());
    $router->post('/tipos/{id}/activar', [TiposController::class, 'activate'], $managerPost());

    // Proveedores
    $router->get('/proveedores', [ProveedoresController::class, 'index'], $auth());
    $router->get('/proveedores/nuevo', [ProveedoresController::class, 'create'], $manager());
    $router->post('/proveedores', [ProveedoresController::class, 'store'], $managerPost());
    $router->get('/proveedores/{id}/editar', [ProveedoresController::class, 'edit'], $manager());
    $router->post('/proveedores/{id}', [ProveedoresController::class, 'update'], $managerPost());
    $router->post('/proveedores/{id}/desactivar', [ProveedoresController::class, 'destroy'], $managerPost());
    $router->post('/proveedores/{id}/activar', [ProveedoresController::class, 'activate'], $managerPost());
};
