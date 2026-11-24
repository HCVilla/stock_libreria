<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Usuarios\UsuarioService;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Infrastructure\Persistence\UsuarioRepository;
use DomainException;
use PDOException;

final class UsuariosController
{
    private const ROLES = ['admin', 'encargado', 'vendedor', 'consulta'];

    private UsuarioRepository $usuarios;
    private UsuarioService $service;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->service = new UsuarioService();
    }

    public function index(): void
    {
        $query = trim((string) Request::query('q', ''));
        $rol = trim((string) Request::query('rol', ''));

        if (!in_array($rol, self::ROLES, true)) {
            $rol = '';
        }

        View::render('usuarios.index', [
            'title' => 'Usuarios',
            'usuarios' => $this->usuarios->search($query, $rol),
            'stats' => $this->usuarios->stats(),
            'q' => $query,
            'rol' => $rol,
            'roles' => self::ROLES,
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', [
            'nombre' => '',
            'usuario' => '',
            'rol' => 'vendedor',
            'activo' => 1,
            'password' => '',
            'password_confirm' => '',
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $errors = $this->validate($data, null, true);

        if ($errors !== []) {
            $this->renderForm('create', $data, $errors);

            return;
        }

        try {
            $this->service->crear([
                'nombre' => $data['nombre'],
                'usuario' => $data['usuario'],
                'rol' => $data['rol'],
                'activo' => $data['activo'],
                'password' => $data['password'],
            ]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('create', $data, ['usuario' => 'Ya existe un usuario con ese nombre de acceso.']);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Usuario creado correctamente.');
        Response::redirect('/usuarios');
    }

    public function edit(string $id): void
    {
        $usuario = $this->findOrFail((int) $id);

        $this->renderForm('edit', [
            'nombre' => (string) $usuario['nombre'],
            'usuario' => (string) $usuario['usuario'],
            'rol' => (string) $usuario['rol'],
            'activo' => (int) $usuario['activo'],
            'password' => '',
            'password_confirm' => '',
        ], [], (int) $usuario['id']);
    }

    public function update(string $id): void
    {
        $usuarioId = (int) $id;
        $this->findOrFail($usuarioId);

        $data = $this->formData();
        $errors = $this->validate($data, $usuarioId, false);

        if ($errors !== []) {
            $this->renderForm('edit', $data, $errors, $usuarioId);

            return;
        }

        try {
            $this->service->actualizar($usuarioId, [
                'nombre' => $data['nombre'],
                'usuario' => $data['usuario'],
                'rol' => $data['rol'],
                'activo' => $data['activo'],
                'password' => $data['password'],
            ], Auth::id());
        } catch (DomainException $exception) {
            $this->renderForm('edit', $data, ['general' => $exception->getMessage()], $usuarioId);

            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('edit', $data, ['usuario' => 'Ya existe un usuario con ese nombre de acceso.'], $usuarioId);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Usuario actualizado correctamente.');
        Response::redirect('/usuarios');
    }

    public function destroy(string $id): void
    {
        $usuario = $this->findOrFail((int) $id);

        try {
            $this->service->setActivo((int) $usuario['id'], false, Auth::id());
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
            Response::redirect('/usuarios');
        }

        Session::flash('info', 'Usuario desactivado. No podrá iniciar sesión hasta reactivarlo.');
        Response::redirect('/usuarios');
    }

    public function activate(string $id): void
    {
        $usuario = $this->findOrFail((int) $id);
        $this->service->setActivo((int) $usuario['id'], true, Auth::id());

        Session::flash('success', 'Usuario reactivado correctamente.');
        Response::redirect('/usuarios');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'nombre' => trim((string) Request::input('nombre', '')),
            'usuario' => trim((string) Request::input('usuario', '')),
            'rol' => trim((string) Request::input('rol', 'vendedor')),
            'activo' => Request::input('activo') !== null ? 1 : 0,
            'password' => (string) Request::input('password', ''),
            'password_confirm' => (string) Request::input('password_confirm', ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validate(array $data, ?int $exceptId, bool $passwordRequired): array
    {
        $validator = new Validator($data);
        $validator
            ->required('nombre', 'nombre')
            ->max('nombre', 120, 'El nombre')
            ->required('usuario', 'usuario')
            ->max('usuario', 60, 'El usuario')
            ->in('rol', self::ROLES, 'El rol');

        if (
            trim((string) $data['usuario']) !== ''
            && preg_match('/^[A-Za-z0-9._-]{3,60}$/', (string) $data['usuario']) !== 1
        ) {
            $validator->add('usuario', 'El usuario debe tener entre 3 y 60 caracteres: letras, números, punto, guion o guion bajo.');
        }

        if (!$validator->fails() && $this->usuarios->existsUsuario((string) $data['usuario'], $exceptId)) {
            $validator->add('usuario', 'Ya existe un usuario con ese nombre de acceso.');
        }

        $password = (string) $data['password'];
        $confirm = (string) $data['password_confirm'];

        if ($passwordRequired || $password !== '' || $confirm !== '') {
            if ($password === '') {
                $validator->add('password', 'La contraseña es obligatoria.');
            } elseif (mb_strlen($password) < 8) {
                $validator->add('password', 'La contraseña debe tener al menos 8 caracteres.');
            }

            if ($password !== $confirm) {
                $validator->add('password_confirm', 'La confirmación no coincide con la contraseña.');
            }
        }

        return $validator->errors();
    }

    private function renderForm(string $mode, array $usuario, array $errors = [], ?int $id = null): void
    {
        View::render('usuarios.form', [
            'title' => $mode === 'create' ? 'Nuevo usuario' : 'Editar usuario',
            'mode' => $mode,
            'usuario' => $usuario,
            'errors' => $errors,
            'id' => $id,
            'roles' => self::ROLES,
            'esPropio' => $id !== null && $id === Auth::id(),
        ]);
    }

    private function findOrFail(int $id): array
    {
        $usuario = $this->usuarios->findPublic($id);

        if ($usuario === null) {
            Response::abort(404, 'El usuario no existe.');
        }

        return $usuario;
    }
}
