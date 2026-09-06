ALTER TABLE orders
    ADD INDEX idx_orders_abandoned (status, payment_status, stock_released, updated_at);

ALTER TABLE payment_attempts
    ADD INDEX idx_payment_order_status (order_id, status);
