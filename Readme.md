# ANGELOW

Tienda de moda infantil desarrollada con PHP 8+, MySQL y JavaScript vanilla. Cuenta con un frontend para clientes (catálogo, carrito, checkout, seguimiento) y un panel de administración para gestión de productos, pedidos, clientes, repartidores e inventario.

## Tabla de Contenidos

1. [Características](#características)
2. [Tecnologías](#tecnologías)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Arquitectura](#arquitectura)
5. [Base de Datos](#base-de-datos)
6. [Rutas](#rutas)
7. [Controladores](#controladores)
8. [Modelos](#modelos)
9. [Vistas](#vistas)
10. [Assets (CSS/JS)](#assets-cssjs)
11. [Configuración](#configuración)
12. [Requisitos](#requisitos)
13. [Instalación](#instalación)
14. [Uso](#uso)
15. [Notas Técnicas](#notas-técnicas)

---

## Características

### Cliente
- Catálogo de productos con filtros por categoría, búsqueda y detalle de producto.
- Carrito de compras sincronizado con base de datos para usuarios e invitados.
- Checkout con generación de pedido, cálculo de envío y soporte para Mercado Pago / PSE.
- Seguimiento de pedidos en tiempo real.
- Sistema de favoritos sincronizado.
- Perfil de usuario con historial de pedidos y facturas.
- Autenticación por email y Google OAuth.
- Recuperación y cambio de contraseña.
- Página de contacto.
- Modo invitado con carrito persistente por `cart_session` cookie.
- Documentos legales: términos, políticas de privacidad, políticas de envío, políticas de devolución, preguntas frecuentes, guía de tallas.

### Administración
- Dashboard con métricas: total pedidos, pendientes, favoritos, ganancias, usuarios.
- Gestión de inventario (stock, ajustes, productos).
- Gestión de pedidos (estados, detalles).
- Gestión de clientes (búsqueda, cambio de rol).
- Gestión de repartidores.
- Mapa de pedidos con Leaflet para visualizar ubicaciones de entrega.

---

## Tecnologías

| Componente | Tecnología |
|------------|------------|
| Backend | PHP 8+ |
| Base de Datos | MySQL / MariaDB |
| Frontend | JavaScript vanilla, CSS |
| Email | PHPMailer (vía Composer) |
| Iconos | Font Awesome 6 (CDN) |
| Gráficos | Chart.js (CDN) |
| Mapas | Leaflet (CDN) |
| PDF | jsPDF + jsPDF-AutoTable (CDN) |
| Fuentes | Google Fonts (Inter) |
| Contenedores | Docker, Docker Compose |

---

## Estructura del Proyecto

```
Angelow/
├── .env                         # Variables de entorno
├── .htaccess                    # Rewrite rules Apache
├── angelow.sql                  # Script de base de datos
├── composer.json                # Dependencias PHP (PHPMailer)
├── composer.lock
├── config/
│   ├── app.php                  # Configuración de la aplicación
│   ├── database.php             # Configuración de base de datos
│   └── routes.php               # Rutas de la aplicación
├── docker-compose.yml           # Orquestación Docker
├── Dockerfile                   # Imagen Docker
├── docker/
│   ├── apache-vhost.conf
│   └── entrypoint.sh
├── Document/                    # Documentación adicional
├── public/
│   ├── index.php                # Punto de entrada
│   └── assets/
│       ├── css/                 # Hojas de estilo
│       ├── js/                  # Scripts frontend
│       ├── imagenes/            # Imágenes del sitio
│       │   ├── general/
│       │   ├── ninos/
│       │   ├── ninas/
│       │   └── bebe/
│       └── chatbot/
│           └── botpress.js
├── vendor/                      # Dependencias Composer
└── app/
    ├── Controllers/             # Controladores
    │   ├── Api/                 # API REST
    │   ├── Admin/               # Panel de administración
    │   ├── Cliente/             # API de cliente
    │   ├── Procesar/            # Controladores legacy (procesar)
    │   └── [Controladores principales]
    ├── Core/                    # Núcleo del framework
    ├── Libraries/               # Librerías (EmailService)
    ├── Models/                  # Modelos de datos
    └── Views/                   # Vistas (PHP)
        ├── layouts/
        ├── home/
        ├── auth/
        ├── admin/
        ├── paginas/
        ├── repartidor/          # Vistas del panel de repartidor (login y dashboard)
        ├── documentos/
        └── emails/
    ```
    
    ### Cambios en Estructura de Carpetas (Reorganización Repartidor)
    - **Vistas movidas**: `app/Views/auth/login-repartidor.php` → `app/Views/repartidor/login.php`
    - **Nuevo dashboard**: `app/Views/repartidor/dashboard.php` (reemplaza a `app/Views/paginas/repartidor.php`)
    - **CSS organizado**:
      - `public/assets/css/repartidor-login.css` - Estilos del login de repartidor
      - `public/assets/css/repartidor-dashboard.css` - Estilos del dashboard de repartidor
      - `public/assets/css/repartidor.css` - Estilos generales del panel repartidor
    - **Proyecto Node.js movido**: El proyecto Node.js anterior de `/Repartidor` fue respaldado en `Document/Repartidor_NodeJS_Backup/` y eliminado de la raíz para evitar conflictos.
    
    ### Flujo de Navegación Repartidor
    1. Usuario hace clic en icono de repartidor en la página principal → redirige a `/repartidor/login`
    2. Login exitoso → redirige a `/repartidor` (dashboard)
    3. Cierre de sesión → redirige a `/repartidor/login`

---

## Arquitectura

La aplicación usa un patrón MVC personalizado:

- **Router** (`app/Core/Router.php`): Enruta solicitudes a controladores basándose en método HTTP y ruta.
- **Controller** (`app/Core/Controller.php`): Clase base con métodos `view()`, `json()`, `redirect()`.
- **Database** (`app/Core/Database.php`): Singleton PDO para conexión a MySQL.
- **Env** (`app/Core/Env.php`): Carga variables desde `.env`.

---

## Base de Datos

El archivo `angelow.sql` contiene el schema completo. Las tablas principales incluyen:

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Usuarios del sistema (clientes, repartidores, administradores) |
| `categorias` | Categorías y subcategorías de productos |
| `productos` | Catálogo de productos |
| `variantes_producto` | Variantes por talla y color |
| `pedidos` | Pedidos realizados |
| `detalles_pedido` | Items de cada pedido |
| `carrito` | Carrito de compras (usuarios e invitados) |
| `favoritos` | Productos favoritos por usuario |
| `logs_actividad` | Registro de actividad de usuarios |

---

## Rutas

### Públicas
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/` | HomeController | index |
| GET | `/auth/login` | AuthController | showLogin |
| POST | `/auth/login` | AuthController | login |
| POST | `/auth/register` | AuthController | register |
| POST | `/auth/forgot-password` | AuthController | forgotPassword |
| POST | `/auth/google` | AuthController | googleLogin |
| GET | `/auth/logout` | AuthController | logout |
| GET | `/contactenos` | ContactoController | index |
| POST | `/contacto/enviar` | ContactoController | enviar |
| GET | `/seguimiento` | SeguimientoController | index |
| GET | `/auth/change-password` | AuthController | showChangePassword |
| POST | `/auth/change-password` | AuthController | changePassword |
| GET | `/auth/reset-password` | AuthController | showResetForm |
| POST | `/auth/reset-password` | AuthController | resetPassword |

### Cliente (requieren sesión)
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/perfil` | PerfilController | index |
| GET | `/compra` | CompraController | index |
| POST | `/procesar-compra` | CompraController | procesar |
| GET | `/factura` | FacturaController | index |
| GET | `/documentos/Pedidos_envios` | DocumentoController | pedidosEnvios |
| GET | `/documentos/Politicas_devolucion` | DocumentoController | politicasDevolucion |
| GET | `/documentos/Preguntas` | DocumentoController | preguntas |
| GET | `/documentos/Guia_Tallas` | DocumentoController | guiaTallas |
| GET | `/documentos/Terminos` | DocumentoController | terminos |
| GET | `/documentos/Politicas_Priv` | DocumentoController | politicasPrivacidad |
| GET | `/documentos/Politicas_Env` | DocumentoController | politicasEnv |

### Administración (requieren rol administrador)
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/admin` | Admin\\DashboardController | index |
| GET | `/admin/pedidos` | Admin\\PedidosController | index |
| GET | `/admin/usuarios` | Admin\\UsuariosController | index |
| GET | `/admin/repartidores` | Admin\\RepartidorController | index |
| GET | `/admin/inventario` | Admin\\InventarioController | index |

### API
| Método | Ruta | Controlador | Acción |
|--------|------|-------------|--------|
| GET | `/api/carrito` | Api\\CarritoController | index |
| POST | `/api/carrito/agregar` | Api\\CarritoController | agregar |
| POST | `/api/carrito/actualizar` | Api\\CarritoController | actualizar |
| DELETE | `/api/carrito/eliminar` | Api\\CarritoController | eliminar |
| POST | `/api/carrito/sincronizar` | Api\\CarritoController | sincronizar |
| POST | `/api/carrito/vaciar` | Api\\CarritoController | vaciar |
| GET | `/api/favoritos` | Api\\FavoritoController | index |
| POST | `/api/favoritos/agregar` | Api\\FavoritoController | agregar |
| DELETE | `/api/favoritos/eliminar` | Api\\FavoritoController | eliminar |
| GET | `/api/productos` | Api\\ProductsController | index |
| POST | `/api/productos` | Api\\ProductsController | store |
| PUT | `/api/productos` | Api\\ProductsController | update |
| DELETE | `/api/productos` | Api\\ProductsController | destroy |
| GET | `/api/categorias` | Api\\CategoriesController | index |
| POST | `/api/categorias` | Api\\CategoriesController | store |
| PUT | `/api/categorias` | Api\\CategoriesController | update |
| DELETE | `/api/categorias` | Api\\CategoriesController | destroy |
| GET | `/api/inventario` | Api\\StockController | index |
| POST | `/api/inventario/update` | Api\\StockController | update |
| POST | `/api/inventario/ajustar` | Api\\StockController | ajustar |
| GET | `/api/clientes` | Admin\\ClientesController | index |
| POST | `/api/clientes/buscar` | Admin\\ClientesController | buscar |
| POST | `/api/clientes/rol` | Admin\\ClientesController | cambiarRol |
| GET | `/api/pedidos` | Admin\\PedidosController | obtenerPedidos |
| POST | `/api/pedidos/estado` | Admin\\PedidosController | updateStatus |
| GET | `/api/mis-pedidos` | Cliente\\PedidosController | index |
| GET | `/api/mis-pedidos/:id` | Cliente\\PedidosController | detalle |
| POST | `/api/mis-pedidos/:id/cancelar` | Cliente\\PedidosController | cancelar |
| GET | `/api/mis-pedidos/:id/factura` | Cliente\\PedidosController | factura |

---

## Controladores

### Principales
- **HomeController** (`app/Controllers/HomeController.php`) - Renderiza la página de inicio.
- **AuthController** (`app/Controllers/AuthController.php`) - Login, registro, Google OAuth, recuperación/cambio de contraseña, logout.
- **PerfilController** (`app/Controllers/PerfilController.php`) - Perfil del cliente (redirige a admin si es administrador).
- **CompraController** (`app/Controllers/CompraController.php`) - Checkout y procesamiento de pedidos.
- **FacturaController** (`app/Controllers/FacturaController.php`) - Vista de factura del pedido.
- **SeguimientoController** (`app/Controllers/SeguimientoController.php`) - Pública, muestra seguimiento de pedidos.
- **ContactoController** (`app/Controllers/ContactoController.php`) - Formulario y envío de contacto.
- **DocumentoController** (`app/Controllers/DocumentoController.php`) - Páginas legales y de soporte.
- **LogoutController** (`app/Controllers/LogoutController.php`) - Cierra sesión y redirige a login.

### Administración
- **Admin\DashboardController** - Dashboard con estadísticas.
- **Admin\PedidosController** - Listado y actualización de estados de pedidos.
- **Admin\ClientesController** - Listado, búsqueda y cambio de rol de clientes.
- **Admin\RepartidorController** - Gestión de repartidores.
- **Admin\InventarioController** - Gestión de inventario.
- **Admin\SeguimientoControler** - (Vacío, placeholder).

### Repartidor
- **RepartidorAuthController** (`app/Controllers/RepartidorAuthController.php`) - Login, logout y obtención de datos del repartidor autenticado. Renderiza `repartidor/login.php`.
- **RepartidorController** (`app/Controllers/RepartidorController.php`) - Dashboard del repartidor. Renderiza `repartidor/dashboard.php`. Protegido por rol `repartidor`.

### Cliente (API)
- **Cliente\PedidosController** - Listado, detalle, cancelación y factura de pedidos del cliente autenticado.

### API
- **Api\CarritoController** - CRUD y sincronización del carrito.
- **Api\FavoritoController** - Gestión de favoritos.
- **Api\StockController** - Consulta y ajuste de stock/inventario.
- **Api\ProductsController** - CRUD de productos.
- **Api\CategoriesController** - CRUD de categorías.

### Legacy (Procesar)
- **Procesar/registrar.php** - Procesamiento de registro legacy.
- **Procesar/login.php** - Procesamiento de login legacy.
- **Procesar/recuperar_password.php** - Recuperación de contraseña legacy.
- **Procesar/db.php** - Conexión PDO legacy.
- **ProcesarGoogleController.php** - Procesamiento de login con Google legacy.
- **DashboardController.php** - Dashboard legacy (HTML directo).

---

## Modelos

| Modelo | Archivo | Descripción |
|---------|---------|-------------|
| UsuarioModel | `app/Models/UsuarioModel.php` | CRUD de usuarios, búsqueda, reset tokens, cambio de rol. |
| PedidoModel | `app/Models/PedidoModel.php` | Creación de pedidos, consultas, actualización de estado, generación de número de pedido (ORD-YYYY-NNNN). |
| CarritoModel | `app/Models/CarritoModel.php` | Gestión de carrito por usuario o sesión, merge de carrito invitado. |
| Favorito | `app/Models/Favorito.php` | Gestión de favoritos por usuario. |

---

## Vistas

### Layouts
- `layouts/main.php` - Layout principal (actualmente vacío).

### Páginas Públicas/Cliente
- `home/bienvenida.php` - Página de inicio con carrusel.
- `auth/login.php` - Formulario de inicio de sesión.
- `auth/register.php` - Formulario de registro.
- `auth/olvide-password.php` - Solicitud de recuperación.
- `auth/reset-password.php` - Formulario de nueva contraseña.
- `auth/change-password.php` - Cambio de contraseña (logueado).
- `paginas/compra.php` - Checkout.
- `paginas/perfil.php` - Perfil del cliente.
- `paginas/seguimiento.php` - Seguimiento de pedidos.
- `paginas/factura.php` - Factura del pedido.
- `paginas/contactenos.php` - Contacto.

### Repartidor
- `repartidor/login.php` - Login de repartidor (diseño acorde al sitio principal).
- `repartidor/dashboard.php` - Dashboard de repartidor (estadísticas, pedidos, filtros, historial).

### Administración
- `admin/panel.php` - Panel de administración con dashboard, mapa Leaflet, gestión completa.
- `admin/inventario.php` - Gestión de inventario.
- `admin/repartidor.php` - Gestión de repartidores.

### Documentos
- `documentos/Terminos.php`
- `documentos/Politicas_Priv.php`
- `documentos/Politicas_Env.php`
- `documentos/Politicas_devolucion.php`
- `documentos/Preguntas.php`
- `documentos/Pedidos_envios.php`
- `documentos/Guia_Tallas.php`

---

## Assets (CSS/JS)

### CSS
| Archivo | Página |
|---------|--------|
| `bienvenida.css` | Página de inicio |
| `carrusel.css` | Carrusel de ofertas |
| `login.css` | Login/Registro |
| `compra.css` | Checkout |
| `seguimiento.css` | Seguimiento de pedidos |
| `perfil.css` | Perfil cliente |
| `factura.css` | Factura |
| `contactenos.css` | Contacto |
| `repartidor.css` | Estilos generales del panel repartidor |
| `repartidor-login.css` | Estilos del login de repartidor |
| `repartidor-dashboard.css` | Estilos del dashboard de repartidor |
| `panel.css` | Panel de administración |
| `inventario.css` | Inventario admin |
| `preguntas.css` | Preguntas frecuentes |
| `guia_tallas.css` | Guía de tallas |
| `pedidos_envios.css` | Pedidos y envíos |
| `politicas_devolucion.css` | Políticas de devolución |
| `politicas_env.css` | Políticas de envío |
| `politicas_priv.css` | Políticas de privacidad |
| `terminos.css` | Términos y condiciones |
| `tokens.css` | Tokens de identidad visual compartidos (cliente/admin/repartidor) — se carga primero en perfil/panel/dashboard |

### JavaScript
| Archivo | Funcionalidad |
|---------|---------------|
| `bienvenida.js` | Lógica de la página de inicio (ofertas dinámicas, carrusel). |
| `carrusel.js` | Carrusel de productos/ofertas. |
| `login.js` | Validación y envío de login/registro. |
| `compra.js` | Checkout, cálculo de envío, envío de pedido. |
| `seguimiento.js` | Seguimiento de pedidos en tiempo real. |
| `perfil.js` | Gestión del perfil de usuario. |
| `factura.js` | Visualización y descarga de factura. |
| `contactenos.js` | Formulario de contacto. |
| `repartidor.js` | Funcionalidad de repartidor. |
| `panel.js` | Lógica del panel de administración (dashboard, mapa, pedidos). |
| `inventario.js` | Gestión de inventario. |
| `preguntas.js` | Preguntas frecuentes. |
| `guia_tallas.js` | Guía de tallas. |
| `pedidos_envios.js` | Información de pedidos y envíos. |
| `politicas_env.js` | Políticas de envío. |
| `politicas_priv.js` | Políticas de privacidad. |
| `terminos.js` | Términos y condiciones. |
| `chatbot/botpress.js` | Integración con chatbot Botpress. |

---

## Configuración

### Variables de Entorno (`.env`)
| Variable | Descripción | Default |
|----------|-------------|---------|
| `DB_HOST` | Host de la base de datos | `localhost` / `db` (Docker) |
| `DB_NAME` | Nombre de la base de datos | `angelow_db` |
| `DB_USER` | Usuario de la base de datos | `root` |
| `DB_PASS` | Contraseña de la base de datos | `` |
| `DB_CHARSET` | Charset de la base de datos | `utf8mb4` |
| `APP_NAME` | Nombre de la aplicación | `Angelow` |
| `APP_URL` | URL base de la aplicación | `http://localhost/Angelow/public` |
| `TIMEZONE` | Zona horaria | `America/Bogota` |
| `SMTP_HOST` | Host SMTP | `smtp.gmail.com` |
| `SMTP_USERNAME` | Usuario SMTP | - |
| `SMTP_PASSWORD` | Contraseña SMTP | - |
| `SMTP_PORT` | Puerto SMTP | `587` |
| `SMTP_FROM_EMAIL` | Email remitente | - |
| `SMTP_FROM_NAME` | Nombre remitente | `Angelow` |
| `GOOGLE_CLIENT_ID` | Google OAuth Client ID | - |
| `GOOGLE_CLIENT_SECRET` | Google OAuth Client Secret | - |
| `JWT_SECRET` | Clave de firma HS256 de los tokens de repartidor | `angelow_jwt_secret_key_2026` |

> ⚠️ **`JWT_SECRET`**: usa una clave aleatoria segura (ej: `php -r "echo base64_encode(random_bytes(32));"`).
> Guarda el `.env` **fuera de Git** (ya está en `.gitignore`). Existe una plantilla en
> `.env.example` con todos los valores documentados y sin secretos reales.

### Archivos de Configuración
- `config/app.php` - Constantes de la aplicación (APP_NAME, APP_URL, TIMEZONE).
- `config/database.php` - Constantes de conexión a base de datos.
- `config/routes.php` - Definición de todas las rutas.
- `.env.example` - Plantilla de variables de entorno (sin secretos reales).
- `docs/` - Documentación técnica (arquitectura, estructura, seguridad, validaciones).

---

## Requisitos

- PHP 8.0+
- MySQL 8.0+ / MariaDB
- Composer (para PHPMailer)
- Apache con mod_rewrite (o servidor compatible)
- Extensiones PHP: PDO, PDO_MySQL, json, mbstring

---

## Instalación

### Opción 1: Docker (Recomendado)

1. Clonar el repositorio:
   ```bash
   git clone <repo-url> Angelow
   cd Angelow
   ```

2. Levantar los servicios:
   ```bash
   docker-compose up -d --build
   ```

3. Acceder a `http://localhost:8080`.

4. La base de datos se inicializa automáticamente desde `angelow.sql`.

### Opción 2: Instalación Local (XAMPP/WAMP)

1. Clonar o copiar el proyecto en el directorio del servidor web (ej: `C:\xampp\htdocs\Angelow`).

2. Importar el archivo `angelow.sql` en la base de datos MySQL.

3. Configurar la conexión a la base de datos en `.env` o en `config/database.php`.

4. Instalar dependencias PHP:
   ```bash
   composer install
   ```

5. Configurar el servidor web para que apunte a `public/` como documento raíz, o usar el `.htaccess` incluido.

6. Asegurarse de que la carpeta `public/assets/` tenga permisos de lectura.

---

## Uso

### Acceso al Sitio
- **Tienda**: Ingresar a la raíz del sitio para ver el catálogo y página de inicio.
- **Registro / Login**: Disponibles en el menú de usuario.
- **Panel Admin**: Ruta `/admin` (acceso para rol administrador).
- **Panel Repartidor**: Ruta `/repartidor/login` (acceso para rol repartidor). Una vez autenticado, redirige a `/repartidor`.
- **Perfil Cliente**: Ruta `/perfil`.
- **Seguimiento**: Ruta `/seguimiento`.
- **Contacto**: Ruta `/contactenos`.

### Roles de Usuario
| Rol | Acceso |
|-----|--------|
| `cliente` | Tienda, perfil, pedidos, seguimiento. |
| `repartidor` | Panel de repartidor. |
| `administrador` | Panel de administración completo. |

---

## Notas Técnicas

- El carrito usa `localStorage` como respaldo si la API no está disponible.
- Las imágenes de productos se cargan desde `public/assets/imagenes/`.
- El panel usa Font Awesome 6 para la iconografía.
- El mapa del panel utiliza Leaflet.
- El modo invitado utiliza una cookie `cart_session` para persistir el carrito sin sesión.
- Los emails transaccionales se envían mediante PHPMailer con soporte para imágenes incrustadas (CID).
- El framework es propio (sin Laravel, Symfony, etc.) con autoload PSR-4 simple.
- El archivo `public/index.php` es el punto de entrada único que despacha todas las rutas.

---

## Seguridad

Auditoría y estado por capa. Detalle completo en [docs/SEGURIDAD.md](docs/SEGURIDAD.md).

| Capa | Estado |
|------|--------|
| Hash de contraseñas (bcrypt vía `password_hash`) | ✅ IMPLEMENTADO |
| SQL Injection | ✅ IMPLEMENTADO (PDO con sentencias preparadas) |
| Autorización por rol (backend por método) | ✅ IMPLEMENTADO |
| JWT (`JWT_SECRET` real en `.env`) | ✅ IMPLEMENTADO |
| Subida de documentos (MIME real, límite, whitelist) | ✅ IMPLEMENTADO |
| XSS en `json_encode` / atributos | ✅ IMPLEMENTADO |
| CSRF | ❌ PENDIENTE |
| Endpoint autenticado para documentos | ❌ PENDIENTE (recomendado) |
| Cookies `SameSite`/`HttpOnly`/`Secure` | ❌ PENDIENTE |
| Expiración de sesión | ❌ PENDIENTE |

---

## Pedidos, Facturación y Repartidores

### Flujo de pedidos
```
Carrito → /procesar-compra (CompraController) → pedidos + detalle
       → FacturaController / microservicio Flask → facturas
       → Admin cambia estado → Repartidor entrega → entregado
```
- Creación/validación: `app/Controllers/CompraController.php`.
- Consulta/estado: `Admin\PedidosController`, `Api\RepartidorPedidosController`.

### Flujo de repartidores
```
Registro (RepartidorAuthController) → solicitud 'pendiente'
       → Admin aprueba (AdminRepartidorController) → usuario 'activo'
       → Dashboard repartidor (login JWT) → entregas
       → Rechazo / Suspensión → 'inactivo' / 'suspendido'
```

### Facturación
- Tablas `facturas`, `facturas_detalle`, `facturas_historial` (migración `002_add_facturas_table.sql`).
- Microservicio Flask en `facturacion/` (puerto 5000, requiere su propio `.env` y
  `requirements.txt`). Genera PDF (fpdf2) y envía email (smtplib) con la **misma** base
  de datos de la app.
- **Autenticación del microservicio**: los endpoints de `facturacion/routes/factura_routes.py`
  exigen `Authorization: Bearer <FACTURA_API_SECRET>` (`require_api_key`). El secreto se
  define **solo en `facturacion/.env`** (generado 64 hex) y se lee en `facturacion/config.py`.
  Fail-closed si no está definido.

### Identidad visual y mapa unificados
- `public/assets/css/tokens.css`: tokens de diseño canónicos en ambos juegos de nombres
  (inglés cliente/admin + español repartidor). Se carga primero en `perfil.php`,
  `panel.php`, `dashboard.php` y `perfil.php` (repartidor).
- Partials `app/Views/layouts/leaflet-css.php` y `leaflet-js.php`: fuente única del bloque
  CDN de Leaflet, reutilizados en perfil/seguimiento/panel/dashboard (antes duplicado en 5 archivos).
- Header de la tienda ya es condicional por rol (`bienvenida.js`): admin → `/admin`,
  repartidor → `/repartidor`, cliente → `/perfil`.

---

## Tiempo real

- **App Repartidor (Node.js Express + Socket.IO)**: `Repartidor/server.js` (puerto 3000)
  permite entregas/seguimiento en tiempo real.
- **Seguimiento en el sitio**: `SeguimientoController` + Leaflet/mapeo.
- **Estado actual del seguimiento**: el mapa de cliente/admin usa una simulación en el
  frontend; el backend real (`/api/mis-pedidos/:id/seguimiento`, `seguimiento_tiempo_real`)
  aún no se consume en esas vistas. El repartidor sí usa datos reales (módulo "Rastrear
  Pedido"). Hoy la BD no tiene coordenadas de destino (NULL) ni filas en
  `seguimiento_tiempo_real`.

---

## Manejo de errores

- Las APIs devuelven JSON con `success`/`error`/`message`.
- `error_log()` para diagnóstico interno en las API.
- El front controller devuelve `404 - Página no encontrada` para rutas inexistentes.

> ⚠️ Algunos controladores exponen el mensaje de excepción PDO al usuario; recomendado
> sustituirlo por mensajes genéricos (ver docs/SEGURIDAD.md).

---

## Pruebas

- No existe un framework de pruebas configurado (PHPUnit/usería).
- Verificación manual:
  ```bash
  # Comprobar sintaxis de los controladores modificados
  php -l app/Controllers/Api/RepartidorDocumentosController.php
  # Comprobar código de respuesta de rutas
  curl -s -o /dev/null -w "%{http_code}" http://localhost/Angelow/login
  ```
- Después de tocar la BD, revisar `database/` (migraciones) y `angelow.sql`.

---

## Mantenimiento

- **Núcleo** (`app/Core/`): cambios impactan todas las rutas → probar después de modificar.
- **Rutas** (`config/routes.php`): añadir/editar aquí cualquier nueva URL.
- **Base de datos**: usar migraciones incrementales; no dejar de sincronizar `angelow.sql`.
- **Sesiones**: el directorio de sesión es `storage/sessions` (creado automáticamente).
- **Microservicios**: `facturacion/` (Flask) y `Repartidor/` (Node) son aplicaciones
  independientes; requieren arrancarse por su cuenta.
- **Documentación**: ver `docs/ARQUITECTURA.md`, `docs/ESTRUCTURA.md`,
  `docs/VALIDACIONES.md`, `docs/SEGURIDAD.md`.

---

## Referencia rápida de documentación

| Documento | Contenido |
|-----------|-----------|
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, flujo de datos, BD, roles, pedidos, facturación, repartidores |
| [docs/ESTRUCTURA.md](docs/ESTRUCTURA.md) | Propósito de cada carpeta y archivo |
| [docs/VALIDACIONES.md](docs/VALIDACIONES.md) | Formularios y sus validaciones frontend/backend |
| [docs/SEGURIDAD.md](docs/SEGURIDAD.md) | Auditoría de seguridad, riesgos y correcciones |
