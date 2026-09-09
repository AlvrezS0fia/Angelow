# Seguridad de ANGELOW

Auditoría de seguridad basada en la revisión del código **real**. Los estados usados son:

- **IMPLEMENTADO** — existe y funciona correctamente.
- **IMPLEMENTADO PARCIALMENTE** — existe pero es mejorable o no cubre todos los casos.
- **PENDIENTE** — no existe o requiere trabajo.
- **IMPLEMENTADO DURANTE ESTA AUDITORÍA** — se añadió/corrigió en esta revisión.

---

## 1. Autenticación

| Item | Estado | Detalle |
|------|--------|---------|
| Login (cliente/admin) | IMPLEMENTADO | `AuthController::login` — verifica `password_verify` contra `password_hash`. |
| Login (repartidor) | IMPLEMENTADO | `Api\RepartidorAuthController::login` + `RepartidorAuthController::login` (formulario). |
| Logout | IMPLEMENTADO | `AuthController::logout`, `LogoutController` (sesión), `Api\RepartidorAuthController::logout`. |
| Registro cliente | IMPLEMENTADO | `AuthController::register` valida email (`FILTER_VALIDATE_EMAIL`) y crea hash. |
| Registro repartidor | IMPLEMENTADO | `RepartidorAuthController::registro` — multi-paso con validación (email, tamaño archivos). |
| Hash de contraseñas | IMPLEMENTADO | `password_hash(..., PASSWORD_DEFAULT)` en todos los puntos (AuthController:102,319, RepartidorAuthController, Procesar/registrar). |
| Validación de contraseña | IMPLEMENTADO | Se establece requisito de contraseña (longitud) en registro/registro repartidor. |
| Recuperación de contraseña | IMPLEMENTADO | `AuthController::forgotPassword` + email + token de reset (`reset_token`/`reset_expiry`). |
| Expiración de sesión | NO IMPLEMENTADO | No hay límite de expiración/renovación de sesiones configurado. |
| Encriptación en tránsito (HTTPS) | PENDIENTE | No se fuerza HTTPS desde la app (depende del servidor). |

**Contraseñas**: método `password_hash`/`password_verify`. Ubicación principal:
`app/Controllers/AuthController.php`, `app/Controllers/Procesar/db.php`. Lo que protege:
el almacenamiento — las contraseñas nunca se guardan en texto plano.

---

## 2. Autorización y roles

| Recurso | Protección | Estado |
|---------|-----------|--------|
| Páginas `/admin/*` | Comprueban `$_SESSION['user']['rol'] === 'administrador'`; si no, redirect a login | IMPLEMENTADO |
| `Admin\RepartidorController::index` (`/admin/repartidores`) | **Faltaba por completo** | **IMPLEMENTADO DURANTE ESTA AUDITORÍA** (se añadió `Auth::isAdmin()` + redirect) |
| APIs `/api/admin/*` | `requireAdmin()` → `403` si no hay sesión admin | IMPLEMENTADO |
| `/api/clientes*` | Admin check con `403` | IMPLEMENTADO |
| Dashboard repartidor | `$_SESSION['user']['rol'] === 'repartidor'` si no redirect a `/repartidor/login` | IMPLEMENTADO |
| APIs de pedidos/inventario/productos/categorías (mutables) | `$_SESSION['user']['rol'] === 'administrador'` | IMPLEMENTADO |

**ENFOQUE ADOPTADO**: la protección NO confía en el frontend (ocultar botones con JS no
es seguridad). Toda operación sensible se verifica en el backend por método.

> **Patrón de riesgo**: la autorización es manual y se repite en cada método. Si un
> desarrollador olvida añadirla (como ocurrió en `Admin\RepartidorController`) el recurso
> queda expuesto. **Recomendación futura**: centralizar en un middleware o en una clase
> `Admin\BaseAdminController`.

---

## 3. Validación de entrada

- Lo que **NO** se valida: entrada por formularios (email, contraseña, cédula, etc.) en
  frontend + backend. Ver `docs/VALIDACIONES.md` para el detalle completo.
- Los datos de entrada se insertan en BD mediante **sentencias preparadas PDO**
  (ver sección SQL Injection), por lo que la validación de entrada y la seguridad contra
  SQL están separadas: una valida datos, la otra evita inyección.

