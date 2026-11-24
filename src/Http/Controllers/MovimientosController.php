<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Inventario\ConcurrencyException;
use App\Application\Inventario\MovimientoService;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\MovimientoRepository;
use DomainException;

final class MovimientosController
{
    private const TIPOS = ['entrada', 'salida', 'ajuste'];

    private MovimientoRepository $movimientos;
    private ArticuloRepository $articulos;
    private MovimientoService $service;

    public function __construct()
    {
        $this->movimientos = new MovimientoRepository();
        $this->articulos = new ArticuloRepository();
        $this->service = new MovimientoService();
    }

    public function index(): void
    {
        $query = trim((string) Request::query('q', ''));
        $tipo = trim((string) Request::query('tipo', ''));

        if (!in_array($tipo, self::TIPOS, true)) {
            $tipo = '';
        }

        View::render('movimientos.index', [
            'title' => 'Movimientos de stock',
            'movimientos' => $this->movimientos->search($query, $tipo, 300),
            'resumen' => $this->movimientos->resumen(),
            'q' => $query,
            'tipo' => $tipo,
        ]);
    }

    public function create(): void
    {
        $articulos = $this->articulos->allActive();

        if ($articulos === []) {
            Session::flash('warning', 'Necesitás al menos un artículo activo para registrar movimientos.');
            Response::redirect('/articulos/nuevo');
        }

        $tipo = (string) Request::query('tipo', 'entrada');

        if (!in_array($tipo, self::TIPOS, true)) {
            $tipo = 'entrada';
        }

        $articuloId = (int) Request::query('articulo_id', 0);
        $seleccionado = '';

        foreach ($articulos as $articulo) {
            if ((int) $articulo['id'] === $articuloId) {
                $seleccionado = (string) $articulo['id'];
                break;
            }
        }

        $this->renderForm([
            'tipo_movimiento' => $tipo,
            'articulo_id' => $seleccionado,
            'cantidad' => normalize_decimal_input(Request::query('cantidad', '')),
            'stock_real' => '',
            'precio_unitario' => '',
            'motivo' => trim((string) Request::query('motivo', '')),
            'referencia' => '',
        ]);
    }

    public function store(): void
    {
        $data = $this->formData();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->renderForm($data, $errors);

            return;
        }

        try {
            $resultado = $this->service->registrar($this->toPayload($data), Auth::id());
        } catch (ConcurrencyException | DomainException $exception) {
            $this->renderForm($data, ['general' => $exception->getMessage()]);

            return;
        }

        $meta = movement_meta($resultado['tipo']);
        $signo = $resultado['cantidad'] > 0 ? '+' : '';

        Session::flash('success', sprintf(
            '%s registrada: %s%s %s de «%s». Stock resultante: %s %s.',
            $meta['label'],
            $signo,
            qty($resultado['cantidad']),
            $resultado['articulo']['unidad_medida'],
            $resultado['articulo']['nombre'],
            qty($resultado['stock_resultante']),
            $resultado['articulo']['unidad_medida']
        ));

        Response::redirect('/movimientos');
    }

    /**
     * @return array<string, string>
     */
    private function formData(): array
    {
        return [
            'tipo_movimiento' => trim((string) Request::input('tipo_movimiento', 'entrada')),
            'articulo_id' => trim((string) Request::input('articulo_id', '')),
            'cantidad' => normalize_decimal_input(Request::input('cantidad', '')),
            'stock_real' => normalize_decimal_input(Request::input('stock_real', '')),
            'precio_unitario' => normalize_decimal_input(Request::input('precio_unitario', '')),
            'motivo' => trim((string) Request::input('motivo', '')),
            'referencia' => trim((string) Request::input('referencia', '')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validate(array $data): array
    {
        $validator = new Validator($data);
        $validator
            ->required('articulo_id', 'artículo')
            ->integer('articulo_id', 'El artículo')
            ->in('tipo_movimiento', self::TIPOS, 'El tipo de movimiento')
            ->max('motivo', 255, 'El motivo')
            ->max('referencia', 64, 'La referencia');

        if ($data['tipo_movimiento'] === 'entrada' || $data['tipo_movimiento'] === 'salida') {
            $validator
                ->numeric('cantidad', 'La cantidad')
                ->minValue('cantidad', 0, 'La cantidad');

            if (!isset($validator->errors()['cantidad']) && (float) $data['cantidad'] <= 0) {
                $validator->add('cantidad', 'La cantidad debe ser mayor que cero.');
            }
        }

        if ($data['tipo_movimiento'] === 'ajuste') {
            $validator
                ->numeric('stock_real', 'El stock real')
                ->minValue('stock_real', 0, 'El stock real');

            if ($data['motivo'] === '') {
                $validator->add('motivo', 'El motivo es obligatorio para los ajustes de inventario.');
            }
        }

        if ($data['precio_unitario'] !== '') {
            $validator
                ->numeric('precio_unitario', 'El precio unitario')
                ->minValue('precio_unitario', 0, 'El precio unitario');
        }

        if (!$validator->fails()) {
            $articulo = $this->articulos->find((int) $data['articulo_id']);

            if ($articulo === null) {
                $validator->add('articulo_id', 'El artículo seleccionado no existe.');
            } elseif ((int) $articulo['activo'] !== 1) {
                $validator->add('articulo_id', 'El artículo seleccionado está inactivo.');
            }
        }

        return $validator->errors();
    }

    /**
     * @return array{
     *     articulo_id: int,
     *     tipo_movimiento: string,
     *     cantidad: float,
     *     stock_real: float,
     *     precio_unitario: float|null,
     *     motivo: string,
     *     referencia: string
     * }
     */
    private function toPayload(array $data): array
    {
        return [
            'articulo_id' => (int) $data['articulo_id'],
            'tipo_movimiento' => (string) $data['tipo_movimiento'],
            'cantidad' => (float) $data['cantidad'],
            'stock_real' => (float) $data['stock_real'],
            'precio_unitario' => $data['precio_unitario'] !== '' ? (float) $data['precio_unitario'] : null,
            'motivo' => (string) $data['motivo'],
            'referencia' => (string) $data['referencia'],
        ];
    }

    private function renderForm(array $data, array $errors = []): void
    {
        View::render('movimientos.form', [
            'title' => 'Registrar movimiento',
            'data' => $data,
            'errors' => $errors,
            'articulos' => $this->articulos->allActive(),
            'tipos' => self::TIPOS,
        ]);
    }
}
