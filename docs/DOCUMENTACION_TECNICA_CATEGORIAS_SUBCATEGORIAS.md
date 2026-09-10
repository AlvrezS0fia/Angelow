# DOCUMENTACIÓN TÉCNICA — CATEGORÍAS Y SUBCATEGORÍAS (ANGELOW)

> **Proyecto:** ANGELOW — Tienda en línea de ropa infantil (Colombia).
> **Ruta del código:** `C:\xampp\htdocs\Angelow`
> **Alcance de este documento:** análisis profundo del módulo de **categorías y subcategorías**, 100 % basado en el código real del repositorio. Cuando un mecanismo no existe, se indica con la nota `NO SE ENCONTRÓ ESTE MECANISMO EN EL CÓDIGO ANALIZADO.`
> **Referencias:** formato `ruta/archivo.php:línea`.
> **Documento hermano:** `docs/GUION_EXPOSICION_CATEGORIAS_SUBCATEGORIAS.md` (guion oral).
> **Relación con análisis general:** este módulo complementa las secciones 4 (roles), 8 (productos) y 13 (endpoints) de `docs/DOCUMENTACION_TECNICA_ANGELOW.md`.

---

## 0. HALLAZGO PRINCIPAL: EL MÓDULO VIVE EN "DOS MUNDOS"

El análisis revela que las categorías/subcategorías existen en **tres implementaciones independientes y desconectadas entre sí**:

| # | Implementación | Dónde | Persistencia | ¿La usa la UI actual de administración? |
|---|---|---|---|---|
| 1 | **Backend real (BD + API PHP namespaced)** | Tabla `categorias`, `CategoriaModel`, `CategoriesController`, rutas `/api/categorias*` | MySQL | **NO** — el panel admin no llama a esta API |
| 2 | **Scripts procedurales heredados** | `app/Controllers/Api/categories.php`, `products.php`, `config.php` | MySQL (`mysqli`) | **NO** — código muerto e inalcanzable |
| 3 | **Frontend de demostración (admin + público)** | `public/assets/js/panel.js`, `bienvenida.js`, `inventario.js` | **`localStorage`** del navegador | **SÍ** — toda la gestión visual de categorías es local al navegador |

> **Consecuencia documentada:** el panel de administración "Gestión de Categorías / Subcategorías" opera sobre `localStorage` y **nunca** consume `/api/categorias` (verificado: ninguna referencia a esa URL en JS). El backend CRUD completo existe y funciona contra MySQL, pero está **desconectado de la interfaz**.

---

## 1. MODELO DE DATOS — TABLA `categorias`

Definición en `angelow.sql:114-146`:

| Columna | Tipo | Detalle |
|---|---|---|
| `id` | `INT AUTO_INCREMENT PRIMARY KEY` | Clave primaria |
| `nombre` | `VARCHAR(100) NOT NULL` | Nombre visible |
| `slug` | `VARCHAR(120) UNIQUE NOT NULL` | Slug único (impuesto por la BD) |
| `descripcion` | `TEXT` | Descripción opcional |
| `imagen_url` | `VARCHAR(500)` | Imagen opcional |
| `parent_id` | `INT DEFAULT NULL` | **Auto-referencia**: si no es NULL, este registro es **subcategoría** de ese padre |
| `orden` | `INT DEFAULT 0` | Orden de visualización |
| `visible` | `BOOLEAN DEFAULT TRUE` | Visibilidad |
| `tipo` | `ENUM('categoria','subcategoria','etiqueta') DEFAULT 'categoria'` | Discriminador de tipo |
| `destacada` | `BOOLEAN DEFAULT FALSE` | Destacada |
| `fecha_creacion` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Fecha de creación |

**Índices y FK:**
- `FOREIGN KEY (parent_id) REFERENCES categorias(id) ON DELETE CASCADE` (`angelow.sql:138`) — al borrar una categoría se **eliminan en cascada** sus subcategorías (nivel BD).
- Índices `idx_slug`, `idx_parent`, `idx_tipo` (`angelow.sql:140-144`).

