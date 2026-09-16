# Rapport d'audit — Center Domiciliation App

> **Date de l'audit** : 16 septembre 2026
> **Périmètre** : code source complet (hors `vendor/`), base de données, assets frontend, tests, dépôt Git, documentation
> **Méthode** : analyse statique automatisée + exploration dirigée + exécution de la suite de tests + revue de l'historique Git

---

## 1. Synthèse exécutive

Application PHP vanilla (sans framework) de gestion des dossiers de domiciliation de sociétés, fonctionnant sur XAMPP (Apache + MySQL). Le projet est **mature, bien architecturé et sécurisé sur l'essentiel**, avec un taux de conformité élevé aux conventions internes (CSRF systématique, échappement HTML généralisé, requêtes préparées).

| Domaine | Verdict | Note |
|---|---|---|
| Sécurité | **Solide** — CSRF partout, pas d'injection SQL, pas de secrets versionnés | A- |
| Architecture & organisation | **Excellente** pour du PHP vanilla (front controller, allowlist, wizards modulaires) | A |
| Qualité du code | **Bonne** (strict_types 93 %, prepared statements 274×, redirect-after-POST systématique) | B+ |
| Base de données | **Fonctionnelle** mais désynchronisée (`schema.sql` obsolète vs migrations) | C+ |
| Frontend | **Sobre et cohérent**, mais assets monolithiques et accessibilité lacunaire | B- |
| Tests | **Complets sur le cœur métier** : 17 tests / 51 assertions → **OK** | B+ |
| Maintenabilité | Bonne, souillée par 60 scripts de debug dans `tmp_test/` | B |
| Déploiement | CI/CD GitHub Actions → Heberjahiz en place | B+ |

**Points noirs à traiter en priorité** :
1. `database/schema.sql` et `database/import.sql` **ne reflètent plus la vraie base** (11 tables et 9 colonnes manquantes).
2. Migration cassée `20260612_000004_societe_source.sql` (colonnes inexistantes → erreur 42S22 récurrente à chaque chargement).
3. **993 commits / 4 participants / branches multiples** — le dépôt mérite un nettoyage (déchets `tmp_test/`, exports DB périmés).
4. Assets frontend monolithiques (CSS 5 993 lignes, JS 1 923 lignes) sans build ni minification.

---

## 2. Contexte et stack technique

### 2.1 Vue d'ensemble

| Élément | Valeur |
|---|---|
| Projet | App de gestion de dossiers de domiciliation / création de sociétés (Maroc) |
| Langage | PHP 8.2 (XAMPP), procédural sans framework, `declare(strict_types=1)` |
| Base de données | MySQL `center_domiciliation` (utf8mb4), 33 tables |
| Fichiers PHP (hors vendor) | **~175** |
| Fichiers totaux versionnés | 4 096 |
| Templates DOCX | **180** répartis en 13 dossiers par forme juridique |
| Dépendances Composer | `phpword ^1.4`, `dompdf ^3.1`, `phpspreadsheet ^5.8` + `phpunit ^11` (dev) |
| Nombre de commits | **993** depuis le 26/07/2025 |
| Routes (front controller) | 62 pages dans l'allowlist |
| Wizards | 3 (Création 6 étapes, Cession 7 étapes, PV AGO 8 étapes) = 27 fichiers d'étapes |

### 2.2 Versions

| Composant | Installé | Dernière stable | Statut |
|---|---|---|---|
| PHP | 8.2.12 | 8.3/8.4 | ⚠️ support sécu jusqu'à fin 2026 |
| phpoffice/phpword | 1.4.0 | 1.4.0 | ✅ |
| dompdf/dompdf | 3.1.6 | 3.1.6 | ✅ (CVE corrigées) |
| phpoffice/phpspreadsheet | 5.9.0 | 5.9.0 | ✅ |
| phpunit/phpunit | 11.5.56 | 11.x | ✅ |

---

## 3. Audit d'architecture

### 3.1 Patterns dominants (bien appliqués)

