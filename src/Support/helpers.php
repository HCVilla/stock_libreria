<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function env(string $key, ?string $default = null): ?string
{
    return App\Core\Env::get($key, $default);
}

function url(string $path = ''): string
{
    return Url::to($path);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function money(mixed $value): string
{
    return '$ ' . number_format((float) $value, 2, ',', '.');
}

function qty(mixed $value): string
{
    $formatted = number_format((float) $value, 2, ',', '.');

    return rtrim(rtrim($formatted, '0'), ',');
}

function normalize_decimal_input(mixed $value): string
{
    if (is_string($value)) {
        return str_replace(',', '.', trim($value));
    }

    return (string) $value;
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrador',
        'encargado' => 'Encargado',
        'vendedor' => 'Vendedor',
        'consulta' => 'Consulta',
        default => ucfirst($role),
    };
}

function can_manage(): bool
{
    return in_array(Auth::role(), ['admin', 'encargado'], true);
}

/**
 * Estado visual del stock. La regla de negocio es stock_actual <= stock_minimo.
 *
 * @return array{label: string, class: string}
 */
function stock_state(array $articulo): array
{
    if ((int) ($articulo['activo'] ?? 0) !== 1) {
        return ['label' => 'Inactivo', 'class' => 'muted'];
    }

    $stock = (float) ($articulo['stock_actual'] ?? 0);
    $minimo = (float) ($articulo['stock_minimo'] ?? 0);

    if ($stock <= 0) {
        return ['label' => 'Sin stock', 'class' => 'danger'];
    }

    if ($stock <= $minimo) {
        return ['label' => 'Bajo stock', 'class' => 'warning'];
    }

    return ['label' => 'Disponible', 'class' => 'success'];
}

/**
 * Metadatos de presentación para los tipos de movimiento.
 *
 * @return array{label: string, class: string, sign: string, description: string}
 */
function movement_meta(string $tipo): array
{
    return match ($tipo) {
        'entrada' => [
            'label' => 'Entrada',
            'class' => 'success',
            'sign' => '+',
            'description' => 'Compra o reposición de proveedor',
        ],
        'salida' => [
            'label' => 'Salida',
            'class' => 'danger',
            'sign' => '',
            'description' => 'Venta o entrega de mercadería',
        ],
        'ajuste' => [
            'label' => 'Ajuste',
            'class' => 'info',
            'sign' => '',
            'description' => 'Corrección por inventario físico',
        ],
        default => [
            'label' => ucfirst($tipo),
            'class' => 'muted',
            'sign' => '',
            'description' => '',
        ],
    };
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '—' : date('d/m/Y H:i', $timestamp);
}

function format_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function selected(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? ' selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? ' checked' : '';
}

function field_error(array $errors, string $field): ?string
{
    return isset($errors[$field]) ? (string) $errors[$field] : null;
}

function field_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' has-error' : '';
}

function arrow_right(): string
{
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/><path d="M3 12h12"/></svg>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : '?';
}

function icon(string $name, string $class = 'icon'): string
{
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'box' => '<path d="m21 8-9 5-9-5"/><path d="M3 8v8l9 5 9-5V8"/><path d="m3 8 9-5 9 5"/><path d="M12 13v8"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'truck' => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'logout' => '<path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
        'login' => '<path d="m9 17-5-5 5-5"/><path d="M4 12h12"/><path d="M14 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'power' => '<path d="M18.4 6.6a9 9 0 1 1-12.8 0"/><path d="M12 2v9"/>',
        'arrow-left' => '<path d="m15 18-6-6 6-6"/><path d="M9 12h12"/>',
        'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
        'coins' => '<ellipse cx="9" cy="6" rx="6" ry="3"/><path d="M3 6v4c0 1.7 2.7 3 6 3s6-1.3 6-3V6"/><path d="M3 10v4c0 1.7 2.7 3 6 3 1.4 0 2.7-.2 3.7-.6"/><path d="M15 12.5V16c0 1.7 2.7 3 6 3s-6-1.3-6-3v-3.5"/><ellipse cx="15" cy="12" rx="6" ry="3"/>',
        'chart' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M8 7h8"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'eye-off' => '<path d="m3 3 18 18"/><path d="M10.6 6.2A9.9 9.9 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.4 3.1"/><path d="M6.1 6.1C3.6 7.8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 3.7-.7"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'layers' => '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M16 12h5"/><path d="M3 8h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'filter' => '<path d="M3 5h18l-7 8v6l-4 2v-8Z"/>',
        'swap' => '<path d="M7 4v16"/><path d="m3 8 4-4 4 4"/><path d="M17 20V4"/><path d="m21 16-4 4-4-4"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.2a3.5 3.5 0 0 1 0 6.6"/><path d="M18.5 14.4A6.5 6.5 0 0 1 22 20"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9"/><path d="m17 6 2 2"/><path d="m14 9 2 2"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 20h16"/>',
        'upload' => '<path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M4 4h16"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
        'camera' => '<path d="M4 7h3l1.5-2h7L17 7h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="3.5"/>',
        'scan' => '<path d="M3 7V4a1 1 0 0 1 1-1h3"/><path d="M17 3h3a1 1 0 0 1 1 1v3"/><path d="M21 17v3a1 1 0 0 1-1 1h-3"/><path d="M7 21H4a1 1 0 0 1-1-1v-3"/><path d="M7 8v8M10 8v8M14 8v8M17 8v8"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'report' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6M9 9h2"/>',
        'dot' => '<circle cx="12" cy="12" r="4"/>',
    ];

    $path = $icons[$name] ?? $icons['dot'];

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
