# Center Domiciliation App — PHP Project Guide

## Overview
Vanilla PHP 8.x procedural app for managing company domiciliation dossiers. No framework, no autoloader. Runs on XAMPP (Apache + MySQL). Composer used for PHPWord + Dompdf (DOCX→PDF fallback) + PhpSpreadsheet (Excel export/import).

## Architecture
- **Routing**: Single front controller `index.php?page=` with an allowlist of pages + `$pageDir` mapping → subdirectory
- **Globals available in pages**: `$pdo` (PDO|null), `$config` (app config), `$flash` (?array), `$dbError` (?string), `$pageTitle` (string)
- **Boutons alignés à droite du titre (inline, jamais en dessous)**: Les boutons doivent TOUJOURS être sur la même ligne que le titre, alignés à droite. Ne JAMAIS les placer juste après le texte du titre (nouvelle ligne).
  - **Niveau H1 (`.page-header`)**: Utiliser `$pageActions` dans `index.php` AVANT `require entete.php`. Le `.page-header` est `display: flex; justify-content: space-between` ; le premier `div` contient le H1, le second contient `$pageActions`. Exemple :
    ```php
    $pageActions = '<a class="btn btn-next" href="' . e(app_url('ma_page')) . '"><span class="material-symbols-outlined">add</span> Nouvel élément</a>';
    ```
    Ajouter le bloc dans `index.php` avant `require entete.php` :
    ```php
    $pageActions = '';
    if ($page === 'ma-page' && function_exists('has_permission') && has_permission('ma_page.create')) {
        $pageActions = '<a class="btn btn-next" href="' . e(app_url('ma_page_create')) . '"><span class="material-symbols-outlined">add</span> Nouveau</a>';
    }
    ```
    CSS : `.page-header > div:first-child` a `flex: 1`, `.table-actions` dans le header garde `flex: 0 1 auto`.
  - **Niveau section (H2/H3 dans la page)**: Utiliser `<div class="section-title-row">` contenant le titre ET les boutons inline. `.section-title-row` utilise `display: flex; justify-content: space-between; align-items: center`. Ne PAS mettre le titre dans un `<div>` séparé du bloc des actions — tout doit être dans le même `.section-title-row`. Exemple :
    ```html
    <div class="section-title-row">
        <h2>Mon titre</h2>
        <div class="table-actions">
            <a class="btn btn-next" href="...">Bouton</a>
        </div>
    </div>
    ```
  - Ne PAS mettre de titre H2 en double dans la page — le H1 du `page-header` suffit.
- **Page files** (`pages/{group}/{page}.php`): Self-contained — PHP logic at top (POST handling, data fetching), HTML at bottom. Pages are grouped by sidebar section:
  - `accueil/` — dashboard, notifications
  - `dossiers/` — sociétés, associés, contrats, collaborateurs, societe_suivi
  - `modification-juridique/` — modifications
  - `modification-juridique/cession/` — cessions, cession, cession_dossier, cession_steps
  - `templates/` — templates, generation, documents
  - `outils/` — analyse-couverture, defaults, variables, convert-word-pdf, ai-assistant
  - `configuration/` — configuration tabs, roles, setup
- **Step files** (`*/_steps/*.php`): Wizard steps split into separate files (e.g. `creation_steps/`, `cession_steps/`), included conditionally by `$step`. Each step file handles its own POST + HTML output. Paths from `_steps/` use `__DIR__ . '/../../..'` to reach project root (3 niveaux) ; depuis `cessions/cession_steps/` utiliser `__DIR__ . '/../../../..'` (4 niveaux).
- **`__DIR__` convention**: From `pages/{group}/` use `__DIR__ . '/../..'` (2 niveaux) ; from `pages/{group}/_steps/` use `__DIR__ . '/../../..'` (3 niveaux) ; from `pages/{group}/{subgroup}/_steps/` use `__DIR__ . '/../../../..'` (4 niveaux)
   - **Includes**: `includes/amorcage.php` (session, config, DB), `includes/fonctions.php` (helpers), `includes/entete.php` + `includes/navigation.php` + `includes/pied_page.php`

## Code Conventions
- Every PHP file starts with `declare(strict_types=1);`
- All HTML output uses `<?= e($var) ?>` for escaping (`htmlspecialchars`)
- French UI labels only — no English in user-facing text
- CSRF on every POST form: `<?= csrf_input() ?>` + `verify_csrf()` at top of handler
- Redirect-after-POST via `redirect_to('page')` — never render on POST
- Flash messages via `set_flash('success'|'error', 'message')`
- DB queries use PDO prepared statements with named params only