---

## 4. SQL Injection

**Estado: IMPLEMENTADO** — el proyecto usa **PDO con sentencias preparadas** en todos
los modelos y controladores analizados:

- `App\Core\Database::query($sql, $params)` (prepara y bindea).
- Modelos: `UsuarioModel`, `PedidoModel`, `ProductoModel`, `CategoriaModel`,
  `CarritoModel`, `DireccionModel`, `TarjetaModel`.
- Controladores API: `RepartidorPedidosController`, `RepartidorClientesController`,
  `AdminRepartidorController`, etc.

Todos los valores de usuario se pasan como parámetros vinculados (prepared statements);
los `LIMIT`/`OFFSET` se bindean con `PARAM_INT`. Se evita la concatenación de valores
controlados por el usuario.

**Conclusión**: no se encontró una vulnerabilidad real de SQL injection explotable.

---

## 5. XSS

| Caso | Estado |
|------|--------|
| Salida de datos de usuario en HTML (perfil, admin) | IMPLEMENTADO PARCIALMENTE — la mayoría usa `htmlspecialchars`, pero había huecos |
| `json_encode` dentro de `<script>` con nombre de usuario | **CORREGIDO EN ESTA AUDITORÍA** |
| `fecha_nacimiento` en atributo `value` | **CORREGIDO EN ESTA AUDITORÍA** (se añadió `htmlspecialchars`) |

Cambios aplicados:
- `app/Views/home/bienvenida.php` y `app/Views/repartidor/dashboard.php`:
  `json_encode(..., JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)` para que un
  nombre con `</script>` no rompa el contexto de script.
- `app/Views/paginas/perfil.php`: mismo flag en `CURRENT_USER` + `htmlspecialchars` en
  `fecha_nacimiento`.

**Recomendación**: revisar puntos donde aún se hace `echo $dato` sin escapar; usar
`htmlspecialchars` en toda salida de datos de usuario.

---

## 6. CSRF

**Estado: NO IMPLEMENTADO** — las operaciones sensibles (cambiar contraseña, actualizar
perfil, crear pedido, cambiar estado, aprobar repartidor, eliminar registros) **no usan
tokens CSRF** ni verifican `SameSite` en las cookies.

- `¿Dónde está?` — no existe mecanismo CSRF centralizado.
- `¿Qué protege?` — nada específico hoy.
- `¿Qué endpoints deberían protegerse?` — todos los POST/PUT/DELETE sensibles
  (`/auth/change-password`, `/procesar-compra`, `/api/pedidos/estado`,
  `/api/admin/repartidores/*`, `/api/clientes/*`, etc.).

**PENDIENTE**. Recomendación: token CSRF por sesión, verificar en el front controller y
enviarlo desde los formularios/fetch. Las cookies de sesión deberían marcarse al menos
`SameSite=Lax`.

---

## 7. Contraseñas

| Item | Estado |
|------|--------|
| Hash seguro | IMPLEMENTADO (`password_hash` con `PASSWORD_DEFAULT`, i.e. bcrypt) |
| Nunca en texto plano | IMPLEMENTADO |
| Nunca en logs | IMPLEMENTADO (no se loguean contraseñas) |
| Nunca devueltas por API | IMPLEMENTADO (`PerfilApiController` hace `unset($user['password_hash'], ...)`) |
| Validación de fortaleza | IMPLEMENTADO (longitud en registro) |

---

## 8. Archivos y documentos (repartidores)

Estado antes de la auditoría:

| Item | Estado previo | Problema |
|------|---------------|----------|
| Validación de MIME | PARCIAL | En `Api\RepartidorDocumentosController` se confiaba en `$_FILES['archivo']['type']` (falsificable) |
| Extensión | PARCIAL | Se tomaba la extensión del nombre del archivo enviado |
| Límite de tamaño | PARCIAL | No existía en `subir()` |
| Whitelist de tipo | PARCIAL | `$tipo` se interpolaba en la ruta sin validar |
| Nombre de archivo | PARCIAL | Predecible (id_tipo_time) |

