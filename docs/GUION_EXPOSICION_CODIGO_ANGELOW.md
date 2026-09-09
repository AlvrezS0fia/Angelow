# GUION DE EXPOSICIÓN — CÓDIGO ANGELOW

> **Proyecto:** ANGELOW — Tienda en línea de ropa infantil (Colombia).
> **Para:** presentación oral ante evaluadores.
> **Formato:** guion hablado en primera persona, con bloques de código real para mostrar en pantalla y tips de qué hacer durante la demo.
> **Convención:** todo bloque señalado con `⭐ CÓDIGO CLAVE PARA EXPOSICIÓN` es código **literal del repositorio**; se indica `archivo:línea`. Nada está inventado ni "propuesto como si existiera".
> **Documento hermano:** `docs/DOCUMENTACION_TECNICA_ANGELOW.md` (análisis técnico completo).

---

## PARTE 0 — APERTURA (2 min)

> *Voz en off mientras corre el "cargador" animado que ya trae la app.*

**Hablar:** "Hola, buenos días. Hoy les voy a presentar **ANGELOW**, una tienda en línea de ropa infantil. Es un proyecto completo: tiene tienda pública para el cliente, panel de repartidor y panel de administrador. Y lo más interesante: no usa un framework comercial como Laravel, el MVC es nuestro, hecho a mano en PHP; y además tiene un **microservicio independiente en Python con FastAPI** encargado del registro de repartidores. Voy a recorrer el código por capas para que vean cómo está construido y por qué lo decidimos así."

**En pantalla:** abrir navegador en `http://localhost/Angelow/` mientras la animación del cargador pasa a la tienda.

---

## PARTE 1 — PUNTO DE ENTRADA (5 min)

### 1.1 El front controller

**Hablar:** "Toda la aplicación web entra por un solo archivo: `public/index.php`. Es el famoso *front controller*, el punto único de entrada. No hay nada que el navegador pueda tocar que no pase primero por aquí. El flujo, tal como lo documentamos en el propio código, es: navegador → `.htaccess` → `index.php` → sesión → variables de entorno → configuración → autoload → cookie de invitado → router → controlador → vista."

**En pantalla:** abrir `public/index.php`, mostrar el bloque de cabecera (líneas 3-24).

```php
// FLUJO: navegador → .htaccess (Apache) → index.php
//        → [1] sesión PHP → [2] .env → [3] config → [4] autoload
//        → [5] cookie de invitado → [6] Router::dispatch() → controlador → vista/JSON.
```

**Hablar:** "El `.htaccess` redirige todo lo que no sea un archivo o directorio real a `index.php`."

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### 1.2 Sesión endurecida y cookie de invitado

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `public/index.php:42-54`

**Hablar:** "Antes de abrir la sesión la endurecemos: Id de sesión solo por cookies, modo estricto para evitar la fijación de sesión, cookie `HttpOnly` y `SameSite=Lax` para bloquear CSRF basado en cookies, y `Secure` cuando hay HTTPS."

