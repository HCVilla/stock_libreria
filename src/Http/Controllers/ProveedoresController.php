<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Infrastructure\Persistence\ProveedorRepository;
use PDOException;

final class ProveedoresController
{
    private ProveedorRepository $proveedores;

    public function __construct()
    {
        $this->proveedores = new ProveedorRepository();
    }

    public function index(): void
    {
        View::render('proveedores.index', [
            'title' => 'Proveedores',
            'proveedores' => $this->proveedores->allWithCounts(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', [
            'nombre' => '',
            'telefono' => '',
            'contacto' => '',
            'notas' => '',
            'activo' => 1,
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->renderForm('create', $data, $errors);

            return;
        }

        try {
            $this->proveedores->create($data);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('create', $data, ['nombre' => 'Ya existe un proveedor con ese nombre.']);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Proveedor creado correctamente.');
        Response::redirect('/proveedores');
    }

    public function edit(string $id): void
    {
        $proveedor = $this->findOrFail((int) $id);
        $this->renderForm('edit', $proveedor, [], (int) $proveedor['id']);
    }

    public function update(string $id): void
    {
        $proveedorId = (int) $id;
        $this->findOrFail($proveedorId);

        $data = $this->formData();
        $errors = $this->validate($data, $proveedorId);

        if ($errors !== []) {
            $this->renderForm('edit', $data, $errors, $proveedorId);

            return;
        }

        try {
            $this->proveedores->update($proveedorId, $data);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('edit', $data, ['nombre' => 'Ya existe un proveedor con ese nombre.'], $proveedorId);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Proveedor actualizado correctamente.');
        Response::redirect('/proveedores');
    }

    public function destroy(string $id): void
    {
        $proveedor = $this->findOrFail((int) $id);
        $this->proveedores->setActivo((int) $proveedor['id'], false);

        Session::flash('info', 'Proveedor desactivado. Sus artículos conservan el historial.');
        Response::redirect('/proveedores');
    }

    public function activate(string $id): void
    {
        $proveedor = $this->findOrFail((int) $id);
        $this->proveedores->setActivo((int) $proveedor['id'], true);

        Session::flash('success', 'Proveedor reactivado correctamente.');
        Response::redirect('/proveedores');
    }

    private function formData(): array
    {
        return [
            'nombre' => trim((string) Request::input('nombre', '')),
            'telefono' => trim((string) Request::input('telefono', '')),
            'contacto' => trim((string) Request::input('contacto', '')),
            'notas' => trim((string) Request::input('notas', '')),
            'activo' => Request::input('activo') !== null ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $exceptId = null): array
    {
        $validator = new Validator($data);
        $validator->required('nombre', 'nombre')
            ->max('nombre', 150, 'El nombre')
            ->max('telefono', 30, 'El teléfono')
            ->max('contacto', 120, 'El contacto')
            ->max('notas', 2000, 'Las notas');

        if (!$validator->fails() && $this->proveedores->existsNombre((string) $data['nombre'], $exceptId)) {
            $validator->add('nombre', 'Ya existe un proveedor con ese nombre.');
        }

        return $validator->errors();
    }

    private function renderForm(string $mode, array $proveedor, array $errors = [], ?int $id = null): void
    {
        View::render('proveedores.form', [
            'title' => $mode === 'create' ? 'Nuevo proveedor' : 'Editar proveedor',
            'mode' => $mode,
            'proveedor' => $proveedor,
            'errors' => $errors,
            'id' => $id,
        ]);
    }

    private function findOrFail(int $id): array
    {
        $proveedor = $this->proveedores->find($id);

        if ($proveedor === null) {
            Response::abort(404, 'El proveedor no existe.');
        }

        return $proveedor;
    }
}
