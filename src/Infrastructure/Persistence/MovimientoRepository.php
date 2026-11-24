<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Core\Database;
use DomainException;
use InvalidArgumentException;

final class MovimientoRepository
{
    /**
     * Registra un movimiento y actualiza stock_actual dentro de la transacción
     * abierta por quien invoca. La cantidad se guarda con signo:
     * entrada > 0, salida < 0, ajuste <> 0.
     */
    public function registrar(
        int $articuloId,
        int $usuarioId,
        string $tipo,
        float $cantidad,
        float $precioUnitario,
        ?string $motivo = null,
        ?string $referencia = null
    ): int {
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            throw new InvalidArgumentException('Tipo de movimiento inválido.');
        }

        $cantidad = round($cantidad, 2);
        $precioUnitario = round($precioUnitario, 2);

        if ($tipo === 'entrada' && $cantidad <= 0) {
            throw new InvalidArgumentException('La entrada debe tener cantidad positiva.');
        }

        if ($tipo === 'salida' && $cantidad >= 0) {
            throw new InvalidArgumentException('La salida debe tener cantidad negativa.');
        }

        if ($tipo === 'ajuste' && $cantidad === 0.0) {
            throw new InvalidArgumentException('El ajuste no puede ser cero.');
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT stock_actual FROM articulos WHERE id = ? FOR UPDATE');
        $stmt->execute([$articuloId]);
        $stockActual = $stmt->fetchColumn();

        if ($stockActual === false) {
            throw new DomainException('El artículo no existe.');
        }

        $nuevoStock = round((float) $stockActual + $cantidad, 2);

        if ($nuevoStock < 0) {
            throw new DomainException('Stock insuficiente: el movimiento dejaría el stock en negativo.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO movimientos_stock
                (articulo_id, usuario_id, tipo_movimiento, cantidad,
                 precio_unitario_momento, motivo, referencia, stock_resultante)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $articuloId,
            $usuarioId,
            $tipo,
            $cantidad,
            $precioUnitario,
            $motivo !== '' ? $motivo : null,
            $referencia !== '' ? $referencia : null,
            $nuevoStock,
        ]);

        $update = $pdo->prepare('UPDATE articulos SET stock_actual = ? WHERE id = ?');
        $update->execute([$nuevoStock, $articuloId]);

        return (int) $pdo->lastInsertId();
    }

    public function recent(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));

        $sql = 'SELECT m.*, a.nombre AS articulo_nombre, a.codigo, u.nombre AS usuario_nombre
                FROM movimientos_stock m
                INNER JOIN articulos a ON a.id = m.articulo_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                ORDER BY m.fecha_hora DESC, m.id DESC
                LIMIT ' . $limit;

        return Database::pdo()->query($sql)->fetchAll();
    }

    /**
     * Historial con filtros por texto (artículo, código, motivo, referencia)
     * y por tipo de movimiento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query = '', string $tipo = '', int $limit = 300): array
    {
        $limit = max(1, min($limit, 500));

        $sql = 'SELECT m.*,
                       a.codigo,
                       a.nombre AS articulo_nombre,
                       a.unidad_medida,
                       t.nombre AS tipo_nombre,
                       u.nombre AS usuario_nombre
                FROM movimientos_stock m
                INNER JOIN articulos a ON a.id = m.articulo_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN tipos_articulo t ON t.id = a.tipo_id';
        $conditions = [];
        $params = [];

        if ($query !== '') {
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $conditions[] = '(a.nombre LIKE ? OR a.codigo LIKE ? OR m.motivo LIKE ? OR m.referencia LIKE ?)';
            $params = [$like, $like, $like, $like];
        }

        if (in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            $conditions[] = 'm.tipo_movimiento = ?';
            $params[] = $tipo;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY m.fecha_hora DESC, m.id DESC LIMIT ' . $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Totales históricos por tipo de movimiento.
     *
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(tipo_movimiento = 'entrada'), 0) AS entradas,
                       COALESCE(SUM(tipo_movimiento = 'salida'), 0) AS salidas,
                       COALESCE(SUM(tipo_movimiento = 'ajuste'), 0) AS ajustes,
                       COALESCE(SUM(CASE WHEN tipo_movimiento = 'entrada' THEN cantidad ELSE 0 END), 0) AS unidades_entrada,
                       COALESCE(SUM(CASE WHEN tipo_movimiento = 'salida' THEN ABS(cantidad) ELSE 0 END), 0) AS unidades_salida
                FROM movimientos_stock";

        $row = Database::pdo()->query($sql)->fetch();

        return $row === false ? [] : $row;
    }

    /**
     * Movimientos dentro de un período, con filtro opcional por tipo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function report(string $desde, string $hasta, string $tipo = '', int $limit = 1000): array
    {
        $limit = max(1, min($limit, 50000));

        $sql = 'SELECT m.*,
                       a.codigo,
                       a.nombre AS articulo_nombre,
                       a.unidad_medida,
                       t.nombre AS tipo_nombre,
                       u.nombre AS usuario_nombre,
                       (m.cantidad * m.precio_unitario_momento) AS importe
                FROM movimientos_stock m
                INNER JOIN articulos a ON a.id = m.articulo_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN tipos_articulo t ON t.id = a.tipo_id
                WHERE DATE(m.fecha_hora) BETWEEN ? AND ?';
        $params = [$desde, $hasta];

        if (in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            $sql .= ' AND m.tipo_movimiento = ?';
            $params[] = $tipo;
        }

        $sql .= ' ORDER BY m.fecha_hora ASC, m.id ASC LIMIT ' . $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>
     */
    public function resumenPeriodo(string $desde, string $hasta, string $tipo = ''): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(tipo_movimiento = 'entrada'), 0) AS entradas,
                       COALESCE(SUM(tipo_movimiento = 'salida'), 0) AS salidas,
                       COALESCE(SUM(tipo_movimiento = 'ajuste'), 0) AS ajustes,
                       COALESCE(SUM(CASE WHEN tipo_movimiento = 'entrada' THEN cantidad ELSE 0 END), 0) AS unidades_entrada,
                       COALESCE(SUM(CASE WHEN tipo_movimiento = 'salida' THEN ABS(cantidad) ELSE 0 END), 0) AS unidades_salida,
                       COALESCE(SUM(cantidad * precio_unitario_momento), 0) AS importe_neto
                FROM movimientos_stock
                WHERE DATE(fecha_hora) BETWEEN ? AND ?";
        $params = [$desde, $hasta];

        if (in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            $sql .= ' AND tipo_movimiento = ?';
            $params[] = $tipo;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? [] : $row;
    }
}
