-- EduMarket - Marketplace escolar de reutilización
-- Base de datos: edumarket

CREATE DATABASE IF NOT EXISTS edumarket
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE edumarket;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS articulo;
DROP TABLE IF EXISTS categoria;
DROP TABLE IF EXISTS institucion;
DROP TABLE IF EXISTS usuario;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuario (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  telefono VARCHAR(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categoria (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE institucion (
  id_institucion INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articulo (
  id_articulo INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tipo ENUM('venta','intercambio') NOT NULL DEFAULT 'venta',
  estado ENUM('disponible','entregado') NOT NULL DEFAULT 'disponible',
  telefono_contacto VARCHAR(20) NOT NULL,
  foto_url VARCHAR(255) DEFAULT NULL,
  id_categoria INT NOT NULL,
  id_institucion INT NOT NULL,
  id_usuario INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_articulo_categoria FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria),
  CONSTRAINT fk_articulo_institucion FOREIGN KEY (id_institucion) REFERENCES institucion(id_institucion),
  CONSTRAINT fk_articulo_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios demo (todos: Admin123!)
-- admin@demo.com / Admin123!
-- maria@demo.com / Admin123!
-- carlos@demo.com / Admin123!
INSERT INTO usuario (email, nombre, password_hash, telefono) VALUES
('admin@demo.com', 'Admin EduMarket', '$2y$10$EAW37Su9xX/ARqasc0NJBOp1MDm0o5ZJ.Y46MLlwjkRifGBat1AN.', '3001234567'),
('maria@demo.com', 'María López', '$2y$10$EAW37Su9xX/ARqasc0NJBOp1MDm0o5ZJ.Y46MLlwjkRifGBat1AN.', '3109876543'),
('carlos@demo.com', 'Carlos Ramírez', '$2y$10$EAW37Su9xX/ARqasc0NJBOp1MDm0o5ZJ.Y46MLlwjkRifGBat1AN.', '3205558899');

INSERT INTO categoria (nombre) VALUES
('Libros'),
('Uniformes'),
('Maletas'),
('Cartucheras'),
('Útiles');

INSERT INTO institucion (nombre) VALUES
('Liceo Gran Virrey'),
('Gimnasio Cáceres'),
('Colegio San José'),
('Instituto Técnico Norte'),
('Colegio Santa María'),
('Colegio Boston'),
('Escuela Normal Superior');

INSERT INTO articulo (titulo, descripcion, precio, tipo, estado, telefono_contacto, foto_url, id_categoria, id_institucion, id_usuario) VALUES
('Libro de Matemáticas 10°', 'Libro guía grado décimo, pocas marcas a lápiz. Ideal para empezar el año sin comprar nuevo.', 25000.00, 'venta', 'disponible', '3001234567', 'img/libros-1.jpg', 1, 1, 1),
('Novela El Principito', 'Edición ilustrada en español, tapa blanda. Busco intercambio por otro libro de lectura escolar.', 0.00, 'intercambio', 'disponible', '3109876543', 'img/libros-2.jpg', 1, 4, 2),
('Diccionario Larousse escolar', 'Diccionario español completo, casi nuevo. Perfecto para secundaria.', 18000.00, 'venta', 'disponible', '3205558899', 'img/libros-3.jpg', 1, 3, 3),
('Uniforme completo Liceo', 'Camisa y pantalón talla M, poco uso. Incluye escudo bordado. Lavado y listo.', 45000.00, 'venta', 'disponible', '3001234567', 'img/uniforme-1.jpg', 2, 1, 1),
('Sudadera institucional gris', 'Sudadera talla L del Gimnasio Cáceres. Color gris, sin manchas.', 35000.00, 'venta', 'disponible', '3001234567', 'img/sudadera-1.jpg', 2, 2, 1),
('Camisa colegio blanca talla S', 'Camisa institucional talla S, dos usos. Ideal para primaria alta.', 22000.00, 'venta', 'disponible', '3109876543', 'img/uniforme-2.jpg', 2, 5, 2),
('Maleta escolar azul con ruedas', 'Maleta resistente con ruedas y 3 compartimentos. Intercambio por maleta más compacta.', 0.00, 'intercambio', 'disponible', '3109876543', 'img/maleta-1.jpg', 3, 2, 2),
('Morral liviano secundaria', 'Morral negro liviano, correas acolchadas. Buen estado general.', 28000.00, 'venta', 'disponible', '3205558899', 'img/maleta-2.jpg', 3, 6, 3),
('Maleta tipo trolley rosa', 'Trolley escolar con ruedas silenciosas. Intercambio por cartuchera o set de útiles.', 0.00, 'intercambio', 'disponible', '3109876543', 'img/maleta-3.jpg', 3, 5, 2),
('Cartuchera con útiles básicos', 'Incluye lápices HB, borradores, regla y tajalápiz. Lista para el colegio.', 12000.00, 'venta', 'disponible', '3109876543', 'img/cartuchera-2.jpg', 4, 3, 2),
('Cartuchera de jean artesanal', 'Cartuchera de jean con cierre funcional y bolsillo interno.', 10000.00, 'venta', 'disponible', '3001234567', 'img/cartuchera-1.jpg', 4, 1, 1),
('Set de geometría completo', 'Compás, transportador y escuadras en estuche rígido. Casi nuevo.', 8000.00, 'venta', 'disponible', '3001234567', 'img/geometria-1.jpg', 5, 1, 1),
('Cuadernos cuadriculados x5', 'Paquete de 5 cuadernos 100 hojas, sin usar. Marca escolar estándar.', 15000.00, 'venta', 'disponible', '3205558899', 'img/cuadernos-1.jpg', 5, 3, 3),
('Kit de colores y marcadores', 'Caja de colores + 6 marcadores. Perfectos para artes y proyectos.', 14000.00, 'venta', 'disponible', '3109876543', 'img/utiles-2.jpg', 5, 7, 2),
('Útiles mixtos para primaria', 'Regla, tijeras punta roma, pegante y lápices. Todo en buen estado.', 9000.00, 'venta', 'disponible', '3205558899', 'img/utiles-1.jpg', 5, 6, 3),
('Libro Ciencias Naturales 8°', 'Texto de ciencias 8°, subrayados leves. Entregado el año pasado (demo histórico).', 20000.00, 'venta', 'entregado', '3001234567', 'img/libros-1.jpg', 1, 1, 1);