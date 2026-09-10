# GUION DE EXPOSICIÓN — CATEGORÍAS Y SUBCATEGORÍAS (ANGELOW)

> **Proyecto:** ANGELOW — Tienda en línea de ropa infantil (Colombia).
> **Uso:** guion para presentar el módulo de categorías/subcategorías ante una audiencia técnica (docente/jurado). Texto narrable en primera persona. Cuando algo NO existe en el código se lee textualmente: `NO SE ENCONTRÓ ESTE MECANISMO EN EL CÓDIGO ANALIZADO.`
> **Documento técnico completo:** `docs/DOCUMENTACION_TECNICA_CATEGORIAS_SUBCATEGORIAS.md`.
> **Estimación:** ~4-5 min. Bloques `⭐ CÓDIGO CLAVE PARA EXPOSICIÓN` = pausa y mostrar el archivo.

---

## 1. CONTEXTO (qué voy a exponer)

"Hola. En la presentación anterior cubrí el sistema de roles, la autenticación JWT y el manejo de pedidos. Hoy voy a profundizar en el **módulo de categorías y subcategorías**, que es el corazón de la navegación de la tienda: la barra de filtros que el usuario ve arriba —*Bebés, Niños, Niñas, Edición especial, Oferta, Popular*—.

Les adelanto el mensaje principal, porque es honesto y técnicamente importante: **en ANGELOW este módulo existe en dos capas paralelas**. Por un lado hay un backend real con tabla en MySQL, modelo y API REST. Por otro, la **interfaz visual** de administración y del público trabaja con `localStorage` del navegador. Acompáñenme a ver cada pieza."

---

## 2. EL MODELO DE DATOS (MySQL)

"La base se define en `angelow.sql`. La tabla se llama `categorias`, y tiene un detalle que les va a gustar: **es autorreferenciada**. Un mismo registro funciona como 'categoría principal' si su `parent_id` es `NULL`, o como 'subcategoría' si apunta a otra fila. Además hay una columna `tipo` que distingue `categoria`, `subcategoria` y `etiqueta`."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `angelow.sql:114-146` (mostrar el CREATE TABLE):

