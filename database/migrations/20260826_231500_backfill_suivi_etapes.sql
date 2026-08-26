-- Backfill suivi etapes for all societes that don't have any yet

INSERT INTO societe_suivi_etapes (societe_id, etape, ordre)
SELECT s.id, steps.etape, steps.ordre
FROM societes s
JOIN (
    SELECT 'creation' AS gen, 'certificat_negatif' AS etape, 1 AS ordre UNION ALL
    SELECT 'creation', 'redaction_statuts', 2 UNION ALL
    SELECT 'creation', 'signature', 3 UNION ALL
    SELECT 'creation', 'enregistrement', 4 UNION ALL
    SELECT 'creation', 'depot_greffe', 5 UNION ALL
    SELECT 'creation', 'publication_jal_bo', 6 UNION ALL
    SELECT 'creation', 'rc', 7 UNION ALL
    SELECT 'creation', 'remise', 8 UNION ALL
    SELECT 'domiciliation', 'contrat_domiciliation', 1 UNION ALL
    SELECT 'domiciliation', 'redaction', 2 UNION ALL
    SELECT 'domiciliation', 'signature', 3 UNION ALL
    SELECT 'domiciliation', 'enregistrement', 4 UNION ALL
    SELECT 'domiciliation', 'depot_greffe', 5 UNION ALL
    SELECT 'domiciliation', 'publication_jal', 6 UNION ALL
    SELECT 'domiciliation', 'rc_modificatif', 7 UNION ALL
    SELECT 'domiciliation', 'remise', 8
) steps ON steps.gen = CASE WHEN s.societe_type_generation = 'creation' THEN 'creation' ELSE 'domiciliation' END
WHERE NOT EXISTS (
    SELECT 1 FROM societe_suivi_etapes e WHERE e.societe_id = s.id
);
