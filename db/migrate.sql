-- Migration: Add indexes for performance
USE second_hand_phones;

ALTER TABLE phones ADD INDEX idx_imei (imei);
ALTER TABLE phones ADD INDEX idx_status (status);
ALTER TABLE sales ADD INDEX idx_invoice (invoice_no);
ALTER TABLE sales ADD INDEX idx_warranty_end (warranty_end_date);
ALTER TABLE returns_refunds ADD INDEX idx_status (status);
