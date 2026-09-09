# DOCUMENTACIÓN TÉCNICA ANGELOW

> **Proyecto:** ANGELOW — Tienda en línea de ropa infantil (Colombia).
> **Ruta del código:** `C:\xampp\htdocs\Angelow`
> **Tecnologías:** PHP 8 (MVC propio) + Apache (XAMPP) + MySQL + Servidor Python FastAPI (microservicio de repartidores).
> **Alcance del análisis:** análisis 100 % basado en el código real del repositorio. Cuando un mecanismo no existe en el código analizado, se indica explícitamente con la nota `NO SE ENCONTRÓ ESTE MECANISMO EN EL CÓDIGO ANALIZADO.`
> **Referencias de archivos:** se usa el formato `ruta/archivo.php:línea`.

---

## 1. PUNTO DE ENTRADA (BOOTSTRAP)

### 1.1 Flujo general del front controller

Toda la aplicación web entra por `public/index.php` (186 líneas). Su comentario de cabecera resume el flujo (`public/index.php:3-24`):

> "Flujo: navegador -> .htaccess (Apache) -> index.php -> [1] sesion PHP -> [2] .env -> [3] config -> [4] autoload -> [5] cookie de invitado -> [6] Router::dispatch() -> controlador -> vista/JSON."

### 1.2 `.htaccess` (`public/.htaccess`)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```
Todo lo que no sea un archivo o directorio real se enruta a `index.php`.

### 1.3 Pasos de `public/index.php`

