ALTER TABLE ref_tribunaux DROP INDEX uq_ref_tribunaux;
ALTER TABLE ref_tribunaux ADD COLUMN tribunal_type VARCHAR(60) DEFAULT NULL AFTER tribunal;
ALTER TABLE ref_tribunaux ADD UNIQUE KEY uq_ref_tribunaux (tribunal, tribunal_type);
UPDATE ref_tribunaux SET tribunal_type = 'Tribunal de commerce' WHERE tribunal_type IS NULL;
