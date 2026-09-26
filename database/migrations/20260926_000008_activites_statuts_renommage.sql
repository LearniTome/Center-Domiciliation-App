-- Activites Statuts : renommage de la table ref_activites -> ref_activites_statuts
-- S execute AVANT 20260926_000009_activites_nma2010_activites_seules.sql : le
-- nettoyage du miroir ci-dessous doit comparer avec la liste NMA encore
-- complete (sections + branches + activites), donc avant son elagage.
-- 1. renommage conditionnel de l'ancienne table vers le nouveau nom (idempotent)
-- 2. nettoyage du miroir NMA herite des versions precedentes des migrations
--    20260926_000007 (qui alignait ref_activites sur les libelles de
--    ref_activites_ompic) : toute ligne dont le libelle existe dans
--    ref_activites_ompic est retiree, sauf si elle fait partie des 24 libelles
--    de reference ci-dessous. Les activites ajoutees par l'utilisateur sont
--    conservees.
-- 3. purge de la valeur corrompue heritee de database/import.sql versionne
--    (octets 4E E2 94 9C C2 AE 67 6F 63 69 61 6E 74 = "Neo" + box-drawing +
--    (R) + "gociant", au lieu de C3 A9 pour l'accent) puis INSERT IGNORE des
--    24 libelles de reference de database/seed.sql : sans ce purge l'entree
--    "Negociant" apparaitrait en double dans les listes deroulantes
-- 4. suppression de l'ancienne table si le renommage a ete court-circuite
-- La NMA 2010 (649 activites) reste dans ref_activites_ompic, reservee aux dossiers
-- de domiciliation ; ref_activites_statuts reste la liste libre des dossiers
-- Creation / Cession / PV AGO.

SET @act_src = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ref_activites');
SET @act_dst = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ref_activites_statuts');
SET @act_sql = IF(@act_src = 1 AND @act_dst = 0, 'RENAME TABLE ref_activites TO ref_activites_statuts', 'DO 0');
PREPARE st_act FROM @act_sql;
EXECUTE st_act;
DEALLOCATE PREPARE st_act;

CREATE TABLE IF NOT EXISTS ref_activites_statuts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activite VARCHAR(190) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_activites_statuts (activite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM ref_activites_statuts
WHERE activite IN (SELECT libelle FROM ref_activites_ompic)
  AND activite NOT IN (
      'Commerce de gros', 'Commerce de detail', 'Restauration', 'Hotel', 'Transport',
      'Logistique', 'Consulting', 'Services IT', 'Services de sante', 'Education',
      'Immobilier', 'Construction', 'Manufacture', 'Agriculture', 'Peche', 'Energie',
      'Telecommunications', 'Banque et Finance', 'Assurance', 'Tourisme',
      'Travaux Divers ou de Construction', 'Marchand effectuant Import Export',
      'Négociant', 'Conseil de Gestion'
  );

DELETE FROM ref_activites_statuts
WHERE HEX(activite) = '4EE2949CC2AE676F6369616E74';

INSERT IGNORE INTO ref_activites_statuts (activite, sort_order) VALUES
('Commerce de gros', 1),
('Commerce de detail', 2),
('Restauration', 3),
('Hotel', 4),
('Transport', 5),
('Logistique', 6),
('Consulting', 7),
('Services IT', 8),
('Services de sante', 9),
('Education', 10),
('Immobilier', 11),
('Construction', 12),
('Manufacture', 13),
('Agriculture', 14),
('Peche', 15),
('Energie', 16),
('Telecommunications', 17),
('Banque et Finance', 18),
('Assurance', 19),
('Tourisme', 20),
('Travaux Divers ou de Construction', 21),
('Marchand effectuant Import Export', 22),
('Négociant', 23),
('Conseil de Gestion', 24);

SET @act_drop = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ref_activites');
SET @act_sql = IF(@act_drop = 1, 'DROP TABLE ref_activites', 'DO 0');
PREPARE st_act FROM @act_sql;
EXECUTE st_act;
DEALLOCATE PREPARE st_act;