```sql
CREATE TABLE `categorias` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,              -- único
  `parent_id` int unsigned DEFAULT NULL,     -- auto-referencia: NULL = categoría, no nulo = subcategoría
  `tipo` enum('categoria','subcategoria','etiqueta') DEFAULT 'categoria',
  ... 
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `fk_categorias_parent` FOREIGN KEY (`parent_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB ...
```

"Los productos se conectan con **dos llaves foráneas** en `angelow.sql:233-235`: `categoria_id` es obligatoria y con `ON DELETE RESTRICT` (no deja borrar una categoría que tenga productos), y `subcategoria_id` es opcional con `ON DELETE SET NULL` (si borras la subcategoría, el producto queda sin ella pero no se borra)."

"Y la base viene sembrada con datos reales — `angelow.sql:1735-1755`: cuatro categorías —Bebés, Niños, Niñas, Zapatos— y cinco subcategorías —Edición Especial, Conjuntos, Ropa Deportiva, Vestidos y Tenis—, cada una colgando de su categoría."

---

## 3. EL MODELO (árbol de consultas)

"El modelo `CategoriaModel` usa PDO con sentencias preparadas —patrón de todo el proyecto—. El método más interesante es `getAll()` (líneas 14 a 23), porque devuelve cada categoría **con dos subconsultas de conteo**: cuántos productos tiene y cuántas subcategorías cuelgan de ella. Fíjense en el `ORDER BY c.orden ASC, c.nombre ASC`: el `orden` es la columna que arma la secuencia de la barra de filtros."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `app/Models/CategoriaModel.php:14-23`:

```php
public function getAll() {
    $sql = "SELECT c.*,
            (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) as total_productos,
            (SELECT COUNT(*) FROM categorias WHERE parent_id = c.id) as total_subcategorias
            FROM categorias c
            WHERE c.tipo = 'categoria'
            ORDER BY c.orden ASC, c.nombre ASC";
    // ...
}
```

"Y `getAllWithSub()` (líneas 25-33) hace lo mismo pero para `tipo = 'subcategoria'`; así la API puede responder 'dame todas las categorías' y 'dame todas las subcategorías' por separado, o juntas."

---

## 4. LA API REST (backend real, con guardián de rol)

"Cada endpoint pasa primero por un guardián de rol. Vean `checkAdmin()` en las líneas 14-20: si no hay sesión, o el rol no es `administrador`, responde **403 con JSON**. Es la misma técnica de 'middleware casero' del proyecto."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `app/Controllers/Api/CategoriesController.php:14-20`:

```php
private function checkAdmin() {
    if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'administrador') {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
}
```

"Con eso el CRUD completo está en el controlador – `show`, `store`, `update`, `destroy`, todos con su código de estado: 404 si la categoría no existe, 400 si el nombre viene vacío, 500 si MySQL falla. Las rutas están registradas en `config/routes.php:63-67`, y responden en `/api/categorias`."

---

## 5. EL PATRÓN DE FICHAS VIOLETA — CADA PIEZA DEL MÓDULO

| Pieza | Archivo:líneas | Responsabilidad |
|---|---|---|
| Tabla `categorias` | `angelow.sql:114-146` | Datos, auto-referencia, `tipo`, índices |
| FKs de productos | `angelow.sql:233-235` | `RESTRICT` / `SET NULL` |
| Semilla | `angelow.sql:1735-1755` | 4 categorías + 5 subcategorías |
| Modelo | `app/Models/CategoriaModel.php:14-100` | CRUD + conteos + `generarSlug` |
| API | `app/Controllers/Api/CategoriesController.php:14-104` | 5 endpoints con `checkAdmin` |
| Rutas | `config/routes.php:63-67` | `/api/categorias*` |
| Vista del panel | `app/Views/admin/panel.php:211-236` | Sección "Categorías" del admin |
| Lógica del panel | `public/assets/js/panel.js:600-907` | Render + modales + borrado |
| Sitio público | `public/assets/js/bienvenida.js:36,708-716,850-915` | Barra y filtrado |
| Legacy (muerto) | `app/Controllers/Api/{config,categories,products}.php` | CRUD procedural anterior |

---

## 6. LA SORPRESA HONESTA — LA UI TRABAJA EN `localStorage` (2 min)

"Ahora viene lo importante. Si abrimos el panel y añadimos una categoría… y después inspeccionamos el tráfico de red, **no veremos ninguna llamada a `/api/categorias`**. ¿Por qué? Porque la gestión visual del panel está construida sobre `localStorage`."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `public/assets/js/panel.js:2-18`:

```js
let mainCategories = JSON.parse(localStorage.getItem('angelow_main_categories')) || [
  { id: 1, nombre: "Bebés", enBarra: true, productos: 24 },
  { id: 2, nombre: "Niños", enBarra: true, productos: 32 },
  { id: 3, nombre: "Niñas", enBarra: true, productos: 41 },
  { id: 4, nombre: "Edición especial", enBarra: true, productos: 3 },
  { id: 5, nombre: "Oferta", enBarra: true, productos: 12 },
  { id: 6, nombre: "Todos", enBarra: true, productos: 150 },
  { id: 7, nombre: "Popular", enBarra: true, productos: 25 }
];
let subCategories = JSON.parse(localStorage.getItem('angelow_sub_categories')) || [
  { id: 101, nombre: "Body's", padre: "Bebés", enBarra: true, productos: 10 },
  { id: 102, nombre: "Pijamas", padre: "Bebés", enBarra: true, productos: 8 },
  { id: 103, nombre: "Vestidos", padre: "Niñas", enBarra: true, productos: 15 },
  { id: 104, nombre: "Conjuntos", padre: "Niños", enBarra: true, productos: 22 },
  { id: 105, nombre: "Accesorios", padre: "Niñas", enBarra: false, productos: 7 }
];
```

"Cuando el admin guarda, `saveAllData()` —líneas 141-147— escribe en cuatro claves de `localStorage`, y `updateClientCategories()` —líneas 149-154— arma la lista que verá el cliente: el prefijo 'Todos' más las que tengan la bandera `enBarra: true`. La comunicación entre la pestaña del administrador y la pestaña de la tienda **no pasa por la red**: pasa por el evento `storage` del navegador (`bienvenida.js:716`)."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN (cliente)** — `public/assets/js/bienvenida.js:36` y `:850-867`:

```js
let categories = JSON.parse(localStorage.getItem('angelow_client_categories'))
  || ["Todos","Bebés","Niños","Niñas","Popular","Edición especial","Oferta"];
