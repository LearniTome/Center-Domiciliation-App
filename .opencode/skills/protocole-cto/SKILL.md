---
name: protocole-cto
description: Protocole de pilotage en 3 phases (Planification → Exécution → Modification chirurgicale) pour agir comme CTO sur le projet Center Domiciliation. Utiliser pour toute fonctionnalité importante, refonte ou ajout majeur.
license: MIT
compatibility: opencode
---

# Skill Protocole CTO (Triple Prompting)

Transforme la session en gestion technique structurée : planifier d'abord, exécuter sans placeholder, modifier chirurgicalement. Les commandes `/plan`, `/execute`, `/modify` encapsulent ce protocole.

## Phase 1 — Planification et conscience (`/plan`)
1. **KISS** : lister les hypothèses, proposer la solution la plus simple. Aucun fichier écrit à cette phase.
2. **Mémoire externe** : lire `AGENTS.md` (conventions/architecture) + `docs/ROADMAP.md`. Ne jamais créer de `project_map.md`.
3. **Exposition temporelle** : `php -v`, `composer show --direct`, recherche web des versions stables récentes (PHPWord, Dompdf, PhpSpreadsheet). Zéro dépendance dépréciée.
4. **Architecture** : respecter `pages/{groupe}/[_steps/]`, `src/`, `includes/`, `database/migrations/YYYYMMDD_HHMMSS_*.sql`.
5. Livrables : état des lieux, hypothèses/questions, plan détaillé, mise à jour `docs/ROADMAP.md`.

## Phase 2 — Exécution stricte (`/execute`)
1. **Anti-placeholder** : code 100% complet, production-ready, try/catch PDO. Conventions : `declare(strict_types=1)`, `e()`, CSRF, redirect-after-POST, flash, PDO nommé.
2. **Auto-validation** : `php -l` après chaque fichier ; en cas d'erreur, diagnostiquer soi-même (logs `C:\xampp\php\logs\php_error_log`) avant de demander.
3. **Synchronisation vivante** : barrer chaque tâche dans `docs/ROADMAP.md` dès qu'elle est faite ; tester avec le serveur dev (`scripts/dev-server.ps1`) et le skill manual-test avant de clore.

## Phase 3 — Modification chirurgicale (`/modify <fonctionnalité>`)
1. **Analyse d'impact** : rapport des fichiers/dépendances touchés AVANT modification.
2. **Chirurgie** : ne toucher que les fichiers concernés ; jamais de « nettoyage » hors périmètre.
3. **Anti-régression** : `php -l` + test navigateur (chrome-devtools MCP) des zones modifiées et voisines.
4. **Mémoire** : documenter dans `AGENTS.md` + `docs/ROADMAP.md`.

## Adaptations au projet (vs protocole générique)
- Mémoire externe = `AGENTS.md` (versionné, déjà structuré par domaine).
- Feuille de route/tâches = `docs/ROADMAP.md`.
- Stack fixe : PHP 8.x vanilla + MySQL (XAMPP) — pas de framework à choisir.
- Validation UI via MCP chrome-devtools, pas de suite de tests automatisée.
