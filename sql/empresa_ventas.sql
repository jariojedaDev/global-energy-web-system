-- ============================================
-- empresa_ventas.sql
-- Base de datos del sistema de ventas
-- Global Energy B.C.
-- Electrodomesticos: Lavadora, Refrigerador,
-- Aire acondicionado
-- ============================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS empresa_ventas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE empresa_ventas;

-- ============================================
-- Tabla: venta
-- Registra cada venta realizada al cliente.
-- num_solicitud: folio interno de la solicitud.
-- El campo producto usa ENUM para restringir
-- los valores permitidos.
-- ============================================
CREATE TABLE IF NOT EXISTS venta (
    id_venta        INT          AUTO_INCREMENT PRIMARY KEY,
    num_solicitud   VARCHAR(60)  NOT NULL,
    nombre_cliente  VARCHAR(120) NOT NULL,
    telefono        VARCHAR(20)  NOT NULL,
    producto        ENUM('Lavadora', 'Refrigerador', 'Aire acondicionado') NOT NULL,
    fecha_registro  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: pdf
-- Almacena los archivos PDF asociados a cada
-- venta. No se guarda el archivo en la BD,
-- solo la ruta fisica en el servidor.
-- tipo_pdf usa ENUM con los 5 documentos fijos:
--   Poliza, Factura, Contrato, Presupuesto, Tramite
-- ============================================
CREATE TABLE IF NOT EXISTS pdf (
    id_pdf          INT           AUTO_INCREMENT PRIMARY KEY,
    nombre_pdf      VARCHAR(255)  NOT NULL,          -- Nombre original del archivo
    ruta_archivo    VARCHAR(400)  NOT NULL,          -- Ruta relativa: pdfs/archivo.pdf
    tipo_pdf        ENUM('Poliza','Factura','Contrato','Presupuesto','Tramite') NOT NULL,
    id_venta        INT           NOT NULL,
    CONSTRAINT fk_pdf_venta
        FOREIGN KEY (id_venta)
        REFERENCES venta (id_venta)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Datos de ejemplo para pruebas
-- ============================================
INSERT INTO venta (num_solicitud, nombre_cliente, telefono, producto) VALUES
('GE-2024-001', 'Maria Garcia Lopez',  '664-123-4567', 'Refrigerador'),
('GE-2024-002', 'Carlos Rodriguez',    '646-987-6543', 'Lavadora'),
('GE-2024-003', 'Ana Martinez Cruz',   '664-555-0001', 'Aire acondicionado'),
('GE-2024-004', 'Roberto Sanchez',     '646-555-0002', 'Refrigerador'),
('GE-2024-005', 'Lucia Flores Vega',   '664-555-0003', 'Lavadora');
