# Librería Stock

Aplicación web/desktop de control de stock para una librería: artículos escolares, de oficina, papelería, textos y más. Incluye login con contraseñas hasheadas, ABM de usuarios con roles, cambio de contraseña propio, gestión de tipos de artículo, proveedores y artículos con búsqueda instantánea, movimientos de stock auditados (entradas, salidas y ajustes), alertas de reposición pendiente, reportes exportables a CSV, importación/exportación de artículos y lectura de códigos de barras con cámara o lector USB.

El proyecto está pensado para ejecutarse en un PC servidor de la LAN (XAMPP + MariaDB) y usarse desde cualquier PC cliente con el navegador o con una ventana tipo aplicación (`msedge --app=...`).

---

## Funcionalidades implementadas

- Login con sesión PHP, contraseñas con `password_hash()` (bcrypt) y protección CSRF.
- Roles: `admin`, `encargado`, `vendedor`, `consulta`.
- CRUD de tipos de artículo con activación/desactivación lógica.
- CRUD de proveedores con activación/desactivación lógica.
- CRUD de artículos con:
  - Búsqueda instantánea por nombre o código (debounce + endpoint JSON/HTML).
  - Validación de precios y stock no negativos en cliente y servidor.
  - Indicador **Sin stock** / **Bajo stock** cuando `stock_actual <= stock_minimo`.
  - Stock inicial registrado como movimiento de entrada.
  - Cambios de stock desde la edición registrados como ajuste auditado.
- Panel con indicadores: artículos activos, bajo stock, valor a costo, valor a venta y últimos movimientos.
- **Movimientos de stock** (`/movimientos`):
  - Entradas por compra/reposición, salidas por venta/entrega y ajustes por inventario físico.
  - Actualización de `stock_actual` y registro del historial en `movimientos_stock` dentro de una única transacción con `SELECT ... FOR UPDATE`.
  - Motivo obligatorio en los ajustes; motivo predeterminado en entradas y salidas si se deja vacío.
  - Precio unitario automático (costo para entradas/ajustes, venta para salidas) con posibilidad de overriding.
  - Validación de stock insuficiente y de ajustes sin diferencia, con rollback completo.
  - Reintento automático ante deadlocks/timeouts y mensaje claro si el conflicto persiste.
  - Historial filtrable por texto y tipo, con totales de entradas y salidas.
- **Reposición pendiente** (`/reposicion`):
  - Lista los artículos activos con `stock_actual <= stock_minimo`.
  - Muestra cuánto falta para el mínimo y para un stock objetivo configurable (mínimo × 1, 1.5, 2 o 3).
  - Botón «Reponer» que abre el formulario de entrada precargado con el artículo, la cantidad sugerida y el motivo.
- **Usuarios** (`/usuarios`, solo `admin`):
  - Alta, edición, desactivación y reactivación de usuarios desde la interfaz.
  - Roles `admin`, `encargado`, `vendedor` y `consulta`, con filtros por texto y rol.
  - Protección para no dejar el sistema sin administradores activos y para que un administrador no pueda quitarse su propio rol ni desactivar su cuenta.
  - Restablecimiento de contraseña por parte del administrador.
- **Cambio de contraseña propio** (`/cuenta/password`): cualquier usuario autenticado puede cambiar su contraseña indicando la actual.
- **Reportes** (`/reportes`):
  - Stock bajo con faltante y valor de reposición estimado a costo.
  - Valorización del inventario activo a costo, a venta y margen potencial.
  - Movimientos por período con filtros `desde`, `hasta` y `tipo`, más totales del período.
  - Exportación de cada reporte a CSV.
- **Importación/exportación CSV de artículos**:
  - Exportación completa del catálogo y plantilla descargable.
  - Importación con separador `;` o `,`, UTF-8 o Windows-1252, alias de encabezados y hasta 2.000 filas / 2 MB.
  - Los artículos nuevos se crean con su stock inicial como movimiento de entrada; los existentes actualizan sus datos sin modificar el stock.
  - Informe de creados, actualizados, omitidos y filas con error.
