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

### SaaS multi-tenancy — Architecture (2026-09-27)

#### État actuel (audit)
| Élément | Constat |
|---|---|
| RBAC | **Existe déjà** : `roles` (16), `permissions` (58), `role_permissions`, `collaborateur_permissions` (override par collaborateur) |
| Comptes de connexion | **Aucun `users`** — le login porte sur `collaborateurs` (`can_login=1`, `role_id`, `password_hash`). 1 seul compte réel en base |
| Multi-tenancy | **Aucun** : pas de `cabinets`, pas de `cabinet_id` sur les 12 tables métier |
| Bypass super-admin | `has_permission()` teste `role_id === 1` en dur (`includes/fonctions.php:1241`) |
| Isolation | **Aucune** — toutes les pages détail lisent par `?id=` sans contrôle de propriétaire |
| Périmètre d'isolation | 87 couples (fichier, table) à couvrir : societes 22, collaborateurs 16, documents_generes 13, associes 10, contrats 9, cessions 8, pv_ago 5, uploaded_docs 4 |

#### Décisions retenues (KISS, sans question utilisateur)
1. **`users` séparé de `collaborateurs`** — un cabinet n'a pas de « collaborateur de société ». `users.cabinet_id NULL` = interne Centre (non facturé).
2. **Base partagée, discriminant `cabinet_id` nullable** — les 10 467 lignes prod existantes restent à `NULL` (= Centre), aucune migration de données destructrice.
3. **`roles` étendu, pas de table neuve** — ajout de `roles.scope` (`centre`/`cabinet`) + `is_billable`. Les 16 rôles existants sont conservés (aucune régression) ; `is_internal=0` (Expert-comptable, Avocat, Notaire…) devient `scope='cabinet'`.
4. **`user_roles` canonique, `users.role_id` conservé** comme rôle primaire dénormalisé (compatibilité avec les ~16 lectures de `$user['role_id']`).
5. **Garde IDOR centrale** (`assert_tenant_access()`) plutôt que 87 éditions mécaniques : bloque la lecture croisée `?id=` en un point.
6. **Aucune Librairie ajoutée** — le besoin est de l'RBAC maison, pas d'un framework. Composer reste à 3 libs.

#### Phase 1 — Fondation données
- [ ] `20260927_100000_saas_cabinets.sql` — `cabinets`, `plans`
- [ ] `20260927_100001_saas_facturation.sql` — `abonnements`, `paiements`, `factures`
- [ ] `20260927_100002_saas_users.sql` — `users`, `user_roles`, `user_permissions` + backfill depuis `collaborateurs`
- [ ] `20260927_100003_saas_roles_permissions.sql` — `roles.scope` / `is_billable`, 6 rôles canoniques, 18 permissions SaaS, matrices
- [ ] `20260927_100004_saas_tenant_columns.sql` — `cabinet_id` + index sur les 12 tables métier
- [ ] `database/schema.sql` — refléter le nouveau schéma (source de vérité des fresh installs)

#### Phase 2 — Couche authentification
- [ ] `current_user()` → lit `users`, expose `cabinet_id`, `scope`, `role_nom`
- [ ] `current_cabinet_id()`, `is_centre_user()`, `tenant_scope_sql()`, `assert_tenant_access()`
- [ ] `get_user_permissions()` → `user_roles` + `user_permissions` (suppression du shortcut `role_id === 1` au profit de `roles.is_system`)
- [ ] `pages/auth/connexion.php` + `includes/amorcage.php` (auto-login dev) → table `users`
- [ ] `user_sessions` : purge à la déconnexion + colonne `cabinet_id`

#### Phase 3 — Isolation des données
- [ ] Garde IDOR sur toutes les pages détail (`societe`, `associe`, `contrat`, `collaborateur`, `cession_dossier`, `societe_suivi`)
- [ ] Filtre tenant sur les listes (`creations`, `domiciliations`, `societes`, `associes`, `contrats`, `collaborateurs`, `cessions`, `pv_ago`, `documents`)
- [ ] Filtre tenant sur `api.php` (quick_create, inline_update, bulk_update) + uploads

#### Phase 4 — Abonnements
- [ ] `require_active_subscription()` — blocage des utilisateurs cabinet si `abonnement` expiré/suspendu
- [ ] Contrôle des quotas plan (`max_utilisateurs`, `max_societes`, `max_dossiers`)

#### Phase 5 — Écrans d'administration
- [ ] Routes + pages : `cabinets`, `cabinet`, `plans`, `abonnements`, `abonnement`, `paiements`, `factures`, `users`, `user`, `parametres`
- [ ] Menu : section « Administration » (Super Admin), section « Mon cabinet » (Administrateur Cabinet)
- [ ] Bandeau d'état abonnement dans l'entête (J−30 / expiré / suspendu)

#### Sécurité multi-tenancy
- [ ] Toute requête métier passe par `tenant_scope_sql()` ou `assert_tenant_access()` — revue fichier par fichier
- [ ] Mot de passe obligatoire au premier login cabinet (`must_change_password`)
- [ ] Rappel : `collaborateurs.password_hash` devient inutile, à purger en phase 5

### Backend / Dépendances
- [ ] Installer XAMPP PHP 8.3+ (action manuelle) puis valider avec
      `scripts/verifier_montree_php.ps1` — procédure : `docs/MONTAJEE_PHP_83.md`
      (audit déjà fait : 0 blocage 8.3 ; canari CI 8.3/8.4 en place)
