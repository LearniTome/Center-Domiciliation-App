-- Add 20 missing CRUD permissions to match production
-- These permissions exist on production but were never migrated locally

-- 1. Insert missing permissions (IDs 39-58)
INSERT IGNORE INTO permissions (id, nom, permission_key, category, description) VALUES
(39, 'Voir les sociétés',                'societes.view',        'societes',     'Consulter la liste et les fiches sociétés'),
(40, 'Créer une société',                'societes.create',      'societes',     'Créer une nouvelle société'),
(41, 'Modifier une société',             'societes.edit',        'societes',     'Modifier les informations d une société'),
(42, 'Supprimer une société',            'societes.delete',      'societes',     'Supprimer une société'),
(43, 'Exporter les sociétés',            'societes.export',      'societes',     'Exporter la liste des sociétés en CSV'),
(44, 'Voir les associés',                'associes.view',        'associes',     'Consulter la liste des associés'),
(45, 'Créer un associé',                 'associes.create',      'associes',     'Ajouter un associé'),
(46, 'Modifier un associé',              'associes.edit',        'associes',     'Modifier les informations d un associé'),
(47, 'Supprimer un associé',             'associes.delete',      'associes',     'Supprimer un associé'),
(48, 'Exporter les associés',            'associes.export',      'associes',     'Exporter la liste des associés en CSV'),
(49, 'Voir les contrats',                'contrats.view',        'contrats',     'Consulter la liste des contrats'),
(50, 'Créer un contrat',                 'contrats.create',      'contrats',     'Ajouter un contrat'),
(51, 'Modifier un contrat',              'contrats.edit',        'contrats',     'Modifier les informations d un contrat'),
(52, 'Supprimer un contrat',             'contrats.delete',      'contrats',     'Supprimer un contrat'),
(53, 'Exporter les contrats',            'contrats.export',      'contrats',     'Exporter la liste des contrats en CSV'),
(54, 'Voir les collaborateurs',          'collaborateurs.view',       'collaborateurs', 'Consulter la liste des collaborateurs'),
(55, 'Créer un collaborateur',           'collaborateurs.create',     'collaborateurs', 'Ajouter un collaborateur'),
(56, 'Modifier un collaborateur',        'collaborateurs.edit',       'collaborateurs', 'Modifier les informations d un collaborateur'),
(57, 'Supprimer un collaborateur',       'collaborateurs.delete',     'collaborateurs', 'Supprimer un collaborateur'),
(58, 'Exporter les collaborateurs',      'collaborateurs.export',     'collaborateurs', 'Exporter la liste des collaborateurs en CSV'),
(59, 'Voir le tableau de bord',          'dashboard.view',       'dashboard',    'Accéder au tableau de bord');

-- 2. Update Admin role (id=2): add the 20 new permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE id BETWEEN 39 AND 59;

-- 3. Update Super Admin role (id=1): add the 20 new permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE id BETWEEN 39 AND 59;
