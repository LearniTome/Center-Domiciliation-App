INSERT IGNORE INTO permissions (nom, permission_key, category, description) VALUES
('Suivi administratif des cessions', 'cessions.suivi', 'cessions', 'Consulter et gerer le suivi administratif des cessions');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key = 'cessions.suivi';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key = 'cessions.suivi';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key = 'cessions.suivi';
