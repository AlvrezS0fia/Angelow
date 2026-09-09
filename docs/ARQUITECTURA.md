# Arquitectura de ANGELOW

Este documento describe la arquitectura **real** del proyecto, tal como está implementada hoy.
No describe capas idealizadas: indica explícitamente qué existe y qué **NO IMPLEMENTADO**.

---

## 1. Visión general del sistema

ANGELOW es una tienda de ropa online con un panel de administración y un módulo de
repartidores (entregas). Está compuesto por **cuatro aplicaciones que comparten la misma
base de datos MySQL** (`angelow_db`):

| Aplicación | Tecnología | Puerta de entrada | Propósito |
|------------|-----------|-------------------|-----------|
| Tienda + Panel admin + APIs | PHP 8 (MVC propio) | `public/index.php` vía Apache | Frontend tienda, carrito, checkout, perfil, panel admin |
| App de repartidor (realtime) | Node.js Express + Socket.IO | `Repartidor/server.js` (puerto 3000) | Dashboard de entregas en tiempo real, seguimiento |
| App de repartidor (HTML/JS) | Vanilla HTML/JS/PHP | `app/Views/repartidor/*` | Dashboard repartidor embebido en la app PHP |

> **IMPORTANTE**: El núcleo PHP es la fuente de verdad para usuarios, pedidos y roles.
> La app Node escribe en las mismas tablas MySQL.

---

## 2. Flujo general de datos

```
Usuario (navegador)
      │  HTTP (GET/POST/AJAX)
      ▼
public/index.php (front controller)
      │  carga .env, sesión, config, autoloader
      ▼
App\Core\Router  (config/routes.php)
      │  hace match método+ruta → controlador
      ▼
Controller (App\Controllers\...)
      │  valida/autentica/autoriza según el caso
      │  (AuthController, PerfilController, Admin\*, Api\*, ...)
      ▼
Model (App\Models\... + App\Core\BaseModel)
      │  SQL parametrizado (PDO prepared statements)
      ▼
MySQL (angelow_db)
```

**Capas que SÍ existen**:
- `Controller` → `Model` → `Database (PDO)` → MySQL.
- `App\Core\Auth` (helper de sesión/rol).
- `App\Core\JWTHelper` (tokens para APIs de repartidor).

**Capas que NO existen** (indicado porque la documentación no debe inventarlas):
- **NO implementado**: Servicio/repositorio/DAO separados del `Model`. La lógica de
  negocio vive directamente en los Controladores (varios controladores mezclan
  validación + SQL + respuesta).
- **NO implementado**: middleware global / filtros de ruta centralizados. La
  autorización se hace método a método dentro de cada controlador.
- **NO implementado**: ORM. Se usa PDO con consultas preparadas a mano.

---

## 3. Estructura de carpetas

```
Angelow/
├── .env                  # Variables de entorno (NO se sube a Git)
├── .env.example          # Plantilla de variables (para otros desarrolladores)
├── composer.json         # Autoload PSR-4 (App\ → app/)
├── angelow.sql           # Esquema/referencia de la base de datos
├── app/                  # Núcleo PHP (controladores, modelos, vistas, core)
├── config/               # Rutas y configuración
├── database/             # Migraciones SQL de la BD
├── Document/             # Notas de cambios y soluciones del equipo
├── Repartidor/           # App Node.js Express de repartidor (tiempo real)
├── public/               # Raíz web (index.php, assets)
├── storage/              # Sesiones del servidor (writable)
└── uploads/              # Archivos subidos (documentos de repartidores)
```

---

## 4. Capas y módulos principales

### 4.1 Front controller (`public/index.php`)
- Configura el directorio de sesiones en `storage/sessions` (evita errores de permisos
  en `C:\xampp\tmp`).
- Carga `.env`, configuraciones, autoloader y helpers.
- Crea cookie `cart_session` para invitados (carrito).
- Carga `config/routes.php` en el `Router` y despacha.

### 4.2 Router (`app/Core/Router.php`)
- Convierte rutas con parámetros `{id}` en expresiones regulares nombradas.
- Instancia el controlador `App\Controllers\<Controlador>` y ejecuta la acción.
- En caso de no encontrar ruta devuelve `404 - Página no encontrada`.