- **Lectura de códigos de barras**:
  - Botón «Escanear» en artículos (búsqueda y formulario) y en el formulario de movimientos.
  - Cámara mediante la API `BarcodeDetector` del navegador, con marco de escaneo y detección EAN/UPC/Code128/QR.
  - Alternativa manual o lector USB (el código se envía con Enter) cuando la cámara no está disponible.
- Backend separado en controladores, servicios y repositorios; sin frameworks ni dependencias externas.

---

## Requisitos

- Windows con **XAMPP** (Apache + PHP 8.1 o superior + MariaDB 10.4 o superior).
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`.
- No requiere Composer ni `npm`.

> No hay `vendor/` ni `node_modules/` que instalar. El proyecto solo necesita las extensiones PHP indicadas (`pdo_mysql`, `mbstring`, `openssl`). Verificalas con:

```powershell
php -m | Select-String 'pdo_mysql|mbstring|openssl'
```

Verificado en este equipo con:

- PHP 8.4.15
- MariaDB 10.4.32
- XAMPP en `C:\xampp`

---

## Puesta en marcha local

### 0. Obtener el código

```powershell
git clone https://github.com/HCVilla/stock_libreria.git Stock
cd Stock
```

Si ya tenés la carpeta, saltá este paso. No copies un `.env` de otra máquina: creá uno nuevo a partir de `.env.example`.

### 1. Configurar el entorno

```powershell
Copy-Item .env.example .env
notepad .env
```

Completá al menos:

```dotenv
APP_NAME="Librería Stock"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=America/Argentina/Buenos_Aires

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=libreria_stock
DB_USERNAME=stock_app
DB_PASSWORD=cambiar_esta_clave

DB_ADMIN_USER=root
DB_ADMIN_PASSWORD=
```

- `DB_USERNAME` / `DB_PASSWORD` son las credenciales que usa la aplicación. Elegí una clave propia: `php bin/migrate.php` la usará para crear o actualizar el usuario `stock_app` en MariaDB.
- `DB_ADMIN_USER` / `DB_ADMIN_PASSWORD` se usan **solo** en `bin/migrate.php` para crear la base y el usuario de la app.
- En XAMPP, `root` normalmente no tiene contraseña. Si le pusiste una, completá `DB_ADMIN_PASSWORD`.
- Para producción, usá `APP_DEBUG=false`.
- Si cambiás `DB_PASSWORD` después de migrar, volvé a ejecutar `php bin/migrate.php`.

### 2. Iniciar MariaDB

Desde el panel de XAMPP, iniciá **MySQL**. También podés hacerlo por consola:

```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--console" -WindowStyle Hidden
```

### 3. Crear la base, tablas, vistas y usuario de la app

```powershell
php bin/migrate.php
```

Esto crea `libreria_stock`, las tablas, las vistas de apoyo, el usuario `stock_app` y carga los tipos iniciales (Cuadernos, Lápices y bolígrafos, Carpetas, Resmas, Arte y manualidades, Textos).

### 4. Crear los usuarios de prueba (uno por rol)

```powershell
php bin/crear_admin.php --usuario=admin     --password="Admin123!"     --nombre="Administrador"       --rol=admin
php bin/crear_admin.php --usuario=encargado --password="Encargado123!" --nombre="Encargado de stock"  --rol=encargado
php bin/crear_admin.php --usuario=vendedor  --password="Vendedor123!"  --nombre="Vendedor de mostrador" --rol=vendedor
php bin/crear_admin.php --usuario=consulta  --password="Consulta123!"  --nombre="Usuario de consulta" --rol=consulta
```

El script guarda únicamente el hash de la contraseña. Si el usuario ya existe, actualiza su contraseña, el rol y lo reactiva.

> **Credenciales de prueba por rol**

| Usuario | Contraseña | Rol | Permisos principales |
|---|---|---|---|
| `admin` | `Admin123!` | admin | Acceso total: usuarios, catálogo, movimientos, importación y reportes |
| `encargado` | `Encargado123!` | encargado | Gestión de catálogo, importación CSV y registro de movimientos |
| `vendedor` | `Vendedor123!` | vendedor | Consulta de catálogo, movimientos, reposición y reportes; no registra movimientos |
| `consulta` | `Consulta123!` | consulta | Solo lectura de catálogo, movimientos, reposición y reportes |

> **Importante:** son credenciales de demostración. Cambialas antes de usar el sistema en producción ejecutando nuevamente el script con otra contraseña.

### 5. Ejecutar la aplicación

**Opción A — servidor embebido de PHP (desarrollo):**

```powershell
php -S 127.0.0.1:8080 -t public public/index.php
```

Abrí <http://127.0.0.1:8080>.

**Opción B — Apache de XAMPP (recomendado para la LAN):**

El `DocumentRoot` **debe** apuntar a la carpeta `public`, nunca a la raíz del proyecto. Si se sirve la raíz, `.env` queda accesible por URL.

Creá un VirtualHost en `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName stock.local
    DocumentRoot "C:/xampp/htdocs/Stock/public"

    <Directory "C:/xampp/htdocs/Stock/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Agregá en `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 stock.local
