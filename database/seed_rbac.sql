-- Seed RBAC data
USE center_domiciliation;

-- 1. Roles (fusionnés avec collaborateur_type existants + nouveaux internes)
INSERT IGNORE INTO roles (id, nom, description, is_internal, is_system, sort_order) VALUES
(1,  'Super Admin',       'Accès total au système',                         1, 1, 1),
(2,  'Admin',             'Administrateur avec presque tous les droits',    1, 0, 2),
(3,  'Chef d équipes',    'Gère les dossiers et son équipe',               1, 0, 3),
(4,  'Employé',           'Agent de traitement des dossiers',              1, 0, 4),
(5,  'Assistante',        'Support administratif et documentaire',          1, 0, 5),
(6,  'Stagiaire',         'Accès lecture seule',                           1, 0, 6),
(7,  'Expert-comptable',  'Expert-comptable externe',                      0, 0, 10),
(8,  'Comptable agréé',   'Comptable agréé externe',                       0, 0, 11),
(9,  'Commissaire aux comptes', 'Commissaire aux comptes',                 0, 0, 12),
(10, 'Coursier',          'Coursier / livreur',                             0, 0, 13),
(11, 'Avocat',            'Avocat externe',                                 0, 0, 14),
(12, 'Notaire',           'Notaire externe',                                0, 0, 15),
(13, 'Conseil juridique', 'Conseil juridique externe',                      0, 0, 16),
(14, 'Banque',            'Représentant bancaire',                          0, 0, 17),
(15, 'Assurance',         'Représentant assurance',                         0, 0, 18),
(16, 'Autre',             'Autre type de collaborateur',                    0, 0, 99);

-- 2. Permissions
INSERT IGNORE INTO permissions (id, nom, permission_key, category, description) VALUES
-- Dashboard
(1,  'Voir le tableau de bord',                       'dashboard.view',       'dashboard',    'Accéder au tableau de bord'),

-- Sociétés
(2,  'Voir les sociétés',                             'societes.view',        'societes',     'Consulter la liste et les fiches sociétés'),
(3,  'Créer une société',                             'societes.create',      'societes',     'Créer une nouvelle société'),
(4,  'Modifier une société',                          'societes.edit',        'societes',     'Modifier les informations d une société'),
(5,  'Supprimer une société',                         'societes.delete',      'societes',     'Supprimer une société'),
(6,  'Exporter les sociétés',                         'societes.export',      'societes',     'Exporter la liste des sociétés en CSV'),

-- Associés
(7,  'Voir les associés',                             'associes.view',        'associes',     'Consulter la liste des associés'),
(8,  'Créer un associé',                              'associes.create',      'associes',     'Ajouter un associé'),
(9,  'Modifier un associé',                           'associes.edit',        'associes',     'Modifier les informations d un associé'),
(10, 'Supprimer un associé',                          'associes.delete',      'associes',     'Supprimer un associé'),
(11, 'Exporter les associés',                         'associes.export',      'associes',     'Exporter la liste des associés en CSV'),

-- Contrats
(12, 'Voir les contrats',                             'contrats.view',        'contrats',     'Consulter la liste des contrats'),
(13, 'Créer un contrat',                              'contrats.create',      'contrats',     'Ajouter un contrat'),
(14, 'Modifier un contrat',                           'contrats.edit',        'contrats',     'Modifier les informations d un contrat'),
(15, 'Supprimer un contrat',                          'contrats.delete',      'contrats',     'Supprimer un contrat'),
(16, 'Exporter les contrats',                         'contrats.export',      'contrats',     'Exporter la liste des contrats en CSV'),

-- Collaborateurs (gestion des utilisateurs)
(17, 'Voir les collaborateurs',                       'collaborateurs.view',       'collaborateurs', 'Consulter la liste des collaborateurs'),
(18, 'Créer un collaborateur',                        'collaborateurs.create',     'collaborateurs', 'Ajouter un collaborateur'),
(19, 'Modifier un collaborateur',                     'collaborateurs.edit',       'collaborateurs', 'Modifier les informations d un collaborateur'),
(20, 'Supprimer un collaborateur',                    'collaborateurs.delete',     'collaborateurs', 'Supprimer un collaborateur'),
(21, 'Exporter les collaborateurs',                   'collaborateurs.export',     'collaborateurs', 'Exporter la liste des collaborateurs en CSV'),

-- Wizard
(22, 'Utiliser l assistant de création',              'wizard.create',        'wizard',       'Accéder au wizard de création de dossier'),

-- Templates
(23, 'Voir les templates',                            'templates.view',       'templates',    'Consulter la liste des templates'),
(24, 'Créer un template',                             'templates.create',     'templates',    'Ajouter un nouveau template'),
(25, 'Modifier un template',                          'templates.edit',       'templates',    'Modifier un template existant'),
(26, 'Supprimer un template',                         'templates.delete',     'templates',    'Supprimer un template'),

-- Génération
(27, 'Utiliser le générateur de dossiers',            'generation.use',       'generation',   'Générer les documents d un dossier'),

-- Documents
(28, 'Voir les documents générés',                    'documents.view',       'documents',    'Consulter la liste des documents'),
(29, 'Télécharger les documents',                     'documents.download',   'documents',    'Télécharger les fichiers générés'),

-- Configuration
(30, 'Voir la configuration',                         'configuration.view',   'configuration','Accéder à la page de configuration'),
(31, 'Modifier la configuration',                     'configuration.edit',   'configuration','Modifier les données de configuration'),

-- Analyse de couverture
(32, 'Voir l analyse de couverture',                  'analyse.view',         'analyse',      'Accéder à l analyse de couverture'),

