CREATE TABLE IF NOT EXISTS qr_menus (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 slug VARCHAR(190) NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uq_qr_menus_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_menu_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 menu_id BIGINT UNSIGNED NOT NULL,
 locale VARCHAR(5) NOT NULL,
 name VARCHAR(190) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX idx_qr_categories (menu_id,locale,sort_order),
 CONSTRAINT fk_qr_category_menu FOREIGN KEY (menu_id) REFERENCES qr_menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_menu_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(190) NOT NULL,
 description VARCHAR(500) NULL,
 price DECIMAL(12,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL DEFAULT 'TRY',
 image_path VARCHAR(255) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX idx_qr_items (category_id,is_active,sort_order),
 CONSTRAINT fk_qr_item_category FOREIGN KEY (category_id) REFERENCES qr_menu_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
