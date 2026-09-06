CREATE TABLE IF NOT EXISTS carrier_shipments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 provider ENUM('mng','aras','yurtici') NOT NULL,
 reference_code VARCHAR(80) NOT NULL,
 tracking_number VARCHAR(120) NULL,
 provider_status VARCHAR(120) NULL,
 recipient_district VARCHAR(120) NOT NULL,
 package_count INT UNSIGNED NOT NULL DEFAULT 1,
 weight_kg DECIMAL(10,2) NOT NULL DEFAULT 1,
 desi DECIMAL(10,2) NOT NULL DEFAULT 1,
 last_message VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_carrier_order_provider (order_id,provider),
 UNIQUE KEY uq_carrier_reference (provider,reference_code),
 INDEX idx_carrier_tracking (provider,tracking_number),
 CONSTRAINT fk_carrier_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
