-- Retrait de `qualification` et `fonction`, et report de `raison_sociale` dans
-- `nom`.
--
-- Refonte du formulaire Cabinet sur le gabarit Collaborateur : les deux champs
-- etaient de purs doublons d'edition. Aucun n'est renseigne par un autre module
-- ni repris dans un export, et la distinction n'a pas de sens pour un cabinet
-- (il n'y a pas de « fonction du cabinet » a renseigner, ni de « qualification
-- professionnelle » propre a l'entite : le type de cabinet, lui, l'exprime
-- deja, et c'est lui qui est obligatoire).
--
-- `raison_sociale` n'est pas supprimee : elle n'est pas fusionnee par simple
-- reaffectation de colonne. Les deux colonnes se recopient d'abord l'une dans
-- l'autre, puis la saisie se fait sur le champ unique « Nom du cabinet /
-- Raison sociale » qui alimente `nom`. `raison_sociale` reste en base pour ne
-- pas detruire d'historique et parce que `mon_abonnement.php` la lisait ; la
-- lecture y bascule sur `nom`. La colonne ne recoit plus aucune ecriture.
--
-- L'UPDATE precede l'ALTER : sur une base ou `nom` est vide et `raison_sociale`
-- rempli, le nom doit etre recupere avant toute autre operation.
--
-- Un seul bloc, comme les migrations voisines : le runner envoie le fichier
-- d'un trait et s'arrete a la premiere erreur. L'ALTER est atomique (les deux
-- colonnes partent ou aucune), et l'erreur 1091 « can't DROP » sur une base
-- ou elles n'existent pas est explicitement toleree par le runner.

UPDATE cabinets
SET nom = raison_sociale
WHERE (nom IS NULL OR nom = '')
  AND raison_sociale IS NOT NULL
  AND raison_sociale <> '';

ALTER TABLE cabinets
    DROP COLUMN qualification,
    DROP COLUMN fonction;
