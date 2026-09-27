-- SaaS multi-tenancy : portee des roles, roles canoniques, permissions SaaS
-- Phase 1/5 - Fondation donnees
--
-- roles.scope  : 'centre' (acces transversal, non facture)
--                 'cabinet' (adherent, confine a son cabinet)
-- roles.is_billable : 0 = interne (les employes du Centre ne sont pas factures)
--
-- Les 16 roles existants sont conserves tels quels : aucune rengression.
-- is_internal = 0 (Expert-comptable, Avocat, Notaire, ...) regroupe des
-- professions externes : ces roles sont des roles de cabinet.

ALTER TABLE roles
    ADD COLUMN scope VARCHAR(20) NOT NULL DEFAULT 'centre' AFTER is_system,
    ADD COLUMN is_billable TINYINT(1) NOT NULL DEFAULT 0 AFTER scope,
    ADD INDEX idx_roles_scope (scope);

-- Roles metier externes (is_internal = 0) : ce sont des roles de cabinet
UPDATE roles SET scope = 'cabinet' WHERE is_internal = 0;
UPDATE roles SET is_billable = 0 WHERE is_internal = 1;

-- Roles canoniques du modele SaaS
INSERT IGNORE INTO roles (nom, description, is_internal, is_system, scope, is_billable, sort_order) VALUES
    ('Responsable Centre',    'Pilotage operationnel du Centre : dossiers, societes, contrats, collaborateurs. Aucun acces aux parametres systeme ni aux abonnements.', 1, 0, 'centre',  0, 10),
    ('Employe Interne',       'Gestion quotidienne des dossiers : creation, modification, generation de documents. Aucune suppression sensible.',                1, 0, 'centre',  0, 20),
    ('Administrateur Cabinet','Gere son cabinet, ses utilisateurs et son abonnement. Acces limite aux donnees de son cabinet.',                              0, 0, 'cabinet', 1, 30),
    ('Collaborateur Cabinet', 'Gestion des dossiers et societes du cabinet. Ni utilisateurs, ni abonnements.',                                             0, 0, 'cabinet', 1, 40),
    ('Lecture seule',         'Consultation uniquement, aucune ecriture.',                                                                                                    0, 0, 'cabinet', 1, 50);

UPDATE roles SET scope = 'centre', is_billable = 0, description = 'Acces total : abonnements, plans, paiements, cabinets, utilisateurs internes, parametres systeme.'
WHERE nom = 'Super Admin';

-- Permissions SaaS
INSERT IGNORE INTO permissions (nom, permission_key, category, description) VALUES
    ('Voir les cabinets',           'cabinets.view',         'cabinets',     'Consulter la liste des cabinets clients'),
    ('Creer un cabinet',            'cabinets.create',       'cabinets',     'Enregistrer un nouveau cabinet client'),
    ('Modifier un cabinet',         'cabinets.edit',         'cabinets',     'Modifier la fiche d un cabinet'),
    ('Supprimer un cabinet',        'cabinets.delete',       'cabinets',     'Supprimer un cabinet'),
    ('Voir les plans',              'plans.view',            'plans',        'Consulter les plans tarifaires'),
    ('Gerer les plans',             'plans.edit',            'plans',        'Creer et modifier les plans tarifaires'),
    ('Voir les abonnements',        'abonnements.view',       'abonnements',  'Consulter les abonnements'),
    ('Creer un abonnement',         'abonnements.create',     'abonnements',  'Souscrire un abonnement annuel'),
    ('Modifier un abonnement',      'abonnements.edit',       'abonnements',  'Renouveler, suspendre, resilier un abonnement'),
    ('Supprimer un abonnement',     'abonnements.delete',     'abonnements',  'Supprimer un abonnement'),
    ('Voir les paiements',          'paiements.view',         'paiements',   'Consulter les paiements'),
    ('Enregistrer un paiement',     'paiements.create',       'paiements',   'Saisir un paiement'),
    ('Modifier un paiement',        'paiements.edit',         'paiements',   'Modifier un paiement'),
    ('Supprimer un paiement',       'paiements.delete',       'paiements',   'Supprimer un paiement'),
    ('Voir les factures',           'factures.view',          'factures',    'Consulter les factures'),
    ('Creer une facture',           'factures.create',        'factures',    'Editer une facture'),
    ('Modifier une facture',        'factures.edit',          'factures',    'Modifier une facture'),
    ('Supprimer une facture',       'factures.delete',        'factures',    'Supprimer une facture'),
    ('Voir les utilisateurs',       'users.view',             'users',       'Consulter les comptes'),
    ('Creer un utilisateur',        'users.create',           'users',       'Creer un compte'),
    ('Modifier un utilisateur',     'users.edit',             'users',       'Modifier un compte, role et acces'),
    ('Supprimer un utilisateur',    'users.delete',           'users',       'Supprimer un compte'),
    ('Voir les parametres',         'parametres.view',        'parametres',  'Consulter les parametres systeme'),
    ('Gerer les parametres',        'parametres.edit',        'parametres',  'Modifier les parametres systeme'),
    ('Voir mon abonnement',         'mon_abonnement.view',    'abonnements', 'Consulter son propre abonnement (auto-service cabinet)');

