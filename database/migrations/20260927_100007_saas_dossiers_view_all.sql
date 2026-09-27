-- SaaS multi-tenancy : permission "vue transverse des dossiers"
-- Phase 2/5 - Fondation donnees
--
-- Plusieurs pages testaient `(int) $user['role_id']` contre [1, 2] pour
-- decider si l'utilisateur voit tous les dossiers ou seulement ceux qu'il a
-- crees. Ce test sur un entier fige casse des que les identifiants de roles
-- bougent, et il n'a pas d'equivalent dans le modele cabinet.
--
-- `dossiers.view_all` exprime l'intention : le Centre pilote l'ensemble, un
-- adherent voit son cabinet. Les roles qui avaient ce comportement (Super
-- Admin, Admin, Responsable Centre) le conservent.

INSERT IGNORE INTO permissions (nom, permission_key, category, description)
VALUES ('Voir tous les dossiers', 'dossiers.view_all', 'societes',
        'Consulter les dossiers de tous les collaborateurs et de tous les cabinets');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.nom IN ('Super Admin', 'Admin', 'Responsable Centre')
  AND p.permission_key = 'dossiers.view_all';