Cambios aplicados en `app/Controllers/Api/RepartidorDocumentosController.php::subir()`:
- **MIME real** mediante `finfo` (sniffing, no confiar en el cliente).
- **Extensión** derivada del MIME real (`pdf`/`jpg`/`png`), no del nombre.
- **Límite de tamaño** de 10 MB.
- **Whitelist de `tipo`** (`cedula`, `licencia_conduccion`, `tarjeta_profesional`,
  `tarjeta_propiedad`, `soat`, `tecnomecanica`) → evita path traversal y archivos
  arbitrarios.
- **Nombre aleatorio** (`id_tipo_hash.ext`) → evita adivinación de URLs.
- **IDOR corregido**: `index()` ya no permite leer documentos de otros repartidores
  (salvo rol admin).

Pendiente recomendado:
- **Servir los documentos** a través de un **endpoint autenticado** en lugar de un
  enlace estático público (`uploads/documentos/...`), para controlar el acceso.

---

## 9. Sesiones

| Item | Estado |
|------|--------|
| Directorio de sesión escribible | IMPLEMENTADO (se usa `storage/sessions`) |
| `session_start` con `@` | IMPLEMENTADO (maneja el caso de sesiones ya iniciadas) |
| Cookie `cart_session` para invitados | IMPLEMENTADO |
| `SameSite` en cookies | NO IMPLEMENTADO |
| `HttpOnly`/`Secure` en cookies de sesión | NO IMPLEMENTADO |

**Cambio aplicado**: `public/index.php` coloca el directorio de sesión en
`storage/sessions` (con creación automática) y usa `@session_start()`, evitando errores
intermitentes de `Permission denied (13)` en `C:\xampp\tmp`.

---

## 9b. Dashboard de repartidor — sesión + JWT (fijado en esta sesión)

El dashboard de repartidor dependía **exclusivamente** de un token JWT guardado en
`localStorage` (`repartidor_token`) para cargar sus datos, pero la *página* se autoriza
por **sesión PHP** (`RepartidorController::index`). Ese doble criterio producía:

- **Bucle de redirección** "No autenticado": si el token de `localStorage` faltaba/expiraba,
  el JS del dashboard (`if (!token) { window.location = .../login }`) mandaba al usuario a
  login, y `showLogin()` (sesión válida) lo regresaba al dashboard → bucle infinito.
- **Datos vacíos**: `loadStats()`/`loadOrders()` hacían `if (!token) return;` sin llamar a
  las APIs, aunque éstas ya soportaban sesión.

**Corrección aplicada (usa backend real, sin bypasear auth):**

- Las APIs `/api/repartidor/*` ya tenían *fallback a sesión* en `getRepartidorId()`
  (JWT primero, sesión después). Se eliminó la dependencia del JS del token.
- `dashboard.php` y `perfil.php` ahora **siempre** llaman a las APIs (sin early-return) y
  rellenan el `Authorization: Bearer` solo si existe token.
- Nuevo endpoint `POST /repartidor/refresh-token` (`RepartidorAuthController::refreshToken`)
  que emite un JWT nuevo a partir de la **sesión válida** (revisa rol y `estado='activo'`).
  `authHeaders()` lo usa cuando falta el token → flujo: auth(sesión) → token → datos reales.
- Solo se redirige a login cuando una **API devuelve 401/403 real** (`handleNoAutorizado`),
  nunca por ausencia de `localStorage`.

| Escenario | Antes | Ahora |
|-----------|-------|-------|
| Repartidor activo, sin token en localStorage | Bucle/redirect a login | Datos cargados por sesión + refresh-token |
| Token expirado | Fallo mudo / redirect | Se renueva desde sesión o se re-loguea |
| Anónimo a `/api/repartidor/*` | 401 | 401 (correcto) |
| `POST /repartidor/refresh-token` anónimo | — | 401 (correcto) |

Además se añadió el método que faltaba `RepartidorDashboardController::notificaciones()`
(ruta `/api/repartidor/notificaciones` existía pero la acción no existía → 500/fallo).

### 9c. "Rastrear Pedido" - endpoints del repartidor (creado en esta sesión)

Nuevo controlador `Api\RepartidorRastreoController.php` para buscar y avanzar el estado de
los pedidos del repartidor. Todas las acciones exigen **sesión PHP o JWT** de repartidor
activo y aplican **verificación estricta de propiedad**: el repartidor solo puede leer o
actualizar pedidos cuyo `repartidor_id` coincida con su id.

