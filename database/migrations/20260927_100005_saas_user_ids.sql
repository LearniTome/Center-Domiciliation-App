-- SaaS multi-tenancy : reconciliation des identites de connexion
-- Phase 2/5 - Fondation donnees
--
-- Avant cette migration, `users` n'existait pas : l'identite de connexion
-- etait celle de `collaborateurs`. Les colonnes qui designent l'AUTEUR d'un
-- evenement (et non le collaborateur affecte a un dossier) contiennent donc
-- un collaborateurs.id :
--
--   activity_logs.user_id          auteur de la trace
--   notifications.target_user_id   destinataire
--   user_sessions.user_id          session ouverte
--
-- Elles passent a users.id. `societes.created_by` et consorts NE sont PAS
-- concernees : ces colonnes designent le collaborateur metier en charge du
-- dossier (helper current_collaborateur_id()).
--
-- Le garde NOT EXISTS rend l'operation idempotente : une seconde execution
-- ne reapplique rien, car la valeur ciblee existe deja dans users.id.

UPDATE activity_logs al
SET al.user_id = (SELECT u.id FROM users u WHERE u.collaborateur_id = al.user_id LIMIT 1)
WHERE al.user_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM users u WHERE u.collaborateur_id = al.user_id)
  AND NOT EXISTS (SELECT 1 FROM users u2 WHERE u2.id = al.user_id);

UPDATE notifications n
SET n.target_user_id = (SELECT u.id FROM users u WHERE u.collaborateur_id = n.target_user_id LIMIT 1)
WHERE n.target_user_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM users u WHERE u.collaborateur_id = n.target_user_id)
  AND NOT EXISTS (SELECT 1 FROM users u2 WHERE u2.id = n.target_user_id);

UPDATE user_sessions s
SET s.user_id = (SELECT u.id FROM users u WHERE u.collaborateur_id = s.user_id LIMIT 1)
WHERE s.user_id IS NOT NULL
  AND EXISTS (SELECT 1 FROM users u WHERE u.collaborateur_id = s.user_id)
  AND NOT EXISTS (SELECT 1 FROM users u2 WHERE u2.id = s.user_id);
