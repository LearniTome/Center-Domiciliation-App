---
name: heberjahiz-deploy
description: Déploie et opère l'app sur l'hébergement mutualisé Heberjahiz (app.centirio.ma) via GitHub Actions, FTPS zip+extract, baseline DB et scripts temporaires à jeton. Utiliser quand l'utilisateur parle de déploiement, deploy, push php-haja, mise en ligne, app.centirio.ma, FTP/FTPS, workflow Deploy Heberjahiz, secrets FTP, ou d'un problème visible uniquement en production.
license: MIT
compatibility: opencode
metadata:
  audience: developers
  source: Pipeline mis au point et validé le 2026-08-24 (runs GitHub 32750469649+)
---

# Skill Heberjahiz — Déploiement production app.centirio.ma

## Architecture du pipeline

```
git push origin php-haja
  └→ GitHub Actions (.github/workflows/deploy-heberjahiz.yml)   ~2 min
       1. Checkout + packaging dist_pkg/app.zip (exclusions lourdes/inutiles)
       2. Génération _deploy_extract.php depuis .github/deploy/extract_template.php
          (token = ${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT})
       3. Upload FTPS des 2 fichiers via curl -k --ssl-reqd (retry x3)
       4. POST {token} sur https://app.centirio.ma/_deploy_extract.php (retry x5)
          → extraction du zip + suppression du zip + self-delete → EXTRACT_OK
```

Pourquoi zip+extract : le serveur coupe les sessions FTP longues (`tlsv1 alert decode error`
après ~14 min multi-fichiers) et refuse les dotfiles (`553`). Un seul upload binaire contourne tout.

## Fichiers clés

| Fichier | Rôle |
|---|---|
| `.github/workflows/deploy-heberjahiz.yml` | Workflow complet (branch trigger : `php-haja`) |
| `.github/deploy/extract_template.php` | Template extracteur (placeholder `__TOKEN__`) |
| `.htaccess` (racine) | Protection web : bloque dotfiles, `_debug*`, extensions sensibles, dossiers internes ; garde `uploads/`, `assets/`, `templates/` publics |
| `docs/DEPLOIEMENT_HEBERJAHIZ.md` | Guide historique setup initial |

## Secrets GitHub (repo LearniTome/Center-Domiciliation-App)

| Secret | Valeur |
|---|---|
| `FTP_HOST` | `ftp.centirio.ma` |
| `FTP_USER` | compte FTP dédié au docroot (format `user@app.centirio.ma`) |
| `FTP_PASSWORD` | mot de passe associé |

Mise à jour (ne JAMAIS écrire ces valeurs dans un fichier versionné) :
```powershell
gh secret set FTP_HOST --repo LearniTome/Center-Domiciliation-App --body "ftp.centirio.ma"
gh secret set FTP_USER --repo LearniTome/Center-Domiciliation-App --body "<user>"
gh secret set FTP_PASSWORD --repo LearniTome/Center-Domiciliation-App --body "<pass>"
```

## Topologie serveur

- Docroot réel : `/home/centiaxh/centirio.ma/app.centirio.ma/`
- Pattern hébergeur : chaque sous-domaine vit dans `centirio.ma/<sous-domaine>/`
  (preuve : Dolibarr réel dans `centirio.ma/doli.centirio.ma/`)
- Le compte FTP de déploiement est **chrooté directement dans le docroot** :
  les uploads partent à la racine (`ftp://$HOST/fichier`), SANS préfixe de dossier
- Piège historique : un ancien compte FTP était chrooté dans son propre sous-dossier
  (`centirio.ma/Ftp-Abdeljalil/`) → uploads invisibles du web (404). Ne plus l'utiliser.
- MySQL : MariaDB mutualisée, base `centiaxh_domiciliation` (préfixe cPanel obligatoire),
  accès `localhost` uniquement depuis le PHP du serveur

## Quirks curl FTPS (obligatoires)

