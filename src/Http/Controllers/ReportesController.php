<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\MovimientoRepository;
use App\Support\Csv;
use DateTimeImmutable;

final class ReportesController
{
    private const TIPOS = ['', 'entrada', 'salida', 'ajuste'];
    private const EXPORTABLES = ['stock-bajo', 'valorizacion', 'movimientos'];

    private ArticuloRepository $articulos;
    private MovimientoRepository $movimientos;

    public function __construct()
    {
        $this->articulos = new ArticuloRepository();
        $this->movimientos = new MovimientoRepository();
    }

    public function index(): void
    {
        [$desde, $hasta] = $this->periodo();
        $tipo = $this->tipo();

        $stockBajo = $this->stockBajo();
        $valorizacion = $this->valorizacion();
        $movimientos = $this->movimientos->report($desde, $hasta, $tipo, 500);
        $resumen = $this->movimientos->resumenPeriodo($desde, $hasta, $tipo);

        View::render('reportes.index', [
            'title' => 'Reportes',
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo' => $tipo,
            'stockBajo' => $stockBajo['filas'],
            'stockBajoTotales' => $stockBajo['totales'],
            'valorizacion' => $valorizacion['filas'],
            'valorizacionTotales' => $valorizacion['totales'],
            'movimientos' => $movimientos,
            'resumen' => $resumen,
        ]);
    }

    public function export(string $tipo): void
    {
        if (!in_array($tipo, self::EXPORTABLES, true)) {
            \App\Core\Response::abort(404, 'El reporte solicitado no existe.');
        }

        if ($tipo === 'stock-bajo') {
            $this->exportStockBajo();

            return;
        }

        if ($tipo === 'valorizacion') {
            $this->exportValorizacion();

            return;
        }

        $this->exportMovimientos();
    }

    private function exportStockBajo(): void
    {
        $data = $this->stockBajo(10000);

        Csv::download(
            'reporte-stock-bajo-' . date('Y-m-d') . '.csv',
            [
                'Codigo',
                'Articulo',
                'Tipo',
                'Proveedor',
                'Stock actual',
                'Stock minimo',
                'Faltante para minimo',
                'Unidad',
                'Costo unitario',
                'Valor faltante a costo',
            ],
            array_map(static fn (array $fila): array => [
                $fila['codigo'],
                $fila['nombre'],
                $fila['tipo_nombre'],
                $fila['proveedor_nombre'] ?? '',
                number_format((float) $fila['stock_actual'], 2, '.', ''),
                number_format((float) $fila['stock_minimo'], 2, '.', ''),
                number_format((float) $fila['faltante'], 2, '.', ''),
                $fila['unidad_medida'],
                number_format((float) $fila['precio_costo'], 2, '.', ''),
                number_format((float) $fila['valor_faltante'], 2, '.', ''),
            ], $data['filas'])
        );
    }

    private function exportValorizacion(): void
    {
        $data = $this->valorizacion(100000);

        Csv::download(
            'reporte-valorizacion-' . date('Y-m-d') . '.csv',
            [
                'Codigo',
                'Articulo',
                'Tipo',
                'Proveedor',
                'Stock actual',
                'Unidad',
                'Costo unitario',
                'Precio venta',
                'Valor a costo',
                'Valor a venta',
                'Margen potencial',
            ],
            array_map(static fn (array $fila): array => [
                $fila['codigo'],
                $fila['nombre'],
                $fila['tipo_nombre'],
                $fila['proveedor_nombre'] ?? '',
                number_format((float) $fila['stock_actual'], 2, '.', ''),
                $fila['unidad_medida'],
                number_format((float) $fila['precio_costo'], 2, '.', ''),
                number_format((float) $fila['precio_venta'], 2, '.', ''),
                number_format((float) $fila['valor_costo'], 2, '.', ''),
                number_format((float) $fila['valor_venta'], 2, '.', ''),
                number_format((float) $fila['margen'], 2, '.', ''),
            ], $data['filas'])
        );
    }