**Relaciones desde `productos`** (`angelow.sql:233-235`):
- `productos.categoria_id INT NOT NULL` → `FOREIGN KEY ... REFERENCES categorias(id) ON DELETE RESTRICT` → **no se puede borrar una categoría que tenga productos**.
- `productos.subcategoria_id INT` → `FOREIGN KEY ... REFERENCES categorias(id) ON DELETE SET NULL` → al borrar una subcategoría, los productos quedan con `NULL`.

### 1.1 Sinónimos de "subcategoría" en el esquema

El esquema usa `tipo = 'subcategoria'` con `parent_id` apuntando a una fila `tipo = 'categoria'`. La misma columna `tipos` incluye `'etiqueta'`, pero en el **código real del modelo/controlador no se usa `etiqueta`** — las consultas sólo filtra por `'categoria'` y `'subcategoria'`. → `NO SE ENCONTRÓ USO DE `tipo='etiqueta'` EN EL CÓDIGO HTTP/Models ANALIZADO.`

### 1.2 Datos iniciales (seed)

`angelow.sql:1735-1755`:

**Categorías principales (4):**

| id implícito | nombre | slug | orden | destacada |
|---|---|---|---|---|
| 1 | Bebés | bebes | 1 | TRUE |
| 2 | Niños | ninos | 2 | TRUE |
| 3 | Niñas | ninas | 3 | TRUE |
| 4 | Zapatos | zapatos | 4 | FALSE |

**Subcategorías (5):**

| nombre | slug | parent_id | orden |
|---|---|---|---|
| Edición Especial | edicion-especial | 1 (Bebés) | 1 |
| Conjuntos | conjuntos | 2 (Niños) | 1 |
| Ropa Deportiva | ropa-deportiva | 2 (Niños) | 2 |
| Vestidos | vestidos | 3 (Niñas) | 1 |
| Tenis | tenis | 4 (Zapatos) | 1 |

---

## 2. CAPA MODELO — `app/Models/CategoriaModel.php` (101 líneas)

Clase namespaced que usa PDO con **sentencias preparadas** (patrón de proyecto, `Database::getInstance()->getConnection()`, línea 11).

| Método | Líneas | Comportamiento |
|---|---|---|
| `getAll()` | 14-23 | `SELECT c.*, (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) as total_productos, (SELECT COUNT(*) FROM categorias WHERE parent_id = c.id) as total_subcategorias FROM categorias c WHERE c.tipo = 'categoria' ORDER BY c.orden ASC, c.nombre ASC`. Devuelve cada categoría **con su conteo de productos y subcategorías**. |
| `getAllWithSub()` | 25-33 | Igual, pero `WHERE c.tipo = 'subcategoria'` y solo `total_productos`. |
| `getById($id)` | 35-39 | `SELECT * FROM categorias WHERE id = :id`. |
| `create($data)` | 41-62 | INSERT de 9 columnas; **defectos**: `slug = $data['slug'] ?? generarSlug(nombre)` (48), `parent_id = null` (51), `orden = 0` (52), `visible = 1` (53), `tipo = 'categoria'` (54), `destacada = 0` (55). Devuelve `['id' => lastInsertId()]`. |
| `update($id, $data)` | 64-84 | **Whitelist** `$camposPermitidos = ['nombre','slug','descripcion','imagen_url','parent_id','orden','visible','tipo','destacada']` (68); construye `SET` dinámico solo con campos presentes; si no hay campos → `false` (77-79). |
| `delete($id)` | 86-89 | `DELETE FROM categorias WHERE id = :id`. |
| `generarSlug($texto)` | 91-100 | `strtolower(trim())` → `preg_replace('/[^a-z0-9-]/', '-', ...)` → colapsa `-+` → recorta guiones; si queda vacío → `'categoria-' . time()`. |

