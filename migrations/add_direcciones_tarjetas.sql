-- =====================================================
-- MIGRACIÓN: Tablas direcciones y tarjetas_credito
-- ANGELOW - Panel de Perfil del Cliente
-- =====================================================

-- Tabla de direcciones guardadas por el usuario
CREATE TABLE IF NOT EXISTS direcciones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    titulo          VARCHAR(50) DEFAULT 'Casa',
    pais            VARCHAR(50) DEFAULT 'Colombia',
    departamento    VARCHAR(100) NOT NULL,
    municipio       VARCHAR(100) NOT NULL,
    calle           VARCHAR(255) NOT NULL,
    info_adicional  VARCHAR(255) DEFAULT NULL,
    barrio          VARCHAR(100) NOT NULL,
    destinatario    VARCHAR(200) NOT NULL,
    codigo_postal   VARCHAR(10) DEFAULT '05001',
    es_predeterminada TINYINT(1) DEFAULT 0,
    fecha_creacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_direcciones_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tarjetas de crédito guardadas por el usuario
-- IMPORTANTE: Solo se guarda la tokenización/máscara, NUNCA el número completo
CREATE TABLE IF NOT EXISTS tarjetas_credito (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT NOT NULL,
    alias               VARCHAR(50) DEFAULT 'Mi tarjeta',
    numero_enmascarado  VARCHAR(30) NOT NULL,  -- Últimos 4 dígitos: **** **** **** 1234
    titular             VARCHAR(200) NOT NULL,
    mes_expiracion      TINYINT NOT NULL,
    anio_expiracion     SMALLINT NOT NULL,
    tipo_tarjeta        ENUM('visa','mastercard','amex','discover','otra') DEFAULT 'visa',
    pais                VARCHAR(50) DEFAULT 'Colombia',
    departamento        VARCHAR(100) NOT NULL,
    municipio           VARCHAR(100) NOT NULL,
    codigo_postal       VARCHAR(10) NOT NULL,
    calle               VARCHAR(255) NOT NULL,
    barrio              VARCHAR(100) NOT NULL,
    es_predeterminada   TINYINT(1) DEFAULT 0,
    activa              TINYINT(1) DEFAULT 1,
    fecha_creacion      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_tarjetas_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