| Paso | Línea(s) | Descripción |
|---|---|---|
| 1. Carpeta de sesión | 29-35 | Crea `storage/sessions` y la asigna con `session_save_path()` si es escribible. |
| 2. Blindaje de sesión | 42-54 | `ini_set('session.use_strict_mode','1')` (anti fijación), `use_only_cookies=1`, cookie con `httponly=true`, `samesite=Lax`, `secure` si hay HTTPS; `session_start()` activo. |
| 3. Expiración por inactividad | 60-69 | Si `last_activity` supera 12 horas → `$_SESSION = []; session_regenerate_id(true);`; si no, renueva la marca. |
| 4. Cabeceras de seguridad | 73-78 | `X-Content-Type-Options: nosniff`; `X-Frame-Options: DENY`; `Referrer-Policy: strict-origin-when-cross-origin`; `Permissions-Policy`. |
| 5. Cargar `.env` | 83 | `require app/Core/Env.php` |
| 6. Cargar config | 88-89 | `config/app.php` y `config/database.php` |
| 7. Autoload PSR-4 | 94-102 | Registra `App\` → `app/`. |
| 8. Helpers | 106-108 | Carga por `glob()` todos los `app/Helpers/*.php`. |
| 9. Cookie de invitado | 116-125 | Si no hay sesión ni cookie: `$session_id = bin2hex(random_bytes(16));` (32 hex) y `setcookie('cart_session', ...)` por 30 días, `httponly=false`, `samesite=Lax`. |
| 10. Chequeo CSRF Origin/Referer | 139-181 | Ver §15. |
| 11. Despacho | 185-186 | `$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);` |

**Clave de acceso puntual:**
- `public/index.php:42-54` — endurecimiento de sesión.
- `public/index.php:116-125` — cookie de invitado para carrito anónimo.
- `public/index.php:139-181` — protección CSRF por Origin/Referer.

### 1.4 Enrutador (`app/Core/Router.php`)

- `add(method, path, controller, action)` — líneas 16-26: convierte `{param}` en regex con nombre `(?P<param>[^/]+)` y la ancla `^...$`.
- `dispatch()` — líneas 40-75: toma el path, quita prefijos `/Angelow` y `/public`, normaliza `/`, busca coincidencia de método + regex, instancia `App\Controllers\...`, extrae los parámetros nombrados y llama con `call_user_func_array`. Si no coincide: `http_response_code(404); echo "404 - Pagina no encontrada";` (líneas 73-74).

### 1.5 Capa de datos (`app/Core/Database.php`)

- Clase `Database` (Singleton, líneas 11-12): conexión PDO con DSN `mysql:host=DB_HOST;dbname=DB_NAME;charset=DB_CHARSET` (línea 22).
- `ERRMODE_EXCEPTION` + `DEFAULT_FETCH_MODE = FETCH_ASSOC` (líneas 24-25).
- `getInstance()` / `getConnection()` (líneas 34-43).
- **Método estático `query(string $sql, array $params = [])`** (líneas 46-58): prepara y ejecuta. Su comentario es la política de seguridad de datos: *"SIEMPRE sentencias preparadas, lo que neutraliza la inyeccion SQL (los valores jamas se concatenan en el SQL)."*
- Fallo de conexión → `error_log` + `die('Error de conexion con la base de datos...')` (líneas 26-29).

---

## 2. MENÚ DE NAVEGACIÓN / VISTA PRINCIPAL

### 2.1 Controlador principal

**`app/Controllers/HomeController.php`** (`index()`, líneas 7-14):
```php
public function index() {
    if (!isset($_GET['from'])) {
        $this->redirect(APP_URL . '/cargador');   // línea 8-9
    }
    $user = $_SESSION['user'] ?? null;            // línea 13
    $this->view('home.bienvenida', ['user' => $user]); // línea 14
}
```
- **No** carga productos ni categorías en el servidor: el contenido (productos, categorías, ofertas, carrusel) se carga **por JavaScript** (`public/assets/js/bienvenida.js` y `carrusel.js`), referenciados en `app/Views/home/bienvenida.php:107-109`.

**`app/Controllers/CargadorController.php`** (`index()`, líneas 7-9): pantalla "splash" animada que luego redirige a `APP_URL . '/?from=loader'` (`app/Views/cargador/index.php:574,583`). No requiere autenticación.

### 2.2 Elementos del menú en `app/Views/home/bienvenida.php`

| Elemento | Línea | Destino |
|---|---|---|
| Logo (funciona como Inicio) | 46-52 | `APP_URL /` |
| Barra de búsqueda de productos | 53-57 | JS client-side |
| Botón dorado **"Ser Repartidor"** | 59 | `MICROSERVICE_URL` (microservicio Python) |
| Ícono **Contacto** | 60 | `APP_URL /contactenos` |
| Ícono **Carrito** (`#cartCount`) | 61 | overlay JS |
| Ícono **Favoritos** (`#favHeaderBadge`) | 62 | overlay JS |
| Desplegable **Perfil** | 63 | "Mi perfil" (`/perfil`) y "Mis Favoritos" (`#favBadge`) |

El menú lateral del **panel de cliente** (`app/Views/paginas/perfil.php:78-109`) contiene: Perfil, Direcciones, Mis Facturas, Rastrea tu pedido, Tarjetas de crédito, Mis Favoritos, Mi Carrito. El menú **admin** incluye la opción "Categorías" (`app/Views/admin/panel.php:76`). El **panel de repartidor** incluye "Tienda", "Rastrear Pedido" y "Cerrar Sesión" (`app/Views/repartidor/dashboard.php:744-776`, y `app/Views/paginas/repartidor.php:138`).

> **Nota:** no existe una barra clásica "Inicio / Catálogo / Categorías / Productos / Mis Pedidos / Administrar / Cerrar sesión" en la vista de inicio; esas opciones viven en las vistas de perfil, admin y repartidor.

---

## 3. AUTENTICACIÓN

### 3.1 Módulo de sesión: `app/Core/Auth.php`

| Método | Líneas | Comportamiento |
|---|---|---|
| `user()` | 10-13 | `$_SESSION['user'] ?? null` (array `{id, email, nombre, rol, estado}`). |
| `id()` | 19-22 | `(int) $_SESSION['user']['id'] ?? null` (se usa como `WHERE usuario_id = ?`). |
| `check()` | 27-30 | `!empty($_SESSION['user']['id'])`. |
| `rol()` | 35-38 | `'cliente' \| 'repartidor' \| 'administrador'`. |
| `estado()` | 43-46 | `'pendiente' \| 'activo' \| 'inactivo' \| 'suspendido'`. |
| `isCliente()` | 51-54 | `rol() === 'cliente'`. |
| `isRepartidor()` | 56-59 | `rol() === 'repartidor'`. |
| `isAdmin()` | 61-64 | `rol() === 'administrador'`. |
| `isRepartidorAprobado()` | 68-71 | `isRepartidor() && estado() === 'activo'`. |
| `homeForRole()` | 73-90 | `/admin`, `/repartidor/dashboard` o `/`. |

### 3.2 Login local — `AuthController::login()` (`app/Controllers/AuthController.php:25-89`)

1. Lee JSON de `php://input` (`email`, `password`, `remember`).
2. Campos vacíos → `{'success': false, 'message': 'Completa todos los campos'}`.
3. **Rate limit** (líneas 36-40): clave `login:{IP}:{email}`, `RateLimiter::tooMany($rlKey, 5, 900)` → HTTP **429** "Demasiados intentos. Espera 15 minutos."
4. `findByEmail()` y **`password_verify($password, $user['password_hash'])`** (línea 44) → "Credenciales incorrectas".
5. `RateLimiter::clear($rlKey)` al éxito (línea 49).
6. Guarda `$_SESSION['user']` con `id, email, nombre, apellido, rol, estado` (líneas 52-59) y `$_SESSION['user_id']` (línea 60).
7. `session_regenerate_id(true)` (líneas 61-63).
8. Redirección por rol: admin → `/admin`; repartidor → `/repartidor/dashboard` (con respuesta especial `pending` si `estado === 'pendiente'`, línea 71); si no → `/`.
9. Respuesta JSON `{success, message, redirect, nombre, rol}` (líneas 82-88).

**El checkbox "Recordar mi cuenta" (`remember`):** se envía desde el frontend (`public/assets/js/login.js:279`) y existe la columna `remember_token` en la base (`angelow.sql`, tabla `usuarios`), pero **`AuthController::login()` no procesa el parámetro `remember`** (no lo persiste ni crea cookie de recuerdo). → NO SE ENCONTRÓ ESTE MECANISMO EN EL CÓDIGO ANALIZADO (la sesión de PHP es la única persistencia de login).

### 3.3 Login con Google — `AuthController::googleLogin()` (líneas 181-259)

- Flujo: `public/assets/js/login.js:450-473` `handleCredentialResponse()` decodifica el `credential` de Google **en el navegador** (parte media del JWT de Google, `atob`), serializa `{email, name, sub}` en un input oculto y hace `POST /auth/google` (campo `user_data`).
- En PHP (`googleLogin`, líneas 182-213): si no existe el correo, registra automáticamente un usuario con contraseña aleatoria (`password_hash(bin2hex(random_bytes(16)))`), `email_verificado=true`, `acepta_terminos=true`, `estado='activo'`; crea sesión y redirige a `/admin?welcome=1` o `/?welcome=1`.
- **Hallazgo de seguridad:** `googleLogin()` **no verifica la firma del id_token de Google** (no hay validación de firma, ni `nonce`, ni `aud`). El backend confía en el JSON que envía el navegador. → Es una debilidad documentada del sistema actual.

### 3.4 Rate limiter — `app/Core/RateLimiter.php`

- Archivos JSON ofuscados `storage/logs/rl_<md5(clave)>.json` (líneas 37-51).
- `tooMany(string $key, int $max, int $windowSeconds): bool` (línea 37): ventana móvil (si pasó `windowSeconds` desde `first`, reinicia).
- `clear(string $key)` (líneas 55-61): elimina el archivo.

### 3.5 Cambio / recuperación de contraseña

| Flujo | Controlador / líneas | Detalle |
|---|---|---|
| `forgotPassword()` | `AuthController:262-313` | Rate limit `forgot:{IP}:{email}` **3/3600** (línea 272); token `bin2hex(random_bytes(32))` (64 hex) (línea 283); expira en **+1 hora** (línea 284); `setResetToken()`; correo `recuperacion` vía `EmailService`. |
| `showResetForm()` | `AuthController:316-324` | Valida token con `findByResetToken($token)` (que en SQL exige `reset_expiry > NOW()`, `UsuarioModel:116-120`). |
| `resetPassword()` | `AuthController:327-358` | Exige token válido y las **mismas reglas de fortaleza** que el registro (líneas 338-342); `updatePassword()` + `clearResetToken()`; correo `cambio-contrasena`. |
| `showChangePassword()` | `AuthController:368-374` | Requiere sesión. |
| `changePassword()` | `AuthController:377-426` | Requiere sesión; verifica `password_verify` de la contraseña actual (línea 403); aplica reglas fuertes; `updatePassword()`; correo `cambio-contrasena`. |

### 3.6 Cierre de sesión — `logout()` (`AuthController:361-365`)

```php
$_SESSION = [];
session_destroy();
$this->redirect('/auth/login');
```

---

## 4. ROLES DE USUARIO

La columna `rol` de `usuarios` es `ENUM('cliente','repartidor','administrador','vendedor')` con valor por defecto `'cliente'` (`angelow.sql:53`). La columna `estado` es `ENUM('pendiente','activo','inactivo','suspendido','eliminado')` (`angelow.sql:99`).

### 4.1 Matriz de acceso por rol (verificado en controladores)

| Rol | Guardas usadas | Dónde |
|---|---|---|
| **cliente** | `isset($_SESSION['user'])`, `Auth::check()`, `Auth::id()` | `CompraController::index/procesar`, `Cliente\*`, `PerfilController` |
| **repartidor** | `$_SESSION['user']['rol'] === 'repartidor'` o JWT Bearer + `rol=repartidor` en sesión | `RepartidorController` (web), `Api\Repartidor*` (dual JWT/sesión) |
| **administrador** | `$_SESSION['user']['rol'] === 'administrador'`, `Auth::isAdmin()` | `Admin\*`, `Api\StockController`, `Api\AdminRepartidorController` |
| **vendedor** | Existe en el ENUM de BD | NO SE ENCONTRÓ USO EN EL CÓDIGO PHP ANALIZADO |

### 4.2 Redirecciones según rol

- `AuthController::login` (líneas 66-80), vista `app/Views/auth/login.php:4-14` y `Auth::homeForRole()` (líneas 73-90): admin → `/admin`, repartidor → `/repartidor/dashboard`, cliente → `/`.
- `PerfilController::index`: cliente ve su perfil; admin va a `/admin`; repartidor a `/repartidor/dashboard`.
- `CompraController::index`: admin → `/admin`; repartidor → `/repartidor/dashboard`; cliente → vista de compra.

---

## 5. REGISTRO DE USUARIO (CLIENTE)

### 5.1 Validaciones en el frontend — `public/assets/js/login.js`

`handleEmailRegister()` (líneas 309-384):
- Campos + términos requeridos (317-325).
- `password !== confirm` (327-330).
- `password.length < 8` (332-335), mayúscula `/[A-Z]/` (337-340), número `/[0-9]/` (342-345), especial `/[^a-zA-Z0-9]/` (347-350).
- Envía `POST /auth/register` con `{email, nombre, password, terms, newsletter}` (355-359).

Además, validación en vivo de fortaleza (`validatePasswordStrength`, líneas 55-87), coincidencia (`validatePasswordMatch`, 95-115) y medidor visual de barras (`.strength-bar`). Las reglas visibles al usuario están en `app/Views/auth/login.php:176-184` (mín. 8 caracteres, una mayúscula, un número, un carácter especial).

### 5.2 Validaciones en el backend — `AuthController::register()` (líneas 92-178)

| Regla | Línea(s) | Detalle |
|---|---|---|
| Campos requeridos + `terms` | 99-102 | `'Completa todos los campos y acepta terminos'` |
| Email válido | 103-106 | `filter_var($email, FILTER_VALIDATE_EMAIL)` |
| Complejidad de contraseña | 107-111 | `strlen >= 8`, `/[A-Z]/`, `/[0-9]/`, `/[^a-zA-Z0-9]/` |
| Email duplicado | 112-115 | `findByEmail()` |
| Hash | 117 | `password_hash($password, PASSWORD_DEFAULT)` (bcrypt) |
| Creación | 120-128 | `rol='cliente'`, `estado='activo'`, `acepta_terminos`, `fecha_registro` |
| Sesión + regeneración | 130-141 | Sesión auto-login al registrarse |
| Correo de bienvenida | 143-166 | `EmailService::enviar($email, $nombre, 'bienvenida')` en try/catch (no bloquea el registro si falla) |

**`UsuarioModel::create()`** (`app/Models/UsuarioModel.php:47-86`): recibe `password_hash` ya generado por el controlador (no hashea internamente; no hay parámetro de cost). Inserta con `rol` (defecto `'cliente'`), `estado` (defecto `'activo'`), `acepta_terminos` y `fecha_registro`.

---

## 6. CARRITO DE COMPRAS

### 6.1 Persistencia

- **Usuario logueado:** fila con `usuario_id`.
- **Invitado:** fila con `session_id` (cookie `cart_session`, creada en `index.php:116-125`, 30 días).
- Tabla `carrito` (`angelow.sql:638-672`): `usuario_id`, `session_id`, `producto_id`, `variante_id`, `cantidad`, `precio_unitario`, `talla_seleccionada`, `color_seleccionado`, timestamps.

### 6.2 Modelo — `app/Models/CarritoModel.php`

| Método | Líneas | Detalle |
|---|---|---|
| `getByUsuario($id)` | 16-22 | JOIN `productos` por `usuario_id`. |
| `getBySession($sid)` | 27-33 | JOIN `productos` por `session_id`. |
| `addOrUpdate($data)` | 38-63 | WHERE de "existe": `(usuario_id = ? OR (session_id = ? AND usuario_id IS NULL)) AND producto_id = ? AND (variante_id = ? OR (variante_id IS NULL AND ? IS NULL)) AND talla_seleccionada = ?` (39-46); si existe, `cantidad = cantidad + ?`; si no, INSERT. |
| `remove()` | 70-81 | DELETE con alcance de propiedad (`AND usuario_id = ?` o `AND session_id = ?`). |
| `clear()` | 87-91 | Vacía por usuario o por sesión. |
| `mergeGuestCart()` | 96-113 | Migra ítems de invitado a usuario (usado por `sincronizar`). |

### 6.3 API — `app/Controllers/Api/CarritoController.php` (284 líneas)

Resolución de identidad (en todos los métodos): `$usuario_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;` y `$session_id = $_COOKIE['cart_session'] ?? null;`.

| Método | Líneas | Comportamiento |
|---|---|---|
| `index()` | 8-61 | Devuelve `{success, cart}` con items enriquecidos (`id`, `name`, `price`, `quantity`, `selectedSize`, `category`, `imgs`, `stock`). Sin usuario ni sesión → `cart: []`. |
| `agregar()` | 63-138 | Valida `producto_id`, existencia, `stock_total > 0`; si ya existe (por usuario/sesión + producto + talla) verifica que `cantidad + nueva ≤ stock_total` y suma; si no, INSERT. Respuesta `{success, cartId}`. |
| `actualizar()` | 140-189 | Consulta el ítem con cláusula de propiedad; si `cantidad <= 0` → DELETE; si supera stock → "Stock insuficiente"; si no, UPDATE. |
| `eliminar()` | 191-219 | DELETE con cláusula de propiedad (`id` + `usuario_id` o `session_id`). |
| `sincronizar()` | 221-261 | Fusión de carrito de invitado al iniciar sesión: suma cantidades, borra filas de invitado y elimina la cookie. |
| `vaciar()` | 263-283 | DELETE por `usuario_id` o `session_id`. |

### 6.4 Carrito en el frontend

- El icono del carrito carga con `/api/carrito` y, si falla, cae a `localStorage` (`public/assets/js/bienvenida.js:201-232`).
- En el checkout, el carrito se compone de `localStorage` (`compra.js:199-240`: `angelow_cart`, `cart`, `angelow_cart_guest`).

---

## 7. PROCESO DE COMPRA (CHECKOUT)

### 7.1 Vista — `CompraController::index()` (`app/Controllers/CompraController.php:10-29`)

- Sin sesión → `/auth/login`.
- Admin → `/admin`; repartidor → `/repartidor/dashboard`.
- Cliente → vista `paginas.compra` con `['user' => $user]`.

### 7.2 Procesamiento — `procesar()` (líneas 41-77)

```php
if (!isset($_SESSION['user'])) { $this->json(['error' => 'No autorizado'], 403); }   // 43-47
$data = json_decode(file_get_contents('php://input'), true);                          // 49-52
if (!is_array($data)) { $this->json(['error' => 'Datos inválidos'], 400); }           // 55-59
$resultado = $pedidoModel->crearPedido($data);                                        // 64
// respuesta: {'success', 'message', 'pedido_id', 'numero_pedido'}                    // 67-72
```
Códigos HTTP: **403** (sin sesión), **400** (JSON inválido), **500** (error de persistencia), 200 (éxito).

### 7.3 Lógica de negocio — `PedidoModel::crearPedido()` (`app/Models/PedidoModel.php:22-162`)

1. `beginTransaction()` (línea 23).
2. `generarNumeroPedido()` (líneas 14-20): consulta `SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido,10) AS UNSIGNED)),0)+1` para el patrón `ORD-{AÑO}-%` y devuelve `ORD-{AÑO}-{secuencia en 4 dígitos}` (línea 19). *(El esquema BD también define el trigger `generar_numero_pedido`, `angelow.sql:1076-1102`, que solo actúa si `numero_pedido` es NULL: es un respaldo de BD.)*
3. Valida `usuario_id` (28-30) y que `productos` sea un array (32-34).
4. Por cada producto: `SELECT id, precio, nombre, stock_total FROM productos WHERE id = :id` (línea 39); valida `id > 0`, `cant > 0`, existencia y `stock_total >= cantidad` (45-60); **el precio se toma del servidor** (`$precioReal = (float)$prod['precio'];`, línea 62) — nunca se confía en el precio del cliente.
5. **Recálculo del total en servidor** (78-85):
   ```php
   $totalReal = $subtotalReal - $descuento + $costoEnvio;
   if ($descuento > $subtotalReal) $descuento = $subtotalReal;
   if ($totalReal < 0) $totalReal = 0;
   ```
6. INSERT en `pedidos` (87-129) con `estado_pago='procesando'`, `zona='centro'`, `prioridad='normal'` (líneas 124-126).
7. INSERT de cada ítem en `detalles_pedido` (133-153).
8. `commit()` (línea 155); respuesta `['id' => $pedidoId, 'numero_pedido' => $numeroPedido]` (156). En catch: `rollBack()` y relanza (158-161).

**Stock:** `crearPedido` valida disponibilidad pero **no descuenta en PHP**; el descuento lo hace el **trigger de MySQL `actualizar_stock_pedido`** (`angelow.sql:1150-1176`) `AFTER INSERT ON detalles_pedido` que actualiza `productos.stock_total`, `total_vendidos` y, si aplica, `variantes_producto.stock`.

### 7.4 Frontend del checkout — `public/assets/js/compra.js`

- Wizard de 3 pasos (`goToStep`, líneas 408-442) con validaciones:
  - `validarPaso1()` (96-128): nombre, apellidos, email (`/^[^\s@]+@[^\s@]+\.[^\s@]+$/`), cédula (`/^[0-9]{6,15}$/`), teléfono (`/^[0-9]{7,15}$/`) y checkbox de términos.
  - `validarPaso2()` (130-154): departamento, municipio, dirección, barrio.
- Cupones: `applyPromo()` (320-357) → `POST /api/cupones/validar` con `{codigo, subtotal}`; guarda el código en `localStorage.promoCode`.
- Envío: `selectShipping()` (383-391): `normal` → $0, `express` → **$15.000**.
- Pago: `selectPayment()` (393-398); por defecto PSE (`seleccionarOpcionesPorDefecto`, 400-405).
- Geolocalización: `obtenerUbicacionUsuario()` (82-94) con timeout de 10 s.
- `guardarPedido()` (450-550): construye el payload (incluye `latitud_destino`/`longitud_destino`), `fetch POST /procesar-compra`, guarda el pedido en `localStorage.angelow_orders`, y después de confirmar llama a `/api/carrito/vaciar` (659-665).
- `completePurchase()` (553-669): valida términos → paso 1 → paso 2 → carrito no vacío → `guardarPedido` → confirmación (672-800).

---

## 8. PRODUCTOS Y CATEGORÍAS

### 8.1 `app/Models/ProductoModel.php`

| Método | Líneas | Detalle |
|---|---|---|
| `getAll()` | 14-22 | `SELECT p.*, c.nombre AS categoria_nombre, sc.nombre AS subcategoria_nombre FROM productos p LEFT JOIN categorias c ... LEFT JOIN categorias sc ... ORDER BY p.fecha_creacion DESC`. |
| `getById()` | 24-33 | Mismo JOIN + `WHERE p.id = :id`. |
| `create()` | 35-85 | INSERT multi-columna; **JSON** en `tallas_disponibles`, `colores_disponibles`, `imagenes`, `caracteristicas` (líneas 68-71); defectos: `stock_total=0`, `stock_minimo=5`, `nuevo=1`, `visible=1`. |
| `update()` | 87-124 | Construcción dinámica a partir de `$camposPermitidos`; los campos JSON se codifican (107-115). |
| `delete()` | 126-129 | `DELETE FROM productos WHERE id = :id`. |
| `generarSlug()` | 131-140 | Normaliza a `[a-z0-9-]`; fallback `'producto-'.time()`. |

### 8.2 `app/Models/CategoriaModel.php`

- `getAll()` (14-23) con `tipo='categoria'`, conteo de productos y subcategorías.
- `getAllWithSub()` (25-33) para `tipo='subcategoria'`.
- `getById()`, `create()` (defectos `tipo='categoria'`, `visible=1`, `destacada=0`), `update()`, `delete()`, `generarSlug()` (91-100).

### 8.3 API Administración de productos — `app/Controllers/Api/ProductsController.php`

- Rutas (config/routes.php:57-61): `GET /api/productos`, `GET /api/productos/{id}`, `POST /api/productos`, `PUT /api/productos/{id}`, `DELETE /api/productos/{id}`.
- `checkAdmin()` (401/403 JSON si `$_SESSION['user']['rol'] !== 'administrador'`); `index()` decodifica `imagenes`, `tallas`, `colores`.
- Stock API — `Api/StockController.php`: `index()` (solo admin, 403 si no; JOIN categorías, lista `id, nombre, categoria, subcategoria, precio, stock, stock_minimo, imagen, vendidos`; líneas 8-46); `update($id)` (valida `stock >= 0`, 400 si no; líneas 48-82); `ajustar($id)` (tipos `add|subtract|set`; `subtract` usa `max(0, ...)`; líneas 84-135); `destroy()` (404 si no existe; 166-175).

---

## 9. PEDIDOS

### 9.1 Número de pedido — `PedidoModel::generarNumeroPedido()` (líneas 14-20)

Formato **`ORD-AAAA-NNNN`** (año + secuencia 4 dígitos), calculado por PHP con `MAX(...) + 1` por año y respaldado por el trigger de BD `generar_numero_pedido` (`angelow.sql:1076-1102`).

### 9.2 Consultas — `PedidoModel`

| Método | Líneas | SQL |
|---|---|---|
| `getAll()` | 164-172 | `SELECT p.*, u.nombre AS nombre_usuario, u.email AS email_usuario, (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.pedido_id = p.id) AS total_productos FROM pedidos p LEFT JOIN usuarios u ... ORDER BY p.fecha_pedido DESC`. |
| `getByUsuario($id)` | 174-183 | Igual pero `WHERE p.usuario_id = :usuario_id`. |
| `getDetalles($pedidoId)` | 185-193 | `detalles_pedido` JOIN `pedidos` (estado, fecha, total). |
| `getById($pedidoId)` | 195-203 | `pedidos` LEFT JOIN `usuarios` por id. |
| `getDetallesCompletos($pedidoId)` | 217-239 | Pedido + usuario + `items` (detalles con `precio_actual`, `stock_total`). Devuelve `null` si no existe (228). |
| `updateEstado($id, $estado)` | 207-215 | Valida contra `ESTADOS_VALIDOS` (definido en línea 205) y hace `UPDATE pedidos SET estado = :estado`. |
| `ESTADOS_VALIDOS` | 205 | `pendiente, confirmado, procesando, listo, asignado, aceptado, recogido, en_camino, entregado, cancelado, reembolsado`. |

> `NO SE ENCONTRÓ` en este archivo un método `asignarRepartidor()`; la asignación de repartidor se hace por SQL directo en `Api\RepartidorPedidosController::updateStatus` (`estado='asignado'` → `UPDATE pedidos SET estado=?, repartidor_id=?, fecha_estimada_entrega=?`) y en el procedimiento almacenado `aceptar_pedido` del esquema.

### 9.3 Pedidos del cliente — `app/Controllers/Cliente/PedidosController.php`

- `index()` (17-28): 403 sin sesión; `$usuarioId = (int) $_SESSION['user']['id'];` (siempre desde sesión, nunca del body/URL) → `getByUsuario`.
- `detalle($pedidoId)` (38-53): `getById` y **chequeo anti-IDOR** (línea 44): `if (!$pedido || $pedido['usuario_id'] != $_SESSION['user']['id'])` → 404.
- `cancelar($pedidoId)` (65-82): ownership (71) y solo permite `estado === 'pendiente'` (75-79).
  - **Hallazgo (posible bug vigente):** línea 80 llama `updateEstado($pedidoId, 'rechazada')` y `'rechazada'` **no está** en `ESTADOS_VALIDOS` (que usa `'cancelado'`), por lo que `updateEstado` lanzaría una excepción no capturada. El esquema BD usa `'rechazado'` (sin `a`). → Comportamiento inconsistente documentado.
- `seguimiento($pedidoId)` (95-145): ownership estricto (`(int)$pedido['usuario_id'] !== $usuarioId`, línea 102); consulta la última posición en `seguimiento_tiempo_real` (107-110) y el repartidor (114-117). Respuesta `{success, pedido, ubicacion|null, repartidor|null}`.

Rutas (config/routes.php:79-82): `GET /api/mis-pedidos`, `GET /api/mis-pedidos/{id}`, `GET /api/mis-pedidos/{id}/seguimiento`, `POST /api/mis-pedidos/{id}/cancelar`.

### 9.4 Pedidos del administrador — `app/Controllers/Admin/PedidosController.php`

- `obtenerPedidos()` (24-37): guardián admin (línea 26 → 403); devuelve `PedidoModel::getAll()`.
- `obtenerPedido($pedidoId)` (40-58): `getDetallesCompletos`; 404 `'Pedido no encontrado'` si no existe (50).
- `updateStatus()` (60-104): guardián admin; valida `id`+`estado` presentes (400), `estado` en `ESTADOS_VALIDOS` (400), pedido existe (404) y `updateEstado` (líneas 72-92). Respuesta `{success, message, estado}`.

---

## 10. FACTURA

**NO SE ENCONTRÓ UN MÓDULO DE FACTURA EN EL CÓDIGO ANALIZADO.**

- La migración `database/migrations/999_drop_facturacion.sql` **elimina** las tablas `facturas`, `facturas_detalle` y `facturas_historial` (módulo de facturación desmantelado).
- `angelow.sql` **no contiene** tabla `factura`.
- `PedidoModel::crearPedido()` **no genera** número de factura (no escribe `num_factura`).
- La única referencia visual es el enlace **"Mis Facturas"** del menú del panel de cliente (`app/Views/paginas/perfil.php:78-109`), sin controlador asociado.

---

## 11. MÓDULO REPARTIDOR

Existen **dos frontales** de registro/login de repartidor (la aplicación web PHP y el microservicio Python) y **dos sistemas de autenticación** (sesión PHP y JWT).

### 11.1 Autenticación

**Login web (sesión)** — `app/Controllers/RepartidorAuthController.php`:
- `login()` (34-131): valida campos y formato de email; **contador de intentos en sesión** `login_attempts_<md5(email)>` (línea 48): máx. 5 intentos / 900 s con mensaje de bloqueo y minutos restantes (52-67).
- Exige `rol = 'repartidor'` (71-73). Estados: `pendiente` → login con `pending:true` y redirect a `/repartidor/dashboard` (75-86); otro no activo → mensaje genérico (88-90).
- Sesión + `session_regenerate_id(true)` (92-110), `ultima_sesion = NOW()`, `en_linea = 1` (113) y emite **JWT de 24 horas** `JWTHelper::encode` (117-123).
- `refreshToken()` (615-636): requiere sesión, re-lee el usuario y debe estar `activo`, re-emite JWT de 24 h.
- `me()` (603-613) y `logout()` (596-601).

**Login API (JWT)** — `app/Controllers/Api/RepartidorAuthController.php`:
- Rutas: `POST /api/repartidor/auth/login`, `POST /api/repartidor/auth/logout`, `GET /api/repartidor/auth/me` (config/routes.php:108-110).
- Consulta `WHERE email = ? AND rol = 'repartidor'`, `password_verify`, revisa `estado` y devuelve el token.

**Dual JWT/sesión en todas las APIs de repartidor** — helper idéntico `getRepartidorId()` en `Api\RepartidorPedidosController`, `RepartidorSeguimientoController`, `RepartidorRastreoController`, `RepartidorClientesController`, `RepartidorDocumentosController` (patrón en `RepartidorPedidosController:9-24`):
```php
$token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
if ($token) { $payload = JWTHelper::decode($token); if ($payload && isset($payload['sub'])) return $payload['sub']; }
if (session...  && ($_SESSION['user']['rol'] ?? '') === 'repartidor') return $_SESSION['user']['id'];
return null; // → 401
```

### 11.2 Registro (vía API PHP) — `app/Controllers/Api/RepartidorRegistroController.php` (472 líneas)

Constantes (líneas 28-42):
```php
TIPOS_DOCUMENTOS_VALIDOS = ['CC','CE','TI','PAS'];
TIPOS_DOCUMENTOS_DB_MAP = ['CC'=>'CC','CE'=>'CE','TI'=>'OTRO','PAS'=>'PASAPORTE'];
TIPOS_VEHICULO_VALIDOS = ['moto','carro','bicicleta','camioneta'];
CATEGORIAS_LICENCIA_VALIDAS = ['A1','A2','B1','B2','B3','C1'];
MAX_BYTES_DOCUMENTO = 10 * 1024 * 1024;   // 10 MB
MIME_MAP = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
```
- Endpoint `POST /api/repartidor/registro` (multipart) (líneas 62-189):
  - Rate limit `registro_repartidor:{IP}:{correo}` **5/900** (líneas 69-72) → 429.
  - `validate()` (231-301): nombres/apellidos letras (mín. 2); email; celular `/^3\d{9}$/`; tipo documento válido; `numdoc /^\d{5,12}$/`; **mayor de 18** (254); dirección/ciudad; contraseña (≥8, mayúscula, minúscula, número, especial) e igualdad (262-271); vehículo; `placa /^[A-Z]{3}-?\d{3,4}$/` (274); licencia; categoría; tarjeta de propiedad; SOAT y tecnomecánica **vigentes** (279-296); acepta términos y privacidad (297-298).
  - Archivos: tamaño ≤ 10 MB y **MIME real por contenido** con `finfo_file` (118-124) — nunca solo la extensión.
  - `crearSolicitud()` (305-414): re-registro si el usuario está `inactivo/eliminado` y su última solicitud fue `rechazada` (317-325); duplicado de email → `RuntimeException` (327-329); INSERT en `usuarios`, `solicitudes_repartidores`, `vehiculos_repartidores`, `historial_repartidores`; si `cedula` ya existe → rollback/borrado (367-372).
  - `guardarDocumento()` (418-451): archivos en `public/uploads/documentos/` con nombre aleatorio `{userId}_{tipo}_{hex16}.{ext}` (427-428); filas en `documentos` con `estado='pendiente'`.
  - `notificarAdministradores()` (453-471): notificación `solicitud_repartidor` a cada admin activo.
  - Al finalizar crea sesión `pendiente` y responde `{success:true, pending:true, redirect:'/repartidor/dashboard'}` (182-188).
- Endpoint `GET /api/repartidor/estado?correo=...` (194-228): valida email, rate limit `estado_repartidor:{IP}` **20/900**, y devuelve `estado, motivo, fecha_solicitud, fecha_respuesta`.

### 11.3 Registro web PHP — `RepartidorAuthController::registro()` (133-432)

Mismo juego de validaciones (165-221), mismo handle de archivos (331-406; 10 MB, finfo MIME), mismas tablas y notificación. Es la ruta `POST /repartidor/registro`.

### 11.4 Pedidos del repartidor — `Api/RepartidorPedidosController.php` (313 líneas)

| Método | Líneas | Comportamiento |
|---|---|---|
| `index()` | 35-113 | Filtros GET: `status`, `date` (YYYY-MM-DD), `search` (por `numero_pedido`/`nombre_cliente`) y `available=1` → pedidos con `repartidor_id IS NULL AND estado IN ('pendiente','confirmado','listo')` (48); caso directo: `repartidor_id = ?` (63). Enriquece con ítems (87). |
| `show($id)` | 117-165 | **Chequeo de propiedad** (135): `repartidor_id` debe coincidir → 403. |
| `create()` | 167-222 | Crea pedido en nombre del cliente (toma `client_id` opcional, `items` con precio del cliente); `usuario_id` = cliente o repartidor; **`ciudad='Medellín'`, `departamento='Antioquia'` y `producto_id=1` hardcodeados** (203-211). |
| `updateStatus($id)` | 238-296 | Estados válidos propios: `['pendiente','confirmada','cambio','devolucion','rechazada']` (249) — **difieren de `ESTADOS_VALIDOS` del modelo** (nota de hallazgo). Ramas especiales: `entregado` → `fecha_entrega_real` + INSERT en `historial_entregas` con ganancia `ganancias_repartidor ?? 5000` (263-271); `asignado` → `repartidor_id`, `fecha_estimada_entrega` (273-276). |
| `destroy($id)` | 300-313 | DELETE de detalles + pedido. **Sin chequeo de propiedad** (cualquier repartidor autenticado puede borrar cualquier pedido). |

### 11.5 Seguimiento en tiempo real — `Api/RepartidorSeguimientoController.php`

- `ubicacion()` (35-61): exige `pedido_id`, `latitud`, `longitud` (400 si faltan); INSERT en `seguimiento_tiempo_real` (55).
- `show($pedidoId)` (63-121): última posición + pedido + repartidor; respuesta `{pedido_id, estado, ubicacion|null, repartidor, destino}`.

### 11.6 Rastreo — `Api/RepartidorRastreoController.php` (279 líneas)

- `listar()` (35-55): pedidos con `estado IN ('asignado','aceptado','recogido','en_camino')` (43).
- `buscar()` (57-135): por `numero_pedido`; 422 si vacío; **chequeo de propiedad** (81 → 403).
- `historial($id)` (137-198): timeline desde `historial_pedidos_repartidor` + construcción con hitos `pendiente→confirmado→procesando→listo→(asignado)→(en_camino)→entregado` (`construirTimeline`, 172).
- `actualizar($id)` (200-279): **máquina de estados** (227-232) `asignado→[aceptado,recogido,en_camino,entregado]`, `aceptado→[...]`, `recogido→[...]`, `en_camino→[entregado]`; setea timestamps (`fecha_aceptacion`, `fecha_recogida`, `fecha_entrega_real`) e inserta en `historial_pedidos_repartidor` y, si `entregado`, en `historial_entregas` (ganancia `?? 5000`).

### 11.7 Clientes del repartidor — `Api/RepartidorClientesController.php`

- `index()` (35-74): `SELECT DISTINCT u... FROM usuarios u JOIN pedidos p ON p.usuario_id = u.id WHERE p.repartidor_id = ?` + búsqueda LIKE (45).
- `show($id)` (76-97): devuelve el usuario por ID **sin chequeo de propiedad** (cualquier repartidor autenticado puede ver cualquier cliente).

### 11.8 Documentos del repartidor — `Api/RepartidorDocumentosController.php` (273 líneas)

- `index($repartidorId = null)` (35-73): **anti-IDOR** (46-52): solo el propio repartidor o un admin pueden ver los documentos (403 en otro caso).
- `subir()` (75-150): whitelist `['cedula','licencia_conduccion','tarjeta_profesional','tarjeta_propiedad','soat','tecnomecanica']` (85); archivo obligatorio, ≤ 10 MB, **MIME por `finfo`** (PDF/JPG/PNG); nombre aleatorio `{id}_{tipo}_{hex16}.{ext}` (133); INSERT `estado='pendiente'`. Incluye helper `ensureDocumentosTable()` con `CREATE TABLE IF NOT EXISTS documentos` (152-172).
- `archivo($id)` (196-270): sirve el binario con `realpath()` (evita path traversal), `X-Content-Type-Options: nosniff`, `Content-Disposition: inline` y `X-Sendfile`; autorización: admin o propietario (215-218).

### 11.9 Panel del repartidor

- `app/Controllers/RepartidorController.php` (web): `index()` requiere rol repartidor (11-14); si `estado !== 'activo'` consulta la solicitud y las notificaciones (20-40) y renderiza `repartidor.dashboard` (42-47); `perfil()` (57-64); `registro()` (66-70) redirige a `MICROSERVICE_URL` (el microservicio).
- `app/Views/repartidor/dashboard.php`: guarda al inicio (2-5); flags de estado `$isPending/$isRejected/$isSuspended/$isInactiveNoRejection` (6-13); tarjetas de estadísticas; filtros; mapa Leaflet; pedidos disponibles/activos; historial; notificaciones; auto-refresh de token (`/repartidor/refresh-token`, líneas 1290-1305) y autorefresco de datos cada 30 s (1966-1970).

---

## 12. PANEL ADMINISTRADOR

### 12.1 `Admin/DashboardController.php` (36 líneas)

- Guardián: `$_SESSION['user']['rol'] !== 'administrador'` → `/auth/login` (11-12).
- 5 consultas sobre `$db` (18-23): total pedidos, pendientes (`estado IN ('pendiente','confirmado','procesando','listo')`), total favoritos, ganancias (`SUM(total) WHERE estado='entregado'`), total usuarios.
- Vista `admin.panel` con `totalPedidos, pendientes, favoritos, ganancias, totalUsuarios, nombreAdmin` (34).

### 12.2 `Admin/PedidosController.php` (105 líneas)

- `obtenerPedidos()` (24-37), `obtenerPedido()` (40-58) y `updateStatus()` (60-104), descritos en §9.4.

### 12.3 `Admin/UsuariosController.php` (18 líneas)

- Solo renderiza `admin.usuarios` con **`$usuarios = []`** (línea 15, comentario "Modelo de usuarios"). → El listado **no** se consulta en este controlador; la gestión real existe en la API `Admin\ClientesController`.

### 12.4 `Admin/InventarioController.php` y `Admin/RepartidorController.php`

- Vistas `admin.inventario` y `admin.repartidor` tras guardián admin (`Auth::isAdmin()` en `RepartidorController:11`).

### 12.5 API de administración de repartidores — `Admin\AdminRepartidorController.php` (467 líneas)

- `requireAdmin()` (9-16): sesión, `rol === 'administrador'` → 403 en caso contrario.
- `solicitudes()` (27-113): paginación (`estado`, `per_page`, `page`; COUNT + SELECT con `LIMIT/OFFSET` vía `bindValue(PARAM_INT)`), enriquece con `documentos` (url `APP_URL/api/documentos/{id}/archivo`) y vehículo (86, 104).
- `aprobar()` (116-185): **transacción** (139-177): usuario → `estado='activo'`, `aprobado_por`, `fecha_aprobacion`; solicitud → `aprobada`; documentos → `aprobado`; `grantDefaultPermissions()` (los 10 permisos de `permisos_repartidores` vía `ON DUPLICATE KEY UPDATE`); historial `aprobacion`; notificación `aprobacion_repartidor`.
- `rechazar()` (187-242): solicitud → `rechazada` con `motivo_rechazo`; usuario → `inactivo`; historial `rechazo`; notificación `rechazo_repartidor`.
- `suspender()` (265-294): usuario → `suspendido` con `motivo_suspension` (donde `rol='repartidor'`).
- `activar()` (296-334): usuario → `activo`, re-grant de permisos, historial `reactivacion`, notificación.
- `estadisticas()` (336-385): pendientes, total, activos, suspendidos, rechazadas, total entregas (cada consulta cae a 0 en error).
- `activos()` (387-397): lista `WHERE rol='repartidor' AND estado='activo'`.
- `revisarDocumento()` (399-429): aprueba/rechaza documentos (`estado`, `revisado_por`, `fecha_revision`).
- `dashboardStats()` (431-466): pedidos pendientes, totales, ingresos (`SUM(total) WHERE estado NOT IN ('cancelado')`), repartidores activos.

**Hallazgo:** este controlador **no** llama al microservicio (no usa `X-Admin-Token` ni HTTP hacia Python): opera directo sobre MySQL vía `Database::query`.

---

## 13. API DE LA APLICACIÓN (TABLA DE ENDPOINTS)

Configuración única: `config/routes.php` (162 líneas, **123 rutas**). El router coincide método + path y despacha a `App\Controllers\{controller}->action()`.

### 13.1 Rutas de páginas

| Método | Ruta | Controlador→Acción | Línea |
|---|---|---|---|
| GET | `/` | HomeController→index | 3 |
| GET | `/cargador` | CargadorController→index | 4 |
| GET | `/auth/login` | AuthController→showLogin | 5 |
| POST | `/auth/login` | AuthController→login | 6 |
| POST | `/auth/register` | AuthController→register | 7 |
| POST | `/auth/forgot-password` | AuthController→forgotPassword | 8 |
| POST | `/auth/google` | AuthController→googleLogin | 9 |
| GET | `/auth/logout` | AuthController→logout | 10 |
| GET | `/perfil` | PerfilController→index | 11 |
| GET | `/compra` | CompraController→index | 12 |
| POST | `/procesar-compra` | CompraController→procesar | 13 |
| GET | `/seguimiento` | SeguimientoController→index | 14 |
| GET | `/admin` | Admin\DashboardController→index | 16 |
| GET | `/admin/pedidos` | Admin\PedidosController→index | 17 |
| GET | `/admin/usuarios` | Admin\UsuariosController→index | 18 |
| GET | `/admin/inventario` | Admin\InventarioController→index | 19 |
| GET | `/documentos/Pedidos_envios` | DocumentoController→pedidosEnvios | 22 |
| GET | `/documentos/Politicas_devolucion` | DocumentoController→politicasDevolucion | 23 |
| GET | `/documentos/Preguntas` | DocumentoController→preguntas | 24 |
| GET | `/documentos/Guia_Tallas` | DocumentoController→guiaTallas | 25 |
| GET | `/documentos/Terminos` | DocumentoController→terminos | 26 |
| GET | `/documentos/Politicas_Priv` | DocumentoController→politicasPrivacidad | 27 |
| GET | `/documentos/Politicas_Env` | DocumentoController→politicasEnv | 28 |
| GET | `/auth/change-password` | AuthController→showChangePassword | 31 |
| POST | `/auth/change-password` | AuthController→changePassword | 32 |
| GET | `/auth/reset-password` | AuthController→showResetForm | 33 |
| POST | `/auth/reset-password` | AuthController→resetPassword | 34 |
| GET | `/contactenos` | ContactoController→index | 37 |
| POST | `/contacto/enviar` | ContactoController→enviar | 38 |

### 13.2 API Carrito (líneas 41-46)

| Método | Ruta | Acción | Autenticación |
|---|---|---|---|
| GET | `/api/carrito` | index | usuario o cookie invitado |
| POST | `/api/carrito/agregar` | agregar | ídem |
| POST | `/api/carrito/actualizar` | actualizar | ídem |
| DELETE | `/api/carrito/eliminar` | eliminar | ídem |
| POST | `/api/carrito/sincronizar` | sincronizar | sesión (fusión invitado) |
| POST | `/api/carrito/vaciar` | vaciar | usuario o cookie |

### 13.3 Cupones / Favoritos (49-54)

| Método | Ruta | Acción |
|---|---|---|
| POST | `/api/cupones/validar` | validar (público, requiere `codigo`+`subtotal`) |
| GET | `/api/favoritos` | index (devuelve `[]` si no autenticado) |
| POST | `/api/favoritos/agregar` | agregar (401 si no sesión) |
| DELETE | `/api/favoritos/eliminar` | eliminar (401 si no sesión) |

### 13.4 Productos y categorías (57-67)

| Método | Ruta | Acción | Acceso |
|---|---|---|---|
| GET | `/api/productos` | index | admin |
| GET | `/api/productos/{id}` | show | admin |
| POST | `/api/productos` | store | admin |
| PUT | `/api/productos/{id}` | update | admin |
| DELETE | `/api/productos/{id}` | destroy | admin |
| GET | `/api/categorias` | index | admin |
| GET | `/api/categorias/{id}` | show | admin |
| POST | `/api/categorias` | store | admin |
| PUT | `/api/categorias/{id}` | update | admin |
| DELETE | `/api/categorias/{id}` | destroy | admin |

### 13.5 Inventario / stock (69-70, 83-85)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/inventario` | index |
| POST | `/api/inventario` | update |
| POST | `/api/inventario/update` | update |
| POST | `/api/inventario/ajustar` | ajustar |
| POST | `/api/inventario/eliminar` | destroy |

*(Todas con guardián admin; ver §8.3.)*

### 13.6 Clientes (admin) y pedidos (admin/cliente) (71-82)

| Método | Ruta | Acción | Acceso |
|---|---|---|---|
| GET | `/api/clientes` | index | admin |
| POST | `/api/clientes` | store | admin |
| POST | `/api/clientes/buscar` | buscar | admin |
| POST | `/api/clientes/rol` | cambiarRol | admin |
| DELETE | `/api/clientes/{id}` | destroy | admin |
| GET | `/api/pedidos` | obtenerPedidos | admin |
| GET | `/api/pedidos/{id}` | obtenerPedido | admin |
| POST | `/api/pedidos/estado` | updateStatus | admin |
| GET | `/api/mis-pedidos` | index | cliente |
| GET | `/api/mis-pedidos/{id}` | detalle | cliente (propietario) |
| GET | `/api/mis-pedidos/{id}/seguimiento` | seguimiento | cliente (propietario) |
| POST | `/api/mis-pedidos/{id}/cancelar` | cancelar | cliente (propietario) |

### 13.7 Perfil, direcciones, tarjetas (88-103)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/perfil` | index |
| POST | `/api/perfil/actualizar` | actualizar |
| GET | `/api/direcciones` | index |
| POST | `/api/direcciones/crear` | crear |
| POST | `/api/direcciones/actualizar/{id}` | actualizar |
| POST | `/api/direcciones/eliminar/{id}` | eliminar |
| POST | `/api/direcciones/predeterminada/{id}` | setPredeterminada |
| GET | `/api/tarjetas` | index |
| POST | `/api/tarjetas/crear` | crear |
| POST | `/api/tarjetas/actualizar/{id}` | actualizar |
| POST | `/api/tarjetas/eliminar/{id}` | eliminar |
| POST | `/api/tarjetas/predeterminada/{id}` | setPredeterminada |

### 13.8 Repartidor — registro y auth (106-110)

| Método | Ruta | Acción |
|---|---|---|
| POST | `/api/repartidor/registro` | registro (multipart) |
| GET | `/api/repartidor/estado` | estado |
| POST | `/api/repartidor/auth/login` | login (JWT) |
| POST | `/api/repartidor/auth/logout` | logout |
| GET | `/api/repartidor/auth/me` | me |

### 13.9 Repartidor — pedidos, seguimiento, dashboard, clientes, rastreo, documentos (112-137)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/repartidor/pedidos` | index |
| GET | `/api/repartidor/pedidos/{id}` | show |
| POST | `/api/repartidor/pedidos` | create |
| PUT | `/api/repartidor/pedidos/{id}/estado` | updateStatus |
| DELETE | `/api/repartidor/pedidos/{id}` | destroy |
| POST | `/api/repartidor/seguimiento/ubicacion` | ubicacion |
| GET | `/api/repartidor/seguimiento/{pedidoId}` | show |
| GET | `/api/repartidor/dashboard/stats` | stats |
| GET | `/api/repartidor/dashboard/recent-orders` | recentOrders |
| GET | `/api/repartidor/dashboard/low-stock` | lowStock |
| GET | `/api/repartidor/notificaciones` | notificaciones |
| GET | `/api/repartidor/clientes` | index |
| GET | `/api/repartidor/clientes/{id}` | show |
| GET | `/api/repartidor/rastreo` | listar |
| GET | `/api/repartidor/rastreo/buscar` | buscar |
| GET | `/api/repartidor/rastreo/{id}/historial` | historial |
| PUT | `/api/repartidor/rastreo/{id}/estado` | actualizar |
| POST | `/api/repartidor/documentos/subir` | subir |
| GET | `/api/repartidor/documentos/{repartidorId}` | index |
| GET | `/api/repartidor/documentos` | index |
| GET | `/api/documentos/{id}/archivo` | archivo |

*(Todas con auth dual JWT Bearer / sesión de repartidor; `archivo` admite también admin.)*

### 13.10 Páginas de repartidor (140-148)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/repartidor` | RepartidorController→index |
| GET | `/repartidor/dashboard` | RepartidorController→index |
| GET | `/repartidor/perfil` | RepartidorController→perfil |
| GET | `/repartidor/registro` | RepartidorController→registro (redirige al microservicio) |
| POST | `/repartidor/registro` | RepartidorAuthController→registro |
| GET | `/repartidor/login` | RepartidorAuthController→showLogin |
| POST | `/repartidor/login` | RepartidorAuthController→login |
| GET | `/repartidor/logout` | RepartidorAuthController→logout |
| POST | `/repartidor/refresh-token` | RepartidorAuthController→refreshToken |

### 13.11 Adminsitración de repartidores (151-160)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/admin/repartidores` | Admin\RepartidorController→index |
| GET | `/api/admin/repartidores/solicitudes` | solicitudes |
| POST | `/api/admin/repartidores/solicitudes/aprobar` | aprobar |
| POST | `/api/admin/repartidores/solicitudes/rechazar` | rechazar |
| GET | `/api/admin/repartidores/estadisticas` | estadisticas |
| GET | `/api/admin/repartidores/activos` | activos |
| POST | `/api/admin/repartidores/suspender` | suspender |
| POST | `/api/admin/repartidores/activar` | activar |
| POST | `/api/admin/repartidores/documentos/revisar` | revisarDocumento |
| GET | `/api/admin/dashboard/stats` | dashboardStats |

---

## 14. PANEL DE CLIENTE

### 14.1 Perfil

- **Vista:** `PerfilController::index` (9-31): `Auth::check()`, redirige admin/repartidor, renderiza `paginas.perfil` con `Auth::user()`.
- **API:** `Cliente/PerfilApiController.php` (57 líneas):
  - `requireAuth()` (14-17) → 401.
  - `index()` (19-25): 404 si el usuario no existe; **elimina de la respuesta** `password_hash`, `reset_token`, `reset_expiry` (línea 23).
  - `actualizar()` (27-56): 400 si body vacío; **whitelist** `['nombre','apellido','telefono','cedula','genero','fecha_nacimiento']` (línea 33); UPDATE dinámico preparado solo con campos permitidos; re-sincroniza `$_SESSION['user']['nombre']`.

### 14.2 Direcciones — `Cliente/DireccionController.php`

- `crear()` (25-38): exige `departamento`, `municipio`, `calle`, `barrio`, `destinatario` (400) — validación mínima.
- `actualizar/eliminar/setPredeterminada` (40-77) con alcance por usuario.
- **Modelo** `DireccionModel`: `getByUsuario`, `getById($id,$usuarioId)` (alcance de propiedad, 21-27); `crear` con limpieza de predeterminada y defectos `titulo='Casa'`, `pais='Colombia'`, `codigo_postal='05001'`; `ensureOnePredeterminada` (109-121).

### 14.3 Tarjetas — `Cliente/TarjetaController.php`

- `crear()` (25-46): exige `numero_tarjeta, titular, mes_expiracion, anio_expiracion, departamento, municipio, calle, barrio, codigo_postal`; número **13-19 dígitos numéricos** tras quitar espacios (34-38).
- Modelo `TarjetaModel`: almacena solo `numero_enmascarado = "**** **** **** {últimos 4}"` (línea 51); `detectarTipo()` por prefijo (visa `/^4/`, mastercard `/^5[1-5]/`, amex `/^3[47]/`, discover `/^6(?:011|5)/`); borrado lógico con `activa=0`.

### 14.4 Favoritos — `Api/FavoritoController.php`

- `index()`: `{success, favoritos}`; sin autenticación devuelve `[]` (40-43).
- `agregar()`: 401 si no autenticado; 409 si ya existe (`existe()`).
- `eliminar()`: 404 si no existe.
- Modelo `Favorito`: tabla con clave única `(usuario_id, producto_id)` (`angelow.sql:687`).

---

## 15. SEGURIDAD

### 15.1 Control de acceso

- **Guardas de rol** en todos los paneles (admin/repartidor/cliente), ver §4.
- **Sesión endurecida:** `use_strict_mode`, `use_only_cookies`, cookie `httponly + SameSite=Lax + secure(HTTPS)` (`index.php:42-54`); expiración por inactividad de **12 horas** (60-69); `session_regenerate_id(true)` en login/registro.

### 15.2 Anti-CSRF

Chequeo global **Origin/Referer** para métodos `POST/PUT/DELETE/PATCH` (`index.php:139-181`):
- Whitelist de hosts: `localhost`, `127.0.0.1`, `[::1]`, `::1` + el host de `APP_URL` (150-153).
- Si `Origin` presente y host no permitido → **403** `{"error":"Origen no permitido"}` (168-173); si no hay Origin pero sí Referer y falla → 403 (174-179); si no hay ninguna de las dos cabeceras, el chequeo se omite (180).

### 15.3 Contraseñas

- `password_hash(..., PASSWORD_DEFAULT)` (bcrypt) en registro, login `password_verify`; para repartidor el microservicio usa `bcrypt.hashpw` (`patterns/structural/facade.py:178`).

### 15.4 Rate limiting (líderes verificados)

| Superficie | Clave | Límite |
|---|---|---|
| Login web | `login:{IP}:{email}` (`AuthController:36-40`) | 5 / 900 s |
| Recuperar contraseña | `forgot:{IP}:{email}` (`AuthController:271-272`) | 3 / 3600 s |
| Registro repartidor | `registro_repartidor:{IP}:{email}` (`RepartidorRegistroController:69-72`) | 5 / 900 s |
| Estado repartidor | `estado_repartidor:{IP}` | 20 / 900 s |
| Login repartidor web | `login_attempts_<md5(email)>` en sesión | 5 / 900 s |
| Microservicio dashboard | por `repartidor_id` (`routes/dashboard.py`) | 60 / min |

### 15.5 Subida de archivos

- Límite **10 MB** + **MIME real por contenido** (`finfo`) PDF/JPG/PNG (`RepartidorRegistroController:114-124`, `RepartidorDocumentosController:100-125`; en Python `mime_detector.py` por magic bytes).
- Nombres aleatorios `bin2hex(random_bytes(8))`.
- Servicio de descarga usa `realpath()` para bloquear path traversal (`RepartidorDocumentosController:221-246`).

### 15.6 JWT — `app/Core/JWTHelper.php` (101 líneas)

- Evento: HS256 (`hash_hmac('sha256', ...)`) — líneas 65, 70, 89.
- `encode()` (58-73) y `decode()` (75-100): validación de 3 segmentos, firma con `hash_equals` (comparación en tiempo constante, línea 92) y expiración si `exp < time()` (97). **Fail-closed:** sin `JWT_SECRET` en `.env` → devuelve `null` (líneas 12-38).
- Límites: `encode()` no agrega `exp` automáticamente (debe agregarlo el llamador); `decode()` **no** valida `iat`, `nbf`, `iss`, `aud` ni `sub` contra algo.

### 15.7 Anti-IDOR (controles de propiedad)

| Control | Propiedad | Línea |
|---|---|---|
| `Cliente/PedidosController::detalle` | pedido del cliente (comparación `!=`) | 44 |
| `Cliente/PedidosController::seguimiento` | comparación estricta `!==` | 102 |
| `RepartidorDocumentosController::index/archivo` | repartidor propio o admin | 46-52, 215-218 |
| `RepartidorPedidosController::show`, `RastreoController::buscar/historial/actualizar` | repartidor asignado | 135, 81, 155, 218 |

**Huecos documentados (sin control de propiedad):** `Api/RepartidorPedidosController::destroy` (cualquier repartidor autenticado borra cualquier pedido) y `Api/RepartidorClientesController::show` (cualquier repartidor ve cualquier cliente por ID).

### 15.8 Protección de datos sensibles

- `PerfilApiController` elimina `password_hash/reset_token/reset_expiry` de las respuestas.
- Números de tarjeta almacenados **enmascarados** (solo últimos 4 dígitos).
- Respuestas de error genéricas al cliente; detalle técnico solo a `error_log` (práctica declarada en `RepartidorRegistroController` y aplicada en los catch).

### 15.9 Debilidades encontradas (para informe)

1. `googleLogin()` no verifica firma/nonce del id_token de Google.
2. `Cliente/PedidosController::cancelar` usa el estado `'rechazada'` que no está en `ESTADOS_VALIDOS` → posible excepción.
3. `RepartidorPedidosController::updateStatus` usa su propia lista de estados (`confirmada`, `cambio`, `devolucion`, `rechazada`) que no coincide con la de `PedidoModel`.
4. `destroy`/`show` de clientes sin chequeo de propiedad (ver §15.7).

---

## 16. MICROSERVICIO DE REPARTIDORES (Python / FastAPI)

Código en `repartidor-service/`. Sirve el formulario de registro del repartidor, su login y un dashboard; la UI es servida por FastAPI (templates Jinja + estáticos).

### 16.1 `main.py`

- App FastAPI con middleware de cabeceras de seguridad, límite de tamaño de body, CORS; sirve `/static`; endpoints `/` (registro.html), `/dashboard`, `/loader`, `/dashboard/loader`, `/health`; ejecución con uvicorn y `ConfigManager`.

### 16.2 `config.py`

- **Singleton `ConfigManager`** con `_load_dotenv()` propio (no sobrescribe variables ya existentes).
- Define: `DOC_TYPES` (soat-file, tarjeta-file, licencia-file, ...), `ALLOWED_MIME`, `MAX_FILE_SIZE` (10 MB), `TIPOS_DOCUMENTOS_VALIDOS` (`CC/CE/TI/PAS`) con mapa a BD (`TI→OTRO`, `PAS→PASAPORTE`), `TIPOS_VEHICULO_VALIDOS`, `CATEGORIAS_LICENCIA_VALIDAS`, `ADMIN_TOKEN`, `DB_URL` (SQLite `repartidores.db`), y la configuración MySQL de ANGELOW para sincronización.

### 16.3 `database.py`

- SQLAlchemy + **SQLite** (`check_same_thread=False`); `SessionLocal`; `init_db()` con `Base.metadata.create_all` (líneas 28-30) → crea las 6 tablas de los modelos ORM: `usuarios`, `solicitudes_repartidores`, `vehiculos_repartidores`, `documentos`, `historial_repartidores`, `notificaciones`.

### 16.4 `routes/repartidor.py` — registro/login API

- `POST /api/repartidor/login`: proxy con rate limiting en memoria (`_login_attempts`) hacia el login PHP de ANGELOW.
- `POST /api/repartidor/registro`: multipart → `RepartidorService.register`.
- `GET /api/repartidor/estado`.
- `GET /api/repartidor` (lista) y `PUT /api/repartidor/{id}/estado` (aprobar/rechazar) protegidos por **`_require_admin`** (líneas 46-54): acepta header `X-Admin-Token` igual a `ADMIN_TOKEN` **o** cliente desde `127.0.0.1`/`::1`; en otro caso **403**.

### 16.5 `routes/dashboard.py` (169 líneas)

- `_auth()` (39-75): valida Bearer JWT (`decode_token`), **revisa blacklist (`blacklist.is_revoked(jti)`)**, y aplica rate limit 60/min por repartidor. Devuelve `repartidor_id`.
- Endpoints: `GET /me`, `GET /resumen`, `POST /pedidos/{id}/aceptar`, `POST /pedidos/{id}/rechazar`, `PUT /pedidos/{id}/estado`, `GET /rastreo`, `POST /ubicacion`, `POST /logout` (revoca el token en la blacklist).
- `dashboard_service.py`: conexión **directa a MySQL de ANGELOW** (pymysql), decodifica el JWT HS256 de ANGELOW, implementa la máquina de estados `TRANSICIONES_PERMITIDAS` (líneas 18-23) y serializa los pedidos.

### 16.6 `security/blacklist.py`

- `TokenBlacklist` Singleton en memoria (`{jti: exp}`) con `revoke(jti, exp)`, `is_revoked(jti)` (expira → quita y devuelve False), `_cleanup()`, `reset()` (solo tests). Usado en `routes/dashboard.py:57` (rechazo de tokens revocados) y `:165` (logout).

### 16.7 Registro del microservicio (validaciones Python)

Viven en `patterns/behavioral/strategy.py` (ver §18):
- `PersonalDataValidation` (22-77): nombres/apellidos letras mínimo 2; email regex; celular `/^3\d{9}$/`; tipodoc en `CC/CE/TI/PAS`; numdoc `/^\d{5,12}$/`; contraseña ≥8 + mayúscula + minúscula + número + especial + confirmación; `fecha_nacimiento` solo para edad.
- `VehicleValidation` (80-135): vehículo en lista; **placa** `/^[A-Z]{3}\d{3}$|^[A-Z]{3}\d{2}[A-Z]$/` (ABC123 o ABC12D) — tras quitar guiones (solo vehículos no bici); `tarjeta` `/^\d{6,15}$/`; licencia `/^\d{5,15}$/`; categoría en lista; vigencias de SOAT/licencia en el **futuro**.
- `DocumentFileValidation` (138-150): obligatorios `soat-file`, `tarjeta-file`, `licencia-file`.
- `ConsentsValidation` (153-161): `acepta_terminos` y `acepta_privacidad` verdaderos.
- Los archivos se validan por **magic bytes** (`patterns/behavioral/mime_detector.py`: `%PDF`, `\xff\xd8\xff`, `\x89PNG...`).

### 16.8 Servicios

- `services/repartidor_service.py`: compone `RepartidorFacade` envuelto en `LoggingDecorator` + `RateLimitDecorator` y delega `register`, `get_estado`, `listar`, `cambiar_estado`.
- `services/dashboard_service.py`: estadísticas, aceptar/rechazar, transición de estados, `reportar_ubicacion` (INSERT en `seguimiento_tiempo_real`), historial/entregas, y serialización de pedidos.

### 16.9 Vistas del microservicio

- `templates/`: `registro.html` (wizard 3 pasos), `loader_registro.html`, `dashboard.html`, `loader_dashboard.html`.
- `static/js/`: `script.js` (validación del wizard + login, línea 38: regex de contraseña `^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~´\`]).{8,}$`), `promesas.js`, `dashboard.js`.

---

## 17. PARÁMETROS DEL SISTEMA / CONFIGURACIÓN

### 17.1 Configuración por archivo `.env` (`.env.example`, 35 líneas)

| Clave | Uso |
|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexión MySQL (`config/database.php`, que además fuerza `charset=utf8mb4`). Defectos: localhost / angelow_db / root / vacío. |
| `SMTP_HOST`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_PORT`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` | Envío de correos (`EmailService`) — PHPMailer SMTP con tipos `bienvenida/recuperacion/notificacion/factura` (`app/Libraries/EmailService.php`). |
| `APP_NAME`, `APP_URL`, `MICROSERVICE_URL` | Constantes `config/app.php` (APP_URL defecto `http://localhost/Angelow/public`; MICROSERVICE_URL `http://127.0.0.1:8000/`). |
| `TIMEZONE` | `date_default_timezone_set()` (defecto `America/Bogota`). |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Login con Google (el ID se usa en la vista `app/Views/auth/login.php:16` y la etiqueta `g_id_onload`). |
| `JWT_SECRET` | Firma HS256 de los tokens de repartidor (`JWTHelper`). |

### 17.2 Otras constantes relevantes

- **Tamaño de documentos repartidor:** 10 MB (constante PHP `MAX_BYTES_DOCUMENTO` y Python `MAX_FILE_SIZE`).
- **Tarifa de envío express:** $15.000 (frontend `compra.js:389`).
- **Expiración de token de recuperación:** +1 hora (`AuthController:284`).
- **JWT de repartidor:** 24 horas (`RepartidorAuthController`).
- **Cookie de invitado:** 30 días (`index.php:118-124`).

---

## 18. PATRONES DE DISEÑO

En el **microservicio Python** se aplicaron patrones GOF explícitos (documentados en los comentarios `.py`), y el proyecto PHP usa Singleton + Front Controller + Repository-style.

### 18.1 PHP (aplicación web)

| Patrón | Uso |
|---|---|
| **Front Controller** | `public/index.php` + `Router` + controladores. |
| **Singleton** | `Database::getInstance()` (`app/Core/Database.php:11-12, 34-39`). |
| **CRUD genérico / capa modelo** | `App\Core\Model` con `getAll/getById/create/update/delete`; los modelos de dominio extienden esta capa. |

### 18.2 Python (microservicio)

| Patrón | Archivo | Propósito |
|---|---|---|
| **Facade** | `patterns/structural/facade.py` (`RepartidorFacade`) | Expone un único `register()` que orquesta repositorios, estrategias, cadena de documentos, factory y observadores. |
| **Strategy** | `patterns/behavioral/strategy.py` | `PersonalDataValidation`, `VehicleValidation`, `DocumentFileValidation`, `ConsentsValidation`. |
| **Template Method** | `patterns/behavioral/template_method.py` (`RegistrationFlow`) | Esqueleto fijo del registro: paso personal → vehículo → consentimientos. |
| **Chain of Responsibility** | `patterns/behavioral/chain_of_responsibility.py` | `build_document_chain()`: FileExists → FileSize → MimeValidation → FileSave → DatabaseInsert. |
| **Observer** | `patterns/behavioral/observer.py` | `AdminNotificationObserver`, `HistoryLogObserver`, `AngelowSyncObserver`; `SolicitudStatusSubject`. |
| **Factory** | `patterns/creational/factory.py` (`RepartidorFactory`) | `create_or_reactivate()` (crear o re-activar según estado previo). |
| **Builder** | `patterns/creational/builder.py` (`SolicitudBuilder`) | Construcción paso a paso de la solicitud con campos condicionales. |
| **Adapter** | `patterns/structural/adapter.py` y `angelow_adapter.py` | Abstracción de almacenamiento de archivos y **sincronización best-effort hacia el MySQL de ANGELOW** (`_upsert_usuario`, `_insertar_solicitud`, `_insertar_vehiculo`, `_insertar_historial`, `_copiar_guardar_documentos`, `_notificar_administradores`). |
| **Decorator** | (pattern explicado en `services/repartidor_service.py`) | `LoggingDecorator` y `RateLimitDecorator` envuelven la fachada. |
| **Repository** | `repositories/*` | `UsuarioRepository`, `SolicitudRepository`, `VehiculoRepository`, `DocumentoRepository`, `HistorialRepository`, `NotificacionRepository`. |
| **Singleton** | `config.py` (`ConfigManager`) y `security/blacklist.py` (`TokenBlacklist`, thread-safe con `threading.Lock`) | Configuración y blacklist de tokens. |

---

## 19. VALIDACIONES (TABLA RESUMEN)

Convención de estado: ✅ implementada · ⚠️ inconsistencia/parcial · ❌ no implementada en el código analizado.

| Campo | Regla / Regex | Frontend | Backend PHP | Microservicio |
|---|---|---|---|---|
| Email (cliente/repartidor) | formato email | ✅ `login.js`, `compra.js` | ✅ `FILTER_VALIDATE_EMAIL` | ✅ |
| Contraseña cliente | ≥8, mayúscula, número, especial | ✅ `login.js:332-350` | ✅ `AuthController:107-111` | — |
| Contraseña repartidor | ≥8, may./min., número, especial | ✅ (PHP view + micro JS) | ✅ `RepartidorRegistroController:262-271` | ✅ |
| Términos y condiciones | checkbox obligatorio | ✅ | ✅ | ✅ (`acepta_terminos`) |
| Cédula (checkout) | `/^[0-9]{6,15}$/` | ✅ `compra.js:77-79` | (campo texto; valida indirectamente) | — |
| Teléfono (checkout) | `/^[0-9]{7,15}$/` | ✅ `compra.js:72-75` | — | — |
| Celular repartidor | `/^3\d{9}$/` | ✅ (view y micro) | ✅ `:246` | ✅ `strategy.py:51` |
| Tipo documento repartidor | `CC,CE,TI,PAS` | ✅ (select) | ✅ `:247` (map TI→OTRO, PAS→PASAPORTE) | ✅ |
| Número documento repartidor | `/^\d{5,12}$/` | ✅ | ✅ `:248` | ✅ |
| Edad repartidor | ≥ 18 | ✅ (fecha) | ✅ `:254` | (fecha) |
| Placa vehículo | PHP: `/^[A-Z]{3}-?\d{3,4}$/`; Python/JS: `ABC123` o `ABC12D` | ⚠️ (dos formatos distintos según capa) | ✅ `:274` | ⚠️ `strategy.py:97-99` y `script.js:48` |
| Categoría licencia | `A1,A2,B1,B2,B3,C1` | ✅ (select) | ✅ `:276` | ✅ |
| Tarjeta de propiedad | PHP: requerido; Python: `/^\d{6,15}$/` | ✅ | ✅ `:278` | ⚠️ (regex distinta) |
| Vigencias SOAT / tecnomecánica / licencia | fecha futura | ✅ | ✅ `:279-296` | ✅ |
| Número tarjeta (cliente) | 13-19 dígitos | — | ✅ `TarjetaController:34-38` | — |
| Dirección envío (checkout) | campos requeridos | ✅ `compra.js` | (Recálculo en crearPedido valida stock/precios) | — |
| Stock | cantidad ≤ stock / cantidad > 0 | ✅ (carrito JS) | ✅ `PedidoModel:45-60`, `CarritoController` | — |
| Cupón | vigencia, usos, mínimo | ✅ | ✅ `CuponController::validar` | — |
| Archivos repartidor | PDF/JPG/PNG, ≤ 10 MB, MIME real | ✅ (tamaño/MIME en JS de vista PHP) | ✅ `finfo` | ✅ (magic bytes + tamaño) |

---

## 20. ESTADOS DE LA APLICACIÓN

### 20.1 Estados de usuario (`usuarios.estado`, `angelow.sql:99`)

`pendiente` → `activo` → (`inactivo | suspendido | eliminado`). Transiciones repartidor: registro → `pendiente`; admin `aprobar()` → `activo`; `rechazar()` → `inactivo`; `suspender()` → `suspendido`; `activar()` → `activo`.

### 20.2 Estados de pedido

Múltiples fuentes coexisten (inconsistencia documentada):

| Fuente | Estados |
|---|---|
| `PedidoModel::ESTADOS_VALIDOS` (línea 205) | `pendiente, confirmado, procesando, listo, asignado, aceptado, recogido, en_camino, entregado, cancelado, reembolsado` |
| ENUM de BD `pedidos.estado` (`angelow.sql`) | `..., entregado, cancelado, reembolsado, rechazado` |
| `RepartidorPedidosController::updateStatus` (línea 249) | `pendiente, confirmada, cambio, devolucion, rechazada` |
| `Cliente/PedidosController::cancelar` (línea 80) | llama con `rechazada` (no válido para el modelo) |
| Máquina de estados microservicio (`dashboard_service.py:18-23`) | `asignado→[aceptado,recogido,en_camino,entregado]`; `aceptado→[recogido,en_camino,entregado]`; `recogido→[en_camino,entregado]`; `en_camino→[entregado]` |
| Trigger / SP `aceptar_pedido` | asigna repartidor y maneja aceptación |

### 20.3 Otros estados

| Entidad | Estados |
|---|---|
| `pedidos.estado_pago` | `pendiente, procesando, completado, fallido, reembolsado` |
| `solicitudes_repartidores.estado` | `pendiente, aprobada, rechazada, cancelada` |
| `documentos.estado` | `pendiente, aprobado, rechazado, vencido, por_vencer` |
| `notificaciones` (flag) | `leida: 0/1` |
| Permisos de repartidor | 10 permisos binarios en `permisos_repartidores` |
| Repartidor en línea | `usuarios.en_linea: 0/1` |

---

## 21. REQUISITOS DE INSTALACIÓN / HARDWARE

### 21.1 Aplicación web (PHP)

- **Servidor:** Apache (XAMPP) con módulo `mod_rewrite`.
- **PHP ≥ 8** (psr-4, named-group regex, tipos escalares, `readonly`, match — código moderno).
- **MySQL/MariaDB** con la base `angelow_db` importada desde `angelow.sql` (incluye triggers, procedimientos almacenados y vistas).
- **Composer** con dependencia mínima: `phpmailer/phpmailer ^7.0` (verificación: `composer.json`).
- **Permisos de escritura:** `storage/sessions`, `storage/logs`, `public/uploads/documentos/`.
- **`.env`** con las claves de §17.1 (DB, SMTP, APP_URL, MICROSERVICE_URL, GOOGLE, JWT_SECRET).
- Ubicación típica bajo `C:\xampp\htdocs\Angelow` con `APP_URL=http://localhost/Angelow/public`.

### 21.2 Microservicio (Python)

- **Python 3.11+**; dependencias (del código): `fastapi`, `uvicorn`, `sqlalchemy`, `pymysql`, `bcrypt`, `python-multipart`, `jinja2`, `pydantic` (+ `cryptography`/`PyJWT` si se usa en el servicio de dashboard para decodificar HS256).
- **SQLite** local (`repartidores.db`) + **MySQL de ANGELOW** para la sincronización/dashboard.
- **Puerto 8000** por defecto (configurado en `MICROSERVICE_URL`).
- `ADMIN_TOKEN` para las llamadas administrativas del microservicio.

### 21.3 Red / dominios

- `localhost` o 127.0.0.1 para Origin/Referer permitidos; el host de `APP_URL` se agrega a la whitelist CSRF.
- Puertos: 80/443 (Apache) y 8000 (uvicorn).
- Para Google Login: credenciales OAuth de Google (el ID visible por defecto en `app/Views/auth/login.php:16`).

### 21.4 Verificación básica de instalación

1. `public/.htaccess` habilitado (RewriteEngine).
2. `GET /` redirige a `/cargador` y luego a `/?from=loader`.
3. `GET /auth/login` renderiza el login; `POST /auth/login` responde JSON.
4. Microservicio: `GET http://127.0.0.1:8000/` sirve el registro del repartidor y `GET /health` responde OK.
5. Las tablas del microservicio se crean solas (`database.py` `create_all` en el lifespancde `main.py`).

---

*Anexo — Inventario de tablas y triggers del esquema `angelow.sql` (verificado):*

| Tipo | Nombre |
|---|---|
| Tablas principales | `usuarios`, `categorias`, `productos`, `variantes_producto`, `pedidos`, `detalles_pedido`, `historial_entregas`, `seguimiento_tiempo_real`, `rutas`, `notificaciones`, `carrito`, `favoritos`, `resenas`, `cupones`, `configuraciones`, `logs_actividad`, `vehiculos_repartidores`, `solicitudes_repartidores`, `documentos`, `permisos_repartidores`, `historial_repartidores`, `historial_pedidos_repartidor` |
| Tablas de cliente (migración `add_direcciones_tarjetas.sql`) | `direcciones`, `tarjetas_credito` |
| Triggers | `generar_numero_pedido` (BEFORE INSERT pedidos, `angelow.sql:1076-1102`), `actualizar_stats_repartidor` (AFTER INSERT historial_entregas, 1105-1147), `actualizar_stock_pedido` (AFTER INSERT detalles_pedido, 1150-1176), `notificar_nuevo_pedido` (AFTER INSERT pedidos, 1179-1221) |
| Procedimientos almacenados | `obtener_pedidos_disponibles`, `aceptar_pedido`, `actualizar_ubicacion`, `completar_entrega`, `obtener_estadisticas_repartidor` |
| Vistas | `vista_documentos_repartidores`, `vista_solicitudes_repartidores`, `vista_dashboard_repartidor_completo`, `vista_dashboard_repartidor`, `vista_seguimiento_pedidos`, `vista_productos_populares` |
| Migración de facturación | `999_drop_facturacion.sql` — elimina `facturas`, `facturas_detalle`, `facturas_historial` (módulo de factura retirado) |