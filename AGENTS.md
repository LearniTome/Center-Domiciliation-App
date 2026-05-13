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

## URL Patterns
- List page: `index.php?page=societes`
- Detail page: `index.php?page=societe&id=1`
- Search: `index.php?page=societes&q=term` — use `search_term()` + `like_term()`

## Database (MySQL via PDO)
- Host: `127.0.0.1:3306`, DB: `center_domiciliation`, user: `root`, pass: empty
- Schema: `database/schema.sql` (tables + ref tables), seed: `database/seed.sql`
- Core tables: `societes`, `associes`, `contrats`, `collaborateurs`
- Ref tables: `ref_tribunaux`, `ref_ste_adresses`, `ref_nationalites`, `ref_lieux_naissance`, `ref_activites`
- Import: `mysql -u root center_domiciliation < database/import.sql`

## Page Patterns
- **List pages** (`societes`, `associes`, `contrats`): Table with search bar, CSV export link, delete button per row, "Voir" link to detail page
- **Configuration** (`configuration.php`): Unified page with tabs for all 8 reference tables (formes-juridiques, villes, tribunaux, nationalites, lieux-naissance, adresses, qualites-associe, activites). Add/edit/delete inline.
- **Wizard** (`creation.php`): 3-step session-based wizard with JS dynamic associate forms
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
- **Icons**: Every button MUST have an MDI icon `<span class="mdi mdi-xxx"></span>` before the text
- **Color by role**:
  - Green (`.btn-next`) — primary/creation actions: Creer un dossier, Nouveau collaborateur, Nouveau dossier, Ajouter un template, Suivant, Generer les documents, Creer le dossier complet
  - Violet (`.btn-info`) — secondary/explore actions: Voir tout, Exporter CSV, Remplir automatiquement
  - Orange (`.btn-back`) — backward navigation: Retour, Reinitialiser
  - Grey (`.btn-cancel`) — cancel/abort: Annuler
  - Red (`.btn-danger`) — destructive: delete/remove icons
- **Table action buttons**: `class="btn-icon"` with MDI icons only (no text). Voir → `mdi-eye`, Modifier → `mdi-pencil`, Supprimer → `mdi-delete` with `class="btn-icon danger"`
- Cards: `<article class="card">` with optional `.stack` for vertical spacing

## Sidebar
- Layout: `.shell` CSS grid `260px 1fr`, collapse via `.shell.collapsed` → `60px 1fr`
- Toggle button in `.brand` with `[data-sidebar-toggle]` — rotates chevron icon
- Collapsed state hides `.brand-text`, `.nav-section-label`, `[data-nav-label]`; nav links center icons only
- Sidebar: `overflow: hidden; display: flex; flex-direction: column` — no scroll
- `.main`: `overflow-y: auto; height: 100vh` — content scrolls independently

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

## Assets
- CSS: `assets/css/app.css` (~1116 lignes) — custom design system (CSS variables, no framework)
- JS: `assets/js/app.js` (~638 lignes) — vanilla JS: sidebar toggle, column toggle, confirmation dialogs (`data-confirm`), dynamic associate form cloning (`data-associe-template`, `data-add-associe`, `data-remove-associe`), wizard capital distribution

## Config
- `config/app.php`: app_name, base_url
- `config/database.php`: MySQL connection settings
- `config/defaults.json`: Form field defaults for wizard (societe, associe, contrat, collaborateur sections)
