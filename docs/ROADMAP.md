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
