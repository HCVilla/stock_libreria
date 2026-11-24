<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Core\Database;
use PDO;

final class ArticuloRepository
{
    private const SELECT_BASE = 'SELECT a.*, t.nombre AS tipo_nombre, p.nombre AS proveedor_nombre
        FROM articulos a
        INNER JOIN tipos_articulo t ON t.id = a.tipo_id
        LEFT JOIN proveedores p ON p.id = a.proveedor_id';

    public function search(string $query = '', int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $sql = self::SELECT_BASE;
        $params = [];

        if ($query !== '') {
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $sql .= ' WHERE a.nombre LIKE ? OR a.codigo LIKE ?';
            $params = [$like, $like];
        }

        $sql .= ' ORDER BY a.activo DESC, a.nombre ASC LIMIT ' . $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(self::SELECT_BASE . ' WHERE a.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Artículos activos para selects de movimientos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allActive(): array
    {
        $sql = self::SELECT_BASE . ' WHERE a.activo = 1 ORDER BY a.nombre ASC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function findByCodigo(string $codigo): ?array
    {
        $stmt = Database::pdo()->prepare(self::SELECT_BASE . ' WHERE a.codigo = ? LIMIT 1');
        $stmt->execute([$codigo]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Inventario para reportes y exportaciones (sin el límite de la búsqueda).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allForReport(bool $soloActivos = true, int $limit = 10000): array
    {
        $limit = max(1, min($limit, 100000));
        $sql = self::SELECT_BASE;

        if ($soloActivos) {
            $sql .= ' WHERE a.activo = 1';
        }

        $sql .= ' ORDER BY a.nombre ASC LIMIT ' . $limit;

        return Database::pdo()->query($sql)->fetchAll();
    }

    /**
     * Debe llamarse dentro de una transacción. Bloquea la fila para evitar
     * condiciones de carrera al calcular el nuevo stock.
     */
    public function findForUpdate(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articulos WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO articulos
                (codigo, nombre, tipo_id, proveedor_id, precio_costo, precio_venta,
                 stock_actual, stock_minimo, unidad_medida, activo)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
        );
        $stmt->execute([
            $data['codigo'],
            $data['nombre'],
            $data['tipo_id'],
            $data['proveedor_id'],
            $data['precio_costo'],
            $data['precio_venta'],
            $data['stock_minimo'],
            $data['unidad_medida'],
            $data['activo'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Actualiza los datos del artículo sin tocar stock_actual.
     * El stock solo cambia a través del libro de movimientos.
     */
    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE articulos
             SET codigo = ?, nombre = ?, tipo_id = ?, proveedor_id = ?,
                 precio_costo = ?, precio_venta = ?, stock_minimo = ?,
                 unidad_medida = ?, activo = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['codigo'],
            $data['nombre'],
            $data['tipo_id'],
            $data['proveedor_id'],
            $data['precio_costo'],
            $data['precio_venta'],
            $data['stock_minimo'],
            $data['unidad_medida'],
            $data['activo'],
            $id,
        ]);
    }

    public function setActivo(int $id, bool $activo): void
    {
        $stmt = Database::pdo()->prepare('UPDATE articulos SET activo = ? WHERE id = ?');
        $stmt->execute([$activo ? 1 : 0, $id]);
    }

    public function existsCodigo(string $codigo, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM articulos WHERE codigo = ?';
        $params = [$codigo];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function exists(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM articulos WHERE id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function tipoExists(int $tipoId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM tipos_articulo WHERE id = ?');
        $stmt->execute([$tipoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function proveedorExists(int $proveedorId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM proveedores WHERE id = ?');
        $stmt->execute([$proveedorId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function stats(): array
    {
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(activo = 1), 0) AS activos,
                       COALESCE(SUM(activo = 0), 0) AS inactivos,
                       COALESCE(SUM(activo = 1 AND stock_actual = 0), 0) AS sin_stock,
                       COALESCE(SUM(activo = 1 AND stock_actual <= stock_minimo), 0) AS bajo_stock,
                       COALESCE(SUM(CASE WHEN activo = 1 THEN stock_actual * precio_costo ELSE 0 END), 0) AS valor_costo,
                       COALESCE(SUM(CASE WHEN activo = 1 THEN stock_actual * precio_venta ELSE 0 END), 0) AS valor_venta
                FROM articulos';

        $row = Database::pdo()->query($sql)->fetch();

        return $row === false ? [] : $row;
    }

    public function lowStock(int $limit = 10): array
    {
        $limit = max(1, min($limit, 10000));

        $sql = self::SELECT_BASE . '
                WHERE a.activo = 1 AND a.stock_actual <= a.stock_minimo
                ORDER BY (a.stock_actual = 0) DESC,
                         (a.stock_minimo - a.stock_actual) DESC,
                         a.nombre ASC
                LIMIT ' . $limit;

        return Database::pdo()->query($sql)->fetchAll();
    }
}
