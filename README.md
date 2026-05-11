# Center Domiciliation App

Application PHP compatible XAMPP pour la gestion des dossiers de domiciliation : societes, associes, contrats et collaborateurs.

## Prérequis

- XAMPP (Apache + MySQL/MariaDB) ou tout serveur PHP 8.x + MySQL
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

## Installation

1. Cloner ou copier le projet dans `C:\xampp\htdocs\Center-Domiciliation-App`
2. Demarrer **Apache** et **MySQL** depuis le panneau XAMPP
3. Creer la base de donnees et importer le schema + donnees initiales :
   ```
   mysql -u root center_domiciliation < database/import.sql
   ```
   Ou via phpMyAdmin : importer `database/import.sql`
4. Ouvrir `http://localhost/Center-Domiciliation-App/`

## Structure

```
├── index.php              # Point d'entree unique (front controller ?page=)
├── assets/
│   ├── css/app.css        # Design system personnalise (CSS variables)
│   └── js/app.js          # Vanilla JS : confirmations, formulaires dynamiques
├── config/
│   ├── app.php            # Nom de l'app, URL de base
│   ├── database.php       # Acces MySQL (host, port, user, password)
│   └── defaults.json      # Valeurs par defaut pour le wizard
├── database/
│   ├── schema.sql         # Structure des tables + tables de reference
│   ├── seed.sql           # Donnees de reference + exemples
│   └── import.sql         # Schema + seed combines
├── includes/
│   ├── bootstrap.php      # Session, config, connexion DB, flash
│   ├── db.php             # Singleton PDO
│   ├── functions.php      # 20 fonctions utilitaires
│   ├── header.php         # <head>, sidebar, flash/error display
│   ├── nav.php            # Navigation laterale
│   └── footer.php         # JS + fermeture HTML
├── pages/
│   ├── dashboard.php      # Tableau de bord avec statistiques
│   ├── creation.php       # Assistant 3 etapes (wizard)
│   ├── societes.php       # Liste des societes + recherche + export CSV
│   ├── societe.php        # Fiche detail d'une societe
│   ├── associes.php       # Liste des associes
│   ├── contrats.php       # Liste des contrats
│   ├── collaborateurs.php # Gestion des collaborateurs
│   ├── documents.php      # Generation de documents
│   ├── setup.php          # Instructions XAMPP
│   └── not-found.php      # Page 404
├── docs/setup/XAMPP_SETUP.md  # Documentation complete XAMPP
└── AGENTS.md              # Guide pour l'agent OpenCode
```

## Pages disponibles

| Page | URL | Description |
|---|---|---|
| Tableau de bord | `?page=dashboard` | Stats, dernieres entrees, acces rapide |
| Nouveau dossier | `?page=creation` | Assistant 3 etapes avec associates dynamiques |
| Societes | `?page=societes` | Liste + recherche + export CSV + fiche detail |
| Associes | `?page=associes` | Liste + suppression |
| Contrats | `?page=contrats` | Liste + suppression |
| Collaborateurs | `?page=collaborateurs` | Gestion complete (ajout, modification, suppression) |

## Technologie

- **PHP 8.x** procedural vanilla, `declare(strict_types=1)`
- **MySQL/MariaDB** via PDO (requetes preparees, parametres nommes)
- **Zero dependances** — pas de Composer, pas de framework, pas de npm
- **CSS personnalise** — design system avec variables CSS, grille, sans framework externe
- **JavaScript vanilla** — confirmation avant suppression, formulaire d'associes dynamique
- **Protection CSRF** integree sur tous les formulaires POST

## Conventions de code

- `declare(strict_types=1)` en haut de chaque fichier PHP
- `<?= e($var) ?>` pour tout affichage HTML (escaping via `htmlspecialchars`)
- Interface en francais uniquement
- Soumission POST avec token CSRF puis redirection (Post/Redirect/Get)
- Messages flash via `set_flash('success'|'error', 'message')`
- Toutes les requetes DB avec PDO prepared statements et parametres nommes (`:param`)