```

Reiniciá Apache y abrí <http://stock.local>.

> Requisito: `mod_rewrite` habilitado y `AllowOverride All` para que funcione `public/.htaccess`.

### 6. Verificación rápida

1. Abrí <http://127.0.0.1:8080/login>.
2. Ingresá con el usuario administrador.
3. Creá un tipo, un proveedor y un artículo de prueba.
4. Registrá una entrada y una salida desde Movimientos.
5. Revisá `/reportes` y descargá un CSV.
6. Probá el botón «Escanear» o cargá un código manualmente.

### 7. Uso desde otra PC de la LAN

1. Asigná IP fija o reserva DHCP al PC servidor (ej. `192.168.1.50`).
2. Iniciá Apache y MariaDB en el servidor.
3. Permití el puerto 80 en el Firewall de Windows.
4. Desde el cliente abrí `http://192.168.1.50/stock/` (si usás subcarpeta) o `http://stock.local/`.
5. Para una ventana tipo aplicación:

```powershell
msedge --app=http://192.168.1.50/stock/
```

MariaDB debe escuchar solo en `127.0.0.1`; los clientes nunca se conectan directo a la base.

---

## Estructura del proyecto

> **Archivos locales:** `.env`, `storage/logs/*`, `storage/backups/*` y `storage/cache/*` no se versionan. En un clon nuevo solo existen `.env.example` y los `.gitkeep` de `storage/`.

```
Stock/
├─ public/                     ← único directorio expuesto
│  ├─ index.php                ← front controller y router del servidor embebido
│  ├─ .htaccess
│  └─ assets/css|js
├─ config/                     ← app, base de datos y rutas
├─ src/
│  ├─ Core/                    ← router, request/response, sesión, CSRF, auth, DB
│  ├─ Http/Controllers/        ← controladores de pantallas, API y CSV
│  ├─ Http/Middleware/         ← autenticación, roles y CSRF
│  ├─ Application/Inventario/  ← servicios de artículos y movimientos de stock
│  ├─ Application/Usuarios/    ← servicio de usuarios y contraseñas
│  ├─ Domain/                  ← reservado para entidades de dominio
│  ├─ Infrastructure/Persistence/ ← repositorios PDO
│  └─ Support/                 ← helpers de vistas, formato y utilidades CSV
├─ resources/views/            ← layouts, pantallas y parciales
├─ database/
│  ├─ schema.sql               ← esquema MariaDB + vistas
│  └─ seeds/                   ← datos iniciales
├─ bin/
│  ├─ migrate.php              ← crea base, usuario y seeds
│  ├─ crear_admin.php          ← crea/resetea usuarios
│  └─ backup.php               ← copia de seguridad con mysqldump
├─ storage/logs|backups        ← fuera del docroot
├─ docs/                       ← arquitectura y modelo de datos
├─ .env.example                ← plantilla de configuración (versionada)
├─ .env                        ← configuración local (no versionar)
├─ roles.txt                   ← credenciales demo de los usuarios de prueba
└─ README.md
```

