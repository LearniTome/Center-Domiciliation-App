---
name: ui-design
description: Applique la charte CSS et les patterns UI du projet Center Domiciliation à chaque nouveau composant ou page
license: MIT
compatibility: opencode
---

# Skill UI/UX Design

Utilise cette skill quand tu crées ou modifies une page/interface dans ce projet PHP vanilla.

## Principes généraux
- Framework CSS : custom (variables CSS dans `assets/css/app.css`), zéro librairie externe
- Icônes : Material Symbols Outlined via Google Fonts (`<span class="material-symbols-outlined">icon_name</span>`)
- Polices : Rubik (Google Fonts)
- Langue : français uniquement

## Layout
- `.shell` — grille CSS `260px 1fr` (sidebar + main)
- `.main` — `overflow-y: auto; height: 100vh` (le contenu scrolle)
- `.card` — article avec fond panel, bordure arrondie
- `.card.stack` — card avec `display:flex;flex-direction:column;gap:1rem`
- `.grid.two` — grille 2 colonnes pour formulaires/sections
- `.stats` — `display:flex;gap:1rem` avec `.stat` enfants
- `.table-scroll` — wrapper pour scroll horizontal des tableaux

## Couleurs (variables CSS)
- `--primary: #4a6cf7` (bleu) — actions principales, liens
- `--success: #00d25b` (vert) — statuts actifs, validations
- `--danger: #fc424a` (rouge) — suppressions, erreurs
- `--info: #8f5fe8` (violet) — infos, exploration
- `--bg: #1a1d23` / `--panel: #22252d` / `--text: #e8e8e8` — thème sombre

## Boutons
- Base : `.btn` ou `button[type="submit"]` — fond transparent, bordure 2px `var(--primary)`
- Variants : `.btn-secondary` (gris), `.btn-danger` (rouge), `.btn-cancel` (gris), `.btn-back` (orange `#ff6b35`), `.btn-info` (violet), `.btn-next` (vert `#00b894`)
- Toujours un `<span class="material-symbols-outlined">icon_name</span>` avant le texte
- Jamais de `padding:` inline sur les boutons (CSS global 6px 14px)
- Tableau : `.btn-icon` pour les actions (icône seule, sans texte)

## Tableaux
- `<table data-sortable>` + `<th data-col="Nom">` sur les colonnes triables
- Colonnes non triables (cases à cocher, actions) : pas de `data-col`
- En-têtes : `<th>` avec `data-col` pour le tri automatique via `app.js`
- Wrapper : `<div class="table-scroll">` pour le scroll horizontal
- État vide : `<p class="table-empty">Aucun(e) ...</p>`

## Titre de page
- `<h1>` géré par `header.php` via `$pageTitle`
- Sous-titre optionnel : définir `$pageSubtitle` dans la page, rendu automatiquement

## Autres patterns
- `info-grid` — grille 2 colonnes pour affichage clé/valeur
- `section-header` — flex entre titre et actions (filtres, boutons)
- `help-text` — texte d'aide sous le titre
- `flash flash-success` / `flash flash-error` — messages flash
- `statut-badge actif` (vert) / `statut-badge resilie` (rouge) — badges de statut
