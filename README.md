# Center Domiciliation App

Application PHP compatible XAMPP pour la gestion des dossiers de domiciliation : societes, associes, contrats et collaborateurs.

## Prérequis

- XAMPP (Apache + MySQL/MariaDB) ou tout serveur PHP 8.x + MySQL
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

## Installation

1. Cloner ou copier le projet dans `C:\xampp\htdocs\Center-Domiciliation-App`
2. Copier `.env.example` vers `.env` et ajuster si besoin (base de donnees, cle API IA) :
   ```
   copy .env.example .env
   ```
3. Demarrer **Apache** et **MySQL** :
   - **Automatiquement** : lancer `run.ps1` (PowerShell)
   - **Manuellement** : depuis le panneau XAMPP Control Panel
4. Creer la base de donnees et importer le schema + donnees initiales :
   ```
   mysql -u root center_domiciliation < database/import.sql
   ```
   Ou via phpMyAdmin : importer `database/import.sql`
5. Ouvrir `http://localhost/Center-Domiciliation-App/`

## Structure

```
├── index.php              # Point d'entree unique (front controller ?page=)
├── run.ps1                # Lanceur XAMPP (Apache + MySQL)
├── api.php                # Endpoint API JSON (table-editor)
├── router.php             # Routeur serveur PHP integre
├── .env.example           # Modele de configuration (.env local, ignore par git)
├── AGENTS.md              # Guide pour l'agent OpenCode
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
│   ├── amorcage.php          # Session, config, connexion DB, flash
│   ├── base_donnees.php      # Singleton PDO
│   ├── migrations.php        # Migration automatique du schema DB
│   ├── fonctions.php         # 20+ fonctions utilitaires
│   ├── rendu_configuration.php # Rendu onglets configuration
│   ├── entete.php            # <head>, sidebar, flash/error display
│   ├── navigation.php        # Navigation laterale
│   └── pied_page.php         # JS + fermeture HTML
│
├── pages/
│   ├── accueil/
│   │   ├── dashboard.php     # Tableau de bord avec statistiques
│   │   └── notifications.php # Notifications
│   │
│   ├── dossiers/
│   │   ├── societes_liste.php       # Liste + recherche + export CSV
│   │   ├── societe_details.php      # Fiche detail d'une societe
│   │   ├── associes_liste.php       # Liste des associes
│   │   ├── associe_details.php      # Fiche detail d'un associe
│   │   ├── collaborateurs_liste.php # Gestion des collaborateurs
│   │   ├── collaborateur_details.php# Fiche detail d'un collaborateur
│   │   ├── contrats_liste.php       # Liste des contrats
│   │   └── creation_steps/          # Wizard 6 etapes
│   │       ├── _init.php
│   │       ├── _main.php
│   │       ├── step_01_Societe.php
│   │       ├── step_02_Associes.php
│   │       ├── step_03_Contrat.php
│   │       ├── step_04_Recap.php
│   │       ├── step_05_Upload.php
│   │       └── step_06_Generation.php
│   │
│   ├── modification-juridique/
│   │   ├── modifications_juridiques.php
│   │   └── cession/
│   │       ├── cessions_liste.php          # Liste des cessions
│   │       ├── cession_details_dossier.php # Detail dossier cession
│   │       └── cession_steps/              # Wizard 3 etapes
│   │           ├── _init.php
│   │           ├── _main.php
│   │           ├── step_00_Mode.php
│   │           ├── step_01_Societe.php
│   │           ├── step_02_Associes.php
│   │           ├── step_03_Parts.php
│   │           ├── step_04_Recap.php
│   │           ├── step_05_Upload.php
│   │           └── step_06_Generation.php
│   │
│   ├── templates/
│   │   ├── templates.php       # Import, scan des templates DOCX
│   │   ├── template.php        # Detail template (vars detectees, mapping)
│   │   ├── template_edit.php   # Editeur WYSIWYG temps-reel
│   │   ├── generation.php      # Generation de documents
│   │   ├── documents.php       # Historique des documents generes
│   │   └── download_all.php    # Telechargement groupe
│   │
│   ├── outils/
│   │   ├── analyse-couverture.php # Analyse de couverture des variables
│   │   ├── variables.php          # Gestion des variables
│   │   ├── defaults.php           # Valeurs par defaut
│   │   ├── convert-word-pdf.php   # Conversion Word en PDF
│   │   └── ai-assistant.php       # Assistant IA Claude
│   │
│   ├── configuration/
│   │   ├── configuration.php  # Configuration unifiee (onglets)
│   │   ├── setup.php          # Instructions XAMPP
│   │   ├── roles.php / role.php # Gestion des roles
│   │   ├── activites.php / activite.php
│   │   ├── activites-ompic.php
│   │   ├── notifications-manage.php / notif-ajax.php
│   │   ├── adresses.php / villes.php / tribunaux.php
│   │   ├── formes-juridiques.php
│   │   ├── nationalites.php / lieux-naissance.php
│   │   └── qualites-associe.php
│   │
│   └── auth/
│       ├── connexion.php      # Page de connexion
│       ├── deconnexion.php    # Deconnexion
│       └── not-found.php      # Page 404
│
├── src/
│   ├── analyseur_templates.php # TemplateAnalyzer : analyse et modification .docx
│   ├── rendu_document.php      # DocumentRenderer : rendu DOCX avec contexte
│   ├── service_claude.php      # ClaudeService : integration IA Anthropic
│   └── editeur_templates.php   # TemplateEditor : edition WYSIWYG DOCX
│
├── templates/             # Templates DOCX par forme juridique
│   ├── _Racine-Actifs/
│   ├── SARL AU/
│   ├── SARL/
│   ├── SA/
│   ├── _References/
│   └── _Guides/
│
├── scripts/               # Scripts d'outillage (.ps1/.sh/.cmd)
│   ├── run.sh             # Lanceur macOS/Linux (MySQL + PHP intégré)
│   ├── setup.ps1          # Setup Windows (XAMPP)
│   ├── setup.sh           # Setup macOS/Linux (Homebrew)
│   ├── dev-server.ps1     # Serveur PHP intégré multi-projets
│   ├── sync.ps1           # Sync DB + git
│   ├── post-push-sync.ps1 # Export DB après push
│   ├── git-push.cmd       # Push + export DB
│   ├── chrome-debug.ps1   # Chrome remote debugging (MCP)
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
| Nouveau dossier | `?page=creation` | Assistant 6 etapes avec associes dynamiques |
| Societes | `?page=societes` | Liste + recherche + export CSV |
| Fiche societe | `?page=societe&id=N` | Detail d'une societe (associes, contrats, collaborateurs) |
| Associes | `?page=associes` | Liste + suppression |
| Fiche associe | `?page=associe&id=N` | Detail d'un associe |
| Collaborateurs | `?page=collaborateurs` | Gestion (ajout, modification, suppression) |
| Fiche collaborateur | `?page=collaborateur&id=N` | Fiche detail d'un collaborateur |
| Contrats | `?page=contrats` | Liste + suppression |
| Cessions | `?page=cessions` | Liste des cessions de parts |
| Nouvelle cession | `?page=cession` | Wizard 3 etapes (societe, cedant/cessionnaire, recap) |
| Detail cession | `?page=cession_dossier&id=N` | Detail dossier cession avec documents |
| Modifications juridiques | `?page=modifications-juridiques` | Liste des modifications |
| Templates | `?page=templates` | Liste, import, scan des templates DOCX |
| Detail template | `?page=template&path=...` | Detection des variables, mapping DB |
| Editeur template | `?page=template_edit&path=...` | Editeur WYSIWYG temps-reel (A4, toolbar complete) |
| Generation | `?page=generation` | Generation de documents depuis les templates |
| Documents | `?page=documents` | Historique des documents generes |
| Analyse couverture | `?page=analyse-couverture` | Analyse de couverture des variables |
| Variables | `?page=variables` | Gestion des variables et mapping |
| Valeurs par defaut | `?page=defaults` | Valeurs par defaut pour le wizard |
| Word to PDF | `?page=convert-word-pdf` | Conversion de documents Word en PDF |
| Assistant IA | `?page=ai-assistant` | Chat avec Claude (IA Anthropic) |
| Configuration | `?page=configuration` | Gestion unifiee de toutes les tables de reference (onglets) |

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
