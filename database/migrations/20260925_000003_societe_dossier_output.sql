-- Gel du chemin de sortie : le nom du dossier est calcule UNE SEULE FOIS a la
-- premiere generation puis ecrit ici. Tous les consommateurs (telechargement,
-- ZIP, regeneration) lisent ces colonnes au lieu de recalculer le nom, ce qui
-- eliminait les doublons quand la date, le collaborateur ou la raison sociale
-- changeaient.
--
-- dossier_output_path : chemin RELATIF a la racine du projet, separateur '/',
--                       ex. dossiers_generer/DOM-2026-042_TECH-SOLUTIONS-MAROC_SARL-AU
-- dossier_output_nom  : nom du dossier seul (le dernier segment du chemin).
--
-- Les 19 chemins deja stocks dans documents_generes.file_path et
-- uploaded_docs.file_path sont encore absolus et majorite orphelins : leur
-- conversion en relatif fait l'objet d'une migration dediee ulterieure, une
-- fois leCleanup des fichiers morts tranche.
--
-- Idempotent : le runner absorbe 42S21 (colonne existe).

ALTER TABLE societes
    ADD COLUMN dossier_output_path VARCHAR(500) NULL DEFAULT NULL AFTER societe_dossier_creation_number;

ALTER TABLE societes
    ADD COLUMN dossier_output_nom VARCHAR(160) NULL DEFAULT NULL AFTER dossier_output_path;
