<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Utilidades para exportar e importar archivos CSV del inventario.
 *
 * Los archivos se generan con BOM UTF-8 y separador punto y coma para que
 * Excel en español los abra directamente en columnas.
 */
final class Csv
{
    public static function download(string $filename, array $headers, iterable $rows): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . self::safeFilename($filename) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            throw new RuntimeException('No se pudo preparar la descarga CSV.');
        }

        // BOM para que Excel detecte UTF-8.
        fwrite($output, "\xEF\xBB\xBF");
        self::writeRow($output, $headers);

        foreach ($rows as $row) {
            self::writeRow($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Lee un CSV y devuelve encabezados normalizados y filas asociativas.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     *
     * @throws RuntimeException
     */
    public static function read(string $path, int $maxRows = 2000): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('No se pudo procesar el archivo CSV.');
        }

        fwrite($stream, $content);
        rewind($stream);

        $rawHeaders = fgetcsv($stream, 0, $delimiter, '"', '');

        if ($rawHeaders === false || $rawHeaders === [null]) {
            fclose($stream);

            throw new RuntimeException('El archivo CSV está vacío o no tiene encabezados.');
        }

        $headers = [];

        foreach ($rawHeaders as $index => $header) {
            $normalized = self::normalizeHeader((string) $header);

            if ($normalized === '') {
                $normalized = 'columna_' . ($index + 1);
            }

            $headers[$index] = $normalized;
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $line++;

            if ($values === [null] || self::isEmptyRow($values)) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = trim((string) ($values[$index] ?? ''));
            }

            $row['_linea'] = (string) $line;
            $rows[] = $row;

            if (count($rows) > $maxRows) {
                fclose($stream);

                throw new RuntimeException('El archivo supera el máximo de ' . $maxRows . ' filas por importación.');
            }
        }

        fclose($stream);

        if ($rows === []) {
            throw new RuntimeException('El archivo CSV no contiene filas de datos.');
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Convierte valores escritos en formato español o inglés a float.
     */
    public static function decimal(mixed $value): float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return round((float) $value, 2);
    }

    public static function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = strtr($header, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';

        return trim($header, '_');
    }

    /**
     * Evita que Excel interprete texto como fórmula.
     */
    public static function safeCell(mixed $value): mixed
    {
        if (
            is_string($value)
            && $value !== ''
            && !is_numeric($value)
            && preg_match('/^[=+@\t\r-]/', $value) === 1
        ) {
            return "'" . $value;
        }

        return $value;
    }

    private static function writeRow(mixed $stream, array $row): void
    {
        $sanitized = array_map(static fn (mixed $value): mixed => self::safeCell($value), $row);

        fputcsv($stream, $sanitized, ';', '"', '');
    }

    private static function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function safeFilename(string $filename): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? 'export.csv';
    }
}
