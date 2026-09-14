CREATE DATABASE IF NOT EXISTS kion_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kion_db;

-- Tabla de Productos
CREATE TABLE IF NOT EXISTS productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(50) UNIQUE NULL,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Ventas / Cobros
CREATE TABLE IF NOT EXISTS ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(150) DEFAULT 'Cliente General',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    metodo_pago ENUM('EFECTIVO', 'TARJETA') DEFAULT 'EFECTIVO',
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Detalle de Ventas
CREATE TABLE IF NOT EXISTS detalle_ventas (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Datos de prueba para escanear/probar en el POS
INSERT INTO productos (codigo_barras, nombre, precio, stock) VALUES
('750100000001', 'Producto Demo 1', 25.50, 50),
('750100000002', 'Producto Demo 2', 100.00, 20),
('750100000003', 'Servicio Técnico Base', 150.00, 100);