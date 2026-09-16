-- Add societe_source column to societes to distinguish creation vs modification.
-- NB : les INSERT sont volontairement AVANT l'ALTER pour garantir l'idempotence :
-- si la colonne societe_source existe deja, le ALTER leve SQLSTATE 42S21 (ignore par
-- le runner comme "deja applique") mais les INSERT idempotents ont deja ete executes.

-- Add modifications.view permission
INSERT IGNORE INTO permissions (nom, permission_key, category, description) VALUES ('Voir les modifications juridiques', 'modifications.view', 'Modifications', 'Accéder à la liste des modifications juridiques');

-- Assign to existing roles (Admin=2, Chef equipe=3, Employe=4, Assistante=5)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT r.id, p.id
  FROM roles r, permissions p
  WHERE r.id IN (2,3,4,5) AND p.permission_key = 'modifications.view';

-- Add societe_source column (idempotent : 42S21 ignore par le runner)
ALTER TABLE societes
  ADD COLUMN societe_source VARCHAR(20) DEFAULT 'creation' COMMENT 'creation|cession|augmentation_capital|transfert_siege' AFTER societe_forme_juridique;