-- Matrice : Super Admin (is_system = 1) recoit tout, par construction.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.nom = 'Super Admin' AND r.is_system = 1;

-- Responsabilite de pilotage operationnel : tout le metier, aucun systeme.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.nom = 'Responsable Centre'
  AND p.permission_key IN (
    'dashboard.view','societes.view','societes.create','societes.edit','societes.delete','societes.export','societes.import','societes.suivi',
    'associes.view','associes.create','associes.edit','associes.delete','associes.export','associes.import',
    'contrats.view','contrats.create','contrats.edit','contrats.delete','contrats.export','contrats.import',
    'collaborateurs.view','collaborateurs.create','collaborateurs.edit','collaborateurs.delete','collaborateurs.export','collaborateurs.import',
    'cessions.view','cessions.create','cessions.edit','cessions.delete','cessions.export','cessions.import','cessions.suivi',
    'pv_ago.view','pv_ago.create','pv_ago.edit','pv_ago.delete',
    'modifications.view','documents.view','documents.download',
    'templates.view','templates.create','templates.edit','templates.delete',
    'generation.use','wizard.create','ai.use','analyse.view','convert.use','variables.view'
  );

-- Gestion quotidienne : ecriture autorisee, suppression sensible exclue.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.nom = 'Employe Interne'
  AND p.permission_key IN (
    'dashboard.view','societes.view','societes.create','societes.edit','societes.export','societes.suivi',
    'associes.view','associes.create','associes.edit','associes.export',
    'contrats.view','contrats.create','contrats.edit','contrats.export',
    'collaborateurs.view','collaborateurs.create','collaborateurs.edit','collaborateurs.export',
    'cessions.view','cessions.create','cessions.edit','cessions.export','cessions.suivi',
    'pv_ago.view','pv_ago.create','pv_ago.edit',
    'modifications.view','documents.view','documents.download',
    'templates.view','generation.use','wizard.create','ai.use','convert.use'
  );

-- Administrateur de cabinet : pilote son tenant + ses comptes + son abonnement.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.nom = 'Administrateur Cabinet'
  AND p.permission_key IN (
    'dashboard.view','societes.view','societes.create','societes.edit','societes.delete','societes.export','societes.suivi',
    'associes.view','associes.create','associes.edit','associes.delete','associes.export',
    'contrats.view','contrats.create','contrats.edit','contrats.delete','contrats.export',
    'collaborateurs.view','collaborateurs.create','collaborateurs.edit','collaborateurs.delete','collaborateurs.export',
    'cessions.view','cessions.create','cessions.edit','cessions.delete','cessions.export','cessions.suivi',
    'pv_ago.view','pv_ago.create','pv_ago.edit','pv_ago.delete',
    'modifications.view','documents.view','documents.download',
    'templates.view','generation.use','wizard.create',
    'users.view','users.create','users.edit','users.delete',
    'mon_abonnement.view'
  );

-- Collaborateur de cabinet : idem sans gestion des comptes ni suppression.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.nom = 'Collaborateur Cabinet'
  AND p.permission_key IN (
    'dashboard.view','societes.view','societes.create','societes.edit','societes.export','societes.suivi',
    'associes.view','associes.create','associes.edit','associes.export',
    'contrats.view','contrats.create','contrats.edit','contrats.export',
    'collaborateurs.view','collaborateurs.create','collaborateurs.edit','collaborateurs.export',
    'cessions.view','cessions.create','cessions.edit','cessions.export','cessions.suivi',
    'pv_ago.view','pv_ago.create','pv_ago.edit',
    'modifications.view','documents.view','documents.download',
    'templates.view','generation.use','wizard.create'
  );

-- Lecture seule : consultation, aucune ecriture.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.nom = 'Lecture seule'
  AND (p.permission_key LIKE '%.view' OR p.permission_key IN ('generation.use','convert.use'))
  AND p.permission_key NOT IN ('parametres.view');

-- Reprise du role primaire dans la table pivot multi-roles
INSERT IGNORE INTO user_roles (user_id, role_id, is_primary)
SELECT u.id, u.role_id, 1 FROM users u WHERE u.role_id IS NOT NULL;
