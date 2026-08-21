# Mapping concepts Dolibarr → implémentation dans ce projet

Chaque concept Dolibarr a un équivalent concret dans les conventions du projet (voir AGENTS.md). Suivre ce mapping pour toute nouvelle fonctionnalité ERP.

## Table de correspondance

| Concept Dolibarr | Implémentation projet | Fichiers clés |
|---|---|---|
| Module (`modX.class.php`) | Groupe de pages + entrée sidebar + permissions | `pages/{groupe}/`, `includes/navigation.php`, `index.php` (allowlist `$pageDir`) |
| Permissions `{module}.{action}` | `has_permission('groupe.action')` vérifiée sur boutons ET handlers | table rôles/permissions, `includes/fonctions.php` |
| CommonObject (classe métier) | Fonctions fetch CRUD + tables dédiées, pas de classe — helpers procéduraux | `fetch_record()`, `fetch_all_records()`, `includes/fonctions.php` |
| Trigger (`runTrigger`) | `auto_notify_action()` / `log_activity()` appelés après chaque action métier | `includes/fonctions.php` (17 types) |
| Hook (`formObjectOptions`...) | Points d'injection dans les pages : blocs conditionnels `if (function_exists(...))` ou sections ancrées des fiches | `societe_details.php` (sections `#suivi`, `#historique`...) |
| Workflow statuts | Colonne `statut` + constantes PHP + badges UI + timeline | `activity_logs`, `.badge-*` CSS |
| Numérotation `FA-YYYY-NNN` | `next_dossier_number(?PDO, $prefix, $column)` avec whitelist colonnes | `includes/fonctions.php` (`DOM-YYYY-NNN`, `CRE-YYYY-NNN`, `CES-YYYY-NNN`) |
| Documents générés (GED) | `uploaded_docs` + `uploads/{module}/{id}/` + liens via `word_url()` | `pages/dossiers/societe_suivi.php` |
| Templates PDF/ODT | Templates DOCX + `DocumentRenderer` + conversion PDF (LibreOffice/Word COM/Dompdf) | `src/rendu_document.php`, `src/analyseur_templates.php`, `templates/` |
| Extrafields | Colonnes ajoutées via migration auto (`database/migrations/`) — pas d'UI admin pour l'instant | `includes/migrations.php` |
| API REST `/api/index.php` | `api.php` JSON (auth + CSRF + whitelist) à étendre en REST tokenisé si besoin | `api.php`, `assets/js/table-editor.js` |
| Boxes dashboard | `<section class="stats">` + `dashboard_count()` | `pages/accueil/dashboard.php` |
| Listes filtrées/triées | Recherche `search_term()`/`like_term()` + `data-sortable` + column toggle localStorage | pages liste, `assets/js/app.js` |
| Export CSV/Excel | `export_csv()` / `export_excel()` (PhpSpreadsheet) | `includes/fonctions.php` |
| Import wizard | `import_excel_preview()` + `import_excel_confirm()` | `includes/fonctions.php` |

## Recettes détaillées

### 1. Nouveau module « gestion X » (ex. fournisseurs, abonnements)

1. **DB** : migration `database/migrations/YYYYMMDD_HHMMSS_xxx.sql` (table `xxx` + index) — appliquée automatiquement au prochain chargement
2. **Pages** : `pages/{groupe}/x_liste.php` (liste), `x_details.php` (fiche), éventuellement wizard `_steps/`
3. **Routing** : ajouter les pages dans l'allowlist `index.php` + `$pageDir`
4. **Navigation** : entrée dans `includes/navigation.php` (section existante ou nouvelle)
5. **Permissions** : `x.view/create/edit/delete/export` dans la grille des rôles ; masquer boutons ET refuser handlers
6. **Triggers** : `auto_notify_action('create'|'update'|'delete', 'x', $id, $description)` après chaque mutation
7. **Liste** : recherche + tri (`data-sortable`/`data-col`) + export CSV + suppression confirmée (`data-confirm`)
8. **Fiche** : sections ancrées (`.anchor-nav`), timeline historique, upload documents si pertinent

### 2. Workflow statuts type facture

```php
// Constantes en tête de page ou fichier lib
const STATUT_BROUILLON = 0;
const STATUT_VALIDE    = 1;
const STATUT_PAYE      = 2;

// Transitions autorisées (matrice)
const TRANSITIONS = [
    STATUT_BROUILLON => [STATUT_VALIDE],
    STATUT_VALIDE    => [STATUT_PAYE],
    STATUT_PAYE      => [],
];

function transition_autorisee(int $de, int $vers): bool {
    return in_array($vers, TRANSITIONS[$de] ?? [], true);
}
```

- Handler POST : charger statut courant → vérifier transition → UPDATE → trigger log/notification → redirect
- Transition interdite : `set_flash('error', 'Transition non autorisée')` + redirect sans modification
- UI : badge coloré par statut + boutons d'action conditionnels au statut courant

### 3. Numérotation automatique

Utiliser exclusivement `next_dossier_number(?PDO $pdo, string $prefix, string $column)` :
- Ajouter la colonne à la whitelist de la fonction AVANT usage
- Format produit : `PREFIX-YYYY-NNN` (max+1 par année)
- Appeler à l'init du wizard/création, stocker définitivement

### 4. Endpoint REST JSON (extension api.php)

Suivre le pattern existant `api.php` :
1. Auth session obligatoire (+ permission spécifique par action)
2. CSRF pour les mutations venant du navigateur ; token dédié pour appels serveur-à-serveur
3. Whitelist stricte tables/colonnes par action
4. Réponse JSON uniforme : `['ok' => bool, 'data' => ..., 'error' => ...]`
5. Jamais d'écho de données brutes DB sans validation

### 5. Champs personnalisés (extrafields simplifiés)

Option minimale viable : migration ALTER TABLE + affichage conditionnel.
Option avancée (si besoin récurrent) : table `champs_personnalises(entity, code, label, type, ordre)` + table valeurs `entity_champs_valeurs(entity_id, champ_id, valeur)` + rendu générique dans les fiches.

## Anti-patterns à éviter

- Créer une classe métier OOP dans ce projet procédural — rester cohérent (helpers + fonctions)
- Loger l'activité manuellement dans chaque page avec du SQL ad-hoc — toujours passer par `log_activity()`/`auto_notify_action()`
- Dupliquer la logique de numérotation hors `next_dossier_number()`
- Modifier directement une donnée maître synchronisée avec Dolibarr des deux côtés
- Rendre un POST utilisateur dépendant d'un appel externe synchrone non protégé (try/catch + mode dégradé)
