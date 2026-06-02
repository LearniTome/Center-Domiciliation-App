# Center Domiciliation App — PHP Project Guide

## Overview
Vanilla PHP 8.x procedural app for managing company domiciliation dossiers. No framework, no Composer, no autoloader. Runs on XAMPP (Apache + MySQL).

## Architecture
- **Routing**: Single front controller `index.php?page=` with an allowlist of pages
- **Globals available in pages**: `$pdo` (PDO|null), `$config` (app config), `$flash` (?array), `$dbError` (?string), `$pageTitle` (string)
- **Page files** (`pages/*.php`): Self-contained — PHP logic at top (POST handling, data fetching), HTML at bottom
- **Includes**: `includes/bootstrap.php` (session, config, DB), `functions.php` (helpers), `header.php` + `nav.php` + `footer.php`

## Code Conventions
- Every PHP file starts with `declare(strict_types=1);`
- All HTML output uses `<?= e($var) ?>` for escaping (`htmlspecialchars`)
- French UI labels only — no English in user-facing text
- CSRF on every POST form: `<?= csrf_input() ?>` + `verify_csrf()` at top of handler
- Redirect-after-POST via `redirect_to('page')` — never render on POST
- Flash messages via `set_flash('success'|'error', 'message')`
- DB queries use PDO prepared statements with named params only

## Helper Functions (includes/functions.php)
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
- `export_csv(string filename, array headers, array rows): never`
- `load_defaults(?string key): array`
- `fetch_legal_form_template_folder(?PDO, string formeJuridique): string` — lit `template_folder` depuis `ref_formes_juridiques`
- `fetch_formes_juridiques_with_folders(?PDO): array` — toutes les formes avec leur dossier template
- `ensure_template_folder(string folderName): bool` — crée `templates/<folder>/` si inexistant

## URL Patterns
- List page: `index.php?page=societes`
- Detail page: `index.php?page=societe&id=1`
- Search: `index.php?page=societes&q=term` — use `search_term()` + `like_term()`

## Database (MySQL via PDO)
- Host: `127.0.0.1:3306`, DB: `center_domiciliation`, user: `root`, pass: empty
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
- **List pages** (`societes`, `associes`, `contrats`): Table with search bar, CSV export link, delete button per row, "Voir" link to detail page
- **Configuration** (`configuration.php`): Unified page with tabs for all 8 reference tables (formes-juridiques, villes, tribunaux, nationalites, lieux-naissance, adresses, qualites-associe, activites). Add/edit/delete inline. L'onglet `formes-juridiques` affiche une colonne **Dossier Templates** pour lier chaque forme juridique à un dossier dans `templates/`. Si le dossier n'existe pas, il est créé automatiquement lors de l'ajout ou la modification.
- **Wizard** (`creation.php`): 6-step session-based wizard (Societe, Associes, Contrat, Recapitulatif, Documents, Generation) with JS dynamic associate forms. Step 5 "Documents" requires uploading Certificat Negatif (PDF) and CIN des Gerants (PDF/image) before generation.
- **Detail page** (`societe.php`): Single record view with related data tables (associates, contracts, collaborators inline)

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
- **Global** `::-webkit-scrollbar` : `width: 4px; height: 4px` — sobre et discret
- `::-webkit-scrollbar-track`: `background: transparent`
- `::-webkit-scrollbar-thumb`: `background: var(--line); border-radius: 2px`
- `::-webkit-scrollbar-thumb:hover`: `background: var(--text-muted)`
- Tous les conteneurs scrollables (`.table-scroll`, `.perms-scroll`, `.main`) héritent du style global — pas de scrollbar personnalisée par conteneur
- Les `.perms-scroll` (grille de permissions) utilisent `max-height: calc(100vh - 380px)` avec `overflow-y: auto` pour que l'en-tête et les onglets restent visibles

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

