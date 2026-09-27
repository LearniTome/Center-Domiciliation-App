-- SaaS multi-tenancy : discriminant cabinet_id sur les tables metier
-- Phase 1/5 - Fondation donnees
--
-- Base partagee, une seule ligne par tenant. Convention :
--   cabinet_id IS NULL     -> donnee proprietaire du Centre (acces interne)
--   cabinet_id = <id>      -> donnee d'un cabinet adherent
--
-- Les lignes existantes (10 467 societes en prod) restent a NULL : aucune
-- migration de donnees, aucun risque de perte. Le Centre garde son historique
-- et le voit integralement ; un cabinet adherent ne voit que son tenant.

ALTER TABLE societes
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_societes_cabinet (cabinet_id);

ALTER TABLE associes
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_associes_cabinet (cabinet_id);

ALTER TABLE contrats
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_contrats_cabinet (cabinet_id);

ALTER TABLE collaborateurs
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_collaborateurs_cabinet (cabinet_id);

ALTER TABLE cessions
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_cessions_cabinet (cabinet_id);

ALTER TABLE pv_ago
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_pv_ago_cabinet (cabinet_id);

ALTER TABLE documents_generes
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_documents_generes_cabinet (cabinet_id);

ALTER TABLE uploaded_docs
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_uploaded_docs_cabinet (cabinet_id);

ALTER TABLE societe_suivi_etapes
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_soc_suivi_etapes_cabinet (cabinet_id);

ALTER TABLE societe_suivi_documents
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_soc_suivi_docs_cabinet (cabinet_id);

ALTER TABLE cession_parts
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_cession_parts_cabinet (cabinet_id);

ALTER TABLE activity_logs
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_activity_logs_cabinet (cabinet_id);

ALTER TABLE notifications
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD INDEX idx_notifications_cabinet (cabinet_id);

-- Rattachement de la session a son tenant : permet de purger les sessions
-- d'un cabinet suspendu et d'afficher le bandeau d'etat cote entete.
ALTER TABLE user_sessions
    ADD COLUMN cabinet_id INT UNSIGNED DEFAULT NULL AFTER user_id,
    ADD INDEX idx_user_sessions_cabinet (cabinet_id);
