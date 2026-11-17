-- ฐานข้อมูลสำหรับ Online Product Management System
-- นำเข้าผ่าน phpMyAdmin: แท็บ Import > เลือกไฟล์นี้ > Import

CREATE DATABASE IF NOT EXISTS product_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE product_db;

DROP TABLE IF EXISTS products;

CREATE TABLE products (
  id          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)   NOT NULL,
  description TEXT           NULL,
  price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  category    VARCHAR(100)   NOT NULL,
  stock       INT UNSIGNED   NOT NULL DEFAULT 0,
  brand       VARCHAR(100)   NULL,
  thumbnail   VARCHAR(500)   NULL,
  created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_category (category),
  KEY idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ข้อมูลตัวอย่าง
INSERT INTO products (title, description, price, category, stock, brand) VALUES
('Essence Mascara Lash Princess', 'มาสคาร่าขนตางอนยาว', 9.99, 'beauty', 99, 'Essence'),
('Apple iPhone 15', 'สมาร์ทโฟนรุ่นใหม่', 799.00, 'smartphones', 25, 'Apple'),
('Wireless Mouse', 'เมาส์ไร้สาย 2.4GHz', 12.50, 'accessories', 120, 'Logitech'),
('Office Chair', 'เก้าอี้สำนักงานรองรับสรีระ', 89.90, 'furniture', 15, 'Ergo');
