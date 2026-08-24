---
description: Déploie sur Heberjahiz (push php-haja → GitHub Actions → vérification app.centirio.ma).
agent: build
---

Déploie l'application sur la production Heberjahiz en suivant strictement la skill `heberjahiz-deploy` :

1. Vérifie l'état git (`git status`, `git log origin/php-haja..HEAD --oneline`) ; s'il y a des commits non poussés : `git push origin php-haja`. Si $ARGUMENTS contient un message de commit et qu'il y a des modifications non commitées, committe d'abord avec ce message.
2. Récupère le run déclenché : `gh run list --repo LearniTome/Center-Domiciliation-App --workflow "Deploy Heberjahiz" --limit 1`
3. Poller toutes les 25 s jusqu'à `status=completed` (timeout global 20 min).
4. Si échec : affiche les logs de l'étape fautive (`gh run view <id> --log-failed`) et diagnostique via le tableau de dépannage de la skill.
5. Si succès : vérifie `EXTRACT_OK` dans les logs, puis teste `https://app.centirio.ma/` :
   - attendu : 302 vers `?page=connexion` puis HTTP 200 ;
   - extraire tout `<small>` PDO si flash-error présent.
6. Rapporte un résumé : commits déployés, durée, résultats des vérifications, actions restantes éventuelles.

Ne jamais mettre de secret en clair dans les sorties ou les commits.
