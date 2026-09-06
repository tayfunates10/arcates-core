CREATE TABLE IF NOT EXISTS branches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 locale VARCHAR(5) NOT NULL DEFAULT 'tr',
 name VARCHAR(190) NOT NULL,
 slug VARCHAR(190) NOT NULL,
 phone VARCHAR(50) NULL,
 email VARCHAR(190) NULL,
 address VARCHAR(500) NOT NULL,
 city VARCHAR(120) NOT NULL,
 district VARCHAR(120) NULL,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 opening_hours VARCHAR(500) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_branches_locale_slug (locale,slug),
 INDEX idx_branches_public (locale,is_active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS branch_services (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 branch_id BIGINT UNSIGNED NOT NULL,
 service_name VARCHAR(190) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX idx_branch_services (branch_id,sort_order),
 CONSTRAINT fk_branch_service_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
