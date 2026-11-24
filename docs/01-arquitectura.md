# Arquitectura

## Decisión

Monolito modular en tres capas con servidor central:

```
PC cliente (Edge/Chrome)
        │ HTTP LAN
        ▼
Apache + PHP 8.4 (XAMPP)
        │ PDO
        ▼
MariaDB 10.4 (solo 127.0.0.1)
```

Se descartó SQLite para múltiples PCs por los problemas de bloqueo y corrupción sobre carpetas de red. Electron/nativo con conexión directa a MySQL se descartó porque obliga a distribuir credenciales y actualizar cada máquina sin aportar ventajas frente a una interfaz web.

## Capas

- `src/Core`: infraestructura transversal (router, request/response, sesión, CSRF, autenticación, PDO, validación, vistas).
- `src/Http/Controllers`: adaptación HTTP; validan y delegan.
- `src/Http/Middleware`: autenticación, autorización por rol y CSRF.
- `src/Application`: casos de uso transaccionales (`ArticuloService`, `MovimientoService`, `UsuarioService`).
- `src/Infrastructure/Persistence`: repositorios PDO.
- `src/Support`: helpers de vistas/formato y utilidades CSV.
- `resources/views`: presentación sin lógica de negocio.
- `public`: único docroot permitido.

## Decisiones clave

1. **Server-rendered + mejoras puntuales de JS**, sin SPA ni build step. La búsqueda instantánea usa un endpoint que devuelve HTML parcial renderizado en el servidor, reutilizando el escapado y las reglas de presentación.
2. **Sesiones del servidor**, no JWT: hay un único backend y se reduce la superficie de ataque.
3. **Stock como libro mayor**: los movimientos son inmutables y `stock_actual` es una caché transaccional. Las correcciones se hacen con movimientos inversos/ajustes.
4. **Borrado lógico** (`activo = 0`) para preservar historial y claves foráneas.
5. **MariaDB central** desde el inicio, incluso para un solo PC, para evitar mantener dos motores.
6. **Reportes calculados en el servidor** sobre el estado actual y el kardex, sin tablas agregadas; la exportación CSV reutiliza los mismos cálculos para evitar divergencias.
7. **Importación CSV transaccional por fila**: las filas inválidas se informan y las válidas se conservan; el stock existente solo cambia mediante movimientos auditados.
8. **Escaneo de códigos en el cliente** con `BarcodeDetector` cuando está disponible, más entrada manual/lector USB como respaldo. El servidor solo recibe el código final.

## Despliegue

- PC servidor: Apache puerto 80, MariaDB en `127.0.0.1`, backup diario.
- Clientes: navegador o ventana `msedge --app=...`.
- El tráfico de la app no requiere abrir el puerto de MySQL.