- **Front controller unique** (`index.php`, 299 lignes) : autoroute `?page=` avec **allowlist de 62 slugs** + mapping `$pageDir`. Pages inconnues → 404 propre. Pages auth/connexion sans layout.
- **Pages autonomes** (`pages/{groupe}/{page}.php`) : logique PHP en tête (POST → CSRF → traitement → `redirect_to()`), HTML en bas. Aucun rendu sur POST (110+ appels `redirect_to()`, déclaré `: never`).
- **Wizards à états** : fichiers d'étapes séparés (`_steps/`), état mémorisé en session, numérotation de dossier auto-générée `DOM-YYYY-NNN` / `CRE-YYYY-NNN` / `CES-YYYY-NNN`.
- **Couche service `src/`** : `DocumentRenderer` (rendu DOCX avec boucles `{%p for %}`), `TemplateAnalyzer` (analyse/réécriture de variables), `ClaudeService` (intégration Anthropic via cURL, configurable).
- **Séparation claire** : `includes/` (bootstrap + helpers), `pages/` (UI), `src/` (logique métier réutilisable), `config/`, `assets/`.

### 3.2 Points de vigilance

- **`includes/fonctions.php` est un méga-fichier** (>1700 lignes, 40+ helpers). À terme, découper par domaine (routing, db, security, files).
- **Dépendance à l'auto-migration pour la cohérence du schéma** : une base montée depuis `schema.sql` seul est incomplète (cf. §5).
- **Logique de pages dupliquée** entre les 3 wizards (init, upload, generation) — des extractions vers `src/` réduiraient la duplication.
- **Pas d'autoloader** (choix assumé pour PHP vanilla) : chaque page fait ses `require`. Cohérent mais interdit le lazy-loading.

---

## 4. Audit sécurité

### 4.1 Résultats par axe

| Axe | Résultat | Détail |
|---|---|---|
| CSRF | ✅ Systématique | 76 `verify_csrf()` (tous les handlers POST) + 120 `csrf_input()`. `api.php` fait une validation manuelle `hash_equals()` correcte. `ajax/generation.php` aussi. **Aucun formulaire POST sans CSRF.** |
| XSS | ✅ | `e()` = `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`, utilisé dans 63 fichiers (~1 384 sites de sortie). Les 12 usages directs de `htmlspecialchars()` sont contextuels et sûrs (PDF, XML, flash). |
| Injection SQL | ✅ | 274 `prepare()` dans 43 fichiers. Les 93 `query()` sont des requêtes constantes. Toute interpolation SQL est protégée par **whitelist de tables/colonnes** (`api.php`, `fonctions.php`, `rendu_configuration.php`). Où que des valeurs soient injectées, elles passent par placeholders nommés. |
| Commandes shell | ⚠️ Contrôlées | 16 `shell_exec()` répartis dans 3 fichiers (`rendu_document.php`, `analyseur_templates.php`, `convert-word-pdf.php`) — tous avec `escapeshellarg()`, fichiers internes uniquement (pipeline LibreOffice DOCX→PDF). |
| `eval`/`exec`/`system`/`passthru` en code projet | ✅ **0** | (présents uniquement dans vendor) |
| Superglobales | ✅ | `$_POST`/`$_GET` toujours avec valeur par défaut + `field_value()`/`int_value()`/casts `(int)`. **0 usage de `$_REQUEST`.** |
| Path traversal fichiers | ✅ | `_inspecteur.php`/`_editeur.php` : `realpath()` + `str_starts_with($path, realpath($templatesDir))`. Uploads : `move_uploaded_file()` + allowlists extensions/MIME. |
| Secrets / credentials | ✅ | Aucun mot de passe dur. `.env`, `.env.remote` gitignorés. `.gitleaks.toml` configuré. |

### 4.2 Détails sécurité

- Échappement correct des identifiants de **nombres de dossiers auto-générés** : la séquence `sprintf('%s-%s-%03d', prefix, year, max+1)` est forcée, pas d'injection possible.
- `next_dossier_number()` et les helpers `fetch_*` de `fonctions.php` sont protégés par whitelist de colonnes.
- L'injection paramétrée `IN (...)` est générée via placeholders `:ph0, :ph1...` — jamais de concaténation de valeurs.

### 4.3 Améliorations recommandées

