<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Infrastructure\Persistence\ArticuloRepository;

final class ReposicionController
{
    /** @var array<string, float> */
    private const FACTORES = [
        '1' => 1.0,
        '1.5' => 1.5,
        '2' => 2.0,
        '3' => 3.0,
    ];

    public function index(): void
    {
        $factorKey = trim((string) Request::query('factor', '2'));

        if (!array_key_exists($factorKey, self::FACTORES)) {
            $factorKey = '2';
        }

        $factor = self::FACTORES[$factorKey];
        $articulos = (new ArticuloRepository())->lowStock(500);

        $filas = [];
        $totalFaltanteMinimo = 0.0;
        $totalFaltanteObjetivo = 0.0;
        $sinStock = 0;

        foreach ($articulos as $articulo) {
            $stock = (float) $articulo['stock_actual'];
            $minimo = (float) $articulo['stock_minimo'];

            // Objetivo: mínimo × factor. Si el mínimo es 0, se sugiere al menos 1.
            $objetivo = $minimo > 0 ? round($minimo * $factor, 2) : 1.0;
            $objetivo = max($objetivo, $minimo);

            $faltanteMinimo = max($minimo - $stock, 0.0);
            $faltanteObjetivo = max($objetivo - $stock, 0.0);

            $totalFaltanteMinimo += $faltanteMinimo;
            $totalFaltanteObjetivo += $faltanteObjetivo;

            if ($stock <= 0) {
                $sinStock++;
            }

            $filas[] = $articulo + [
                'objetivo' => $objetivo,
                'faltante_minimo' => $faltanteMinimo,
                'faltante_objetivo' => $faltanteObjetivo,
            ];
        }

        View::render('reposicion.index', [
            'title' => 'Reposición pendiente',
            'filas' => $filas,
            'factor' => $factor,
            'factorKey' => $factorKey,
            'factores' => self::FACTORES,
            'totalFaltanteMinimo' => $totalFaltanteMinimo,
            'totalFaltanteObjetivo' => $totalFaltanteObjetivo,
            'sinStock' => $sinStock,
        ]);
    }
}
