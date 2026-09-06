CREATE TABLE IF NOT EXISTS service_offers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 locale VARCHAR(5) NOT NULL DEFAULT 'tr',
 title VARCHAR(190) NOT NULL,
 slug VARCHAR(190) NOT NULL,
 summary VARCHAR(500) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_service_offer_locale_slug (locale,slug),
 INDEX idx_service_offer_public (locale,is_active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_prices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 service_id BIGINT UNSIGNED NOT NULL,
 label VARCHAR(190) NOT NULL,
 price DECIMAL(12,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL DEFAULT 'TRY',
 unit_label VARCHAR(80) NULL,
 note VARCHAR(500) NULL,
 is_featured TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX idx_service_prices (service_id,sort_order),
 CONSTRAINT fk_service_price_offer FOREIGN KEY (service_id) REFERENCES service_offers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
