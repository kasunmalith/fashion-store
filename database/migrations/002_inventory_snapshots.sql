ALTER TABLE order_items ADD COLUMN stock_tracked BOOLEAN NOT NULL DEFAULT 1;
ALTER TABLE orders ADD COLUMN refund_state VARCHAR(20) NOT NULL DEFAULT 'none';
CREATE INDEX idx_attribute_filter ON product_attribute_values(attribute_id,value(100));
CREATE INDEX idx_variant_option_filter ON variant_attribute_values(attribute_id,option_id);
