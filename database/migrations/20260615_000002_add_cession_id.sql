ALTER TABLE documents_generes ADD COLUMN cession_id INT DEFAULT NULL AFTER societe_id;
CREATE INDEX idx_documents_generes_cession_id ON documents_generes(cession_id);
