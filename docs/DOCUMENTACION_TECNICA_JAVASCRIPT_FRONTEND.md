# DOCUMENTACIÓN TÉCNICA — JAVASCRIPT FRONTEND (ANGELOW)

> **Proyecto:** ANGELOW — Tienda en línea de ropa infantil (Colombia).
> **Alcance:** documentación función por función de los **17 archivos JavaScript** de `public\assets\js\`, 100 % basada en el código real. Los números de línea se verificaron contra los archivos. Cuando un mecanismo no existe se indica con la nota `NO SE ENCONTRÓ ESTE MECANISMO EN EL CÓDIGO ANALIZADO.`
> **Referencias:** formato `public/assets/js/archivo.js:línea`.
> **Documentos hermanos:** `docs/DOCUMENTACION_TECNICA_ANGELOW.md` (sistema), `docs/DOCUMENTACION_TECNICA_CATEGORIAS_SUBCATEGORIAS.md` (categorías).

---

## 0. HALLAZGO TRANSVERSAL: DOS MUNDOS EN EL FRONTEND

El frontend convive con dos arquitecturas que **no están conectadas entre sí**:

| Mundo | Descripción | Archivos |
|---|---|---|
| **Pantallas reales (API PHP + MySQL)** | Carrito, favoritos, pedidos admin, clientes, repartidores admin, dashboard, compra, mis-pedidos (facturas), contactenos, auth | `bienvenida.js` (parcial), `panel.js` (parcial), `compra.js`, `perfil.js` (parcial), `login.js`, `contactenos.js` |
| **Pantallas demo (datos hardcodeados / `localStorage`)** | Catálogos de producto, categorías del panel, inventario, seguimiento del repartidor, tablero del repartidor | `panel.js` (categorías/productos), `bienvenida.js` (productos de portada), `inventario.js`, `seguimiento.js`, `seguimiento-perfil.js`, `repartidor.js`, `carrusel.js` |

Resultados medidos (verificados en el código):
1. **Catálogo de "los 8 productos" duplicado en 5 archivos** con datos distintos: `bienvenida.js:2-11`, `panel.js:20-29`, `inventario.js:44-55`, `perfil.js:32-41`, `seguimiento.js:2-5` (cada uno con precios y stock propios).
2. **El tablero del repartidor NO consume `/api/repartidor/*`**: `repartidor.js` usa un objeto `datosApp` hardcodeado y su único `fetch` es la geocodificación pública **Nominatim** (`:211`). El backend `/api/repartidor/*` existe (`config/routes.php:106-136`) pero **no lo usa esta UI** → `NO SE ENCONTRÓ CONSUMO DE `/api/repartidor/*` EN `repartidor.js`.`
3. **El seguimiento del repartidor es una SIMULACIÓN local**: `seguimiento.js` mueve un marcador "Carlos Rodríguez" hardcodeado (`:471-475`) por coordenadas de una ruta. Su único `fetch` también es Nominatim (`:641`, `:938`); **no consulta pedidos reales**. `SeguimientoController` solo renderiza la vista (`app/Controllers/SeguimientoController.php:15`).
4. **`inventario.js` es 100 % demo**: `NO SE ENCONTRÓ LLAMADA A `/api/inventario` EN `inventario.js`.` El CRUD de stock muta un array en memoria.
5. **`login.js` no usa `localStorage`** (el "remember me" viaja solo en el body del POST `/auth/login`, `:261`); `perfil.js` **no valida mayoría de edad** ("edad ≥ 1", `validarFechaNacimiento:95-108`).

---

## 1. MAPA DE ARCHIVOS

| Archivo | Líneas | Rol / página |
|---|---|---|
| `bienvenida.js` | 1242 | Portada: catálogo, barra de categorías, carrito, favoritos, rasca-gana |
| `panel.js` | 2565 | Panel de administración (mayor archivo) |
| `login.js` | 550 | Login / registro / recuperar contraseña |
| `perfil.js` | 1375 | Perfil del cliente (datos, direcciones, tarjetas, facturas, favoritos) |
| `compra.js` | 805 | Checkout en 3 pasos |
| `seguimiento.js` | 1005 | Mapa de seguimiento de entrega (simulado) |
| `seguimiento-perfil.js` | 690 | Seguimiento dentro del perfil (variante con prefijo `seg*`) |
| `repartidor.js` | 762 | Tablero del repartidor (demo con `datosApp`) |
| `inventario.js` | 304 | Inventario admin (demo en memoria) |
| `carrusel.js` | 377 | Carrusel hero, ofertas relámpago, tarjeta rasca-gana |
| `contactenos.js` | 98 | Formulario de contacto |
| `pedidos_envios.js` | 54 | Página informativa Pedidos y Envíos (navegación/anclas) |
| `terminos.js` | 48 | Términos y condiciones (navegación/anclas) |
| `preguntas.js` | 69 | FAQ (acordeón) |
| `politicas_priv.js` | 47 | Política de privacidad |
| `politicas_env.js` | 47 | Política de envíos (idéntico a priv) |
| `guia_tallas.js` | 94 | Guía de tallas |

---

## 2. CLAVES DE `localStorage` USADAS POR EL FRONTEND (tabla maestra)

| Clave | Lee | Escribe | Archivo:línea |
|---|---|---|---|
| `angelow_main_categories` | ✓ | ✓ | panel.js:2, 142, 2493 |
| `angelow_sub_categories` | ✓ | ✓ | panel.js:12, 143, 2494 |
| `angelow_products` | ✓ | ✓ | panel.js:20, 144, 2495 |
| `angelow_orders` | ✓ | ✓ | panel.js:87, 79 (escribe también 145) |
| `angelow_cart` | ✓ | ✓ | bienvenida.js, panel.js, perfil.js, compra.js, seguimiento.js |
| `angelow_cart_guest` | ✓ | ✓ | compra.js:223 |
| `cart` (legacy) | ✓ | ✓ | compra.js:212, ajuste en 652 |
| `angelow_favorites` | ✓ | ✓ | bienvenida.js, panel.js:131/1923, perfil.js:1208, seguimiento.js:8/12 |
| `angelow_favorites_${email}` / `_${id}` | ✓ | ✓ | dinámicas vía `getFavoritesStorageKey()` (bienvenida.js:60-64, perfil.js:26-30) |
| `angelow_user` | ✓ | ✓ | bienvenida.js:736/836, perfil.js:9, seguimiento.js:9/826 |
| `angelow_profile` | ✓ | ✓ | perfil.js:8, 415, 422 |
| `angelow_addresses` | ✓ | ✓ | perfil.js:4, 541, 565, 590, 606 |
| `angelow_cards` | ✓ | ✓ | perfil.js:6, 1147, 1194, 1201 |
| `angelow_client_categories` | ✓ | ✓ | bienvenida.js:36, 709; panel.js:153 (escribe); story event 716 |
| `angelow_solicitudes` | ✓ | ✓ | panel.js:1499, 1510, 1721, 1754 |
| `angelow_delivery_drivers` | ✓ | ✓ | panel.js:1520, 1531 |
| `angelow_ruta_guardada` | ✓ | ✓ | seguimiento.js:806, 925; seguimiento-perfil.js:601 |
| `destinoEntrega` | ✓ | ✓ | seguimiento.js:769, 772 (borra) |
| `promoCode` | ✓ | ✓ | compra.js:8, 342, 348, 354, 654 |
| `angelow_pending_favorite` | ✓ | ✓ | bienvenida.js:1213, 1217, 1219 |
| `cart_session_id` | ✓ | ✓ | bienvenida.js:48, 54 (+ cookie `cart_session` l.55) |
| `nuevoPedido` (sessionStorage) | — | ✓ | compra.js:540, 646 |

---

## 3. ENDPOINTS QUE CONSUME EL FRONTEND (tabla maestra)

| Endpoint | Método | Dónde |
|---|---|---|
| `/auth/login` | POST | login.js:276 |
| `/auth/register` | POST | login.js:355 |
| `/auth/forgot-password` | POST | login.js:403 |
| `/auth/google` (form action) | POST | login.js:463, 489 |
| `/auth/logout` (navegación) | GET | bienvenida.js:846, perfil.js:246, seguimiento.js:826(clear nattv) |
| `/api/carrito` | GET | bienvenida.js:203 |
| `/api/carrito/agregar` | POST | bienvenida.js:352, 650, 1018 |
| `/api/carrito/eliminar` | DELETE | bienvenida.js:394 |
| `/api/carrito/actualizar` | POST | bienvenida.js:470 |
| `/api/carrito/sincronizar` | POST | bienvenida.js:724 |
| `/api/carrito/vaciar` | POST | compra.js:659 |
| `/api/favoritos` | GET | bienvenida.js:526 |
| `/api/favoritos/agregar` | POST | bienvenida.js:547, 583 |
| `/api/favoritos/eliminar` | DELETE | bienvenida.js:576 |
| `/api/pedidos` | GET | panel.js:36 |
| `/api/pedidos/{id}` | GET | panel.js:1169 |
| `/api/pedidos/estado` | POST | panel.js:98 |
| `/api/mis-pedidos` | GET | perfil.js:622 |
| `/api/mis-pedidos/{id}` | GET | perfil.js:761-765 |
| `/api/clientes` | GET | panel.js:1344 |
| `/api/clientes/buscar` | POST | panel.js:1367 |
| `/api/clientes/rol` | POST | panel.js:1448 |
| `/api/admin/repartidores/solicitudes` | GET | panel.js:1504 |
| `/api/admin/repartidores/activos` | GET | panel.js:1525 |
| `/api/admin/repartidores/estadisticas` | GET | panel.js:1541 |
| `/api/admin/repartidores/solicitudes/aprobar` | POST | panel.js:1712 |
| `/api/admin/repartidores/solicitudes/rechazar` | POST | panel.js:1745 |
| `/api/admin/repartidores/suspender` | POST | panel.js:1776 |
| `/api/admin/repartidores/activar` | POST | panel.js:1804 |
| `/api/admin/dashboard/stats` | GET | panel.js:2388 |
| `/api/cupones/validar` | POST | compra.js:332, 365 |
| `/procesar-compra` | POST | compra.js:494 |
| `/contacto/enviar` | POST | contactenos.js:67 |
| Nominatim OpenStreetMap (`https://nominatim.openstreetmap.org/search?...`) | GET | seguimiento.js:641, 938; seguimiento-perfil.js:387, 613; repartidor.js:211 |

> **NO se consumen desde el frontend**: `/api/categorias*` (core del backend queda sin consumir por la UI de categorías), `/api/productos*`, `/api/inventario`, `/api/repartidor/*`, `/api/repartidor/rastreo/*` — todos existen en `config/routes.php` pero ninguna UI los usa.

---

# PARTE A — PÁGINAS CON BACKEND REAL

## A.1 `bienvenida.js` (1242 líneas) — Portada

### Variables y constantes globales
| Nombre | Líneas | Detalle |
|---|---|---|
| `products` (const) | 2-11 | 8 productos hardcodeados (catálogo de portada; NO es MySQL) |
| `defaultProductConfig` (const) | 13-22 | Respaldo price/stock/imgs por id |
| `categories` | 36 | `localStorage.angelow_client_categories` o defaults ["Todos","Bebés","Niños","Niñas","Popular","Edición especial","Oferta"] |
| `cart`, `favorites`, `activeCategory`, `selectedSizes`, `searchQuery`, `currentUser` | 39-44 | Estado de la sesión del cliente |
| `scratchRevealed`, `canvas`, `ctx`, `isDrawing`, `scratchPixels`, `requiredPixels` | 1046-1049 | Tarjeta rasca-gana |

### Inventario de funciones
| Función | Líneas | Propósito |
|---|---|---|
| `getSessionId()` | 47-58 | UUID v4 de sesión invitado; guarda `cart_session_id` + cookie `cart_session` |
| `getFavoritesStorageKey(user = currentUser)` | 60-64 | Clave de favoritos por usuario: `angelow_favorites_${email}` → `_${id}` → `angelow_favorites` |
| `apiFetch(url, options = {})` | 66-82 | Wrapper único de `fetch` JSON (credentials same-origin, toasts de error) |
| `normalizeCart()` | 85-159 | Normaliza ítems del carrito (mezclando servidor/local) |
| `escapeHtml(value)` | 161-168 | Anti-XSS para strings renderizadas |
| `resolveImageUrl(imagePath)` | 170-177 | Convierte rutas relativas a absolutas con `APP_URL` |
| `ensureSelectedSize(productId)` | 179-186 | Talla por defecto de un producto |
| `updateProductStock(productId, change)` | 189-198 | Descuenta/añade stock en el array `products` |
| `loadCartFromServer()` | 201-223 | GET `/api/carrito` con respaldo a `angelow_cart` local |
| `updateCartBadge()` | 225-232 | Contador del carrito en cabecera |
| `createCartItem(product, size, cartId, quantity)` | 234-252 | Construye un ítem de carrito normalizado |
| `getProductImageUrl(item)` | 254-261 | URL de imagen principal del ítem |
| `renderCart()` | 263-331 | Pinta el carrito; listeners `qty-minus` (305-312), `qty-plus` (314-321), `remove` (323-330) |
| `addToCart(pid)` | 333-382 | POST `/api/carrito/agregar` (o local si invitado) + respaldo local |
| `removeFromCart(cartId)` | 384-417 | DELETE `/api/carrito/eliminar` + respaldo local |
| `updateQty(cartId, delta)` | 419-504 | POST `/api/carrito/actualizar` con validación de stock |
| `saveFavoritesToLocal()` | 507-509 | Persiste favoritos en clave por usuario |
| `loadFavoritesFromLocal()` | 511-518 | Carga favoritos locales |
| `loadFavoritesFromServer()` | 520-537 | GET `/api/favoritos` |
| `syncLocalFavoritesToServer()` | 539-557 | POST `/api/favoritos/agregar` de favoritos locales y limpia |
| `updateFavBadges()` | 559-567 | Contadores de favoritos |
| `toggleFavorite(id)` | 569-611 | Agrega/elimina favorito (POST/DELETE `/api/favoritos/*`) |
| `renderFavorites()` | 613-636 | Pinta el overlay de favoritos |
| `addFavoriteToCart(id)` | 638-676 | POST `/api/carrito/agregar` desde favoritos |
| `showToast({title, message, type, duration})` | 679-705 | Toasts temporales |
| `loadCategories()` | 708-715 | Carga `angelow_client_categories` y re-renderiza |
| `loadUser()` | 718-742 | Inicializa `currentUser` desde `window.phpUser` o `angelow_user`; POST `/api/carrito/sincronizar` |
| `crearMenuItem(id, href, etiqueta, icono)` | 744-756 | Crea ítem `<a>` de menú desplegable |
| `insertarFacturas(ancla)` | 758-769 | Inserta enlace "Facturas" en el menú |
| `updateUserUI()` | 771-829 | Menu según rol: admin/repartidor/cliente/invitado |
| `logout()` | 831-847 | Cierra sesión local y redirige a `/auth/logout` |
| `getFilteredProducts()` | 850-867 | Filtro por categoría (Popular/Edición especial/Oferta → por `subcategory`) + búsqueda |
| `renderCategories()` | 869-874 | Pinta la barra de categorías **×3** (`[...categories,...categories,...categories]`) |
| `renderProducts()` | 876-913 | Grilla de productos (detalle, tallas, carrito, favoritos inline) |
| `setCategory(cat)` | 915 | Cambia categoría activa y re-renderiza |
| `selectSize(id, size)` | 916-920 | Guarda talla seleccionada |
| `openCart()` / `closeCart()` | 923-931 | Overlay del carrito |
| `proceedToCheckout()` | 932-940 | Redirige a compra o login |
| `openFavorites()` / `closeFavorites()` | 942-952 | Overlay de favoritos |
| `openProductDetail(id)` | 955-986 | Modal de detalle (miniaturas, tallas, vueltos) |
| `closeProductDetail()` | 987-990 | Cierra el modal |
| `buyNowProduct(id)` | 992-1001 | Agrega y redirige a compra/login |
| `addToCartFromDetail(id)` | 1003-1043 | POST `/api/carrito/agregar` desde el detalle |
| `getNewProducts()` | 1051-1053 | Productos de "Edición Especial" (rasca-gana) |
| `renderNewProductsGrid()` | 1055-1076 | Grid de ofertas desbloqueadas |
| `initScratchCard()` | 1078-1136 | Canvas de rascar (mousedown/move/up + touch) |
| `checkPendingFavorite()` | 1212-1222 | Procesa `angelow_pending_favorite` si ya hay sesión |

### Inicialización y eventos
- `window.addEventListener('storage', ...)` `:716` → recarga categorías si cambian en otra pestaña.
- `document.addEventListener('DOMContentLoaded', ...)` `:1139-1210` → busqueda, overlays, menú, Escape.
- Top-level `:1225-1242`: `getSessionId()`, `loadUser()`, `loadCategories()`, `renderCategories()`, `renderProducts()`, `normalizeCart()`, `renderCart()`, `renderFavorites()`, `updateFavBadges()`, `checkPendingFavorite()`, `initScratchCard()` y alerta `?welcome` (`:1236-1242`).

### Hallazgos
- **No hay `window.X = ...`**; todo por declaraciones globales (alcanzables desde `onclick` del HTML generado).
- `NO SE ENCONTRÓ CONSUMO DE `/api/productos` NI DE `/api/categorias` EN ESTE ARCHIVO` (catálogo y categorías = localStorage/constantes).

---

## A.2 `login.js` (550 líneas) — Autenticación

### Referencias y navegación de formularios
| Elemento | Líneas | Detalle |
|---|---|---|
| `messageBox`, `loginForm`, `registerForm`, `forgotForm`, `tabs` | 2-6 | Referencias DOM |
| Normalización `APP_URL` | 9-11 | Quita `/` final (modifica `window.APP_URL`) |
| `tabs.forEach(...)` | 14-31 | Cambio login ↔ registro |
| enlace "Olvidé mi contraseña" | 497-506 | Muestra `forgotForm` |
| `#backToLogin` | 508-517 | Regresa al login |
| año `#currentYear` | 520-523 | Texto del footer |
| `setupEnterKey(...)` ×7 | 544-550 | Enter como atajo a cada botón |

### Funciones
| Función | Líneas | Propósito |
|---|---|---|
| `window.togglePassword` | 34-47 | Muestra/oculta contraseña (ojo) |
| `validatePasswordStrength` | 55-87 | Reglas ≥8, mayúscula, número, especial; barra weak/medium/strong |
| `validatePasswordMatch` | 95-115 | Compara contraseña vs confirmación |
| `showMessage` / `hideMessage` | 118-132 | Mensajes en `#messageBox` (auto-oculta 5 s) |
| `mostrarAlertaBienvenida` | 137-247 | Overlay de bienvenida según rol (admin/repartidor/pendiente/cliente) |
| `buildRedirectUrl` | 249-255 | Normaliza la URL de redirección contra `APP_URL` |
| `window.handleEmailLogin` | 258-306 | POST `/auth/login` con `{email, password, remember}` (`:276-279`); bienvenida |
| `window.handleEmailRegister` | 309-384 | POST `/auth/register` con `{email, nombre, password, terms, newsletter}` (`:355-358`) |
| `window.handleForgotPassword` | 387-428 | POST `/auth/forgot-password` (`:403`); vuelve al login a los 3 s |
| `showLoading` | 431-447 | Estado de carga de un botón |
| `window.handleCredentialResponse` | 450-473 | Google JWT (atob) → form a `/auth/google` (`:463`) |
| `window.demoLogin` | 476-494 | Usuario demo → form a `/auth/google` o `?demo=1` |
| `setupEnterKey` | 531-542 | Enter → click del botón |

### Hallazgos
- `NO SE ENCONTRÓ VALIDACIÓN DE MAYORÍA DE EDAD NI DE DOCUMENTO EN `login.js`` (solo formato de email y campos requeridos).
- `NO SE ENCONTRÓ USO DE `localStorage` EN `login.js`` (remember solo en el body del fetch).

---

## A.3 `perfil.js` (1375 líneas) — Perfil del cliente

### Estado global
| Variable | Líneas | Detalle |
|---|---|---|
| `cart`, `addresses`, `orders`, `cards` | 3-6 | Desde `angelow_cart`, `angelow_addresses`, `angelow_orders`, `angelow_cards` |
| `favorites` | 7 | Array local |
| `profileData` | 8 | Desde `angelow_profile` |
| `currentUser` | 9 | `window.CURRENT_USER` → `angelow_user` → null |
| `STOCK_LIMITS` | 15-24 | tope de stock por producto |
| `products` (const) | 32-41 | Catálogo estático de 8 productos (otra copia) |

### Validaciones (editables del perfil)
| Función | Líneas | Regla |
|---|---|---|
| `validarCedula` | 68-75 | 6-15 dígitos |
| `validarTelefono` | 77-84 | 7-15 dígitos |
| `validarNombre` | 86-93 | 2-50 letras/espacios |
| `validarFechaNacimiento` | 95-108 | No futura; edad 1..120 (**sin 18**) |
| `validarNumeroTarjeta`/`validarCVV`/`validarFechaExpiracion`/`validarCodigoPostal`/`validarNombreTitular` | 115-162 | Reglas de tarjeta |
| `marcarCampoError`/`limpiarErroresCampos`/`mostrarErrorEnCampo` | 187-225 | Visual de errores |

### Módulos
| Módulo | Funciones (líneas) |
|---|---|
| Stock (carrito perfil) | `getStockLimit` (165-167), `getCurrentQuantityInCart` (169-172), `canAddToCart` (174-178), `getRemainingStock` (180-184) |
| Sesión | `showLogoutConfirm` (228-238), `confirmLogout` (240-248, redirige a `/auth/logout`) |
| Carrito | `renderCartProfile` (251-319), `updateQtyProfile` (321-341, escribe `angelow_cart`), `removeFromCartProfile` (343-348) |
| Datos personales | `toggleEdit` (351-371), `saveProfile` (373-419, escribe `angelow_profile`), `loadProfile` (421-429) |
| Direcciones | `renderAddresses` (434-482), `showAddressForm` (484-489), `cancelAddressForm` (491-501), `saveAddress` (503-569), `editAddress` (571-586), `setDefaultAddress` (588-593), `deleteAddressConfirmed` (601-609) |
| Pedidos/Facturas | `loadOrders` (612-618), `fetchOrdersFromAPI` (620-636, GET `/api/mis-pedidos`), `getStatusLabel/Class/Color` (638-699), `renderOrders` (701-751), `viewInvoice` (753-786, GET `/api/mis-pedidos/{id}`), `showInvoiceModal` (788-920), `closeOrderModal` (922-929), `descargarFacturaPDF` (931-1016, jsPDF + html2canvas) |
| Tarjetas | `renderCards` (1021-1076), `showCardForm` (1078-1088), `cancelCardForm` (1090-1102), `saveCard` (1104-1151), `initCardPreview` (1153-1181), `deleteCardConfirmed` (1189-1197), `setDefaultCard` (1199-1204) |
| Favoritos | `getFavoritesStorageKey` (26-30), `loadFavorites` (1207-1211), `renderFavorites` (1213-1237), `showDeleteFavoriteAlert` (1239-1243), `deleteFavoriteConfirmed` (1245-1250), `addToCartFromFavorites` (1252-1292) |
| Alertas | `showAlert` (1295-1299), `closeAlert` (1301-1305), `confirmDelete` (1307-1321) |
| Stubs | `definePassword`, `recoverPassword`, `viewSessions`, `enableTwoFactor` (1324-1338) — toasts "Funcionalidad en desarrollo" |
| Menú | `initSidebar` (1341-1361); al entrar a seguimiento delega en `initSeguimiento()` (`:1351-1352` → `seguimiento-perfil.js`) |
| Arranque | `DOMContentLoaded` (1364-1375): `loadProfile`, `loadAddresses`, `loadOrders`, `loadCards`, `loadFavorites`, `initSidebar`, año, `renderCartProfile` |

### Hallazgos
- Las **facturas sí vienen de la API** (`/api/mis-pedidos`), pero **direcciones, tarjetas y perfil solo viven en `localStorage`** (no hay API de direcciones/tarjetas consumida) → `NO SE ENCONTRÓ ENDPOINT DE DIRECCIONES/TARJETAS/PERFIL CONSUMIDO DESDE `perfil.js``.
- Sin `window.X = ...`; todo por declaraciones globales.

---

## A.4 `compra.js` (805 líneas) — Checkout

### Estado y flujo
| Elemento | Líneas |
|---|---|
| Variables `carrito`, `descuentoAplicado`, `codigoDescuento`, `costoEnvio`, `datosUsuario`, `ultimoPedido` | 15-20 |
| Inicialización (DOMContentLoaded) | 1-13 |

### Funciones
| Función | Líneas | Propósito |
|---|---|---|
| `inicializarValidaciones()` | 23-53 | blur/input en campos de pasos 1 y 2 |
| `validarCampo` | 55-65 | Marca error/success |
| `validarEmail`/`validarTelefono`/`validarCedula` | 67-80 | Regex por campo |
| `obtenerUbicacionUsuario()` | 82-94 | `navigator.geolocation` (timeout 10 s) |
| `validarPaso1()` / `validarPaso2()` | 96-154 | Check de datos + términos / envío |
| `cargarDatosUsuario()` | 156-165 | Precarga desde `window.CURRENT_USER` |
| `showToast(mensaje, tipo)` | 167-190 | Toasts 4 s |
| `calcularDescuento(codigo, subtotal)` | 192-196 | **Función obsoleta**: retorna 0 (el cupón se valida en backend) |
| `cargarCarrito()` | 199-240 | Precedencia: `angelow_cart` → `cart` → `angelow_cart_guest` |
| `renderizarResumen()` | 243-292 | Lista del resumen de compra |
| `actualizarTotales()` | 294-317 | subtotal − descuento + envío |
| `window.applyPromo` | 320-357 | POST `/api/cupones/validar` (`:332`); guarda/limpia `promoCode` |
| `aplicarPromoGuardado()` | 359-380 | Revalida silenciosa del cupón guardado (`:365`) |
| `window.selectShipping` | 383-391 | Envío normal (0) o express (15000) |
| `window.selectPayment` | 393-398 | Marca método de pago (`mercadopago`/`pse`) |
| `seleccionarOpcionesPorDefecto()` | 400-405 | Preselecciona normal + PSE |
| `window.goToStep` | 408-442 | Navegación de pasos con validación previa |
| `generarNumeroPedidoLocal()` | 445-447 | Devuelve siempre `'PENDIENTE'` |
| `guardarPedido(pedidoInfo)` | 450-550 | POST `/procesar-compra` (`:494`); guarda en `angelow_orders` + sessionStorage `nuevoPedido` |
| `window.completePurchase` | 553-669 | Orquesta validación → POST → limpieza de claves + `/api/carrito/vaciar` (`:659`) |
| `mostrarConfirmacionPedido(data)` | 672-800 | HTML de confirmación (crea `#pedidoContainer`) |
| `actualizarAnioFooter()` | 802-805 | Año del footer |

### Objeto POST `/procesar-compra` (`:460-491`)
`{ usuario_id, numero_pedido, nombre_cliente, email_cliente, telefono_cliente, cedula_cliente, direccion_envio, direccion_complementaria, barrio, ciudad, departamento, destinatario, informacion_adicional, metodo_pago, metodo_envio, costo_envio, subtotal, descuento, total, latitud_destino, longitud_destino, productos[] }`

### Hallazgos
- **Pago por radiobutton** (`name="payment"`, valores `mercadopago`/`pse`) → `NO SE ENCONTRÓ FORMULARIO DE TARJETA DE CRÉDITO/DÉBITO EN `compra.js``; el único "pago" es selección de método.
- Limpieza post-compra (`:650-654`): `angelow_cart`, `cart`, `angelow_cart_guest`, `promoCode` + vaciar carrito en servidor.

---

## A.5 `contactenos.js` (98 líneas) — Contacto

- Todo dentro de `DOMContentLoaded` (`:1-98`).
- Reglas `rules` por campo (`:9-15`): nombre ≥3, email regex, teléfono `/^[\+\d\s\-]{7,}$/`, asunto ≥4, mensaje ≥10.
- `showFieldError` (17-20), `validateField` (22-27), listeners blur/input (30-40).
- Submit (`:43-88`): valida, **POST `/contacto/enviar`** (`:67`, body `{nombre,email,telefono,asunto,mensaje}`), resetea formulario.
- `showMessage` (90-97).
- `NO SE ENCONTRÓ USO DE `localStorage`/`sessionStorage` EN `contactenos.js``.

---

# PARTE B — PÁGINAS DEMO / SIMULACIÓN

## B.1 `panel.js` (2565 líneas) — Panel de administración

Es híbrido: **pedidos, clientes y repartidores SÍ consumen API**; **categorías y productos operan en `localStorage`**.

### Secciones del archivo
| Sección | Líneas |
|---|---|
| Datos globales (`mainCategories`, `subCategories`, `products`) | 1-30 |
| Pedidos en tiempo real (`cargarPedidos`, `cambiarEstadoPedido`) | 31-122 |
| Variables (charts, mapa, filtros, edición) | 123-139 |
| Almacenamiento (`saveAllData`, `updateClientCategories`, `showToast`) | 140-185 |
| Productos | 186-583 |
| Categorías | 584-908 |
| Pedidos (mapa, listas, detalle/factura, filtros) | 909-1334 |
| Refresh en tiempo real | 1335-1339 |
| Clientes | 1340-1496 |
| Repartidores (solicitudes, activos, stats) | 1497-1637 |
| Modal personalizado | 1638-1700 |
| Solicitudes (aprobar/rechazar/suspender/activar) | 1701-1875 |
| Carrito/favoritos del panel | 1876-1926 |
| Gráficos Chart.js | 1927-2097 |
| Navegación | 2098-2150 |
| Filtros + botones + modales | 2151-2246 |
| Exportar PDF (jsPDF + autoTable) | 2247-2384 |
| Métricas del dashboard | 2385-2463 |
| Listener `storage` | 2464-2490 |
| Inicialización (DOMContentLoaded) | 2491-2535 |
| Exposición `window.X` (28 líneas) | 2537-2565 |

### Funciones destacadas del backend real
| Función | Líneas | Endpoint |
|---|---|---|
| `cargarPedidos()` | 34-94 | GET `/api/pedidos` + fallback `angelow_orders` |
| `cambiarEstadoPedido(id, estado)` | 96-117 | POST `/api/pedidos/estado` |
| `verDetallesPedido(id)` | 1158-1297 | GET `/api/pedidos/{id}` |
| `cargarUsuarios()` / `buscarUsuarios(t)` | 1343-1383 | GET/POST `/api/clientes`, `/api/clientes/buscar` |
| `cambiarRol(id, rol)` | 1439-1476 | POST `/api/clientes/rol` |
| `cargarSolicitudes()` / `cargarRepartidoresActivos()` / `cargarEstadisticasRepartidores()` | 1498-1557 | GET `/api/admin/repartidores/*` (con caché localStorage) |
| `aprobarSolicitud` / `rechazarSolicitud` | 1703-1765 | POST solicitudes/aprobar | rechazar |
| `suspenderRepartidor` / `activarRepartidor` | 1767-1821 | POST `/api/admin/repartidores/suspender|activar` |
| `cargarDashboardStats()` | 2386-2400 | GET `/api/admin/dashboard/stats` |

### Características demo/visuales
| Función | Líneas |
|---|---|
| `initCharts()` (4 gráficos: ventas, productos, tráfico, conversión) | 1928-2096 |
| `exportOrdersToPDF()` / `exportarClientesPDF()` | 2248-2384 |
| `updateMetrics()` / `updateChartsWithRealData()` | 2402-2462 |
| `initMap()` / `updateMapMarkers()` (Leaflet Colombia) | 910-955 |
| Filtros `setupFilters()` | 2152-2175 |
| `initNavigation()` | 2099-2149 |

### Categorías y productos (solo localStorage — ver doc de categorías)
`renderMainCategories` (585-628), `renderSubCategories` (630-674), `updateCategorySelects` (676-710), `openCategoryModal` (712-774), `closeCategoryModal` (776-782), `saveCategory` (784-853), `deleteMainCategory`/`deleteSubCategory` (869-903), modales 2228-2245. `openProductModal` (395-451), `saveProduct` (470-582), `deleteProduct` (357-393).

### Listener `storage` (`:2465-2489`)
Reacciona a cambios de otras pestañas en `angelow_orders` (2466), `angelow_products` (2475), `angelow_cart` (2480), `angelow_favorites` (2484).

### Arranque (DOMContentLoaded `:2492-2535`)
Carga: `renderProducts`, `renderMainCategories`, `renderSubCategories`, `updateCategorySelects`, `cargarPedidos`, `cargarUsuarios`, `cargarSolicitudes`, `cargarRepartidoresActivos`, `cargarEstadisticasRepartidores`, `cargarDashboardStats`, `initCharts`, `initNavigation`, `setupFilters`, `setupOrderFilters`, `setupButtons`, `setupModal`, `setupCategoryModal`, `setupImageUpload`, `setupCustomerSearch`, `updateClientCategories`, `initMap`. **Polling**: `setInterval(cargarPedidos, 30000)` (`:2526-2528`) y `setInterval(solicitudes+activos+stats, 60000)` (`:2530-2534`).

### Hallazgos
- `window.X` exportadas: 28 asignaciones finales (`:2538-2565`) + definiciones directas (`removeImage` 232, `toggleSize` 237, `editProduct` 350, `deleteProduct` 357, `editMainCategory` 855, `editSubCategory` 862, `deleteMainCategory` 869, `deleteSubCategory` 892, `openCategoryModal` 905, `closeCategoryModal` 906, `saveCategory` 907, `selectOrder` 957, `editOrder` 1147, `verDetallesPedido` 1158, `cambiarRol` 1439).
- La única función con nombre `editingProductId` (125); **no existe** `editProductId`.

---

## B.2 `seguimiento.js` (1005 líneas) — Mapa de seguimiento (simulado)

### Características
- Catálogo local de 2 productos (`:2-5`); `cart`/`favorites`/`currentUser` desde `localStorage` (`:7-9`), con `saveCart`/`saveFavorites` (`:11-12`).
- Mapa Leaflet: `L.map('map')` centrado en Medellín (`:261`), 3 capas (`:263-273`), skeleton con fallback 5 s (`:278-293`).
- Iconos SVG (`teardropSvg` 340-356, `circleSvg` 358-377, `adjustColor` 379-385, `svgMarker` 387-395).

### Inventario de funciones
| Función | Líneas | Propósito |
|---|---|---|
| `showToast` | 15-89 | Toasts con barra de progreso |
| `renderCart` / `updateQty` / `removeFromCart` / `openCart` / `closeCart` / `proceedToCheckout` | 92-176 | Carrito y checkout |
| `updateFavBadges` / `renderFavorites` / `toggleFavorite` / `openFavorites` / `closeFavorites` | 179-256 | Favoritos |
| `getCachedGeocode` / `setCachedGeocode` | 318-326 | Caché de geocodificación |
| `bearing` / `easeInOut` | 328-338 | Rotación del marcador / easing |
| `updateStatus` | 418-425 | Indicador de estado |
| `updateRouteInfo` | 427-432 | Distancia/tiempo |
| `setStep` / `updateTrackingProgress` | 434-469 | Stepper y barra de progreso |
| `updateDriverInfo` | 471-475 | **Repartidor SIMULADO** "Carlos Rodríguez", "+57 300 123 4567" |
| `animateValue` / `updateRemainingTime` | 477-499 | Animaciones de números |
| `resetMapState` | 501-525 | Reinicia mapa/estado |
| `startTracking(totalTimeMinutes)` | 527-611 | **Simula** el viaje por `routeCoordinates` con `setInterval` (`:553`) |
| `normalizeAddress` | 613-621 | Expande abreviaturas (cl→Calle...) |
| `geocodeAddress` | 623-683 | **fetch GET Nominatim** (`:641`) |
| `drawEnhancedRoute` | 685-719 | Línea brillo + punteada animada + decorador |
| `calculateRoute` | 721-755 | `L.Routing.control`; inicia simulación a los 3 s |

### Eventos/escuchadores
- Geolocalización inicial (`:758-799`): centra, crea `userMarker`, lee **`destinoEntrega`** (`:769`, lo borra `:772`), calcula ruta.
- DOMContentLoaded (`:802-1003`): ruta guardada (`angelow_ruta_guardada` `:806-815`), menú perfil, logout (borra `angelow_user`/`angelow_cart`/`angelow_favorites`), búsqueda → `/index.html?search=`, carrito/favoritos, panel de controles, sugerencias, uso de ubicación, cálculo de ruta, guardado de ruta (escribe `angelow_ruta_guardada` `:925`), autocompletar Nominatim (`:938`), compartir, zoom, capas.

### Hallazgos
- `NO SE CONSULTA NINGÚN PEDIDO REAL`: no hay fetch a `/api/*` propio; el viaje es una animación sobre `routeCoordinates`.
- Claves: `angelow_cart`(7/11), `angelow_favorites`(8/12), `angelow_user`(9/826), `destinoEntrega`(769/772), `angelow_ruta_guardada`(806/925).

---

## B.3 `seguimiento-perfil.js` (690 líneas) — Variante dentro del perfil

- Toda la lógica con prefijo `seg*` y envoltura `initSeguimiento()`/`initSegMap()` para no chocar con `seguimiento.js`.
- **No toca carrito/favoritos/usuario**: la única clave localStorage es `angelow_ruta_guardada` (escritura `:601`).
- `initSeguimiento()` (`:29-39`) reutiliza o crea el mapa; `initSegMap()` (`:41-690`) contiene todo (mismas utilidades que seguimiento.js, versionadas `seg*`): `segGeocodeAddress` (fetch Nominatim `:387`), `segDrawEnhancedRoute` (429-457), `segCalculateRoute` (459-494), `segStartTracking` (262-356, simulación), y `initSegEventListeners` (521-689, autocompletar Nominatim `:613`).
- Diferencia clave con `seguimiento.js`: **no lee ni borra `destinoEntrega`** (bloque de geolocalización `:497-518`).

---

## B.4 `repartidor.js` (762 líneas) — Tablero del repartidor (demo)

### Datos
- `datosApp` (`:2-88`): `conductor`, `pedidosDisponibles`, `pedidosActivos`, `historialEntregas`, `notificaciones` — **todo hardcodeado**.

### Funciones
| Función | Líneas | Propósito |
|---|---|---|
| `formatearDinero` / `obtenerIniciales` | 108-115 | Formato de montos / avatar |
| `mostrarToast` | 118-160 | Toasts con FontAwesome |
| `iniciarMapa()` | 163-206 | Leaflet en Medellín; `navigator.geolocation.getCurrentPosition` (`:173`) |
| `geocodificarDireccion(dir)` | 209-232 | **único fetch** → Nominatim (`:211-216`) |
| `calcularRuta()` | 235-330 | Origen/destino, marcadores, `L.Routing.control`, inicia simulación |
| `iniciarSimulacionViaje(coords, tiempo)` | 333-387 | Mueve `driverMarker` con setInterval; "Entregado" |
| `actualizarEstadoMapa` / `limpiarRuta` | 390-435 | Estado del mapa / reset |
| `iniciarPanel()` | 438-460 | Rellena datos del conductor desde `datosApp` |
| `renderizarPedidosDisponibles` / `renderizarPedidosActivos` / `renderizarHistorial` | 462-607 | Tablero |
| `actualizarNotificaciones` | 609-612 | Badge de notificaciones |
| `aceptarPedido` / `rechazarPedido` / `recogerPedido` / `entregarPedido` | 615-673 | Mutaciones del objeto local (estado 'ready'/'picked', historial, ganancias) |
| `contactarCliente` / `usarDireccionEnMapa` | 675-688 | Toasts / ruta |
| `filtrarPedidos` / `limpiarFiltros` / `refrescarDatos` | 690-704 | Filtros (sin lógica real) y toast "Buscando nuevos pedidos..." |
| `verNotificaciones` / `verTodoHistorial` / `salir` | 706-721 | UI / logout a `index.html` |

### Hallazgos
- `NO SE ENCONTRÓ NINGÚN FETCH A `/api/repartidor/*` EN `repartidor.js`` ni listener `ONGEOLOCATION`; la única llamada externa es Nominatim (`:211`) y la geolocalización (`:173`).
- `NO SE ENCONTRÓ USO DE `localStorage` EN `repartidor.js``.

---

## B.5 `inventario.js` (304 líneas) — Inventario admin (demo)

### Funciones
| Función | Líneas | Propósito |
|---|---|---|
| `showToast` | 2-34 | Toasts |
| `loadInitialProducts()` | 44-55 | 8 productos hardcodeados |
| `updateCategoryFilter()` | 57-66 | Categorías únicas del array local → select |
| `getFilteredProducts()` | 68-83 | Filtra por stock/categoría/búsqueda |
| `renderInventoryTable()` | 85-111 | Tabla con `onclick="openStockEditModal(id)"` |
| `updateSummary()` | 113-122 | Contadores total/stock/agotados/bajo |
| `updateMaxLimit()` | 124-148 | Tope según tipo de cambio |
| `openStockEditModal` / `closeStockEditModal` | 150-166 | Modal de stock |
| `updateStock()` | 168-187 | Aplica cambio en memoria |
| `exportToPDF()` | 189-208 | jsPDF + autoTable |
| `initFilters()` | 210-241 | Botones, categoría, búsqueda, refresh, export |
| `initQuantityButtons()` | 243-273 | +/- del input de cantidad |

### Hallazgos
- `NO SE ENCONTRÓ LLAMADA A `/api/inventario` EN `inventario.js``; `NO SE ENCONTRÓ USO DE `localStorage` EN `inventario.js``. El backend `StockController` (`/api/inventario`) existe pero **no se consume**.
- `window.openStockEditModal`/`window.closeStockEditModal`/`window.updateStock` (`:302-304`).

---

## B.6 `carrusel.js` (377 líneas) — Carrusel de portada

Tres IIFE:
1. **Hero carousel** (`:2-256`): slides en constantes (`:10-17`), `createGiftCard` (35-51), `buildSlides` (53-84), `initCountdown` (86-111), `goToSlide` (113-125, **`translateX(-i*100%)`**, NO scrollLeft), `resetProgressBar` (127-134), `autoSlide`/`pauseCarousel`/`resumeCarousel` (136-154), `bindSlideActions` (156-162), `initHeroCarousel` (164-249: dots, botones, swipe táctil, `setInterval(autoSlide, 6500)`).
2. **Ofertas relámpago** (`:259-349`): `offersData` (261-265, 3 ofertas con timers), `updateTimers` (284-295), `openProduct` (297-305, usa `window.openProductDetail` si existe), `initOffers` (307-346).
3. **Scratch card** (`:352-376`): cupones hardcodeados (358), clic revela cupón.

### Hallazgos
- `NO SE ENCONTRÓ USO DE `localStorage` NI `fetch()` EN `carrusel.js`` (ni `angelow_client_categories` ni `angelow_products`).
- Posicionamiento con `transform: translateX` (`:118`), no `scrollLeft/scrollBy`.

---

## B.7 Páginas informativas (sin backend)

| Archivo | Líneas | Qué hace |
|---|---|---|
| `pedidos_envios.js` | 54 | IIFE: año, scroll suave por `.nav-link`, scroll-spy. `NO SE ENCONTRÓ fetch NI localStorage` |
| `terminos.js` | 48 | `updateActiveNav` (8-26), scroll-spy + scroll suave a anclas (34-48) |
| `preguntas.js` | 69 | Acordeón `.faq-item` (6-22), nav suave (25-39), scroll-spy (42-60) |
| `politicas_priv.js` | 47 | Año, fecha `lastUpdate` (4-9), nav suave + scroll-spy |
| `politicas_env.js` | 47 | Idéntico a `politicas_priv.js` |
| `guia_tallas.js` | 94 | IIFE: año, nav suave (28-49), scroll-spy (58-77), botón volver (86-91) |

Ninguno usa `fetch()`, `localStorage` ni endpoints `/api/*`.

---

## 4. HALLAZGOS CONSOLIDADOS Y DEBILIDADES

1. **Desconexión demo/backend (patrón del proyecto):** existen backends completos (`/api/categorias`, `/api/productos`, `/api/inventario`, `/api/repartidor/*`, `/api/repartidor/rastreo/*`) que **ninguna vista JS consume**. Las pantallas correspondientes (panel categorías/productos, inventario, tablero repartidor, seguimiento) son demos locales o simulaciones.
2. **Catálogo de 8 productos duplicado en 5 archivos** (`bienvenida.js:2-11`, `panel.js:20-29`, `inventario.js:44-55`, `perfil.js:32-41`, `seguimiento.js:2-5`) con precios/stock distintos → inconsistencias.
3. **Carrito híbrido:** la portada (`bienvenida.js`) y el checkout (`compra.js`) usan API real + respaldo `localStorage`; `perfil.js` y `seguimiento.js` solo `localStorage`. La clave `angelow_cart` se sincroniza entre pestañas vía evento `storage` (panel.js:2480, bienvenida.js).
4. **Seguimiento y tablero del repartidor son simulaciones** (conductor "Carlos Rodríguez" hardcodeado; viaje animado vía `setInterval`; única fuente de mapas = Leaflet + Routing Machine + Nominatim público).
5. **Validaciones faltantes:** `login.js` no valida mayoría de edad; `perfil.js` exige edad ≥1 (no 18); las tarjetas/direcciones se guardan **en `localStorage` plano** (sensibles) sin API.
6. **Año del footer** y scroll-spy son un patrón repetido en 5 páginas informativas; `politicas_env.js` es copia de `politicas_priv.js`.
7. **Stubs de funciones** en `perfil.js:1324-1338` (definir contraseña, 2FA, sesiones) devuelven solo toasts "Funcionalidad en desarrollo" → `NO SE ENCONTRÓ IMPLEMENTACIÓN DE 2FA/CAMBIO DE CONTRASEÑA DESDE EL PERFIL`.

---

## 5. LIBRERÍAS EXTERNAS UTILIZADAS

| Librería/servicio | Dónde |
|---|---|
| Leaflet (`L.map`, `L.marker`, `L.polyline`, `L.divIcon`, `L.icon`) | seguimiento.js, seguimiento-perfil.js, repartidor.js, panel.js |
| Leaflet.Routing.Machine (`L.Routing.control`) | seguimiento.js:726, seguimiento-perfil.js:464, repartidor.js:282 |
| Leaflet.polylineDecorator | seguimiento.js:713, seguimiento-perfil.js:451 |
| Nominatim OpenStreetMap (geocodificación) | seguimiento.js:641/938, seguimiento-perfil.js:387/613, repartidor.js:211 |
| Chart.js | panel.js (initCharts :1928+) |
| jsPDF + autoTable | panel.js:2248+, inventario.js:189+, perfil.js:931+ |
| html2canvas | perfil.js:974 |
| FontAwesome (iconos) | repartidor.js:118+ |
| Google Identity (login con Google) | login.js:450-473 (requiere http) |
| Leaflet Routing / geolocalización nativa (`navigator.geolocation`) | bienvenida no; compra.js:82-94, seguimiento.js:758-799, repartidor.js:173 |