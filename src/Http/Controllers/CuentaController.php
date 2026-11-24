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
use DomainException;

final class CuentaController
{
    public function password(): void
    {
        View::render('cuenta.password', [
            'title' => 'Cambiar contraseña',
        ]);
    }

    public function updatePassword(): void
    {
        $data = [
            'actual' => (string) Request::input('actual', ''),
            'password' => (string) Request::input('password', ''),
            'password_confirm' => (string) Request::input('password_confirm', ''),
        ];

        $validator = new Validator($data);
        $validator
            ->required('actual', 'contraseña actual')
            ->required('password', 'nueva contraseña');

        if ($data['password'] !== '' && mb_strlen($data['password']) < 8) {
            $validator->add('password', 'La nueva contraseña debe tener al menos 8 caracteres.');
        }

        if ($data['password'] !== $data['password_confirm']) {
            $validator->add('password_confirm', 'La confirmación no coincide con la nueva contraseña.');
        }

        if ($validator->fails()) {
            $this->render($data, $validator->errors());

            return;
        }

        try {
            (new UsuarioService())->cambiarPasswordPropia(Auth::id(), $data['actual'], $data['password']);
        } catch (DomainException $exception) {
            $this->render($data, ['general' => $exception->getMessage()]);

            return;
        }

        Session::flash('success', 'Tu contraseña se actualizó correctamente.');
        Response::redirect('/');
    }

    private function render(array $data, array $errors): void
    {
        View::render('cuenta.password', [
            'title' => 'Cambiar contraseña',
            'data' => $data,
            'errors' => $errors,
        ]);
    }
}