-- Variables
(33, 'Voir les variables',                            'variables.view',       'variables',    'Consulter la gestion des variables'),
(34, 'Modifier les variables',                        'variables.edit',       'variables',    'Renommer et supprimer des variables'),

-- Valeurs par défaut
(35, 'Modifier les valeurs par défaut',               'defaults.edit',        'defaults',     'Configurer les valeurs par défaut'),

-- Conversion Word → PDF
(36, 'Utiliser la conversion Word → PDF',             'convert.use',          'convert',      'Accéder à l outil de conversion'),

-- Assistant IA
(37, 'Utiliser l assistant IA',                       'ai.use',               'ai',           'Accéder à l assistant IA'),

-- Gestion des rôles
(38, 'Gérer les rôles et permissions',                'roles.manage',         'roles',        'Créer, modifier et supprimer des rôles');

-- 3. Role-Permission assignments
-- Helper: delete existing then insert
SET @super_admin = 1, @admin = 2, @chef = 3, @employe = 4, @assistante = 5, @stagiaire = 6,
    @expert = 7, @comptable = 8, @commissaire = 9, @coursier = 10, @avocat = 11,
    @notaire = 12, @conseil = 13, @banque = 14, @assurance = 15, @autre = 16;

DELETE FROM role_permissions;

-- Super Admin: ALL permissions (1-38)
INSERT INTO role_permissions (role_id, permission_id)
SELECT @super_admin, id FROM permissions;

-- Admin: all except roles.manage (38)
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin, id FROM permissions WHERE id < 38;

-- Chef d équipes: dashboard, full societes/associes/contrats, wizard, generation, documents, collaborateurs view, templates view
INSERT INTO role_permissions (role_id, permission_id) VALUES
-- Dashboard
(@chef, 1),
-- Societes (full)
(@chef, 2), (@chef, 3), (@chef, 4), (@chef, 5), (@chef, 6),
-- Associes (full)
(@chef, 7), (@chef, 8), (@chef, 9), (@chef, 10), (@chef, 11),
-- Contrats (full)
(@chef, 12), (@chef, 13), (@chef, 14), (@chef, 15), (@chef, 16),
-- Collaborateurs (view only)
(@chef, 17),
-- Wizard
(@chef, 22),
-- Templates (view only)
(@chef, 23),
-- Generation
(@chef, 27),
-- Documents
(@chef, 28), (@chef, 29),
-- Analyse
(@chef, 32),
-- Variables (view only)
(@chef, 33);

-- Employé: view societes/associes/contrats, create wizard, generation, documents
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@employe, 1),
(@employe, 2), (@employe, 3), (@employe, 4),
(@employe, 7), (@employe, 8), (@employe, 9),
(@employe, 12), (@employe, 13), (@employe, 14),
(@employe, 22),
(@employe, 23),
(@employe, 27),
(@employe, 28), (@employe, 29);

-- Assistante: view, create wizard, documents, templates view
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@assistante, 1),
(@assistante, 2), (@assistante, 3),
(@assistante, 7), (@assistante, 8),
(@assistante, 12), (@assistante, 13),
(@assistante, 22),
(@assistante, 23),
(@assistante, 27),
(@assistante, 28), (@assistante, 29);

-- Stagiaire: view only (dashboard, societes, associes, contrats, templates)
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@stagiaire, 1),
(@stagiaire, 2),
(@stagiaire, 7),
(@stagiaire, 12),
(@stagiaire, 23),
(@stagiaire, 28);

-- Expert-comptable: view societes/associes/contrats, documents
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@expert, 1),
(@expert, 2),
(@expert, 7),
(@expert, 12),
(@expert, 28), (@expert, 29);

-- Comptable agréé: same as expert
INSERT INTO role_permissions (role_id, permission_id)
SELECT @comptable, permission_id FROM role_permissions WHERE role_id = @expert;

-- Commissaire aux comptes: same as expert
INSERT INTO role_permissions (role_id, permission_id)
SELECT @commissaire, permission_id FROM role_permissions WHERE role_id = @expert;

-- Coursier: dashboard only
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@coursier, 1);

-- Avocat: view societes, contrats, documents
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@avocat, 1),
(@avocat, 2),
(@avocat, 12),
(@avocat, 28), (@avocat, 29);

-- Notaire: same as avocat
INSERT INTO role_permissions (role_id, permission_id)
SELECT @notaire, permission_id FROM role_permissions WHERE role_id = @avocat;

-- Conseil juridique: same as avocat
INSERT INTO role_permissions (role_id, permission_id)
SELECT @conseil, permission_id FROM role_permissions WHERE role_id = @avocat;

-- Banque: dashboard only
INSERT INTO role_permissions (role_id, permission_id)
VALUES (@banque, 1);

-- Assurance: dashboard only
INSERT INTO role_permissions (role_id, permission_id)
VALUES (@assurance, 1);

-- Autre: dashboard only
INSERT INTO role_permissions (role_id, permission_id)
VALUES (@autre, 1);

-- 4. Create default Super Admin collaborator (email: admin@center.test, password: admin123)
INSERT INTO collaborateurs (nom_complet, fonction, collaborateur_type, role_id, collaborateur_email, email, can_login, password_hash, statut, notes)
SELECT 'Super Admin', 'Administrateur système', 'interne', @super_admin, 'admin@center.test', 'admin@center.test', 1,
       '$2y$10$QOZo9.7oOayIbJEsGwRxLuuS6BvQ9rJT6oX1rAsQoFG4cAvwyHZBG', 'actif',
       'Compte super admin par defaut — changer le mot de passe'
WHERE NOT EXISTS (SELECT 1 FROM collaborateurs WHERE email = 'admin@center.test' OR collaborateur_email = 'admin@center.test');
