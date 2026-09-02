-- =============================================
-- MIGRACION: Tabla de Facturas
-- Angelow - Modulo de Facturacion
-- =============================================

-- Agregar campo control de factura generada en pedidos
ALTER TABLE pedidos ADD COLUMN factura_generada TINYINT(1) DEFAULT 0 AFTER prioridad;

-- Tabla principal de facturas
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50) UNIQUE NOT NULL,
    pedido_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nombre_cliente VARCHAR(200) NOT NULL,
    email_cliente VARCHAR(255) NOT NULL,
    telefono_cliente VARCHAR(20),
    cedula_cliente VARCHAR(50),
    direccion_envio TEXT,
    barrio VARCHAR(100),
    ciudad VARCHAR(100),
    departamento VARCHAR(100),
    destinatario VARCHAR(200),
    metodo_envio VARCHAR(50),
    costo_envio DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50),
    estado ENUM('pendiente','autorizada','cancelada','devolucion') DEFAULT 'pendiente',
    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_estado TIMESTAMP NULL,
    notas TEXT,
    enviado_correo TINYINT(1) DEFAULT 0,
    fecha_envio_correo TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_numero_factura (numero_factura),
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_emision (fecha_emision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de detalle de productos por factura
CREATE TABLE IF NOT EXISTS facturas_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    producto_id INT,
    nombre_producto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    talla VARCHAR(20),
    color VARCHAR(50),
    imagen_url TEXT,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    INDEX idx_factura_id (factura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de estados de factura
CREATE TABLE IF NOT EXISTS facturas_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    estado_anterior ENUM('pendiente','autorizada','cancelada','devolucion'),
    estado_nuevo ENUM('pendiente','autorizada','cancelada','devolucion') NOT NULL,
    cambiado_por INT,
    notas TEXT,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (cambiado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_factura_id (factura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
