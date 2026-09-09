# ANGELOW

Tienda de moda infantil desarrollada con PHP 8+, MySQL y JavaScript vanilla. Cuenta con un frontend para clientes (catálogo, carrito, checkout, seguimiento de pedidos, perfil) y paneles para administradores (productos, pedidos, clientes, repartidores, inventario) y repartidores (entregas, rastreo). Incluye un **microservicio Python (FastAPI)** para el registro de repartidores con sincronización automática a la base de datos principal.

## Tabla de Contenidos

1. [Características](#características)
2. [Tecnologías](#tecnologías)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Arquitectura](#arquitectura)
5. [Capas de Seguridad (modelo ISO-OSI)](#capas-de-seguridad-modelo-iso-osi)
6. [Base de Datos](#base-de-datos)
7. [Rutas](#rutas)
8. [Controladores](#controladores)
9. [Modelos](#modelos)
10. [Vistas](#vistas)
11. [Assets (CSS/JS)](#assets-cssjs)
12. [Menú de usuario por rol](#menú-de-usuario-por-rol)
13. [Configuración](#configuración)
14. [Requisitos](#requisitos)
15. [Instalación](#instalación)
16. [Uso](#uso)
17. [Notas Técnicas](#notas-técnicas)
18. [Mantenimiento](#mantenimiento)
19. [Referencia de documentación](#referencia-de-documentación)

---

## Características

### Cliente
- Catálogo de productos con filtros por categoría, búsqueda y detalle de producto.
- Carrito de compras sincronizado con base de datos para usuarios e invitados (cookie `cart_session`).
- Checkout con generación de pedido, cálculo de envío y soporte para Mercado Pago / PSE.
- Seguimiento de pedidos en tiempo real.
- Sistema de favoritos sincronizado.
- Perfil de usuario con historial de pedidos, direcciones y tarjetas.
- Autenticación por email y Google OAuth.
- Recuperación y cambio de contraseña.
- Página de contacto y documentos legales (términos, políticas, preguntas frecuentes, guía de tallas).

### Administración
- Dashboard con métricas: total pedidos, pendientes, favoritos, ganancias, usuarios.
- Gestión de inventario (stock, ajustes, productos).
- Gestión de pedidos (estados, detalles) y exportación a PDF.
- Gestión de clientes (búsqueda, cambio de rol).
- Gestión de repartidores (solicitudes, aprobación, suspensión, documentos).
- Mapa de pedidos con Leaflet para visualizar ubicaciones de entrega.

### Repartidor
- Login y dashboard propios (sesión PHP + token JWT para las APIs).
- Listado de pedidos asignados y cambio de estado con transiciones validadas.
- Módulo "Rastrear Pedido": búsqueda por número, mapa Leaflet e historial real.
- Subida de documentos (cédula, licencia, SOAT, etc.) con validación de MIME real.
- **Formulario de registro multi-paso** (microservicio FastAPI):
  - 3 pasos: Datos Personales → Vehículo → Documentos.
  - Validación frontend en tiempo real.
  - Sincronización automática con MySQL (usuarios, solicitudes, documentos, vehículos).
  - Modal de login integrado con conexión CORS al endpoint `/repartidor/login`.

---

## Tecnologías

| Componente | Tecnología |
|------------|------------|
| Backend | PHP 8+ (MVC propio, sin framework) |
| Base de Datos | MySQL / MariaDB |
| Frontend | JavaScript vanilla, CSS |
| Email | PHPMailer (vía Composer) |
| Iconos | Font Awesome 6.4.0 (CDN) |
| Gráficos | Chart.js (CDN) |
| Mapas | Leaflet (CDN) |
| PDF | jsPDF + jsPDF-AutoTable (CDN) |
| Tokens | JWT HS256 (`App\Core\JWTHelper`) |
| Fuentes | Google Fonts (Inter / Quicksand) |
| Microservicio | Python 3.10+, FastAPI, Uvicorn, SQLite (registro repartidores) |
| Contenedores | Docker, Docker Compose |
| Tiempo real | Node.js Express + Socket.IO (`Repartidor/`) |

---

## Estructura del Proyecto

```
Angelow/
├── .env                         # Variables de entorno (NO se sube a Git)
├── .env.example                 # Plantilla de variables (sin secretos reales)
├── .htaccess                    # Rewrite rules Apache
├── angelow.sql                  # Script de la base de datos (referencia)
├── composer.json                # Autoload PSR-4 (App\ → app/) + PHPMailer
├── docker-compose.yml           # Orquestación Docker
├── Dockerfile                   # Imagen Docker
├── Document/                    # Notas de cambios y soluciones del equipo
├── Repartidor/                  # App Node.js (Express + Socket.IO) — tiempo real de entregas
├── repartidor-service/          # Microservicio Python (FastAPI) — registro de repartidores
│   ├── main.py                  # Punto de entrada FastAPI
│   ├── config.py                # Configuración del microservicio
│   ├── database.py              # Conexión SQLite (sincronización con MySQL)
│   ├── models/                  # Modelos SQLAlchemy (solicitud, vehículo, documentos)
│   ├── patterns/                # Patrones GOF (Builder, Facade, Adapter, etc.)
│   ├── routes/                  # Rutas FastAPI (registro)
│   ├── schemas/                 # Pydantic schemas
│   ├── services/                # Lógica de negocio
│   ├── repositories/            # Acceso a datos
│   ├── static/                  # CSS, JS, fuentes, imágenes del formulario
│   └── templates/               # HTML del formulario multi-paso
├── config/
│   ├── app.php                  # Constantes de la aplicación
│   ├── database.php             # Constantes de conexión a BD
│   └── routes.php               # Tabla central de todas las rutas (web + API)
├── database/                    # Migraciones SQL incrementales
│   └── 999_drop_facturacion.sql # Limpieza de la funcionalidad de facturación eliminada
├── app/                         # Núcleo PHP (MVC)
│   ├── Controllers/
│   │   ├── Api/                 # APIs REST (carrito, repartidor, admin, etc.)
│   │   ├── Admin/               # Panel de administración
│   │   ├── Cliente/             # APIs del cliente (pedidos, perfil, direcciones, tarjetas)
│   │   ├── Procesar/            # Controladores legacy
│   │   └── [Controladores principales]
│   ├── Core/                    # Núcleo (Router, Controller, Database, Auth, JWTHelper, ...)
│   ├── Helpers/                 # Helpers PHP
│   ├── Libraries/               # EmailService (PHPMailer)
│   ├── Models/                  # Modelos de datos (PDO con sentencias preparadas)
│   └── Views/                   # Vistas PHP
│       ├── layouts/             # Partials compartidos (Leaflet, etc.)
│       ├── home/                # Página de inicio (bienvenida)
│       ├── auth/                # Login, registro, recuperación de contraseña
│       ├── admin/               # Panel admin (panel, inventario, repartidor)
│       ├── repartidor/          # Login y dashboard del repartidor
│       ├── paginas/             # Perfil, compra, seguimiento, contacto
│       ├── documentos/          # Páginas legales
│       ├── cargador/            # Página de precarga (carga fuentes, iconos y estilos)
│       └── emails/              # Plantillas de correo transaccional
├── public/
│   ├── index.php                # Front controller (punto de entrada único)
│   └── assets/
│       ├── css/                 # Hojas de estilo (tokens.css = identidad compartida)
│       ├── js/                  # Scripts frontend (bienvenida.js, panel.js, ...)
│       ├── chatbot/botpress.js  # Integración de chatbot
│       └── imagenes/            # Imágenes del sitio (general, ninos, ninas, bebe)
├── storage/sessions/            # Sesiones PHP (writable)
├── uploads/                     # Archivos subidos (documentos de repartidores)
└── vendor/                      # Dependencias Composer
```

> **Nota**: la funcionalidad de **facturación** (microservicio Python `facturacion/`,
> controladores/modelos PHP y tablas `facturas*`) fue **eliminada** del proyecto. Solo
> permanece el enlace de menú **"Facturas"** para el cliente (ver [Menú de usuario](#menú-de-usuario-por-rol)).

---

## Arquitectura

### Modelo de capas (MVC propietario)

ANGELOW usa un **MVC propio** (no Laravel ni Symfony), con autoload PSR-4 simple
(`App\` → `app/`). El flujo de una petición es:

```
Navegador (HTTP / AJAX)
   │
   ▼
public/index.php  →  front controller
   │  capa 5 ISO-OSI (Sesión): session_start + storage/sessions + cookie cart_session
   │  carga .env, config, autoloader, helpers
   ▼
App\Core\Router (config/routes.php)
   │  match método + ruta (soporta parámetros {id}) → controlador
   ▼
Controller (App\Controllers\*)
   │  capa 7 ISO-OSI (Aplicación): valida entrada, autentica y autoriza por rol
   ▼
Model (App\Models\* → App\Core\Database::query)
   │  capa 7 ISO-OSI (Aplicación): SQL parametrizado (PDO prepared statements)
   ▼
MySQL (angelow_db)
```

### Capas que SÍ existen
- `Controller → Model → Database (PDO) → MySQL`.
- `App\Core\Auth`: helper de sesión y autorización por rol.
- `App\Core\JWTHelper`: tokens JWT (HS256) para las APIs de repartidor.
- `App\Core\Controller`: métodos base `view()`, `json()`, `redirect()`.

### Capas que NO existen (no se inventan en la documentación)
- **Servicio/Repositorio/DAO** separado del Model: la lógica de negocio vive en los
  controladores.
- **Middleware global** de rutas: la autorización se repite método a método.
- **ORM**: se usa PDO con consultas preparadas a mano.

### Flujo por rol
- **Cliente**: tienda → carrito → checkout → pedido → seguimiento.
- **Administrador**: `/admin` → dashboard, pedidos, clientes, repartidores, inventario.
- **Repartidor**: `/repartidor/login` → dashboard → entregas y rastreo.
- La puerta de entrada del sitio es `/cargador` (precarga de fuentes, Font Awesome y
  estilos), que carga `home/bienvenida.php`.

### Microservicio de Registro de Repartidores (`repartidor-service/`)

Microservicio independiente construido con **FastAPI** que maneja el formulario multi-paso
de registro de repartidores. Implementa patrones de diseño GOF (Builder, Facade, Adapter,
Singleton, Factory, Strategy, Chain of Responsibility, Observer, Template Method, Repository).

**Flujo de datos:**
```
Frontend (registro.html → script.js)
   │  POST /api/repartidor/registro (FormData multipart)
   ▼
FastAPI (main.py → routes/repartidor.py)
   │  Builder → Facade → Repository → SQLite
   ▼
SQLite (repartidores.db)  ──sync──▶  MySQL (angelow_db)
                                       │
                                       ▼
                           Angelow adapter → INSERT/UPDATE
                           (solicitudes_repartidores, usuarios,
                            documentos, vehiculos_repartidores)
```

**Puerto:** 8000 (`http://127.0.0.1:8000`)

**Sincronización automática:** Cuando se registra un repartidor, el adapter
(`patterns/structural/angelow_adapter.py`) inserta directamente en MySQL:
- `usuarios` — datos personales del repartidor
- `solicitudes_repartidores` — solicitud con tarjeta, SOAT, licencia, vencimientos
- `documentos` — archivos subidos (SOAT, tarjeta, licencia)
- `vehiculos_repartidores` — datos del vehículo

**CORS habilitado:** Para requests del microservicio (puerto 8000) a ANGELOW (puerto 80),
se agregaron headers CORS en `RepartidorAuthController::login()` y handler OPTIONS
preflight en `public/index.php`.

---

## Capas de Seguridad (modelo ISO-OSI)

Se mapea cada control de seguridad al **modelo de referencia ISO-OSI (ISO 7498)** según la
capa en la que actúa. Los comentarios en el código usan la notación
`// CAPA N ISO-OSI (Nombre): ...`.

| Capa ISO-OSI | Nombre | Qué protege en ANGELOW | Dónde está en el código |
|--------------|--------|------------------------|--------------------------|
| **7** | Aplicación | Autenticación (login/registro), autorización por rol, validación de entrada, política de contraseñas, **SQL Injection**, subida de archivos (whitelist de tipos), JWT emisión/verificación | `app/Controllers/AuthController.php`, `app/Controllers/Api/RepartidorDocumentosController.php`, `app/Core/Auth.php`, `app/Core/Database.php`, `app/Core/JWTHelper.php` |
| **6** | Presentación | Escape y codificación de salida (**XSS**), comparación de firmas en tiempo constante (`hash_equals`), JSON seguro dentro de `<script>` | `app/Views/home/bienvenida.php`, `app/Views/repartidor/dashboard.php`, `app/Views/paginas/perfil.php`, `app/Core/JWTHelper.php` |
| **5** | Sesión | Gestión de sesiones PHP, directorio de sesiones, cookies (`cart_session`), autorización basada en sesión | `public/index.php`, `app/Core/Auth.php`, `config/database.php` |
| **4** | Transporte | Cifrado en tránsito: SMTP **STARTTLS** (correo). HTTPS depende del servidor/Apache (pendiente forzarlo) | `app/Libraries/EmailService.php`, `.htaccess` |
| **3** | Red | Firewall y rate limiting en el borde. **NO IMPLEMENTADO** para login/APIs (recomendación pendiente) | — |

### Estado de los controles

| Control | Capa ISO-OSI | Estado |
|---------|--------------|--------|
| Hash de contraseñas (bcrypt `password_hash`/`password_verify`) | 7 | ✅ IMPLEMENTADO |
| Autorización por rol (backend, método a método) | 7 + 5 | ✅ IMPLEMENTADO |
| SQL Injection (PDO con sentencias preparadas) | 7 | ✅ IMPLEMENTADO |
| Subida de documentos (MIME real `finfo`, límite, whitelist) | 7 | ✅ IMPLEMENTADO |
| XSS en `json_encode` / atributos (`JSON_HEX_*`, `htmlspecialchars`) | 6 | ✅ IMPLEMENTADO |
| JWT HS256 (`JWT_SECRET` en `.env`) | 7 | ✅ IMPLEMENTADO |
| Comparación de firmas en tiempo constante | 6 | ✅ IMPLEMENTADO (`hash_equals`) |
| SMTP cifrado (STARTTLS) | 4 | ✅ IMPLEMENTADO |
| CSRF (tokens en formularios/fetch) | 7 | ❌ PENDIENTE |
| Cookies `SameSite`/`HttpOnly`/`Secure` | 5 | ❌ PENDIENTE |
| Expiración y renovación de sesiones | 5 | ❌ PENDIENTE |
| Endpoint autenticado para servir documentos | 7 | ❌ PENDIENTE |
| Rate limiting en login/APIs | 3 | ❌ PENDIENTE |
| HTTPS forzado | 4 | ❌ PENDIENTE (depende del servidor) |

Detalle y auditoría completa: [`docs/SEGURIDAD.md`](docs/SEGURIDAD.md).

---

## Base de Datos

- Motor: **MySQL** `angelow_db`, charset `utf8mb4`, conexión con **PDO**.
- Esquema de referencia: `angelow.sql`.
- Migraciones incrementales en `database/` y `migrations/`.

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Usuarios (cliente / repartidor / administrador) y su estado |
| `categorias` | Categorías y subcategorías de productos |
| `productos` | Catálogo de productos |
| `variantes_producto` | Variantes por talla y color |
| `pedidos` | Pedidos y sus estados de entrega |
| `detalles_pedido` | Items de cada pedido |
| `carrito` | Carrito (usuarios e invitados) |
| `favoritos` | Productos favoritos por usuario |
| `direcciones` / `tarjetas` | Datos de envío y medios de pago del cliente |
| `solicitudes_repartidores` / `documentos` | Flujo de aprobación y documentos de repartidores |
| `vehiculos_repartidores` / `permisos_repartidores` / `historial_repartidores` | Gestión de repartidores |
| `historial_pedidos_repartidor` / `historial_entregas` | Timeline real de entregas |
| `seguimiento_tiempo_real` | Ubicaciones en vivo (hoy sin datos) |
| `notificaciones` | Notificaciones por tipo (pedido, entrega, sistema, seguridad, ...) |
| `logs_actividad` | Registro de actividad de usuarios |

### Columnas agregadas (sincronización microservicio)

| Tabla | Columna | Tipo | Descripción |
|-------|---------|------|-------------|
| `solicitudes_repartidores` | `tarjeta_propiedad` | VARCHAR(50) | Número de tarjeta de propiedad del vehículo |
| `solicitudes_repartidores` | `soat_vencimiento` | DATE | Fecha de vencimiento del SOAT |
| `solicitudes_repartidores` | `tecnomecanica_vencimiento` | DATE | Fecha de vencimiento del tecnomecánico |
| `solicitudes_repartidores` | `vencimiento_licencia` | DATE | Fecha de vencimiento de la licencia de conducción |

> Estas columnas se llenan automáticamente al sincronizar desde el microservicio.

---

## Rutas

Tabla central: `config/routes.php`.

### Públicas
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/` | HomeController | index (redirige a `/cargador`) |
| GET | `/cargador` | CargadorController | index |
| GET | `/auth/login` | AuthController | showLogin |
| POST | `/auth/login` | AuthController | login |
| POST | `/auth/register` | AuthController | register |
| POST | `/auth/forgot-password` | AuthController | forgotPassword |
| POST | `/auth/google` | AuthController | googleLogin |
| GET | `/auth/logout` | AuthController | logout |
| GET | `/auth/change-password` | AuthController | showChangePassword / changePassword |
| GET | `/auth/reset-password` | AuthController | showResetForm / resetPassword |
| GET | `/contactenos` | ContactoController | index |
| POST | `/contacto/enviar` | ContactoController | enviar |
| GET | `/seguimiento` | SeguimientoController | index |
| GET | `/documentos/*` | DocumentoController | páginas legales |

### Cliente (requieren sesión)
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/perfil` | PerfilController | index |
| GET | `/compra` | CompraController | index |
| POST | `/procesar-compra` | CompraController | procesar |

### Administración (requieren rol administrador)
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/admin` | Admin\DashboardController | index |
| GET | `/admin/pedidos` | Admin\PedidosController | index |
| GET | `/admin/usuarios` | Admin\UsuariosController | index |
| GET | `/admin/repartidores` | Admin\RepartidorController | index |
| GET | `/admin/inventario` | Admin\InventarioController | index |

### Repartidor
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/repartidor` `/repartidor/dashboard` | RepartidorController | index |
| GET | `/repartidor/perfil` | RepartidorController | perfil |
| GET | `/repartidor/registro` | RepartidorController | registro |
| POST | `/repartidor/registro` | RepartidorAuthController | registro |
| GET/POST | `/repartidor/login` | RepartidorAuthController | showLogin / login |
| GET | `/repartidor/logout` | RepartidorAuthController | logout |
| POST | `/repartidor/refresh-token` | RepartidorAuthController | refreshToken |

### Microservicio (FastAPI, puerto 8000)
| Método | Ruta | Función | Acción |
|--------|------|---------|--------|
| GET | `/` | `formulario()` | Formulario multi-paso de registro |
| POST | `/api/repartidor/registro` | `registro()` | Registro completo de repartidor |
| GET | `/health` | `health()` | Health check del microservicio |

### CORS (OPTIONS preflight)

### APIs principales
| Ámbito | Rutas | Controlador |
|--------|-------|-------------|
| Carrito | `/api/carrito*` | Api\CarritoController |
| Favoritos | `/api/favoritos*` | Api\FavoritoController |
| Productos / Categorías | `/api/productos*`, `/api/categorias*` | Api\ProductsController, Api\CategoriesController |
| Inventario | `/api/inventario*` | Api\StockController |
| Pedidos cliente | `/api/mis-pedidos*` | Cliente\PedidosController |
| Perfil / Direcciones / Tarjetas | `/api/perfil*`, `/api/direcciones*`, `/api/tarjetas*` | Cliente\*ApiController |
| Repartidor (auth) | `/api/repartidor/auth/*` | Api\RepartidorAuthController |
| Repartidor (pedidos, rastreo, dashboard, clientes, documentos) | `/api/repartidor/*` | Api\Repartidor*Controller |
| Admin (repartidores, dashboard) | `/api/admin/*` | Api\AdminRepartidorController |
| Clientes (admin) | `/api/clientes*` | Admin\ClientesController |

---

## Controladores

### Principales
- **HomeController** — Página de inicio (redirige a `/cargador`).
- **CargadorController** — Precarga de fuentes, iconos y estilos de la home.
- **AuthController** — Login, registro, Google OAuth, recuperación/cambio de contraseña, logout.
- **PerfilController** — Perfil del cliente / enrutador por rol (`Auth::homeForRole()`).
- **CompraController** — Checkout y procesamiento de pedidos.
- **SeguimientoController** — Seguimiento público de pedidos.
- **ContactoController** — Formulario y envío de contacto.
- **DocumentoController** — Páginas legales.

### Administración
- **Admin\DashboardController**, **Admin\PedidosController**, **Admin\ClientesController**,
  **Admin\RepartidorController**, **Admin\InventarioController**.

### Repartidor
- **RepartidorAuthController** — Login/registro/logout/refresh-token.
- **RepartidorController** — Dashboard y perfil (protegidos por rol `repartidor`).

### API / Cliente
- **Api\\CarritoController**, **Api\FavoritoController**, **Api\StockController**,
  **Api\ProductsController**, **Api\CategoriesController**.
- **Cliente\PedidosController**, **Cliente\PerfilApiController**,
  **Cliente\DireccionController**, **Cliente\TarjetaController**.
- **Api\RepartidorAuthController**, **Api\RepartidorPedidosController**,
  **Api\RepartidorRastreoController**, **Api\RepartidorSeguimientoController**,
  **Api\RepartidorDashboardController**, **Api\RepartidorClientesController**,
  **Api\RepartidorDocumentosController**.
- **Api\AdminRepartidorController** — Solicitudes, aprobación, suspensión, documentos.

### Legacy (Procesar)
- **Procesar/login.php**, **Procesar/registrar.php**, **Procesar/recuperar_password.php**,
  **Procesar/db.php**, **ProcesarGoogleController.php**, **DashboardController.php**.

---

## Modelos

Todos usan **PDO con sentencias preparadas** (capa 7 ISO-OSI, anti SQL Injection).

| Modelo | Archivo | Descripción |
|---------|---------|-------------|
| UsuarioModel | `app/Models/UsuarioModel.php` | CRUD de usuarios, búsqueda, reset tokens, cambio de rol |
| PedidoModel | `app/Models/PedidoModel.php` | Creación de pedidos, estados, número ORD-YYYY-NNNN |
| CarritoModel | `app/Models/CarritoModel.php` | Carrito por usuario o sesión, merge de carrito invitado |
| Favorito | `app/Models/Favorito.php` | Gestión de favoritos |

---

## Vistas

### Páginas públicas / cliente
- `home/bienvenida.php` — Inicio (cargada por el cargador).
- `auth/*.php` — Login, registro, recuperación y cambio de contraseña.
- `paginas/perfil.php` — Perfil del cliente (sidebar por secciones).
- `paginas/compra.php` — Checkout.
- `paginas/seguimiento.php` — Seguimiento de pedidos.
- `paginas/contactenos.php` — Contacto.

### Administración
- `admin/panel.php` — Dashboard con métricas, mapa Leaflet y gestión completa.
- `admin/inventario.php` — Gestión de inventario (exporta PDF).
- `admin/repartidor.php` — Gestión de repartidores.

### Repartidor
- `repartidor/login.php` — Login del repartidor.
- `repartidor/dashboard.php` — Dashboard (pedidos, "Rastrear Pedido", historial).
- `repartidor/perfil.php` — Perfil del repartidor.
- `repartidor/registro_repartidor.php` — Registro multi-paso del repartidor (legacy).
- `repartidor-service/templates/registro.html` — Formulario multi-paso de registro (FastAPI).
  - Incluye modal de login integrado (`loginModalOverlay`) con conexión al endpoint
    `/repartidor/login` de ANGELOW (CORS habilitado).

### Documentos y emails
- `documentos/` — Términos, políticas, preguntas, guía de tallas.
- `emails/` — Plantillas de bienvenida, recuperación y cambio de contraseña.
- `layouts/` — Partials compartidos (Leaflet CSS/JS).

---

## Assets (CSS/JS)

### ANGELOW (PHP)
- **CSS**: `tokens.css` (identidad visual compartida cliente/admin/repartidor), `bienvenida.css`,
  `carrusel.css`, `login.css`, `compra.css`, `seguimiento.css`, `perfil.css`, `contactenos.css`,
  `panel.css`, `inventario.css`, `repartidor.css`, `repartidor-login.css`,
  `repartidor-dashboard.css` y los CSS de las páginas legales.
- **JS**: `bienvenida.js` (home + menú de usuario por rol), `carrusel.js`, `login.js`,
  `compra.js`, `seguimiento.js`, `seguimiento-perfil.js`, `perfil.js`, `contactenos.js`,
  `panel.js`, `inventario.js`, `repartidor.js`, `chatbot/botpress.js`.

### Microservicio (FastAPI)
- **CSS**: `repartidor-service/static/css/style.css` — Estilos del formulario multi-paso y modal de login.
- **JS**: `repartidor-service/static/js/script.js` — Validación por pasos, envío FormData, modal de login.
- **Fuentes**: Inter (Google Fonts).
- **Iconos**: Font Awesome 6.4.0 (local en `static/vendor/font-awesome/`).

---

## Menú de usuario por rol

El menú desplegable del icono de usuario (header) se construye dinámicamente en
`public/assets/js/bienvenida.js` → `updateUserUI()`, según `currentUser.rol`:

| Rol | Ítems del menú |
|-----|----------------|
| Cliente | Mi cuenta · Mis Favoritos · **Facturas** · Cerrar sesión |
| Repartidor | Panel repartidor · Cerrar sesión |
| Administrador | Administración · Inventario · Cerrar sesión |
| Invitado | Mi perfil · Mis Favoritos · Ser Repartidor |

> **Facturas (CLIENTE)**: enlace agregado entre "Mis Favoritos" y "Cerrar sesión", con el
> icono de Font Awesome `fa-file-invoice` (misma clase `dropdown-item` y mismo estilo que
> el resto). Actualmente apunta a `href="#"` a la espera de la URL definitiva. La
> funcionalidad de facturación se eliminó del proyecto; este menú queda listo para cuando
> se vuelva a habilitar.

---

## Configuración

### Variables de Entorno (`.env`)
| Variable | Descripción | Default |
|----------|-------------|---------|
| `DB_HOST` | Host de la base de datos | `localhost` / `db` (Docker) |
| `DB_NAME` | Nombre de la base de datos | `angelow_db` |
| `DB_USER` / `DB_PASS` | Usuario / contraseña de BD | `root` / — |
| `DB_CHARSET` | Charset de la base de datos | `utf8mb4` |
| `APP_NAME` / `APP_URL` / `TIMEZONE` | Aplicación | `Angelow` / `http://localhost/Angelow/public` / `America/Bogota` |
| `SMTP_HOST` / `SMTP_USERNAME` / `SMTP_PASSWORD` / `SMTP_PORT` | Correo (STARTTLS) | `smtp.gmail.com` / — / — / `587` |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google OAuth | — |
| `JWT_SECRET` | Firma HS256 de tokens de repartidor | — (generar aleatorio) |

> ⚠️ **`JWT_SECRET`**: usa una clave aleatoria segura (ej: `php -r "echo base64_encode(random_bytes(32));"`).
> `.env` está en `.gitignore` y existe la plantilla `.env.example`.

---

## Requisitos

### ANGELOW (PHP)
- PHP 8.0+
- MySQL 8.0+ / MariaDB
- Composer (PHPMailer)
- Apache con mod_rewrite (o servidor equivalente)
- Extensiones PHP: PDO, PDO_MySQL, json, mbstring
- Node.js (solo para la app de repartidor en tiempo real, opcional)

### Microservicio (Python)
- Python 3.10+
- FastAPI, Uvicorn, SQLAlchemy, Pydantic
- SQLite (para datos temporales del registro)
- Conexión a MySQL `angelow_db` (para sincronización)

---

## Instalación

### Opción 1: Docker (recomendado)
```bash
git clone <repo-url> Angelow
cd Angelow
docker-compose up -d --build
```
Acceder a `http://localhost:8080`. La BD se inicializa desde `angelow.sql`.

### Opción 2: XAMPP/WAMP
1. Copiar el proyecto a `C:\xampp\htdocs\Angelow`.
2. Importar `angelow.sql` en MySQL.
3. Configurar `.env` (o `config/database.php`).
4. `composer install`.
5. Apuntar el docroot a `public/` o usar el `.htaccess` incluido.

### Microservicio de Registro (adicional)
```bash
cd repartidor-service
pip install -r requirements.txt
python main.py
```
Acceder a `http://127.0.0.1:8000` para el formulario de registro.

---

## Uso

- **Tienda**: raíz del sitio (rutas `/` y `/cargador`).
- **Registro / Login**: menú de usuario del header.
- **Panel Admin**: `/admin` (rol administrador).
- **Panel Repartidor**: `/repartidor/login` → `/repartidor` (rol repartidor).
- **Registro Repartidor (microservicio)**: `http://127.0.0.1:8000` (FastAPI).
- **Perfil Cliente**: `/perfil`.
- **Seguimiento**: `/seguimiento`.
- **Contacto**: `/contactenos`.

### Roles
| Rol | Acceso |
|-----|--------|
| `cliente` | Tienda, perfil, pedidos, seguimiento, favoritos, facturas (enlace) |
| `repartidor` | Panel de repartidor y entregas |
| `administrador` | Panel de administración completo |

---

## Notas Técnicas

### Panel Admin (`public/assets/js/panel.js`)
- **Nombres y apellidos**: Las tarjetas de solicitud ahora muestran `s.nombres` y `s.apellidos`
  en lugar de campos undefined.
- **Datos del vehículo**: Se muestra "Tarjeta de propiedad", "Vence SOAT" y "Vence licencia"
  con formato de fecha legible (`fmtFechaIso()`).
- **Modales estilizados**: `aprobarSolicitud()` y `rechazarSolicitud()` usan modales inline
  (`.modal-overlay`) con textarea para observaciones/motivo, en lugar de `confirm()`/`prompt()`
  nativos del navegador.
- **Suspender/Activar**: Se mantiene `confirm()` nativo por simplicidad.

### Microservicio de Registro
- **Formulario multi-paso**: 3 pasos (Datos Personales → Vehículo → Documentos) con validación
  frontend en tiempo real.
- **Modal de login**: Botón "Iniciar Sesión" en el sidebar que abre un modal conectado al
  endpoint `/repartidor/login` de ANGELOW (CORS habilitado).
- **Sincronización automática**: Al registrar un repartidor, se inserta en MySQL directamente
  desde el microservicio (adaptador `angelow_adapter.py`).
- **Documentos**: Los archivos subidos (SOAT, tarjeta, licencia) se almacenan tanto en SQLite
  (referencia) como en MySQL (binario).

- El carrito usa `localStorage` como respaldo si la API no está disponible.
- El modo invitado usa la cookie `cart_session` para persistir el carrito.
- Las imágenes de productos se cargan desde `public/assets/imagenes/`.
- Font Awesome 6.4.0, Chart.js, Leaflet y jsPDF se cargan por CDN.
- El framework es propio; `public/index.php` es el punto de entrada único.
- El mapa de seguimiento cliente/admin usa una simulación en el frontend; los datos reales
  (`/api/mis-pedidos/:id/seguimiento`) aún no se consumen en esas vistas. El repartidor sí
  usa datos reales (módulo "Rastrear Pedido").
- La identidad visual compartida vive en `public/assets/css/tokens.css` y los CDN de
  Leaflet están centralizados en `app/Views/layouts/leaflet-*.php`.

---

## Mantenimiento

- **Núcleo** (`app/Core/`): cambios impactan todas las rutas → probar después de modificar.
- **Rutas** (`config/routes.php`): añadir/editar aquí cualquier nueva URL.
- **Base de datos**: usar migraciones incrementales; mantener sincronizado `angelow.sql`.
- **Sesiones**: `storage/sessions` (se crea automáticamente).
- **App de repartidor (Node)**: `Repartidor/` es una aplicación independiente (puerto 3000).
- **Microservicio Python** (`repartidor-service/`): FastAPI en puerto 8000 con `reload=True`.
  - Si se modifica `angelow_adapter.py`, reiniciar el microservicio.
  - Los schemas de MySQL se actualizan en `angelow.sql` y `database/`.
- **Anotaciones de seguridad**: el código lleva comentarios `// CAPA N ISO-OSI (...)`
  señalando el control de seguridad en cada punto (ver sección
  [Capas de Seguridad](#capas-de-seguridad-modelo-iso-osi)).

---

## Referencia de documentación

| Documento | Contenido |
|-----------|-----------|
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, flujo de datos, BD, roles, pedidos, repartidores |
| [docs/ESTRUCTURA.md](docs/ESTRUCTURA.md) | Propósito de cada carpeta y archivo |
| [docs/VALIDACIONES.md](docs/VALIDACIONES.md) | Formularios y sus validaciones frontend/backend |
| [docs/SEGURIDAD.md](docs/SEGURIDAD.md) | Auditoría de seguridad, riesgos y correcciones |
| [repartidor-service/README.md](repartidor-service/README.md) | Documentación del microservicio Python (FastAPI) |