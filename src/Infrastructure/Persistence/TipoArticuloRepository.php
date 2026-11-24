<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Core\Database;

final class TipoArticuloRepository
{
    public function allWithCounts(): array
    {
        $sql = 'SELECT t.*,
                       (SELECT COUNT(*) FROM articulos a WHERE a.tipo_id = t.id) AS total_articulos
                FROM tipos_articulo t
                ORDER BY t.activo DESC, t.nombre ASC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function all(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM tipos_articulo ORDER BY activo DESC, nombre ASC')
            ->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM tipos_articulo WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO tipos_articulo (nombre, descripcion, activo) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] !== '' ? $data['descripcion'] : null,
            $data['activo'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE tipos_articulo SET nombre = ?, descripcion = ?, activo = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] !== '' ? $data['descripcion'] : null,
            $data['activo'],
            $id,
        ]);
    }

    public function setActivo(int $id, bool $activo): void
    {
        $stmt = Database::pdo()->prepare('UPDATE tipos_articulo SET activo = ? WHERE id = ?');
        $stmt->execute([$activo ? 1 : 0, $id]);
    }

    public function existsNombre(string $nombre, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tipos_articulo WHERE nombre = ?';
        $params = [$nombre];

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
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM tipos_articulo WHERE id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countArticulos(int $id): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM articulos WHERE tipo_id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn();
    }
}