```bash
curl -k --ssl-reqd -T fichier "ftp://$FTP_HOST/fichier" --user "$FTP_USER:$FTP_PASSWORD"
```
- `-k` : le certificat TLS du FTP n'a pas le bon hostname (CN du domaine principal)
- `--ssl-reqd` : TLS explicite obligatoire
- Suppressions FTP : effet parfois différée — revérifier avant de conclure à un échec

## Procédure de déploiement standard

```powershell
git push origin php-haja
Start-Sleep 15
$run = gh run list --repo LearniTome/Center-Domiciliation-App --workflow "Deploy Heberjahiz" --limit 1 --json databaseId --jq '.[0].databaseId'
# poller : gh run view $run ... jusqu'à status=completed, conclusion=success
```

### Checklist post-déploiement
1. Log du run contient `EXTRACT_OK` (étape « Extraction sur le serveur »)
2. `https://app.centirio.ma/` → 302 vers `?page=connexion` puis 200
3. Page connexion SANS `flash-error` / « Connexion MySQL impossible »
   (le détail PDO est dans `<small>` après le message générique — toujours l'extraire)
4. Login fonctionnel (test POST email/password + cookie jar si besoin)

## Base de données production

- Import d'un dump local : NE JAMAIS exécuter tel quel — retirer les lignes
  `CREATE DATABASE ...` (multi-lignes ! sauter jusqu'au `;` final) et `USE \`...\`;`,
  puis se connecter en DSN `dbname=centiaxh_domiciliation`
- **Baseline obligatoire après install neuve** (import schema.sql+seed.sql) :
  insérer TOUS les noms de `database/migrations/*.sql` dans `_migrations`
  (INSERT IGNORE) — sinon le runner rejoue l'historique et casse (colonnes déjà renommées…)
- Nouvelles migrations : interdit de coder `USE center_domiciliation` ou le nom de base
  en dur — le DSN sélectionne la base (portabilité PC local ↔ prod)
- Le runner tolère déjà : 42S21, 42S01, 1091, 1061, 1826, `errno: 121`

## Scripts temporaires serveur (opérations one-shot)

Pattern éprouvé pour agir sur la prod sans SSH :
1. Générer un PHP local (token aléatoire 16 car., constantes en dur, logique transactionnelle)
2. Upload FTPS à la racine du docroot
3. `curl -sk -X POST -d "token=$tok" https://app.centirio.ma/_script.php`
4. Vérifier la sortie (`*_OK ...`), le script fait `@unlink(__FILE__)` en cas de succès
5. Si échec persistant : supprimer le fichier via FTP (DELE) avant de partir

Règles : hash_equals pour le token ; rollback SQL en transaction ; sortie texte exploitable ;
jamais de données sensibles dans la réponse ; auto-suppression systématique.

## Dépannage rapide

| Symptôme | Cause | Fix |
|---|---|---|
| Run échoue : `553` sur dotfiles | Exclusion minimatch incorrecte | Exclure `**/.dossier/**` (contenu), pas `**/.dossier*` |
| `tlsv1 alert decode error` | Session FTP longue coupée | Stratégie zip unique (déjà en place) |
| Extraction : HTTP 404 | Extracteur dans le mauvais dossier / chroot | Vérifier racine du login FTP = docroot |
| `Access denied ... to database 'center_domiciliation'` | Migration avec base en dur | Retirer USE/nom de base du .sql, pousser |
| Migration `errno: 121 Duplicate key` | FK déjà créée par schema.sql | Toléré par le runner (marquée appliquée) |
| Page templates : `[000]` intermittent | Scan ZipArchive de tous les .docx à chaque chargement | Retry ×5 espacé ; cache du scan à implémenter si gênant |
| Ancien flash-error affiché alors que la BD marche | Cache LiteSpeed de page anonyme | Tester avec query string unique (`&cb=<random>`) |

## Sécurité

- Aucun secret dans les fichiers versionnés (secrets → GitHub Secrets / `.env` gitigné)
- Mots de passe partagés en conversation → rotation recommandée via cPanel ensuite
- Comptes applicatifs : un seul superadmin actif en prod ; comptes de seed locaux
  (`@center.test`) ne doivent jamais être recréés sur le serveur