## TemplateAnalyzer (src/TemplateAnalyzer.php)
- Static class for .docx template analysis and modification
- **extractVariables(path)**: Reads `word/document.xml` via ZipArchive, strips XML tags, regex `{{ VAR }}`
- **scanTemplates(dir)**: Recursively finds `.docx` files, returns array with filename/date/doc_type/variables
- **analyzeCoverage(templates)**: Cross-references template variables against `getExpectedContextKeys()`, returns `summary` + `variables[].{variable,occurrences,section,coverage,templates}` + `details[]`
- **renameVariable(old, new, dir)**: Renames `{{ OLD }}` → `{{ NEW }}` in all .docx files. Returns `['modified' => int, 'errors' => []]`
- **deleteVariable(name, dir)** / **deleteVariables(names, dir)**: Removes `{{ NAME }}` from all .docx files
- **replaceInDocxXml(xml, pattern, replacement)**: DOMDocument + XPath `//w:p` → concat all `<w:t>` text → `preg_replace` → put result in first `<w:t>`, clear others
- **getExpectedContextKeys()**: Returns flat array of canonical variable names (SOCIETE_*, ASSOCIE_*, CONTRAT_*, ACTIVITES_*, DATE)
- **inferSection(name)**: Maps a variable name to `societe|associe|contrat|collaborateur|autre`
- **extractTemplateInfo(path)**: Parses filename parts (prefix_date_docType_Template.docx) into structured array
- **groupByFolder(templates)**: Groups templates by parent folder name

## OpenCode Skills (.opencode/skills/)
- **awesome-design** — design system complet (charte CSS, boutons, layout, animations, responsive)
- **ui-design** — charte CSS (couleurs, boutons, layout, tableaux) pour toute nouvelle page
- **database** — migrations MySQL, schema, seed, requêtes PDO
- **docx-template** — manipulation .docx (TemplateAnalyzer, ZipArchive, variables)
- **security** — CSRF/XSS/injection/fichiers à chaque nouvelle fonctionnalité
- **manual-test** — checklist pré-commit (PHP lint, navigation, formulaires, UI, DB)

## Claude AI Integration
- **ClaudeService** (`src/ClaudeService.php`): Static class with cURL to Anthropic API
  - `ask()`: generic prompt → response (session-cached)
  - `autoFill()`: suggests realistic values for wizard form fields
  - `analyzeTemplates()`: suggests improvements for template variables (rename/delete/keep)
  - `generateClause()`: generates legal clauses (objet social, mentions légales, siège social)
  - `validateDossier()`: checks completeness/coherence of dossier data
  - `chat()`: multi-turn conversational assistant
- **Config**: `config/ai.php` (defaults) + `config/ai.local.php` (gitignored, for API key)
- **Available**: Check `ClaudeService::isAvailable()` — returns false if no API key
- **Cache**: Responses cached in `$_SESSION['_claude_cache']` with configurable TTL

### AI Features by Page
- **Wizard** (`creation.php`): "Remplir avec IA" button on steps 1-3 (stores suggestions in session, rendered as `data-apply-ai-fill` on button → JS fills fields). Step 5: "Valider avec IA" button (shows validation points), clause generation (3 types: objet social, mentions legales, siege social)
- **Analyse de couverture** (`analyse-couverture.php`): "Suggérer avec IA" button → shows suggestions card with variable/action badges (rename/delete/keep)
- **Assistant IA** (`ai-assistant.php`): Multi-turn chat with Claude, history stored in session

## Analyse de Couverture (pages/analyse-couverture.php)
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

## DOCX Manipulation Gotchas
- **Underscore split**: `{{ CIVILITE_ASSOCIE }}` can be split across `<w:t>` as `{{ CIVILITE` + ` ` + `ASSOCIE }}`. Always use `[\s_]*` in regex patterns, not literal `_`.
- **Headers/footers**: Always scan `word/header*.xml` and `word/footer*.xml` too, not just `word/document.xml`
- **ZipArchive**: Must be enabled in `C:\xampp\php\php.ini` (`extension=zip`) + Apache restart

## Root Directory Cleanliness
- **No `.txt` or `.png` files in root** — place documentation text files in `docs/`, screenshots in `docs/screenshots/`
- Root should only contain: `index.php`, `run.ps1`, `opencode.json`, `AGENTS.md`, `CLAUDE.md`, `.gitignore`, and directories
- `.gitignore` already blocks `/*.txt` and `/*.png` from root to prevent accidental commits

## XAMPP Debugging
- PHP binary: `C:\xampp\php\php.exe`
- Error log: `C:\xampp\php\logs\php_error_log` or Apache `error.log`
- Quick test: create `_debug.php`, run via `php.exe _debug.php` (runs outside Apache, good for testing TemplateAnalyzer)
- No `composer.json`, no autoloader — use `require_once` for classes
- No lint/typecheck commands available (vanilla PHP project)
