ALTER TABLE documents_generes ADD COLUMN cession_id INT DEFAULT NULL AFTER societe_id, ADD INDEX idx_documents_generes_cession_id (cession_id);
