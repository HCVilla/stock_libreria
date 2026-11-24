<?php

declare(strict_types=1);

namespace App\Application\Inventario;

use App\Core\Database;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\MovimientoRepository;
use DomainException;

final class ArticuloService
{
    private ArticuloRepository $articulos;
    private MovimientoRepository $movimientos;

    public function __construct()
    {
        $this->articulos = new ArticuloRepository();
        $this->movimientos = new MovimientoRepository();
    }

    public function crear(array $data, int $usuarioId): int
    {
        return Database::transaction(function () use ($data, $usuarioId): int {
            $stockInicial = (float) $data['stock_actual'];
            $payload = $data;
            unset($payload['stock_actual']);

            $id = $this->articulos->create($payload);

            if ($stockInicial > 0) {
                $this->movimientos->registrar(
                    $id,
                    $usuarioId,
                    'entrada',
                    $stockInicial,
                    (float) $data['precio_costo'],
                    'Stock inicial',
                    null
                );
            }

            return $id;
        });
    }

    public function actualizar(int $id, array $data, int $usuarioId): void
    {
        Database::transaction(function () use ($id, $data, $usuarioId): void {
            $actual = $this->articulos->findForUpdate($id);

            if ($actual === null) {
                throw new DomainException('El artículo no existe.');
            }

            $delta = round((float) $data['stock_actual'] - (float) $actual['stock_actual'], 2);

            $this->articulos->update($id, $data);

            if ($delta !== 0.0) {
                $this->movimientos->registrar(
                    $id,
                    $usuarioId,
                    'ajuste',
                    $delta,
                    (float) $data['precio_costo'],
                    'Ajuste manual desde edición de artículo',
                    null
                );
            }
        });
    }
}
