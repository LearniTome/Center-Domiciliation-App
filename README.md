# Center Domiciliation App

Application PHP compatible XAMPP pour la gestion des dossiers de domiciliation : societes, associes, contrats et collaborateurs.

## Prérequis

- XAMPP (Apache + MySQL/MariaDB) ou tout serveur PHP 8.x + MySQL
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

## Installation

1. Cloner ou copier le projet dans `C:\xampp\htdocs\Center-Domiciliation-App`
2. Demarrer **Apache** et **MySQL** :
   - **Automatiquement** : lancer `run.ps1` (PowerShell)
   - **Manuellement** : depuis le panneau XAMPP Control Panel
3. Creer la base de donnees et importer le schema + donnees initiales :
   ```
   mysql -u root center_domiciliation < database/import.sql
   ```
   Ou via phpMyAdmin : importer `database/import.sql`
4. Ouvrir `http://localhost/Center-Domiciliation-App/`

## Structure

```
├── index.php              # Point d'entree unique (front controller ?page=)
├── run.ps1                # Lanceur XAMPP (Apache + MySQL)
├── AGENTS.md              # Guide pour l'agent OpenCode
├── CLAUDE.md              # Memoire projet IA
├── .gitignore
├── opencode.json
│
├── assets/
│   ├── css/app.css        # Design system personnalise (CSS variables)
│   ├── js/app.js          # Vanilla JS : confirmations, formulaires dynamiques
│   └── img/               # Captures d'ecran
│
├── config/
│   ├── app.php            # Nom de l'app, URL de base
│   ├── database.php       # Acces MySQL (host, port, user, password)
│   ├── defaults.json      # Valeurs par defaut pour le wizard
│   └── templates.php      # Configuration templates (types, aliases, formes juridiques)
│
├── database/
│   ├── schema.sql         # Structure des tables + tables de reference
│   ├── seed.sql           # Donnees de reference + exemples
│   └── import.sql         # Schema + seed combines
│
├── includes/
│   ├── bootstrap.php      # Session, config, connexion DB, flash
│   ├── db.php             # Singleton PDO
│   ├── functions.php      # 20+ fonctions utilitaires
│   ├── header.php         # <head>, sidebar, flash/error display
│   ├── nav.php            # Navigation laterale
│   └── footer.php         # JS + fermeture HTML
│
├── pages/                 # 22 pages
│   ├── dashboard.php      # Tableau de bord avec statistiques
│   ├── creation.php       # Assistant 3 etapes (wizard)
│   ├── configuration.php  # Configuration unifiee (tables de reference)
│   ├── societes.php       # Liste + recherche + export CSV
│   ├── societe.php        # Fiche detail d'une societe
│   ├── associes.php       # Liste des associes
│   ├── contrats.php       # Liste des contrats
│   ├── collaborateurs.php # Gestion des collaborateurs
│   ├── collaborateur.php  # Fiche detail d'un collaborateur
│   ├── templates.php      # Import, scan des templates DOCX
│   ├── template.php       # Detail template (vars detectees, mapping)
│   ├── template_edit.php  # Editeur WYSIWYG temps-reel
│   ├── generation.php     # Generation de documents
│   ├── documents.php      # Historique des documents generes
│   ├── analyse-couverture.php # Analyse de couverture des variables
│   ├── variables.php      # Gestion des variables
│   ├── defaults.php       # Valeurs par defaut
│   ├── convert-word-pdf.php # Conversion Word en PDF
│   ├── formes-juridiques.php
│   ├── adresses.php
│   ├── villes.php
│   ├── nationalites.php
│   ├── lieux-naissance.php
│   ├── qualites-associe.php
│   ├── setup.php          # Instructions XAMPP
│   └── not-found.php      # Page 404
│
├── src/
│   ├── TemplateEditor.php # Editeur WYSIWYG DOCX (round-trip HTML <-> Word XML)
│   └── TemplateAnalyzer.php # Analyse et detection de variables
│
├── templates/             # Templates DOCX par forme juridique
│   ├── _Racine-Actifs/
│   ├── SARL AU/
│   ├── SARL/
│   ├── SA/
│   ├── _References/
│   └── _Guides/
│
├── scripts/               # Utilitaires
│   ├── renomme_variables_docx.php
│   ├── renomme_variables_docx.ps1
│   └── _check_coverage.php
│
├── docs/                  # Documentation
│   ├── analysis.md
│   ├── DESIGN_SYSTEM.md
│   ├── Guide-Variables-Creation-Dossier.md
│   ├── Guide-Variables-Templates-Documents.md
│   ├── php-xampp-migration-master-prompt.md
│   ├── wizard-etapes-4-5.md
│   └── setup/
│
└── backups/               # Sauvegardes (ignore par git)
```