1. **`content-security-policy` absent** (pas d'en-tête CSP) — risqué à ajouter sans test car le code injecte des styles inline (12 fichiers avec `<style>`, 35 avec `<script>`), mais à étudier.
2. **Pas de rate-limiting** sur `connexion.php` ni d'are-you-human — une attaque par force brute sur l'auth est possible.
3. **Session PHPSESSID** : vérifier les flags `HttpOnly`, `SameSite` et le renouvellement d'ID après login (à confirmer dans `includes/amorcage.php`).
4. **`dashboard_count()`** (`fonctions.php:163`) interpole `$table` sans whitelist — actuellement code mort (0 appelant) mais à sécuriser par défense en profondeur.
5. Les exports DB (`database/exports/`) contiennent des données réelles — s'assurer que le dossier reste hors Git ou que les exports sont nettoyés avant commit.

---

## 5. Audit base de données

### 5.1 État des lieux

33 tables finales : **18 tables métier** (`societes`, `associes`, `contrats`, `collaborateurs`, `cessions`, `cession_parts`, `pv_ago`, `pv_resolutions_templates`, `documents_generes`, `uploaded_docs`, `notifications`, `user_sessions`, `activity_logs`, `societe_suivi_etapes`/`_documents`, `cession_suivi_etapes`/`_documents`, `centre_affaires`) + **10 tables de référence** + **4 tables RBAC** + **1 table infrastructure** (`_migrations`).

### 5.2 Problèmes identifiés

| # | Gravité | Problème | Impact |
|---|---|---|---|
| 1 | **HAUTE** | `schema.sql` et `import.sql` **obsolètes** (21 tables documentées sur 33 ; 9 colonnes manquantes — `societe_tp`, `societe_cnss`, `societe_source`, `societe_sigle`, `created_by`, `associe_duree_gerance`, `ville`/`code_postal` sur `ref_ste_adresses`, `cession_id`/`pv_ago_id` sur `documents_generes`). | Tout rebuild de base "propre" produit un schéma incomplet, réparé indirectement par l'auto-migration. **Régénérer `schema.sql` depuis la base réelle.** |
| 2 | **HAUTE** | Migration cassée `20260612_000004_societe_source.sql` : INSERT sur colonnes `permission_label`/`permission_category` inexistantes → **42S22** traité comme erreur dure par `migrations.php`. | La migration ne s'applique jamais ; erreur affichée en bandeau à chaque page. Corriger le SQL (colonnes réelles : `nom`, `permission_key`, `category`, `description`). |
| 3 | MOYENNE | Types signés/non signés : `documents_generes.cession_id`/`pv_ago_id` (INT) vs `cessions.id`/`pv_ago.id` (INT UNSIGNED) ; absence de FK sur ces colonnes. | Risque d'erreurs implicites de conversion / orphelins. |
| 4 | MOYENNE | Index manquants sur colonnes filtrées/joinées : `societe_raison_sociale` (LIKE), numéros de dossier (pas UNIQUE → doublons possibles), `societe_type_generation`, `societe_date_exp_cert_neg`, `associe_date_validite_cin`, `contrat_date_fin`, `contrat_statut`, `collaborateurs.email`. | Requêtes liste/échéances lentes à volume. |
| 5 | BASSE | Pas de FK entre tables métier et tables de référence (VARCHAR dénormalisés, choix design assumé). | Pas d'intégrité référentielle — orphelins possibles. |
| 6 | BASSE | `societe_suivi_etapes` sans `COLLATE=utf8mb4_unicode_ci` (dérive). Ordre-dépendance de la migration `20260824_120000`. Doublons logiques de permissions (ids 39-59, `INSERT IGNORE`). 2 hashes de mot de passe "admin123" différents entre `seed.sql` et `seed_rbac.sql` — un des deux est faux. | Data quality / docs. |
| 7 | BASSE | `database/exports/*.sql` : les 3 dumps les plus récents sont **invalides** (erreur 1932 `activity_logs` — tablespace corrompu à un moment). Dernier export exploitable : `2026-06-12` (25 tables, pré-suivi/centre). | Backups périmés / non restaurables. |
| 8 | BASSE | **Dérive doc** : `AGENTS.md` référence une table `page_views` inexistante (le suivi de pages passe par `activity_logs`). | Confusion. |

### 5.3 Points forts

- FK `societe_id` en **CASCADE** sur le master `societes` (suppression propre), `collaborateurs` en **SET NULL** (préservation de l'historique).
- Auto-migration **idempotente** (ignore 42S21/42S01/1091/1061/1826/121), échec explicite sur erreurs inattendues (42S22) — principe sain, seul le contenu d'une migration est défectueux.
- Indexation correcte de `notifications` (ciblage user/role/type/global) et `activity_logs` (timeline).
- Expo backfill : migrations de données (statuts 'finalise'→'Valider', nombre d'activités ≥3, réparation numéros de dossier).

---

## 6. Audit qualité du code

### 6.1 Conformité aux conventions internes

| Convention | Taux | Détail |
|---|---|---|
| `declare(strict_types=1)` | **93,1 %** (163/175) | Les 12 manquants = 4 partials HTML, 3 scripts d'outillage, 5 déchets `tmp_test/`. Tout le code applicatif est conforme. |
| CSRF | **100 %** des POST | cf. §4.1 |
| `<?= e()` | 63 fichiers | Seuls les contextes PDF/XML/flash utilisent `htmlspecialchars()` directement (justifié). |
| Redirect-after-POST | **100 %** | 110+ `redirect_to()` ; aucun rendu sur POST. |
| Préparation des requêtes | 274 `prepare()` | Interpolation toujours whitelistée. |
| try/catch | 89 blocs / 28 fichiers | Couche service + génération couverte. Les listes CRUD s'appuient sur `$dbError` global (géré par le front controller — acceptable). |

### 6.2 Points de vigilance qualité

- **`includes/fonctions.php` : >1 700 lignes** — à fractionner.
- **`tmp_test/` : 60 scripts de debug** dans le dépôt — déchets techniques incomplets qui affaiblissent la lisibilité, échouent à la convention `strict_types` et **ne devraient pas être versionnés** (à ajouter au `.gitignore`).
- **`database/exports/` (7 fichiers) et `backups/`, `output/`, `_debug_output/`** : contenus intermédiaires à exclure du versionnage.
- 12 fichiers avec `<style>` inline et 35 avec `<script>` inline : JS/CSS spécifiques aux pages non centralisés dans `assets/`.
- Réponse `notif-ajax` contourne le layout HTML (choix d'architecture assumé, à documenter).
- **Dérives de documentation** : `AGENTS.md` mentionne `page_views` (inexistante) et la pagination `page-count` est un résidu.

---

## 7. Audit frontend

### 7.1 Assets

| Asset | Taille | Contenu |
|---|---|---|
| `assets/css/app.css` | **5 993 lignes / 158 Ko** | Design system dark complet (~250+ règles), variables CSS, responsive (10 media queries, x4 blocs print), animations. |
| `assets/js/app.js` | **1 923 lignes / 84 Ko** | IIFE vanilla : sidebar/nav collapse, colonnes, tri tableaux, distributions capital, groupes d'activités, calculs contrat, remplissage IA, notifications (poll 30 s, toasts, chime), import Excel 2 étapes. |
| `assets/js/table-editor.js` | **429 lignes / 20 Ko** | Création rapide, édition inline (dbl-clic), édition en masse — via `api.php`. |

### 7.2 Constats

- **Zéro dépendance du côté navigateur** (hors Google Fonts Rubik/Material + html2pdf sur 3 pages wizard). RAW DOM, `data-*` attributes. Cohérent avec la stack.
- **Assets monolithiques non minifiés**, versionnés par `?v=filemtime()` — nettoyage et cache efficace, mais aucune étape de build, aucun source map, aucun découpage par page.
- **Accessibilité très lacunaire** : ~10 attributs ARIA seulement, pas de `sr-only`, pas de focus trap dans les modales, pas de skip-link, boutons icônes sans nom accessible (titres `title` seulement). Les modales se ferment par Escape/clic overlay sans gestion du focus.
- **Responsive desktop-first** (que des `max-width`), breakpoints 480/760/820/900/980. Le zoom global `--page-zoom: 0.85` compense mais peut créer des surprises sur petits écrans.
- **CSS dupliqué / conflits d'évolution organique** : `.stats` défini 2×, `.modal-overlay` 2 mécanismes (`display:none+.show` vs `opacity+.open`), règles `.perms-table` répétées, `}` orpheline ligne 2871. Un nettoyage ciblé réduirait le CSS de ~10-15 %.
- **Assets morts** : `assets/fonts/MaterialSymbolsOutlined.woff2` (318 Ko) jamais référencé (le CDN est utilisé à la place) ; confirmer l'usage des `assets/img/generation*.png`.

### 7.3 Recommandations frontend

1. Supprimer le woff2 local ou ajouter un `@font-face` avec fallback CDN (perf hors-ligne).
2. Introduire au moins une **barrière de sécurité d'accessibilité** : un composant `<dialog>` natif (ou focus trapping) pour les modales, des `aria-label` sur les boutons-icônes.
3. Corriger les doublons CSS en profondeur (audit ciblé des blocs dupliqués).
4. Déplacer les ~35 blocs `<script>` inline vers des fichiers/taches (ou au minimum vers un seul fichier `page.js` chargé de façon conditionnelle).

---

## 8. Audit tests

### 8.1 Exécution de la suite (ce jour, PHP 8.2.12, PHPUnit 11.5.56)

```
OK (17 tests, 51 assertions)
```

### 8.2 Couverture

| SUT | Tests | Couvre |
|---|---|---|
| `TemplateAnalyzer` | ✓ | Extraction de variables, rename, delete, split-runs corrigés |
| `DocumentRenderer` | ✓ | Rendu `_VAR_`, fusion tokens coupés, boucle `cession_parts`, tri par longueur (régression collisions) |
| `tests/Support/DocxFixture.php` | — | Fixture DOCX réutilisable |

**Points faibles** :
- 17 tests couvrent uniquement `src/` (le cœur métier). **Aucun test fonctionnel sur `pages/`** (wizards, CRUD, uploads) ni sur l'API `api.php`.
- Pas de tests de base de données (intégration) — les migrations (ex. cassée `20260612_000004`) ne sont pas vérifiées au CI.
- Pas de CI d'exécution automatique des tests dans le workflow GitHub Actions (le workflow ne teste que le déploiement).

### 8.3 Recommandations

1. Ajouter une étape `phpunit` au workflow CI (au moins sur `main`).
2. Ajouter des tests d'intégration minimaux sur `api.php` (whitelists, CSRF) et sur le pipeline migration (détection 42S22).
3. Idéalement, un smoke-test HTTP : les 62 routes autorisées répondent 200/302.

---

## 9. Audit DevOps & déploiement

### 9.1 Dépôt Git

| Métrique | Valeur |
|---|---|
| Commits | 993 |
| Participants | 4 (Devopsabdel 666, LearniTome 157, devopsabdel 141, DevopsAbdel 31) |
| Branches locales | `main`, `php`, `php-haja`, `sql` |
| Branches distantes | `develop`, `php-haja`, `php-almoudir`, `main`, ... |
| Période | 26/07/2025 → 13/09/2026 (~13,5 mois) |
| Taille pack | ~46 Mo |
| Fichiers suivis | 4 096 |

### 9.2 Workflow

- **Pipeline** : push `main` → GitHub Actions `deploy-heberjahiz.yml` → zip + extracteur auto-destructeur → FTPS cPanel `app.centirio.ma`.
- **Multi-committers / branches multiples** : stratégie de branching clarifiée (seul `main` déploie). À consolider pour limiter les merges croisés.
- **`docs/ROADMAP.md` tenu à jour** — bon réflexe, unique source pour le suivi.

### 9.3 Recommandations DevOps

1. **Nettoyage du dépôt** : supprimer `tmp_test/`, `database/exports/` périmés, `backups/`, `output/`, `_debug_output/`, `composer.phar` (137 Mo inutiles en versionnage), le woff2 mort. Ajouter les règles `.gitignore` correspondantes.
2. Ajouter **PHPUnit dans le CI** et, si possible, `php -l` sur les fichiers modifiés.
3. **`AGENTS.md`** : corriger la doc `page_views`, et l'enrichir du double workflow suivi (il est déjà bien complet).
4. Veiller à ce que `.env.remote` reste strictement local (déjà gitignoré) — jamais de credentials prod dans l'historique.
5. **Planifier la montée PHP 8.3/8.4** (8.2 EOL support sécurité fin 2026) : tester zip/com_dotnet après upgrade XAMPP.

---

## 10. Audit documentation

| Doc | État |
|---|---|
| `README.md` | ✅ Complet (installation, arborescence, pages, stack) |
| `AGENTS.md` | ✅ Très complet (architecture, conventions, helpers, wizards, DB, deploy, MCP, CLI). 2 dérives (`page_views`, ci-dessus). |
| `docs/ROADMAP.md` | ✅ Structuré, à jour |
| `docs/` (9 autres fichiers) | ✅ Guides vars, wizard, cession, XAMPP, deploy |
| `docs/DESIGN_SYSTEM.md` | ✅ A côté de la charte réellement appliquée |
| Graphify out (`graphify-out/`) | ✅ Graphe de connaissances régénéré |

**Documentation exceptionnelle pour un projet de cette taille** — la plus grande richesse de maintenabilité du projet.

---

## 11. Forces du projet

1. **Sécurité systemàtique** : le trio CSRF / `e()` / prepared-statements est appliqué à 100 % des chemins — rare.
2. **Architecture claire sans framework** : front controller + allowlist + pages autonomes + wizards modulaires.
3. **Wizards métier riches** : 3 parcours de génération de documents (création, cession, PV AGO) avec validation IA, uploads contraints, capital auto-distribué.
4. **DOCX expertise** : 180 templates, renderer tolérant aux split-runs, analyseur, conversion DOCX→PDF multi-stratégies (LibreOffice / Word COM / PHPWord+Dompdf).
5. **Intégration IA Claude** sur 4 surfaces (remplissage, validation, clauses, chat) derrière une fonction `isAvailable()` propre.
6. **RBAC complet** (16 rôles, 38+ permissions, surcharges par collaborateur).
7. **Auto-migration DB** multi-PC fonctionnelle malgré une migration défectueuse.
8. **Workflows de déploiement et d'outillage** soignés (skills/commands/skills opencode, MCP servers, dev-server).

---

## 12. Plan d'action priorisé

| Priorité | Action | Justification | Effort |
|---|---|---|---|
| **P0** | Corriger la migration `20260612_000004_societe_source.sql` (colonnes réelles) | Supprime l'erreur 42S22 permanente + risque de divergence prod | Faible |
| **P0** | Régénérer `schema.sql` + `import.sql` depuis la vraie base (manquant : 11 tables, 9 colonnes) | Fiabilise tout rebuild de base | Moyen |
| **P1** | Ajouter PHPUnit au workflow CI sur `main` | Empêche la régression des 17 tests | Faible |
| **P1** | Nettoyer le dépôt : `tmp_test/`, exports SQL invalides, `composer.phar`, woff2 mort + `.gitignore` | Hygiène, taille, lisibilité des PR | Faible |
| **P1** | Index/UQ manquants : numéros de dossier (UNIQUE), `societe_type_generation`, `societe_raison_sociale`, dates d'échéance, `collaborateurs.email` | Performance à volume + empêche doublons | Moyen |
| **P2** | Reverse-engineering `dashboard_count()` (whitelist) | Défense en profondeur | Faible |
| **P2** | Réduire `fonctions.php` (>1700 lignes) par découpage | Maintenabilité | Moyen |
| **P2** | Composer `<dialog>`/focus management + `aria-label` boutons icônes | Accessibilité | Moyen |
| **P3** | Auditer les doublons CSS + déplacer les scripts inline | Perf + nettoyage | Moyen |
| **P3** | Rate-limiting / verrouillage sur la connexion | Anti force-brute | Faible |
| **P3** | Montée PHP 8.3/8.4 (EOL 8.2) | Sécurité runtime | Moyen |

---

## 13. Conclusion

**Projet solide, très bien tenu.** Le socle (sécurité, architecture, conventions, documentation, gestion de la génération DOCX) est d'un niveau nettement supérieur à la moyenne des applications PHP vanilla. Les deux problèmes les plus urgents sont **purement de synchronisation** (migration cassée + `schema.sql` obsolète) et non de conception ; ils sont corrigeables en quelques heures. L'hygiène du dépôt (déchets `tmp_test/`, exports invalides) et l'accessibilité frontend sont les deux améliorations ciblées à engager ensuite, suivies par la couverture de tests au-delà de `src/`.

**Score global : 16/20** (8,5/10 sécurité & architecture ; 7/10 héritage & hygiène ; 8/10 tests avec périmètre à élargir).