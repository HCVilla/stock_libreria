<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Inventario\ArticuloService;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\ProveedorRepository;
use App\Infrastructure\Persistence\TipoArticuloRepository;
use DomainException;
use PDOException;

final class ArticulosController
{
    private const UNIDADES = ['unidad', 'paquete', 'caja', 'resma', 'metro', 'kg', 'litro'];

    private ArticuloRepository $articulos;
    private TipoArticuloRepository $tipos;
    private ProveedorRepository $proveedores;
    private ArticuloService $service;

    public function __construct()
    {
        $this->articulos = new ArticuloRepository();
        $this->tipos = new TipoArticuloRepository();
        $this->proveedores = new ProveedorRepository();
        $this->service = new ArticuloService();
    }

    public function index(): void
    {
        $query = trim((string) Request::query('q', ''));
        $articulos = $this->articulos->search($query);

        View::render('articulos.index', [
            'title' => 'Artículos',
            'articulos' => $articulos,
            'q' => $query,
        ]);
    }

    public function search(): void
    {
        $query = trim((string) Request::query('q', ''));
        $articulos = $this->articulos->search($query);

        header('Cache-Control: no-store');

        Response::json([
            'html' => View::partial('articulos._rows', ['articulos' => $articulos]),
            'count' => count($articulos),
            'query' => $query,
        ]);
    }

