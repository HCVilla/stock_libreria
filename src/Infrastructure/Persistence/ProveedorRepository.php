<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Core\Database;

final class ProveedorRepository
{
    public function allWithCounts(): array
    {
        $sql = 'SELECT p.*,
                       (SELECT COUNT(*) FROM articulos a WHERE a.proveedor_id = p.id) AS total_articulos
                FROM proveedores p
                ORDER BY p.activo DESC, p.nombre ASC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function all(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM proveedores ORDER BY activo DESC, nombre ASC')
            ->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM proveedores WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO proveedores (nombre, telefono, contacto, notas, activo)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['telefono'] !== '' ? $data['telefono'] : null,
            $data['contacto'] !== '' ? $data['contacto'] : null,
            $data['notas'] !== '' ? $data['notas'] : null,
            $data['activo'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE proveedores
             SET nombre = ?, telefono = ?, contacto = ?, notas = ?, activo = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['nombre'],
            $data['telefono'] !== '' ? $data['telefono'] : null,
            $data['contacto'] !== '' ? $data['contacto'] : null,
            $data['notas'] !== '' ? $data['notas'] : null,
            $data['activo'],
            $id,
        ]);
    }

    public function setActivo(int $id, bool $activo): void
    {
        $stmt = Database::pdo()->prepare('UPDATE proveedores SET activo = ? WHERE id = ?');
        $stmt->execute([$activo ? 1 : 0, $id]);
    }

    public function existsNombre(string $nombre, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM proveedores WHERE nombre = ?';
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
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM proveedores WHERE id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
