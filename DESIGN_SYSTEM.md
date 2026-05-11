# Design System — Corona Dark (Center Domiciliation)

## Palette
| Token | Hex | Usage |
|---|---|---|
| `--bg` | `#000000` | Page background |
| `--panel` | `#191c24` | Cards, sidebar, navbar |
| `--panel-strong` | `#1f2430` | Card hover, table stripes |
| `--text` | `#ffffff` | Body text |
| `--text-secondary` | `#6c7293` | Muted text, labels |
| `--line` | `#2c2e33` | Borders, dividers |
| `--primary` | `#0090e7` | Primary buttons, links |
| `--primary-hover` | `#0078c2` | Primary hover |
| `--success` | `#00d25b` | Success states |
| `--danger` | `#fc424a` | Danger buttons, delete |
| `--danger-hover` | `#e0353d` | Danger hover |
| `--warning` | `#ffab00` | Warning states |
| `--info` | `#8f5fe8` | Info states |

## Typographie
- **Famille**: `'Rubik', sans-serif` (polic. Light/Regular/Medium/Bold)
- **Body**: 400 / 1rem / line-height 1.5
- **Titres**: 600 (h1: 2rem, h2: 1.5rem)
- **Muted**: `#6c7293`

## Composants

### Cards
- Fond: `#191c24`, border: `1px solid #2c2e33`, radius: `8px`, padding: `1.25rem`
- Pas de box-shadow (flat design)
- Title: white, `0.875rem` uppercase letter-spacing

### Boutons
- Primary: `#0090e7`, hover: `#0078c2`, radius: `4px`, padding: `10px 20px`
- Secondary: `#2c2e33`, hover: `#3a3d45`
- Danger: `#fc424a`, hover: `#e0353d`
- Pas de box-shadow, transition 200ms

### Tableaux
- Fond: transparent, border: `1px solid #2c2e33`
- En-têtes: `#6c7293` uppercase small
- Lignes hover: `#1f2430`
- Stripes: `rgba(255,255,255,0.02)`

### Formulaires
- Input/select: fond `#191c24`, border `#2c2e33`, text `#fff`
- Focus: border `#0090e7`, pas de box-shadow
- Placeholder: `#6c7293`

### Sidebar
- Fond: `#191c24`, border-right: `1px solid #2c2e33`
- Nav items: border-radius `4px`, hover: fond `#1f2430`
- Active: border-left `3px solid #0090e7`

## Icônes
- Material Design Icons (`@mdi/font`)
- Taille: `1.25rem` dans la nav, `1rem` dans les boutons

## Espacements
- Section padding: `1.5rem`
- Card padding: `1.25rem`
- Grid gap: `1rem`
- Stack gap: `0.75rem`

## Animations
- `transition: 200ms ease` sur hover/focus
- Flash: `slideDown` 300ms