```php
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `public/index.php:116-125`

**Hablar:** "Una decisión importante: un visitante **sin iniciar sesión** puede armar un carrito. Le asignamos una cookie `cart_session` de 30 días con un id aleatorio de 32 hexadecimales. En la tabla `carrito` los productos se guardan bajo esa `session_id`, así que el carrito sobrevive al cerrar el navegador."

```php
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['cart_session'])) {
    $session_id = bin2hex(random_bytes(16));
    setcookie('cart_session', $session_id, [
        'expires' => time() + (30 * 24 * 3600),
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
}
```

### 1.3 Protección CSRF por Origin/Referer

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `public/index.php:139-181`

**Hablar:** "Teníamos dos problemas: no queríamos depender de un token por formulario y necesitábamos proteger el login. La solución fue un chequeo *global*: toda petición que muta estado (POST, PUT, DELETE, PATCH) debe venir del mismo sitio. Si el navegador envía un `Origin` o `Referer` cuyo host no es el nuestro, respondemos `403`. Complementa el `SameSite=Lax` de las cookies."

```php
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    if ($origin !== null || $referer !== null) {
        $hostsPermitidos = ['localhost', '127.0.0.1', '[::1]', '::1'];
        $hostApp = parse_url(APP_URL, PHP_URL_HOST);
        if ($hostApp !== null && $hostApp !== false) {
            $hostsPermitidos[] = $hostApp;
        }
        // ...valida el host del Origin/Referer contra $hostsPermitidos...
        if ($origin !== null && !$origenOk($origin)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo '{"error":"Origen no permitido"}';
            exit;
        }
    }
}
```

**Momento de demo:** "Miren esto en la Consola del navegador" → enviar un `fetch('http://localhost/Angelow/api/carrito', {method:'POST', headers:{'Origin':'http://evil.com'}})` y mostrar el `403 {"error":"Origen no permitido"}` que responde todo el sistema (vale para cualquier endpoint).

---

## PARTE 2 — ENRUTADOR Y BASE DE DATOS (4 min)

### 2.1 Router propio

**Hablar:** "El `Router` es una clase nuestra, simple pero completa: convierte `{param}` en expresiones regulares con nombre, y `dispatch()` instancia el controlador y le pasa los parámetros de la URL."

**Ruta del código:** `app/Core/Router.php:16-26` (método `add`) y `app/Core/Router.php:40-75` (método `dispatch`).

**Hablar:** "El mapa de rutas está centralizado en `config/routes.php`, con **123 rutas**. Miren la proporcionalidad del proyecto con solo la lista de controladores."

**En pantalla:** buscar en el editor `Controller` dentro de `config/routes.php`.

### 2.2 Acceso a datos seguro

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `app/Core/Database.php:46-58`

**Hablar:** "La capa de datos usa PDO y el 100 % de las consultas son **sentencias preparadas**. Lo más importante, y lo dejamos escrito como política en el propio código, es que los valores nunca se concatenan en el SQL: eso neutraliza la inyección SQL de raíz."

```php
// SIEMPRE sentencias preparadas, lo que neutraliza la inyeccion SQL
// (los valores jamas se concatenan en el SQL).
public static function query(string $sql, array $params = []) {
    $stmt = self::getInstance()->getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
```

---

## PARTE 3 — AUTENTICACIÓN (6 min)

### 3.1 Claves de sesión

**Hablar:** "El helper de autenticación `Auth` es la puerta de roles: `user()`, `check()`, `rol()`, `isAdmin()`, `isRepartidor()`, `homeForRole()`."

**Ruta del código:** `app/Core/Auth.php:10-13` (`user()`), `app/Core/Auth.php:61-64` (`isAdmin()`).

### 3.2 Login con límite de intentos y bcrypt

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `app/Controllers/AuthController.php:25-89`

**Hablar:** "El login procesa JSON, valida campos, aplica un **rate limiter**: máximo 5 intentos por dirección IP y correo en 15 minutos; si se supera, responde `429`. La contraseña se verifica con `password_verify` contra un hash **bcrypt**, y al autenticar se regenera el id de sesión para evitar fijación."

```php
$rlKey = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . strtolower(trim($email));
if (RateLimiter::tooMany($rlKey, 5, 900)) {
    $this->json(['success' => false, 'message' => 'Demasiados intentos. Espera 15 minutos.'], 429);
    return;
}

$user = $this->usuarioModel->findByEmail($email);
// CAPA 7 ISO-OSI (Aplicación): verificación de hash bcrypt (password_verify).
if (!$user || !password_verify($password, $user['password_hash'])) {
    $this->json(['success' => false, 'message' => 'Credenciales incorrectas']);
    return;
}

RateLimiter::clear($rlKey);

$_SESSION['user'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'nombre' => $user['nombre'],
    'rol' => $user['rol'] ?? 'cliente',
    'estado' => $user['estado'] ?? 'activo'
];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}
```

**Hablar:** "El registró exige contraseñas fuertes desde el backend: mínimo 8 caracteres, una mayúscula, un número y un carácter especial." → `app/Controllers/AuthController.php:107-111`.

```php
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
    $this->json(['success' => false, 'message' => 'La contraseña no cumple los requisitos']);
    return;
}
```

**Momento de demo:** en la pestaña de red mostrar la cabecera `HTTP/1.1 429 Too Many Requests` al forzar varios login erróneos desde `public/assets/js/login.js`.

---

## PARTE 4 — CARRITO Y COMPRA (6 min)

### 4.1 Agregar al carrito

**Hablar:** "El carrito funciona vía AJAX. La cookie `cart_session` que ya vimos identifica al invitado; cuando el usuario inicia sesión se puede fusionar o recuperar su carrito desde la BD usando su `usuario_id`."

**Rutas del código:** `app/Controllers/Cliente/CarritoController.php` (controlador) y `app/Models/CarritoModel.php` (modelo).

**Momento de demo:** dejar el carrito con un producto **sin** iniciar sesión, recargar, y mostrar que el contador del carrito se mantiene (persistencia por cookie).

### 4.2 Creación del pedido: la función estrella

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `app/Models/PedidoModel.php:22-162` (`crearPedido`)

**Hablar:** "Esta es una de las partes que más me gusta mostrar. Crear un pedido es una **transacción**: o se hace todo o no se hace nada. Dos decisiones de diseño importantes:

1. **Los precios NO vienen del cliente.** El frontend envía `producto_id` y `cantidad`; el servidor consulta la BD, valida stock, y recalcula subtotal, descuento y total. Nadie puede comprar más barato manipulando el JavaScript.
2. El número de pedido `ORD-2026-0001` se genera en PHP con `MAX(SUBSTRING(...)) + 1`; y además existe un **trigger en la base de datos** que genera el mismo número si alguien inserta un registro sin él: redundancia entre capas."

```php
$this->db->beginTransaction();
try {
    $numeroPedido = $this->generarNumeroPedido();

    // Obtener precios reales desde la BD y calcular subtotal real
    $subtotalReal = 0;
    $productosValidados = [];
    $stmtPrice = $this->db->prepare("SELECT id, precio, nombre, stock_total FROM productos WHERE id = :id");

    foreach ($data['productos'] as $producto) {
        $pid = (int)($producto['producto_id'] ?? 0);
        $cant = (int)($producto['cantidad'] ?? 0);
        if ($pid <= 0)  throw new \Exception("ID de producto inválido.");
        if ($cant <= 0) throw new \Exception("La cantidad del producto debe ser mayor a 0.");

        $stmtPrice->execute(['id' => $pid]);
        $prod = $stmtPrice->fetch();
        if (!$prod) throw new \Exception("Producto ID {$pid} no encontrado en la base de datos.");
        if ((int)$prod['stock_total'] < $cant) {
            throw new \Exception("Stock insuficiente para \"{$prod['nombre']}\": solicita {$cant} pero hay {$prod['stock_total']} unidades.");
        }

        $precioReal = (float)$prod['precio'];
        $subtotalProducto = $precioReal * $cant;
        $subtotalReal += $subtotalProducto;
        $productosValidados[] = [ ... ];
    }

    // Recalcular totals en el servidor
    $totalReal = $subtotalReal - $descuento + $costoEnvio;
    // ...
    $this->db->commit();
    return ['id' => $pedidoId, 'numero_pedido' => $numeroPedido];
} catch (\Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

**Hablar:** "Y de aquí sale el **descuento de stock automático**, pero no lo hace PHP: lo hace un trigger de MySQL `actualizar_stock_pedido` al insertar los detalles. La base de datos es la que garantiza la consistencia."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `angelow.sql:1150-1176`

```sql
CREATE TRIGGER actualizar_stock_pedido
AFTER INSERT ON detalles_pedido
FOR EACH ROW
BEGIN
    UPDATE productos
    SET stock_total = stock_total - NEW.cantidad,
        total_vendidos = total_vendidos + NEW.cantidad
    WHERE id = NEW.producto_id;

    IF NEW.variante_id IS NOT NULL THEN
        UPDATE variantes_producto
        SET stock = stock - NEW.cantidad
        WHERE id = NEW.variante_id;
    END IF;
END//
```

---

## PARTE 5 — ESTADOS DE PEDIDO Y REPARTIDOR (5 min)

### 5.1 Los estados están validados

**Hablar:** "Los estados válidos de un pedido están definidos como constante y el modelo valida contra esa lista; si alguien pasa un estado no válido, el modelo lanza excepción."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `app/Models/PedidoModel.php:205-215`

```php
const ESTADOS_VALIDOS = ['pendiente', 'confirmado', 'procesando', 'listo', 'asignado', 'aceptado', 'recogido', 'en_camino', 'entregado', 'cancelado', 'reembolsado'];

public function updateEstado($pedidoId, $estado) {
    $estado = strtolower(trim($estado));
    if (!in_array($estado, self::ESTADOS_VALIDOS)) {
        throw new \Exception("Estado inválido: {$estado}. Estados permitidos: " . implode(', ', self::ESTADOS_VALIDOS));
    }
    $sql = "UPDATE pedidos SET estado = :estado WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute(['estado' => $estado, 'id' => $pedidoId]);
}
```

### 5.2 Registro de repartidor (validación estricta)

**Hablar:** "El registro de repartidor en PHP valida rigurosamente: tipos de documento válidos (CC, CE, TI, PAS), tipos de vehículo, categorías de licencia, y las placas con regex. Los documentos deben ser JPG, PNG o PDF, de máximo 10 MB, y el MIME real se detecta con `finfo`, no con la extensión."

**Ruta del código (verificado):** `app/Controllers/Api/RepartidorRegistroController.php:28` (`TIPOS_DOCUMENTOS_VALIDOS = ['CC', 'CE', 'TI', 'PAS']`), `:37` (`MAX_BYTES_DOCUMENTO = 10 * 1024 * 1024`), `:274` validación de placa con `preg_match('/^[A-Z]{3}-?\d{3,4}$/', $placa)` (mensaje "Placa inválida (formato: ABC-123)"); el MIME se valida con `finfo`. La misma política se repite en `app/Controllers/RepartidorAuthController.php:197`.

---

## PARTE 6 — EL MICROSERVICIO EN PYTHON (8 min)

**Hablar:** "Esta es la parte que nos diferencia. El registro y el panel de repartidor no viven dentro del PHP: son un **microservicio independiente** en Python con FastAPI. Corre por separado, en su propia base SQLite, y se encarga de registrar solicitudes, validar documentos y aprobar repartidores."

### 6.1 El punto de entrada: `main.py`

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `repartidor-service/main.py:53-78`

**Hablar:** "El microservicio también aplica cabeceras de seguridad a todas las respuestas y una **Content Security Policy** que restringe de dónde pueden venir scripts, imágenes y conexiones. Las llamadas API se responden con `Cache-Control: no-store`, porque son datos sensibles de repartidores."

```python
class SecurityHeadersMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        response: Response = await call_next(request)
        response.headers["X-Content-Type-Options"] = "nosniff"
        response.headers["X-Frame-Options"] = "DENY"
        response.headers["Referrer-Policy"] = "strict-origin-when-cross-origin"
        response.headers["Permissions-Policy"] = "camera=(), microphone=(), geolocation=()"
        response.headers["X-XSS-Protection"] = "1; mode=block"
        response.headers["Content-Security-Policy"] = (
            "default-src 'self'; "
            "script-src 'self' 'unsafe-inline'; "
            "style-src 'self' 'unsafe-inline'; "
            "img-src 'self' https://*.tile.openstreetmap.org data:; "
            "connect-src 'self' https://router.project-osrm.org https://nominatim.openstreetmap.org; "
            "font-src 'self' https://cdnjs.cloudflare.com; "
            "frame-ancestors 'none'"
        )
        if request.url.path.startswith("/api/"):
            response.headers["Cache-Control"] = "no-store, no-cache, must-revalidate"
            response.headers["Pragma"] = "no-cache"
        return response
```

**Hablar:** "También protegemos el microservicio con un middleware que limita el **tamaño del cuerpo** de la petición (`413 si excede`), y validamos en el arranque que el secreto del JWT exista y sea suficientemente largo."

**Ruta del código:** `repartidor-service/main.py:115-141` (límite de tamaño y CORS permitido).

### 6.2 El registro usa patrones de diseño GOF

**Hablar:** "Aquí es donde demostramos ingeniería de software. El registro completo de un repartidor está implementado con **10 patrones GOF**, en carpetas separadas por tipo: creacionales, estructurales y de comportamiento."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `repartidor-service/patterns/structural/facade.py:59-105`

**Hablar:** "El **Facade** expone un único método `register()` que internamente orquesta: validación, persistencia, guardado de documentos y notificaciones. Todo el proceso de registro pasa por un solo punto."

```python
def register(self, form_data: dict, files: dict) -> dict:
    """Punto único de entrada del registro. Retorna dict de resultado."""
    errors = self._validate(form_data, files)
    if errors:
        return {"success": False, "message": " | ".join(errors)}
    user = None
    try:
        user, solicitud_id = self._persist(form_data, files)
    except ValueError as exc:
        self.db.rollback()
        return {"success": False, "message": str(exc)}
    except IntegrityError:
        self.db.rollback()
        return {"success": False, "message": (...)}
    try:
        self._notify(user, self.solicitud_repo.get_by_id(solicitud_id))
    finally:
        # SEGURIDAD: Limpiar la contraseña en claro del objeto en memoria
        if user is not None and hasattr(user, "angelow_plain_password"):
            ...
    return {
        "success": True,
        "message": "Solicitud enviada. Un administrador revisará tus datos y te aprobará el acceso.",
        "pending": True,
        "redirect": "/repartidor/dashboard",
        "id": user.id,
        "email": user.email,
    }
```

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `repartidor-service/patterns/behavioral/strategy.py:14-77`

**Hablar:** "El patrón **Strategy** valida por etapas. Cada estrategia implementa la misma interfaz `validate(data)`; si mañana cambian las reglas del celular colombiano, cambiamos solo esa estrategia."

```python
class ValidationStrategy(ABC):
    """Contrato: validate(data) -> list[str] de errores."""
    @abstractmethod
    def validate(self, data: dict) -> list[str]:
        raise NotImplementedError

class PersonalDataValidation(ValidationStrategy):
    def validate(self, data: dict) -> list[str]:
        # ...
        if not celular or not re.match(r"^3\d{9}$", celular):
            errors.append("Celular inválido (debe iniciar en 3 y tener 10 dígitos)")
        if tipodoc not in self._cfg.TIPOS_DOCUMENTOS_VALIDOS:
            errors.append("Tipo de documento inválido")
        if not numdoc or not re.match(r"^\d{5,12}$", numdoc):
            errors.append("Número de documento inválido (5-12 dígitos)")
        if len(passwd) < 8:
            errors.append("Contraseña: mínimo 8 caracteres")
        # ...
        return errors
```

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `repartidor-service/patterns/behavioral/chain_of_responsibility.py:134-142`

**Hablar:** "Los documentos pasan por una **cadena de responsabilidad**: existe → tamaño ≤ 10 MB → MIME real (por *magic bytes*, como `finfo` en PHP) → guardado en disco → inserción en BD. Si cualquier eslabón falla, la cadena se corta y el documento queda rechazado."

```python
def build_document_chain(doc_repo, solicitud_id: Optional[int]) -> Handler:
    """Fábrica de la cadena completa (FileExists → ... → DatabaseInsert)."""
    first = FileExistsHandler()
    tail = first
    tail = tail.set_next(FileSizeHandler())
    tail = tail.set_next(MimeValidationHandler())
    tail = tail.set_next(FileSaveHandler())
    tail = tail.set_next(DatabaseInsertHandler(doc_repo, solicitud_id))
    return first
```

**Momento de demo:** abrir `repartidor-service/patterns/` y mostrar las carpetas `creational/`, `structural/`, `behavioral/`, listando los 11 archivos de patrones (Singleton, Factory, Builder, Facade, Adapter, Decorator, Strategy, Chain of Responsibility, Observer, Template Method) más `repositories/` (patrón Repository).

---

## PARTE 7 — DASHBOARD DEL REPARTIDOR Y MÁQUINA DE ESTADOS (5 min)

**Hablar:** "El panel del repartidor lee y escribe directamente en el MySQL de ANGELOW. Para saber quién es el repartidor, decodifica el **JWT** que emitió ANGELOW con el secreto compartido; si no hay secreto configurado, falla en modo seguro (no autoriza a nadie)."

**Ruta del código:** `repartidor-service/services/dashboard_service.py:42-55` (`decode_token`).

**Hablar:** "Y algo muy elegante: las transiciones de estado de una entrega están modeladas como una **máquina de estados**. Un pedido `asignado` solo puede ir a `aceptado`, `recogido`, `en_camino` o `entregado`; no se puede saltar de `recogido` a `entregado` sin pasar por `en_camino`... lo miren:"

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `repartidor-service/services/dashboard_service.py:18-23`

```python
TRANSICIONES_PERMITIDAS = {
    "asignado": ["aceptado", "recogido", "en_camino", "entregado"],
    "aceptado": ["recogido", "en_camino", "entregado"],
    "recogido": ["en_camino", "entregado"],
    "en_camino": ["entregado"],
}
```

**Ruta del código:** la verificación en `dashboard_service.py:259-265` ("Transicion no permitida de ... a ..."). Al llegar a `entregado` se calcula la ganancia y se inserta en `historial_entregas`, lo que dispara a su vez el trigger `actualizar_stats_repartidor` en MySQL.

---

## PARTE 8 — PANEL DE ADMINISTRADOR (3 min)

**Hablar:** "El administrador tiene su propio panel. El `DashboardController` arma el resumen con indicadores reales: totales de ventas, pedidos, clientes y repartidores. Para acciones compuestas, por ejemplo repartidores, se usan **transacciones** y un sistema de **permisos granulares** con valores por defecto."

**Rutas del código:**
- Resumen con consultas agrupadas: `app/Controllers/Admin/DashboardController.php`.
- Transacciones en administración de repartidores: `app/Controllers/Admin/AdminRepartidorController.php`.
- Permisos por defecto (10 acciones) al crear administradores/repartidores: método `grantDefaultPermissions` del controlador admin.

**Momento de demo:** entrar al panel admin y mostrar el menú (panel, pedidos, clientes, productos, categorías, repartidores, permisos) y el conteo de indicadores del resumen.

---

## PARTE 9 — BASE DE DATOS (3 min)

**Hablar:** "El esquema `angelow.sql` tiene 20 tablas, y lo más bonito son sus **4 triggers**: `generar_numero_pedido`, `actualizar_stock_pedido`, `actualizar_stats_repartidor` y `notificar_nuevo_pedido`; además de procedimientos almacenados y vistas. Dejamos la lógica crítica del negocio en el motor de datos."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `angelow.sql:1076-1102` (backup del número de pedido)

```sql
CREATE TRIGGER generar_numero_pedido
BEFORE INSERT ON pedidos
FOR EACH ROW
BEGIN
    DECLARE year_val INT;
    DECLARE seq_val INT;
    SET year_val = YEAR(NOW());
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 10) AS UNSIGNED)), 0) + 1 INTO seq_val
    FROM pedidos
    WHERE numero_pedido LIKE CONCAT('ORD-', year_val, '-%');

    IF NEW.numero_pedido IS NULL THEN
        SET NEW.numero_pedido = CONCAT('ORD-', year_val, '-', LPAD(seq_val, 4, '0'));
    END IF;
END//
```

---

## PARTE 10 — SEGURIDAD EN UNA SOLA PANTALLA (4 min)

**Hablar:** "Recorran conmigo las defensas que ya vimos, de abajo hacia arriba:"

1. **Inyección SQL:** 100 % sentencias preparadas PDO. → `Database.php`
2. **Contraseñas:** bcrypt (`password_hash`/`password_verify`). → `AuthController.php:44`
3. **Fuerza bruta:** rate limiter por IP+correo (login 5/15 min, recuperación 3/1 h, registro repartidor 5/15 min). Respuesta `429`.
4. **Fijación de sesión:** `use_strict_mode` + regeneración de id al autenticar.
5. **CSRF:** `SameSite=Lax` + chequeo global Origin/Referer con respuesta `403`.
6. **XSS reflejado:** helpers de sanitización + cabeceras `nosniff`, `X-Frame-Options: DENY`.
7. **Microservicio:** CSP, límite de tamaño de cuerpo, secretos configurados por entorno, JWT HS256 con blacklist de `jti` tras logout/expiración, y endpoints de administración restringidos a `X-Admin-Token` o localhost.

**Ruta del código:** `app/Core/RateLimiter.php` (almacenamiento en `storage/logs/rl_<md5(firma)>.json`), `app/Core/JWTHelper.php` (emisión HS256), `repartidor-service/security/blacklist.py`, `repartidor-service/routes/repartidor.py` (dependencia `_require_admin`).

**Momento de demo (rápido):** mostrar `storage/logs/rl_*.json` (un archivo por intentos) después de la prueba del 429.

---

## PARTE 11 — TRANSPARENCIA: DEBILIDADES CONOCIDAS (2 min — suma puntos)

**Hablar:** "No todo es perfecto, y mientras analizábamos el código encontramos cosas que mejorar. Para mí es importante ser honesto sobre esto:"

1. `googleLogin()` (`app/Controllers/AuthController.php:181`) **no verifica la firma, ni el `nonce`, ni la audiencia** del `id_token` de Google: crea/autentica con los datos que lleguen en `$_POST['user_data']`. Recomendación: validar firma con `cryptographic` API de Google antes de confiar.
2. El checkbox "Recordar mi cuenta" (`remember`) se envía desde el frontend, pero `AuthController::login()` **no lo procesa**: no existe cookie de recordación.
3. Módulo de **facturación no existe**: en `database/migrations/999_drop_facturacion.sql` las tablas de facturas se eliminan; solo queda el enlace "Mis Facturas" en el menú del perfil.
4. Pequeñas **inconsistencias de estados**: `Cliente/PedidosController::cancelar` usa el estado `rechazada`, que **no está** en `ESTADOS_VALIDOS` (existe `cancelado`); y `RepartidorPedidosController::updateStatus` maneja su propia lista de estados distinta a la constante del modelo.
5. La placa se valida distinto entre PHP (`/^[A-Z]{3}-?\d{3,4}$/`) y el microservicio (`ABC123` o `ABC12D` tras quitar guiones).

**Hablar:** "Estas observaciones las dejamos documentadas en `DOCUMENTACION_TECNICA_ANGELOW.md`, sección de Seguridad."

---

## PARTE 12 — CIERRE (1 min)

**Hablar:** "En resumen: ANGELOW es, primero, una tienda funcional de punta a punta —el cliente compra, el repartidor entrega y el administrador gobierna—. Pero además es un proyecto de **ingeniería de software**: MVC hecho a mano, seguridad en capas, separación en microservicio con 10 patrones GOF documentados, y reglas de negocio garantizadas incluso desde la base de datos. Y dejo dicho con claridad lo que falta y lo que se puede mejorar. Muchas gracias, con gusto respondo preguntas."

---

## ANEXO — GUION RÁPIDO DE DEMO (orden de pantallas)

1. Cargador animado → tienda (`http://localhost/Angelow/`).
2. Carrito de invitado (agregar producto sin sesión → recargar → sigue ahí).
3. Registro/login (mostrar error `429` e archivo `storage/logs/rl_*.json`).
4. Simular pedido echo desde consola con `Origin: http://evil.com` → `403 {"error":"Origen no permitido"}`.
5. Crear un pedido real y mostrar en BD: `numero_pedido` `ORD-YYYY-NNNN`, `stock` ya descontado (trigger).
6. Registro de repartidor en el microservicio (`http://127.0.0.1:8000`): mostrar validación de placas/documentos en vivo.
7. Dashboard del repartidor: aceptar un pedido y avanzar por la máquina de estados hasta `entregado` (ver `historial_entregas` y trigger de stats).
8. Panel admin: lista de solicitudes, aprobación, y permisos asignados.
9. Cierre con las debilidades conocidas.