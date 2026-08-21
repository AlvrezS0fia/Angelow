-- Migration 002: Repartidor Schema Update
-- Adds missing columns for full repartidor functionality

-- ============================================================
-- 1. solicitudes_repartidores: Add document detail columns
-- ============================================================
ALTER TABLE solicitudes_repartidores
    ADD COLUMN IF NOT EXISTS tipo_documento VARCHAR(20) NULL AFTER telefono,
    ADD COLUMN IF NOT EXISTS numero_documento VARCHAR(50) NULL AFTER tipo_documento,
    ADD COLUMN IF NOT EXISTS numero_licencia VARCHAR(50) NULL AFTER placa_vehiculo,
    ADD COLUMN IF NOT EXISTS categoria_licencia VARCHAR(10) NULL AFTER numero_licencia,
    ADD COLUMN IF NOT EXISTS numero_tarjeta VARCHAR(50) NULL AFTER categoria_licencia,
    ADD COLUMN IF NOT EXISTS direccion TEXT NULL AFTER numero_tarjeta,
    ADD COLUMN IF NOT EXISTS ciudad VARCHAR(100) NULL AFTER direccion,
    ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL AFTER ciudad;

-- ============================================================
-- 2. documentos: Add expiration tracking columns
-- ============================================================
ALTER TABLE documentos
    ADD COLUMN IF NOT EXISTS numero_documento VARCHAR(100) NULL AFTER archivo_url,
    ADD COLUMN IF NOT EXISTS fecha_expedicion DATE NULL AFTER numero_documento,
    ADD COLUMN IF NOT EXISTS fecha_vencimiento DATE NULL AFTER fecha_expedicion,
    ADD COLUMN IF NOT EXISTS revisado_por INT NULL AFTER observaciones,
    ADD COLUMN IF NOT EXISTS fecha_revision TIMESTAMP NULL AFTER revisado_por;

-- ============================================================
-- 3. usuarios: Add missing profile fields
-- ============================================================
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL AFTER ciudad,
    ADD COLUMN IF NOT EXISTS tipo_documento VARCHAR(20) NULL AFTER fecha_nacimiento,
    ADD COLUMN IF NOT EXISTS numero_licencia VARCHAR(50) NULL AFTER tipo_vehiculo,
    ADD COLUMN IF NOT EXISTS categoria_licencia VARCHAR(10) NULL AFTER numero_licencia;

-- ============================================================
-- 4. notificaciones: Add repartidor_id for driver notifications
-- ============================================================
-- Check if notificaciones table exists and add repartidor support if needed
-- The table already has usuario_id which can serve this purpose

-- ============================================================
-- 5. Create audit_log table for admin actions on repartidores
-- ============================================================
CREATE TABLE IF NOT EXISTS auditoria_repartidores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repartidor_id INT NOT NULL,
    administrador_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    motivo TEXT NULL,
    observaciones TEXT NULL,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (administrador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_admin (administrador_id),
    INDEX idx_fecha (fecha_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
