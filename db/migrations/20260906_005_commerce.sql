CREATE TABLE IF NOT EXISTS products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 locale VARCHAR(5) NOT NULL DEFAULT 'tr',
 name VARCHAR(190) NOT NULL,
 slug VARCHAR(190) NOT NULL,
 description MEDIUMTEXT NOT NULL,
 image_path VARCHAR(255) NULL,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_products_locale_slug (locale, slug),
 INDEX idx_products_publish (status, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 sku VARCHAR(120) NOT NULL,
 name VARCHAR(190) NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_variants_sku (sku),
 INDEX idx_variants_product (product_id, is_active),
 CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipping_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 min_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 max_total DECIMAL(12,2) NULL,
 fee DECIMAL(12,2) NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX idx_shipping_rules (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(80) NOT NULL,
 type ENUM('percent','fixed') NOT NULL,
 value DECIMAL(12,2) NOT NULL,
 min_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 starts_at DATETIME NULL,
 ends_at DATETIME NULL,
 usage_limit INT UNSIGNED NULL,
 usage_count INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 public_code CHAR(36) NOT NULL,
 customer_name VARCHAR(190) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(50) NOT NULL,
 address TEXT NOT NULL,
 city VARCHAR(120) NOT NULL,
 postal_code VARCHAR(30) NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 grand_total DECIMAL(12,2) NOT NULL,
 coupon_code VARCHAR(80) NULL,
 payment_provider VARCHAR(50) NULL,
 payment_reference VARCHAR(190) NULL,
 payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
 status ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
 stock_released TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_orders_public_code (public_code),
 INDEX idx_orders_status (status, created_at),
 INDEX idx_orders_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 variant_id BIGINT UNSIGNED NULL,
 sku VARCHAR(120) NOT NULL,
 name VARCHAR(190) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 line_total DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 provider VARCHAR(50) NOT NULL,
 provider_token VARCHAR(255) NULL,
 status ENUM('initialized','success','failed') NOT NULL DEFAULT 'initialized',
 error_code VARCHAR(120) NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_payment_order (order_id, created_at),
 CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS b2b_accounts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_name VARCHAR(190) NOT NULL,
 email VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_b2b_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