## Helper Functions (includes/fonctions.php)
- `e(?string): string` — HTML escape
- `app_url(string $page, array $params): string` — build URL
- `redirect_to(string $page, array $params): never`
- `set_flash(string $type, string $message): void`
- `csrf_input(): string` / `verify_csrf(): void`
- `is_post(): bool`
- `field_value(array, string, string=''): string` — trimmed string from $_POST
- `money_value(array, string): ?float` — comma-to-dot decimal parsing
- `int_value(array, string): ?int`
- `dashboard_count(?PDO, string): int`
- `fetch_record(?PDO, string table, int id): ?array`
- `fetch_all_records(?PDO, string): array`
- `fetch_societes_options(?PDO): array`
- `fetch_reference_options(?PDO, string table, string column): array`
- `auto_notify_action(string $action, string $entityType, int $entityId, string $description, array $extra = []): void` — crée notification automatique via `log_activity()` (17 types)
- `update_user_session(int $userId, string $sessionId): void` — enregistre session active dans `user_sessions`
- `get_online_users(?PDO $pdo): array` — utilisateurs actifs dans la dernière heure
- `get_most_visited_pages(?PDO $pdo, int $limit = 5): array` — pages les plus consultées (depuis `page_views`)
- `log_page_view(?PDO $pdo, int $userId, string $page): void` — enregistre visite de page
- `page_display_name(string $page): string` — traduit page en français
- `export_csv(string filename, array headers, array rows): never`
- `export_excel(string filename, array headers, array rows): never` — génère .xlsx via PhpSpreadsheet, auto-column width
- `import_excel_preview(string table, array columnMap, array defaults): array|string` — lit .xlsx uploadé, mappe colonnes, retourne preview ou message d'erreur
- `import_excel_confirm(string table, array columnMap, array defaults): array` — insère les données validées depuis `$_SESSION['_import_preview']`
- `load_defaults(?string key): array`
- `fetch_legal_form_template_folder(?PDO, string formeJuridique): string` — lit `template_folder` depuis `ref_formes_juridiques`
- `fetch_formes_juridiques_with_folders(?PDO): array` — toutes les formes avec leur dossier template
- `ensure_template_folder(string folderName): bool` — crée `templates/<folder>/` si inexistant

## URL Patterns
- List pages sociétés: `page=creations` (dossiers création), `page=domiciliations` (dossiers domiciliation), `page=societes` (toutes, hors menu, conservée pour compatibilité)
- Detail page: `index.php?page=societe&id=1`
- Suivi administratif société: `index.php?page=societe_suivi&id=1` — étapes par type de génération (création vs domiciliation), toggle étape, statut/dates/notes, upload/delete de documents. Fichiers dans `uploads/suivi/{societeId}/`, chemin DB relatif depuis la racine (lien via `word_url()`). Paramètre optionnel `open={etapeId}` pour ouvrir une étape au chargement.
- Fiche société (`societe_details.php`): sections ancrées via barre sticky (`.anchor-nav`) — `#societe-infos`, `#suivi`, `#historique`, `#associes`, `#contrats`, `#documents`, `#documents-uploades`. Alertes échéances (certificat négatif ≤15j/expiré, CIN gérants ≤30j/expirée, contrats actifs ≤30j/échus). Timeline historique (dernieres `activity_logs` pour `societe`/`document`). Upload direct de certificat négatif / CIN gérant vers `uploads/dossiers/{societeId}/` (INSERT `uploaded_docs` + `log_activity('upload','document',...)`), type de document + associé géré en JS.
- Search: `index.php?page=creations&q=term` — use `search_term()` + `like_term()`

