<?php
return [
    ['method' => 'GET', 'path' => '/', 'controller' => 'HomeController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/cargador', 'controller' => 'CargadorController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/auth/login', 'controller' => 'AuthController', 'action' => 'showLogin'],
    ['method' => 'POST', 'path' => '/auth/login', 'controller' => 'AuthController', 'action' => 'login'],
    ['method' => 'POST', 'path' => '/auth/register', 'controller' => 'AuthController', 'action' => 'register'],
    ['method' => 'POST', 'path' => '/auth/forgot-password', 'controller' => 'AuthController', 'action' => 'forgotPassword'],
    ['method' => 'POST', 'path' => '/auth/google', 'controller' => 'AuthController', 'action' => 'googleLogin'],
    ['method' => 'GET', 'path' => '/auth/logout', 'controller' => 'AuthController', 'action' => 'logout'],
    ['method' => 'GET', 'path' => '/perfil', 'controller' => 'PerfilController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/compra', 'controller' => 'CompraController', 'action' => 'index'],
    ['method' => 'POST', 'path' => '/procesar-compra', 'controller' => 'CompraController', 'action' => 'procesar'],
    ['method' => 'GET', 'path' => '/factura', 'controller' => 'FacturaController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/factura/:id', 'controller' => 'FacturaController', 'action' => 'show'],
    ['method' => 'GET', 'path' => '/seguimiento', 'controller' => 'SeguimientoController', 'action' => 'index'],
    // Panel de administración
    ['method' => 'GET', 'path' => '/admin', 'controller' => 'Admin\\DashboardController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/admin/pedidos', 'controller' => 'Admin\\PedidosController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/admin/usuarios', 'controller' => 'Admin\\UsuariosController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/admin/inventario', 'controller' => 'Admin\\InventarioController', 'action' => 'index'],
    
    // Documentos legales y de soporte
    ['method' => 'GET', 'path' => '/documentos/Pedidos_envios', 'controller' => 'DocumentoController', 'action' => 'pedidosEnvios'],
    ['method' => 'GET', 'path' => '/documentos/Politicas_devolucion', 'controller' => 'DocumentoController', 'action' => 'politicasDevolucion'],
    ['method' => 'GET', 'path' => '/documentos/Preguntas', 'controller' => 'DocumentoController', 'action' => 'preguntas'],
    ['method' => 'GET', 'path' => '/documentos/Guia_Tallas', 'controller' => 'DocumentoController', 'action' => 'guiaTallas'],
    ['method' => 'GET', 'path' => '/documentos/Terminos', 'controller' => 'DocumentoController', 'action' => 'terminos'],
    ['method' => 'GET', 'path' => '/documentos/Politicas_Priv', 'controller' => 'DocumentoController', 'action' => 'politicasPrivacidad'],  
    ['method' => 'GET', 'path' => '/documentos/Politicas_Env', 'controller' => 'DocumentoController', 'action' => 'politicasEnv'],
    
    // NUEVAS RUTAS PARA CAMBIO DE CONTRASEÑA
    ['method' => 'GET', 'path' => '/auth/change-password', 'controller' => 'AuthController', 'action' => 'showChangePassword'],
    ['method' => 'POST', 'path' => '/auth/change-password', 'controller' => 'AuthController', 'action' => 'changePassword'],
    ['method' => 'GET', 'path' => '/auth/reset-password', 'controller' => 'AuthController', 'action' => 'showResetForm'],
    ['method' => 'POST', 'path' => '/auth/reset-password', 'controller' => 'AuthController', 'action' => 'resetPassword'], 
    
    // Rutas para contacto
    ['method' => 'GET', 'path' => '/contactenos', 'controller' => 'ContactoController', 'action' => 'index'],
    ['method' => 'POST', 'path' => '/contacto/enviar', 'controller' => 'ContactoController', 'action' => 'enviar'],
   
     // RUTAS API CARRITO 
    ['method' => 'GET',    'path' => '/api/carrito',          'controller' => 'Api\\CarritoController', 'action' => 'index'],
    ['method' => 'POST',   'path' => '/api/carrito/agregar',   'controller' => 'Api\\CarritoController', 'action' => 'agregar'],
    ['method' => 'POST',   'path' => '/api/carrito/actualizar','controller' => 'Api\\CarritoController', 'action' => 'actualizar'],
    ['method' => 'DELETE', 'path' => '/api/carrito/eliminar',  'controller' => 'Api\\CarritoController', 'action' => 'eliminar'],
    ['method' => 'POST',   'path' => '/api/carrito/sincronizar','controller' => 'Api\\CarritoController', 'action' => 'sincronizar'],
    ['method' => 'POST',   'path' => '/api/carrito/vaciar',     'controller' => 'Api\\CarritoController', 'action' => 'vaciar'],

     // RUTAS API FAVORITOS 
     ['method' => 'GET',    'path' => '/api/favoritos',         'controller' => 'Api\\FavoritoController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/favoritos/agregar',  'controller' => 'Api\\FavoritoController', 'action' => 'agregar'],
     ['method' => 'DELETE', 'path' => '/api/favoritos/eliminar', 'controller' => 'Api\\FavoritoController', 'action' => 'eliminar'],

     // RUTAS API INVENTARIO 
     ['method' => 'GET',    'path' => '/api/productos',          'controller' => 'Api\\ProductsController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/productos/{id}',     'controller' => 'Api\\ProductsController', 'action' => 'show'],
     ['method' => 'POST',   'path' => '/api/productos',          'controller' => 'Api\\ProductsController', 'action' => 'store'],
     ['method' => 'PUT',    'path' => '/api/productos/{id}',     'controller' => 'Api\\ProductsController', 'action' => 'update'],
     ['method' => 'DELETE', 'path' => '/api/productos/{id}',     'controller' => 'Api\\ProductsController', 'action' => 'destroy'],

     ['method' => 'GET',    'path' => '/api/categorias',         'controller' => 'Api\\CategoriesController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/categorias/{id}',    'controller' => 'Api\\CategoriesController', 'action' => 'show'],
     ['method' => 'POST',   'path' => '/api/categorias',         'controller' => 'Api\\CategoriesController', 'action' => 'store'],
     ['method' => 'PUT',    'path' => '/api/categorias/{id}',    'controller' => 'Api\\CategoriesController', 'action' => 'update'],
     ['method' => 'DELETE', 'path' => '/api/categorias/{id}',    'controller' => 'Api\\CategoriesController', 'action' => 'destroy'],

     ['method' => 'GET',    'path' => '/api/inventario',         'controller' => 'Api\\StockController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/inventario',         'controller' => 'Api\\StockController', 'action' => 'update'],
     ['method' => 'GET',    'path' => '/api/clientes',           'controller' => 'Admin\\ClientesController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/clientes',           'controller' => 'Admin\\ClientesController', 'action' => 'store'],
     ['method' => 'POST',   'path' => '/api/clientes/buscar',    'controller' => 'Admin\\ClientesController', 'action' => 'buscar'],
     ['method' => 'POST',   'path' => '/api/clientes/rol',       'controller' => 'Admin\\ClientesController', 'action' => 'cambiarRol'],
     ['method' => 'DELETE', 'path' => '/api/clientes/{id}',      'controller' => 'Admin\\ClientesController', 'action' => 'destroy'],
     ['method' => 'GET',    'path' => '/api/pedidos',             'controller' => 'Admin\\PedidosController', 'action' => 'obtenerPedidos'],
     ['method' => 'POST',   'path' => '/api/pedidos/estado',     'controller' => 'Admin\\PedidosController', 'action' => 'updateStatus'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos',          'controller' => 'Cliente\\PedidosController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos/:id',      'controller' => 'Cliente\\PedidosController', 'action' => 'detalle'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos/:id/seguimiento', 'controller' => 'Cliente\\PedidosController', 'action' => 'seguimiento'],
     ['method' => 'POST',   'path' => '/api/mis-pedidos/:id/cancelar', 'controller' => 'Cliente\\PedidosController', 'action' => 'cancelar'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos/:id/factura', 'controller' => 'Cliente\\PedidosController', 'action' => 'factura'],
     ['method' => 'POST',   'path' => '/api/inventario/update',    'controller' => 'Api\\StockController', 'action' => 'update'],
     ['method' => 'POST',   'path' => '/api/inventario/ajustar',    'controller' => 'Api\\StockController', 'action' => 'ajustar'],
     ['method' => 'POST',   'path' => '/api/inventario/eliminar',   'controller' => 'Api\\StockController', 'action' => 'destroy'],

      // RUTAS API PERFIL CLIENTE 
      ['method' => 'GET',    'path' => '/api/perfil',                   'controller' => 'Cliente\\PerfilApiController', 'action' => 'index'],
      ['method' => 'POST',   'path' => '/api/perfil/actualizar',        'controller' => 'Cliente\\PerfilApiController', 'action' => 'actualizar'],

      // RUTAS API DIRECCIONES 
      ['method' => 'GET',    'path' => '/api/direcciones',              'controller' => 'Cliente\\DireccionController', 'action' => 'index'],
      ['method' => 'POST',   'path' => '/api/direcciones/crear',        'controller' => 'Cliente\\DireccionController', 'action' => 'crear'],
      ['method' => 'POST',   'path' => '/api/direcciones/actualizar/{id}','controller' => 'Cliente\\DireccionController', 'action' => 'actualizar'],
      ['method' => 'POST',   'path' => '/api/direcciones/eliminar/{id}', 'controller' => 'Cliente\\DireccionController', 'action' => 'eliminar'],
      ['method' => 'POST',   'path' => '/api/direcciones/predeterminada/{id}','controller' => 'Cliente\\DireccionController', 'action' => 'setPredeterminada'],

      // RUTAS API TARJETAS 
      ['method' => 'GET',    'path' => '/api/tarjetas',                 'controller' => 'Cliente\\TarjetaController', 'action' => 'index'],
      ['method' => 'POST',   'path' => '/api/tarjetas/crear',           'controller' => 'Cliente\\TarjetaController', 'action' => 'crear'],
      ['method' => 'POST',   'path' => '/api/tarjetas/actualizar/{id}', 'controller' => 'Cliente\\TarjetaController', 'action' => 'actualizar'],
      ['method' => 'POST',   'path' => '/api/tarjetas/eliminar/{id}',   'controller' => 'Cliente\\TarjetaController', 'action' => 'eliminar'],
      ['method' => 'POST',   'path' => '/api/tarjetas/predeterminada/{id}','controller' => 'Cliente\\TarjetaController', 'action' => 'setPredeterminada'],

      // API REPARTIDOR 
     ['method' => 'POST',   'path' => '/api/repartidor/auth/login',   'controller' => 'Api\\RepartidorAuthController', 'action' => 'login'],
     ['method' => 'POST',   'path' => '/api/repartidor/auth/logout',  'controller' => 'Api\\RepartidorAuthController', 'action' => 'logout'],
     ['method' => 'GET',    'path' => '/api/repartidor/auth/me',      'controller' => 'Api\\RepartidorAuthController', 'action' => 'me'],

     ['method' => 'GET',    'path' => '/api/repartidor/pedidos',           'controller' => 'Api\\RepartidorPedidosController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/repartidor/pedidos/{id}',      'controller' => 'Api\\RepartidorPedidosController', 'action' => 'show'],
     ['method' => 'POST',   'path' => '/api/repartidor/pedidos',           'controller' => 'Api\\RepartidorPedidosController', 'action' => 'create'],
     ['method' => 'PUT',    'path' => '/api/repartidor/pedidos/{id}/estado','controller' => 'Api\\RepartidorPedidosController', 'action' => 'updateStatus'],
     ['method' => 'DELETE', 'path' => '/api/repartidor/pedidos/{id}',      'controller' => 'Api\\RepartidorPedidosController', 'action' => 'destroy'],

     ['method' => 'POST',   'path' => '/api/repartidor/seguimiento/ubicacion', 'controller' => 'Api\\RepartidorSeguimientoController', 'action' => 'ubicacion'],
     ['method' => 'GET',    'path' => '/api/repartidor/seguimiento/{pedidoId}','controller' => 'Api\\RepartidorSeguimientoController', 'action' => 'show'],

     ['method' => 'GET',    'path' => '/api/repartidor/dashboard/stats',         'controller' => 'Api\\RepartidorDashboardController', 'action' => 'stats'],
     ['method' => 'GET',    'path' => '/api/repartidor/dashboard/recent-orders', 'controller' => 'Api\\RepartidorDashboardController', 'action' => 'recentOrders'],
     ['method' => 'GET',    'path' => '/api/repartidor/dashboard/low-stock',     'controller' => 'Api\\RepartidorDashboardController', 'action' => 'lowStock'],
     ['method' => 'GET',    'path' => '/api/repartidor/notificaciones',          'controller' => 'Api\\RepartidorDashboardController', 'action' => 'notificaciones'],

     ['method' => 'GET',    'path' => '/api/repartidor/clientes',      'controller' => 'Api\\RepartidorClientesController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/repartidor/clientes/{id}', 'controller' => 'Api\\RepartidorClientesController', 'action' => 'show'],

     ['method' => 'GET',    'path' => '/api/repartidor/rastreo',             'controller' => 'Api\\RepartidorRastreoController', 'action' => 'listar'],
     ['method' => 'GET',    'path' => '/api/repartidor/rastreo/buscar',      'controller' => 'Api\\RepartidorRastreoController', 'action' => 'buscar'],
     ['method' => 'GET',    'path' => '/api/repartidor/rastreo/{id}/historial','controller' => 'Api\\RepartidorRastreoController', 'action' => 'historial'],
     ['method' => 'PUT',    'path' => '/api/repartidor/rastreo/{id}/estado', 'controller' => 'Api\\RepartidorRastreoController', 'action' => 'actualizar'],

     ['method' => 'POST',   'path' => '/api/repartidor/documentos/subir',       'controller' => 'Api\\RepartidorDocumentosController', 'action' => 'subir'],
     ['method' => 'GET',    'path' => '/api/repartidor/documentos/{repartidorId}','controller' => 'Api\\RepartidorDocumentosController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/repartidor/documentos',             'controller' => 'Api\\RepartidorDocumentosController', 'action' => 'index'],
     
      // Página repartidor
     ['method' => 'GET', 'path' => '/repartidor', 'controller' => 'RepartidorController', 'action' => 'index'],
     ['method' => 'GET', 'path' => '/repartidor/dashboard', 'controller' => 'RepartidorController', 'action' => 'index'],
     ['method' => 'GET', 'path' => '/repartidor/perfil', 'controller' => 'RepartidorController', 'action' => 'perfil'],
     ['method' => 'GET', 'path' => '/repartidor/registro', 'controller' => 'RepartidorController', 'action' => 'registro'],
     ['method' => 'POST', 'path' => '/repartidor/registro', 'controller' => 'RepartidorAuthController', 'action' => 'registro'],
     ['method' => 'GET', 'path' => '/repartidor/login', 'controller' => 'RepartidorAuthController', 'action' => 'showLogin'],
     ['method' => 'POST', 'path' => '/repartidor/login', 'controller' => 'RepartidorAuthController', 'action' => 'login'],
     ['method' => 'GET', 'path' => '/repartidor/logout', 'controller' => 'RepartidorAuthController', 'action' => 'logout'],
     ['method' => 'POST', 'path' => '/repartidor/refresh-token', 'controller' => 'RepartidorAuthController', 'action' => 'refreshToken'],
      
      // Admin - Gestión de repartidores
      ['method' => 'GET',    'path' => '/admin/repartidores',                           'controller' => 'Admin\\RepartidorController', 'action' => 'index'],
      ['method' => 'GET',    'path' => '/api/admin/repartidores/solicitudes',           'controller' => 'Api\\AdminRepartidorController', 'action' => 'solicitudes'],
      ['method' => 'POST',   'path' => '/api/admin/repartidores/solicitudes/aprobar',   'controller' => 'Api\\AdminRepartidorController', 'action' => 'aprobar'],
      ['method' => 'POST',   'path' => '/api/admin/repartidores/solicitudes/rechazar',  'controller' => 'Api\\AdminRepartidorController', 'action' => 'rechazar'],
      ['method' => 'GET',    'path' => '/api/admin/repartidores/estadisticas',          'controller' => 'Api\\AdminRepartidorController', 'action' => 'estadisticas'],
      ['method' => 'GET',    'path' => '/api/admin/repartidores/activos',               'controller' => 'Api\\AdminRepartidorController', 'action' => 'activos'],
      ['method' => 'POST',   'path' => '/api/admin/repartidores/suspender',             'controller' => 'Api\\AdminRepartidorController', 'action' => 'suspender'],
      ['method' => 'POST',   'path' => '/api/admin/repartidores/activar',               'controller' => 'Api\\AdminRepartidorController', 'action' => 'activar'],
      ['method' => 'POST',   'path' => '/api/admin/repartidores/documentos/revisar',    'controller' => 'Api\\AdminRepartidorController', 'action' => 'revisarDocumento'],
      ['method' => 'GET',    'path' => '/api/admin/dashboard/stats',                    'controller' => 'Api\\AdminRepartidorController', 'action' => 'dashboardStats'],

      // ========== RUTAS API FACTURACION ==========
     ['method' => 'GET',    'path' => '/api/facturas',                    'controller' => 'Api\\FacturasController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/facturas/stats',              'controller' => 'Api\\FacturasController', 'action' => 'stats'],
     ['method' => 'POST',   'path' => '/api/facturas/crear',              'controller' => 'Api\\FacturasController', 'action' => 'crear'],
     ['method' => 'GET',    'path' => '/api/facturas/{id}',               'controller' => 'Api\\FacturasController', 'action' => 'show'],
     ['method' => 'PUT',    'path' => '/api/facturas/{id}/estado',        'controller' => 'Api\\FacturasController', 'action' => 'cambiarEstado'],
     ['method' => 'POST',   'path' => '/api/facturas/{id}/enviar-correo', 'controller' => 'Api\\FacturasController', 'action' => 'enviarCorreo'],
     ['method' => 'GET',    'path' => '/api/facturas/{id}/pdf',           'controller' => 'Api\\FacturasController', 'action' => 'pdf'],
     ['method' => 'GET',    'path' => '/api/facturas/pedido/{pedidoId}',  'controller' => 'Api\\FacturasController', 'action' => 'porPedido'],
];