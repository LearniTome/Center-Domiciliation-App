-- Resiliation de contrat + normalisation du vocabulaire de statut.
--
-- Le tableau de bord comptait deja un statut 'resilie' (dashboard_count sur
-- contrats.contrat_statut) mais aucun ecran ne permettait de le saisir : le
-- compteur restait donc a zero en permanence. Les listes, elles, proposaient
-- 'expire', qu'aucun code ne produit.
--
-- Le vocabulaire est ramene a 4 valeurs, et une seule table de reference
-- (ci-dessous) devient la source de verite, consommee par la liste, la page de
-- suivi et le tableau de bord.
--
-- Idempotent : le runner absorbe 42S21 (colonne existe).

-- Motivation de resiliation (texte libre, la raison n'est pas univoque).
ALTER TABLE contrats
    ADD COLUMN contrat_motif_resiliation VARCHAR(255) NULL
        COMMENT 'Raison de la resiliation (demande client, defaut, litige...)'
        AFTER contrat_statut;

-- Date de resiliation. NULL tant que le contrat n'est pas resilie.
ALTER TABLE contrats
    ADD COLUMN contrat_date_resiliation DATE NULL
        COMMENT 'Date effective de resiliation ; NULL tant que non resilie'
        AFTER contrat_motif_resiliation;

-- Normalisation : NULL et chaine vide sont des brouillons, pas des contrats
-- actifs. Un contrat qui n'a jamais eu de statut n'a jamais ete signe.
UPDATE contrats
SET contrat_statut = 'brouillon'
WHERE contrat_statut IS NULL OR TRIM(contrat_statut) = '';

-- Normalisation des synonymes deja presents en base vers le vocabulaire cible.
-- 'expire' reste une valeur du vocabulaire (contrat echu non resilie).
UPDATE contrats SET contrat_statut = 'resilie'
WHERE contrat_statut IN ('resiliee', 'resilié', 'resiliee_', 'terminé', 'termine', 'clos', 'annule');

UPDATE contrats SET contrat_statut = 'expire'
WHERE contrat_statut IN ('expired', 'expiré', 'expiree', 'echu', 'échu');

UPDATE contrats SET contrat_statut = 'brouillon'
WHERE contrat_statut IN ('draft', 'en attente', 'en_attente', 'projet', 'prepare', 'préparé');

-- Une date de resiliation implique le statut 'resilie'.
UPDATE contrats
SET contrat_statut = 'resilie'
WHERE contrat_date_resiliation IS NOT NULL
  AND (contrat_statut IS NULL OR contrat_statut <> 'resilie');

-- Index de suivi : filtre recurrent "actifs echus dans N jours" et "resilies".
ALTER TABLE contrats
    ADD KEY idx_contrats_suivi (contrat_statut, contrat_date_fin);