### 4.3 Configuración
- `config/app.php`: define `APP_NAME`, `APP_URL`, `TIMEZONE`.
- `config/database.php`: define `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`.
- `config/routes.php`: **tabla central de todas las rutas** (frontend + APIs).

### 4.4 Modelo de datos (PDO)
- `app/Core/Database.php`: singleton de conexión PDO; `query($sql, $params)` con
  sentencias preparadas (protección contra SQL injection).
- `app/Core/Model.php`: clase base con CRUD genérico (`getAll`, `getById`, `create`,
  `update`, `delete`).
- Los modelos concretos (`UsuarioModel`, `PedidoModel`, etc.) usan
  consultas preparadas.

### 4.5 Autenticación y autorización
- `app/Core/Auth.php`: helper por sesión (`check`, `rol`, `isAdmin`, `isRepartidor`,
  `isRepartidorAprobado`, `homeForRole`).
- `app/Core/JWTHelper.php`: firma/verificación HS256 de tokens para las APIs de
  repartidor (`JWT_SECRET`).
- Cada controlador valida el acceso manualmente (patrón repetido).

---

## 5. Comunicación frontend / backend

- Las páginas HTML/PHP cargan JS vanilla en `public/assets/js/`.
- El JS hace `fetch()` a endpoints `/api/...` con `fetch` con cabecera `Accept: application/json`
  y, para operaciones mutables, `Content-Type: application/json` con el cuerpo en JSON.
- Las APIs devuelven JSON con campos `success`/`error`/`message` (no uniforme al 100 %).
- Las APIs de repartidor exigen un token `Authorization: Bearer <jwt>` o una sesión
  con rol `repartidor`.

---

## 6. Base de datos

- Motor: MySQL `angelow_db`, charset `utf8mb4`, conexión PDO.
- Esquema de referencia: `angelow.sql`.
- Migraciones:
  - `database/001_add_asignado_status.sql`
  - `database/999_drop_facturacion.sql` (elimina `facturas`, `facturas_detalle`,
    `facturas_historial` y `pedidos.factura_generada`)
  - `migrations/add_direcciones_tarjetas.sql`

### Tablas principales
| Tabla | Uso |
|-------|-----|
| `usuarios` | Personas y roles (`rol` ENUM: cliente/repartidor/administrador); estado `estado` ENUM (pendiente/activo/inactivo/suspendido/eliminado) |
| `pedidos` | Pedidos y estados (pendiente, confirmado, procesando, listo, asignado, aceptado, recogido, en_camino, entregado, cancelado) |
| `pedidos_detalle` (o detalle de pedido) | Líneas de cada pedido |
| `productos`, `categorias` | Catálogo e inventario |
| `carrito`, `favoritos`, `direcciones`, `tarjetas` | Datos de cliente |
| `solicitudes_repartidores` | Flujo de aprobación de repartidores (con `email`) |
| `documentos` | Documentos subidos por repartidores |
| `vehiculos_repartidores`, `permisos_repartidores`, `historial_repartidores` | Gestión de repartidores |
| `notificaciones` | Notificaciones (`tipo` ENUM: pedido, entrega, promocion, sistema, seguridad, nuevo_pedido, solicitud_repartidor, documento_repartidor, aprobacion_repartidor, rechazo_repartidor, suspension_repartidor) |

---

## 7. Roles y control de acceso

| Rol | Recursos que puede consultar |
|-----|------------------------------|
| `cliente` | Tienda, perfil, carrito, favoritos, sus pedidos |
| `repartidor` | `/repartidor/dashboard`, su perfil, pedidos asignados, sus documentos |
| `administrador` | `/admin` y subrutas (dashboard, pedidos, usuarios, repartidores, inventario) + APIs `/api/admin/*`, `/api/clientes`, `/api/productos`, etc. |

La verificación se hace dentro de cada controlador:
- Páginas admin: `$_SESSION['user']['rol'] === 'administrador'` → si no, redirect a login.
- APIs admin: devuelven `403` si no hay sesión admin.
- Dashboard repartidor: `$_SESSION['user']['rol'] === 'repartidor'` → si no, redirect a `/repartidor/login`.