---

## Capas y flujo

```
Vista (PHP + HTML)
   ↓
Controlador (valida, autoriza, redirige)
   ↓
Servicio de aplicación (transacciones y reglas)
   ↓
Repositorio (PDO, consultas preparadas)
   ↓
MariaDB (InnoDB)
```

- **Controladores:** no escriben SQL; reciben la petición, validan y llaman a repositorios/servicios.
- **Repositorios:** todo el acceso a datos con PDO y consultas preparadas.
- **Servicio `ArticuloService`:** garantiza que el stock solo cambie junto con un movimiento.
- **Servicio `MovimientoService`:** registra entradas, salidas y ajustes en una transacción y aplica las reglas de stock.
- **Vistas:** reciben datos ya preparados; no contienen lógica de negocio.

### Movimientos de stock

| Ruta | Método | Acceso | Descripción |
|---|---|---|---|
| `/movimientos` | GET | Todos los autenticados | Historial con filtros por texto y tipo |
| `/movimientos/nuevo` | GET | admin, encargado | Formulario de entrada, salida o ajuste |
| `/movimientos` | POST | admin, encargado | Registra el movimiento y actualiza el stock |
| `/reposicion` | GET | Todos los autenticados | Reposición pendiente con factor de objetivo |

Reglas aplicadas por `MovimientoService`:

- **Entrada:** `cantidad > 0`; suma al stock. Motivo predeterminado: «Compra / reposición de proveedor».
- **Salida:** `cantidad > 0`; se guarda con signo negativo. Se rechaza si el stock resultante sería negativo. Motivo predeterminado: «Venta / entrega».
- **Ajuste:** se ingresa el **stock real contado** y el servicio calcula `delta = stock_real − stock_actual`. El motivo es obligatorio y el delta no puede ser cero.
- El precio unitario es opcional: por defecto se usa `precio_costo` (entradas/ajustes) o `precio_venta` (salidas).
- Cada operación bloquea el artículo con `SELECT ... FOR UPDATE` y confirma stock + movimiento en la misma transacción. Ante deadlock o timeout se reintenta hasta 3 veces; si persiste, se muestra un mensaje de conflicto y no se guarda nada.
- El objetivo de reposición es `max(stock_minimo × factor, stock_minimo)`; si el mínimo es 0 se sugiere 1 unidad.

### Usuarios y contraseñas

| Ruta | Método | Acceso | Descripción |
|---|---|---|---|
| `/usuarios` | GET | admin | Listado con filtros por nombre, usuario y rol |
| `/usuarios/nuevo` | GET | admin | Formulario de alta |
| `/usuarios` | POST | admin | Crea el usuario |
| `/usuarios/{id}/editar` | GET | admin | Formulario de edición |
| `/usuarios/{id}` | POST | admin | Actualiza datos, rol, estado y contraseña opcional |
| `/usuarios/{id}/desactivar` | POST | admin | Desactiva el acceso sin borrar el historial |
| `/usuarios/{id}/activar` | POST | admin | Reactiva el acceso |
| `/cuenta/password` | GET | autenticados | Formulario de cambio de contraseña propio |
| `/cuenta/password` | POST | autenticados | Verifica la contraseña actual y guarda la nueva |

Reglas de seguridad:

