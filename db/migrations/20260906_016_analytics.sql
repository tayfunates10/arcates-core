CREATE TABLE IF NOT EXISTS analytics_daily (
 day DATE NOT NULL,
 path VARCHAR(255) NOT NULL,
 referrer_host VARCHAR(190) NOT NULL DEFAULT '',
 pageviews BIGINT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY (day,path,referrer_host),
 INDEX idx_analytics_day_views (day,pageviews),
 INDEX idx_analytics_referrer (day,referrer_host)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
