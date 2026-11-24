<?php

declare(strict_types=1);

namespace App\Application\Inventario;

use App\Core\Database;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\MovimientoRepository;
use DomainException;
use PDOException;

final class MovimientoService
{
    private const MAX_INTENTOS = 3;

    private ArticuloRepository $articulos;
    private MovimientoRepository $movimientos;

    public function __construct()
    {
        $this->articulos = new ArticuloRepository();
        $this->movimientos = new MovimientoRepository();
    }

    /**
     * Registra una entrada, salida o ajuste de stock en una única transacción.
     *
     * @param array{
     *     articulo_id: int,
     *     tipo_movimiento: string,
     *     cantidad: float,
     *     stock_real: float,
     *     precio_unitario: float|null,
     *     motivo: string,
     *     referencia: string
     * } $data
     *
     * @return array{id: int, articulo: array<string, mixed>, tipo: string, cantidad: float, stock_resultante: float}
     */
    public function registrar(array $data, int $usuarioId): array
    {
        return $this->conReintentos(function () use ($data, $usuarioId): array {
            return Database::transaction(function () use ($data, $usuarioId): array {
                $articulo = $this->articulos->findForUpdate((int) $data['articulo_id']);

                if ($articulo === null) {
                    throw new DomainException('El artículo seleccionado ya no existe. Recargá la página e intentá nuevamente.');
                }

                if ((int) $articulo['activo'] !== 1) {
                    throw new DomainException('El artículo «' . $articulo['nombre'] . '» está inactivo. Reactivalo antes de mover stock.');
                }

                $tipo = (string) $data['tipo_movimiento'];
                $stockActual = round((float) $articulo['stock_actual'], 2);
                $motivo = trim((string) $data['motivo']);
                $referencia = trim((string) $data['referencia']);
                $unidad = (string) $articulo['unidad_medida'];

                if ($tipo === 'entrada') {
                    $cantidad = round((float) $data['cantidad'], 2);

                    if ($cantidad <= 0) {
                        throw new DomainException('La cantidad de la entrada debe ser mayor que cero.');
                    }

                    $delta = $cantidad;

                    if ($motivo === '') {
                        $motivo = 'Compra / reposición de proveedor';
                    }
                } elseif ($tipo === 'salida') {
                    $cantidad = round((float) $data['cantidad'], 2);

                    if ($cantidad <= 0) {
                        throw new DomainException('La cantidad de la salida debe ser mayor que cero.');
                    }

                    $delta = -$cantidad;

                    if ($stockActual + $delta < 0) {
                        throw new DomainException(sprintf(
                            'Stock insuficiente: hay %s %s disponibles y se intentaron retirar %s. El stock no puede quedar negativo.',
                            self::formatear($stockActual),
                            $unidad,
                            self::formatear($cantidad)
                        ));
                    }

                    if ($motivo === '') {
                        $motivo = 'Venta / entrega';
                    }
                } elseif ($tipo === 'ajuste') {
                    $stockReal = round((float) $data['stock_real'], 2);

                    if ($stockReal < 0) {
                        throw new DomainException('El stock real contado no puede ser negativo.');
                    }

                    if ($motivo === '') {
                        throw new DomainException('El motivo es obligatorio para registrar un ajuste de inventario.');
                    }

                    $delta = round($stockReal - $stockActual, 2);

                    if ($delta === 0.0) {
                        throw new DomainException(sprintf(
                            'El stock real informado (%s %s) coincide con el stock actual. No hay ningún ajuste para registrar.',
                            self::formatear($stockReal),
                            $unidad
                        ));
                    }
                } else {
                    throw new DomainException('El tipo de movimiento seleccionado no es válido.');
                }

                $precio = $data['precio_unitario'];

                if ($precio === null) {
                    $precio = $tipo === 'salida'
                        ? (float) $articulo['precio_venta']
                        : (float) $articulo['precio_costo'];
                }

                $precio = round((float) $precio, 2);

                if ($precio < 0) {
                    throw new DomainException('El precio unitario no puede ser negativo.');
                }

                $movimientoId = $this->movimientos->registrar(
                    (int) $articulo['id'],
                    $usuarioId,
                    $tipo,
                    $delta,
                    $precio,
                    $motivo,
                    $referencia !== '' ? $referencia : null
                );

                return [
                    'id' => $movimientoId,
                    'articulo' => $articulo,
                    'tipo' => $tipo,
                    'cantidad' => $delta,
                    'stock_resultante' => round($stockActual + $delta, 2),
                ];
            });
        });
    }

    /**
     * Reintenta la transacción ante deadlocks o timeouts de bloqueo, que son
     * propios de dos usuarios moviendo stock del mismo artículo a la vez.
     */
    private function conReintentos(callable $callback): mixed
    {
        for ($intento = 1; ; $intento++) {
            try {
                return $callback();
            } catch (PDOException $exception) {
                if (!$this->esConflictoDeBloqueo($exception)) {
                    throw $exception;
                }

                if ($intento >= self::MAX_INTENTOS) {
                    throw new ConcurrencyException(
                        'Otro usuario está moviendo stock de este artículo en este momento. ' .
                        'No se registró ningún cambio; esperá unos segundos e intentá de nuevo.'
                    );
                }

                usleep(75000 * $intento);
            }
        }
    }

    private function esConflictoDeBloqueo(PDOException $exception): bool
    {
        $codigoDriver = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($codigoDriver, [1205, 1213], true)
            || (string) $exception->getCode() === '40001';
    }

    private static function formatear(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    }
}
