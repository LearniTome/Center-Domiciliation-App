# Feuille de route — Center Domiciliation App

> Mémoire de planification du protocole CTO (`/plan`, `/execute`, `/modify`).
> Les conventions et l'architecture vivent dans `AGENTS.md` — ce fichier suit uniquement les tâches.

## Audit technique (2026-08-21)

| Composant | Installé | Dernière stable | Statut |
|---|---|---|---|
| PHP (XAMPP) | 8.2.12 | 8.3.x / 8.4.x | ⚠️ 8.2 support sécurité jusqu'à fin 2026 |
| phpoffice/phpword | 1.4.0 | 1.4.0 | ✅ à jour |
| dompdf/dompdf | 3.1.6 | 3.1.6 | ✅ à jour (CVE corrigées) |
| phpoffice/phpspreadsheet | 5.9.0 | 5.9.0 | ✅ à jour |
| Extensions PHP | zip, pdo_mysql, com_dotnet, curl, gd, mbstring | — | ✅ toutes actives |

**Structure** : 77 fichiers PHP dans `pages/` (7 groupes + `_steps/`), `src/` (renderer, analyseur, éditeur, services), `includes/`, `database/migrations/` (auto-migration au chargement).

## Tâches en attente

### Backend / Dépendances
- [ ] Planifier la montée PHP 8.2 → 8.3+ (nouvelle version XAMPP ; tester zip/com_dotnet après)

### Base de données
- [ ] Aucune migration en attente — schéma synchronisé via le système auto-migration

### Frontend / UI
- [ ] RAS — charte appliquée (skills ui-design / awesome-design)

### Qualité
- [x] Suite de tests PHPUnit 11 sur `src/` : TemplateAnalyzer (extraction/rename/delete) + DocumentRenderer (rendu `_VAR_`, fusion split-runs, boucle cession_parts) — `vendor/bin/phpunit` (15 tests)
- [ ] Vérification manuelle avant commit : skill manual-test

## Tâches terminées

- [x] 2026-08-21 — Fix generation.php : l'admin (`collaborateur_type` NULL) ne voyait que les templates Domiciliation — les admins/interne voient désormais Creation + Domiciliation
- [x] 2026-08-21 — PHPUnit 11.5.56 (require-dev) + tests TemplateAnalyzer / DocumentRenderer
- [x] 2026-08-21 — Dompdf 3.1.5 → 3.1.6 : corrige 6 CVE (lecture de fichier local via SVG data-URI, fuite filesystem, DoS images surdimensionnées, contournement chroot)
- [x] 2026-08-21 — PhpSpreadsheet 5.8.0 → 5.9.0 (`composer audit` : 0 vulnérabilité restante)
- [x] 2026-08-21 — Conversion des variables templates `{{ VAR }}` → `_VAR_` (29 .docx, 589 variables) + adaptation DocumentRenderer / TemplateAnalyzer / UI
- [x] 2026-08-21 — Protocole CTO : commandes `/plan` `/execute` `/modify` + skill `protocole-cto`
