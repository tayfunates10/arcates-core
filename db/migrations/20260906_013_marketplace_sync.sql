CREATE TABLE IF NOT EXISTS marketplace_mappings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 variant_id BIGINT UNSIGNED NOT NULL,
 provider ENUM('trendyol','hepsiburada') NOT NULL,
 external_sku VARCHAR(190) NOT NULL,
 external_product_id VARCHAR(190) NULL,
 barcode VARCHAR(190) NULL,
 price_multiplier DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
 stock_reserve INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 last_payload_hash CHAR(64) NULL,
 pending_payload_hash CHAR(64) NULL,
 pending_batch_id BIGINT UNSIGNED NULL,
 last_status VARCHAR(40) NULL,
 last_error VARCHAR(500) NULL,
 last_synced_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_marketplace_mapping (variant_id,provider),
 INDEX idx_marketplace_provider (provider,is_active),
 INDEX idx_marketplace_pending (provider,pending_batch_id),
 CONSTRAINT fk_marketplace_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 provider ENUM('trendyol','hepsiburada') NOT NULL,
 external_batch_id VARCHAR(255) NOT NULL,
 status ENUM('submitted','processing','success','failed') NOT NULL DEFAULT 'submitted',
 item_count INT UNSIGNED NOT NULL DEFAULT 0,
 last_error VARCHAR(1000) NULL,
 checked_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_marketplace_external_batch (provider,external_batch_id),
 INDEX idx_marketplace_batch_status (provider,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_batch_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 batch_id BIGINT UNSIGNED NOT NULL,
 mapping_id BIGINT UNSIGNED NOT NULL,
 item_index INT UNSIGNED NOT NULL,
 external_key VARCHAR(190) NOT NULL,
 payload_hash CHAR(64) NOT NULL,
 status ENUM('submitted','success','failed') NOT NULL DEFAULT 'submitted',
 error_message VARCHAR(500) NULL,
 UNIQUE KEY uq_marketplace_batch_item (batch_id,mapping_id),
 INDEX idx_marketplace_batch_item_index (batch_id,item_index),
 CONSTRAINT fk_marketplace_batch FOREIGN KEY (batch_id) REFERENCES marketplace_batches(id) ON DELETE CASCADE,
 CONSTRAINT fk_marketplace_batch_mapping FOREIGN KEY (mapping_id) REFERENCES marketplace_mappings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
