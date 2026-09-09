-- ============================================================
-- Migracion: 999_drop_facturacion
-- Descripcion: Elimina toda funcionalidad de facturacion de la
--              base de datos (tablas y columna relacionadas).
--
-- IMPORTANTE: Este archivo es la LIMPIEZA FINAL de la BD real.
-- Aplicalo solo cuando confirmes que ya no necesitas facturacion.
-- Ejecutar de forma manual, ya que el proyecto no cuenta con un
-- runner automatico de migraciones.
-- ============================================================

START TRANSACTION;

DROP TABLE IF EXISTS facturas_historial;
DROP TABLE IF EXISTS facturas_detalle;
DROP TABLE IF EXISTS facturas;

-- Quitar la columna de marca de factura en pedidos
ALTER TABLE pedidos DROP COLUMN IF EXISTS factura_generada;

COMMIT;