# Validaciones en ANGELOW

Documento de validaciones por formulario, basado en el código **real**.

Convención:
- **Frontend** = validación para experiencia del usuario (no es seguridad).
- **Backend** = validación del servidor (seguridad e integridad). Es la que realmente
  protege el sistema. Una validación solo en frontend **NO** es suficiente.

---

## Tabla resumen

| Formulario | Campo | Validación | Ubicación | Frontend | Backend | Propósito |
|------------|-------|------------|-----------|:--------:|:-------:|-----------|
| Login | email | requerido | `AuthController::login` | sí | sí | Evita login incompleto |
| Login | password | requerido | `AuthController::login` | sí | sí | Evita login incompleto |
| Registro | email | requerido | `AuthController::register` (línea 85) | sí | sí | Evita datos incompletos |
| Registro | email | formato válido (`FILTER_VALIDATE_EMAIL`) | `AuthController::register:89` | sí | sí | Evita correos inválidos |
| Registro | nombre | requerido | `AuthController::register:85` | sí | sí | Garantiza identidad mínima |
| Registro | password | mínimo 8 + mayúscula + número + especial | `AuthController::register:93` | sí | sí | Evita contraseñas débiles |
| Registro | terms | debe aceptar términos | `AuthController::register:85` | sí | sí | Cumplimiento legal |
| Registro | email | no duplicado (`findByEmail`) | `AuthController::register:97` | sí | sí | Evita duplicados |
| Perfil | cédula | solo dígitos, 6–15 | `perfil.js::validarCedula` | sí | — | Datos correctos |
| Perfil | tarjeta | 13–19 dígitos, no vencida (Luhn parcial) | `perfil.js::validarNumeroTarjeta` | sí | — | Método de pago válido |
| Compra/Checkout | nombre, apellidos, email, cédula, teléfono | requeridos | `compra.js::camposPaso1` | sí | — | Datos de envío completos |
| Compra/Checkout | cédula | válida (regex) | `compra.js::validarCedula` | sí | — | Número de documento válido |
| Contacto | email | válido (`FILTER_VALIDATE_EMAIL`) | `ContactoController::enviar:31` | sí | sí | Correo de contacto válido |
| Repartidor registro | email | válido | `RepartidorAuthController:168` | sí | sí | Correo válido |
| Repartidor registro | documentos | tamaño ≤ 10 MB | `RepartidorAuthController:351`, `perfil.js` | sí | sí | Evita archivos muy grandes |
| Repartidor registro | documentos | MIME PDF/JPG/PNG (finfo real) | `RepartidorAuthController:355-362` | sí | sí | Solo formatos permitidos |
| Repartidor subida docs | archivo | MIME real finfo + tamaño + tipo whitelist | `Api\RepartidorDocumentosController::subir` | sí | sí | Seguridad de archivos |
| Repartidor login | email/password | requeridos + `password_verify` | `Api\RepartidorAuthController::login` | sí | sí | Autenticación |
| Admin cambio rol | rol | whitelist (`cambiarRol:58`) | `Admin\ClientesController` | — | sí | Solo roles válidos |
| Admin crear cliente | campos | requeridos + hash | `Admin\ClientesController::store` | — | sí | Crear usuario admin |

---

## Diferencia Frontend vs Backend

```
Validación Frontend   →  Experiencia del usuario (rápida, no bloquea al atacante)
Validación Backend    →  Seguridad e integridad (protege la BD y el sistema)
```

**Ejemplo (registro)**:
- Frontend: comprueba que el email tenga formato y que la contraseña cumpla requisitos
  antes de enviar (mejora UX).
- Backend (`AuthController::register`): vuelve a comprobar el email, la contraseña y los
  términos, ya que un cliente puede enviar una petición directamente sin pasar por la UI.

⚠️ **Regla**: la validación crítica (email, contraseña, roles, tamaño/MIME de archivos)
**debe** existir también en backend. En este proyecto así es para los casos sensibles.

---

## Detalle por formulario

### 1. Login
- **Ubicación**: `app/Controllers/AuthController.php::login` (sesión cliente/admin) y
  `Api/RepartidorAuthController.php::login` (repartidor).
- **Frontend**: `auth/login.php` + `login.js` (requeridos, formato).
- **Backend**: `password_verify($password, $user['password_hash'])`.
- **Sirve contra**: accesos no autorizados con credenciales incorrectas.
- **Completo**: SÍ.

### 2. Registro de usuario
- **Ubicación**: `AuthController::register`.
- **Validaciones backend**:
  - Campos requeridos (email, nombre, password, terms) — línea 85.
  - Email válido con `FILTER_VALIDATE_EMAIL` — línea 89.
  - Contraseña: ≥8, mayúscula, número, carácter especial — línea 93.
  - Email único — línea 97.
- **Sirve contra**: datos inválidos/incompletos, contraseñas débiles, cuentas duplicadas.
- **Completo**: SÍ.

### 3. Registro de repartidor (multi-paso)
- **Ubicación**: `app/Controllers/RepartidorAuthController.php::registro`.
- **Validaciones backend**: email válido (línea 168), tamaño de archivos (≤ 10 MB),
  MIME real con `finfo` (solo pdf/jpg/png).
- **Sirve contra**: solicitudes mal formadas y subida de archivos no permitidos.
- **Completo**: PARCIAL — conviene reforzar fuerza de contraseña y otros campos.

### 4. Perfil / métodos de pago
- **Ubicación**: `app/Views/paginas/perfil.php` + `perfil.js`
  (`validarCedula`, `validarNumeroTarjeta`).
- **Solo frontend** para cédula/tarjeta → es validación de datos para UX; el backend
  (`Cliente\PerfilApiController`) persiste vía PDO preparado (no SQLi).
- **Nota**: validación de tarjeta es parcial (sin Luhn completo en todos los casos).

### 5. Checkout / compra
- **Ubicación**: `app/Views/paginas/compra.php` + `compra.js` (paso 1: datos de envío;
  paso 2: envío; paso 3: pago).
- **Frontend**: campos requeridos + cédula válida.
- **Backend**: `CompraController::procesar` crea el pedido con datos validados.
- **Completo**: SÍ para lo esencial.

### 6. Contacto
- **Ubicación**: `ContactoController::enviar`.
- **Backend**: `FILTER_VALIDATE_EMAIL`.
- **Completo**: SÍ.

### 7. Administración
- Cambio de rol (`Admin\ClientesController::cambiarRol`): whitelist de roles.
- Crear usuario (`store`): campos + hash automático.
- Aprobar/rechazar/suspender repartidor (`Api\AdminRepartidorController`): valida `id`,
  estado y transacción, con whitelist de permisos (`grantDefaultPermissions`).

---

## ¿Qué es VALIDACIÓN DE DATOS y qué es SEGURIDAD?

- **VALIDACIÓN DE DATOS** (ej. email con formato, cédula numérica, contraseña con
  requisitos) → mejora la calidad de la información y evita errores de procesamiento.
- **SEGURIDAD** (ej. `password_verify`, parámetros PDO/SQLi, `finfo` MIME real,
  whitelist de roles, control de acceso por sesión/rol) → protege el sistema de ataques
  y accesos no autorizados.

En este proyecto ambas coexisten; la de seguridad vive en el backend.
