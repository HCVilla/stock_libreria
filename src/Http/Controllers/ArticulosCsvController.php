<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Inventario\ArticuloService;
use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Infrastructure\Persistence\ArticuloRepository;
use App\Infrastructure\Persistence\ProveedorRepository;
use App\Infrastructure\Persistence\TipoArticuloRepository;
use App\Support\Csv;
use DomainException;
use PDOException;
use RuntimeException;

final class ArticulosCsvController
{
    private const UNIDADES = ['unidad', 'paquete', 'caja', 'resma', 'metro', 'kg', 'litro'];
    private const MAX_BYTES = 2097152;

    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'sku' => 'codigo',
        'codigo_barras' => 'codigo',
        'codigo_de_barras' => 'codigo',
        'descripcion' => 'nombre',
        'articulo' => 'nombre',
        'categoria' => 'tipo',
        'tipo_articulo' => 'tipo',
        'precio_compra' => 'precio_costo',
        'costo' => 'precio_costo',
        'precio' => 'precio_venta',
        'venta' => 'precio_venta',
        'existencia' => 'stock_actual',
        'stock' => 'stock_actual',
        'minimo' => 'stock_minimo',
        'unidad' => 'unidad_medida',
        'estado' => 'activo',
    ];

    private ArticuloRepository $articulos;
    private ArticuloService $service;

    public function __construct()
    {
        $this->articulos = new ArticuloRepository();
        $this->service = new ArticuloService();
    }

    public function export(): void
    {
        $rows = $this->articulos->allForReport(false, 100000);

        Csv::download(
            'articulos-' . date('Y-m-d') . '.csv',
            [
                'Codigo',
                'Nombre',
                'Tipo',
                'Proveedor',
                'Precio costo',
                'Precio venta',
                'Stock actual',
                'Stock minimo',
                'Unidad',
                'Activo',
            ],
            array_map(static fn (array $articulo): array => [
                $articulo['codigo'],
                $articulo['nombre'],
                $articulo['tipo_nombre'],
                $articulo['proveedor_nombre'] ?? '',
                number_format((float) $articulo['precio_costo'], 2, '.', ''),
                number_format((float) $articulo['precio_venta'], 2, '.', ''),
                number_format((float) $articulo['stock_actual'], 2, '.', ''),
                number_format((float) $articulo['stock_minimo'], 2, '.', ''),
                $articulo['unidad_medida'],
                (int) $articulo['activo'],
            ], $rows)
        );
    }

    public function plantilla(): void
    {
        $tipos = (new TipoArticuloRepository())->all();
        $tipoEjemplo = (string) ($tipos[0]['nombre'] ?? 'Cuadernos');

        Csv::download(
            'plantilla-articulos.csv',
            [
                'Codigo',
                'Nombre',
                'Tipo',
                'Proveedor',
                'Precio costo',
                'Precio venta',
                'Stock actual',
                'Stock minimo',
                'Unidad',
                'Activo',
            ],
            [[
                'COD-001',
                'Cuaderno universitario 100 hojas',
                $tipoEjemplo,
                '',
                '1500.00',
                '2200.00',
                '10',
                '5',
                'unidad',
                '1',
            ]]
        );
    }

    public function importForm(): void
    {
        $this->renderImport([
            'modo' => 'actualizar',
        ]);
    }

    public function import(): void
    {
        $data = [
            'modo' => Request::input('modo') === 'solo_crear' ? 'solo_crear' : 'actualizar',
        ];

        $file = Request::file('archivo');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->renderImport($data, ['archivo' => 'Seleccioná un archivo CSV para importar.']);

            return;
        }

        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_OK);

        if ($uploadError !== UPLOAD_ERR_OK) {
            $this->renderImport($data, ['archivo' => $this->uploadErrorMessage($uploadError)]);

            return;
        }

        $name = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'txt'], true)) {
            $this->renderImport($data, ['archivo' => 'El archivo debe tener extensión .csv o .txt.']);

            return;
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            $this->renderImport($data, ['archivo' => 'El archivo supera el máximo permitido de 2 MB.']);

            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_file($tmp)) {
            $this->renderImport($data, ['archivo' => 'No se pudo acceder al archivo subido.']);

            return;
        }

        try {
            $parsed = Csv::read($tmp, 2000);
        } catch (RuntimeException $exception) {
            $this->renderImport($data, ['archivo' => $exception->getMessage()]);

            return;
        }

        $headerMap = $this->canonicalHeaderMap($parsed['headers']);
        $canonicalHeaders = array_values(array_unique(array_values($headerMap)));

        $required = ['codigo', 'nombre', 'tipo'];
        $missing = array_values(array_diff($required, $canonicalHeaders));

        if ($missing !== []) {
            $this->renderImport($data, [
                'archivo' => 'Faltan columnas obligatorias: ' . implode(', ', $missing) . '. Descargá la plantilla para ver el formato.',
            ]);

            return;
        }

        $resultado = $this->procesar($parsed['rows'], $headerMap, $data['modo']);

        $this->renderImport($data, [], $resultado);
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, string> $headerMap
     *
     * @return array{total: int, creados: int, actualizados: int, omitidos: int, errores: array<int, string>}
     */
    private function procesar(array $rows, array $headerMap, string $modo): array
    {
        $tipos = (new TipoArticuloRepository())->all();
        $proveedores = (new ProveedorRepository())->all();

        $tiposPorNombre = [];
        $tiposPorId = [];

        foreach ($tipos as $tipo) {
            $tiposPorNombre[mb_strtolower((string) $tipo['nombre'])] = $tipo;
            $tiposPorId[(int) $tipo['id']] = $tipo;
        }

        $proveedoresPorNombre = [];
        $proveedoresPorId = [];

        foreach ($proveedores as $proveedor) {
            $proveedoresPorNombre[mb_strtolower((string) $proveedor['nombre'])] = $proveedor;
            $proveedoresPorId[(int) $proveedor['id']] = $proveedor;
        }

        $existentes = [];

        foreach ($this->articulos->allForReport(false, 100000) as $articulo) {
            $existentes[mb_strtolower((string) $articulo['codigo'])] = $articulo;
        }

        $resultado = [
            'total' => count($rows),
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => [],
        ];
        $vistos = [];

        foreach ($rows as $rawRow) {
            $row = $this->canonicalRow($rawRow, $headerMap);
            $linea = (int) ($row['_linea'] ?? 0);
            $erroresFila = [];

            $codigo = trim((string) ($row['codigo'] ?? ''));
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $tipoValor = trim((string) ($row['tipo'] ?? ''));
            $proveedorValor = trim((string) ($row['proveedor'] ?? ''));
            $unidad = mb_strtolower(trim((string) ($row['unidad_medida'] ?? '')));

            if ($unidad === '') {
                $unidad = 'unidad';
            }

            if ($codigo === '') {
                $erroresFila[] = 'el código es obligatorio';
            } elseif (mb_strlen($codigo) > 64) {
                $erroresFila[] = 'el código supera 64 caracteres';
            }

            if ($nombre === '') {
                $erroresFila[] = 'el nombre es obligatorio';
            } elseif (mb_strlen($nombre) > 200) {
                $erroresFila[] = 'el nombre supera 200 caracteres';
            }

            $tipo = $this->resolverTipo($tipoValor, $tiposPorNombre, $tiposPorId);

            if ($tipo === null) {
                $erroresFila[] = 'el tipo «' . $tipoValor . '» no existe (creá el tipo antes de importar)';
            }

            $proveedor = null;

            if ($proveedorValor !== '') {
                $proveedor = $this->resolverProveedor($proveedorValor, $proveedoresPorNombre, $proveedoresPorId);

                if ($proveedor === null) {
                    $erroresFila[] = 'el proveedor «' . $proveedorValor . '» no existe.';
                }
            }

            $precioCosto = $this->decimal($row['precio_costo'] ?? '0', 'precio de costo', $erroresFila);
            $precioVenta = $this->decimal($row['precio_venta'] ?? '0', 'precio de venta', $erroresFila);
            $stockActual = $this->decimal($row['stock_actual'] ?? '0', 'stock actual', $erroresFila);
            $stockMinimo = $this->decimal($row['stock_minimo'] ?? '0', 'stock mínimo', $erroresFila);

            if (!in_array($unidad, self::UNIDADES, true)) {
                $erroresFila[] = 'la unidad «' . $unidad . '» no es válida (' . implode(', ', self::UNIDADES) . ')';
            }

            $activo = $this->activo($row['activo'] ?? '1');

            if ($erroresFila !== []) {
                $resultado['errores'][] = 'Línea ' . $linea . ': ' . implode('. ', $erroresFila) . '.';
                continue;
            }

            $clave = mb_strtolower($codigo);

            if (isset($vistos[$clave])) {
                $resultado['errores'][] = 'Línea ' . $linea . ': el código «' . $codigo . '» está duplicado en el archivo (primera aparición en línea ' . $vistos[$clave] . ').';
                continue;
            }

            $vistos[$clave] = $linea;

            $payload = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo_id' => (int) $tipo['id'],
                'proveedor_id' => $proveedor !== null ? (int) $proveedor['id'] : null,
                'precio_costo' => $precioCosto ?? 0.0,
                'precio_venta' => $precioVenta ?? 0.0,
                'stock_minimo' => $stockMinimo ?? 0.0,
                'unidad_medida' => $unidad,
                'activo' => $activo,
            ];

            if (isset($existentes[$clave])) {
                if ($modo === 'solo_crear') {
                    $resultado['omitidos']++;
                    continue;
                }

                try {
                    $this->articulos->update((int) $existentes[$clave]['id'], $payload);
                    $resultado['actualizados']++;
                } catch (PDOException $exception) {
                    $resultado['errores'][] = 'Línea ' . $linea . ': no se pudo actualizar el artículo (' . $exception->getMessage() . ').';
                }

                continue;
            }

            $payload['stock_actual'] = $stockActual ?? 0.0;

            try {
                $id = $this->service->crear($payload, Auth::id());
                $existentes[$clave] = ['id' => $id] + $payload;
                $resultado['creados']++;
            } catch (DomainException | PDOException $exception) {
                $resultado['errores'][] = 'Línea ' . $linea . ': no se pudo crear el artículo (' . $exception->getMessage() . ').';
            }
        }

        return $resultado;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $resultado
     */
    private function renderImport(array $data, array $errors = [], ?array $resultado = null): void
    {
        View::render('articulos.importar', [
            'title' => 'Importar artículos',
            'data' => $data,
            'errors' => $errors,
            'resultado' => $resultado,
        ]);
    }

    /**
     * @param array<int, string> $headers
     *
     * @return array<string, string>
     */
    private function canonicalHeaderMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $header) {
            $map[$header] = self::HEADER_ALIASES[$header] ?? $header;
        }

        return $map;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, string> $headerMap
     *
     * @return array<string, string>
     */
    private function canonicalRow(array $row, array $headerMap): array
    {
        $out = [];

        foreach ($headerMap as $original => $canonical) {
            if (!array_key_exists($original, $row)) {
                continue;
            }

            if (!isset($out[$canonical]) || trim((string) $out[$canonical]) === '') {
                $out[$canonical] = $row[$original];
            }
        }

        $out['_linea'] = $row['_linea'] ?? '';

        return $out;
    }

    /**
     * @param array<string, array<string, mixed>> $porNombre
     * @param array<int, array<string, mixed>> $porId
     *
     * @return array<string, mixed>|null
     */
    private function resolverTipo(string $valor, array $porNombre, array $porId): ?array
    {
        if ($valor === '') {
            return null;
        }

        if (ctype_digit($valor) && isset($porId[(int) $valor])) {
            return $porId[(int) $valor];
        }

        return $porNombre[mb_strtolower($valor)] ?? null;
    }

    /**
     * @param array<string, array<string, mixed>> $porNombre
     * @param array<int, array<string, mixed>> $porId
     *
     * @return array<string, mixed>|null
     */
    private function resolverProveedor(string $valor, array $porNombre, array $porId): ?array
    {
        if (ctype_digit($valor) && isset($porId[(int) $valor])) {
            return $porId[(int) $valor];
        }

        return $porNombre[mb_strtolower($valor)] ?? null;
    }

    /**
     * @param array<int, string> $errors
     */
    private function decimal(string $value, string $label, array &$errors): ?float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        $check = str_contains($value, ',')
            ? str_replace(',', '.', str_replace('.', '', $value))
            : $value;

        if (!is_numeric($check)) {
            $errors[] = 'el ' . $label . ' no es un número válido';

            return null;
        }

        $number = round((float) $check, 2);

        if ($number < 0) {
            $errors[] = 'el ' . $label . ' no puede ser negativo';

            return null;
        }

        return $number;
    }

    private function activo(string $value): int
    {
        $value = mb_strtolower(trim($value));

        return in_array($value, ['0', 'no', 'false', 'inactivo', 'n'], true) ? 0 : 1;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño permitido por el servidor.',
            UPLOAD_ERR_PARTIAL => 'La carga del archivo quedó incompleta. Intentá nuevamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene una carpeta temporal configurada.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo guardar el archivo temporal.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la carga del archivo.',
            default => 'No se pudo subir el archivo. Intentá nuevamente.',
        };
    }
}
