# DOCUMENTACIÓN TÉCNICA — MODELOS (ANGELOW)

> **Proyecto:** ANGELOW — Tienda en línea de ropa infantil (Colombia).
> **Alcance:** documentación método por método de la capa de modelos en `app\Models\` (8 archivos) y sus dependencias clave `app\Core\Model.php` (CRUD genérico) y `app\Core\Database.php` (conexión PDO singleton). 100 % basada en el código real; los números de línea se verificaron contra los archivos. Cuando un mecanismo no existe se indica con la nota `NO SE ENCONTRÓ ESTE MECANISMO EN EL CÓDIGO ANALIZADO.`
> **Referencias:** formato `app/Models/Archivo.php:línea` (métodos) y `app/Controllers/...:línea` (quién llama).
> **Documentos hermanos:** `docs/DOCUMENTACION_TECNICA_ANGELOW.md` (sistema), `docs/DOCUMENTACION_TECNICA_CATEGORIAS_SUBCATEGORIAS.md`, `docs/DOCUMENTACION_TECNICA_JAVASCRIPT_FRONTEND.md`.

---

## 0. HALLAZGO TRANSVERSAL: DOS ESTILOS DE ACCESO A DATOS Y UN MODELO HUÉRFANO

La capa de modelos **no es uniforme**: conviven dos estilos de conexión/consulta y un modelo que **no se usa en ninguna parte**.

| # | Hallazgo (verificado) | Evidencia |
|---|---|---|
| 1 | **`CarritoModel` NO se instancia en ningún controlador.** El carrito real está implementado en `Api\CarritoController.php` con `Database::query()` directo (6 acciones: index, agregar, actualizar, eliminar, sincronizar, vaciar). `CarritoModel` queda como código duplicado/huérfano. | grep `new CarritoModel` → 0 resultados; `app/Controllers/Api/CarritoController.php:8-283` |
| 2 | **Dos estilos de acceso a BD:** patrón **instancia** (constructor lee el PDO del singleton y usa placeholders nombrados `:campo`) en 6 modelos (`CategoriaModel`, `DireccionModel`, `PedidoModel`, `ProductoModel`, `TarjetaModel`, `UsuarioModel`) vs. patrón **estático** (`Database::query()` con placeholders posicionales `?`) en `CarritoModel`, `Favorito` y en la clase base `Model`. | constructores, p. ej. `CategoriaModel.php:10-12` vs. `Favorito.php:16` |
| 3 | **`Favorito` rompe la convención de nombre:** no lleva el sufijo `Model` (clase `Favorito`, archivo `Favorito.php`). Es el único caso. | `app/Models/Favorito.php:6` |
| 4 | **Un solo modelo hereda del CRUD genérico:** `CarritoModel extends Model` (`CarritoModel.php:8`). Los otros 7 reimplementan su propio SQL a mano (aunque 5 de ellos comparten el patrón "por usuario"). | `CarritoModel.php:8`; resto no usa `use App\Core\Model` |
| 5 | **`PedidoModel` recalcula todo en el servidor:** no confía en precios/cantidades del cliente; relee precio y stock de BD, valida stock y calcula subtotal real, todo dentro de una **transacción** con rollback. Único modelo con transacción. | `PedidoModel.php:22-162` |
| 6 | **Los modelos de "tarjeta" y "usuario" ocultan secretos por diseño:** `TarjetaModel` guarda solo `numero_enmascarado` (últimos 4, `:51`); `UsuarioModel::getAll()` no devuelve `password_hash` ni `reset_token` (`:139`). | ver columnas abajo |
| 7 | **Borrado físico vs. recomendado lógico:** `UsuarioModel::delete()` hace `DELETE` físico (`:180-183`) aunque `docs/SEGURIDAD.md` recomienda borrado lógico; en contraste, `TarjetaModel::eliminar()` sí es lógico (`activa = 0`, `:91-99`). | `UsuarioModel.php:180`; `TarjetaModel.php:92` |

> El patrón del **Singleton** (`Database`) y la **herencia** (`CarritoModel extends Model`) son los dos ejemplos de conceptos POO visibles en esta capa — ver secciones 1 y 2.

---

## 1. MAPA DE MODELOS

| Modelo | Archivo | Líneas | Tabla(s) BD | Estilo de acceso | ¿Se instancia? (dónde) |
|---|---|---|---|---|---|
| `Database` (singleton PDO) | `app/Core/Database.php` | 55 | — (conexión) | estático `::getInstance()` / `::query()` | Global (todos) |
| `Model` (CRUD genérico) | `app/Core/Model.php` | 49 | dinámica ($table) | estático `Database::query` | (no directo; padre de `CarritoModel`) |
| `CarritoModel` | `app/Models/CarritoModel.php` | 114 | `carrito` | hereda + estático | ❌ **NO SE INSTANCIA EN NINGÚN CONTROLADOR** |
| `CategoriaModel` | `app/Models/CategoriaModel.php` | 101 | `categorias` | instancia | `Api\CategoriesController.php:11` |
| `DireccionModel` | `app/Models/DireccionModel.php` | 122 | `direcciones` | instancia | `Cliente\DireccionController.php:11` |
| `Favorito` | `app/Models/Favorito.php` | 50 | `favoritos` | estático | `Api\FavoritoController.php:30` |
| `PedidoModel` | `app/Models/PedidoModel.php` | 240 | `pedidos`, `detalles_pedido` | instancia | `CompraController.php:61`, `Cliente\PedidosController.php:15`, `Admin\PedidosController.php:12` |
| `ProductoModel` | `app/Models/ProductoModel.php` | 141 | `productos`, `categorias` (JOIN) | instancia | `Api\ProductsController.php:13` |
| `TarjetaModel` | `app/Models/TarjetaModel.php` | 133 | `tarjetas_credito` | instancia | `Cliente\TarjetaController.php:11` |
| `UsuarioModel` | `app/Models/UsuarioModel.php` | 184 | `usuarios` | instancia | `AuthController.php:13`, `RepartidorAuthController.php:14`, `Api\RepartidorRegistroController.php:48`, `Cliente\PerfilApiController.php:11`, `Admin\ClientesController.php:13` |

---

## 2. LA BASE DE TODO: `Database` (Singleton) Y `Model` (CRUD Genérico)

### 2.1 `app/Core/Database.php` — conexión PDO única (patrón Singleton)

| Línea | Miembro | Qué hace |
|---|---|---|
| 9-14 | `class Database` | Propiedades `private static ?Database $instance` (13) y `private PDO $conn` (14). Todo privado → nadie toca la conexión por fuera (encapsulamiento). |
| 20-30 | `private function __construct()` | Crea la única conexión PDO con constantes `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET` (definidas en `config/database.php` desde `.env`). Activa `ERRMODE_EXCEPTION` (24) y `FETCH_ASSOC` (25). Ante error: `error_log` + `die()` (27-28). Al ser `private`, no se puede instanciar `new Database()` (25 significado: patrón Singleton). |
| 34-39 | `public static function getInstance()` | Devuelve la única instancia; la crea la primera vez y la reutiliza siempre. |
| 41-43 | `public function getConnection()` | Expone el objeto `PDO` crudo (lo usan los modelos "de instancia"). |
| 49-54 | `public static function query($sql, $params = [])` | Prepara + ejecuta una consulta con parámetros y devuelve un `PDOStatement` listo para `fetch()`/`fetchAll()`. Usado por `Model`, `CarritoModel` y `Favorito`. |

**Concepto POO:** encapsulamiento (privados) + patrón de diseño Singleton → **una sola conexión compartida por toda la app**.

### 2.2 `app/Core/Model.php` — CRUD genérico reutilizable

| Línea | Miembro | Qué hace |
|---|---|---|
| 9-10 | `protected string $table; protected PDO $db;` | Protegidos: visibles solo para la clase y sus hijas (encapsulamiento). La hija define `$table`. |
| 12-14 | `__construct()` | Toma la conexión del singleton (`Database::getInstance()->getConnection()`). |
| 16-18 | `getAll()` | `SELECT * FROM {$this->table}` → array de filas. |
| 22-24 | `getById($id)` | `SELECT * ... WHERE id = :id` → una fila o `false`. |
| 28-35 | `create(array $data)` | Genera el `INSERT` con columnas y placeholders de forma dinámica a partir de las **llaves** del array; devuelve el `lastInsertId()`. |
| 39-43 | `update($id, $data)` | Genera el `UPDATE` dinámico (`col = :col, ...`) con `WHERE id = :id`; devuelve `bool` (según `rowCount()`). |
| 46-48 | `delete($id)` | `DELETE ... WHERE id = :id` → `bool`. |

**Quién la usa:** únicamente `CarritoModel` (`CarritoModel.php:5,8`). El resto de modelos no hereda de esta clase.

---

## 3. `CarritoModel` — LA CAPA HUÉRFANA (carrito)

- **Archivo:** `app/Models/CarritoModel.php` (114 líneas). Único modelo que hereda (`extends Model`, línea 8) del CRUD genérico.
- **Tabla:** `carrito` (`protected string $table = 'carrito'`, línea 11).
- **Base de datos:** usa el método estático heredado/`Database::query` con marcadores posicionales `?`.
- ⚠️ **NO SE ENCONTRÓ INSTANCIACIÓN EN NINGÚN CONTROLADOR** (verificado por grep global: 0 coincidencias de `new CarritoModel`). La funcionalidad real de carrito vive en `Api\CarritoController.php` con `Database::query()` directo, sin modelo.

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 16-22 | `getByUsuario($usuario_id)` | `SELECT c.*, p.nombre, p.precio, p.imagenes FROM carrito c JOIN productos p ON c.producto_id = p.id WHERE c.usuario_id = ?` → carrito del cliente logueado. | — (sin uso) |
| 27-33 | `getBySession($session_id)` | Igual pero por `session_id` (carrito invitado). | — (sin uso) |
| 38-63 | `addOrUpdate($data)` | Busca si el ítem ya existe (mismo producto + variante + talla, tanto para usuario como para invitado). Si existe: `UPDATE cantidad = cantidad + ?`; si no: `INSERT` (incluye `variante_id`, `color_seleccionado`). | — (sin uso) |
| 70-81 | `remove($item_id, $usuario_id = null, $session_id = null)` | `DELETE` del ítem, restringiendo por usuario **o** sesión según cuál se pase. | — (sin uso) |
| 87-91 | `clear($usuario_id = null, $session_id = null)` | Vacía el carrito completo del usuario o de la sesión. | — (sin uso) |
| 96-113 | `mergeGuestCart($session_id, $usuario_id)` | Migra el carrito de invitado al usuario logueado: suma cantidades (vía `addOrUpdate`) y luego borra el carrito invitado. | — (sin uso) |

**Diferencia con el controlador que SÍ se usa (`Api/CarritoController.php`):**

| Aspecto | `CarritoModel` (huérfano) | `Api/CarritoController.php` (en producción) |
|---|---|---|
| Campos que maneja | `variante_id`, `color_seleccionado`, `talla_seleccionada` | solo `talla_seleccionada` (sin variante ni color) |
| Validación de stock | ❌ NO valida stock | ✅ valida stock (`:87-90,105-108,118-121,176-179`) |
| Merge invitado→usuario | método `mergeGuestCart` | método `sincronizar()` (`:221-261`, con cookie `cart_session`) |
| Precio | lo recibe en `$data` | lo toma de BD: `$producto['precio']` (`:126`) |
| Respuestas | sin mensajes | responde JSON `success`/`message` |

---

## 4. `CategoriaModel` — categorías y subcategorías

- **Archivo:** `app/Models/CategoriaModel.php` (101 líneas). Patrón **instancia**: constructor toma el PDO del singleton (10-12). Placeholders nombrados `:campo`.
- **Tabla:** `categorias` (un solo campo `tipo` distingue 'categoria' de 'subcategoria' → jerarquía autoparentada con `parent_id`).
- **Consumidor:** `Api\CategoriesController` (rutas `/api/categorias*` y `/api/categorias/subcategorias` en `config/routes.php:63-67`).

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 14-23 | `getAll()` | Categorías raíz (`tipo = 'categoria'`) con dos **subconsultas**: `total_productos` (count de productos por `categoria_id`) y `total_subcategorias` (count de hijos por `parent_id`). Orden: `orden ASC, nombre ASC`. | `Api/CategoriesController.php:24` |
| 25-33 | `getAllWithSub()` | Subcategorías (`tipo = 'subcategoria'`) con su `total_productos` (por `subcategoria_id`). | `Api/CategoriesController.php:25` |
| 35-39 | `getById($id)` | Una categoría por id. | `:35` (detalle), `:70` (pre-actualizar), `:89` (pre-eliminar) |
| 41-62 | `create($data)` | `INSERT` de 9 columnas (`nombre, slug, descripcion, imagen_url, parent_id, orden, visible, tipo, destacada`). Genera `slug` automáticamente si no llega (48). Convierte booleanos a 0/1 (53-55). Devuelve `['id' => lastInsertId]` o `false`. | `Api/CategoriesController.php:56` |
| 64-84 | `update($id, $data)` | `UPDATE` dinámico con **whitelist** de 9 campos (68): solo actualiza los que vienen en `$data`. Devuelve `false` si no hay nada que actualizar (77-79). | `Api/CategoriesController.php:77` |
| 86-89 | `delete($id)` | `DELETE` físico por id. | `Api/CategoriesController.php:96` |
| 91-100 | `private generarSlug($texto)` | Normaliza a minúsculas, reemplaza no-alfanuméricos por `-`, colapsa guiones; si queda vacío genera `categoria-<timestamp>`. | interno (usado por `create`:48) |

---

## 5. `DireccionModel` — direcciones de envío del cliente

- **Archivo:** `app/Models/DireccionModel.php` (122 líneas). Patrón instancia. Placeholders nombrados.
- **Tabla:** `direcciones` (11 columnas: `usuario_id, titulo, pais, departamento, municipio, calle, info_adicional, barrio, destinatario, codigo_postal, es_predeterminada`).
- **Consumidor:** `Cliente\DireccionController` (rutas `/api/direcciones*`).
- **Regla de negocio central:** solo puede existir una dirección predeterminada por usuario (`clearPredeterminada` + `ensureOnePredeterminada`).

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 13-19 | `getByUsuario($usuarioId)` | Lista direcciones del usuario, predeterminada primero (`ORDER BY es_predeterminada DESC, fecha_creacion DESC`). | `Cliente/DireccionController.php:21` |
| 21-27 | `getById($id, $usuarioId)` | Una dirección **restringida al usuario** (evita acceder a direcciones ajenas). | interno (`actualizar` 57, `eliminar` y vistas) |
| 29-54 | `crear($usuarioId, $data)` | Si llega `es_predeterminada` quita la anterior (31). `INSERT` con defaults (`titulo`→'Casa', `pais`→'Colombia', `codigo_postal`→'05001'). Devuelve `lastInsertId` o `false`. | `:32` |
| 56-86 | `actualizar($id, $usuarioId, $data)` | Verifica que exista (57-58). Si marca predeterminada la limpia (60-62). `UPDATE` de los 10 campos usando valores previos como respaldo todos con `??` (75-84). | `:43` |
| 88-96 | `eliminar($id, $usuarioId)` | `DELETE` con `rowCount() > 0`; si se borró, garantiza que quede al menos una predeterminada (92). | `:53` |
| 98-102 | `setPredeterminada($id, $usuarioId)` | Limpia la actual y marca la elegida (`es_predeterminada = 1`). | `:63` |
| 104-107 | `private clearPredeterminada($usuarioId)` | Pone `es_predeterminada = 0` a todas las del usuario. | interno |
| 109-121 | `private ensureOnePredeterminada($usuarioId)` | Si no queda ninguna predeterminada, promueve la más antigua (`ORDER BY fecha_creacion ASC LIMIT 1`). | interno (tras `eliminar`) |

---

## 6. `Favorito` — favoritos del cliente

- **Archivo:** `app/Models/Favorito.php` (50 líneas). Clase **sin sufijo `Model`** (inconsistencia de convención). Patrón **estático**: usa directamente `Database::query()` con placeholders posicionales `?`.
- **Tabla:** `favoritos` (`usuario_id, producto_id, fecha_agregado`).
- **Consumidor:** `Api\FavoritoController` (rutas `/api/favoritos*`).

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 8 | `protected $table = 'favoritos'` | Tabla (aunque no hereda de `Model`, la guarda como propiedad). |
| 13-19 | `getByUsuario($usuarioId)` | `SELECT producto_id ... ORDER BY fecha_agregado DESC` → devuelve **solo un array de IDs** (`array_column`). | `Api/FavoritoController.php:46` |
| 24-29 | `existe($usuarioId, $productoId)` | `SELECT COUNT(*)` → `true` si ya es favorito. | `:77` |
| 34-39 | `agregar($usuarioId, $productoId)` | `INSERT ... NOW()` → `true` si insertó. | `:82` |
| 44-49 | `eliminar($usuarioId, $productoId)` | `DELETE` → `true` si borró. | `:111` |

---

## 7. `PedidoModel` — pedidos y detalle (el más completo)

- **Archivo:** `app/Models/PedidoModel.php` (240 líneas). Patrón instancia.
- **Tablas:** `pedidos` (maestro) + `detalles_pedido` (ítems).
- **Consumidores:** `CompraController` (creación de compra), `Cliente\PedidosController` (mis pedidos/factura), `Admin\PedidosController` (panel admin).

### 7.1 Métodos

| Línea | Método | Qué hace / SQL | Quién lo llama |
|---|---|---|---|
| 14-20 | `private generarNumeroPedido()` | Genera `ORD-{año}-{4 dígitos}`: toma el `MAX(CAST(SUBSTRING(numero_pedido, 10) AS UNSIGNED))+1` de los pedidos `LIKE 'ORD-{año}-%'` y lo rellena a 4 dígitos. | interno (usado por `crearPedido`:26) |
| 22-162 | `crearPedido($data)` | **Transacción completa** (ver 7.2). | `CompraController.php:64` (`/procesar-compra`) |
| 164-172 | `getAll()` | Todos los pedidos `JOIN usuarios` (nombre/email) + subconsulta `total_productos`. Orden por `fecha_pedido DESC`. | `Admin/PedidosController.php:32` (lista admin) |
| 174-183 | `getByUsuario($usuarioId)` | Pedidos de un cliente + `total_productos`. | `Cliente/PedidosController.php:25` (mis pedidos) |
| 185-193 | `getDetalles($pedidoId)` | Ítems de un pedido (`detalles_pedido JOIN pedidos`, incluye `numero_pedido`, `nombre_cliente`, `estado`, `fecha_pedido`, `total`). | `Cliente/PedidosController.php:48` (factura) |
| 195-203 | `getById($pedidoId)` | Pedido individual `LEFT JOIN usuarios` (nombre, email, teléfono). | `Cliente/PedidosController.php:43,70,101`; `Admin/PedidosController.php:84` |
| 205 | `const ESTADOS_VALIDOS` | Lista blanca de 11 estados: `pendiente, confirmado, procesando, listo, asignado, aceptado, recogido, en_camino, entregado, cancelado, reembolsado`. | — |
| 207-215 | `updateEstado($pedidoId, $estado)` | Normaliza minúsculas + valida contra `ESTADOS_VALIDOS`; si no es válido **lanza `Exception`** con la lista de permitidos (209-211). `UPDATE pedidos SET estado`. | `Cliente/PedidosController.php:80` (rechazada); `Admin/PedidosController.php:90` |
| 217-239 | `getDetallesCompletos($pedidoId)` | Pedido + datos del usuario (incl. cédula) + `items`: cada ítem enriquece con `precio_actual` y `stock_total` actuales del producto (`JOIN productos` 230-233). | `Admin/PedidosController.php:48` (detalle admin) |

### 7.2 Flujo de `crearPedido()` (la transacción, líneas 22-162)

1. `beginTransaction()` (23).
2. **Validaciones (28-34):** usuario_id obligatorio, `productos` debe ser array no vacío.
3. **Relectura de precios y stock desde BD (36-76):** prepara `SELECT id, precio, nombre, stock_total` (39) y valida cada ítem: producto existe (55-57), `stock_total >= cantidad` (58-60). El `subtotalReal` se calcula con el **precio de BD** (62-64), sin confiar en el precio enviado por el cliente. Construye `productosValidados` (66-75).
4. **Totales en servidor (78-85):** `costo_envio`, `descuento` (recortado si supera el subtotal, 81-83), `totalReal = subtotal - descuento + envio`, nunca negativo (85).
5. **INSERT maestro `pedidos` (87-129):** 25 columnas, con `estado_pago = 'procesando'` (124), `zona = 'centro'` (125), `prioridad = 'normal'` (126) y coordenadas opcionales `latitud_destino/longitud_destino` (127-128). Fijados en servidor (no vienen del cliente).
6. **INSERT de cada ítem en `detalles_pedido` (133-153):** captura `pedido_id`, guarda nombre/precio/cantidad/talla/color/imagen del momento.
7. `commit()` (155) y devuelve `['id' => $pedidoId, 'numero_pedido' => $numeroPedido]` (156).
8. **Cualquier `Exception` → `rollBack()` (159) y relanza (160)** : si algo falla a mitad de camino, no queda ni el pedido ni sus detalles.

---

## 8. `ProductoModel` — productos del catálogo

- **Archivo:** `app/Models/ProductoModel.php` (141 líneas). Patrón instancia.
- **Tablas:** `productos` con `LEFT JOIN categorias` ×2 (`categoria_id` y `subcategoria_id`, ambas apuntan a la misma tabla `categorias`).
- **Consumidor:** `Api\ProductsController` (rutas `/api/productos*`).

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 14-22 | `getAll()` | Todos los productos + nombres de categoría y subcategoría (`JOIN categorias`). Orden `fecha_creacion DESC`. | `Api/ProductsController.php:34` |
| 24-33 | `getById($id)` | Un producto con sus nombres de categoría/subcategoría. | `:81` (detalle), `:148` y `:168` (pre-validación) |
| 35-85 | `create($data)` | `INSERT` de 23 columnas (36-52). Campos de array (`tallas_disponibles, colores_disponibles, imagenes, caracteristicas`) se guardan con `json_encode` (68-71). Defaults: `precio→0`, `stock_minimo→5`, `categoria_id→1`, `nuevo→1`, `visible→1`. Slug auto si no llega (57). Devuelve `['id' => lastInsertId]` o `false`. | `:133` |
| 87-124 | `update($id, $data)` | `UPDATE` dinámico con **whitelist** de campos escalares (91-98) + campos **JSON especiales** que solo se actualizan si vienen en `$data` (107-115, con `json_encode`). `false` si no hay campos (117-119). | `:155` |
| 126-129 | `delete($id)` | `DELETE` físico por id. ⚠️ No comprueba si el producto tiene pedidos/carritos asociados (`FK`) — puede fallar por restricción de integridad. | `:175` |
| 131-140 | `private generarSlug($texto)` | Igual patrón que `CategoriaModel::generarSlug`, con prefijo `producto-` si queda vacío. | interno (`create`:57) |

---

## 9. `TarjetaModel` — tarjetas de crédito guardadas

- **Archivo:** `app/Models/TarjetaModel.php` (133 líneas). Patrón instancia.
- **Tabla:** `tarjetas_credito` (incluye dirección de facturación y bandera `activa`).
- **Consumidor:** `Cliente\TarjetaController` (rutas `/api/tarjetas*`).
- **Seguridad:** nunca se persiste el número completo → solo `'**** **** **** ' . últimos 4` (`.numero_enmascarado`, línea 51). El `DELETE` es **lógico** (`activa = 0`).

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 13-20 | `getByUsuario($usuarioId)` | Tarjetas `activa = 1`, seleccionando solo `numero_enmascarado` (nunca el número real), predeterminada primero. | `Cliente/TarjetaController.php:21` |
| 22-28 | `getById($id, $usuarioId)` | Una tarjeta (todo el registro) restringida al usuario → la usa `actualizar` como referencia. | interno + controlador |
| 30-66 | `crear($usuarioId, $data)` | Si es predeterminada, limpia la anterior (31-33). Limpia espacios del número (35) y guarda solo el enmascarado (36, 51). **Detecta el tipo** con `detectarTipo` (38). Titular en MAYÚSCULAS (52). `INSERT` de 14 columnas (incluye dirección de facturación: `pais, departamento, municipio, codigo_postal, calle, barrio`). Devuelve `lastInsertId` o `false`. | `:40` |
| 68-89 | `actualizar($id, $usuarioId, $data)` | Verifica que exista (69-70). `UPDATE` dinámico **solo** de `alias, titular, mes_expiracion, anio_expiracion, es_predeterminada` — ⚠️ **no permite cambiar el número** (el número enmascarado queda fijo de por vida). | `:51` |
| 91-99 | `eliminar($id, $usuarioId)` | **Eliminación lógica:** `UPDATE ... SET activa = 0` (no `DELETE`). Luego garantiza una predeterminada (95). Retorna `true` incondicional si el UPDATE se ejecutó (94-97). | `:61` |
| 101-105 | `setPredeterminada($id, $usuarioId)` | Limpia la actual y marca la elegida (`es_predeterminada = 1`). | `:71` |
| 107-110 | `private clearPredeterminada($usuarioId)` | `es_predeterminada = 0` en todas las activas del usuario. | interno |
| 112-124 | `private ensureOnePredeterminada($usuarioId)` | Si ninguna activa es predeterminada, promueve la más antigua activa (116-121). | interno (tras `eliminar`) |
| 126-132 | `private detectarTipo($numero)` | Reglas regex: `^4`→`visa`, `^5[1-5]`→`mastercard`, `^3[47]`→`amex`, `^6(?:011|5)`→`discover`, resto→`otra`. | interno (usado por `crear`:38) |

---

## 10. `UsuarioModel` — usuarios, login y roles

- **Archivo:** `app/Models/UsuarioModel.php` (184 líneas). Patrón instancia; propiedad tipada `private PDO $db` (10). El archivo está lleno de comentarios doc POO/seguridad (7-8, 16-19, 39-42, 88-90, 153-165, etc.).
- **Tabla:** `usuarios` (14 columnas; incluye `password_hash`, `reset_token`, `reset_expiry`, `rol`, `estado`, `tipo_vehiculo`, `placa_vehiculo`, `total_entregas`, etc.).
- **Consumidores (5):** `AuthController`, `RepartidorAuthController`, `Api\RepartidorRegistroController`, `Cliente\PerfilApiController`, `Admin\ClientesController`.

### 10.1 Métodos

| Línea | Método | Qué hace | Quién lo llama |
|---|---|---|---|
| 22-26 | `findByEmail($email)` | `SELECT * FROM usuarios WHERE email = :email` → fila completa (incluye `password_hash` y rol) o `false`. Base de login, registro y recuperación. | `AuthController.php:42,113,132,194,212,278`; `RepartidorAuthController.php:58,223`; `Api/RepartidorRegistroController.php:312` |
| 33-37 | `findById($id)` | Usuario por id (desde `$_SESSION['user']['id']` o payload JWT). | `AuthController.php:402`; `Cliente/PerfilApiController.php:21,50` |
| 47-86 | `create($data)` | `INSERT` de 14 columnas (63-64). Defaults: `rol→'cliente'` (52), `estado→'activo'` (59), `acepta_terminos→0` (60), `fecha_registro→now` (61). Devuelve `lastInsertId` o `false`. | `AuthController.php:121,200`; `RepartidorAuthController.php:256`; `Api/RepartidorRegistroController.php:347`; `Admin/ClientesController.php:114` |
| 94-97 | `updatePassword($email, $passwordHash)` | `UPDATE usuarios SET password_hash = :pwd WHERE email = :email`. Recibe el hash bcrypt **ya generado** (la app hace `password_hash()` en el controlador). | `AuthController.php:346,411` |
| 106-109 | `setResetToken($email, $token, $expires)` | `UPDATE ... SET reset_token = :token, reset_expiry = :expires`. Token binario + expiración (1 hora) previos al envío del correo. | `AuthController.php:286` |
| 116-120 | `findByResetToken($token)` | `SELECT * ... WHERE reset_token = :token AND reset_expiry > NOW()` — la **caducidad se valida dentro del SQL** (112). Devuelve fila o `false`. | `AuthController.php:319,333` |
| 127-130 | `clearResetToken($email)` | Pone `reset_token = NULL, reset_expiry = NULL` tras un reset exitoso (**anti-reutilización** del token). | `AuthController.php:347` |
| 138-141 | `getAll()` | Lista admin **sin exponer secretos**: selecciona campos operativos (id, nombre, apellido, email, telefono, rol, estado, `tipo_vehiculo`, `placa_vehiculo`, `total_entregas`, `calificacion_promedio`, `motivo_suspension`, `fecha_registro`) — **NO** incluye `password_hash` ni `reset_token` (comentario 132-136). | `Admin/ClientesController.php:32,51` |
| 147-151 | `buscarPorNombre($nombre)` | `WHERE nombre LIKE :nombre OR email LIKE :nombre` con `%...%` (comodines) y parametrizado → sin SQLi. Selecciona también sin secretos. | `Admin/ClientesController.php:55` |
| 169-172 | `updateRol($id, $rol)` | `UPDATE usuarios SET rol = :rol WHERE id = :id`. Punto único de cambio de rol (comentario 153-165): lo usan admin (`cambiarRol`) y la conversión cliente→repartidor. | `Admin/ClientesController.php:83` |
| 180-183 | `delete($id)` | **Borrado físico** `DELETE FROM usuarios WHERE id = :id`. ⚠️ Contradice la recomendación de `docs/SEGURIDAD.md` (borrado lógico) y puede fallar si el usuario tiene pedidos/FKs. | `Admin/ClientesController.php:140` |

---

## 11. RESUMEN DE CONCEPTOS POO VISIBLES EN LOS MODELOS

| Concepto POO | Dónde está en el código | Referencia |
|---|---|---|
| **Encapsulamiento** (datos privados + acceso controlado) | `private static $instance` / `private PDO $conn` en `Database`; `private $db` en 6 modelos; `protected string $table` en `Model`/`CarritoModel` | `Database.php:13-14`; `PedidoModel.php:8`; `Model.php:9-10` |
| **Herencia** (reutilizar el CRUD genérico) | `CarritoModel extends Model` → hereda `getAll`, `getById`, `create`, `update`, `delete` | `CarritoModel.php:5,8`; `Model.php:16-48` |
| **Abstracción** (ocultar el PDO detrás del Singleton) | Controladores y modelos nunca crean su propia conexión; piden la única conexión compartida | `Database.php:34-43` |
| **Patrón de diseño Singleton** | `Database::getInstance()` | `Database.php:34-39` |
| **Constantes / listas blancas de dominio** | `ESTADOS_VALIDOS` como `const` pública de clase | `PedidoModel.php:205` |
| **Métodos privados de apoyo** | `generarSlug`, `clearPredeterminada`, `ensureOnePredeterminada`, `detectarTipo`, `generarNumeroPedido` | `CategoriaModel.php:91`; `DireccionModel.php:104`; `TarjetaModel.php:126`; `PedidoModel.php:14` |

---

## 12. DEBILIDADES OBSERVADAS (verificadas)

1. **`CarritoModel` muerto/duplicado:** no se instancia en ningún controlador; la lógica real está duplicada en `Api/CarritoController.php` con diferencias de comportamiento (por ejemplo, el modelo soporta `variante_id`/`color_seleccionado` y el controlador real no; el controlador real valida stock y el modelo no).
2. **Convenciones inconsistentes:** `Favorito` sin sufijo `Model`; estilos de consulta mixtos (`?` posicional vs `:nombre` nombrado); `CarritoModel` y `Favorito` usan el `Database::query` estático en lugar del patrón instancia del resto.
3. **Borrados físicos arriesgados:** `ProductoModel::delete` y `UsuarioModel::delete` hacen `DELETE` sin verificar dependencias (carrito, pedidos, favoritos) — pueden fallar por integridad referencial o borrar historial. `docs/SEGURIDAD.md` recomienda borrado lógico y solo `TarjetaModel` lo implementa.
4. **Sin timestamps de auditoría parciales:** `DireccionModel::actualizar()` conserva campos viejos con `??` (75-84) pero no actualiza un `actualizado_en`; `carrito` sí mantiene `actualizado_en` (`CarritoModel.php` y `Api/CarritoController.php`).
5. **`validarStock` en `crearPedido` no descuenta stock:** `PedidoModel::crearPedido` valida `stock_total >= cantidad` (`:58-60`) pero **NO decrementa el stock** al confirmar el pedido → `NO SE ENCONTRÓ DECREMENTO DE `stock_total` EN `PedidoModel`.` (el stock real se muta aparte vía `/api/stock` / `StockController`).
6. **Respuesta de `crear` sin datos de fila:** `create()` de `CategoriaModel`/`ProductoModel` devuelve solo `['id' => ...]`; el controlador debe releer con `getById` para responder (patrón correcto pero doble consulta).

---

## 13. ENDPOINTS QUE TERMINAN EN ESTOS MODELOS (flujo completo)

| Endpoint (config/routes.php) | Controlador:acción | Modelo:método |
|---|---|---|
| `/api/categorias` GET/POST | `Api\CategoriesController` | `CategoriaModel::getAll/create` |
| `/api/categorias/{id}` GET/PUT/DELETE | `Api\CategoriesController` | `getById/update/delete` |
| `/api/categorias/subcategorias` GET | `Api\CategoriesController` | `getAllWithSub` |
| `/api/productos` GET/POST | `Api\ProductsController` | `ProductoModel::getAll/create` |
| `/api/productos/{id}` GET/PUT/DELETE | `Api\ProductsController` | `getById/update/delete` |
| `/api/favoritos*` | `Api\FavoritoController` | `Favorito::getByUsuario/existe/agregar/eliminar` |
| `/api/direcciones*` | `Cliente\DireccionController` | `DireccionModel::getByUsuario/crear/actualizar/eliminar/setPredeterminada` |
| `/api/tarjetas*` | `Cliente\TarjetaController` | `TarjetaModel::getByUsuario/crear/actualizar/eliminar/setPredeterminada` |
| `/api/mis-pedidos*` | `Cliente\PedidosController` | `PedidoModel::getByUsuario/getById/getDetalles/updateEstado` |
| `/api/pedidos*` (admin) | `Admin\PedidosController` | `PedidoModel::getAll/getDetallesCompletos/getById/updateEstado` |
| `/procesar-compra` POST | `CompraController` | `PedidoModel::crearPedido` |
| `/auth/*` | `AuthController`, `RepartidorAuthController`, `Api\RepartidorRegistroController` | `UsuarioModel::findByEmail/create/findById/updatePassword/setResetToken/findByResetToken/clearResetToken` |
| `/api/clientes*` (admin) | `Admin\ClientesController` | `UsuarioModel::getAll/buscarPorNombre/updateRol/create/delete` |
| `/api/perfil*` | `Cliente\PerfilApiController` | `UsuarioModel::findById` |
| `/api/carrito*` | `Api\CarritoController` (**sin modelo**) | `Database::query` directo |