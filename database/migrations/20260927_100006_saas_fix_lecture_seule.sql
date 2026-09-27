-- SaaS multi-tenancy : correction du role "Lecture seule"
-- Phase 2/5 - Fondation donnees
--
-- 20260927_100003 attribuait a "Lecture seule" toute permission finissant par
-- `.view`. Le motif est trop large : il attrape aussi `configuration.view`
-- (referentiels du Centre), `users.view`, `cabinets.view`, `abonnements.view`
-- et `paiements.view` — c'est-a-dire des ecrans d'administration qu'un profil
-- en lecture seule ne doit pas atteindre.
--
-- On retire ces octrois hors liste et on re-pose une liste explicite limitee
-- au metier dossier. Les migrations appliquees ne s'editent pas : cette
-- migration corrective est donc separee.

DELETE rp
FROM role_permissions rp
JOIN roles r ON r.id = rp.role_id
JOIN permissions p ON p.id = rp.permission_id
WHERE r.nom = 'Lecture seule'
  AND p.permission_key NOT IN (
    'dashboard.view',
    'societes.view','societes.export','societes.suivi',
    'associes.view','associes.export',
    'contrats.view','contrats.export',
    'collaborateurs.view','collaborateurs.export',
    'cessions.view','cessions.export','cessions.suivi',
    'pv_ago.view',
    'modifications.view',
    'documents.view','documents.download',
    'templates.view',
    'generation.use',
    'convert.use',
    'mon_abonnement.view'
  );
