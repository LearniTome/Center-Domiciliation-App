-- Remplace le suivi domiciliation (8 etapes generiques partagees avec la creation)
-- par le vrai workflow de domiciliation en 12 etapes.
-- Reset complet : les anciennes etapes sont supprimees (les documents attaches
-- a ces etapes sont supprimes en cascade), puis les 12 nouvelles etapes sont inserees.

DELETE e FROM societe_suivi_etapes e
JOIN societes s ON s.id = e.societe_id
WHERE s.societe_type_generation != 'creation';

INSERT INTO societe_suivi_etapes (societe_id, etape, ordre)
SELECT ss.id, steps.etape, steps.ordre
FROM societes ss
JOIN (
    SELECT 'recup_documents' AS etape, 1 AS ordre UNION ALL
    SELECT 'verification', 2 UNION ALL
    SELECT 'remplir_documents', 3 UNION ALL
    SELECT 'envoi_contrats', 4 UNION ALL
    SELECT 'retour_contrats_legalises', 5 UNION ALL
    SELECT 'legalisation_attestations', 6 UNION ALL
    SELECT 'appel_remise', 7 UNION ALL
    SELECT 'attestation_enregistrement', 8 UNION ALL
    SELECT 'recup_dossier_final', 9 UNION ALL
    SELECT 'impression_dossier', 10 UNION ALL
    SELECT 'classement_archivage', 11 UNION ALL
    SELECT 'archivage_cloud', 12
) steps
WHERE ss.societe_type_generation != 'creation';