    public function create(): void
    {
        $tipos = $this->tipos->all();
        $hasActiveType = false;

        foreach ($tipos as $tipo) {
            if ((int) $tipo['activo'] === 1) {
                $hasActiveType = true;
                break;
            }
        }

        if (!$hasActiveType) {
            Session::flash('warning', 'Antes de crear un artículo necesitás al menos un tipo activo.');
            Response::redirect('/tipos/nuevo');
        }

        $this->renderForm('create', [
            'codigo' => '',
            'nombre' => '',
            'tipo_id' => '',
            'proveedor_id' => '',
            'precio_costo' => '0.00',
            'precio_venta' => '0.00',
            'stock_actual' => '0',
            'stock_minimo' => '0',
            'unidad_medida' => 'unidad',
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
            $this->service->crear($this->toPayload($data), Auth::id());
        } catch (DomainException $exception) {
            $this->renderForm('create', $data, ['stock_actual' => $exception->getMessage()]);

            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('create', $data, ['codigo' => 'Ya existe un artículo con ese código.']);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Artículo creado correctamente.');
        Response::redirect('/articulos');
    }

    public function edit(string $id): void
    {
        $articulo = $this->findOrFail((int) $id);
        $this->renderForm('edit', $articulo, [], (int) $articulo['id']);
    }

    public function update(string $id): void
    {
        $articuloId = (int) $id;
        $this->findOrFail($articuloId);

        $data = $this->formData();
        $errors = $this->validate($data, $articuloId);

        if ($errors !== []) {
            $this->renderForm('edit', $data, $errors, $articuloId);

            return;
        }

        try {
            $this->service->actualizar($articuloId, $this->toPayload($data), Auth::id());
        } catch (DomainException $exception) {
            $this->renderForm('edit', $data, ['stock_actual' => $exception->getMessage()], $articuloId);

            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->renderForm('edit', $data, ['codigo' => 'Ya existe un artículo con ese código.'], $articuloId);

                return;
            }

            throw $exception;
        }

        Session::flash('success', 'Artículo actualizado correctamente.');
        Response::redirect('/articulos');
    }

    public function destroy(string $id): void
    {
        $articulo = $this->findOrFail((int) $id);
        $this->articulos->setActivo((int) $articulo['id'], false);

        Session::flash('info', 'Artículo desactivado. El historial de movimientos se conserva.');
        Response::redirect('/articulos');
    }

    public function activate(string $id): void
    {
        $articulo = $this->findOrFail((int) $id);
        $this->articulos->setActivo((int) $articulo['id'], true);

        Session::flash('success', 'Artículo reactivado correctamente.');
        Response::redirect('/articulos');
    }

    private function formData(): array
    {
        return [
            'codigo' => trim((string) Request::input('codigo', '')),
            'nombre' => trim((string) Request::input('nombre', '')),
            'tipo_id' => trim((string) Request::input('tipo_id', '')),
            'proveedor_id' => trim((string) Request::input('proveedor_id', '')),
            'precio_costo' => normalize_decimal_input(Request::input('precio_costo', '')),
            'precio_venta' => normalize_decimal_input(Request::input('precio_venta', '')),
            'stock_actual' => normalize_decimal_input(Request::input('stock_actual', '')),
            'stock_minimo' => normalize_decimal_input(Request::input('stock_minimo', '')),
            'unidad_medida' => (string) Request::input('unidad_medida', 'unidad'),
            'activo' => Request::input('activo') !== null ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $exceptId = null): array
    {
        $validator = new Validator($data);
        $validator
            ->required('codigo', 'código')
            ->max('codigo', 64, 'El código')
            ->required('nombre', 'nombre')
            ->max('nombre', 200, 'El nombre')
            ->integer('tipo_id', 'El tipo de artículo')
            ->numeric('precio_costo', 'El precio de costo')
            ->minValue('precio_costo', 0, 'El precio de costo')
            ->numeric('precio_venta', 'El precio de venta')
            ->minValue('precio_venta', 0, 'El precio de venta')
            ->numeric('stock_actual', 'El stock actual')
            ->minValue('stock_actual', 0, 'El stock actual')
            ->numeric('stock_minimo', 'El stock mínimo')
            ->minValue('stock_minimo', 0, 'El stock mínimo')
            ->in('unidad_medida', self::UNIDADES, 'La unidad de medida');

        if (trim((string) $data['proveedor_id']) !== '') {
            $validator->integer('proveedor_id', 'El proveedor');
        }

        if (!$validator->fails() && !$this->articulos->tipoExists((int) $data['tipo_id'])) {
            $validator->add('tipo_id', 'El tipo de artículo seleccionado no existe.');
        }

        if (
            !$validator->fails()
            && trim((string) $data['proveedor_id']) !== ''
            && !$this->articulos->proveedorExists((int) $data['proveedor_id'])
        ) {
            $validator->add('proveedor_id', 'El proveedor seleccionado no existe.');
        }

        if (!$validator->fails() && $this->articulos->existsCodigo((string) $data['codigo'], $exceptId)) {
            $validator->add('codigo', 'Ya existe un artículo con ese código.');
        }

        return $validator->errors();
    }

    private function toPayload(array $data): array
    {
        return [
            'codigo' => (string) $data['codigo'],
            'nombre' => (string) $data['nombre'],
            'tipo_id' => (int) $data['tipo_id'],
            'proveedor_id' => $data['proveedor_id'] !== '' ? (int) $data['proveedor_id'] : null,
            'precio_costo' => (float) $data['precio_costo'],
            'precio_venta' => (float) $data['precio_venta'],
            'stock_actual' => (float) $data['stock_actual'],
            'stock_minimo' => (float) $data['stock_minimo'],
            'unidad_medida' => (string) $data['unidad_medida'],
            'activo' => (int) $data['activo'],
        ];
    }

    private function renderForm(string $mode, array $articulo, array $errors = [], ?int $id = null): void
    {
        $tipos = $this->tipos->all();
        $proveedores = $this->proveedores->all();

        if ($mode === 'create') {
            $tipos = array_values(array_filter($tipos, static fn (array $tipo): bool => (int) $tipo['activo'] === 1));
            $proveedores = array_values(array_filter($proveedores, static fn (array $proveedor): bool => (int) $proveedor['activo'] === 1));
        }

        View::render('articulos.form', [
            'title' => $mode === 'create' ? 'Nuevo artículo' : 'Editar artículo',
            'mode' => $mode,
            'articulo' => $articulo,
            'errors' => $errors,
            'id' => $id,
            'tipos' => $tipos,
            'proveedores' => $proveedores,
            'unidades' => self::UNIDADES,
        ]);
    }

    private function findOrFail(int $id): array
    {
        $articulo = $this->articulos->find($id);

        if ($articulo === null) {
            Response::abort(404, 'El artículo no existe.');
        }

        return $articulo;
    }
}