**Observaciones (hechos del código):**
- `create()` no valida duplicados de `slug` ni de `nombre`: el `UNIQUE` de BD (`angelow.sql:120`) lanzaría `PDOException` no capturada en el controlador (ver §3).
- `update()` **no recalcula el slug** si cambia el nombre: si el cliente manda `nombre` sin `slug`, el slug queda viejo (el slug debe venir del payload).
- `delete()` delega la cascada al FK `ON DELETE CASCADE` de la BD (§1); no borra manualmente las subcategorías (a diferencia del script heredado §5).

---

## 3. API BACKEND — `app/Controllers/Api/CategoriesController.php` (105 líneas)

Controlador enrutado (5 endpoints). **Guardián de rol** en cada acción: `checkAdmin()` (`:14-20`) responde `403 {"error":"No autorizado"}` si no hay sesión o `rol !== 'administrador'`.

| Método HTTP | Ruta | Acción | Líneas | Comportamiento |
|---|---|---|---|---|
| GET | `/api/categorias` | `index()` | 22-31 | `checkAdmin`; devuelve `{"categorias": getAll(), "subcategorias": getAllWithSub()}`. |
| GET | `/api/categorias/{id}` | `show($id)` | 33-44 | `checkAdmin`; 404 `{"error":"Categoría no encontrada"}` si no existe; si no, devuelve la fila. |
| POST | `/api/categorias` | `store()` | 46-64 | `checkAdmin`; **400** si `nombre` vacío; `create($data)`; 500 si falla. Respuesta `{success, id}`. |
| PUT | `/api/categorias/{id}` | `update($id)` | 66-85 | `checkAdmin`; **404** si no existe; `update($id,$data)`; 500 si falla. |
| DELETE | `/api/categorias/{id}` | `destroy($id)` | 87-104 | `checkAdmin`; **404** si no existe; `delete($id)`; 500 si falla. |

**Rutas registradas en `config/routes.php:63-67`.**

**Hallazgo (validación de integridad):**
- `store()` no valida que `parent_id` exista ni que `tipo` sea coherente; se apoya en las FKs del esquema.
- Un `slug` duplicado lanza excepción PDO **no capturada** (el `catch` del controlador no existe) → respondería error 500 de PHP, no JSON limpio.

---

## 4. INTEGRACIÓN CON PRODUCTOS (BD + API)

El cruce categoría→producto está en tres puntos (todo verificado):

### 4.1 `ProductoModel` — JOIN para nombres
```sql
SELECT p.*, c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN categorias sc ON p.subcategoria_id = sc.id
```
- En `getAll()` (`ProductoModel.php:15-18`) y `getById()` (`:25-28`).
- `create()` inserta `categoria_id` (NOT NULL) y `subcategoria_id` (nullable) (`:38,46,61`).
- `update()` permite actualizar `categoria_id`, `subcategoria_id` vía `$camposPermitidos` (`:93`).

### 4.2 `ProductsController` — serialización
- `index()` (`ProductsController.php:32-76`): expone `categoria`, `categoria_id`, `subcategoria`, `subcategoria_id` por producto (líneas 49-52).
- `show()` (`:79-120`): expone `categoria_id` y `subcategoria_id` (100-101).
- **No** consulta la lista de categorías; solo lee etiquetas de los productos.

### 4.3 Otros consumidores de nombres de categoría
- `StockController::index()` (`StockController.php:19-36`): `LEFT JOIN categorias c / sc` y serializa `categoria`/`subcategoria` por producto (endpoint `/api/inventario`).
- `CarritoController::index()` (`CarritoController.php:22-48`): JOIN para devolver `categoria` y `subcategoria` en los ítems del carrito.

### 4.4 Vista de BD
- `vista_productos_populares` hace `JOIN categorias c ON p.categoria_id = c.id` (`angelow.sql:1687`) — uso de categorías a nivel de vista.

---

## 5. SCRIPTS PROCEDURALES HEREDADOS (código muerto documentado)

Existen tres archivos **fuera del router** y **fuera de `public/`**:

