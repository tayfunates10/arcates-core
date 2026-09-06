CREATE TABLE IF NOT EXISTS contact_submissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(50) NULL,
 message TEXT NOT NULL,
 kvkk_consent TINYINT(1) NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS gallery_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 slug VARCHAR(190) NOT NULL UNIQUE,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS gallery_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 image_path VARCHAR(255) NOT NULL,
 alt_text VARCHAR(255) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX idx_gallery_category (category_id, sort_order),
 CONSTRAINT fk_gallery_category FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS blog_posts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 locale VARCHAR(5) NOT NULL,
 title VARCHAR(190) NOT NULL,
 slug VARCHAR(190) NOT NULL,
 excerpt VARCHAR(500) NULL,
 body MEDIUMTEXT NOT NULL,
 category VARCHAR(120) NOT NULL DEFAULT '',
 tags VARCHAR(500) NOT NULL DEFAULT '',
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 meta_title VARCHAR(190) NULL,
 meta_description VARCHAR(320) NULL,
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_blog_locale_slug (locale, slug),
 INDEX idx_blog_publish (status, locale, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS testimonials (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 company VARCHAR(190) NULL,
 quote TEXT NOT NULL,
 is_published TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
