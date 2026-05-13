# Center Domiciliation App — Contexte & Mémoire

## Projet
Application PHP vanilla de gestion de dossiers de domiciliation d'entreprises.
- PHP 8.x procedural, MySQL via PDO, XAMPP
- Pas de framework, pas de Composer, pas d'autoloader
- Front controller: `index.php?page=`
- UI 100% française

## Conventions clés
- `declare(strict_types=1);` en tête de chaque fichier PHP
- Échappement HTML via `<?= e($var) ?>`
- CSRF sur tous les POST : `<?= csrf_input() ?>` + `verify_csrf()`
- Redirect-after-POST via `redirect_to('page')`
- Sessions pour flash messages : `set_flash('success'|'error', 'message')`
- Requêtes DB : PDO prepared statements avec paramètres nommés uniquement

## Architecture fichiers
- `pages/*.php` — logique PHP en haut, HTML en bas
- `includes/bootstrap.php` — amorçage (session, config, DB)
- `includes/functions.php` — helpers (e, redirect_to, csrf_input, etc.)
- `includes/header.php`, `nav.php`, `footer.php`
- `config/app.php`, `config/database.php`, `config/defaults.json`
- `assets/css/app.css`, `assets/js/app.js`
- `database/schema.sql`, `database/seed.sql`

## Pages principales
- `societes` — liste des sociétés avec recherche, CSV, suppression
- `societe&id=X` — détail d'une société (associés, contrats, collaborateurs)
- `associes`, `contrats` — listes similaires
- `creation` — wizard 3 étapes avec formulaire dynamique JS
- `configuration` — gestion des tables de référence (8 onglets)

## Base de données
- Hôte: `127.0.0.1:3306`, DB: `center_domiciliation`, user: `root`, pass: (vide)
- Tables: `societes`, `associes`, `contrats`, `collaborateurs`
- Tables de réf: `ref_tribunaux`, `ref_ste_adresses`, `ref_nationalites`, `ref_lieux_naissance`, `ref_activites`
- Import: `mysql -u root center_domiciliation < database/import.sql`

## Design system
- Boutons: `.btn` (transparent, bordure 2px), variantes: `.btn-secondary`, `.btn-danger`, `.btn-cancel`, `.btn-back`, `.btn-info`, `.btn-next`
- Icônes MDI obligatoires sur tous les boutons
- Table action buttons: `.btn-icon` avec icône MDI seule
- Layout: `<section class="grid two">`, `<section class="card">`, `<article class="card">`
- Stats: `<section class="stats">` > `<article class="stat">`
- Info grid: classe `.info-grid` pour affichage clé-valeur

## Commandes
- Démarrer XAMPP (Apache + MySQL)
- Importer DB: `mysql -u root center_domiciliation < database/import.sql`
- Accès: `http://localhost/center-domiciliation-app/index.php?page=dashboard`

## Sessions précédentes
_Cette section sera mise à jour automatiquement par le serveur de mémoire._