| Archivo | Contenido |
|---|---|
| `app/Controllers/Api/categories.php` (191 líneas) | CRUD procedural completo de categorías/subcategorías con `switch($_SERVER['REQUEST_METHOD'])`. |
| `app/Controllers/Api/products.php` (201 líneas) | CRUD procedural de productos con JOIN a `categorias` para nombres. |
| `app/Controllers/Api/config.php` (13 líneas) | Conexión **`mysqli`** (`$conn`) desde `$_ENV['DB_*']` (con fallbacks localhost/root/vacío/angelow_db). |

**Por qué es código muerto (verificado en el código):**
1. **No los invoca el Router**: la aplicación solo enruta `App\Controllers\Api\CategoriesController` / `ProductsController` (namespaced). Nada hace `require`/`include` a `categories.php`/`products.php`.
2. **No son servibles por Apache**: están en `app/Controllers/`, fuera de `public/` (docroot), y `public/.htaccess` redirige todo a `index.php`.
3. **Están rotos internamente**: `categories.php:10` y `products.php:11` usan `$pdo->...`, pero `config.php` solo define `$conn` (mysqli) y **nunca define `$pdo`**. → En PHP 8 lanzan *"Call to a member function query() on null"*. → `NO ES POSIBLE EJECUTARLOS EN EL ESTADO ACTUAL DEL CÓDIGO ANALIZADO.`

> Son una implementación anterior (pre-router) del CRUD; su lógica de negocio (resolución de categoría padre **por nombre**, borrado manual de subcategorías antes del padre, `{success, main, sub}`) quedó sustituida por el modelo/controlador namespaced.

---

## 6. PANEL DE ADMINISTRACIÓN — GESTIÓN VISUAL EN `localStorage`

### 6.1 Vista — `app/Views/admin/panel.php`

Sección "Categorías" (`:211-236`):
- "Gestión de Categorías Principales" con tabla `#mainCategoriesTable` y botón "+ Añadir Categoría" (`:216`, llama `openCategoryModal('categoria')`).
- "Gestión de Subcategorías (Ofertas/Promociones)" con tabla `#subcategoriesTable` y "+ Añadir Subcategoría" (`:227`, `openCategoryModal('subcategoria')`).

### 6.2 Lógica — `public/assets/js/panel.js`

