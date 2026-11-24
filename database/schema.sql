-- ============================================================
-- Librería Stock — Esquema MariaDB 10.4+
-- Ejecutar con: php bin/migrate.php
-- ============================================================
CREATE DATABASE IF NOT EXISTS libreria_stock
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE libreria_stock;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1. Usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(120) NOT NULL,
  usuario         VARCHAR(60)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('admin','encargado','vendedor','consulta')
                    NOT NULL DEFAULT 'vendedor',
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  ultimo_acceso   DATETIME     NULL,
  creado_en       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY idx_usuarios_rol_activo (rol, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Tipos / categorías de artículo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tipos_articulo (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(100) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  activo       TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tipos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Proveedores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS proveedores (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(150) NOT NULL,
  telefono        VARCHAR(30)  NULL,
  contacto        VARCHAR(120) NULL,
  notas           TEXT         NULL,
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proveedores_nombre (nombre),
  KEY idx_proveedores_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Artículos
--    codigo = SKU interno o código de barras (único)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articulos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo          VARCHAR(64)  NOT NULL,
  nombre          VARCHAR(200) NOT NULL,
  tipo_id         INT UNSIGNED NOT NULL,
  proveedor_id    INT UNSIGNED NULL,
  precio_costo    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  precio_venta    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock_actual    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock_minimo    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  unidad_medida   VARCHAR(20)  NOT NULL DEFAULT 'unidad',
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_articulos_codigo (codigo),
  KEY idx_articulos_tipo (tipo_id),
  KEY idx_articulos_proveedor (proveedor_id),
  KEY idx_articulos_nombre (nombre),
  KEY idx_articulos_activo (activo),
  CONSTRAINT fk_articulos_tipo
    FOREIGN KEY (tipo_id) REFERENCES tipos_articulo(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_articulos_proveedor
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_articulos_precios
    CHECK (precio_costo >= 0 AND precio_venta >= 0),
  CONSTRAINT chk_articulos_stock
    CHECK (stock_actual >= 0 AND stock_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Movimientos de stock (libro mayor, solo INSERT)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movimientos_stock (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  articulo_id             INT UNSIGNED NOT NULL,
  usuario_id              INT UNSIGNED NOT NULL,
  tipo_movimiento         ENUM('entrada','salida','ajuste') NOT NULL,
  cantidad                DECIMAL(12,2) NOT NULL,
  precio_unitario_momento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  motivo                  VARCHAR(255) NULL,
  referencia              VARCHAR(64)  NULL,
  stock_resultante        DECIMAL(12,2) NOT NULL,
  fecha_hora              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mov_articulo_fecha (articulo_id, fecha_hora, id),
  KEY idx_mov_usuario_fecha (usuario_id, fecha_hora, id),
  KEY idx_mov_tipo_fecha (tipo_movimiento, fecha_hora),
  KEY idx_mov_referencia (referencia),
  CONSTRAINT fk_mov_articulo
    FOREIGN KEY (articulo_id) REFERENCES articulos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mov_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_mov_cantidad CHECK (cantidad <> 0),
  CONSTRAINT chk_mov_signo CHECK (
       (tipo_movimiento = 'entrada' AND cantidad > 0)
    OR (tipo_movimiento = 'salida'  AND cantidad < 0)
    OR (tipo_movimiento = 'ajuste'  AND cantidad <> 0)
  ),
  CONSTRAINT chk_mov_precio CHECK (precio_unitario_momento >= 0),
  CONSTRAINT chk_mov_stock_resultante CHECK (stock_resultante >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Vistas de apoyo
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW v_articulos_alerta AS
SELECT a.id, a.codigo, a.nombre,
       t.nombre AS tipo,
       a.stock_actual, a.stock_minimo,
       GREATEST(a.stock_minimo - a.stock_actual, 0) AS faltante
FROM articulos a
JOIN tipos_articulo t ON t.id = a.tipo_id
WHERE a.activo = 1
  AND (a.stock_actual = 0 OR a.stock_actual <= a.stock_minimo);

CREATE OR REPLACE VIEW v_kardex AS
SELECT m.id, m.fecha_hora,
       a.codigo, a.nombre AS articulo,
       m.tipo_movimiento, m.cantidad, m.stock_resultante,
       m.precio_unitario_momento,
       (m.cantidad * m.precio_unitario_momento) AS importe,
       m.motivo, m.referencia,
       u.usuario AS usuario
FROM movimientos_stock m
JOIN articulos a ON a.id = m.articulo_id
JOIN usuarios  u ON u.id = m.usuario_id;
