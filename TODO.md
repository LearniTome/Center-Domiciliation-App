# TODO — Assainissement post-audit (2026-09-16)

> Source : docs/AUDIT.md
> Branch : main == php-haja (fb18fec, identiques)
> ⚠️ Tout push sur main déclenche le déploiement prod (workflow Deploy Heberjahiz)
>
> Vérif doublons numéros dossier : **local = 0 doublon** (OK pour UNIQUE).
> ⚠️ Migration index/UNIQUE appliquée en LOCAL — avant déploiement prod, re-vérifier les doublons sur la base prod.
> Vérif prod bloquée : accès MySQL prod refusé (Access denied pour `centiaxh_dom_user` depuis cette IP — à whitelister dans cPanel « MySQL distant »).

## P0 — Corrections critiques DB

- [x] Tâche 1 — Corriger la migration cassée `20260612_000004_societe_source.sql`
      (colonnes `permission_label`/`permission_category` → `nom`/`permission_key`/`category`/`description`)
      → déployer en premier, vérifier en prod que `modifications.view` est créée
- [x] Tâche 2 — Régénérer `database/schema.sql` + `import.sql` depuis la vraie base (21→33 tables)
      → validé sur base jetable (import complet OK, 33 tables, seeds OK)
- [x] Tâche 3 — Migration index/UNIQUE `20260916_000001_indexes_audit.sql` :

      UNIQUE sur societe_dossier_domiciliation_number / societe_dossier_creation_number
      INDEX sur type_generation, raison_sociale, date_exp_cert_neg, date_validite_cin, cin,
             date_fin (contrats), statut (contrats), email (collaborateurs)
      → appliquée + vérifiée en local (0 doublon) ; intégrée dans schema.sql/import.sql

## P1 — CI + nettoyage

- [x] Tâche 4 — Ajouter le job PHPUnit au workflow (bloque le deploy si test KO)
      → job `test` ajouté, `deploy` a `needs: test` ; YAML validé
- [x] Tâche 5 — `git rm --cached` : database/exports/ (7), backups/ (14), dossiers_generer/ (183),
      uploads/dossiers/ + uploads/tmp/ (13, données clients), assets/fonts/*.woff2, composer.phar
      (+ `.gitignore` complété) — fichiers conservés sur disque, uniquement déversionnés
- [x] Tâche 6 — Corriger AGENTS.md (mention `page_views` → `activity_logs`, signature `log_page_view`)

## P2 — Sécurité / accessibilité / maintenance

- [x] Tâche 7 — Whitelist `dashboard_count()` dans fonctions.php
      (pattern identique à fetch_all_records/fetch_record — helper inutilisé mais assaini)
- [x] Tâche 8 — Focus trapping + aria-modal sur les modales
      (MutationObserver universel dans app.js : role dialog, aria-modal, aria-labelledby,
       trap Tab/Shift+Tab, retour du focus à l'ouvreur — couvre .open/.show/.active,
       sans toucher aux call sites des modales)
- [x] Tâche 9 — Consolidation doublons CSS (stats, modal-overlay, perms-table)
      (2 blocs .stats fusionnés ; 2 définitions .modal-overlay unifiées → .show corrige le
       doc-viewer invisible (opacity 0) ; doublons checkbox/select-all-toggle/.perm-cell.empty supprimés)
- [x] Fix CI (exit 126) — `vendor/bin/phpunit` + `vendor/bin/php-parse` committés sans bit +x
      (mode 100644) → `./vendor/bin/phpunit` non exécutable sur le runner Linux.
      Corrigé via `git update-index --chmod=+x` (mode 100755) — run de vérification SUCCESS.
      ⚠️ Ne jamais ré-ajouter vendor/ sans préserver le bit +x (git sur Windows ne le traque pas).

## P3 — Améliorations

- [ ] Tâche 10 — Rate-limiting connexion (5 tentatives → délai croissant)
- [ ] Tâche 11 — Montée PHP 8.2 → 8.3+ (local uniquement, tester zip/com_dotnet)

---
## Ordre d'exécution recommandé
1. ✅ Tâche 1 (fix migration) → appliquée et vérifiée localement
2. ✅ Tâche 2 (schema.sql + import.sql) → validé sur base jetable
3. ✅ Tâche 3 (index/UNIQUE) → appliquée en local (re-vérif doublons prod avant deploy)
4. ✅ Tâche 4 (CI + PHPUnit) → job `test` bloque le deploy ; tests verts en local
5. ✅ Tâche 5 (nettoyage git) → déversionnés (fichiers conservés sur disque, `.gitignore` à jour)
6. ✅ Tâche 6 (AGENTS.md) → mention corrigée
7. ✅ Tâche 7 (dashboard_count) → whitelisted
8. ✅ Tâche 8 (focus trap + aria) → MutationObserver universel dans app.js
9. ✅ Tâche 9 (CSS dedup) → stats, modal-overlay (.show réparé), perms-table
10. ✅ Fix CI exit 126 → bit +x sur vendor/bin (phpunit, php-parse) ; run SUCCESS (test + deploy)
11. ⏳ Reste : P3 (Tâches 10-11) au fil des semaines

## ⚠️ Avant push/deploy prod
- Re-vérifier les doublons sur les numéros de dossier **en prod** (la migration UNIQUE échouera proprement si doublons).
- Vérifier en prod que `modifications.view` existe (Tâche 1).
- Le workflow déploie uniquement si les tests PHPUnit passent (désormais).