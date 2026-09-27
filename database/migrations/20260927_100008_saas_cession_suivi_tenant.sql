-- SaaS multi-tenancy : suivi des cessions
-- Phase 3/5 - Cloisonnement
--
-- 20260927_100004 a oublie les deux tables du suivi de cession. Elles ne
-- contenaient aucune ligne au moment du deploiement, ce qui a masque l'oubli :
-- la premiere cession creee par un adherent aurait produit des etapes
-- visibles de tous les tenants.
--
-- Elles sont des tables metier au meme titre que societe_suivi_etapes et
-- societe_suivi_documents, deja cloisonnees.

ALTER TABLE cession_suivi_etapes
    ADD COLUMN cabinet_id INT(10) UNSIGNED NULL DEFAULT NULL AFTER cession_id,
    ADD INDEX idx_cse_cabinet (cabinet_id);

ALTER TABLE cession_suivi_documents
    ADD COLUMN cabinet_id INT(10) UNSIGNED NULL DEFAULT NULL AFTER etape_id,
    ADD INDEX idx_csd_cabinet (cabinet_id);
