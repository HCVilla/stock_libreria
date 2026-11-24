<?php

declare(strict_types=1);

namespace App\Application\Usuarios;

use App\Core\Database;
use App\Infrastructure\Persistence\UsuarioRepository;
use DomainException;

final class UsuarioService
{
    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    /**
     * @param array{nombre: string, usuario: string, rol: string, activo: int, password: string} $data
     */
    public function crear(array $data): int
    {
        return Database::transaction(function () use ($data): int {
            return $this->usuarios->create([
                'nombre' => $data['nombre'],
                'usuario' => $data['usuario'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'rol' => $data['rol'],
                'activo' => $data['activo'],
            ]);
        });
    }

    /**
     * @param array{nombre: string, usuario: string, rol: string, activo: int, password?: string} $data
     */
    public function actualizar(int $id, array $data, int $actorId): void
    {
        $actual = $this->usuarios->findPublic($id);

        if ($actual === null) {
            throw new DomainException('El usuario ya no existe.');
        }

        $this->protegerAdministrador($actual, $data['rol'], $data['activo'], $actorId);

        Database::transaction(function () use ($id, $data): void {
            $this->usuarios->update($id, [
                'nombre' => $data['nombre'],
                'usuario' => $data['usuario'],
                'rol' => $data['rol'],
                'activo' => $data['activo'],
            ]);

            if (($data['password'] ?? '') !== '') {
                $this->usuarios->updatePassword($id, password_hash((string) $data['password'], PASSWORD_DEFAULT));
            }
        });
    }

    public function setActivo(int $id, bool $activo, int $actorId): void
    {
        if ($activo) {
            $this->usuarios->setActivo($id, true);

            return;
        }

        $actual = $this->usuarios->findPublic($id);

        if ($actual === null) {
            throw new DomainException('El usuario ya no existe.');
        }

        $this->protegerAdministrador($actual, (string) $actual['rol'], 0, $actorId);
        $this->usuarios->setActivo($id, false);
    }

    public function cambiarPassword(int $id, string $password): void
    {
        $this->usuarios->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
    }

    public function cambiarPasswordPropia(int $id, string $actual, string $nueva): void
    {
        $usuario = $this->usuarios->findById($id);

        if ($usuario === null) {
            throw new DomainException('Tu usuario ya no existe. Volvé a iniciar sesión.');
        }

        if (!password_verify($actual, (string) $usuario['password_hash'])) {
            throw new DomainException('La contraseña actual no es correcta.');
        }

        if (password_verify($nueva, (string) $usuario['password_hash'])) {
            throw new DomainException('La nueva contraseña debe ser distinta de la actual.');
        }

        $this->cambiarPassword($id, $nueva);
    }

    /**
     * Evita que la aplicación quede sin ningún administrador activo y que un
     * administrador se bloquee a sí mismo.
     */
    private function protegerAdministrador(array $actual, string $nuevoRol, int $nuevoActivo, int $actorId): void
    {
        $id = (int) $actual['id'];

        if ($id === $actorId && ($nuevoRol !== 'admin' || $nuevoActivo !== 1)) {
            throw new DomainException('No podés cambiarte el rol ni desactivar tu propia cuenta.');
        }

        $eraAdminActivo = (string) $actual['rol'] === 'admin' && (int) $actual['activo'] === 1;
        $seguiraAdminActivo = $nuevoRol === 'admin' && $nuevoActivo === 1;

        if ($eraAdminActivo && !$seguiraAdminActivo && $this->usuarios->countActiveAdmins($id) === 0) {
            throw new DomainException('Debe quedar al menos un administrador activo en el sistema.');
        }
    }
}
