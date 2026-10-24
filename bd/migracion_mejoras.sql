-- Migración EduMarket: admin, categorías amplias, colegios únicos
-- Si alguna sentencia falla porque el cambio ya existe, continúa con las demás.

USE edumarket;

-- 1) Rol administrador (omitir si la columna ya existe)
ALTER TABLE usuario ADD COLUMN es_admin TINYINT(1) NOT NULL DEFAULT 0;

UPDATE usuario SET es_admin = 1 WHERE email = 'admin@demo.com';

-- 2) Limpiar colegios duplicados (conserva el de menor id)
UPDATE articulo a
INNER JOIN institucion i1 ON a.id_institucion = i1.id_institucion
INNER JOIN institucion i2 ON LOWER(i1.nombre) = LOWER(i2.nombre) AND i1.id_institucion > i2.id_institucion
SET a.id_institucion = i2.id_institucion;

DELETE i1 FROM institucion i1
INNER JOIN institucion i2
  ON LOWER(i1.nombre) = LOWER(i2.nombre) AND i1.id_institucion > i2.id_institucion;

-- 3) Índice único de colegio (omitir si ya existe)
ALTER TABLE institucion ADD UNIQUE KEY uq_institucion_nombre (nombre);

-- 4) Índice único de categoría (omitir si ya existe)
ALTER TABLE categoria ADD UNIQUE KEY uq_categoria_nombre (nombre);

-- 5) Categorías nuevas
INSERT IGNORE INTO categoria (nombre) VALUES
('Cuadernos'),
('Calzado'),
('Calculadoras'),
('Deportes'),
('Arte y manualidades'),
('Tecnología'),
('Instrumentos musicales'),
('Accesorios'),
('Otros');
