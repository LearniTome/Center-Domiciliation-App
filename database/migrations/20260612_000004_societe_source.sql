-- Add societe_source column to societes to distinguish creation vs modification
ALTER TABLE societes
  ADD COLUMN societe_source VARCHAR(20) DEFAULT 'creation' COMMENT 'creation|cession|augmentation_capital|transfert_siege' AFTER societe_forme_juridique;

-- Add modifications.view permission
INSERT IGNORE INTO permissions (permission_key, permission_label, permission_category) VALUES ('modifications.view', 'Voir les modifications juridiques', 'Modifications');

-- Assign to existing roles (Admin=2, Chef equipe=3, Employe=4, Assistante=5)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT r.id, p.id
  FROM roles r, permissions p
  WHERE r.id IN (2,3,4,5) AND p.permission_key = 'modifications.view';
