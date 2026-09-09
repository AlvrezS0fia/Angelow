# Estructura del proyecto ANGELOW

Documentación por carpeta. Está basada en el árbol **real** del proyecto.

---

## Raíz

```
Angelow/
```

Contiene la configuración global y los cuatro módulos coordinados.

Archivos relevantes:
- `angelow.sql` — referencia del esquema MySQL.
- `.env` / `.env.example` — variables de entorno (⚠️ `.env` NO debe subirse a Git).
- `composer.json` — autoload PSR-4 `App\` → `app/` (no se usa ninguna dependencia de Packagist).
- `Dockerfile` / `docker-compose.yml` / `.dockerignore` — contenedores.
- `Document/` — notas internas del equipo.

---

## `app/` — Núcleo PHP

**Propósito**: contiene toda la lógica de la aplicación PHP (MVC propio).

**Responsabilidad**: recibir solicitudes, autenticar/autorizar, orquestar datos y
renderizar vistas.

**Subcarpetas y su papel**:

| Carpeta | Contenido | Responsabilidad |
|---------|-----------|-----------------|
| `app/Core/` | `Router.php`, `Controller.php`, `Auth.php`, `Database.php`, `Model.php`, `Env.php`, `JWTHelper.php`, `Helpers.php` | Infraestructura base: enrutado, PDO, sesión/roles, JWT, entorno |
| `app/Controllers/` | Controladores por módulo: `AuthController`, `HomeController`, `PerfilController`, `CompraController`, `ContactoController`, `RepartidorController`, `DocumentoController`, `LogoutController`, `SeguimientoController`, ... | Coordinan solicitudes y respuestas |
| `app/Controllers/Admin/` | `DashboardController`, `PedidosController`, `UsuariosController`, `RepartidorController`, `InventarioController`, `ClientesController`, `SeguimientoControler` | Lógica del panel de administración |
| `app/Controllers/Api/` | Controladores REST para carrito, favoritos, productos, categorías, inventario, perfil, direcciones, tarjetas, repartidores | APIs JSON consumidas desde el front |
| `app/Controllers/Api/Admin*` | `AdminRepartidorController` | Operaciones de administración de repartidores (aprobar/rechazar/suspender/activar) |
| `app/Controllers/Cliente/` | `PerfilApiController`, `DireccionController`, `TarjetaController`, `PedidosController` | Datos del cliente autenticado |
| `app/Controllers/Procesar/` | `db.php`, `login.php`, `registrar.php`, `recuperar_password.php` | Scripts heredados (login/registro por formulario tradicional) |
| `app/Models/` | `UsuarioModel`, `PedidoModel`, `ProductoModel`, `CategoriaModel`, `CarritoModel`, `DireccionModel`, `TarjetaModel`, `Favorito` | Acceso a datos con PDO parametrizado |
| `app/Libraries/` | `EmailService.php` | Envío de correos (SMTP) |
| `app/Views/` | Vistas HTML/PHP organizadas por módulo | Renderización del frontend |

**Depende de**: `config/` (rutas y configuración), `.env`.
**Es utilizada por**: `public/index.php`.
**Archivos críticos**: `app/Core/Router.php`, `app/Core/Database.php`, `config/routes.php`.

**Qué pasaría si se modifica**: cualquier cambio en `Core/` afecta a todos los
controladores y vistas; los cambios en `config/routes.php` cambian el mapa completo
de URLs.

---

## `config/` — Configuración y rutas

**Propósito**: configurar la aplicación y declarar el mapa de rutas.

**Responsabilidad**: definir constantes (`APP_URL`, `DB_*`, `TIMEZONE`) y el arreglo
de rutas que alimenta al `Router`.

Archivos:
- `routes.php` — tabla central de rutas (GET/POST/DELETE/PUT + controlador:acción).
- `app.php` — constantes de aplicación.
- `database.php` — credenciales y charset.

**Depende de**: `.env`, `app/Core/Env.php`.
**Es utilizada por**: `public/index.php`, `app/Core/Router.php`.

---

## `database/` y `migrations/` — Migraciones SQL

**Propósito**: cambios incrementales al esquema de la base de datos.

Archivos:
- `database/001_add_asignado_status.sql`
- `database/999_drop_facturacion.sql`
- `migrations/add_direcciones_tarjetas.sql`

**Responsabilidad**: evolucionar la BD sin destruir datos existentes.

---

## `public/` — Raíz web

**Propósito**: único directorio expuesto por el servidor web.

**Responsabilidad**: front controller y estáticos (CSS/JS/imágenes).

Archivos:
- `index.php` — front controller (sesión, ENV, autoload, router).
- `.htaccess` — reglas Apache.
- `assets/` — `css/`, `js/`, `imagenes/`, `chatbot/`, `uploads/`.

**Es utilizada por**: el navegador (punto de entrada HTTP).

---

## `uploads/` — Archivos subidos

**Propósito**: almacenar archivos subidos por los usuarios.

**Contenido**: `uploads/documentos/` → documentos de repartidores (PDF/JPG/PNG).

**Responsabilidad**: persistencia de adjuntos. Requiere permisos de escritura y
protección para no ejecutar scripts (`.htaccess`).

---

## `storage/` — Almacenamiento interno

**Propósito**: escribir datos temporales del servidor.

**Contenido**: `storage/sessions/` → archivos de sesión de PHP.

> Se usa para evitar los errores intermitentes de permisos de `C:\xampp\tmp`.

---

## `facturacion/` — (Eliminado)

El microservicio de facturación Python (Flask) fue **eliminado** del proyecto.
Ver `docs/ARQUITECTURA.md` §13 para el detalle de la limpieza.

---

## `Repartidor/` — App de repartidor en tiempo real (Node.js)

**Propósito**: dashboard/entrega en tiempo real para repartidores.

**Estructura**:
- `server.js` — servidor Express + Socket.IO (puerto 3000).
- `config.js`, `middleware/`, `routes/`, `services/`, `public/`, `uploads/`.
- `package.json` — dependencias Node.
- `repartidor.db` (SQLite local) + acceso a MySQL compartida.

**Depende de**: Node.js y de `node_modules/` (en `.gitignore`).

---

## `Document/` — Notas internas

**Propósito**: registro de cambios y soluciones del equipo.

Archivos: `Cambios_generados.txt`, `CAMBIOS_REALIZADOS.txt`, `Soluciones.txt`.
**No** forma parte del código ejecutable.

---

## `docs/` — Documentación técnica

**Propósito**: documentación de arquitectura, seguridad, validaciones y estructura.

Archivos:
- `docs/ARQUITECTURA.md`
- `docs/ESTRUCTURA.md`
- `docs/VALIDACIONES.md`
- `docs/SEGURIDAD.md`

---

## Activos compartidos de identidad visual y del mapa (unificación)

Nuevos archivos para unificar la identidad visual y los activos de Leaflet entre las
áreas CLIENTE / ADMIN / REPARTIDOR (aditivos, no destructivos):

- `public/assets/css/tokens.css` — tokens de diseño canónicos en ambos juegos de nombres
  (inglés cliente/admin + español repartidor), mapeados al mismo valor. Cargado primero
  en `paginas/perfil.php`, `admin/panel.php`, `repartidor/dashboard.php`,
  `repartidor/perfil.php`.
- `app/Views/layouts/leaflet-css.php` — fuente única del bloque CDN de CSS de Leaflet.
- `app/Views/layouts/leaflet-js.php` — fuente única del bloque CDN de JS de Leaflet.

Vistas que incluyen los parciales Leaflet: `paginas/perfil.php`, `paginas/seguimiento.php`,
`admin/panel.php`, `repartidor/dashboard.php`.

