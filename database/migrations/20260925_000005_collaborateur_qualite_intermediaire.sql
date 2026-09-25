-- Rattachement d'un collaborateur a la taxonomie des qualifications.
--
-- La table ref_qualites_intermediaire (migration 20260925_000001) alimente le
-- segment [TYPE] du nom de dossier, mais aucun colonne ne la referencait
-- depuis collaborateurs : la taxonomie etait donc invisible et inutilisable.
--
-- Collaborateurs n'emporte volontairement pas de NOT NULL : les comptes
-- internes (can_login = 1, type 'interne') ne sont pas des intermediaires et
-- n'ont pas de qualification. Un NULL affiche "non qualifie", ce qui est
-- exact, la ou une valeur inventee serait fausse.
--
-- Backfill : uniquement quand collaborateur_code contient deja un code de la
-- taxonomie. Le code 'EXP' porte aujourd'hui par la ligne de test ne matche
-- aucun code (les codes sont en 3 parties : CPT-EXP, COU-AGR...) et reste donc
-- sans qualification, a qualifier a la main depuis la modale. Aucune donnee
-- n'est devinee.
--
-- Idempotent : le runner absorbe 42S21 (colonne existe), 1061 (index existe)
-- et 1826 (FK deja nommee).

ALTER TABLE collaborateurs
    ADD COLUMN qualite_intermediaire_id INT UNSIGNED NULL
        COMMENT 'FK ref_qualites_intermediaire (CPT-*, COU-*, CLT-*)'
        AFTER collaborateur_type;

ALTER TABLE collaborateurs
    ADD KEY idx_collaborateurs_qualite (qualite_intermediaire_id);

-- Rattachement effectif de la FK : ADD COLUMN IF NOT EXISTS n'etant pas
-- disponible sur la version MySQL de XAMPP, on tente et on absorbe l'erreur
-- de doublon via le runner.
ALTER TABLE collaborateurs
    ADD CONSTRAINT fk_collaborateurs_qualite
        FOREIGN KEY (qualite_intermediaire_id)
        REFERENCES ref_qualites_intermediaire (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- Backfill : on ne rattache que les codes deja conformes a la taxonomie.
-- Le code dossier est forme du code qualification puis du code dossier
-- (CPT-AGR-FIDBA) : la qualification est donc le PREFIXE. MySQL compte les
-- occurrences a partir de la gauche pour un count positif, d'ou le 2.
--   SUBSTRING_INDEX(code, '-', 1)  -> 'CPT'      (trop court, aucun match)
--   SUBSTRING_INDEX(code, '-', 2)  -> 'CPT-AGR'  (le code recherche)
-- Les codes de ref_qualites_intermediaire sont eux-memes en 2 segments
-- (CPT-EXP, COU-AGR, CLT-*), pas 'AGR' seul.
UPDATE collaborateurs c
JOIN ref_qualites_intermediaire q
    ON q.code = SUBSTRING_INDEX(c.collaborateur_code, '-', 2)
SET c.qualite_intermediaire_id = q.id
WHERE c.collaborateur_code LIKE 'CPT-%'
   OR c.collaborateur_code LIKE 'COU-%'
   OR c.collaborateur_code LIKE 'CLT-%';