- [ ] PHP 8.4 — corriger les 26 paramètres implicitement nullable
      (`Type $x = null` → `?Type $x = null`) avant qu'ils ne deviennent des
      erreurs. Emplacements listés dans `docs/MONTAJEE_PHP_83.md` § 1

### Base de données
- [ ] Aucune migration en attente — schéma synchronisé via le système auto-migration

### Frontend / UI
- [ ] RAS — charte appliquée (skills ui-design / awesome-design)

### Suivi administratif
- [x] 2026-08-26 — Vue detail amelioree : stepper vertical, KPIs, auto-scroll vers etape courante
- [x] 2026-08-26 — Vue pipeline Kanban 3 colonnes (En attente / En cours / Termine) avec drag & drop
- [x] 2026-08-26 — Badge "En retard" sur etapes debutees depuis > 7 jours
- [x] 2026-08-26 — Toggle vue Detail / Pipeline dans la barre d'actions
- [x] 2026-08-26 — PDF recapitulatif client (Dompdf) : plan de travail complet, prochaines etapes, delais estimes
- [x] 2026-08-26 — Sidebar : Suivi Creations + Suivi Domiciliations dans section Dossiers
- [x] 2026-08-26 — Navigation supporte params query (type=creation|domiciliation)
- [x] 2026-09-13 — Workflow domiciliation reél en 12 étapes (Récupération docs → Vérification → Remplir → Envoi contrats → Retour légalisés → Légalisation attestations → Appel/remise → Attestation d'enregistrement 48h → Dossier final 10-20j → Impression → Classement → Archivage cloud) : labels/icônes/suggestions docs, seeding wizard, reset des sociétés existantes (migration 20260913_000001), bandeau + puce rouge échéances expirées (CIN gérants, certificat négatif), PDF suivi 12 étapes + délais, fix collaborateur via collaborateurs.societe_id — déployé en prod (10467db), validé visuellement

### Qualité
- [x] Suite de tests PHPUnit 11 sur `src/` : TemplateAnalyzer (extraction/rename/delete) + DocumentRenderer (rendu `_VAR_`, fusion split-runs, boucle cession_parts) — `vendor/bin/phpunit` (15 tests)
- [x] 2026-09-27 — Vérification manuelle avant commit (skill manual-test) : `php -l` sur les 7 fichiers modifiés, 101 tests PHPUnit verts, parcours navigateur sur le serveur de dev
      (modale quick-create collaborateur, dialogues de confirmation, cartes KPI du suivi des contrats, wizard étape 1) — 0 erreur console. Défaut corrigé au passage : la création rapide
      n'insérait jamais de ligne dans les listes (`<template data-row-template>` est un frère de `<table>`, `buildRow` ne le trouvait pas → toast de succès sans ligne visible) ;
      la branche liste recharge désormais la page, la branche `<select>` du wizard reste sans rechargement
- [ ] Vérification manuelle avant commit : skill manual-test (à rejouer à chaque lot)

## Tâches terminées

- [x] 2026-09-27 — Modale de création rapide : en-tête sticky (17 champs pour un collaborateur), icône + libellé de bouton paramétrable (`$quickCreateSubmitLabel`), astérisques sur les champs obligatoires, `role="dialog"` + `aria-modal` + `aria-labelledby`, focus sur le premier champ à l'ouverture et rendu à l'ouvreur à la fermeture
- [x] 2026-09-27 — Erreurs de création rapide affichées **dans** la modale (`.qc-alert`) : le toast était peint sous l'overlay (z-index 2000 < 9999) donc illisible ; le message se masque à la frappe ou via la croix
- [x] 2026-09-27 — `data-confirm-tone="primary"` : les confirmations non destructives (réinitialisation d'assistant, import Excel, rétablissement de contrat) ne sont plus affichées en rouge destructif
- [x] 2026-09-27 — Suivi des contrats : les 4 compteurs `.stat` remplacés par les cartes KPI du tableau de bord (`.dash-metric`), chacune étant un lien vers sa vue + `aria-current` ; nouvelle teinte `.dm-icon-sec`
- [x] 2026-08-21 — Fix collisions de tokens dans le rendu : `_SOCIETE_ADRESSE_` consommait `_SOCIETE_ADRESSE_SIEGE_` (restait « SIEGE_ ») et `_a.NOM_` mangeait `_a.NOM_COMPLET_` — tri par longueur décroissante dans replaceValues + réordonnancement boucle associes ; token malformé corrigé dans SARL AU/2026-07 Statuts ; 2 tests de régression
- [x] 2026-08-21 — Fix generation.php : l'admin (`collaborateur_type` NULL) ne voyait que les templates Domiciliation — les admins/interne voient désormais Creation + Domiciliation
- [x] 2026-08-21 — PHPUnit 11.5.56 (require-dev) + tests TemplateAnalyzer / DocumentRenderer
- [x] 2026-08-21 — Dompdf 3.1.5 → 3.1.6 : corrige 6 CVE (lecture de fichier local via SVG data-URI, fuite filesystem, DoS images surdimensionnées, contournement chroot)
- [x] 2026-08-21 — PhpSpreadsheet 5.8.0 → 5.9.0 (`composer audit` : 0 vulnérabilité restante)
- [x] 2026-08-21 — Conversion des variables templates `{{ VAR }}` → `_VAR_` (29 .docx, 589 variables) + adaptation DocumentRenderer / TemplateAnalyzer / UI
- [x] 2026-08-21 — Protocole CTO : commandes `/plan` `/execute` `/modify` + skill `protocole-cto`
