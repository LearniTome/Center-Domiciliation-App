-- ============================================================
-- 20260916_000001_indexes_audit.sql
-- Audit 2026-09-16 : indexes manquants + unicite numeros de dossier
--
-- Verifie 0 doublon sur les numeros de dossier avant deploy prod :
--   SELECT societe_dossier_domiciliation_number, COUNT(*) c FROM societes
--   GROUP BY 1 HAVING c > 1;
--   SELECT societe_dossier_creation_number, COUNT(*) c FROM societes
--   GROUP BY 1 HAVING c > 1;
-- ============================================================

-- Index manquants (performance, sans risque)
ALTER TABLE societes
    ADD KEY idx_societes_type_generation (societe_type_generation),
    ADD KEY idx_societes_raison_sociale (societe_raison_sociale),
    ADD KEY idx_societes_date_exp_cert_neg (societe_date_exp_cert_neg);

ALTER TABLE associes
    ADD KEY idx_associes_date_validite_cin (associe_date_validite_cin),
    ADD KEY idx_associes_cin (associe_cin);

ALTER TABLE contrats
    ADD KEY idx_contrats_date_fin (contrat_date_fin),
    ADD KEY idx_contrats_statut (contrat_statut);

ALTER TABLE collaborateurs
    ADD KEY idx_collaborateurs_collaborateur_email (collaborateur_email);

-- Unicite des numeros de dossier (integrite metier)
ALTER TABLE societes
    ADD UNIQUE KEY uq_societes_dossier_domiciliation (societe_dossier_domiciliation_number),
    ADD UNIQUE KEY uq_societes_dossier_creation (societe_dossier_creation_number);