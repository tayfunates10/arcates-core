CREATE TABLE reservation_units (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 unit_type ENUM('room','table','session') NOT NULL,
 capacity INT UNSIGNED NOT NULL DEFAULT 1,
 base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL DEFAULT 'TRY',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE seasonal_prices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 unit_id BIGINT UNSIGNED NOT NULL,
 starts_on DATE NOT NULL,
 ends_on DATE NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_season_unit_dates (unit_id, starts_on, ends_on),
 CONSTRAINT fk_season_unit FOREIGN KEY (unit_id) REFERENCES reservation_units(id) ON DELETE CASCADE,
 CHECK (ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE reservations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 public_code VARCHAR(32) NOT NULL UNIQUE,
 unit_id BIGINT UNSIGNED NOT NULL,
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 guest_name VARCHAR(190) NOT NULL,
 guest_email VARCHAR(190) NOT NULL,
 guest_phone VARCHAR(50) NULL,
 guests INT UNSIGNED NOT NULL DEFAULT 1,
 status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
 total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL DEFAULT 'TRY',
 notes TEXT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_reservation_overlap (unit_id, status, starts_at, ends_at),
 INDEX idx_reservation_public (public_code),
 CONSTRAINT fk_reservation_unit FOREIGN KEY (unit_id) REFERENCES reservation_units(id) ON DELETE RESTRICT,
 CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