- `GET /api/repartidor/rastreo` → 401 anónimo; lista solo pedidos activos asignados.
- `GET /api/repartidor/rastreo/buscar?numero=...` → 403 si el pedido no es suyo, 404 si no
  existe. **Agrega control de propiedad** frente a la búsqueda por número.
- `GET /api/repartidor/rastreo/{id}/historial` → 403 si no es su pedido, 404 si no existe.
- `PUT /api/repartidor/rastreo/{id}/estado` → transición validada; 400 si es inválida
  (`asignado→aceptado→recogido→en_camino→entregado`, con saltos válidos), 403 si el
  pedido no le pertenece. Escribe en `historial_pedidos_repartidor` y, al entregar, en
  `historial_entregas`.

**IDOR corregido**: `Api\RepartidorPedidosController::show()` ahora devuelve **403** cuando
el repartidor solicita un pedido que no es suyo (antes devolvía los datos de cualquier
pedido) y **404** si no existe. `RepartidorRastreoController` usa la misma verificación.

**Mapa**: usa Leaflet con datos reales; cuando un pedido no tiene `latitud_destino`/
`longitud_destino` (la mayoría) se muestra un aviso claro en vez de inventar coordenadas.
La línea de tiempo se deriva de `historial_pedidos_repartidor` + timestamps reales del
pedido (sin datos simulados).

---

## 10. APIs

- Las APIs públicas (productos, categorías, catálogo) no requieren autenticación (correcto).
- Las APIs de datos sensibles requieren sesión admin (403) o token JWT de repartidor.
- Token JWT: firma HS256 con `JWT_SECRET`. **Antes** el valor de `JWT_SECRET` era el
  literal público `angelow_jwt_secret_key_2026` (porque `.env` no lo definía).

**Cambio aplicado**: se añadió un `JWT_SECRET` aleatorio real en `.env` (no se sube a
Git). Ahora los tokens se firman con un secreto desconocido para terceros.

**PENDIENTE recomendado**: rotar el secreto periódicamente; añadir `rate limiting` a
login/endpoints de autenticación.

---

## 11. Variables de entorno y secretos

| Variable | .env | Uso |
|----------|------|-----|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | sí | `config/database.php` |
| `SMTP_*` | sí | `EmailService` |
| `APP_NAME`, `APP_URL`, `TIMEZONE` | sí | `config/app.php` |
| `GOOGLE_CLIENT_ID/SECRET` | sí | Google OAuth |
| `JWT_SECRET` | **AÑADIDO EN ESTA AUDITORÍA** | firma de tokens de repartidor |

- `.env` está en `.gitignore` → **NO se sube a Git** (correcto).
- Se creó `.env.example` (sin secretos reales) para otros desarrolladores.

---

## 12. Manejo de errores

| Item | Estado |
|------|--------|
| try/catch en APIs | IMPLEMENTADO |
| Respuesta JSON con `success:false` | IMPLEMENTADO |
| `error_log()` para diagnóstico | IMPLEMENTADO |
| **Exponer `$e->getMessage()` de PDO al usuario** | **RIESGO MEDIO** — varios `catch` devuelven el mensaje de la excepción al cliente |
| Loguear `$_SESSION` completo en panel admin | **CORREGIDO EN ESTA AUDITORÍA** (se eliminó `error_log(print_r($_SESSION,true))` de `admin/panel.php`) |

**Recomendación (PENDIENTE)**: reemplazar `echo $e->getMessage()` por mensajes genéricos
en las respuestas y registrar el detalle solo en el log interno.

---

## 13. Protección de rutas / acceso directo probado (conceptualmente)

| Escenario | Resultado | Estado |
|-----------|-----------|--------|
| Cliente → `/admin` | `Admin\DashboardController` rechaza y redirige a login | IMPLEMENTADO |
| Cliente → `/dashboard-repartidor` | `RepartidorController::index` rechaza y redirige a `/repartidor/login` | IMPLEMENTADO |
| Repartidor → `/admin` | Rechazado (los controladores admin exigen rol administrador) | IMPLEMENTADO |
| Repartidor pendiente → dashboard | Se le muestra el estado "pendiente" con timeline (no el panel activo) | IMPLEMENTADO |
| Anónimo → `/perfil` | `perfil.php` redirige a login si no hay sesión | IMPLEMENTADO |
| Anónimo → `/admin/repartidores` | **ANTES: sin protección** → **AHORA: redirige a login** | **CORREGIDO** |

