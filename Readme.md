# Angelow – Plataforma de E‑Commerce

Sistema de venta en línea desarrollado con PHP puro (sin framework) que permite a los usuarios registrarse, iniciar sesión, explorar productos, añadirlos al carrito, gestionar favoritos y completar compras. Incluye panel de administración para gestión de inventario, pedidos y usuarios.

---

## Tabla de Contenidos
- [Características Principales](#-características-principales)
- [Tecnologías Utilizadas](#-tecnologías-utilizadas)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Instalación y Configuración](#-instalación-y-configuración)
- [Uso](#-uso)
- [APIs Externas](#-apis-externas)
- [Base de Datos](#-base-de-datos)
- [Creditos](#-creditos)
- [Licencia](#-licencia)

---

## Características Principales

- **Autenticación de usuarios** (registro, login, logout) con sesiones PHP y opción de login con Google (OAuth2).
- **Gestión de carrito** (añadir, eliminar, actualizar cantidades) persistente en sesión.
- **Lista de favoritos** para guardar productos de interés.
- **Catálogo de productos** con filtros por categoría y búsqueda.
- **Detalle de producto** con imágenes, descripción, precio y stock.
- **Proceso de compra** simulado (pasos de dirección y pago).
- **Panel de administración** (solo para roles admin) con:
  - Gestión de usuarios (listado, edición, eliminación).
  - Gestión de inventario (productos, categorías, stock).
  - Visualización de pedidos y reportes.
- **Diseño responsivo** usando CSS personalizado y Bootstrap 5 (local).
- **Modo oscuro** disponible en algunas vistas.
- **Notificaciones y alertas** mediante SweetAlert2 (incluido vía CDN).
- **Envío de emails** (bienvenida, recuperación de contraseña) usando PHPMailer.
- **Archivo SQL** para crear la base de datos inicial.

---

## Tecnologías Utilizadas

| Área | Tecnologías |
|------|--------------|
| **Frontend** | HTML5, CSS3 (Bootstrap 5 local + estilos propios), JavaScript (ES6+), SweetAlert2, Font Awesome |
| **Backend** | PHP 7.4+, PDO (MySQL), Sesiones, PHPMailer |
| **Plantillas** | Vistas PHP separadas (MVC rudimentario) |
| **Base de datos** | MySQL 5.7+ |
| **Autenticación externa** | Google OAuth2 (Google Sign-In) |
| **Herramientas** | Composer (para autoload y PHPMailer), Git |
| **Otros** | .htaccess para URLs amigables, reCAPTCHA (en forms de registro) |

---

## Estructura del Proyecto

```
Angelow/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, logout, registro, Google
│   │   ├── HomeController.php          # Página principal, bienvenida
│   │   ├── DashboardController.php     # Panel de admin
│   │   ├── CompraController.php        # Proceso de compra
│   │   ├── ContactoController.php      # Formulario de contacto
│   │   ├── PerfilController.php        # Edición de perfil
│   │   ├── DocumentoController.php     # Políticas, guías, etc.
│   │   ├── Api/
│   │   │   ├── CarritoController.php   # Endpoints AJAX del carrito
│   │   │   ├── FavoritoController.php  # Endpoints AJAX de favoritos
│   │   │   ├── categories.php          # Lista de categorías (JSON)
│   │   │   ├── config.php              # Configuración de API
│   │   │   └── products.php            # Lista de productos (JSON)
│   │   └── Procesar/
│   │       ├── db.php                  # Conexión PDO singleton
│   │       ├── login.php               # Procesa login tradicional
│   │       ├── registrar.php           # Procesa registro
│   │       └── recuperar_password.php  # Envío de email de recuperación
│   ├── Models/
│   │   ├── UsuarioModel.php            # CRUD de usuarios
│   │   ├── CarritoModel.php            # Operaciones de carrito
│   │   └── Favorito.php                # Operaciones de favoritos
│   ├── Views/
│   │   ├── home/
│   │   │   └── bienvenida.php          # Landing page
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── olvide-password.php
│   │   │   └── reset-password.php
│   │   ├── paginas/
│   │   │   ├── compra.php              # Carrito y checkout
│   │   │   ├── contactenos.php
│   │   │   ├── factura.php
│   │   │   ├── perfil.php
│   │   │   ├── seguimiento.php
│   │   │   └── repartidor.php
│   │   ├── documentos/                 # Políticas, guías, preguntas frecuentes
│   │   ├── emails/                     # Plantillas de correo (HTML)
│   │   │   └── img/
│   │   └── admin/
│   │       ├── panel.php               # Dashboard principal
│   │       ├── inventario.php          # Gestión de productos
│   │       └── repartidor.php          # (vacío, placeholder)
│   ├── Core/
│   │   ├── Controller.php              # Clase base de controladores
│   │   ├── Router.php                  # Enrutamiento sencillo
│   │   ├── Database.php                # Singleton PDO
│   │   └── Helpers.php                 # Funciones auxiliares
│   └── Libraries/
│       └── EmailService.php            # Envío de emails con PHPMailer
├── config/
│   ├── config.php                      # Constantes generales
│   └── routes.php                      # Definición de rutas
├── public/
│   ├── index.php                       # Front controller
│   └── assets/
│       ├── css/
│       │   ├── login.css
│       │   ├── home.css
│       │   ├── compra.css
│       │   ├── contactenos.css
│       │   ├── bienvenida.css
│   │       └── carrusel.css
│       ├── js/
│       │   ├── login.js
│       │   ├── home.js
│       │   ├── bienvenida.js
│       │   ├── carrusel.js
│       │   └── (otros scripts según página)
│       └── bootstrap/                  # Bootstrap 5 (local)
├── vendor/                             # Dependencias de Composer
├── angelow.sql                         # Esquema y datos de ejemplo
├── .gitignore
├── .htaccess
└── composer.json / composer.lock
```

---

## Instalación y Configuración

### Requisitos
- PHP ≥ 7.4
- MySQL ≥ 5.7
- Servidor web (Apache con mod_rewrite habilitado, Nginx con reglas equivalentes)
- Composer (opcional, pero recomendado)

### Pasos

1. **Clonar o copiar el proyecto** en el directorio raíz del servidor (por ejemplo `htdocs` si usas XAMPP):
   ```bash
   git clone https://github.com/tu-usuario/Angelow.git
   # o simplemente descomprimir el ZIP
   ```

2. **Instalar dependencias de Composer** (desde la raíz del proyecto):
   ```bash
   composer install
   ```

3. **Crear la base de datos**:
   - Accede a tu gestor MySQL (phpMyAdmin, línea de comandos, etc.).
   - Crea una base de datos llamada `angelow` (o el nombre que prefieras).
   - Importa el archivo `angelow.sql`:
     ```bash
     mysql -u tu_usuario -p angelow < angelow.sql
     ```

4. **Configurar la conexión**:
   - Edita `app/Core/Database.php` si es necesario (por defecto usa `host=localhost`, `dbname=angelow`, `usuario=root`, `contraseña=''`).
   - Opcionalmente, define constantes en `config/config.php`.

5. **Configurar Google OAuth** (para login con Google):
   - Crea un proyecto en Google Cloud Console.
   - Habilita la API de Google+ / Google Sign-In.
   - Obtiene un **Client ID** y **Client Secret**.
   - Actualiza los valores en `app/Controllers/AuthController.php` (variables `$GOOGLE_CLIENT_ID` y `$GOOGLE_CLIENT_SECRET`).

6. **Permisos de escritura**:
   - Asegúrate de que el servidor tenga permisos de escritura en `public/` (para subir imágenes si se implementa) y en `app/Libraries/` (para logs de email si se usan).

7. **Probar la instalación**:
   - Abre tu navegador y visita `http://localhost/Angelow/` (o el dominio configurado).
   - Deberías ver la página de bienvenida (`app/Views/home/bienvenida.php`).

---

## Uso

### Para usuarios finales
- **Registro**: Haz clic en “Registrarse” y completa el formulario.
- **Login**: Ingresa tu email y contraseña, o usa el botón de Google.
- **Explorar productos**: En la página principal o mediante el menú de categorías.
- **Detalle de producto**: Haz clic en un producto para ver más información.
- **Carrito**: Añade productos al carrito desde el listado o detalle.
- **Favoritos**: Guarda productos haciendo clic en el ícono de corazón.
- **Checkout**: Desde el carrito, procede a dirección y pago (simulado).
- **Perfil**: Actualiza tus datos y contraseña.

### Para administradores
- Accede al panel mediante `/Dashboard` (solo usuarios con rol `admin`).
- **Gestión de usuarios**: Lista, edita roles, elimina.
- **Gestión de inventario**: Añade, edita o elimina productos y categorías.
- **Pedidos**: Visualiza los pedidos realizados (si se implementa el módulo completo).
- **Reportes**: (opcional) Exportar datos.

---

## APIs Externas

- **Google Sign-In (OAuth2)**: Autenticación con cuenta de Google.
- **PHPMailer** (vía Composer): Envío de correos electrónicos.
- **SweetAlert2** (CDN): Alertas y modales atractivos.
- **Bootstrap 5** (local): Sistema de rejilla y componentes UI.
- **Font Awesome** (CDN): Iconos vectoriales.
- **reCAPTCHA** (opcional en forms de registro): Protección contra bots.

> Nota: El proyecto no depende de APIs externas de terceros para datos de productos o clima; todo el catálogo se gestiona localmente.

---

## Base de Datos

El archivo `angelow.sql` contiene las siguientes tablas principales:

- `usuarios` – datos de los clientes y administradores.
- `categorias` – clasificación de productos.
- `productos` – información de cada artículo (nombre, descripción, precio, stock, imagen, id_categoria).
- `carrito` – ítems temporales añadidos por usuarios (relacionado con sesión/usuario).
- `favoritos` – productos marcados como favoritos por usuarios.
- `pedidos` y `detalle_pedido` – registro de compras (si se activa el módulo completo).
- `contactos` – mensajes enviados desde el formulario de contacto.

Puedes revisar el archivo SQL para ver la estructura completa y datos de ejemplo.

---

## Créditos

- Desarrollado por **[Tu Nombre / Equipo de Desarrollo]**.
- Bootstrap 5: https://getbootstrap.com
- SweetAlert2: https://sweetalert2.github.io
- Font Awesome: https://fontawesome.com
- Google Sign-In: https://developers.google.com/identity
- PHPMailer: https://github.com/PHPMailer/PHPMailer

---

## Licencia

Este proyecto se distribuye bajo la licencia **MIT**. Consulta el archivo `LICENSE` para más información.

--- 

*¡Gracias por usar Angelow!* 
