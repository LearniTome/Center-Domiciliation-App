# Montée PHP 8.2 → 8.3+ (tâche 11)

> Contexte : `TODO.md` → tâche 11, issue de `docs/AUDIT.md` (§ P3, EOL 8.2).
> PHP 8.2 est en fin de support sécurité le **31/12/2026**. La prod
> (Heberjahiz, mutualisé) tourne en 8.1+ ; ce document concerne le **poste de
> développement local** (XAMPP).

## 1. Verdict de l'audit de compatibilité (27/09/2026)

Relevé sur les 110 fichiers PHP du projet (`pages/`, `includes/`, `src/`,
`ajax/`, `api.php`, `index.php`).

| Motif | Impact 8.3 | Occurrences |
|---|---|---|
| Interpolation `${var}` (dépréciée 8.2) | bloquant | **0** |
| `E_STRICT` | bloquant | **0** |
| `create_function()` / `utf8_encode()` / `utf8_decode()` | supprimées en 8.0 | **0** |
| `Reflection*` (signatures modifiées en 8.3) | — | **0** |
| `ZipArchive` (libzip) | — | usage supported (TemplateAnalyzer) |
| **Paramètre implicitement nullable** (`Type $x = null`) | déprécié en **8.4**, pas en 8.3 | **0** — corrigé depuis, voir § 1 |

**Conclusion : aucun blocage pour 8.3.** Le pas 8.2 → 8.3 est sans risque
fonctionnel ; le vrai palier est **8.4**, bloqué par les 26 paramètres
implicitement nullable (PHP 8.4 les déprécie, PHP 9 les supprimera).

## 1. Emplacements à corriger avant 8.4 — ✅ CORRIGÉ

> Re-vérifié le 2026-10-01 : les 26 emplacements ci-dessous portent **tous** déjà
> `?Type $x = null`. Contrôle refait par tokenizer PHP sur les 207 fichiers hors
> `vendor/` → **0 occurrence** de `Type $x = null` sans `?`. La liste est conservée
> comme trace, pas comme travail à faire. **Le palier 8.4 est levé.**

Emplacements (état au moment de l'audit, corrigés depuis) :

```
includes/base_donnees.php:7          includes/fonctions.php:21
includes/config_tabs.php:92          includes/fonctions.php:204
includes/fonctions.php:403           includes/fonctions.php:559
includes/fonctions.php:851           includes/fonctions.php:951
includes/fonctions.php:1314-1316     includes/fonctions.php:1351-1353
pages/templates/generation.php:45    src/analyseur_templates.php:221
src/naming_dossier.php:135, 168, 248, 249, 251, 584
src/rendu_document.php:220, 1489    src/service_claude.php:7, 8
```

Correction : `Type $x = null` → `?Type $x = null`.

## 2. Ce qui a déjà été fait pour sécuriser la montée

- **Canari CI non bloquant** — `.github/workflows/deploy-heberjahiz.yml`,
  job `compat` : la suite PHPUnit est exécutée en PHP **8.3 et 8.4** à chaque
  push, avec `continue-on-error: true`. Le job `deploy` ne dépend **que** de
  `test` (8.2, version de production) : une régression 8.3 est visible dans
  l'onglet Actions sans jamais bloquer un déploiement.
- **Script de validation post-upgrade** — `scripts/verifier_montree_php.ps1` :
  version, extensions critiques, aller-retour `ZipArchive` sur un vrai
  `.docx`, `composer check-platform-reqs`, suite PHPUnit, recherche de
  dépréciations dans les journaux. Une seule commande, sortie 0/1.

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\verifier_montree_php.ps1 `
  -PhpBin "C:\xampp83\php\php.exe"
```

## 3. Procédure d'installation (action manuelle, ~20 min)

**Installer la nouvelle XAMPP à côté** (`C:\xampp83`) et non par-dessus
`C:\xampp` : le retour arrière est immédiat, et les deux versions
coexistent pendant la validation.

1. **Relever la configuration courante** (déjà fait, à conserver) :
   `C:\xampp\php\php.ini` —
   `max_execution_time=120`, `memory_limit=512M`, `post_max_size=40M`,
   `upload_max_filesize=40M`, `max_file_uploads=20`, `default_charset="UTF-8"`,
   `date.timezone=Europe/Berlin`, `error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT`,
   `display_errors=On`, extensions activées `bz2 curl fileinfo gd gettext
   mbstring exif mysqli pdo_mysql pdo_sqlite zip php_openssl php_ftp
   php_com_dotnet`.
   > `zip` et `php_com_dotnet` sont les deux extensions **indispensables** au
   > projet (templates `.docx`, conversion DOCX → PDF via Word).
2. **Arrêter** Apache et MySQL (Control Panel XAMPP → Stop sur les deux).
3. **Installer** la nouvelle XAMPP dans `C:\xampp83` (PHP ≥ 8.3).
4. **Reporter** les lignes ci-dessus dans `C:\xampp83\php\php.ini`
   (attention : `extension_dir` et `upload_tmp_dir` doivent pointer sur
   `C:\xampp83\...`).
5. **Démarrer** MySQL puis Apache depuis le Control Panel de la nouvelle
   installation. La base `center_domiciliation` est lue depuis le même
   dossier `data/mysql` : ne rien*y toucher.
6. **Valider** :
   ```powershell
   powershell -ExecutionPolicy Bypass -File .\scripts\verifier_montree_php.ps1 -PhpBin "C:\xampp83\php\php.exe"
   ```
   Tout doit être `OK` (la seule ligne `ECHEC` acceptable avant upgrade est
   « Version PHP »).
7. **Test fonctionnel** (le seul que le script ne couvre pas) : générer un
   dossier complet depuis le wizard Création — c'est l'enchaînement
   `TemplateAnalyzer` → `DocumentRenderer` → DOCX → PDF qui exerce `zip`
   et `com_dotnet`.
8. **Basculer les scripts** : `run.ps1` et `scripts/dev-server.ps1` pointent
   sur `C:\xampp\php\php.exe` par défaut (repli auto-détecté
   `C:\xampp`, `D:\xampp`, `E:\xampp`). Passer `-XamppPath C:\xampp83` au
   lieu d'éditer les scripts ; si un chemin doit être figé, le changer dans
   les deux.
9. **Composer** : `composer install` (le `composer.lock` est compatible 8.3).
   Si `composer` signale un conflit, `composer update --no-interaction`
   puis relancer PHPUnit en local **avant** tout push.

## 4. Retour arrière

`C:\xampp` n'a pas été touché. Il suffit de relancer l'ancien Control Panel
et de retirer `-XamppPath C:\xampp83` des commandes. Aucun fichier du projet
n'est modifié par l'upgrade (hors `vendor/` réinstallable).

## 5. Critères de fin de tâche

- [ ] `verifier_montree_php.ps1` entièrement vert sur 8.3
- [ ] job `compat` vert en 8.3 **et** 8.4 sur GitHub Actions
- [ ] génération d'un dossier complète réussie (DOCX + PDF)
- [ ] import Excel et création rapide testés
- [ ] `date.timezone` corrigé en `Africa/Casablanca` si l'on veut l'heure de
      Casablanca (aujourd'hui `Europe/Berlin`, décalage d'1 h en hiver)