- Las contraseñas se almacenan con `password_hash()` y nunca se muestran.
- El nombre de usuario es único y acepta entre 3 y 60 caracteres (`A-Z`, `a-z`, `0-9`, `.`, `_`, `-`).
- La contraseña debe tener al menos 8 caracteres; en la edición puede dejarse vacía para conservarla.
- No se puede desactivar al último administrador activo.
- Un administrador no puede cambiarse el rol ni desactivar su propia cuenta, para evitar un bloqueo accidental.
- Desactivar un usuario impide su login, pero conserva sus movimientos y registros históricos.

### Reportes y exportaciones

| Ruta | Método | Acceso | Descripción |
|---|---|---|---|
| `/reportes` | GET | autenticados | Stock bajo, valorización y movimientos del período |
| `/reportes/exportar/stock-bajo` | GET | autenticados | CSV de artículos con stock bajo |
| `/reportes/exportar/valorizacion` | GET | autenticados | CSV de valorización del inventario activo |
| `/reportes/exportar/movimientos` | GET | autenticados | CSV de movimientos del período |

- Los filtros `desde` y `hasta` usan el formato `Y-m-d`; por defecto se aplica desde el primer día del mes hasta hoy. Si las fechas vienen invertidas, se ordenan automáticamente.
- `tipo` acepta `entrada`, `salida`, `ajuste` o vacío para todos.
- La valorización considera solo artículos activos: `valor_costo = stock × precio_costo`, `valor_venta = stock × precio_venta` y `margen = valor_venta − valor_costo`.
- El reporte de stock bajo muestra el faltante para el mínimo y su valor estimado a costo.
- Los CSV se descargan con BOM UTF-8, separador `;` y protección contra fórmulas de Excel en celdas de texto.

### Importación y exportación CSV de artículos

| Ruta | Método | Acceso | Descripción |
|---|---|---|---|
| `/articulos/exportar` | GET | autenticados | Exporta todo el catálogo a CSV |
| `/articulos/plantilla` | GET | admin, encargado | Descarga una plantilla con encabezados y ejemplo |
| `/articulos/importar` | GET | admin, encargado | Formulario de importación y ayuda de formato |
| `/articulos/importar` | POST | admin, encargado | Procesa el archivo subido |

Formato esperado (los nombres de columna no distinguen mayúsculas ni acentos):

| Columna | Obligatoria | Descripción |
|---|---|---|
| `Codigo` | Sí | SKU o código de barras único, hasta 64 caracteres |
| `Nombre` | Sí | Nombre del artículo, hasta 200 caracteres |
| `Tipo` | Sí | Nombre exacto de un tipo existente (o su ID) |
| `Proveedor` | No | Nombre exacto de un proveedor existente (o su ID); vacío = sin proveedor |
| `Precio costo` | No | Número no negativo; vacío = 0 |
| `Precio venta` | No | Número no negativo; vacío = 0 |
| `Stock actual` | No | Solo al crear: genera un movimiento de entrada inicial |
| `Stock minimo` | No | Umbral de reposición; vacío = 0 |
| `Unidad` | No | `unidad`, `paquete`, `caja`, `resma`, `metro`, `kg` o `litro`; vacío = `unidad` |
| `Activo` | No | `1/0`, `sí/no`; vacío = activo |

- Se aceptan separadores `;` y `,`, codificación UTF-8 (con o sin BOM) o Windows-1252.
- Límites: 2 MB y 2.000 filas por importación.
- Se aceptan alias habituales: `SKU`, `Descripcion`, `Categoria`, `Costo`, `Precio`, `Stock`, `Minimo`, `Existencia`, entre otros.
- Modo **Crear y actualizar**: crea códigos nuevos y actualiza los existentes (nombre, tipo, proveedor, precios, mínimo, unidad y estado). **No modifica el stock de artículos existentes.**
- Modo **Solo crear nuevos**: omite los códigos que ya existen.
- Los errores se informan por número de línea; las filas válidas se guardan aunque otras fallen.
- Para cambiar stock de artículos existentes usá Movimientos (entrada, salida o ajuste), así queda auditado.

### Lectura de códigos de barras

