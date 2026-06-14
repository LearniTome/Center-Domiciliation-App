# Analyse du projet — Center Domiciliation App

> Généré le 13/05/2026 — analyse complète frontend + backend

## Sommaire

- [Architecture actuelle](#1-architecture-actuelle)
- [Problèmes constatés](#2-problèmes-constatés)
- [Améliorations proposées](#3-améliorations-proposées)
- [Todo liste](#-todo-liste)

---

## 1. Architecture actuelle

| Couche | Stack | État |
|--------|-------|------|
| **Routing** | Front controller `index.php?page=` + allowlist (21 pages) | OK |
| **Pages** | 21 fichiers dans `pages/` (moy. 300-949 lignes) | PHP/HTML mélangé |
| **Couche DB** | PDO via `includes/base_donnees.php` singleton, paramètres nommés | OK |
| **CSS** | ~1116 lignes, custom design system dark, variables CSS | Bon |
| **JS** | ~638 lignes vanilla : wizard, calculs loyers, auto-fill, drag-drop, sidebar toggle, column toggle, capital SARL | OK |
| **Templates doc** | 3 classes dans `src/` (TemplateAnalyzer, DocumentRenderer, TemplateEditor) | Redondant |
| **CSRF** | Token + `verify_csrf()` sur tous les POST | OK |
| **Sessions** | Flash messages, wizard creation 3 étapes | OK |
| **Sécurité** | `htmlspecialchars` via `e()`, PDO préparé, allowlist tables | Satisfaisant |

### Structure fichiers

```
├── index.php              # Front controller (21 pages)
├── AGENTS.md              # Instructions projet
├── CLAUDE.md              # Mémoire projet
├── run.ps1                # Lanceur XAMPP
├── opencode.json          # Config MCP memory
├── config/
│   ├── app.php            # app_name, base_url
│   ├── database.php       # Credentials MySQL
│   └── defaults.json      # Valeurs par défaut wizard
├── includes/
│   ├── amorcage.php          # Session, config, DB
│   ├── fonctiobs.php         # 20 helpers
│   ├── base_donnees.php      # Singleton PDO
│   ├── entete.php            # <head> + sidebar
│   ├── navigation.php        # Navigation sidebar
│   └── pied_page.php         # Scripts + fermeture
├── pages/                    # Pages organisées en sous-dossiers
│   ├── accueil/
│   │   ├── dashboard.php     # Tableau de bord
│   │   └── notifications.php
│   ├── dossiers/
│   │   ├── societes_liste.php       # Liste + recherche + CSV
│   │   ├── societe_details.php      # Détail + édition
│   │   ├── associes_liste.php
│   │   ├── associe_details.php
│   │   ├── contrats_liste.php
│   │   ├── collaborateurs_liste.php
│   │   ├── collaborateur_details.php
│   │   └── creation_steps/          # Wizard création 6 étapes
│   ├── modification-juridique/
│   │   ├── modifications_juridiques.php
│   │   └── cession/                 # Cession de parts sociales
│   ├── templates/
│   │   ├── templates.php
│   │   ├── generation.php
│   │   └── documents.php
│   ├── outils/
│   │   ├── analyse-couverture.php
│   │   ├── variables.php
│   │   └── ai-assistant.php
│   ├── configuration/        # Gestion onglets + tables ref
│   └── auth/                 # Connexion, déconnexion, 404
├── assets/
│   ├── css/app.css        # Design system 982 lignes
│   └── js/app.js          # Scripts 419 lignes
├── src/                   # Classes templates
│   ├── analyseur_templates.php
│   ├── rendu_document.php
│   └── editeur_templates.php
├── database/
│   ├── schema.sql         # 11 tables + FK + index
│   └── seed.sql
└── templates/             # Templates DOCX
```

### Base de données

11 tables : `societes`, `associes`, `contrats`, `collaborateurs`, `documents_generes` + 6 tables de référence (`ref_formes_juridiques`, `ref_villes`, `ref_tribunaux`, `ref_nationalites`, `ref_lieux_naissance`, `ref_ste_adresses`, `ref_qualites_associe`, `ref_activites`).

---

## 2. Problèmes constatés

### Frontend

- **Bug `adresse` dupliquée** dans `testData` (JS lignes 25 et 64) — la 2e écrase la 1re
- **Montants sans séparateur de milliers** — `total-capital`, `total-parts`, loyers readonly affichés sans espace (`100000` au lieu de `100 000,00`)
- **`document.execCommand()`** utilisé dans `template_edit.php` — API dépréciée par tous les navigateurs
- **Aucun loader / feedback** — les soumissions POST n'ont pas d'indicateur de chargement
- **Responsive limité** — un seul breakpoint à 980px qui met tout en 1 colonne brutalement
- **Pas de validation frontend** — les formulaires n'ont que `required` nu, pas de pattern regex
- **CSS inline** dans `configuration.php` (lignes 321-331, 235-319) — devrait être dans `app.css`
- **Pas d'autocomplete** sur les champs de recherche
- **Pas de debounce** sur les calculs de loyer en JS

### Backend

- **`creation_steps/_main.php` : 949 lignes** — logique POST + requêtes DB + HTML + template JS, tout dans le même fichier
- **6 pages redondantes** (`villes.php`, `nationalites.php`, `lieux-naissance.php`, `adresses.php`, `formes-juridiques.php`, `qualites-associe.php`) — elles dupliquent exactement les onglets de `configuration.php`
- **SQL potentiellement injectable** dans `dashboard_count()` (`fonctions.php:107`) et `fetch_all_records()` (`fonctions.php:122`) — concaténation directe du nom de table (protégé partiellement par allowlist, mais pas de `prepare()`)
- **Aucune pagination** — les listes chargent `SELECT * FROM table` sans LIMIT
- **Duplication ZIP** — les 3 classes `src/` ont une logique de décompression quasi identique
- **Erreurs SQL exposées** — `$exception->getMessage()` est affiché à l'utilisateur via `set_flash('error', ...)`
- **Pas de validation email serveur** — stocké tel quel sans `filter_var()`
- **Credentials en dur** — `config/database.php` avec `password: ''` en clair
- **Pas de journalisation** — aucune erreur n'est loggée dans un fichier
- **Index manquants** — pas d'index sur `raison_sociale` (pourtant utilisé dans les recherches et JOIN)

---

## 3. Améliorations proposées

### Frontend — Haute priorité

| # | Amélioration | Fichiers impactés | Effort |
|---|-------------|-------------------|--------|
| 1 | **Responsive multi-breakpoints** (768, 1024, 1280px) | `app.css` | 2h |
| 2 | **Indicateurs de chargement** sur soumissions POST | `app.js`, `entete.php` | 1h |
| 3 | **Validation formulaires client** (regex email, téléphone, ICE, RC, IF) | Toutes les pages avec formulaires | 2h |
| 4 | **Correction bug `adresse` dupliquée** dans testData | `app.js:25,64` | 5min |
| 5 | **Remplacer `document.execCommand()`** par Clipboard API | `template_edit.php` | 30min |
| 6 | **Autocomplete** sur barres de recherche | `societes_liste.php`, `associes_liste.php`, etc. | 1h |
| 7 | **Notifications toast** plutôt que flash repositionnables | `entete.php`, `app.js`, `app.css` | 1h30 |
| 8 | **Extraire CSS inline** de `configuration.php` vers `app.css` | `configuration.php`, `app.css` | 30min |
| 9 | **Debounce sur calculs loyers** en JS | `app.js` | 15min |

### Backend — Haute priorité

| # | Amélioration | Fichiers impactés | Effort |
|---|-------------|-------------------|--------|
| 1 | **Pagination** (LIMIT/OFFSET) sur toutes les listes | `societes_liste.php`, `associes_liste.php`, `contrats_liste.php`, `collaborateurs_liste.php`, `fonctions.php` | 3h |
| 2 | **Refactor `creation_steps/_main.php`** — extraire handlers POST dans un fichier séparé | `creation_steps/_main.php` → `handlers/creation.php` | 2h |
| 3 | **Supprimer 6 pages redondantes** + redirection vers `configuration.php?tab=` | `villes.php`, `nationalites.php`, etc. + `index.php` | 30min |
| 4 | **Sécuriser `dashboard_count()` et `fetch_all_records()`** avec `prepare()` | `fonctions.php` | 30min |
| 5 | **Masquer les erreurs SQL** en production | `creation_steps/_main.php`, `societe.php`, toutes les pages avec catch | 30min |
| 6 | **Dédupliquer logique ZIP** — classe utilitaire unique | `src/*.php` → `src/ZipHelper.php` | 1h |
| 7 | **Validation email serveur** avec `filter_var()` | Tous les handlers POST | 1h |
| 8 | **Logger les erreurs** dans un fichier (`logs/error.log`) | `amorcage.php` | 30min |
| 9 | **Ajouter index DB manquants** — `raison_sociale`, `cin`, `email` | `schema.sql`, migration | 15min |

### Architecture — Priorité moyenne

| # | Amélioration | Effort |
|---|-------------|--------|
| 1 | **Autoloader PSR-4** pour les classes `src/` (actuellement `require` manuel) | 30min |
| 2 | **Dockeriser** — `docker-compose.yml` avec PHP 8 + MySQL + phpMyAdmin | 1h |
| 3 | **Tests unitaires** sur les helpers `fonctions.php` (PHPUnit ou simple assert) | 2h |
| 4 | **Système de logs structuré** — remplacer les `error_log()` dispersés | 1h |
| 5 | **Config via variables d'environnement** — déplacer credentials DB | 1h |

---

## ✅ Todo liste

### Frontend

- [ ] **F-1** Responsive multi-breakpoints (768, 1024, 1280px) — `app.css`
- [ ] **F-2** Indicateurs de chargement sur soumissions POST — `app.js`, `entete.php`
- [ ] **F-3** Validation formulaires client (regex) — toutes les pages
- [ ] **F-4** Corriger bug `adresse` dupliquée dans testData — `app.js:25,64`
- [x] **F-4b** Ajout hidden `forme_juridique` à l'étape 2 — `creation_steps/_main.php`
- [x] **F-4c** Helper JS `formatFR()` + séparateur milliers sur montants — `app.js`
- [x] **F-4d** Helper PHP `parse_money()` + `format_money()` — `fonctions.php`
- [x] **F-4e** Readonly loyers `type="text"` pour afficher format fr-FR — `creation_steps/_main.php`
- [x] **F-4f** Capital SARL : distribution auto capital/parts/%, édition bidirectionnelle — `app.js`, `creation_steps/_main.php`
- [x] **F-4g** Colonnes toggle (afficher/masquer) avec sauvegarde localStorage — `app.js`, `societes_liste.php`, `associes_liste.php`, `contrats_liste.php`, `collaborateurs_liste.php`, `app.css`
- [x] **F-4h** Sidebar collapse toggle + scroll contenu uniquement — `navigation.php`, `app.css`, `app.js`
- [x] **F-4i** Disable bouton Ajout associé pour SARL AU — `app.js`, `app.css`
- [x] **F-4j** Wrap table-scroll sur associes + contrats — `associes_liste.php`, `contrats_liste.php`
- [ ] **F-5** Remplacer `document.execCommand()` par Clipboard API — `template_edit.php`
- [ ] **F-6** Autocomplete sur barres de recherche — pages listes
- [ ] **F-7** Notifications toast au lieu de flash — `entete.php`, `app.js`, `app.css`
- [ ] **F-8** Extraire CSS inline de `configuration.php` — `app.css`
- [ ] **F-9** Debounce sur calculs loyers JS — `app.js`

### Backend

- [ ] **B-1** Pagination LIMIT/OFFSET — pages listes + `fonctions.php`
- [ ] **B-2** Refactor `creation_steps/_main.php` — extraire handlers POST
- [ ] **B-3** Supprimer 6 pages redondantes + redirections
- [ ] **B-4** Sécuriser `dashboard_count()` / `fetch_all_records()` — `fonctions.php`
- [ ] **B-5** Masquer les erreurs SQL en production
- [ ] **B-6** Dédupliquer logique ZIP — `src/ZipHelper.php`
- [ ] **B-7** Validation email serveur (`filter_var`)
- [ ] **B-8** Logger les erreurs dans `logs/error.log`
- [ ] **B-9** Ajouter index DB manquants

### Architecture

- [ ] **A-1** Autoloader PSR-4 pour `src/`
- [ ] **A-2** Dockeriser l'environnement
- [ ] **A-3** Tests unitaires sur `fonctions.php`
- [ ] **A-4** Système de logs structuré
- [ ] **A-5** Config via variables d'environnement
