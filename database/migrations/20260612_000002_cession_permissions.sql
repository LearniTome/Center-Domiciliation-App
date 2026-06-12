-- Assigner les permissions cession aux rôles internes
-- Pattern: mimique les permissions societes

-- Admin (role_id=2) : tout
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key IN ('cessions.view','cessions.create','cessions.edit','cessions.delete','cessions.export');

-- Chef d'équipes (role_id=3) : tout
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN ('cessions.view','cessions.create','cessions.edit','cessions.delete','cessions.export');

-- Employé (role_id=4) : view + create + edit
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key IN ('cessions.view','cessions.create','cessions.edit');

-- Assistante (role_id=5) : view + create
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE permission_key IN ('cessions.view','cessions.create');

-- Stagiaire (role_id=6) : view seulement
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions WHERE permission_key IN ('cessions.view');