- Los botones «Escanear» abren un modal con cámara y entrada manual.
- En Artículos, el código escaneado completa la búsqueda o el campo `Codigo` del formulario.
- En Movimientos, el código selecciona el artículo correspondiente en el desplegable.
- La cámara usa `BarcodeDetector` (Edge/Chrome modernos) y requiere un contexto seguro: `http://localhost`, `http://127.0.0.1` o HTTPS. En una IP de LAN por HTTP simple el navegador bloquea la cámara.
- Si la cámara no está disponible o se deniega el permiso, se puede escribir el código o usar un lector USB; el lector envía el código y Enter automáticamente.
- El código leído debe coincidir exactamente con `articulos.codigo`.

### Reglas de stock

- `movimientos_stock` es el libro mayor. `cantidad` se guarda con signo: entrada `+`, salida `−`, ajuste `+/−`.
- `articulos.stock_actual` es una caché que se actualiza en la misma transacción que inserta el movimiento, con `SELECT ... FOR UPDATE`.
- Al crear un artículo con stock inicial > 0 se registra una **entrada** con motivo “Stock inicial”.
- Al editar el stock desde el formulario se registra un **ajuste** con el delta y el usuario que lo hizo.
- El stock nunca puede quedar negativo (`CHECK` en la base + validación en el servicio).
- Desactivar artículos, tipos o proveedores no borra historial; se usa `activo = 0`.

### Búsqueda instantánea

- La pantalla `/articulos` renderiza la tabla en el servidor.
- El campo de búsqueda consulta `GET /api/articulos?q=...` con `Accept: application/json`.
- El endpoint devuelve el HTML de las filas ya escapado y el total; el JS reemplaza el `<tbody>` sin recargar la página.
- Sin JavaScript, el formulario sigue funcionando con `GET /articulos?q=...`.

### Roles

| Acción | admin | encargado | vendedor | consulta |
|---|---|---|---|---|
| Ver panel, catálogo, movimientos y reposición | ✔ | ✔ | ✔ | ✔ |
| Ver reportes y exportarlos a CSV | ✔ | ✔ | ✔ | ✔ |
| Exportar el catálogo a CSV | ✔ | ✔ | ✔ | ✔ |
| Registrar entradas, salidas y ajustes | ✔ | ✔ | — | — |
| Crear/editar/desactivar artículos, tipos y proveedores | ✔ | ✔ | — | — |
| Importar artículos desde CSV y descargar la plantilla | ✔ | ✔ | — | — |
| Cambiar stock desde la edición del artículo | ✔ | ✔ | — | — |
| Cambiar la contraseña propia | ✔ | ✔ | ✔ | ✔ |
| Gestionar usuarios y restablecer contraseñas | ✔ | — | — | — |

Los usuarios también pueden crearse o resetearse por consola con `bin/crear_admin.php`. Ese script es útil para el administrador inicial o para recuperar el acceso si se pierde la contraseña.

---

## Seguridad

- Contraseñas con `password_hash()` y verificación con `password_verify()`; nunca se guarda texto plano.
- Sesiones con cookie `HttpOnly`, `SameSite=Lax` y regeneración de ID al iniciar sesión.
- Token CSRF en todos los formularios POST, incluidos usuarios, contraseña, movimientos e importación.
- Consultas preparadas y escape de salida con `htmlspecialchars`.
- Middleware de autenticación, expiración por inactividad y autorización por rol.
- Protección del último administrador activo y contra el auto-bloqueo de la cuenta propia.
- Importación CSV limitada a 2 MB / 2.000 filas, con validación de tipo, unidad, precios y stock no negativos.
- Exportaciones CSV con BOM UTF-8 y neutralización de fórmulas (`=`, `+`, `@`, `-`) para evitar inyección al abrir en Excel.
- `.env` fuera del docroot; el servidor debe apuntar a `public/`.
- Usuario de base de datos dedicado con permisos CRUD, sin `root` para la aplicación.