## Database (MySQL via PDO)
- Host: `127.0.0.1:3306`, DB: `center_domiciliation`, user: `root`, pass: empty — surchargeable via `.env` (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`)
- Schema: `database/schema.sql` (tables + ref tables), seed: `database/seed.sql`
- Core tables: `societes`, `associes`, `contrats`, `collaborateurs`, `uploaded_docs`
- Ref tables: `ref_tribunaux`, `ref_ste_adresses`, `ref_nationalites`, `ref_lieux_naissance`, `ref_activites`, `ref_formes_juridiques`
- Import: `mysql -u root center_domiciliation < database/import.sql`
- Migration DB existante (ajout colonne `template_folder`):
  ```sql
  ALTER TABLE ref_formes_juridiques ADD COLUMN template_folder VARCHAR(120) DEFAULT '' NOT NULL AFTER forme_juridique;
  UPDATE ref_formes_juridiques SET template_folder = 'SARL AU' WHERE forme_juridique = 'SARL AU';
  UPDATE ref_formes_juridiques SET template_folder = 'SARL' WHERE forme_juridique = 'SARL';
  UPDATE ref_formes_juridiques SET template_folder = 'SA' WHERE forme_juridique = 'SA';
  ```

## Page Patterns
- **List pages** (`societes_liste`, `associes_liste`, `contrats_liste`): Table with search bar, CSV export link, delete button per row, "Voir" link to detail page
- **Configuration** (`configuration.php`): Unified page with tabs for all 8 reference tables (formes-juridiques, villes, tribunaux, nationalites, lieux-naissance, adresses, qualites-associe, activites). Add/edit/delete inline. L'onglet `formes-juridiques` affiche une colonne **Dossier Templates** pour lier chaque forme juridique à un dossier dans `templates/`. Si le dossier n'existe pas, il est créé automatiquement lors de l'ajout ou la modification.
- **Wizard** (`creation_steps/_main.php`): 6-step session-based wizard (Societe, Associes, Contrat, Recapitulatif, Documents, Generation) with JS dynamic associate forms. Step 5 "Documents" requires uploading Certificat Negatif (PDF) and CIN des Gerants (PDF/image) before generation. Steps are in separate files under `creation_steps/`.
- **Double numérotation de dossier**: `societes` stocke `societe_dossier_domiciliation_number` (`DOM-YYYY-NNN`, domiciliation) et `societe_dossier_creation_number` (`CRE-YYYY-NNN`, création). Les deux numéros sont auto-générés à l'init du wizard via `next_dossier_number(?PDO, string $prefix, string $column)` (`includes/fonctions.php` — whitelist colonnes `['societe_dossier_domiciliation_number','societe_dossier_creation_number']`, format `sprintf('%s-%s-%03d', prefix, year, max+1)`). Le champ `societe_dossier_creation_number` n'est conservé (POST step_01 / UPDATE `societe_details.php`) et inséré (INSERT step_06) que si `societe_type_generation === 'creation'`, sinon il est vidé/NULL. Affiché conditionnellement (mode création) dans : step_01 (champ `[data-depends-type-gen]`), step_04 (récap), fiche société (lecture + édition avec toggle JS), liste sociétés (colonne `dossier-creation` + export), `generation.php` (détail dossier), et injecté dans les DOCX comme variable `SOCIETE_DOSSIER_CREATION`.
- **Detail page** (`societe_details.php`): Single record view with related data tables (associates, contracts, collaborators inline). En mode lecture : carte alertes échéances, carte suivi administratif (progression + liens vers `societe_suivi`), timeline historique, upload direct de documents (certificat négatif / CIN gérant).

## Template Patterns
- Layout: `<section class="grid two">` for two-column, `<section class="card">` for single
- Tables: wrapped in `<article class="card">` with `<div class="section-header">`
- `info-grid`: Key-value display grid (used in detail pages)
- Stats: `<section class="stats">` with `<article class="stat">` children
- Empty state: `<p class="table-empty">Aucun(e) ...</p>`
## Button System
- **Base**: `.btn` or `button[type="submit"]` — transparent + 2px `var(--primary)` border + hover rgba(74,108,247,0.12)
- **Variants**: `.btn-secondary` (grey border), `.btn-danger` (red border + hover rgba(252,66,74,0.12))
- **Wizard variants** (specificity 0,2,0): `.btn.btn-cancel` (grey), `.btn.btn-back` (orange `#ff6b35`), `.btn.btn-info` (violet `var(--info)`), `.btn.btn-next` (green `#00b894`)
- All variants: transparent background, 2px solid border, hover rgba 12%
- **Padding**: `6px 14px` (CSS global) — do NOT use inline `padding:` on buttons
- **Icons**: Every button MUST have a Material Symbol `<span class="material-symbols-outlined">icon_name</span>` before the text
- **Color by role**:
  - Green (`.btn-next`) — primary/creation actions: Creer un dossier, Nouveau collaborateur, Nouveau dossier, Ajouter un template, Suivant, Generer les documents, Creer le dossier complet
  - Violet (`.btn-info`) — secondary/explore actions: Voir tout, Exporter CSV, Remplir automatiquement
  - Orange (`.btn-back`) — backward navigation: Retour, Reinitialiser
  - Grey (`.btn-cancel`) — cancel/abort: Annuler
  - Red (`.btn-danger`) — destructive: delete/remove icons
- **Table action buttons**: `class="btn-icon"` with Material Symbols only (no text). Voir → `visibility`, Modifier → `edit`, Supprimer → `delete` with `class="btn-icon danger"`
- Cards: `<article class="card">` with optional `.stack` for vertical spacing

## Sidebar
- Layout: `.shell` CSS grid `260px 1fr`, collapse via `.shell.collapsed` → `60px 1fr`
- Toggle button in `.brand` with `[data-sidebar-toggle]` — rotates chevron icon
- Collapsed state hides `.brand-text`, `.nav-section-label`, `[data-nav-label]`; nav links center icons only
- Sidebar: `overflow: hidden; display: flex; flex-direction: column` — no scroll
- `.main`: `overflow-y: auto; height: 100vh` — content scrolls independently

## Scrollbar Design (Mode Sobre)
- **Global** `::-webkit-scrollbar` : `width: 8px; height: 8px`
- `::-webkit-scrollbar-track`: `background: transparent`
- `::-webkit-scrollbar-thumb`: `background: var(--line); border-radius: 2px`
- `::-webkit-scrollbar-thumb:hover`: `background: var(--text-muted)`
- Tous les conteneurs scrollables (`.table-scroll`, `.main`) héritent du style global — pas de scrollbar personnalisée par conteneur
- La grille de permissions (`.perms-table`) n'a plus de conteneur scrollable — elle s'étend naturellement et suit le scroll de la page

## Column Toggle (List Tables)
- Button `.btn-secondary` with `[data-col-toggle-btn]` + badge `.col-toggle-count` in `.table-actions`
- Dropdown panel `.col-toggle-panel` (absolute, left-aligned) with checkboxes per column
- Columns: `<th data-col="key">` + `table[data-col-toggle]` — matched by nth-child in JS
- Hidden columns use `.col-hidden` (width/padding zero, opacity 0)
- Preferences saved per page in `localStorage` key `col_visible_{page}`
- Badge color: green (`var(--success)`) when all visible, red (`var(--danger)`) when some hidden
- Tables wrapped in `.table-scroll` for independent horizontal scroll: `societes`, `associes`, `contrats`, `collaborateurs`

## Capital Distribution (Wizard SARL)
- Capital/parts/percentage fields shown only for SARL forms (`[data-capital-field]`)
- `repartirCapital()`: distribue capital et parts équitablement entre associés (dernier reçoit le reste)
- `recalcPctFromCapital()`: recalcule pourcentages depuis les capitaux saisis
- `recalcCapitalFromPct()`: recalcule capital et parts depuis les pourcentages saisis
- `updateCapitalSummary()`: met à jour les totaux, statut équilibre/déséquilibre
- `updatingLock` verrou empêche la boucle récursive entre capital ↔ pourcentage
- `toggleCapitalFields()`: affiche/masque champs selon forme juridique, désactive bouton ajout pour SARL AU
- Summary card: 2 lignes (Capital société + Part social société | Total capital + Total parts + Total % + Statut)
- Parsing monétaire: `parseMoney()` (virgule→point, supprime espaces) + `formatFR()` (toLocaleString fr-FR)

## Table Sorting
- **Every data table** must have `data-sortable` on `<table>` and `data-col` on each sortable `<th>`
- Sorting is handled automatically by `app.js` — click header to toggle asc/desc, Material Symbols indicators
- Non-sortable columns (checkbox, actions) omit `data-col`
- CSS: `th[data-col]` styled in `app.css`
- JS: IIFE runs on all `table[data-sortable]` on page load in `app.js`
- The sort function auto-detects numeric vs text by parsing cell content
- When adding a new table to any page, ALWAYS add `data-sortable` + `data-col` on `<th>`

## page-header (includes/header.php)
- `<h1>` rend `$pageTitle` (défini dans `index.php` ou la page)
- Sous-titre optionnel : définir `$pageSubtitle` dans la page, rendu automatiquement en `<p class="page-subtitle">`
- Messages flash : `$flash` (array `type`/`message`) ou `$dbError` pour les erreurs MySQL

## Assets
- CSS: `assets/css/app.css` — custom design system (CSS variables, no framework)
- JS: `assets/js/app.js` — vanilla JS: sidebar toggle, column toggle, table sorting, confirmation dialogs (`data-confirm`), dynamic associate form cloning (`data-associe-template`, `data-add-associe`, `data-remove-associe`), wizard capital distribution
- JS: `assets/js/table-editor.js` — **création rapide**, **édition inline** (double-clic), **édition en masse** (checkboxes + toolbar)

## Table Editor (Création rapide, Édition inline, Modification en masse)
- **API** `api.php`: Point d'accès sécurisé (JSON). Actions : `quick_create`, `inline_update`, `bulk_update`. Vérifie auth + CSRF. Whitelist des tables/colonnes autorisées.
- **JS** `assets/js/table-editor.js`: IIFE globale, 3 modules indépendants.
- **Quick Create** (`[data-quick-create-btn]` + `[data-modal="quick-create"]`):
  - Modal overlay avec formulaire → `fetch('api.php')` → insère la ligne via `<template data-row-template>`
  - Attributs template : `data-cell="col"`, `data-cell-link="page"`, `data-cell-value="id"`, `data-cell-label="text_col"`, `data-cell-actions`
- **Inline Edit** (`[data-editable="column"]` sur `<td>`):
  - Double-clic → input → blur/Enter → `api.php` → mise à jour cellule
  - Escape annule. Permission `*.edit` requise (attribut non rendu si pas de droit)
- **Bulk Edit** (`[data-bulk]` sur `<table>`):
  - Checkbox colonne + `<template data-bulk-toolbar>` (floating bottom bar)
  - "Tout sélectionner" via `[data-bulk-select-all]`
  - `[data-bulk-edit-btn]` ouvre `[data-modal="bulk-edit"]` → `api.php?action=bulk_update` → rechargement page
- **Modales réutilisables**:
  - `includes/quick_create_modal.php` : Définir `$quickCreateTitle`, `$quickCreateTable`, `$quickCreateFields`
  - `includes/bulk_edit_modal.php` : Définir `$bulkEditTitle`, `$bulkEditTable`, `$bulkEditFields`
  - Champs : `name`, `label`, `type` (text|select|number|email), `required`, `full`, `options` (indexed ou assoc), `placeholder`
- **Perms check** : `has_permission('societes.create')` pour le bouton, `has_permission('societes.edit')` pour `data-editable`

## TemplateAnalyzer (src/analyseur_templates.php)
- Static class for .docx template analysis and modification
- **extractVariables(path)**: Reads `word/document.xml` via ZipArchive, strips XML tags, regex `{{ VAR }}`
- **scanTemplates(dir)**: Recursively finds `.docx` files, returns array with filename/date/doc_type/variables
- **analyzeCoverage(templates)**: Cross-references template variables against `getExpectedContextKeys()`, returns `summary` + `variables[].{variable,occurrences,section,coverage,templates}` + `details[]`
- **renameVariable(old, new, dir)**: Renames `{{ OLD }}` → `{{ NEW }}` in all .docx files. Returns `['modified' => int, 'errors' => []]`
- **deleteVariable(name, dir)** / **deleteVariables(names, dir)**: Removes `{{ NAME }}` from all .docx files
- **replaceInDocxXml(xml, pattern, replacement)**: DOMDocument + XPath `//w:p` → concat all `<w:t>` text → `preg_replace` → put result in first `<w:t>`, clear others
- **getExpectedContextKeys()**: Returns flat array of canonical variable names (SOCIETE_*, ASSOCIE_*, CONTRAT_*, ACTIVITES_*, CESSION_*, SESSION_*, CEDANT_*, CESSIONNAIRE_*, DATE)
- **inferSection(name)**: Maps a variable name to `societe|associe|contrat|collaborateur|cession|autre`
- **extractTemplateInfo(path)**: Parses filename parts (prefix_date_docType_Template.docx) into structured array
- **groupByFolder(templates)**: Groups templates by parent folder name

## OpenCode Skills (.opencode/skills/)
- **awesome-design** — design system complet (charte CSS, boutons, layout, animations, responsive)
- **ui-design** — charte CSS (couleurs, boutons, layout, tableaux) pour toute nouvelle page
- **database** — migrations MySQL, schema, seed, requêtes PDO
- **docx-template** — manipulation .docx (TemplateAnalyzer, ZipArchive, variables)
- **security** — CSRF/XSS/injection/fichiers à chaque nouvelle fonctionnalité
- **manual-test** — checklist pré-commit (PHP lint, navigation, formulaires, UI, DB)
- **dolibarr** — fonctionnalités type ERP/Dolibarr (modules, hooks, triggers, permissions, workflow statuts, numérotation, API REST) + intégration Dolibarr ; référence `.opencode/skills/dolibarr/` (architecture.md, api-rest.md, patterns-vanilla-php.md). Version générique multi-projets dans `~/.agents/skills/dolibarr/`. Agent dédié : `.opencode/agent/dolibarr.md`
- **protocole-cto** — pilotage en 3 phases : planification (`/plan`), exécution sans placeholder (`/execute`), modification chirurgicale (`/modify <fonctionnalité>`). Feuille de route/tâches dans `docs/ROADMAP.md`
- **heberjahiz-deploy** — déploiement et exploitation production sur Heberjahiz (app.centirio.ma) : pipeline GitHub Actions zip+extract, FTPS curl, baseline DB, scripts temporaires à jeton, dépannage. Agent dédié : `.opencode/agent/heberjahiz.md`

## OpenCode Commands (.opencode/command/)
- **/plan** — audit + hypothèses + feuille de route AVANT tout code (KISS, versions à jour, structure respectée)
- **/execute** — implémentation production-ready : zéro TODO, auto-validation (`php -l`, logs), mise à jour de `docs/ROADMAP.md` après chaque tâche
- **/modify** — ajout de fonctionnalité en mode chirurgie : analyse d'impact d'abord, périmètre confiné, test anti-régression
- **/dev** — lance le serveur de dev en arrière-plan et vérifie que l'app répond
- **/deploy-heberjahiz** — push `main` → workflow Deploy Heberjahiz → poll du run → vérifications HTTPS/login (voir skill heberjahiz-deploy)

## Claude AI Integration
- **ClaudeService** (`src/service_claude.php`): Static class with cURL to Anthropic API
  - `ask()`: generic prompt → response (session-cached)
  - `autoFill()`: suggests realistic values for wizard form fields
  - `analyzeTemplates()`: suggests improvements for template variables (rename/delete/keep)
  - `generateClause()`: generates legal clauses (objet social, mentions légales, siège social)
  - `validateDossier()`: checks completeness/coherence of dossier data
  - `chat()`: multi-turn conversational assistant
- **Config**: `.env` (gitignored, `ANTHROPIC_API_KEY`) chargé via `includes/env.php` + `config/ai.php` (defaults). `config/ai.local.php` (gitignored) reste supporté en surcharge.
- **Available**: Check `ClaudeService::isAvailable()` — returns false if no API key
- **Cache**: Responses cached in `$_SESSION['_claude_cache']` with configurable TTL

### AI Features by Page
- **Wizard** (`creation_steps/_main.php`): "Remplir avec IA" button on steps 1-3 (stores suggestions in session, rendered as `data-apply-ai-fill` on button → JS fills fields). Step 5: "Valider avec IA" button (shows validation points), clause generation (3 types: objet social, mentions legales, siege social)
- **Analyse de couverture** (`analyse-couverture.php`): "Suggérer avec IA" button → shows suggestions card with variable/action badges (rename/delete/keep)
- **Assistant IA** (`ai-assistant.php`): Multi-turn chat with Claude, history stored in session

## Variables d'environnement (.env)
- Fichiers : `.env` (local, gitignoré) + `.env.example` (modèle versionné). Surcharge éventuelle `.env.local`.
- Chargé par `includes/env.php` (loader maison, sans phpdotenv) → `require` au début des configs.
- Lecture : `env_var('KEY', 'defaut')` (helper défini dans `includes/env.php`).
- Variables : `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL` (vide → base_url dérivé de `SCRIPT_NAME`), `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`, `ANTHROPIC_API_KEY`, `AI_MODEL`, `AI_MAX_TOKENS`, `AI_TEMPERATURE`, `AI_CACHE_TTL`.
- Les variables déjà définies dans l'environnement système ne sont pas écrasées.

## Analyse de Couverture (pages/outils/analyse-couverture.php)
- Page: `index.php?page=analyse-couverture`
- Reads templates from `templates/`, outputs analysis table
- **Filtres**: Tous / Couvertes / Non couvertes (liens de navigation, pas JS pur)
- **Recherche textuelle**: champ `#var-search` filtre les lignes par nom de variable en direct
- **Per-row actions** (all variables): Rename dropdown (all context keys) + delete button
- **Bulk actions** (checkbox-select): "Inverser la sélection", "Renommer la sélection" (lit le dropdown de chaque ligne + confirmation unique), "Supprimer la sélection" (confirm → loader overlay)
- **Tooltip templates**: survol de la colonne "Templates" → `title` avec la liste des fichiers
- **Loader overlay**: `#loading-overlay` (fixed, rgba(0,0,0,0.6), spinner + message)
- **CSRF + redirect-after-POST** on all actions
- **Table sorting**: `data-sortable` + `data-col` on `<th>` (asc/desc on Variable, Occurrences, Templates, Section, Couverture)

## Cession de Parts Sociales (pages/modification-juridique/cession/)
- **Pages**:
  - `cessions` (`index.php?page=cessions`) — liste CRUD des cessions (recherche, tri, CSV, suppression)
  - `cession` (`index.php?page=cession`) — wizard 3 étapes (Société → Cédant/Cessionnaire → Récapitulatif + Génération)
  - `cession_dossier` (`index.php?page=cession_dossier&id=XX`) — détail dossier avec stats, infos, lignes de cession, documents téléchargeables
- **Tables DB**: `cessions` + `cession_parts` (migration `20260612_000001_cession_parts.sql`)
- **Permissions**: `cessions.view`, `cessions.create`, `cessions.edit`, `cessions.delete`, `cessions.export`
- **Sidebar**: Section "Modification juridique" avec lien "Cession de parts sociales" (`includes/navigation.php`)
- **Wizard 3 étapes** (`cession.php`):
  - Étape 1 : Sélection/Nouvelle société (modal inline avec tous les champs, dropdowns ref_formes_juridiques/ref_villes/ref_tribunaux)
  - Étape 2 : Lignes cédant/cessionnaire (noms depuis DB ou saisie libre, parts, prix unitaire)
  - Étape 3 : Récapitulatif + Génération DOCX (via DocumentRenderer::buildContextFromCession())
- **Numéro de dossier**: Auto-généré `CES-YYYY-NNN`
- **Dossiers de sortie**: `dossiers_generer/dossiers_cession/{date}_{forme}_{raison}/{dossier_cession}/`
- **Templates DOCX**: `templates/_Cession_SARL/` et `templates/_Cession_SARLAU/` (4 fichiers chacun : Acte-Cession-Parts, PV-AGE-Cession, Declaration-Modificative-RC, Annonce-Legale-Cession)
- **DocumentRenderer** (`src/rendu_document.php`): `buildContextFromCession()` construit le contexte avec `SESSION_*`, `CEDANT_*`, `CESSIONNAIRE_*`, boucle `{%p for c in cession_parts %}`
- **TemplateAnalyzer**: clés contexte `CESSION_*` ajoutées dans `getExpectedContextKeys()`, section `cession` dans `inferSection()`
- **Mise à jour parts**: après validation, met à jour `associe_parts` et `associe_capital_detenu` dans la table `associes`

## DOCX Manipulation Gotchas
- **Format des variables** : depuis la conversion `scripts/convertir_variables_underscore.php`, les templates utilisent `_VAR_` (ex `_ASSOCIE_ADRESSE_`) au lieu de `{{ VAR }}`. Le renderer (`DocumentRenderer`) et `TemplateAnalyzer` acceptent les deux formats ; le format canonique pour les nouveaux templates est `_VAR_`. Les boucles `{%p for ... %}` restent inchangées.
- **Tokens collés aux cellules** : `strip_tags()` concatène les textes des cellules voisines (`commerciale_SOCIETE_RAISON_SOCIALE_Sigle`) — l'extraction se fait donc par nœud `w:t` + passe sur les clés connues de `getExpectedContextKeys()`.
- **Underscore split**: `{{ CIVILITE_ASSOCIE }}` can be split across `<w:t>` as `{{ CIVILITE` + ` ` + `ASSOCIE }}`. Always use `[\s_]*` in regex patterns, not literal `_`. Idem pour `_VAR_` : `normalizeSplitUnderscoreVariables()` fusionne les tokens coupés avant rendu.
- **Headers/footers**: Always scan `word/header*.xml` and `word/footer*.xml` too, not just `word/document.xml`
- **ZipArchive**: Must be enabled in `C:\xampp\php\php.ini` (`extension=zip`) + Apache restart

## Lancement multi-projets (Windows, PHP intégré)
- **`scripts/dev-server.ps1`** : un serveur PHP intégré par projet, sur son propre port — aucune config Apache à toucher. Permet de développer plusieurs projets en parallèle.
- Usage : `powershell -ExecutionPolicy Bypass -File .\scripts\dev-server.ps1 -Project <chemin> -Port <port> [-NoBrowser]`
  - `-Project` : chemin du projet (défaut : dossier courant)
  - `-Port` : port (défaut 8000, auto-incrémenté si occupé)
  - `-NoBrowser` : ne pas ouvrir Chrome (utilisé par les agents)
  - Détecte automatiquement `public/` comme document root si présent
- **Commande opencode `/dev`** (`.opencode/command/dev.md`) : lance le serveur de dev en arrière-plan et vérifie que l'app répond (200/302).
- **Limite** : le serveur intégré ignore `.htaccess` — réserver `run.ps1`/Apache aux projets qui en dépendent.
- Arrêt : `Get-NetTCPConnection -LocalPort <port> -State Listen | % { Stop-Process -Id $_.OwningProcess -Force }`
- MySQL reste démarré via `run.ps1` (XAMPP) ou manuellement.

## macOS Setup (Shell Scripts)
- **`scripts/setup.sh`** : Installation complète via Homebrew (PHP, MySQL, Node.js, Composer, LibreOffice). Lance une seule fois.
- **`scripts/run.sh`** : Démarre MySQL + serveur PHP intégré sur le port 8080.
- Exécution : `chmod +x scripts/setup.sh scripts/run.sh && ./scripts/setup.sh` puis `./scripts/run.sh`
- URL : `http://localhost:8080/`

## Convertisseur DOCX → PDF

Le rendu final des documents génère un PDF depuis un DOCX. Trois méthodes sont tentées dans cet ordre :

1. **LibreOffice** (headless) — `libreoffice --headless --convert-to pdf`
   - Installation : `choco install libreoffice` ou via libreoffice.org
   - Apache doit redémarrer après l'install pour que `PATH` soit mis à jour

2. **Word COM** (Windows uniquement) — `COM('Word.Application')` + `ExportAsFixedFormat`
   - Active l'extension COM dans `C:\xampp\php\php.ini` :
     ```
     extension=php_com_dotnet.dll
     ```
   - Microsoft Word doit être installé sur le poste
   - Redémarrer Apache après modification de php.ini

3. **PHPWord → HTML → Dompdf** (fallback PHP pur)
   - Ne nécessite aucun logiciel externe
   - Installation : `composer install` à la racine du projet
   - Dépendances : `phpoffice/phpword` + `dompdf/dompdf`
   - Résultat : HTML simplifié, mise en page basique (sans les en-têtes/pieds de page Word)
   - `vendor/autoload.php` est chargé dans `pages/templates/generation.php` et `pages/dossiers/creation_steps/_main.php`

### Vérification rapide
```powershell
# php.ini : vérifier les extensions
php -m | Select-String "com_dotnet|zip"

# Composer : vérifier que les dépendances sont installées
if (Test-Path vendor) { "vendor OK" }

# Test de conversion
php -r "require 'vendor/autoload.php'; echo class_exists('\PhpOffice\PhpWord\IOFactory') ? 'PHPWord OK' : 'PHPWord missing';"
```

## MCP Servers (OpenCode)

Le projet utilise 4 serveurs MCP configurés dans `opencode.json` :

| Serveur | Package npm | Rôle |
|---------|------------|------|
| **memory** | `@modelcontextprotocol/server-memory` | Mémoire contextuelle (knowledge graph) |
| **chrome-devtools** | `chrome-devtools-mcp` | Automatisation navigateur (tests UI, debug visuel) |
| **mysql-dev** | `@berthojoris/mcp-mysql-server` | Requêtes SQL directes sur la base locale |
| **heberjahiz-db** *(désactivé)* | `@berthojoris/mcp-mysql-server` | Requêtes SQL en lecture sur la base prod `centiaxh_domiciliation`. Activation : (1) cPanel → MySQL distant → ajouter son IP ; (2) remplacer `MOT_DE_PASSE_A_REMPLIR` dans le DSN de `opencode.json` ; (3) passer `enabled: true` puis redémarrer opencode. Jamais de mot de passe réel versionné — remettre le placeholder avant tout commit. |

### Déploiement production Heberjahiz

- Cible : **https://app.centirio.ma** (docroot `/home/centiaxh/centirio.ma/app.centirio.ma/`)
- Pipeline : push `main` → workflow **Deploy Heberjahiz** (~2 min, zip + extracteur auto-destructeur)
- Référence complète (procédures, quirks FTPS, baseline DB, dépannage) : skill `heberjahiz-deploy` + commande `/deploy-heberjahiz`
- Secrets du repo : `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD` (compte FTP chrooté dans le docroot)

### Connexion MySQL depuis le `.env`
- Le serveur MCP `mysql-dev` est lancé via `scripts/mysql-mcp.mjs` (wrapper Node) qui lit `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD` depuis `.env` — pas de valeurs en dur.
- Les scripts PowerShell qui parlent à MySQL (`scripts/sync.ps1`, `scripts/post-push-sync.ps1`) chargent `.env` via `scripts/_env.ps1` (dot-source) et utilisent `$env:DB_*`.
- Pour tester manuellement : `node scripts/mysql-mcp.mjs` (reste actif en stdio) ou `mysql -u "$(Get-Content .env | Select-String '^DB_USERNAME=' | ForEach-Object {$_.ToString().Split('=')[1]})"`.

### Prérequis
- **Node.js ≥ 18** installé et dans le `PATH`
- Les packages sont téléchargés automatiquement par `npx` au premier lancement d'OpenCode

### Installation manuelle (pré-cache)
```powershell
npx -y @modelcontextprotocol/server-memory --version
npx -y chrome-devtools-mcp@latest --no-usage-statistics --version
node scripts/mysql-mcp.mjs
```

### Dépannage
- Si un serveur MCP ne se lance pas, vérifie : `node --version`
- `mysql-dev` nécessite MySQL en cours d'exécution sur le port 3306
- `chrome-devtools` nécessite Chrome lancé avec : `.\scripts\chrome-debug.ps1`

## Root Directory Cleanliness
- **No `.txt` or `.png` files in root** — place documentation text files in `docs/`, screenshots in `docs/screenshots/`
- Root should only contain: `index.php`, `run.ps1`, `router.php`, `api.php`, `.env.example`, `composer.json`, `composer.lock`, `composer.phar`, `opencode.json`, `AGENTS.md`, `README.md`, `.gitignore`, and directories
- **Scripts d'outillage** (`.ps1`/`.sh`/`.cmd`) rangés dans `scripts/` : `run.sh`, `setup.ps1`, `setup.sh`, `dev-server.ps1`, `sync.ps1`, `post-push-sync.ps1`, `git-push.cmd`, `chrome-debug.ps1`, `_env.ps1` (chargeur `.env` pour PowerShell), `mysql-mcp.mjs` (wrapper MCP MySQL) — seul `run.ps1` (lanceur XAMPP) reste à la racine
- `.gitignore` already blocks `/*.txt` and `/*.png` from root to prevent accidental commits

## Knowledge Graph (graphify)

Un graphe de connaissances du projet est généré par le skill `/graphify` (skill Claude installé dans `~/.claude/skills/graphify/`) et stocké dans `graphify-out/`.

- **Sorties versionnées** (commit à chaque build) : `graph.html` (graphe interactif, ~938 nœuds/1162 arêtes), `graph.json` (données brutes), `GRAPH_REPORT.md` (rapport : God Nodes, Surprising Connections, communautés), `cost.json` (coût tokens cumulé), `manifest.json` (pour les mises à jour incrémentales `--update`), `.graphify_labels.json` (noms des communautés)
- **Non versionnés** (`.gitignore`) : `.graphify_python` (chemin interpréteur uv, machine-spécifique), `.graphify_root` (racine de scan), `cache/` (cache d'extraction régénérable ~1 Mo)
- **Régénération** : `/graphify` depuis la racine du projet ; au-delà de ~500 fichiers, le skill demande un filtre (utiliser « tout sauf vendor » — le périmètre est ~356 fichiers : code 158, document 41, paper 82, image 75)
- **Extraction** : AST pour le code (546 nœuds) + extraction sémantique par sous-agents pour docs/PDF/images (392 nœuds). Sans clé `GEMINI_API_KEY`, l'extraction sémantique est faite par les sous-agents de la session. Les PDF/images non lisibles par le modèle produisent des nœuds dérivés des noms de fichiers (marqués INFERRED/AMBIGUOUS)
- **Mise à jour incrémentale** : `graphify --update` ré-extrait uniquement les fichiers modifiés (utilise `manifest.json` + `cache/`)
- **Requêtes** : `/graphify query "<question>"` interroge `graphify-out/graph.json` existant sans reconstruire

## Auto-Migration System

Le système de migration automatique synchronise le schéma DB entre plusieurs PC (XAMPP local).

- **Fonctionnement** : `includes/migrations.php` est appelé dans `includes/amorcage.php` après la connexion PDO
- **Stockage** : Les fichiers SQL timestampés dans `database/migrations/` (format `YYYYMMDD_HHMMSS_description.sql`)
- **Tracking** : Table `_migrations` enregistre les fichiers déjà appliqués
- **Idempotence** : Les migrations `ALTER TABLE` sont protégées — les erreurs "duplicate column" sont ignorées
- **Workflow multi-PC** :
  1. Coder et tester sur PC1 → commit + push
  2. `git pull` sur PC2 → rafraîchir le navigateur → migrations auto-appliquées
  3. Si une migration échoue, un message d'erreur s'affiche en haut du tableau de bord
- **Créer une migration** : Ajouter un fichier `database/migrations/<timestamp>_description.sql` — il sera exécuté automatiquement au prochain chargement de page

## XAMPP Debugging
- PHP binary: `C:\xampp\php\php.exe`
- Error log: `C:\xampp\php\logs\php_error_log` or Apache `error.log`
- Quick test: create `_debug.php`, run via `php.exe _debug.php` (runs outside Apache, good for testing TemplateAnalyzer)
- No lint/typecheck commands available (vanilla PHP project)