## Pages disponibles

| Page | URL | Description |
|------|-----|-------------|
| Tableau de bord | `?page=dashboard` | Stats, dernieres entrees, acces rapide |
| Nouveau dossier | `?page=creation` | Assistant 3 etapes avec associes dynamiques |
| Configuration | `?page=configuration` | Gestion unifiee de toutes les tables de reference (onglets) |
| Societes | `?page=societes` | Liste + recherche + export CSV |
| Fiche societe | `?page=societe&id=N` | Detail d'une societe (associes, contrats, collaborateurs) |
| Associes | `?page=associes` | Liste + suppression |
| Contrats | `?page=contrats` | Liste + suppression |
| Collaborateurs | `?page=collaborateurs` | Gestion (ajout, modification, suppression) |
| Fiche collaborateur | `?page=collaborateur&id=N` | Fiche detail d'un collaborateur |
| Templates | `?page=templates` | Liste, import, scan des templates DOCX |
| Detail template | `?page=template&path=...` | Detection des variables, mapping DB |
| Editeur template | `?page=template_edit&path=...` | Editeur WYSIWYG temps-reel (A4, toolbar complete) |
| Analyse couverture | `?page=analyse-couverture` | Analyse de couverture des variables |
| Variables | `?page=variables` | Gestion des variables et mapping |
| Generation | `?page=generation` | Generation de documents depuis les templates |
| Documents | `?page=documents` | Historique des documents generes |
| Word to PDF | `?page=convert-word-pdf` | Conversion de documents Word en PDF |
| Valeurs par defaut | `?page=defaults` | Valeurs par defaut pour le wizard |
| Formes juridiques | `?page=formes-juridiques` | Gestion des formes juridiques |
| Adresses | `?page=adresses` | Adresses de reference |
| Villes | `?page=villes` | Gestion des villes |
| Nationalites | `?page=nationalites` | Gestion des nationalites |
| Lieux naissance | `?page=lieux-naissance` | Lieux de naissance de reference |
| Qualites associe | `?page=qualites-associe` | Qualites d'associe |

## Technologie

- **PHP 8.x** procedural vanilla, `declare(strict_types=1)`
- **MySQL/MariaDB** via PDO (requetes preparees, parametres nommes)
- **Zero dependances** — pas de Composer, pas de framework, pas de npm
- **CSS personnalise** — design system avec variables CSS, grille, sans framework externe (~1116 lignes)
- **JavaScript vanilla** — sidebar toggle, column toggle, wizard, calculs capitaux SARL, confirmation avant suppression
- **Editeur WYSIWYG DOCX** — edition de templates Word en temps reel (format A4, toolbar complete : gras, italique, souligne, taille police, couleur texte, surlignage, alignements, tableaux, listes, insertion de variables)
- **Round-trip DOCX ↔ HTML** — conversion bidirectionnelle preservant la mise en forme (styles inline, tableaux, listes)
- **Protection CSRF** integree sur tous les formulaires POST

## Conventions de code

- `declare(strict_types=1)` en haut de chaque fichier PHP
- `<?= e($var) ?>` pour tout affichage HTML (escaping via `htmlspecialchars`)
- Interface en francais uniquement
- Soumission POST avec token CSRF puis redirection (Post/Redirect/Get)
- Messages flash via `set_flash('success'|'error', 'message')`
- Toutes les requetes DB avec PDO prepared statements et parametres nommes (`:param`)
