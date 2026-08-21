---
description: Moteur d'exécution — implémentation production-ready sans placeholders, avec auto-validation et synchronisation de la roadmap
agent: build
---

La planification et la feuille de route (`docs/ROADMAP.md`) sont approuvées. Active maintenant le moteur d'exécution selon les contrôles suivants :

1. **Règle anti-placeholder** : interdiction absolue de `// TODO`, `# à compléter`, fonctions tronquées ou code d'exemple. Le code doit être 100% complet, prêt pour la production, avec gestion d'erreurs (try/catch PDO, vérifications null). Conventions obligatoires : `declare(strict_types=1);`, `e()` pour tout output HTML, CSRF (`csrf_input()` + `verify_csrf()`) sur chaque POST, redirect-after-POST via `redirect_to()`, flash via `set_flash()`, requêtes PDO préparées à paramètres nommés.
2. **Auto-validation** : en cas d'erreur, simule le flux de données, lis les logs (`C:\xampp\php\logs\php_error_log`), corrige toi-même via le terminal. Ne m'interromps que pour les cas extrêmes. Vérifie chaque fichier avec `php -l` avant de passer au suivant.
3. **Synchronisation vivante** : après chaque fichier créé/modifié, mets à jour `docs/ROADMAP.md` et barre la tâche terminée. Charge les skills security + manual-test en fin de parcours. La session ne se termine pas tant qu'il reste des tâches non bloquées et que l'application n'est pas testée (serveur dev : `powershell -ExecutionPolicy Bypass -File .\scripts\dev-server.ps1 -Project . -Port 8000 -NoBrowser`).

Commence l'exécution étape par étape, dans l'ordre de `docs/ROADMAP.md`.
