-- Completer les activites (statuts) de toutes les societes a un minimum de 3.
-- Principe : on garde les activites existantes et on ajoute, dans l'ordre,
-- des activites generiques par defaut si elles sont absentes :
--   1. Conseil de Gestion   2. Commerce de detail   3. Services IT
-- Idempotent : une fois 3 activites atteintes, plus aucune passe ne s'applique.

-- Passe 1 : ajouter "Conseil de Gestion" si absent et si moins de 3 activites
UPDATE societes
SET societe_activites_statuts = TRIM(BOTH ', ' FROM CONCAT(COALESCE(societe_activites_statuts, ''), ', Conseil de Gestion'))
WHERE (
    CASE WHEN COALESCE(societe_activites_statuts, '') = '' THEN 0
         ELSE CHAR_LENGTH(societe_activites_statuts) - CHAR_LENGTH(REPLACE(societe_activites_statuts, ',', '')) + 1 END
) < 3
AND NOT FIND_IN_SET('Conseil de Gestion', REPLACE(COALESCE(societe_activites_statuts, ''), ', ', ','));

-- Passe 2 : ajouter "Commerce de detail" si absent et si toujours moins de 3
UPDATE societes
SET societe_activites_statuts = TRIM(BOTH ', ' FROM CONCAT(COALESCE(societe_activites_statuts, ''), ', Commerce de detail'))
WHERE (
    CASE WHEN COALESCE(societe_activites_statuts, '') = '' THEN 0
         ELSE CHAR_LENGTH(societe_activites_statuts) - CHAR_LENGTH(REPLACE(societe_activites_statuts, ',', '')) + 1 END
) < 3
AND NOT FIND_IN_SET('Commerce de detail', REPLACE(COALESCE(societe_activites_statuts, ''), ', ', ','));

-- Passe 3 : ajouter "Services IT" si absent et si toujours moins de 3
UPDATE societes
SET societe_activites_statuts = TRIM(BOTH ', ' FROM CONCAT(COALESCE(societe_activites_statuts, ''), ', Services IT'))
WHERE (
    CASE WHEN COALESCE(societe_activites_statuts, '') = '' THEN 0
         ELSE CHAR_LENGTH(societe_activites_statuts) - CHAR_LENGTH(REPLACE(societe_activites_statuts, ',', '')) + 1 END
) < 3
AND NOT FIND_IN_SET('Services IT', REPLACE(COALESCE(societe_activites_statuts, ''), ', ', ','));