**Datos globales (load-on-start desde localStorage, con defaults hardcodeados):**
- `mainCategories` (`:2-10`): `localStorage.getItem('angelow_main_categories')` o 7 categorías por defecto (Bebés, Niños, Niñas, Edición especial, Oferta, Todos, Popular).
- `subCategories` (`:12-18`): `localStorage.getItem('angelow_sub_categories')` o 5 subcategorías por defecto (Body's→Bebés, Pijamas→Bebés, Vestidos→Niñas, Conjuntos→Niños, Accesorios→Niñas).
- `products` (`:20-29`): `localStorage.getItem('angelow_products')` o 8 productos hardcodeados.

**Flujo de creación/edición/borrado (TODO cliente, sin API):**
- `openCategoryModal(tipo, item)` (`:712-774`): muestra/rellena el modal; para subcategoría pide categoría principal (`parentGroup`, `categoryParent`).
- `saveCategory()` (`:784-853`): valida nombre (790-793) y, para subcategoría, exige `padre` (795-801); muta el array en memoria; si renombra una categoría reasigna el `padre` de sus subcategorías (832-838); guarda con `saveAllData()` (851).
- `deleteMainCategory($id)` (`:869-890`): confirma; si tiene subcategorías lo avisa y las filtra (873-881); borra del array local.
- `deleteSubCategory($id)` (`:892-903`): borra del array local.
- Render: `renderMainCategories()` (`:600-628`), `renderSubCategories()` (`:630-674`), `updateCategorySelects()` (`:676-710`).

**Persistencia hacia el cliente público:**
- `saveAllData()` (`:141-147`): escribe `angelow_main_categories`, `angelow_sub_categories`, `angelow_products`, `angelow_orders` en localStorage y llama `updateClientCategories()`.
- `updateClientCategories()` (`:149-154`): construye `clientCategories = ["Todos", ...mainCategories.enBarra===true, ...subCategories.enBarra===true]` y lo guarda en `localStorage.angelow_client_categories`.

> **Confirmación del hallazgo:** en todo `panel.js` **no aparece** `fetch` hacia `/api/categorias` ni uso de `CategoriesController`. La gestión es 100 % local. Los únicos `fetch` del panel son: pedidos, clientes, solicitudes/repartidores (`/api/admin/repartidores/...`) y stats.

### 6.3 Sincronización entre pestañas

`panel.js:2475` y `:2493-2495`: al recibir el evento `storage` (cambio en otra pestaña) recarga `mainCategories`, `subCategories` y `products` desde localStorage. El sitio público hace lo mismo para las categorías (ver §7.3).

---

## 7. SITIO PÚBLICO — `public/assets/js/bienvenida.js`

### 7.1 Carga de categorías desde localStorage

```js
// line 36
let categories = JSON.parse(localStorage.getItem('angelow_client_categories')) || ["Todos","Bebés","Niños","Niñas","Popular","Edición especial","Oferta"];
```
- Si el admin (en otra pestaña) escribió `angelow_client_categories`, la tienda las toma; si no, usa el arreglo por defecto.

### 7.2 Productos: constantes JS, no BD

```js
// line 2
const products = [ ... 8 productos hardcodeados ... ];
```
- `normalizeProducts()` (`:24-33`) sobreescribe precio/stock/imgs según `defaultProductConfig` (`:13-22`).
- → **El grid de la portada NO se alimenta de `productos` de MySQL** en el flujo actual (los catálogos reales viven en la API, §4). Consolidado como hallazgo.

### 7.3 Funciones de categorías

| Función | Líneas | Qué hace |
|---|---|---|
| `renderCategories()` | 869-874 | Renderiza `categories` **triplicado**: `[...categories, ...categories, ...categories].map(...)` en spans `.cat-item` con `onclick="setCategory('...')"`. |
| `setCategory(cat)` | 915 | `activeCategory = cat; renderCategories(); renderProducts();`. |
| `getFilteredProducts()` | 850-867 | Filtra: si `activeCategory` es **Popular/Edición especial/Oferta** compara contra `p.subcategory` (`:854-856`); si no, contra `p.category` (`:857`); soporta `searchQuery` sobre `name/category/subcategory` (`:861-866`). |
| `loadCategories()` | 708-715 | Recarga desde `angelow_client_categories` (agregando "Todos" si falta) y re-renderiza. |
| listener `storage` | 716 | Si otra pestaña cambió `angelow_client_categories` → `loadCategories()`. |

> **Nota de diseño (real):** el conjunto **"Popular", "Edición especial", "Oferta"** no existe como categoría en la BD; son **subcategorías/nombres de filtro** interpretados a nivel del frontend (`getFilteredProducts`, `:854`).

---

## 8. PÁGINA DE INVENTARIO — `public/assets/js/inventario.js`

- `inventoryProducts` se llena con `loadInitialProducts()` (`:234` y `:277`), un arreglo hardcodeado de 8 productos (sin precios de BD).
- `updateCategoryFilter()` (`:57-66`): construye el "select" de categorías con `[...new Set(inventoryProducts.map(p => p.category))]` → **las opciones vienen del arreglo local**, no de la tabla `categorias`.
- El filtro por categoría compara `p.category === currentCategory` (`:75-77`).
- → **La página de inventario tampoco consume `/api/inventario`** (existe el `StockController` real, §4.3), es otra superficie de demostración local. Documentado como hallazgo adicional del mismo tipo.

---

## 9. FLUJO DE DATOS COMPLETO (resumen)

```
ADMIN (panel.php + panel.js)
  openCategoryModal / saveCategory / delete*  →  arrays en memoria
       │
       ▼
  saveAllData()  →  localStorage: angelow_main_categories, angelow_sub_categories,
                    angelow_products, angelow_orders
       │
       ▼
  updateClientCategories()  →  localStorage: angelow_client_categories
       │                        (solo enBarra === true, prefix "Todos")
       ▼        (evento 'storage' entre pestañas)
CLIENTE (bienvenida.js)
  categories = localStorage.angelow_client_categories  →  renderCategories()  →  setCategory() → getFilteredProducts()
       ↑
  products (const hardcodeadas + defaultProductConfig)      (no usa /api/productos en la portada)

BACKEND REAL (existe pero desconectado de la UI)
  /api/categorias (GET/POST/PUT/DELETE)  →  CategoriesController  →  CategoriaModel  →  MySQL `categorias`
  /api/productos   →  ProductsController  →  ProductoModel        →  MySQL `productos` (+JOIN categorias)
  /api/inventario  →  StockController                              →  MySQL (+JOIN categorias)
```

---

## 10. HALLAZGOS Y DEBILIDADES (para informe)

1. **Desconexión UI ↔ backend:** el panel "categorías" y la portada operan sobre `localStorage`/constantes; el CRUD real (`/api/categorias`, `CategoriaModel`) y los datos de `productos` de MySQL no alimentan esas pantallas. Cambios en BD no se reflejan en la barra de categorías del público y viceversa.
2. **Código heredado roto:** `app/Controllers/Api/categories.php` y `products.php` usan `$pdo` que no existe (config.php define `$conn` mysqli) y no son alcanzables por el router ni servibles por Apache → no ejecutables.
3. **Semántica de subcategoría dividida:** en BD, subcategoría = `parent_id NOT NULL` + `tipo='subcategoria'`; en el frontend público, "Popular/Edición especial/Oferta" se interpretan como filtros por `subcategory` del producto (`bienvenida.js:854`), y el panel los modela como filas `subCategories`. La correspondencia entre **nombres** (localStorage/defaults del panel) y **tabla BD** no está garantizada por ninguna sincronización en el código analizado.
4. **Sin validaciones de integridad en `CategoriesController::store`:** no valida `parent_id` existente, ni unicidad de `slug`/`nombre` (excepción PDO no capturada si el UNIQUE de BD peta), ni coherencia entre `tipo` y `parent_id`.
5. **`CategoriaModel::update()` no regenera `slug`** al cambiar `nombre` (depende de que el payload lo envíe).
6. **Borrado de categorías con productos:** el FK `ON DELETE RESTRICT` lo prohibe a nivel BD, pero el controlador devuelve `500 {"error":"Error al eliminar categoría"}` genérico sin explicar el motivo (mejora posible).
7. **Catálogos duplicados en al menos 5 archivos JS** con **precios distintos** entre sí: `panel.js:21-28` (todo `price` 899900), `bienvenida.js:3-10` (~32.900-49.900), `inventario.js:46-53` (~32.990-99.990), `perfil.js:33-40` y `seguimiento.js:3-4` — fuente de inconsistencias de datos y de categorías (incluso `inventario.js` usa "Ninos"/"Bebes" sin tilde frente al "Niños"/"Bebés" del público).

---

## 11. TABLA DE COMPONENTES (referencia rápida)

| Componente | Archivo | Rol |
|---|---|---|
| Tabla | `angelow.sql:114-146` + FKs `:233-235` + seed `:1735-1755` | Modelo de datos |
| Modelo | `app/Models/CategoriaModel.php` | CRUD + conteos + slug |
| API | `app/Controllers/Api/CategoriesController.php` | 5 endpoints admin-guarded |
| Rutas | `config/routes.php:63-67` | `/api/categorias*` |
| Panel (vista) | `app/Views/admin/panel.php:211-236` | Sección "Categorías" |
| Panel (lógica) | `public/assets/js/panel.js:2-18, 141-154, 600-907` | Gestión en localStorage |
| Sitio público | `public/assets/js/bienvenida.js:36, 708-716, 850-915` | Barra de categorías y filtrado |
| Inventario | `public/assets/js/inventario.js:44-66` | Filtro de categorías del demo |
| Legado (muerto) | `app/Controllers/Api/{config,categories,products}.php` | CRUD procedural anterior (roto) |
| Consumidores de nombres | `ProductoModel`, `ProductsController`, `StockController`, `CarritoController` | JOIN `categorias` para etiquetas |