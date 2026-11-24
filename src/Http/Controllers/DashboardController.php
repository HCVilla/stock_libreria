<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\View;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\MovimientoRepository;
use App\Infrastructure\Persistence\ProveedorRepository;
use App\Infrastructure\Persistence\TipoArticuloRepository;

final class DashboardController
{
    public function index(): void
    {
        $articulos = new ArticuloRepository();
        $movimientos = new MovimientoRepository();

        View::render('dashboard.index', [
            'title' => 'Panel',
            'stats' => $articulos->stats(),
            'lowStock' => $articulos->lowStock(8),
            'recent' => $movimientos->recent(6),
            'tiposCount' => count((new TipoArticuloRepository())->all()),
            'proveedoresCount' => count((new ProveedorRepository())->all()),
        ]);
    }
}
