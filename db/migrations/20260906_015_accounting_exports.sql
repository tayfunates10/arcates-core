CREATE TABLE IF NOT EXISTS accounting_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 provider ENUM('logo','mikro','parasut') NOT NULL,
 template_json MEDIUMTEXT NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_accounting_profile (provider,name),
 INDEX idx_accounting_profile_active (provider,is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounting_exports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 profile_id BIGINT UNSIGNED NOT NULL,
 provider ENUM('logo','mikro','parasut') NOT NULL,
 payload_json MEDIUMTEXT NOT NULL,
 payload_sha256 CHAR(64) NOT NULL,
 external_id VARCHAR(255) NULL,
 response_sha256 CHAR(64) NULL,
 status ENUM('prepared','sending','sent','send_unknown','failed') NOT NULL DEFAULT 'prepared',
 claim_token CHAR(32) NULL,
 claimed_at DATETIME NULL,
 last_error VARCHAR(1000) NULL,
 sent_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_accounting_export (order_id,profile_id),
 INDEX idx_accounting_export_status (provider,status,created_at),
 CONSTRAINT fk_accounting_export_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
 CONSTRAINT fk_accounting_export_profile FOREIGN KEY (profile_id) REFERENCES accounting_profiles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
