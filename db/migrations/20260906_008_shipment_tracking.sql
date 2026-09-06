CREATE TABLE IF NOT EXISTS shipments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tracking_code VARCHAR(32) NOT NULL,
 reference_code VARCHAR(120) NULL,
 carrier VARCHAR(80) NOT NULL DEFAULT 'Arcates',
 status ENUM('created','picked_up','in_transit','out_for_delivery','delivered','cancelled','exception') NOT NULL DEFAULT 'created',
 origin VARCHAR(190) NOT NULL,
 destination VARCHAR(190) NOT NULL,
 current_location VARCHAR(190) NULL,
 estimated_delivery DATE NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_shipments_tracking (tracking_code),
 INDEX idx_shipments_status (status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipment_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 shipment_id BIGINT UNSIGNED NOT NULL,
 status ENUM('created','picked_up','in_transit','out_for_delivery','delivered','cancelled','exception') NOT NULL,
 location VARCHAR(190) NULL,
 note VARCHAR(500) NULL,
 event_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_shipment_events (shipment_id,event_at,id),
 CONSTRAINT fk_shipment_event_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
