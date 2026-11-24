# Modelo de datos

Base: `libreria_stock`, motor InnoDB, `utf8mb4_unicode_ci`.

## Tablas

### `usuarios`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT UNSIGNED AI | PK |
| `nombre` | VARCHAR(120) | Nombre visible |
| `usuario` | VARCHAR(60) | UNIQUE, login |
| `password_hash` | VARCHAR(255) | bcrypt/Argon2id |
| `rol` | ENUM | admin, encargado, vendedor, consulta |
| `activo` | TINYINT(1) | Baja lógica |
| `ultimo_acceso` | DATETIME | Se actualiza al login |

### `tipos_articulo`

`id`, `nombre` UNIQUE, `descripcion`, `activo`, `creado_en`.

### `proveedores`

`id`, `nombre` UNIQUE, `telefono`, `contacto`, `notas`, `activo`, timestamps.

### `articulos`

`id`, `codigo` UNIQUE, `nombre`, `tipo_id` FK, `proveedor_id` FK nullable, `precio_costo`, `precio_venta`, `stock_actual`, `stock_minimo`, `unidad_medida`, `activo`, timestamps.

Reglas:

- Precios y stock `DECIMAL(12,2)`.
- `CHECK` de no negatividad.
- FK a tipo `RESTRICT`; FK a proveedor `SET NULL`.
- `codigo` identifica SKU o código de barras.

### `movimientos_stock`

`id`, `articulo_id` FK, `usuario_id` FK, `tipo_movimiento`, `cantidad` con signo, `precio_unitario_momento`, `motivo`, `referencia`, `stock_resultante`, `fecha_hora`.

- Entrada: `cantidad > 0`.
- Salida: `cantidad < 0`.
- Ajuste: `cantidad <> 0`.
- Restricciones `CHECK` de signo, cantidad distinta de cero, precio no negativo y stock resultante no negativo.
- Índices por artículo/fecha, usuario/fecha y tipo/fecha.

## Relaciones

```
tipos_articulo 1 ─── N articulos
proveedores    1 ─── N articulos
articulos      1 ─── N movimientos_stock
usuarios       1 ─── N movimientos_stock
```

## Vistas

- `v_articulos_alerta`: artículos activos sin stock o por debajo del mínimo.
- `v_kardex`: movimientos con artículo, usuario e importe.

## Consistencia

Invariante:

```sql
SUM(movimientos_stock.cantidad) = articulos.stock_actual
```

Se verifica con:

```sql
SELECT a.id, a.codigo, a.stock_actual, COALESCE(SUM(m.cantidad), 0) AS ledger
FROM articulos a
LEFT JOIN movimientos_stock m ON m.articulo_id = a.id
GROUP BY a.id, a.codigo, a.stock_actual
HAVING a.stock_actual <> COALESCE(SUM(m.cantidad), 0);
```
