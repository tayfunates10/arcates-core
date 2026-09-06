CREATE TABLE IF NOT EXISTS newsletter_subscribers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 status ENUM('pending','active','unsubscribed') NOT NULL DEFAULT 'pending',
 confirm_token_hash CHAR(64) NULL,
 consent_at DATETIME NOT NULL,
 confirmed_at DATETIME NULL,
 unsubscribed_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_newsletter_email (email),
 INDEX idx_newsletter_status (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_campaigns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 subject VARCHAR(190) NOT NULL,
 body_text MEDIUMTEXT NOT NULL,
 status ENUM('draft','queued','sending','sent') NOT NULL DEFAULT 'draft',
 queued_at DATETIME NULL,
 sent_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_newsletter_campaign_status (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_deliveries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 campaign_id BIGINT UNSIGNED NOT NULL,
 subscriber_id BIGINT UNSIGNED NOT NULL,
 status ENUM('pending','sending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
 claim_token CHAR(32) NULL,
 claimed_at DATETIME NULL,
 attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 last_error VARCHAR(500) NULL,
 sent_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_newsletter_delivery (campaign_id,subscriber_id),
 INDEX idx_newsletter_delivery_queue (status,attempts,id),
 CONSTRAINT fk_newsletter_delivery_campaign FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
 CONSTRAINT fk_newsletter_delivery_subscriber FOREIGN KEY (subscriber_id) REFERENCES newsletter_subscribers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
