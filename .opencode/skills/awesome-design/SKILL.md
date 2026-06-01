---
name: awesome-design
description: Design system complet du projet — charte CSS, boutons, layout, animations, responsive, bonnes pratiques UI
license: MIT
compatibility: opencode
---

# Awesome Design Skill

Design system du projet Center Domiciliation. Applique ces règles à chaque nouvelle page ou composant.

## Layout global
- `.shell` — grille CSS `260px 1fr` (sidebar + main container)
- `.shell.collapsed` → `60px 1fr`
- `.main` : `overflow-y: auto; height: 100vh` (le contenu scrolle)
- `.main` padding : `1.5rem` (desktop), `1rem` (< 980px)
- `.card` — fond `var(--panel)`, bordure `1px solid var(--line)`, rayon `var(--radius-lg)`, padding `1.5rem`
- `.card.stack` — flex column gap `1rem`
- `.grid.two` — `display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem`

## Couleurs (CSS variables — thème sombre)
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--primary` | `#4a6cf7` | Actions principales, liens |
| `--success` | `#00d25b` | Validation, statut actif |
| `--danger` | `#fc424a` | Suppression, erreur |
| `--info` | `#8f5fe8` | Exploration, info |
| `--bg` | `#1a1d23` | Fond de page |
| `--panel` | `#22252d` | Fond des cartes |
| `--panel-strong` | `#2a2d35` | Fond plus soutenu |
| `--text` | `#e8e8e8` | Texte principal |
| `--text-secondary` | `#8892a0` | Texte secondaire |
| `--line` | `#2e3038` | Bordures, séparateurs |

## Système de boutons
**Base** : `.btn` ou `button[type="submit"]`
- Fond transparent, bordure 2px `var(--primary)`, couleur `var(--primary)`
- Padding : `6px 14px` (NE JAMAIS mettre de padding inline)
- Icône MDI obligatoire avant le texte : `<span class="mdi mdi-xxx"></span>` + texte

**Variants** (ordre alphabétique) :
| Classe | Bordure | Hover |
|--------|---------|-------|
| `.btn-back` | `#ff6b35` | `rgba(255,107,53,0.12)` |
| `.btn-cancel` | `var(--text-secondary)` | `rgba(136,146,160,0.12)` |
| `.btn-danger` | `var(--danger)` | `rgba(252,66,74,0.12)` |
| `.btn-info` | `var(--info)` | `rgba(143,95,232,0.12)` |
| `.btn-next` | `#00b894` | `rgba(0,184,148,0.12)` |
| `.btn-secondary` | `var(--text-secondary)` | `rgba(136,146,160,0.12)` |

**Couleur par rôle** :
- Vert (`.btn-next`) — création : "Créer un dossier", "Nouveau", "Ajouter", "Suivant", "Générer"
- Violet (`.btn-info`) — exploration : "Voir tout", "Exporter CSV", "Remplir automatiquement"
- Orange (`.btn-back`) — retour : "Retour", "Réinitialiser"
- Gris (`.btn-cancel`) — annulation : "Annuler"
- Rouge (`.btn-danger`) — destructif : supprimer, effacer

**Boutons d'action dans les tableaux** : `.btn-icon` (icône seule, sans texte)
- Voir → `mdi-eye`, Modifier → `mdi-pencil`, Supprimer → `mdi-delete` + `class="btn-icon danger"`

## Tableaux
- `<div class="table-scroll">` pour le scroll horizontal
- `<table data-sortable>` + `<th data-col="Nom">` sur les colonnes triables
- Colonnes non triables (checkbox, actions) : pas de `data-col`
- Tri automatique via `app.js` (clic sur en-tête alterne asc/desc)
- État vide : `<p class="table-empty">Aucun(e) ...</p>`

## En-tête de section
```html
<div class="section-header">
    <div>
        <h2>Titre</h2>
    </div>
    <div class="table-actions">
        <!-- filtres + boutons -->
    </div>
</div>
```
- `.section-header` : flex row, espacement entre titre et actions
- `.table-actions` : flex row avec `gap:8px`, alignement centré
- `.filter-bar` : groupe de filtres (liens/boutons + badges de comptage)

## Stats
```html
<section class="stats">
    <article class="stat">
        <span>Libellé</span>
        <strong>Valeur</strong>
    </article>
</section>
```
- `.stats` : `display:flex; gap:1rem`
- `.stats.compact` : gap réduit, stats plus petites
- `.stat` : fond panel, padding, texte centré

## Info grid (détail d'enregistrement)
```html
<div class="info-grid">
    <div><span class="label">Nom</span><span>Valeur</span></div>
</div>
```
- `.info-grid` : `display:grid; grid-template-columns: repeat(2, 1fr); gap: 1rem`
- `.label` : texte secondaire, petit, uppercase

## Badges et statuts
- `.statut-badge.actif` — fond `rgba(0,210,91,0.12)` + `var(--success)`
- `.statut-badge.resilie` — fond `rgba(252,66,74,0.12)` + `var(--danger)`
- `.badge` — petit compteur rond, utilisé dans les filtres
- `.pill` — petit tag pour section/catégorie

## Animations
- `@keyframes spin` — rotation 360deg (loading overlay)
- `@keyframes pulse-glow` — box-shadow pulsé (step-card actif)
- `@keyframes pop-done` — scale 1→1.15→1 (step-card terminé)
- `@keyframes slideDown` — slide de -8px à 0 (flash messages)

## Loading overlay
```html
<div id="loading-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center">
    <div class="loader-card">
        <div class="spinner"></div>
        <p id="loading-text">Message...</p>
    </div>
</div>
```
- CSS dans `app.css` (`#loading-overlay`, `.spinner`, `.loader-card`)
- Afficher via `window.showOverlay('message')`

## Responsive (< 980px)
- Sidebar forcée à 60px, s'étend en overlay fixe avec backdrop (`.main::after`)
- `.table-scroll` : margin 0 -1rem, padding 0 1rem
- `.card` : padding 1rem
- `.main` : padding 1rem

## Print
- `.shell` → `display:block`
- Sidebar, toolbar, actions masqués
- `.a4-page` : box-shadow enlevé, overflow visible

## Règles d'or
1. Toujours une icône MDI avant le texte d'un bouton
2. Jamais de `padding:` inline sur les boutons
3. Pas de couleurs en dur — utiliser les variables CSS
4. Langue : français uniquement (UI, labels, messages)
5. Thème sombre obligatoire — pas de thème clair
6. `<?= e($var) ?>` pour toutes les sorties HTML
7. `content-visibility: auto` sur les lignes de tableaux longs pour perf