```

"Y aquí está el detalle fino del filtrado: `getFilteredProducts()` en las líneas 850-867. Si el usuario toca *Popular*, *Edición especial* u *Oferta*, el filtro compara contra `p.subcategory`; con cualquier otro nombre compara contra `p.category`. Así, esas tres 'categorías especiales' **no existen como fila en la BD**: son nombres que el frontend interpreta como subcategorías."

⭐ **CÓDIGO CLAVE PARA EXPOSICIÓN** — `public/assets/js/bienvenida.js:850-867`:

```js
function getFilteredProducts() {
  const normalizedCategory = activeCategory.trim().toLowerCase();
  return products.filter(p => {
    if (activeCategory !== "Todos") {
      if (["Popular","Edición especial","Oferta"].some(cat => cat.toLowerCase() === normalizedCategory)) {
        return p.subcategory.toLowerCase() === normalizedCategory;
      } else {
        return p.category.toLowerCase() === normalizedCategory;
      }
    }
    return true;
  }).filter(p => {
    if (!searchQuery) return true;
    return p.name.toLowerCase().includes(searchQuery) ||
           p.category.toLowerCase().includes(searchQuery) ||
           p.subcategory.toLowerCase().includes(searchQuery);
  });
}
```

> **Transparencia:** precisamente por eso, los cambios de categoría que haga el admin en el panel **no se guardan en MySQL** — solo en el navegador. El backend CRUD está terminado y funcional, listo para conectarse; la **integración** entre ambos no está en el código actual. Eso es un hallazgo, no un error inventado: lo comprobé revisando que ninguna llamada `fetch('/api/categorias')` exista en `panel.js`, y no aparece.

---

## 7. CÓDIGO LEGACY Y NOTAS DE CÓDIGO MUERTO

"Existen además dos archivos procedurales: `app/Controllers/Api/categories.php` y `products.php`. Parecen otra versión del CRUD, ¡pero no los va a ejecutar nadie!":

- **No los invoca el Router**: solo se enrutan los controladores namespaced (`Api\CategoriesController`, `Api\ProductsController`).
- **No los sirve Apache**: están en `app/Controllers/`, fuera de `public/`.
- **Y están rotos**: usan `$pdo->query(...)` (`categories.php:10`), pero su conexión `config.php` define `$conn` con **mysqli** y nunca define `$pdo`. → `NO ES POSIBLE EJECUTARLOS EN EL ESTADO ACTUAL DEL CÓDIGO ANALIZADO.`

"En resumen: el proyecto tiene **una API de categorías real y bien hecha** esperando ser consumida, una **UI de demostración** que funciona con `localStorage`, y un **legado roto** que documenté para que no cause confusión."

---

## 8. CIERRE (30 s)

"Hoy vimos: (1) el modelo autorreferenciado de `categorias` en MySQL con sus llaves de integridad; (2) el modelo que cuenta productos y subcategorías por categoría; (3) la API REST protegida por `checkAdmin`; y (4) con total honestidad, que las pantallas actuales construyen el árbol en `localStorage` al estilo demo, donde 'Edición especial', 'Oferta' y 'Popular' son filtros del frontend y no filas de la base. La integración UI↔API del módulo de categorías sería el siguiente paso natural del proyecto. ¿Preguntas?"