---
name: gitleaks
description: Détecte les secrets (clés API, mots de passe, tokens) dans le code source et l'historique Git avec Gitleaks
license: MIT
compatibility: opencode
---

# Skill Gitleaks

Détecte les secrets exposés dans le code et l'historique Git.

## Installation

Binaire : `C:\tools\gitleaks.exe` (ajouté au PATH).

## Utilisation

### Scan de l'historique Git complet (recommandé)
```powershell
gitleaks detect --source . --verbose
```

### Scan uniquement les changements non commités
```powershell
gitleaks protect --staged --verbose
```

### Scan avec rapport JSON
```powershell
gitleaks detect --source . --report-path gitleaks-report.json --report-format json
```

### Scan de l'historique avec limitation de profondeur
```powershell
# Les 100 derniers commits
gitleaks detect --source . --log-opts="-100"

# Depuis une date
gitleaks detect --source . --log-opts="--since=2025-01-01"
```

### Scan d'un commit spécifique
```powershell
gitleaks detect --source . --log-opts="abc123..def456"
```

## Fichier de configuration

### `.gitleaks.toml` (à la racine du projet)
```toml
title = "Center Domiciliation - Gitleaks config"

# Permita: ignorer des fichiers spécifiques
[[allowlist]]
description = "Allowlist .env.example"
paths = [
  '''\.env\.example$''',
  '''\.opencode/''',
  '''graphify-out/''',
  '''vendor/''',
  '''node_modules/''',
  '''docs/''',
]

# Règles personnalisées pour ce projet
[[rules]]
id = " Centre-affaires-api-key"
description = "Détecte les clés API Anthropic dans le code"
regex = '''(?:ANTHROPIC_API_KEY|anthropic_api_key)\s*[:=]\s*['"]?sk-ant-[a-zA-Z0-9\-_]+'''
tags = ["api-key", "anthropic"]

[[rules]]
id = "ftp-credentials"
description = "Détecte les identifiants FTP en dur"
regex = '''(?:FTP_PASSWORD|FTP_PASS)\s*[:=]\s*['"][^'"]+['"]'''
tags = ["credential", "ftp"]
```

## Règles par défaut

Gitleaks détecte automatiquement :
- **Clés API** : AWS, Google, Azure, Anthropic, OpenAI, etc.
- **Tokens** : GitHub tokens, JWT, bearer tokens
- **Mots de passe** : chaînes assignées à des variables de type password/secret
- **Clés privées** : RSA/EC/DSA private keys (-----BEGIN)
- **URI avec credentials** : `mysql://user:pass@host`, `ftp://user:pass@host`
- **Connection strings** : DSN, URLs de connexion

## Ignorer des résultats

### Baseline (résultats connus)
```powershell
# Générer un fichier baseline des résultats existants
gitleaks detect --source . --report-path gitleaks-baseline.json
# Utiliser la baseline pour ignorer les résultats connus
gitleaks detect --source . --baseline-path gitleaks-baseline.json
```

### Ignorer un commit spécifique
```powershell
gitleaks protect --staged --redact
```

## Sorties

| Flag | Usage |
|------|-------|
| `--verbose` | Détails dans le terminal |
| `--report-path X.json` | Rapport JSON complet |
| `--report-format sarif` | Format SARIF (GitHub) |
| `--redact` | Masque les secrets dans la sortie |

## Workflow recommandé

1. **Pré-commit** : `gitleaks protect --staged` — bloque si secret détecté
2. **Pré-push** : `gitleaks detect --source . --log-opts="$(git log --oneline -10 --format=%H)"` — scan récent
3. **Audit complet** : `gitleaks detect --source .` — scan tout l'historique
4. **Après ajout de config** : régénérer la baseline

## Sécurité

- Ne jamais commiter de `.env` avec de vrais secrets
- Les secrets dans `.env` doivent être dans `.gitignore`
- Si un secret est trouvé dans l'historique : le révoquer immédiatement + nettoyer l'historique avec `git filter-branch` ou `BFG`
