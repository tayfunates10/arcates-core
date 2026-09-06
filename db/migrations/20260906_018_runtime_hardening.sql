CREATE TABLE IF NOT EXISTS rate_limit_buckets (
    bucket_key VARCHAR(190) NOT NULL PRIMARY KEY,
    window_started_at DATETIME NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    INDEX idx_rate_limit_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
