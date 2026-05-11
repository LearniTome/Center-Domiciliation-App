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
- **Wizard** (`creation.php`): 3-step session-based wizard with JS dynamic associate forms
- **Detail page** (`societe.php`): Single record view with related data tables (associates, contracts, collaborators inline)

## Template Patterns
- Layout: `<section class="grid two">` for two-column, `<section class="card">` for single
- Tables: wrapped in `<article class="card">` with `<div class="section-header">`
- `info-grid`: Key-value display grid (used in detail pages)
- Stats: `<section class="stats">` with `<article class="stat">` children
- Empty state: `<p class="table-empty">Aucun(e) ...</p>`
- Buttons: `<a class="btn">` (primary) or `<a class="btn btn-secondary">`
- Cards: `<article class="card">` with optional `.stack` for vertical spacing

## Assets
- CSS: `assets/css/app.css` — custom design system (CSS variables, no framework)
- JS: `assets/js/app.js` — vanilla JS: confirmation dialogs (`data-confirm`), dynamic associate form cloning (`data-associe-template`, `data-add-associe`, `data-remove-associe`)

## Config
- `config/app.php`: app_name, base_url
- `config/database.php`: MySQL connection settings
- `config/defaults.json`: Form field defaults for wizard (societe, associe, contrat, collaborateur sections)
