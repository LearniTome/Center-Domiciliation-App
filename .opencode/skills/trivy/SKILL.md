---
name: trivy
description: Scanner de vulnérabilités et de dépendances avec Trivy — détecte CVE, dépendances obsolètes, secrets et mauvaises configurations
license: MIT
compatibility: opencode
---

# Skill Trivy

Scanner de vulnérabilités tout-en-un : dépendances, secrets, configuration, licences.

## Installation

Binaire : `C:\tools\trivy.exe` (ajouté au PATH).

## Utilisation

### Scan des dépendances PHP (composer.json)
```powershell
trivy fs --scanners vuln,secret,license . --severity HIGH,CRITICAL
```

### Scan complet du projet (recommandé)
```powershell
trivy fs --scanners vuln,secret,config,license . --severity HIGH,CRITICAL
```

### Scan des vulnérabilités uniquement
```powershell
trivy fs --scanners vuln . --severity HIGH,CRITICAL
```

### Scan des secrets
```powershell
trivy fs --scanners secret . --severity HIGH,CRITICAL
```

### Scan de configuration (misconfiguration)
```powershell
trivy config . --severity HIGH,CRITICAL
```

### Scan avec rapport JSON
```powershell
trivy fs --scanners vuln,secret,license . --format json --output trivy-report.json
```

### Scan de déploiement (si Dockerfile existe)
```powershell
trivy image --scanners vuln <image-name>:latest
```

## Scanners disponibles

| Scanner | Usage | Ce qu'il détecte |
|---------|-------|-----------------|
| `vuln` | Vulnérabilités | CVE dans les dépendances (composer.lock, npm) |
| `secret` | Secrets | Clés API, tokens, mots de passe exposés |
| `config` | Configuration | Mauvaises pratiques de config (Dockerfile, IaC) |
| `license` | Licences | Licences non compatibles ou risquées |

## Fichiers analisés par Trivy

### PHP (ce projet)
- `composer.json` / `composer.lock` → vulnérabilités des packages PHP
- `*.php` → secrets hardcodés
- `.env` → secrets exposés

### Générique
- `Dockerfile` → mauvaises pratiques de conteneurisation
- `*.yml` / `*.yaml` → configuration Kubernetes/Docker Compose
- `*.tf` → Terraform misconfigurations

## Ignorer des résultats

### Fichier `.trivyignore`
```
# Ignorer un CVE spécifique
CVE-2024-XXXXX

# Ignorer un advisory
GHSA-xxxx-xxxx-xxxx
```

### Ignorer par sévérité
```powershell
trivy fs --severity CRITICAL .  # Uniquement CRITICAL
```

### Ignorer par package
```powershell
trivy fs --ignore-unfixed .  # Ignore les vulnés sans fix disponible
```

## Sorties

| Flag | Usage |
|------|-------|
| `--severity HIGH,CRITICAL` | Filtrer par sévérité |
| `--format json` | Sortie JSON |
| `--format sarif` | Format SARIF (GitHub Security) |
| `--output X.json` | Écrire dans un fichier |
| `--quiet` | Réduire le bruit |
| `--ignore-unfixed` | Ignorer les vulnés sans patch |

## Workflow recommandé

1. **Pré-commit** : `trivy fs --scanners vuln,secret --severity CRITICAL .`
2. **CI/CD** : scan complet avec rapport SARIF
3. **Audit** : `trivy fs --scanners vuln,secret,config,license .`
4. **Après mise à jour Composer** : `trivy fs --scanners vuln .`

## Composer audit (alternative)

Trivy utilise aussi les données de `composer audit` :
```powershell
composer audit --format=json
```

## Rapport typique

```
app.centirio.ma (composer.lock)
=================================
Total: 3 (HIGH: 2, CRITICAL: 1)

+-----------------------------------+------------------+----------+-------------------+---------------+
|             Library               | Vulnerability ID | Severity |  Installed Version | Fixed Version |
+-----------------------------------+------------------+----------+-------------------+---------------+
| phpoffice/phpword                 | CVE-2024-XXXXX  | HIGH     | 0.18.3            | 0.18.4        |
+-----------------------------------+------------------+----------+-------------------+---------------+
```

## Mise à jour des bases de données

Trivy télécharge automatiquement les bases de données de vulnérabilités. Pour forcer :
```powershell
trivy --download-db-only
```