> La protección **depende de que cada método la implemente**. Durante la auditoría se
> corrigió `Admin\RepartidorController::index()` que no validaba acceso (véase
> `docs/SEGURIDAD.md`).

---

## 8. Flujo de pedidos

```
Cliente
   ↓ (carrito en cookie/sesión)
CompraController::procesar  (/procesar-compra)
   ↓ valida datos + crea el pedido
pedidos + detalle
   ↓
Admin (panel admin)  → cambia estado  (Admin\PedidosController)
   ↓
Repartidor (dashboard)  → acepta, recogido, en_camino, entregado
   ↓
Entrega
```

- Creación/validación: `app/Controllers/CompraController.php`.
- Consulta de pedidos (API): `Api/RepartidorPedidosController` (`?available=1` para
  pedidos sin asignar), `Admin/PedidosController::obtenerPedidos`.
- Cambio de estado: `Admin\PedidosController::updateStatus`, `Api\RepartidorPedidosController::updateStatus`.

---

## 9. Flujo de repartidores

```
Registro (registro_repartidor) → crea usuario `repartidor` + solicitud `pendiente`
   ↓
solicitudes_repartidores (con email)
   ↓
Admin revisa (panel repartidor: documentos, vehículo, datos)
   ↓
Aprobar → usuarios.estado = 'activo' + notificación/aprobacion_repartidor
   ↓
Dashboard repartidor → gestiona entregas
   ↓
Rechazo / Suspensión → estado 'inactivo'/'suspendido' + notificación
```

- Registro: `app/Controllers/RepartidorAuthController.php` y `Api/RepartidorAuthController.php`.
- Aprobación/rechazo/suspensión/activación: `Api\AdminRepartidorController`.
- Dashboard: `app/Views/repartidor/dashboard.php` (+ app Node `Repartidor/server.js`).

**Autenticación del dashboard (corregida en esta sesión):** la página `/repartidor` se
autoriza por **sesión PHP** (`RepartidorController::index`). Las APIs `/api/repartidor/*`
aceptan **JWT** (`Authorization: Bearer`) **o** la **sesión** de repartidor
(`getRepartidorId()`). El front ya no depende de `localStorage.repartidor_token` para
cargar datos: `authHeaders()` refresca el token desde la sesión si falta
(`POST /repartidor/refresh-token`) y solo redirige a login ante un 401/403 **real** de la
API. Esto elimina el bucle "No autenticado" y permite que el dashboard muestre datos reales
incluso si el token ha expirado o no está en el almacenamiento local.

**Módulo "Rastrear Pedido" (dashboard repartidor):** funcionalidad integrada en
`dashboard.php` (botón `.btn-rastrear` → modal `.rastreo-overlay`) para buscar, visualizar
en un mapa Leaflet y avanzar el estado de los pedidos asignados al repartidor. Backend
nuevo `app/Controllers/Api/RepartidorRastreoController.php` con 4 endpoints reales
(listar/buscar/historial/actualizar). Todos usan **sesión PHP o JWT** y una **verificación
estricta de propiedad**: el repartidor solo accede/actualiza pedidos cuyo `repartidor_id`
coincida (arregla un IDOR existente en `Api\RepartidorPedidosController::show`). Los
cambios de estado siguen `asignado→aceptado→recogido→en_camino→entregado` (con saltos
válidos) y escriben en `historial_pedidos_repartidor` (+ `historial_entregas` al entregar).
La línea de tiempo se **deriva de estado/datos reales**; el mapa muestra un aviso claro
cuando un pedido no tiene coordenadas (`latitud_destino`/`longitud_destino` null) en vez
de inventar coordenadas.

Endpoints `/api/repartidor/rastreo` (todos exigen sesión/JWT de repartidor activa):
- `GET  /api/repartidor/rastreo` — pedidos activos asignados al repartidor.
- `GET  /api/repartidor/rastreo/buscar?numero=ORD-...` — búsqueda por número; 403 si no
  le pertenece, 404 si no existe.
- `GET  /api/repartidor/rastreo/{id}/historial` — timeline real del pedido.
- `PUT  /api/repartidor/rastreo/{id}/estado` — transición validada (400 si es inválida,
  403 si no es su pedido).

---

## 10. Manejo de errores

