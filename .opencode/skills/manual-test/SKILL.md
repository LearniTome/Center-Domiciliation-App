---
name: manual-test
description: Checklist de validation manuelle avant commit pour le projet Center Domiciliation
license: MIT
compatibility: opencode
---

# Skill Test Manuel

Utilise cette skill avant de commit une modification, pour valider que tout fonctionne.

## Checklist pré-commit

### PHP
- [ ] `php.exe -l pages/ma-page.php` — pas d'erreur de syntaxe
- [ ] `declare(strict_types=1);` en première ligne du PHP
- [ ] Pas de `var_dump()`, `print_r()`, `dd()` ou `exit()` debug oubliés
- [ ] Les fonctions inutilisées ne sont pas importées

### Navigation
- [ ] La page s'affiche sans erreur Apache (pas de white screen, pas de 500)
- [ ] Les liens du menu/ sidebar pointent vers la bonne page
- [ ] Le titre de page (`$pageTitle`) est correct
- [ ] Le sous-titre (`$pageSubtitle`) s'affiche si défini

### Formulaire
- [ ] `<?= csrf_input() ?>` présent dans tout formulaire POST
- [ ] `verify_csrf();` appelé dans le handler POST
- [ ] Redirect-after-POST : `redirect_to('page')` après succès
- [ ] Message flash affiché après action (succès/erreur)
- [ ] Les champs requis ont l'attribut `required`

### Tableaux
- [ ] `<table data-sortable>` présent
- [ ] `<th data-col="Nom">` sur les colonnes triables
- [ ] Colonnes non triables (checkbox, actions) sans `data-col`
- [ ] `<div class="table-scroll">` autour du tableau si scroll horizontal

### UI
- [ ] Icône Material Symbol avant chaque texte de bouton : `<span class="material-symbols-outlined">icon_name</span>`
- [ ] Pas de `padding:` inline sur les boutons
- [ ] Les messages/valeurs sont en français
- [ ] Responsive : la page tient dans 980px de large
- [ ] Thème sombre respecté (variables CSS, pas de couleurs en dur)

### Base de données
- [ ] Pas d'erreur PDO (vérifier la connexion MySQL)
- [ ] Les noms de paramètres dans les requêtes préparées sont nommés (`:nom`)
- [ ] Les colonnes ajoutées existent bien dans la table
