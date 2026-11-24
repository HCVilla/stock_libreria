<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Infrastructure\Persistence\TipoArticuloRepository;
use PDOException;

final class TiposController
{
    private TipoArticuloRepository $tipos;

    public function __construct()
    {
        $this->tipos = new TipoArticuloRepository();
    }

    public function index(): void
    {
        View::render('tipos.index', [
            'title' => 'Tipos de artículo',
            'tipos' => $this->tipos->allWithCounts(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', [
            'nombre' => '',
            'descripcion' => '',
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
            $this->tipos->create($data);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('create', $data, ['nombre' => 'Ya existe un tipo con ese nombre.']);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Tipo de artículo creado correctamente.');
        Response::redirect('/tipos');
    }

    public function edit(string $id): void
    {
        $tipo = $this->findOrFail((int) $id);
        $this->renderForm('edit', $tipo, [], (int) $tipo['id']);
    }

    public function update(string $id): void
    {
        $tipoId = (int) $id;
        $this->findOrFail($tipoId);

        $data = $this->formData();
        $errors = $this->validate($data, $tipoId);

        if ($errors !== []) {
            $this->renderForm('edit', $data, $errors, $tipoId);

            return;
        }

        try {
            $this->tipos->update($tipoId, $data);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('edit', $data, ['nombre' => 'Ya existe un tipo con ese nombre.'], $tipoId);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Tipo de artículo actualizado.');
        Response::redirect('/tipos');
    }

    public function destroy(string $id): void
    {
        $tipo = $this->findOrFail((int) $id);
        $this->tipos->setActivo((int) $tipo['id'], false);

        Session::flash('info', 'Tipo desactivado. Sus artículos conservan el historial.');
        Response::redirect('/tipos');
    }

    public function activate(string $id): void
    {
        $tipo = $this->findOrFail((int) $id);
        $this->tipos->setActivo((int) $tipo['id'], true);

        Session::flash('success', 'Tipo reactivado correctamente.');
        Response::redirect('/tipos');
    }

    private function formData(): array
    {
        return [
            'nombre' => trim((string) Request::input('nombre', '')),
            'descripcion' => trim((string) Request::input('descripcion', '')),
            'activo' => Request::input('activo') !== null ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $exceptId = null): array
    {
        $validator = new Validator($data);
        $validator->required('nombre', 'nombre')
            ->max('nombre', 100, 'El nombre')
            ->max('descripcion', 255, 'La descripción');

        if (!$validator->fails() && $this->tipos->existsNombre((string) $data['nombre'], $exceptId)) {
            $validator->add('nombre', 'Ya existe un tipo con ese nombre.');
        }

        return $validator->errors();
    }

    private function renderForm(string $mode, array $tipo, array $errors = [], ?int $id = null): void
    {
        View::render('tipos.form', [
            'title' => $mode === 'create' ? 'Nuevo tipo de artículo' : 'Editar tipo de artículo',
            'mode' => $mode,
            'tipo' => $tipo,
            'errors' => $errors,
            'id' => $id,
        ]);
    }

    private function findOrFail(int $id): array
    {
        $tipo = $this->tipos->find($id);

        if ($tipo === null) {
            Response::abort(404, 'El tipo de artículo no existe.');
        }

        return $tipo;
    }
}
