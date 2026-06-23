-- =============================================
-- BASE DE DATOS ANGELOW 
-- Versión: 3.1 - 2026 (CORREGIDA)
-- Fecha: 07 de febrero de 2026
-- =============================================

-- Eliminar base de datos existente
DROP DATABASE IF EXISTS angelow_db;

-- Crear la base de datos
CREATE DATABASE angelow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE angelow_db;

-- =============================================
-- TABLA DE USUARIOS (Compatibles con repartidor.html)
-- =============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    cedula VARCHAR(50) UNIQUE,
    genero ENUM('masculino', 'femenino', 'otro', 'prefiero_no_decirlo'),
    fecha_nacimiento DATE,
    telefono VARCHAR(20),
    direccion TEXT,
    ciudad VARCHAR(100),
    departamento VARCHAR(100),
    codigo_postal VARCHAR(20),
    
    -- Autenticación
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(32),
    rol ENUM('cliente', 'repartidor', 'administrador', 'vendedor') DEFAULT 'cliente',
    
    -- Perfil
    avatar_url VARCHAR(500),
    remember_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_expiry DATETIME,
    
    -- Verificaciones
    email_verificado BOOLEAN DEFAULT FALSE,
    telefono_verificado BOOLEAN DEFAULT FALSE,
    
    -- Preferencias
    acepta_terminos BOOLEAN DEFAULT FALSE,
    acepta_marketing BOOLEAN DEFAULT TRUE,
    
    -- Vehículo para repartidores (compatible con repartidor.html)
    tipo_vehiculo ENUM('moto', 'carro', 'bicicleta', 'camioneta') DEFAULT 'moto',
    placa_vehiculo VARCHAR(20),
    
    -- Stats para repartidores
    total_entregas INT DEFAULT 0,
    ganancias_totales DECIMAL(12,2) DEFAULT 0.00,
    calificacion_promedio DECIMAL(3,2) DEFAULT 5.00,
    
    -- Fechas
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion TIMESTAMP NULL DEFAULT NULL,
    
    -- Estado
    estado ENUM('activo', 'inactivo', 'suspendido', 'eliminado') DEFAULT 'activo',
    en_linea BOOLEAN DEFAULT FALSE,
    
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado),
    INDEX idx_vehiculo (tipo_vehiculo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE CATEGORÍAS
-- =============================================
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(500),
    parent_id INT DEFAULT NULL,
    orden INT DEFAULT 0,
    visible BOOLEAN DEFAULT TRUE,
    tipo ENUM('categoria', 'subcategoria', 'etiqueta') DEFAULT 'categoria',
    destacada BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES categorias(id) ON DELETE CASCADE,
    
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE PRODUCTOS
-- =============================================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(300) UNIQUE NOT NULL,
    descripcion LONGTEXT,
    descripcion_corta TEXT,
    categoria_id INT NOT NULL,
    subcategoria_id INT,
    
    -- Precios
    precio DECIMAL(10,2) NOT NULL,
    precio_original DECIMAL(10,2),
    descuento_porcentaje DECIMAL(5,2) DEFAULT 0.00,
    
    -- Identificación
    sku VARCHAR(100) UNIQUE,
    referencia VARCHAR(100),
    
    -- Inventario
    stock_total INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    
    -- Variantes
    tallas_disponibles JSON,
    colores_disponibles JSON,
    
    -- Multimedia
    imagenes JSON,
    
    -- Características
    caracteristicas JSON,
    especificaciones JSON,
    material VARCHAR(200),
    cuidados TEXT,
    edad_recomendada VARCHAR(50),
    peso_gramos DECIMAL(8,2),
    dimensiones VARCHAR(100),
    
    -- Estadísticas
    calificacion_promedio DECIMAL(3,2) DEFAULT 0.00,
    total_resenas INT DEFAULT 0,
    total_vendidos INT DEFAULT 0,
    
    -- Flags (compatibles con HTML)
    destacado BOOLEAN DEFAULT FALSE,
    nuevo BOOLEAN DEFAULT TRUE,
    oferta BOOLEAN DEFAULT FALSE,
    popular BOOLEAN DEFAULT FALSE,
    edicion_especial BOOLEAN DEFAULT FALSE,
    
    -- Visibilidad
    visible BOOLEAN DEFAULT TRUE,
    
    -- Fechas
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (subcategoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    
    INDEX idx_categoria (categoria_id),
    INDEX idx_slug (slug),
    INDEX idx_precio (precio),
    INDEX idx_destacado (destacado),
    INDEX idx_oferta (oferta),
    INDEX idx_popular (popular),
    FULLTEXT idx_busqueda (nombre, descripcion, descripcion_corta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE VARIANTES DE PRODUCTO
-- =============================================
CREATE TABLE variantes_producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    sku VARCHAR(100) UNIQUE,
    talla VARCHAR(20),
    color_nombre VARCHAR(50),
    color_hex VARCHAR(7),
    stock INT DEFAULT 0,
    precio_extra DECIMAL(10,2) DEFAULT 0.00,
    imagen_url VARCHAR(500),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_variante (producto_id, talla, color_nombre),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    
    INDEX idx_producto (producto_id),
    INDEX idx_sku (sku),
    INDEX idx_stock (stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE PEDIDOS (Compatibles con repartidor.html)
-- =============================================
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificación
    numero_pedido VARCHAR(50) UNIQUE NOT NULL,
    usuario_id INT NOT NULL,
    repartidor_id INT,
    
    -- Información del cliente
    nombre_cliente VARCHAR(200) NOT NULL,
    email_cliente VARCHAR(255) NOT NULL,
    telefono_cliente VARCHAR(20) NOT NULL,
    cedula_cliente VARCHAR(50),
    
    -- Dirección de envío
    direccion_envio TEXT NOT NULL,
    direccion_complementaria TEXT,
    barrio VARCHAR(100),
    ciudad VARCHAR(100) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    codigo_postal VARCHAR(20),
    destinatario VARCHAR(200),
    informacion_adicional TEXT,
    
    -- Fechas importantes
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_estimada_entrega DATE,
    fecha_entrega_real TIMESTAMP NULL,
    
    -- Estado del pedido (compatible con repartidor.html)
    estado ENUM('pendiente', 'confirmado', 'procesando', 'listo', 'en_camino', 'entregado', 'cancelado', 'reembolsado') DEFAULT 'pendiente',
    
    -- Métodos
    metodo_pago ENUM('pse', 'tarjeta_credito', 'tarjeta_debito', 'mercadopago', 'efectivo', 'transferencia') DEFAULT 'pse',
    metodo_envio ENUM('normal', 'express', 'pickup') DEFAULT 'normal',
    costo_envio DECIMAL(10,2) DEFAULT 0.00,
    
    -- Totales
    subtotal DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    impuestos DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    
    -- Pago
    estado_pago ENUM('pendiente', 'procesando', 'completado', 'fallido', 'reembolsado') DEFAULT 'pendiente',
    transaccion_id VARCHAR(100),
    
    -- Seguimiento (compatible con mapa.html)
    codigo_seguimiento VARCHAR(100),
    transportadora VARCHAR(100),
    
    -- Zona y prioridad (compatible con repartidor.html)
    zona ENUM('norte', 'sur', 'centro', 'este', 'oeste', 'rural') DEFAULT 'centro',
    prioridad ENUM('normal', 'alta', 'urgente') DEFAULT 'normal',
    
    -- Logística
    tiempo_estimado_minutos INT,
    distancia_km DECIMAL(5,2),
    ganancias_repartidor DECIMAL(10,2),
    
    -- Notas
    notas_cliente TEXT,
    notas_internas TEXT,
    
    -- Auditoría
    ip_cliente VARCHAR(45),
    user_agent TEXT,
    
    -- Ubicación (compatible con mapa.html)
    latitud_origen DECIMAL(10,8),
    longitud_origen DECIMAL(11,8),
    latitud_destino DECIMAL(10,8),
    longitud_destino DECIMAL(11,8),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_numero_pedido (numero_pedido),
    INDEX idx_usuario (usuario_id),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_estado (estado),
    INDEX idx_zona (zona),
    INDEX idx_prioridad (prioridad),
    INDEX idx_fecha_pedido (fecha_pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE DETALLES DE PEDIDO
-- =============================================
CREATE TABLE detalles_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    variante_id INT,
    
    -- Información del producto
    nombre_producto VARCHAR(255) NOT NULL,
    sku_producto VARCHAR(100),
    
    -- Cantidad y precio
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    
    -- Variantes
    talla VARCHAR(20),
    color VARCHAR(50),
    
    -- Multimedia
    imagen_url VARCHAR(500),
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    FOREIGN KEY (variante_id) REFERENCES variantes_producto(id) ON DELETE SET NULL,
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE HISTORIAL DE ENTREGAS (repartidor.html)
-- =============================================
CREATE TABLE historial_entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    repartidor_id INT NOT NULL,
    
    -- Información de la entrega
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tiempo_entrega_minutos INT,
    distancia_recorrida_km DECIMAL(5,2),
    
    -- Ganancia
    ganancia DECIMAL(10,2) NOT NULL,
    
    -- Calificación
    calificacion_cliente TINYINT CHECK (calificacion_cliente >= 1 AND calificacion_cliente <= 5),
    comentario_cliente TEXT,
    
    -- Estado
    estado ENUM('completada', 'fallida', 'cancelada') DEFAULT 'completada',
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE SEGUIMIENTO EN TIEMPO REAL (mapa.html)
-- =============================================
CREATE TABLE seguimiento_tiempo_real (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    repartidor_id INT NOT NULL,
    
    -- Ubicación actual
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8),
    precision_gps DECIMAL(5,2),
    
    -- Estado del repartidor
    velocidad_kmh DECIMAL(5,2),
    direccion_grados SMALLINT,
    bateria_porcentaje TINYINT,
    
    -- Tiempo estimado
    tiempo_estimado_minutos INT,
    distancia_restante_km DECIMAL(5,2),
    
    -- Timestamp
    timestamp_ubicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_timestamp (timestamp_ubicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE RUTAS Y WAYPOINTS (mapa.html)
-- =============================================
CREATE TABLE rutas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    repartidor_id INT NOT NULL,
    
    -- Información de la ruta
    polilinea TEXT,
    distancia_total_km DECIMAL(6,2),
    tiempo_estimado_minutos INT,
    
    -- Waypoints
    waypoints JSON,
    
    -- Estado
    activa BOOLEAN DEFAULT TRUE,
    
    -- Fechas
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_pedido (pedido_id),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE NOTIFICACIONES (repartidor.html)
-- =============================================
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    
    -- Contenido
    tipo ENUM('pedido', 'entrega', 'promocion', 'sistema', 'seguridad', 'nuevo_pedido') DEFAULT 'sistema',
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    enlace VARCHAR(500),
    
    -- Datos adicionales
    pedido_id INT,
    datos_adicionales JSON,
    
    -- Estado
    leida BOOLEAN DEFAULT FALSE,
    
    -- Fechas
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura TIMESTAMP NULL,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_leida (leida),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE CARRITO DE COMPRAS
-- =============================================
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    session_id VARCHAR(100),
    producto_id INT NOT NULL,
    variante_id INT,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    talla_seleccionada VARCHAR(20),
    color_seleccionado VARCHAR(50),
    agregado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (variante_id) REFERENCES variantes_producto(id) ON DELETE SET NULL,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE FAVORITOS
-- =============================================
CREATE TABLE favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_favorito (usuario_id, producto_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE RESEÑAS
-- =============================================
CREATE TABLE resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    pedido_id INT,
    calificacion TINYINT CHECK (calificacion >= 1 AND calificacion <= 5),
    titulo VARCHAR(200),
    comentario TEXT,
    fotos JSON,
    verificada_compra BOOLEAN DEFAULT FALSE,
    likes INT DEFAULT 0,
    reportada BOOLEAN DEFAULT FALSE,
    visible BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_resena (usuario_id, producto_id, pedido_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
    
    INDEX idx_producto (producto_id),
    INDEX idx_calificacion (calificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE CUPONES/DESCUENTOS
-- =============================================
CREATE TABLE cupones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(255),
    tipo ENUM('porcentaje', 'monto_fijo', 'envio_gratis') DEFAULT 'monto_fijo',
    valor DECIMAL(10,2) NOT NULL,
    valor_minimo_compra DECIMAL(10,2) DEFAULT 0.00,
    fecha_inicio DATE,
    fecha_fin DATE,
    usos_maximos INT,
    usos_actuales INT DEFAULT 0,
    limite_por_usuario INT DEFAULT 1,
    productos_aplicables JSON,
    categorias_aplicables JSON,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE CONFIGURACIONES DEL SISTEMA
-- =============================================
CREATE TABLE configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json', 'array') DEFAULT 'string',
    categoria VARCHAR(50),
    descripcion TEXT,
    editable BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_clave (clave),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA DE LOGS DE ACTIVIDAD
-- =============================================
CREATE TABLE logs_actividad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    tipo VARCHAR(50) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos JSON,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TRIGGERS Y FUNCIONES
-- =============================================

DELIMITER //

-- Trigger para generar número de pedido (compatible con repartidor.html)
CREATE TRIGGER generar_numero_pedido
BEFORE INSERT ON pedidos
FOR EACH ROW
BEGIN
    DECLARE year_val INT;
    DECLARE seq_val INT;
    
    SET year_val = YEAR(NOW());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 9) AS UNSIGNED)), 0) + 1 INTO seq_val
    FROM pedidos 
    WHERE numero_pedido LIKE CONCAT('ORD-', year_val, '-%');
    
    IF NEW.numero_pedido IS NULL THEN
        SET NEW.numero_pedido = CONCAT('ORD-', year_val, '-', LPAD(seq_val, 4, '0'));
    END IF;
END//

-- Trigger para actualizar estadísticas del repartidor
CREATE TRIGGER actualizar_stats_repartidor
AFTER INSERT ON historial_entregas
FOR EACH ROW
BEGIN
    DECLARE total_entregas INT;
    DECLARE ganancias_totales DECIMAL(12,2);
    DECLARE avg_calificacion DECIMAL(3,2);
    
    SELECT 
        COUNT(*),
        SUM(ganancia),
        AVG(calificacion_cliente)
    INTO total_entregas, ganancias_totales, avg_calificacion
    FROM historial_entregas
    WHERE repartidor_id = NEW.repartidor_id
    AND estado = 'completada';
    
    UPDATE usuarios
    SET 
        total_entregas = total_entregas,
        ganancias_totales = COALESCE(ganancias_totales, 0),
        calificacion_promedio = COALESCE(avg_calificacion, 5.00)
    WHERE id = NEW.repartidor_id;
END//

-- Trigger para actualizar stock al crear pedido
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

-- Trigger para crear notificación de nuevo pedido
CREATE TRIGGER notificar_nuevo_pedido
AFTER INSERT ON pedidos
FOR EACH ROW
BEGIN
    INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, pedido_id, datos_adicionales)
    SELECT 
        u.id,
        'nuevo_pedido',
        'Nuevo pedido disponible',
        CONCAT('Pedido ', NEW.numero_pedido, ' disponible en zona ', UPPER(NEW.zona)),
        NEW.id,
        JSON_OBJECT(
            'numero_pedido', NEW.numero_pedido,
            'zona', NEW.zona,
            'prioridad', NEW.prioridad,
            'ganancias', NEW.ganancias_repartidor
        )
    FROM usuarios u
    WHERE u.rol = 'repartidor'
    AND u.estado = 'activo'
    AND u.en_linea = TRUE;
END//

-- =============================================
-- PROCEDIMIENTOS ALMACENADOS
-- =============================================

-- Procedimiento para obtener pedidos disponibles para repartidor
CREATE PROCEDURE obtener_pedidos_disponibles(
    IN p_zona VARCHAR(50),
    IN p_prioridad VARCHAR(20)
)
BEGIN
    SELECT 
        p.*,
        u.nombre as nombre_cliente_completo,
        u.telefono as telefono_cliente
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.estado IN ('confirmado', 'listo')
    AND p.repartidor_id IS NULL
    AND (p_zona = 'todas' OR p.zona = p_zona)
    AND (p_prioridad = 'todas' OR p.prioridad = p_prioridad)
    ORDER BY 
        CASE p.prioridad
            WHEN 'urgente' THEN 1
            WHEN 'alta' THEN 2
            ELSE 3
        END,
        p.fecha_pedido;
END//

-- Procedimiento para aceptar pedido (repartidor.html)
CREATE PROCEDURE aceptar_pedido(
    IN p_pedido_id INT,
    IN p_repartidor_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'Error: No se pudo aceptar el pedido' as resultado;
    END;
    
    START TRANSACTION;
    
    UPDATE pedidos 
    SET 
        repartidor_id = p_repartidor_id,
        estado = 'en_camino',
        fecha_estimada_entrega = DATE_ADD(NOW(), INTERVAL tiempo_estimado_minutos MINUTE)
    WHERE id = p_pedido_id;
    
    INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, pedido_id)
    SELECT 
        usuario_id,
        'entrega',
        'Tu pedido está en camino',
        CONCAT('El repartidor ha aceptado tu pedido ', numero_pedido),
        p_pedido_id
    FROM pedidos 
    WHERE id = p_pedido_id;
    
    UPDATE usuarios 
    SET en_linea = TRUE 
    WHERE id = p_repartidor_id;
    
    COMMIT;
    
    SELECT 'Pedido aceptado exitosamente' as resultado;
END//

-- Procedimiento para actualizar ubicación en tiempo real (mapa.html)
CREATE PROCEDURE actualizar_ubicacion(
    IN p_repartidor_id INT,
    IN p_pedido_id INT,
    IN p_latitud DECIMAL(10,8),
    IN p_longitud DECIMAL(11,8),
    IN p_precision DECIMAL(5,2)
)
BEGIN
    INSERT INTO seguimiento_tiempo_real 
    (pedido_id, repartidor_id, latitud, longitud, precision_gps)
    VALUES (p_pedido_id, p_repartidor_id, p_latitud, p_longitud, p_precision);
    
    UPDATE seguimiento_tiempo_real 
    SET tiempo_estimado_minutos = FLOOR(RAND() * 30) + 5,
        distancia_restante_km = FLOOR(RAND() * 10) + 1
    WHERE id = LAST_INSERT_ID();
    
    SELECT 'Ubicación actualizada' as resultado;
END//

-- Procedimiento para completar entrega
CREATE PROCEDURE completar_entrega(
    IN p_pedido_id INT
)
BEGIN
    DECLARE v_repartidor_id INT;
    DECLARE v_ganancia DECIMAL(10,2);
    
    SELECT repartidor_id, ganancias_repartidor 
    INTO v_repartidor_id, v_ganancia
    FROM pedidos 
    WHERE id = p_pedido_id;
    
    START TRANSACTION;
    
    UPDATE pedidos 
    SET 
        estado = 'entregado',
        fecha_entrega_real = NOW()
    WHERE id = p_pedido_id;
    
    INSERT INTO historial_entregas 
    (pedido_id, repartidor_id, ganancia, tiempo_entrega_minutos)
    VALUES (
        p_pedido_id, 
        v_repartidor_id, 
        v_ganancia,
        TIMESTAMPDIFF(MINUTE, 
            (SELECT fecha_pedido FROM pedidos WHERE id = p_pedido_id), 
            NOW()
        )
    );
    
    INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, pedido_id)
    SELECT 
        usuario_id,
        'entrega',
        'Pedido entregado',
        CONCAT('Tu pedido ', numero_pedido, ' ha sido entregado exitosamente'),
        p_pedido_id
    FROM pedidos 
    WHERE id = p_pedido_id;
    
    COMMIT;
    
    SELECT 'Entrega completada exitosamente' as resultado;
END//

-- Procedimiento para obtener estadísticas del repartidor
CREATE PROCEDURE obtener_estadisticas_repartidor(
    IN p_repartidor_id INT,
    IN p_periodo_dias INT
)
BEGIN
    SELECT 
        total_entregas,
        ganancias_totales,
        calificacion_promedio
    FROM usuarios 
    WHERE id = p_repartidor_id;
    
    SELECT 
        COUNT(*) as entregas_periodo,
        SUM(ganancia) as ganancias_periodo,
        AVG(calificacion_cliente) as calificacion_periodo
    FROM historial_entregas
    WHERE repartidor_id = p_repartidor_id
    AND fecha_entrega >= DATE_SUB(NOW(), INTERVAL p_periodo_dias DAY);
    
    SELECT 
        COUNT(*) as pedidos_activos
    FROM pedidos
    WHERE repartidor_id = p_repartidor_id
    AND estado IN ('en_camino', 'listo');
END//

DELIMITER ;

-- =============================================
-- VISTAS ÚTILES (CORREGIDAS)
-- =============================================

-- Vista para dashboard del repartidor (repartidor.html)
CREATE VIEW vista_dashboard_repartidor AS
SELECT 
    u.id as repartidor_id,
    u.nombre,
    u.email,
    u.tipo_vehiculo,
    u.placa_vehiculo,
    u.total_entregas,
    u.ganancias_totales,
    u.calificacion_promedio,
    u.en_linea,
    
    (SELECT COUNT(*) FROM pedidos p WHERE p.repartidor_id = u.id AND p.estado IN ('en_camino', 'listo')) as pedidos_activos,
    
    (SELECT COUNT(*) FROM historial_entregas he WHERE he.repartidor_id = u.id AND DATE(he.fecha_entrega) = CURDATE()) as entregas_hoy,
    
    (SELECT SUM(ganancia) FROM historial_entregas he WHERE he.repartidor_id = u.id AND DATE(he.fecha_entrega) = CURDATE()) as ganancias_hoy
    
FROM usuarios u
WHERE u.rol = 'repartidor';

-- Vista para seguimiento de pedidos (mapa.html)
CREATE VIEW vista_seguimiento_pedidos AS
SELECT 
    p.id as pedido_id,
    p.numero_pedido,
    p.estado,
    p.zona,
    p.prioridad,
    p.direccion_envio,
    p.ciudad,
    p.fecha_estimada_entrega,
    p.ganancias_repartidor,
    
    p.nombre_cliente,
    p.telefono_cliente,
    
    u.nombre as nombre_repartidor,
    u.telefono as telefono_repartidor,
    u.tipo_vehiculo,
    u.placa_vehiculo,
    
    str.latitud,
    str.longitud,
    str.timestamp_ubicacion,
    str.tiempo_estimado_minutos,
    str.distancia_restante_km
    
FROM pedidos p
LEFT JOIN usuarios u ON p.repartidor_id = u.id
LEFT JOIN seguimiento_tiempo_real str ON p.id = str.pedido_id
WHERE str.timestamp_ubicacion = (
    SELECT MAX(timestamp_ubicacion) 
    FROM seguimiento_tiempo_real 
    WHERE pedido_id = p.id
) OR str.timestamp_ubicacion IS NULL;

-- Vista para productos populares (CORREGIDA - Sin duplicados)
CREATE VIEW vista_productos_populares AS
SELECT 
    p.id,
    p.nombre,
    p.slug,
    p.descripcion,
    p.descripcion_corta,
    p.categoria_id,
    p.subcategoria_id,
    p.precio,
    p.precio_original,
    p.descuento_porcentaje,
    p.sku,
    p.referencia,
    p.stock_total,
    p.stock_minimo,
    p.tallas_disponibles,
    p.colores_disponibles,
    p.imagenes,
    p.caracteristicas,
    p.especificaciones,
    p.material,
    p.cuidados,
    p.edad_recomendada,
    p.peso_gramos,
    p.dimensiones,
    p.calificacion_promedio,
    p.total_resenas,
    p.total_vendidos,
    p.destacado,
    p.nuevo,
    p.oferta,
    p.popular,
    p.edicion_especial,
    p.visible,
    p.fecha_creacion,
    p.fecha_actualizacion,
    c.nombre as categoria_nombre,
    c.slug as categoria_slug,
    (SELECT COUNT(*) FROM favoritos f WHERE f.producto_id = p.id) as total_favoritos
FROM productos p
JOIN categorias c ON p.categoria_id = c.id
WHERE p.visible = TRUE
ORDER BY p.total_vendidos DESC, p.calificacion_promedio DESC;

-- =============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =============================================

CREATE INDEX idx_pedidos_zona_estado ON pedidos(zona, estado, prioridad);
CREATE INDEX idx_pedidos_fecha_estado ON pedidos(fecha_pedido, estado);
CREATE INDEX idx_notificaciones_usuario_fecha ON notificaciones(usuario_id, leida, fecha_envio);
CREATE INDEX idx_seguimiento_pedido_fecha ON seguimiento_tiempo_real(pedido_id, timestamp_ubicacion);
CREATE INDEX idx_productos_categoria_precio ON productos(categoria_id, precio, visible);
CREATE INDEX idx_historial_repartidor_fecha ON historial_entregas(repartidor_id, fecha_entrega);
CREATE INDEX idx_pedidos_repartidor_estado ON pedidos(repartidor_id, estado);

-- =============================================
-- DATOS INICIALES DE PRUEBA
-- =============================================

INSERT INTO configuraciones (clave, valor, tipo, categoria, descripcion) VALUES
('tienda_nombre', 'ANGELOW', 'string', 'general', 'Nombre de la tienda'),
('tienda_email', 'info@angelow.com', 'string', 'general', 'Email de contacto'),
('tienda_telefono', '+57 300 123 4567', 'string', 'general', 'Teléfono de contacto'),
('ganancia_por_km', '1500', 'number', 'repartidores', 'Ganancia por kilómetro recorrido'),
('ganancia_base', '5000', 'number', 'repartidores', 'Ganancia base por entrega'),
('envio_gratis_minimo', '150000', 'number', 'envios', 'Mínimo para envío gratis'),
('envio_costo_normal', '8000', 'number', 'envios', 'Costo envío normal'),
('envio_costo_express', '15000', 'number', 'envios', 'Costo envío express'),
('impuesto_porcentaje', '19', 'number', 'impuestos', 'Porcentaje de IVA'),
('moneda', 'COP', 'string', 'general', 'Moneda de la tienda');

INSERT INTO categorias (nombre, slug, descripcion, orden, tipo, destacada) VALUES
('Bebés', 'bebes', 'Ropa y accesorios para bebés', 1, 'categoria', TRUE),
('Niños', 'ninos', 'Ropa para niños de todas las edades', 2, 'categoria', TRUE),
('Niñas', 'ninas', 'Ropa para niñas de todas las edades', 3, 'categoria', TRUE),
('Zapatos', 'zapatos', 'Calzado infantil', 4, 'categoria', FALSE);

INSERT INTO categorias (nombre, slug, parent_id, orden, tipo) VALUES
('Edición Especial', 'edicion-especial', 1, 1, 'subcategoria'),
('Conjuntos', 'conjuntos', 2, 1, 'subcategoria'),
('Ropa Deportiva', 'ropa-deportiva', 2, 2, 'subcategoria'),
('Vestidos', 'vestidos', 3, 1, 'subcategoria'),
('Tenis', 'tenis', 4, 1, 'subcategoria');

-- Password: Angelow2026! (hash generado con password_hash)
INSERT INTO usuarios (email, nombre, apellido, password_hash, rol, telefono, ciudad, acepta_terminos, email_verificado, tipo_vehiculo, placa_vehiculo, en_linea) VALUES
('admin@angelow.com', 'Administrador', 'Sistema', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', '3001112233', 'Bogotá', TRUE, TRUE, NULL, NULL, TRUE),
('repartidor1@angelow.com', 'Juan', 'Delgado', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'repartidor', '3002223344', 'Bogotá', TRUE, TRUE, 'moto', 'ABC-123', TRUE),
('repartidor2@angelow.com', 'Carlos', 'Rodríguez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'repartidor', '3003334455', 'Medellín', TRUE, TRUE, 'carro', 'XYZ-789', FALSE),
('repartidor3@angelow.com', 'Ana', 'Martínez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'repartidor', '3004445566', 'Cali', TRUE, TRUE, 'moto', 'DEF-456', TRUE),
('cliente1@angelow.com', 'María', 'González', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', '3005556677', 'Bogotá', TRUE, TRUE, NULL, NULL, FALSE),
('cliente2@angelow.com', 'Pedro', 'Ramírez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', '3006667788', 'Medellín', TRUE, TRUE, NULL, NULL, FALSE),
('cliente3@angelow.com', 'Laura', 'Sánchez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', '3007778899', 'Cali', TRUE, TRUE, NULL, NULL, FALSE);

INSERT INTO productos (nombre, slug, descripcion_corta, categoria_id, subcategoria_id, precio, precio_original, descuento_porcentaje, sku, stock_total, tallas_disponibles, colores_disponibles, imagenes, caracteristicas, destacado, popular, oferta, nuevo) VALUES
('Conjunto Deportivo', 'conjunto-deportivo', 'Conjunto deportivo para niños', 2, 6, 49900, 69900, 28, 'CONJ-DEP-001', 15, '["2", "4", "6", "8"]', '["Azul Marino", "Negro"]', '["assets/imagenes/ninos/Frente Conjunto Deportivo.png"]', '["Material: 100% Algodón", "Lavable a máquina"]', TRUE, TRUE, TRUE, TRUE),
('Set Bebé Premium', 'set-bebe-premium', 'Set completo para bebé recién nacido', 1, 5, 39900, 54900, 27, 'SET-BEB-002', 10, '["2", "4", "6", "8"]', '["Rosa", "Celeste", "Blanco"]', '["assets/imagenes/bebe/Frente Set Bebe.png"]', '["Material hipoalergénico", "Set de 3 piezas"]', TRUE, FALSE, TRUE, TRUE),
('Conjunto Infantil', 'conjunto-infantil', 'Conjunto elegante y divertido para niñas', 3, 8, 45900, 62900, 27, 'CONJ-INF-003', 12, '["2", "4", "6", "8"]', '["Rosa", "Lila", "Blanco"]', '["assets/imagenes/ninas/Frente Conjunto Infantil.png"]', '["Estampado exclusivo", "Material: Algodón y elastano"]', FALSE, TRUE, FALSE, TRUE),
('Jogger Niño', 'jogger-nino', 'Pantalón jogger cómodo y moderno', 2, 7, 49900, 64900, 23, 'JOG-NIN-004', 8, '["2", "4", "6", "8"]', '["Azul", "Negro"]', '["assets/imagenes/ninos/Frente Jogger.png"]', '["Material: Jersey de algodón", "Cintura elástica"]', TRUE, TRUE, TRUE, FALSE),
('Body Niño', 'body-nino', 'Body cómodo y práctico', 2, 6, 34900, 44900, 22, 'BODY-NIN-005', 20, '["2", "4", "6", "8"]', '["Blanco", "Azul"]', '["assets/imagenes/ninos/Frente Body Niño.png"]', '["100% algodón orgánico", "Broches de seguridad"]', FALSE, TRUE, TRUE, TRUE),
('Body Negro', 'body-negro', 'Body negro básico para niños', 2, 6, 32900, 42900, 23, 'BODY-NEG-006', 6, '["2", "4", "6", "8"]', '["Negro"]', '["assets/imagenes/ninas/Frente Body Negro.png"]', '["Color negro clásico", "100% Algodón"]', FALSE, FALSE, FALSE, FALSE),
('Set Falda', 'set-falda', 'Set con falda y top a juego', 3, 8, 39900, 54900, 27, 'SET-FAL-007', 10, '["2", "4", "6", "8"]', '["Rosa", "Blanco"]', '["assets/imagenes/ninas/Frente Set Falda.png"]', '["Falda con vuelo", "Top a juego"]', FALSE, TRUE, TRUE, TRUE);

INSERT INTO variantes_producto (producto_id, sku, talla, color_nombre, color_hex, stock, precio_extra) VALUES
(1, 'CONJ-DEP-001-AZUL-4', '4', 'Azul Marino', '#1E3A8A', 5, 0),
(1, 'CONJ-DEP-001-NEGRO-6', '6', 'Negro', '#000000', 4, 0),
(2, 'SET-BEB-002-ROSA-3M', '0-3M', 'Rosa', '#F472B6', 3, 0),
(3, 'VEST-NIN-003-LILA-6', '6', 'Lila', '#A855F7', 4, 0),
(4, 'TENIS-INF-004-AZUL-30', '30', 'Azul/Blanco', '#3B82F6', 6, 0);

INSERT INTO pedidos (numero_pedido, usuario_id, nombre_cliente, email_cliente, telefono_cliente, direccion_envio, ciudad, estado, metodo_pago, subtotal, total, zona, prioridad, ganancias_repartidor, codigo_seguimiento) VALUES
('ORD-2024-0001', 5, 'María González', 'cliente1@angelow.com', '3005556677', 'Calle 100 #15-30, Apto 301', 'Bogotá', 'listo', 'tarjeta_credito', 1799800, 1799800, 'centro', 'alta', 8000, 'TRACK-001'),
('ORD-2024-0002', 6, 'Pedro Ramírez', 'cliente2@angelow.com', '3006667788', 'Carrera 7 #120-50, Casa 5', 'Medellín', 'en_camino', 'mercadopago', 899900, 899900, 'norte', 'normal', 6000, 'TRACK-002'),
('ORD-2024-0003', 7, 'Laura Sánchez', 'cliente3@angelow.com', '3007778899', 'Avenida 19 #134-25, Oficina 203', 'Cali', 'pendiente', 'pse', 2699700, 2699700, 'sur', 'normal', 10000, 'TRACK-003'),
('ORD-2024-0004', 5, 'María González', 'cliente1@angelow.com', '3005556677', 'Transversal 23 #45-89, Local 10', 'Bogotá', 'entregado', 'tarjeta_credito', 1799800, 1799800, 'centro', 'normal', 7000, 'TRACK-004');

UPDATE pedidos SET repartidor_id = 3 WHERE id = 2;
UPDATE pedidos SET repartidor_id = 4 WHERE id = 4;

INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal, imagen_url) VALUES
(1, 1, 'Conjunto Deportivo', 2, 899900, 1799800, 'img/conjunto-deportivo-1.jpg'),
(2, 2, 'Set Bebé Premium', 1, 899900, 899900, 'assets/imagenes/bebe/Frente Set Bebe.png'),
(3, 3, 'Vestido de Niña Elegante', 3, 899900, 2699700, 'assets/imagenes/ninas/Frente Conjunto Infantil.png'),
(4, 1, 'Conjunto Deportivo', 2, 899900, 1799800, 'img/conjunto-deportivo-1.jpg');

INSERT INTO historial_entregas (pedido_id, repartidor_id, ganancia, calificacion_cliente, comentario_cliente, tiempo_entrega_minutos) VALUES
(4, 4, 7000, 5, 'Excelente servicio, muy puntual', 45);

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, pedido_id, leida) VALUES
(2, 'nuevo_pedido', 'Nuevo pedido disponible', 'ORD-2024-0001 disponible en zona CENTRO', 1, FALSE),
(3, 'nuevo_pedido', 'Nuevo pedido disponible', 'ORD-2024-0003 disponible en zona SUR', 3, FALSE),
(4, 'entrega', 'Tu pedido está en camino', 'El repartidor ha aceptado tu pedido ORD-2024-0002', 2, TRUE);

INSERT INTO favoritos (usuario_id, producto_id) VALUES
(5, 1), (5, 4), (5, 5),
(6, 2), (6, 3),
(7, 1), (7, 3);

INSERT INTO cupones (codigo, descripcion, tipo, valor, valor_minimo_compra, fecha_inicio, fecha_fin, usos_maximos, activo) VALUES
('ANGELOW10', '10% de descuento en tu primera compra', 'porcentaje', 10, 50000, '2024-01-01', '2024-12-31', 1000, TRUE),
('ENVIOGRATIS', 'Envío gratis en tu pedido', 'envio_gratis', 0, 150000, '2024-01-01', '2024-12-31', NULL, TRUE),
('VERANO25', '25% de descuento en ropa de verano', 'porcentaje', 25, 100000, '2024-06-01', '2024-08-31', 500, TRUE);

-- =============================================
-- VERIFICACIÓN FINAL
-- =============================================

SELECT '=============================================' as separador;
SELECT 'BASE DE DATOS ANGELOW CREADA EXITOSAMENTE' as mensaje;
SELECT 'Versión: 3.1 - CORREGIDA' as version;
SELECT '=============================================' as separador;

SELECT 
    (SELECT COUNT(*) FROM usuarios) as total_usuarios,
    (SELECT COUNT(*) FROM usuarios WHERE rol = 'repartidor') as total_repartidores,
    (SELECT COUNT(*) FROM productos) as total_productos,
    (SELECT COUNT(*) FROM pedidos) as total_pedidos,
    (SELECT COUNT(*) FROM pedidos WHERE estado = 'listo' AND repartidor_id IS NULL) as pedidos_disponibles,
    (SELECT COUNT(*) FROM notificaciones WHERE leida = FALSE) as notificaciones_pendientes;

SELECT 
    u.id,
    u.nombre,
    u.tipo_vehiculo,
    u.placa_vehiculo,
    u.en_linea,
    (SELECT COUNT(*) FROM pedidos p WHERE p.repartidor_id = u.id AND p.estado IN ('en_camino', 'listo')) as pedidos_activos
FROM usuarios u
WHERE u.rol = 'repartidor'
ORDER BY u.en_linea DESC;

SELECT 
    p.numero_pedido,
    p.zona,
    p.prioridad,
    p.ganancias_repartidor,
    p.direccion_envio,
    p.nombre_cliente,
    (SELECT SUM(cantidad) FROM detalles_pedido dp WHERE dp.pedido_id = p.id) as total_productos
FROM pedidos p
WHERE p.estado = 'listo'
AND p.repartidor_id IS NULL
ORDER BY 
    CASE p.prioridad
        WHEN 'urgente' THEN 1
        WHEN 'alta' THEN 2
        ELSE 3
    END;

SELECT '=============================================' as separador;
SELECT 'BASE DE DATOS LISTA PARA USAR' as estado_final;
SELECT '=============================================' as separador;