---

## 14. Resumen de riesgos encontrados y correcciones

Clasificación usada: CRÍTICA / ALTA / MEDIA / BAJA.

| Riesgo | Severidad | Estado |
|--------|-----------|--------|
| Admin `RepartidorController::index` sin autenticación | CRÍTICA | **CORREGIDO** |
| `JWT_SECRET` público y predecible | ALTA | **CORREGIDO** (secreto real en `.env`) |
| Subida de archivos: MIME falsificable, sin límite, extensión del nombre, `$tipo` sin whitelist | ALTA | **CORREGIDO** (finfo, límite, whitelist, random) |
| IDOR: repartidor lee documentos de otros | MEDIA | **CORREGIDO** |
| XSS por `json_encode` en contexto `<script>` | MEDIA | **CORREGIDO** |
| Logueo de `$_SESSION` en panel admin | MEDIA | **CORREGIDO** |
| CSRF no implementado | ALTA | **PENDIENTE** |
| Documentos servidos como estáticos sin endpoint autenticado | MEDIA | **PENDIENTE** |
| Exponer `$e->getMessage()` de PDO al usuario | MEDIA | **PENDIENTE** |
| Expiración de sesiones | MEDIA | **PENDIENTE** |
| `SameSite`/`HttpOnly`/`Secure` en cookies | MEDIA | **PENDIENTE** |
| Rate limiting en login/APIs | BAJA | **PENDIENTE** |
| HTTPS forzado | BAJA | **PENDIENTE** |

---

## 15. Recomendaciones futuras (prioridad)

1. **Implementar CSRF** para todas las operaciones mutables.
2. **Endpoint autenticado** para servir documentos de repartidores.
3. **No exponer mensajes de excepción PDO** al cliente.
4. **Centralizar la autorización** (base controller para admin y para repartidor).
5. **Cookies seguras** (`HttpOnly`, `Secure`, `SameSite=Lax`).
6. **Expiración y renovación de sesiones**, y un **rate limit** en login.
7. Revisar los scripts heredados en `app/Controllers/Procesar/` para unificarlos con el
   flujo moderno (métodos dedicados en los controladores).

---

## 16. Eliminación de la facturación Python (esta sesión)

El microservicio de facturación Python (`facturacion/`) y toda la funcionalidad de
facturas (controladores PHP, modelos, vistas, rutas, correos y tablas `facturas*`)
fueron **eliminados del proyecto**. Ver `docs/ARQUITECTURA.md` §13.

## 17. Observaciones de la auditoría de seguimiento (cliente/admin)

- El mapa de seguimiento de **cliente** y **admin** usa una **simulación en el frontend**
  (`seguimiento-perfil.js`/`seguimiento.js`): conductor falso, datos hardcodeados y
  geolocalización del navegador. No consulta el backend real.
- Ya existe una cadena real y segura **sin consumir** en el frontend:
  `GET /api/mis-pedidos/:id/seguimiento` (`Cliente\PedidosController::seguimiento`, con
  verificación de propiedad del pedido) que devuelve `pedidos.estado`, la última fila de
  `seguimiento_tiempo_real` y el repartidor. **PENDIENTE**: conectar el frontend a este
  endpoint y mostrar aviso cuando no haya coordenadas (hoy `seguimiento_tiempo_real` está
  vacío y `pedidos.latitud_destino/longitud_destino` son NULL).
- El repartidor sí dispone de datos reales: `Api\RepartidorRastreoController` + módulo
  "Rastrear Pedido" en `repartidor/dashboard.php` (Leaflet, timeline, transiciones con
  verificación de propiedad).
- **Tiempo real**: hay polling (PHP) y Socket.IO (solo consumido por el panel Node del
  repartidor). No hay SSE. Las vistas cliente/admin no consumen el broadcast de Socket.IO.