    private function exportMovimientos(): void
    {
        [$desde, $hasta] = $this->periodo();
        $tipo = $this->tipo();
        $movimientos = $this->movimientos->report($desde, $hasta, $tipo, 50000);

        Csv::download(
            'reporte-movimientos-' . $desde . '_' . $hasta . '.csv',
            [
                'Fecha',
                'Tipo',
                'Codigo',
                'Articulo',
                'Cantidad',
                'Unidad',
                'Precio unitario',
                'Importe',
                'Stock resultante',
                'Motivo',
                'Referencia',
                'Usuario',
            ],
            array_map(static function (array $movimiento): array {
                $meta = movement_meta((string) $movimiento['tipo_movimiento']);

                return [
                    date('Y-m-d H:i', strtotime((string) $movimiento['fecha_hora'])),
                    $meta['label'],
                    $movimiento['codigo'],
                    $movimiento['articulo_nombre'],
                    number_format((float) $movimiento['cantidad'], 2, '.', ''),
                    $movimiento['unidad_medida'],
                    number_format((float) $movimiento['precio_unitario_momento'], 2, '.', ''),
                    number_format((float) $movimiento['importe'], 2, '.', ''),
                    number_format((float) $movimiento['stock_resultante'], 2, '.', ''),
                    $movimiento['motivo'] ?? '',
                    $movimiento['referencia'] ?? '',
                    $movimiento['usuario_nombre'],
                ];
            }, $movimientos)
        );
    }

    /**
     * @return array{filas: array<int, array<string, mixed>>, totales: array<string, float>}
     */
    private function stockBajo(int $limit = 500): array
    {
        $filas = [];
        $totalFaltante = 0.0;
        $totalValor = 0.0;

        foreach ($this->articulos->lowStock($limit) as $articulo) {
            $faltante = max((float) $articulo['stock_minimo'] - (float) $articulo['stock_actual'], 0.0);
            $valorFaltante = round($faltante * (float) $articulo['precio_costo'], 2);

            $totalFaltante += $faltante;
            $totalValor += $valorFaltante;

            $filas[] = $articulo + [
                'faltante' => $faltante,
                'valor_faltante' => $valorFaltante,
            ];
        }

        return [
            'filas' => $filas,
            'totales' => [
                'articulos' => (float) count($filas),
                'faltante' => $totalFaltante,
                'valor' => $totalValor,
            ],
        ];
    }

    /**
     * @return array{filas: array<int, array<string, mixed>>, totales: array<string, float>}
     */
    private function valorizacion(int $limit = 10000): array
    {
        $filas = [];
        $totalCosto = 0.0;
        $totalVenta = 0.0;
        $totalUnidades = 0.0;

        foreach ($this->articulos->allForReport(true, $limit) as $articulo) {
            $stock = (float) $articulo['stock_actual'];
            $valorCosto = round($stock * (float) $articulo['precio_costo'], 2);
            $valorVenta = round($stock * (float) $articulo['precio_venta'], 2);

            $totalCosto += $valorCosto;
            $totalVenta += $valorVenta;
            $totalUnidades += $stock;

            $filas[] = $articulo + [
                'valor_costo' => $valorCosto,
                'valor_venta' => $valorVenta,
                'margen' => $valorVenta - $valorCosto,
            ];
        }

        return [
            'filas' => $filas,
            'totales' => [
                'articulos' => (float) count($filas),
                'unidades' => $totalUnidades,
                'costo' => $totalCosto,
                'venta' => $totalVenta,
                'margen' => $totalVenta - $totalCosto,
            ],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodo(): array
    {
        $desde = $this->validDate(Request::query('desde', ''), date('Y-m-01'));
        $hasta = $this->validDate(Request::query('hasta', ''), date('Y-m-d'));

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    private function tipo(): string
    {
        $tipo = trim((string) Request::query('tipo', ''));

        return in_array($tipo, self::TIPOS, true) ? $tipo : '';
    }

    private function validDate(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : $fallback;
    }
}
