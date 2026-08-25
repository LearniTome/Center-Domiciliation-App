---
name: semgrep
description: Analyse de code statique (SAST) avec Semgrep — détecte vulnérabilités, bugs et anti-patterns dans le code PHP/JS/HTML
license: MIT
compatibility: opencode
---

# Skill Semgrep

Analyse statique du code pour détecter les failles de sécurité et les bugs courants.

## Installation

```powershell
pip install semgrep
```

Binaire : `semgrep` (ajouté au PATH via pip).

## Utilisation

### Scan rapide (recommandé)
```powershell
# Scan PHP avec les règles p/security-audit + p/php
semgrep --config "p/php" --config "p/owasp-top-ten" --config "p/security-audit" --severity ERROR --severity WARNING .

# Scan uniquement les fichiers modifiés (depuis git)
semgrep --config "p/php" --config "p/security-audit" --severity ERROR $(git diff --name-only HEAD~1 -- '*.php')
```

### Scan complet du projet
```powershell
semgrep --config "p/php" --config "p/owasp-top-ten" --config "p/security-audit" --config "p/javascript" --severity WARNING .
```

### Scan avec output JSON (pour intégration)
```powershell
semgrep --config "p/php" --config "p/security-audit" --json --output semgrep-results.json .
```

### Scan ciblé sur des fichiers spécifiques
```powershell
semgrep --config "p/php" pages/configuration/centre.php includes/fonctions.php
```

## Règles recommandées pour ce projet

| Config | Usage |
|--------|-------|
| `p/php` | Règles spécifiques PHP (injection SQL, XSS, file upload) |
| `p/security-audit` | Audit de sécurité général |
| `p/owasp-top-ten` | Top 10 OWASP |
| `p/javascript` | Règles JS (si applicable) |
| `p/dockerfile` | Si Dockerfile présent |

## Patterns critiques pour PHP vanilla

Semgrep détecte automatiquement :
- **SQL injection** : concaténation dans requêtes SQL au lieu de requêtes préparées
- **XSS** : `echo $_GET[...]` sans `htmlspecialchars()`
- **CSRF** : formulaires POST sans token
- **File upload** : uploads sans validation MIME/taille
- **Path traversal** : `include($_GET['page'])` sans allowlist
- **Hardcoded secrets** : mots de passe en dur dans le code

## Ignorer des résultats

### Fichier `.semgrepignore` (à la racine du projet)
```
vendor/
node_modules/
graphify-out/
*.min.js
database/
tests/
docs/
```

### Ignorer une règle spécifique
```powershell
semgrep --config "p/php" --exclude-rule php.lang.security.injection.sql-injection .
```

### Ignorer un fichier
```powershell
semgrep --config "p/php" --exclude "includes/ancien_code.php" .
```

## Sortie

- **Terminal** : résultats colorés avec fichier:ligne:rule
- **JSON** : `--json --output results.json` pour CI/CD
- **SARIF** : `--sarif --output results.sarif` pour GitHub Security

## Intégration workflow

Utiliser après chaque modification importante :
1. Avant commit : `semgrep --config "p/php" --severity ERROR .`
2. CI/CD : scan complet en warning
3. Audit sécurité : scan avec toutes les configs