**Pendiente para producción:** HTTPS interno (por ejemplo `mkcert`) —también necesario para la cámara fuera de localhost—, límite de intentos de login y bitácora de accesos.

---

## Copias de seguridad

```powershell
php bin/backup.php
```

Genera un `.sql` en `storage/backups/`. Programalo con el Programador de tareas de Windows y copiá el resultado a otro equipo o a la nube. Probá la restauración periódicamente.

Restauración manual:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root libreria_stock < storage\backups\archivo.sql"
```

---

## Comandos útiles

```powershell
# Aplicar esquema y seeds (idempotente)
php bin/migrate.php

# Crear o resetear un usuario
php bin/crear_admin.php --usuario=admin --password="NuevaClave123!" --nombre="Administrador"

# Backup
php bin/backup.php

# Servidor de desarrollo
php -S 127.0.0.1:8080 -t public public/index.php

# Verificar sintaxis PHP de todo el proyecto
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

---

## Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `SQLSTATE[HY000] [2002]` | MariaDB no está iniciado | Iniciar MySQL desde XAMPP |
| `Access denied for user 'stock_app'` | Faltó migrar o cambió la clave | Revisar `.env` y ejecutar `php bin/migrate.php` |
| Página 404 en `/articulos` | `mod_rewrite` deshabilitado | Habilitarlo en Apache y `AllowOverride All` |
| CSS/JS no cargan bajo subcarpeta | Base URL mal detectada | Usar VirtualHost con docroot en `public` |
| `.env` accesible por URL | Apache apunta a la raíz del proyecto | Cambiar `DocumentRoot` a `.../Stock/public` |
| Error 419 al guardar | Token CSRF vencido/sesión expirada | Recargar la página e iniciar sesión otra vez |
| El stock no cambia | El artículo está inactivo o hubo error de validación | Revisar el mensaje y el kardex en la base |
| «Otro usuario está moviendo stock…» | Conflicto de bloqueo entre operaciones simultáneas | Esperar unos segundos y volver a intentar; no se guardó ningún cambio |
| La cámara no se activa | La página se abrió por IP de LAN sobre HTTP o el navegador no soporta `BarcodeDetector` | Usar `localhost`/`127.0.0.1`, HTTPS, o escribir el código / usar lector USB |
| «La lectura por cámara no está disponible» | Navegador sin soporte de `BarcodeDetector` | Usar Edge/Chrome actualizado o el código manual |
| La importación rechaza un tipo o proveedor | El nombre no coincide exactamente con uno existente | Crear primero el tipo/proveedor o usar su ID numérico |
| El CSV se abre en una sola columna | Excel no detectó el separador | Usar la plantilla del sistema (separador `;`) o importar con «Datos → Texto en columnas» |
| La importación no cambia el stock existente | Comportamiento esperado para proteger el historial | Registrar una entrada, salida o ajuste desde Movimientos |
| No se puede desactivar un usuario | Es el último administrador activo o es tu propia cuenta | Crear/promover otro administrador o editar desde otra cuenta admin |
| «El archivo supera el máximo de 2 MB» | Límite de importación | Dividir el CSV en archivos más pequeños |
| `could not find driver` | Falta la extensión `pdo_mysql` | Habilitarla en `php.ini` y reiniciar PHP/Apache |
| No se puede escribir en `storage/` | Permisos insuficientes | Dar escritura a `storage/logs` y `storage/backups` |

Los errores internos se registran en `storage/logs/app.log` y `storage/logs/php-error.log`.

---

## Próximos pasos sugeridos

1. ~~ABM de usuarios y cambio de contraseña desde la interfaz.~~ ✔ Implementado.
2. ~~Reportes exportables (stock bajo, valorización, movimientos por período).~~ ✔ Implementado.
3. ~~Importación/exportación CSV y lectura de códigos de barras.~~ ✔ Implementado.
4. HTTPS interno, bloqueo por intentos fallidos y bitácora de accesos.
5. Notificaciones de reposición por correo o integración con proveedores.
