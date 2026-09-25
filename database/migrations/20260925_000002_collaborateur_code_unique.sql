-- Code collaborateur fiable (regle H2 : 3 lettres du nom + 2 du prenom).
--
-- 1. collaborateur_prenom : H2 a besoin du prenom, la table n'avait que
--    collaborateur_nom et un nom_complet non exploitable de facon fiable.
-- 2. Normalisation '' -> NULL : MySQL autorise plusieurs chaines vides sur un
--    index unique (une seule valeur '' a la fois), ce qui casserait la garantie
--    d'unicite du code. NULL en revanche est exclu de l'index.
-- 3. Index unique sur collaborateur_code, elargi a VARCHAR(40) pour loger le
--    suffixe anti-collision (CPT-AGR-FIDBA2).
--
-- Idempotent : le runner absorbe 42S21 (colonne existe) et 1061 (index existe).

ALTER TABLE collaborateurs
    ADD COLUMN collaborateur_prenom VARCHAR(120) NULL AFTER collaborateur_nom;

ALTER TABLE collaborateurs
    MODIFY COLUMN collaborateur_code VARCHAR(40) NULL DEFAULT NULL;

-- Nettoyage avant index : les codes vides deviennent NULL, les codes trop
-- longs sont mis de cote. Aucune donnee n'est supprimee.
UPDATE collaborateurs
SET collaborateur_code = NULL
WHERE collaborateur_code IS NOT NULL
  AND (TRIM(collaborateur_code) = '' OR CHAR_LENGTH(collaborateur_code) > 40);

-- Doublons eventuels : on conserve le plus ancien id et on libere les autres.
-- L'erreur 1062 "Duplicate entry" n'est pas ignoree par le runner, la
-- migration doit donc pouvoir poser son index sur n'importe quelle base.
UPDATE collaborateurs c
JOIN (
    SELECT collaborateur_code AS code_dup, MIN(id) AS keep_id
    FROM collaborateurs
    WHERE collaborateur_code IS NOT NULL AND TRIM(collaborateur_code) <> ''
    GROUP BY collaborateur_code
    HAVING COUNT(*) > 1
) d ON c.collaborateur_code = d.code_dup
SET c.collaborateur_code = NULL
WHERE c.id <> d.keep_id;

ALTER TABLE collaborateurs
    ADD UNIQUE KEY uk_collaborateur_code (collaborateur_code);
