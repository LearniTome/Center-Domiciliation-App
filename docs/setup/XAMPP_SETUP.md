# Installation XAMPP

## Objectif

Faire tourner l'application depuis `C:\xampp\htdocs\Center-Domiciliation-App` avec Apache, MySQL et phpMyAdmin.

## Etapes

1. Installer XAMPP pour Windows.
2. Copier ce projet dans `C:\xampp\htdocs\Center-Domiciliation-App`.
3. Ouvrir le panneau XAMPP et lancer `Apache` et `MySQL`.
4. Ouvrir phpMyAdmin via `http://localhost/phpmyadmin`.
5. Creer une base `center_domiciliation` ou importer directement `database/schema.sql` puis `database/seed.sql`.
6. Verifier `config/database.php`.
7. Ouvrir `http://localhost/Center-Domiciliation-App/`.

## Configuration par defaut

- Hote: `127.0.0.1`
- Port: `3306`
- Base: `center_domiciliation`
- Utilisateur: `root`
- Mot de passe: vide par defaut sous XAMPP

## Import via phpMyAdmin

1. Cliquer sur `Nouvelle base de donnees`
2. Nommer la base `center_domiciliation`
3. Onglet `Importer`
4. Importer `database/schema.sql`
5. Importer `database/seed.sql`

## Limites de cette premiere version

- La generation de documents Word/PDF n'est pas encore re-implemente en PHP.
- Cette base couvre surtout les flux CRUD principaux.
- La structure MySQL est une premiere approximation basee sur les entites visibles du projet d'origine.