- Las APIs usan `try/catch` y devuelven JSON con `success:false` y `message`.
- El controlador base (`App\Core\Controller`) incluye `json()` y `redirect()`.
- `Database` lanza excepciones PDO que, en algunos métodos, se capturan y se registran
  con `error_log()`.

> **Riesgo observado**: varios `catch` exponen el mensaje de la excepción PDO al usuario
> (por ej. `'Error al aprobar la solicitud: ' . $e->getMessage()`), lo que puede filtrar
> detalles de la consulta. Recomendado cambiar a mensajes genéricos y log internos
> (véase `docs/SEGURIDAD.md`, pendiente).

---

## 11. Servicios externos / microservicios

- **App repartidor (Node)**: `Repartidor/` — Express + Socket.IO en puerto 3000;
  usa `config.js`, `middleware/`, `routes/`, `services/` y una BD SQLite local
  (`repartidor.db`) además de compartir MySQL.

---

## 12. Unificación de identidad visual y activos del mapa (esta sesión)

Cambios **aditivos y no destructivos** para reforzar la sensación de "una sola app"
(CLIENTE / ADMIN / REPARTIDOR comparten la misma identidad visual), sin reemplazar
diseños existentes.

- **Tokens compartidos** — nuevo `public/assets/css/tokens.css` con los tokens de diseño
  canónicos en **ambos** juegos de nombres usados por el proyecto (inglés: `--primary`,
  `--success`, … para cliente/admin; español: `--azul-principal`, `--verde`, … para
  repartidor), mapeados al mismo valor. Se carga **primero** en `paginas/perfil.php`,
  `admin/panel.php`, `repartidor/dashboard.php` y `repartidor/perfil.php`. Los bloques
  `:root` específicos de cada pantalla siguen pudiendo sobrescribir después.
- **Valores divergentes unificados** — se corrigieron los valores que rompían la
  coherencia:
  - Azul oscuro: `#4A8AD4` y `#4A7FC9` → `#4A7FC4` (en `dashboard.php`, `repartidor/perfil.php`
    inline y `repartidor.css`/`repartidor-dashboard.css`). Canónico: `--primary-dark`/`--azul-oscuro`.
  - Verde de éxito: `#22C55E` → `#10b981`. Canónico: `--success`/`--verde`.
- **Activos de Leaflet centralizados** — nuevos parciales `app/Views/layouts/leaflet-css.php`
  y `leaflet-js.php` que son la fuente única del bloque CDN de Leaflet (mapa + routing +
  markercluster + polyline-decorator). Se incluyeron (`require ../layouts/…`) en
  `paginas/perfil.php`, `paginas/seguimiento.php`, `admin/panel.php` y
  `repartidor/dashboard.php`, **eliminando el bloque CDN duplicado en 5 archivos**.
- **Header/store por rol** — ya estaba implementado en `public/assets/js/bienvenida.js`
  (admin → `/admin`, repartidor → `/repartidor/dashboard`, cliente → `/perfil`, logout,
  ocultar "Ser Repartidor"). Se mantuvo sin cambios (reutilizar, no duplicar).
- `/perfil` sigue siendo el enrutador central por rol (`Auth::homeForRole()`):
  cliente → perfil, admin → `/admin`, repartidor → `/repartidor/dashboard`.

---

## 13. Eliminación de la facturación (esta sesión)

- Se **eliminó toda la funcionalidad de facturación** del proyecto:
  - Microservicio Python `facturacion/` (Flask) borrado por completo.
  - Controladores/modelos PHP (`FacturaController`, `Api\FacturasController`,
    `FacturaModel`), vista `paginas/factura.php`, email `emails/factura.html` y
    `factura.js`/`factura.css`.
- Se limpiaron las referencias en `CompraController`, `Cliente\PedidosController`
  (método `factura`), `Api\RepartidorRastreoController`, `EmailService` (tipo
  `factura`), `config/routes.php`, el dashboard de repartidor y `panel.css`.
- El checkout de `compra.js` mantiene el flujo de **guardar pedido** y muestra una
  **confirmación de pedido** (sin documento de factura ni generación de PDF).
- Migración de limpieza de BD: `database/999_drop_facturacion.sql` (elimina las tablas
  `facturas*` y `pedidos.factura_generada`).
