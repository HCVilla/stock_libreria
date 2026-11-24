<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Core\Database;

final class UsuarioRepository
{
    private const SELECT_PUBLIC = 'SELECT id, nombre, usuario, rol, activo, ultimo_acceso, creado_en, actualizado_en
        FROM usuarios';

    public function findByUsuario(string $usuario): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findPublic(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(self::SELECT_PUBLIC . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query = '', string $rol = '', int $limit = 500): array
    {
        $limit = max(1, min($limit, 1000));
        $sql = self::SELECT_PUBLIC;
        $conditions = [];
        $params = [];

        if ($query !== '') {
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $conditions[] = '(nombre LIKE ? OR usuario LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        if (in_array($rol, ['admin', 'encargado', 'vendedor', 'consulta'], true)) {
            $conditions[] = 'rol = ?';
            $params[] = $rol;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY activo DESC, FIELD(rol, 'admin', 'encargado', 'vendedor', 'consulta'), nombre ASC
                  LIMIT " . $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return Database::pdo()
            ->query(self::SELECT_PUBLIC . " ORDER BY activo DESC, FIELD(rol, 'admin', 'encargado', 'vendedor', 'consulta'), nombre ASC")
            ->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['usuario'],
            $data['password_hash'],
            $data['rol'],
            $data['activo'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, activo = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['nombre'],
            $data['usuario'],
            $data['rol'],
            $data['activo'],
            $id,
        ]);
    }

    public function setActivo(int $id, bool $activo): void
    {
        $stmt = Database::pdo()->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
        $stmt->execute([$activo ? 1 : 0, $id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public function touchUltimoAcceso(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function existsUsuario(string $usuario, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE usuario = ?';
        $params = [$usuario];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countActiveAdmins(?int $exceptId = null): int
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1";
        $params = [];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(activo = 1), 0) AS activos,
                       COALESCE(SUM(activo = 0), 0) AS inactivos,
                       COALESCE(SUM(rol = 'admin' AND activo = 1), 0) AS admins
                FROM usuarios";

        $row = Database::pdo()->query($sql)->fetch();

        return $row === false ? [] : $row;
    }

    public function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }
}
