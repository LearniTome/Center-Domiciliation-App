-- Ajout des permissions d'import Excel pour chaque module
INSERT IGNORE INTO permissions (permission_key, category, description)
VALUES
    ('societes.import', 'societes', 'Importer la liste des societes depuis Excel'),
    ('associes.import', 'associes', 'Importer la liste des associés depuis Excel'),
    ('contrats.import', 'contrats', 'Importer la liste des contrats depuis Excel'),
    ('collaborateurs.import', 'collaborateurs', 'Importer la liste des collaborateurs depuis Excel'),
    ('cessions.import', 'cessions', 'Importer la liste des cessions depuis Excel');

-- Assigner les nouvelles permissions au Super Admin (role_id=1) et Admin (role_id=2)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE permission_key LIKE '%.import';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key LIKE '%.import';
