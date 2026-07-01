<?php
return [
    ['method' => 'GET', 'path' => '/', 'controller' => 'HomeController', 'action' => 'index'],
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
    ['method' => 'GET', 'path' => '/seguimiento', 'controller' => 'SeguimientoController', 'action' => 'index'],
    // ['method' => 'GET', 'path' => '/debug-session', 'controller' => 'DebugController', 'action' => 'session'],
    // Panel de administración
    ['method' => 'GET', 'path' => '/admin', 'controller' => 'Admin\\DashboardController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/admin/pedidos', 'controller' => 'Admin\\PedidosController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/admin/usuarios', 'controller' => 'Admin\\UsuariosController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/admin/repartidores', 'controller' => 'Admin\\RepartidorController', 'action' => 'index'],
    ['method' => 'GET', 'path' => '/perfil', 'controller' => 'PerfilController', 'action' => 'index'],
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
   
     // ========== RUTAS API CARRITO ==========
    ['method' => 'GET',    'path' => '/api/carrito',          'controller' => 'Api\\CarritoController', 'action' => 'index'],
    ['method' => 'POST',   'path' => '/api/carrito/agregar',   'controller' => 'Api\\CarritoController', 'action' => 'agregar'],
    ['method' => 'POST',   'path' => '/api/carrito/actualizar','controller' => 'Api\\CarritoController', 'action' => 'actualizar'],
    ['method' => 'DELETE', 'path' => '/api/carrito/eliminar',  'controller' => 'Api\\CarritoController', 'action' => 'eliminar'],
    ['method' => 'POST',   'path' => '/api/carrito/sincronizar','controller' => 'Api\\CarritoController', 'action' => 'sincronizar'],

     // ========== RUTAS API FAVORITOS ==========
     ['method' => 'GET',    'path' => '/api/favoritos',         'controller' => 'Api\\FavoritoController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/favoritos/agregar',  'controller' => 'Api\\FavoritoController', 'action' => 'agregar'],
     ['method' => 'DELETE', 'path' => '/api/favoritos/eliminar', 'controller' => 'Api\\FavoritoController', 'action' => 'eliminar'],

     // ========== RUTAS API INVENTARIO ==========
     ['method' => 'GET',    'path' => '/api/productos',          'controller' => 'Api\\ProductsController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/productos',          'controller' => 'Api\\ProductsController', 'action' => 'store'],
     ['method' => 'PUT',    'path' => '/api/productos',          'controller' => 'Api\\ProductsController', 'action' => 'update'],
     ['method' => 'DELETE', 'path' => '/api/productos',          'controller' => 'Api\\ProductsController', 'action' => 'destroy'],

     ['method' => 'GET',    'path' => '/api/categorias',         'controller' => 'Api\\CategoriesController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/categorias',         'controller' => 'Api\\CategoriesController', 'action' => 'store'],
     ['method' => 'PUT',    'path' => '/api/categorias',         'controller' => 'Api\\CategoriesController', 'action' => 'update'],
     ['method' => 'DELETE', 'path' => '/api/categorias',         'controller' => 'Api\\CategoriesController', 'action' => 'destroy'],

     ['method' => 'GET',    'path' => '/api/inventario',         'controller' => 'Api\\StockController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/inventario',         'controller' => 'Api\\StockController', 'action' => 'update'],
     ['method' => 'GET',    'path' => '/api/clientes',           'controller' => 'Admin\\ClientesController', 'action' => 'index'],
     ['method' => 'POST',   'path' => '/api/clientes/buscar',    'controller' => 'Admin\\ClientesController', 'action' => 'buscar'],
     ['method' => 'POST',   'path' => '/api/clientes/rol',       'controller' => 'Admin\\ClientesController', 'action' => 'cambiarRol'],
     ['method' => 'GET',    'path' => '/api/pedidos',             'controller' => 'Admin\\PedidosController', 'action' => 'obtenerPedidos'],
     ['method' => 'POST',   'path' => '/api/pedidos/estado',     'controller' => 'Admin\\PedidosController', 'action' => 'updateStatus'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos',          'controller' => 'Cliente\\PedidosController', 'action' => 'index'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos/:id',      'controller' => 'Cliente\\PedidosController', 'action' => 'detalle'],
     ['method' => 'POST',   'path' => '/api/mis-pedidos/:id/cancelar', 'controller' => 'Cliente\\PedidosController', 'action' => 'cancelar'],
     ['method' => 'GET',    'path' => '/api/mis-pedidos/:id/factura', 'controller' => 'Cliente\\PedidosController', 'action' => 'factura'],
     ['method' => 'POST',   'path' => '/api/inventario/update',    'controller' => 'Api\\StockController', 'action' => 'update'],
     ['method' => 'POST',   'path' => '/api/inventario/ajustar',    'controller' => 'Api\\StockController', 'action' => 'ajustar'],
];