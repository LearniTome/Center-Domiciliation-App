---
name: database
description: Gère le schéma MySQL, les migrations, les seed et les imports de la base center_domiciliation
license: MIT
compatibility: opencode
---

# Skill Base de Données

Utilise cette skill pour toute opération sur la base MySQL du projet.

## Connexion
- Host: `127.0.0.1:3306`, DB: `center_domiciliation`, user: `root`, pass: vide
- Fichier de config : `config/database.php`
- Connexion via PDO dans `includes/bootstrap.php`

## Fichiers
- `database/schema.sql` — structure complète (tables + ref tables)
- `database/seed.sql` — données de démo
- `database/import.sql` — import combiné (source unique)
- Import : `mysql -u root center_domiciliation < database/import.sql`

## Tables principales
- `societes` — entreprises domiciliées
- `associes` — associés liés aux sociétés
- `contrats` — contrats de domiciliation
- `collaborateurs` — collaborateurs des sociétés

## Tables de référence
- `ref_formes_juridiques` — formes juridiques (SARL, SA, etc.) avec colonne `template_folder`
- `ref_tribunaux` — tribunaux de commerce
- `ref_ste_adresses` — adresses possibles
- `ref_nationalites` — nationalités
- `ref_lieux_naissance` — lieux de naissance
- `ref_activites` — activités
- `ref_qualites_associe` — qualités d'associé

## Migration (ajout colonne)
```sql
ALTER TABLE ref_formes_juridiques ADD COLUMN template_folder VARCHAR(120) DEFAULT '' NOT NULL AFTER forme_juridique;
UPDATE ref_formes_juridiques SET template_folder = 'SARL AU' WHERE forme_juridique = 'SARL AU';
UPDATE ref_formes_juridiques SET template_folder = 'SARL' WHERE forme_juridique = 'SARL';
UPDATE ref_formes_juridiques SET template_folder = 'SA' WHERE forme_juridique = 'SA';
```

## Règles
- Toujours utiliser des requêtes préparées PDO avec paramètres nommés (`:nom`)
- Pas d'ORM, pas de query builder — SQL brut
- Les helpers de requêtes sont dans `includes/functions.php`
- Pour une nouvelle table de référence, ajouter aussi l'onglet dans `pages/configuration.php`
