ALTER TABLE societes ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER societe_telephone;
ALTER TABLE societes ADD INDEX idx_created_by (created_by);
