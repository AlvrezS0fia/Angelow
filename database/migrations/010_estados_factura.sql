-- Migración: Cambiar estados de pedidos a estados de factura
-- Estados requeridos: pendiente, confirmada, cambio, devolucion, rechazada
-- Fecha: 2026-09-05

-- Actualizar estados existentes al nuevo formato
UPDATE pedidos SET estado = 'pendiente' WHERE estado = 'pendiente';
UPDATE pedidos SET estado = 'confirmada' WHERE estado IN ('confirmado', 'procesando', 'listo', 'asignado', 'aceptado', 'recogido', 'en_camino', 'entregado');
UPDATE pedidos SET estado = 'rechazada' WHERE estado IN ('cancelado', 'reembolsado');

-- Cambiar el ENUM de la columna estado
ALTER TABLE pedidos MODIFY COLUMN estado ENUM(
    'pendiente',
    'confirmada',
    'cambio',
    'devolucion',
    'rechazada'
) NOT NULL DEFAULT 'pendiente';
