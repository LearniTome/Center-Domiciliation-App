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
│   ├── defaults.json      # Valeurs par defaut pour le wizard
│   └── templates.php      # Configuration templates (types, aliases, formes juridiques)
├── database/
│   ├── schema.sql         # Structure des tables + tables de reference
│   ├── seed.sql           # Donnees de reference + exemples
│   └── import.sql         # Schema + seed combines
├── includes/
│   ├── bootstrap.php      # Session, config, connexion DB, flash
│   ├── db.php             # Singleton PDO
│   ├── functions.php      # 20+ fonctions utilitaires
│   ├── header.php         # <head>, sidebar, flash/error display
│   ├── nav.php            # Navigation laterale
│   └── footer.php         # JS + fermeture HTML
├── src/
│   ├── TemplateEditor.php # Editeur WYSIWYG de templates DOCX (round-trip HTML <-> Word XML)
│   └── TemplateAnalyzer.php # Analyse et detection de variables dans les templates
├── templates/             # Templates DOCX organises par forme juridique
│   ├── _Racine-Actifs/    # Templates generiques (Statuts, Contrat, Attestation...)
│   ├── SARL AU/           # Templates specifiques SARL AU
│   ├── SARL/              # Templates specifiques SARL
│   ├── SA/                # Templates specifiques SA
│   ├── _References/       # Documents de reference
│   └── _Guides/           # Guides d'utilisation
├── pages/
│   ├── dashboard.php      # Tableau de bord avec statistiques
│   ├── creation.php       # Assistant 3 etapes (wizard)
│   ├── configuration.php  # Configuration unifiee (toutes les tables de reference)
│   ├── societes.php       # Liste des societes + recherche + export CSV
│   ├── societe.php        # Fiche detail d'une societe
│   ├── associes.php       # Liste des associes
│   ├── contrats.php       # Liste des contrats
│   ├── collaborateur.php  # Fiche detail d'un collaborateur
│   ├── collaborateurs.php # Gestion des collaborateurs
│   ├── generation.php     # Generation de documents depuis les templates
│   ├── documents.php      # Historique des documents generes
│   ├── templates.php      # Gestion des templates (import, scan, suppression)
│   ├── template.php       # Fiche detail d'un template (vars detectees, mapping)
│   ├── template_edit.php  # Editeur WYSIWYG temps-reel (A4, toolbar, variables)
│   ├── formes-juridiques.php # Gestion des formes juridiques
│   ├── adresses.php       # Adresses de reference
│   ├── villes.php         # Gestion des villes
│   ├── nationalites.php   # Gestion des nationalites
│   ├── lieux-naissance.php # Lieux de naissance
│   ├── qualites-associe.php # Qualites d'associe
│   ├── setup.php          # Instructions XAMPP
│   └── not-found.php      # Page 404
├── docs/setup/XAMPP_SETUP.md  # Documentation complete XAMPP
└── AGENTS.md              # Guide pour l'agent OpenCode
```

## Pages disponibles

| Page | URL | Description |
|---|---|---|
| Tableau de bord | `?page=dashboard` | Stats, dernieres entrees, acces rapide |
| Nouveau dossier | `?page=creation` | Assistant 3 etapes avec associes dynamiques |
| Configuration | `?page=configuration` | Gestion unifiee de toutes les tables de reference (onglets) |
| Societes | `?page=societes` | Liste + recherche + export CSV |
| Fiche societe | `?page=societe&id=N` | Detail d'une societe (associes, contrats, collaborateurs) |
| Associes | `?page=associes` | Liste + suppression |
| Contrats | `?page=contrats` | Liste + suppression |
| Collaborateur | `?page=collaborateur&id=N` | Fiche detail d'un collaborateur |
| Collaborateurs | `?page=collaborateurs` | Gestion (ajout, modification, suppression) |
| Formes juridiques | `?page=formes-juridiques` | Gestion des formes juridiques |
| Adresses | `?page=adresses` | Adresses de reference |
| Villes | `?page=villes` | Gestion des villes |
| Nationalites | `?page=nationalites` | Gestion des nationalites |
| Lieux naissance | `?page=lieux-naissance` | Lieux de naissance de reference |
| Qualites associe | `?page=qualites-associe` | Qualites d'associe |
| Templates | `?page=templates` | Liste, import, scan des templates DOCX |
| Detail template | `?page=template&path=...` | Detection des variables, mapping DB |
| Editeur template | `?page=template_edit&path=...` | Editeur WYSIWYG temps-reel (A4, toolbar couleur/taille/police) |
| Generation | `?page=generation` | Generation de documents depuis les templates |
| Documents | `?page=documents` | Historique des documents generes |

## Technologie

- **PHP 8.x** procedural vanilla, `declare(strict_types=1)`
- **MySQL/MariaDB** via PDO (requetes preparees, parametres nommes)
- **Zero dependances** — pas de Composer, pas de framework, pas de npm
- **CSS personnalise** — design system avec variables CSS, grille, sans framework externe
- **JavaScript vanilla** — confirmation avant suppression, formulaire d'associes dynamique
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
