# Feuille de route — Center Domiciliation App

> Mémoire de planification du protocole CTO (`/plan`, `/execute`, `/modify`).
> Les conventions et l'architecture vivent dans `AGENTS.md` — ce fichier suit uniquement les tâches.

## Audit technique (2026-08-21)

| Composant | Installé | Dernière stable | Statut |
|---|---|---|---|
| PHP (XAMPP) | 8.2.12 | 8.3.x / 8.4.x | ⚠️ 8.2 support sécurité jusqu'à fin 2026 |
| phpoffice/phpword | 1.4.0 | 1.4.0 | ✅ à jour |
| dompdf/dompdf | 3.1.6 | 3.1.6 | ✅ à jour (CVE corrigées) |
| phpoffice/phpspreadsheet | 5.9.0 | 5.9.0 | ✅ à jour |
| Extensions PHP | zip, pdo_mysql, com_dotnet, curl, gd, mbstring | — | ✅ toutes actives |

**Structure** : 77 fichiers PHP dans `pages/` (7 groupes + `_steps/`), `src/` (renderer, analyseur, éditeur, services), `includes/`, `database/migrations/` (auto-migration au chargement).

## Tâches en attente

### SaaS multi-tenancy — Architecture (2026-09-27)

#### État actuel (audit post-commit df5199d)
| Élément | Constat |
|---|---|
| RBAC | **Implémenté** : `users` (connexion), `user_roles` + `user_permissions`, `roles.scope` (centre/cabinet), `is_system` remplace le `role_id === 1` dur |
| Comptes de connexion | **Table `users`** opérationnelle (cabinet + Centre), auto-login dev dans `amorcage.php` |
| Multi-tenancy | `cabinet_id` sur 12 tables métier + vues cloisonnées (`v_dossiers_accessibles`, `v_cessions_accessibles`) |
| Bypass super-admin | `has_permission()` utilise `roles.is_system` (plus de dur `role_id === 1`) |
| Isolation | `assert_tenant_access()` + `tenant_scope()` sur fetch/listes/API/documents ; pages détail partiel (documents ✅, societe_details ❌) |
| Périmètre d'isolation | 87 couples : documents_generes ✅, societes ✅ (liste), associes ✅ (liste), cessions ✅ (liste + vues), others en cours |

#### Décisions retenues (KISS, sans question utilisateur)
1. **`users` séparé de `collaborateurs`** — un cabinet n'a pas de « collaborateur de société ». `users.cabinet_id NULL` = interne Centre (non facturé).
2. **Base partagée, discriminant `cabinet_id` nullable** — les 10 467 lignes prod existantes restent à `NULL` (= Centre), aucune migration de données destructrice.
3. **`roles` étendu, pas de table neuve** — ajout de `roles.scope` (`centre`/`cabinet`) + `is_billable`. Les 16 rôles existants sont conservés (aucune régression) ; `is_internal=0` (Expert-comptable, Avocat, Notaire…) devient `scope='cabinet'`.
4. **`user_roles` canonique, `users.role_id` conservé** comme rôle primaire dénormalisé (compatibilité avec les ~16 lectures de `$user['role_id']`).
5. **Garde IDOR centrale** (`assert_tenant_access()`) plutôt que 87 éditions mécaniques : bloque la lecture croisée `?id=` en un point.
6. **Aucune Librairie ajoutée** — le besoin est de l'RBAC maison, pas d'un framework. Composer reste à 3 libs.

#### Phase 1 — Fondation données
- [x] `20260927_100000_saas_cabinets.sql` — `cabinets`, `plans`
- [x] `20260927_100001_saas_facturation.sql` — `abonnements`, `paiements`, `factures`
- [x] `20260927_100002_saas_users.sql` — `users`, `user_roles`, `user_permissions` + backfill depuis `collaborateurs`
- [x] `20260927_100003_saas_roles_permissions.sql` — `roles.scope` / `is_billable`, 6 rôles canoniques, 18 permissions SaaS, matrices
- [x] `20260927_100004_saas_tenant_columns.sql` — `cabinet_id` + index sur les 12 tables métier
- [x] 2026-10-01 — `database/schema.sql` régénéré depuis la base réelle
      (`mysqldump --no-data`, en-têtes `/*!40101` et `AUTO_INCREMENT=` retirés,
      `CREATE TABLE IF NOT EXISTS` + `FOREIGN_KEY_CHECKS` enveloppants). Il était
      en retard de **10 tables** (`cabinets`, `plans`, `abonnements`, `factures`,
      `paiements`, `users`, `user_roles`, `user_permissions`,
      `collaborateur_societes`, `ref_qualites_intermediaire`) et de **18 colonnes**
      (`cabinet_id` sur les 14 tables métier, `societes.dossier_output_path` /
      `dossier_output_nom`, `contrats.contrat_motif_resiliation` /
      `contrat_date_resiliation`, `collaborateurs.qualite_intermediaire_id` /
      `collaborateur_prenom`, `roles.scope` / `is_billable`). Un `fresh install`
      produisait une base sans la couche SaaS — donc une app non fonctionnelle.
      Validé par import réel dans une base jetable : 44 tables, 33 clés
      étrangères, 164 index, et **0 divergence** colonne / type / nullabilité /
      défaut / index / FK face à la base de dev. Réimportable sans erreur
      (`IF NOT EXISTS`).

#### Phase 2 — Couche authentification
- [x] `current_user()` → lit `users`, expose `cabinet_id`, `scope`, `role_nom`
- [x] `current_cabinet_id()`, `is_centre_user()`, `tenant_scope_sql()`, `assert_tenant_access()`
- [x] `get_user_permissions()` → `user_roles` + `user_permissions` (suppression du shortcut `role_id === 1` au profit de `roles.is_system`)
- [x] `pages/auth/connexion.php` + `includes/amorcage.php` (auto-login dev) → table `users`
- [x] `user_sessions` : colonne `cabinet_id` + rattachement dans `update_user_session()`
- [x] `user_sessions` : purge à la déconnexion côté logout — `deconnexion.php` appelle
      `purge_user_session($pdo, session_id())` avant le `session_destroy()`, sans quoi
      l'utilisateur restait « en ligne » jusqu'au purge automatique de 1 heure

#### Phase 3 — Isolation des données
- [x] Filtre tenant sur les listes (`fetch_all_documents`, `fetch_societes_options`, `associes_liste`, `cessions_liste`)
- [x] Filtre tenant sur `api.php` (quick_create rattache `cabinet_id`, inline_update/bulk_update gate par `api_row_visible`)
- [x] Garde IDOR sur `documents.php` (lecture + PDF + validation + suppression)
- [x] Garde IDOR sur les pages détail restantes (`societe`, `associe`, `contrat`, `collaborateur`, `cession_dossier`, `societe_suivi`)
- [x] Extensions Phase 3 : `pv_details` (export, suppression, generation, detail), `cession_suivi`, `cession_details_dossier`, wizards `cession` / `pv_ago` (listes deroulantes + rechargement en edition)
- [x] Lots d'IDs forges : `filter_accessible_ids()` sur validation / suppression / restauration de documents (`societe_details`, `cession_details_dossier`, `pv_details`)
- [x] Contrats : alias SQL `s` + predicat qualifie dans `contrat.php`, `contrats_suivi.php`, `contrats_liste.php` (voir « Contrat de list_scope() » ci-dessous)
- [x] Dashboard : suppression de la branche fail-open `if ($userId !== null) … else …` au profit de `list_scope()` (voir « Fuite inter-cabinet du dashboard » ci-dessous)

##### Contrat de `list_scope()` / `tenant_scope()` (piege recurrant)

Ces deux fonctions renvoient un **predicat nu** : ni `AND` en tete, ni `WHERE`.
C'est le contrat, respecte par `associes_liste.php`, `societes_liste.php` et
`modifications_juridiques.php` (qui joint via un tableau `$where[]`).

L'appelant **doit** fournir la conjonction. Trois pages le violaient et
l'ignoraisent, car MySQL leve une erreur de syntaxe qui masquait le
cloisonnement au lieu de le signaler comme un defaut de securite :

- `contrats_liste.php`, `contrat.php`, `contrats_suivi.php` concatenaient le
  fragment brut apres un `WHERE` → erreur 1064, page hors service pour tout
  adherent. Corrige par `' AND ' . $scope['sql']`.
- `contrat_user_filter()` delegue a `list_scope($alias)` et nomme la colonne
  par l'alias. Passer le nom complet alors que la requete ecrit
  `INNER JOIN societes s` produisait `societes.cabinet_id` → erreur 1054.
- `cession_suivi.php` joignait `cession_suivi_documents d` et
  `cession_suivi_etapes e`, qui ont **toutes deux** `cabinet_id` : un predicat
  non qualifie est ambigu (erreur 1052). Il faut `tenant_scope('d')`.

Ces trois familles de defauts sont couvertes par
`TenantScopeTest::testLeFragmentEstUnPredicatNuSansConjonction`,
`::testChaqueAppelantAjouteLeAnd` et
`TenantIsolationTest::testLeSuiviDeCessionQualifieLaColonneCabinet`.

##### Fuite inter-cabinet du dashboard

`pages/accueil/dashboard.php` separait ses requetes en
`if ($userId !== null) { … } else { … }`, avec `created_by = :uid` dans la
premiere branche et **aucun filtre** dans la seconde. Or un adherent de cabinet
n'a pas de fiche collaborateur par conception : `current_collaborateur_id()`
valait `null`, la page tombait donc dans la branche non filtree et le tableau de
bord exposait societes, contrats, revenus, documents, alertes et activite de
**tous** les cabinets. C'etait la fuite la plus grave du lot : aucun forged ID
n'est necessaire, un simple affichage suffit.

Le correctif ne cree pas une deuxieme regle de portee, il branche la page sur
`list_scope()`, qui portait deja la regle correcte :

| Cas | Predicat |
|---|---|
| Adherent de cabinet | `cabinet_id = :scope_cabinet` |
| Employe du Centre sans `dossiers.view_all` | `created_by = :scope_collaborateur` |
| Centre habilite a tout voir | aucun filtre |

Deux pieges lors de la reecriture :

- **Ne pas simplifier a `cabinet_id` partout.** Le Centre garde la vue globale,
  mais un employe du Centre *sans* `dossiers.view_all` doit conserver la vue
  « mes dossiers » : le remplacer par `cabinet_id` lui aurait elargi ses droits
  a l'integralite de la plateforme. C'est la raison d'etre du troisieme cas.
- **Le fragment `activity_logs`** porte sur une table de journal, pas sur
  `societes` : il se cloisonne via `list_scope('al', null)` pour ne pas
  produire un predicat `created_by` sur une colonne inexistante.

Le dashboard passe maintenant par une closure `$runScoped()` qui remplace un
jeton `{{SCOPE}}` **avec** son `AND`, et le retire quand le perimetre est vide :
`WHERE 1=1 {{SCOPE}}` reste donc valide pour le Centre comme pour un adherent.

Deux bugs de cle corriges au passage :

- `collabActivity` comparait `activity_logs.user_id` a un `collaborateur_id`.
  Deux identifiants sans rapport : ce fil revenait presque toujours vide pour
  un employe du Centre. Il porte desormais sur l'ID DE COMPTE.
- `contrats` **n'a pas** de colonne `created_by`. Tous les compteurs de contrats
  passent par une jointure `societes` plutot que par un predicat direct.

Couvert par `TenantIsolationTest::testLeDashboardFiltreUnAdherentSansFicheCollaborateur`,
`::testLeRevenuDuDashboardIgnoreLesContratsDUnAutreCabinet`,
`::testLesDocumentsDuDashboardSontCloisonnes`,
`::testLeCentreVoitLesDossiersDesDeuxCabinetsSurLeDashboard` et
`::testLeDashboardNestPlusOrganiseAutourDUneBrancheNonFiltrees` (garde de forme
sur le code, commentaires retires via `token_get_all()`, pour que le defaut
puisse rester documente dans la page). Le test de revenu verifie le **montant**
et non l'absence d'une chaine : un libelle masque peut cacher un agregat global.

##### Bug de schema corrige au passage

`contrats_suivi.php` selectionnait `s.societe_dossier_domicilation_number`
(il manque un `i` : la colonne reelle est `societe_dossier_domiciliation_number`).
MySQL levait « Unknown column » (1054) et la page du suivi des contrats etait
integralement hors service. Aucune autre occurrence de la typo dans le depot.

#### Phase 4 — Abonnements

**Blocage à la connexion** : un compte de cabinet dont l'abonnement n'autorise
plus l'accès (`absent`, `suspendu`, `resilie`, ou `actif` échu) n'ouvre pas de
session. Le blocage est porté par la **porte de connexion**
(`pages/auth/connexion.php`), pas par un garde de page : l'écran qui permet de
régler la facture n'est ainsi jamais inatteignable, car on ne peut pas y entrer
sans être abonné — le chemin reste ouvert à l'administration Centre, qui
suspend, relance ou résilie.

- [x] Porte de connexion — refus des comptes cabinet dont l'abonnement
  n'autorise pas l'accès (`abonnement_autorise_acces()`, règle unique partagée
  avec `require_active_subscription()` et avec l'écran « Créer un accès »)
- [x] Absence de blocage **de page** : `require_active_subscription()` reste
  inutilisé. Le bandeau d'en-tête reste informatif, il ne couvre que ce qui peut
  se dégrader *pendant* une session (échéance franchie, suspension)
- [x] Action « Créer un accès » : depuis un abonnement actif ou un essai non
  échu, ouverture du compte administrateur du cabinet (`creer_acces_cabinet()`).
  Le cabinet découle de l'abonnement et n'est jamais choisi dans le formulaire ;
  le rôle proposé est filtré sur `roles.scope = 'cabinet'` ; le mot de passe
  provisoire est affiché une seule fois, hashé en base, et
  `must_change_password` force son remplacement à la première connexion
- [x] 2026-10-01 — Contrôle des quotas plan (`max_utilisateurs`, `max_societes`,
      `max_dossiers`) **câblé**. La machinery existait déjà (`quota_counters()`,
      `cabinet_plan_reference()`, `quota_state()`, `quota_depasse()`,
      `require_quota_disponible()`, `QuotaTest`) mais **`require_quota_disponible()`
      n'était appelé nulle part** : les colonnes étaient affichées dans
      `mon_abonnement` et lues par aucune décision — un cabinet adhérent pouvait
      créer des dossiers sans fin en restant sous sa formule.
      - `require_quota_disponible()` accepte désormais des `$retourParams` : sans
        eux un cabinet au plafond perdait les six étapes déjà saisies au clic final.
      - **Wizard Création** (`step_06`) : garde avant `beginTransaction()` — donc
        avant l'INSERT, pas seulement présent dans le fichier.
      - **Cessions** et **PV AGO** (`step_07`) : garde **conditionnée** au mode
        « nouvelle ». Ces deux wizards n'INSERT une ligne `societes` que dans ce
        mode ; un garde inconditionnel aurait bloqué à tort toute cession sur une
        société existante, qui ne consomme aucun dossier.
      - Le Centre n'est jamais bloqué : `cabinet_id` NULL → aucun plan de
        référence → `limite = null` → `atteint = false`.
      - `QuotaTest::testChaqueCreationDeDossierConsulteLeQuota` verrouille les
        trois points d'entrée **et leur ordre** (garde avant INSERT) — vérifié
        non-vide : le test échoue si on retire un garde.
- [x] Statuts dérivés `expire` / `en_retard` calculés à l'affichage, jamais stockés (`abonnement_display_statut()`, `facture_display_statut()`)
- [x] Numérotation de facture `FAC-YYYY-NNN` avec reprise sur collision (`next_facture_number()`)

#### Phase 5 — Écrans d'administration

- [x] Routes + pages : `cabinets`, `plans`, `abonnements`, `factures`, `mon_abonnement`
- [x] Menu : section « Administration » (Super Admin), entrée « Mon abonnement » (adhérent)
- [x] Bandeau d'état abonnement dans l'entête (essai J−5 / actif J−30 / expiré / suspendu / absent), **informatif**
- [x] `mon_abonnement` en lecture seule, cloisonné par `current_cabinet_id()` — aucun paramètre d'URL n'élargit le périmètre
- [ ] Pages `users` / `user` / `parametres` (non traitées : la gestion des comptes passe encore par la configuration)

Encaissements : intégrés à `factures.php` plutôt qu'une page `paiements` séparée —
un paiement n'existe que pour solder une facture, et une facture payée est
verrouillée (ni suppression, ni second encaissement).

##### Deux bugs corrigés en fin de phase

`current_abonnement_state(?PDO $pdo = null)` **ignorait son paramètre**. Le
paramètre et la directive `global` portant le même nom, `global $pdo;` écrasait
la valeur reçue et la fonction retombait sur la connexion globale. Invisible en
production, où la seule utilisation passe par la connexion globale — le test
restant « correct » par hasard. Toute connexion passée explicitement (test,
script de rapprochement, futur job CLI) aurait lu la mauvaise base, en silence,
la `PDOException` étant absorbée en `statut = 'centre'`. Corrigé en lisant
`$GLOBALS['pdo']` sans déclarer de `global`. Couvrir par
`AbonnementTest::testLePdoPasseEnArgumentPrimeSurLaConnexionGlobale`, qui monte un
schéma miroir contenant le même plan marqué `suspendu` : sans le correctif, la
fonction renvoie l'état de la base du projet.

`pages/abonnement/abonnements.php` ne proposait que les plans **actifs** dans le
`<select>`, y compris en édition. Un abonnement rattaché à un plan désactivé
voyait « Sur mesure (sans plan) » et la sauvegarde **écrasait `plan_id` par NULL**,
perdant la formule et le prix négocié sans la moindre erreur. Le select charge
donc les plans inactifs en édition, et seulement là : en création un plan inactif
ne doit pas être vendable. Une garde en base (plan obligatoire, ou
`ON DELETE RESTRICT` sur `abonnements.plan_id`) éviterait la même perte par un
autre chemin.


#### Sécurité multi-tenancy
- [ ] Toute requête métier passe par `tenant_scope_sql()` ou `assert_tenant_access()` — revue fichier par fichier (en cours)
- [x] 2026-10-01 — Mot de passe obligatoire au premier login cabinet
      (`must_change_password`) : posé à 1 par `creer_acces_cabinet()`, garde dans
      `index.php` (redirige vers `mot_de_passe` avant tout rendu), contrôle dans
      `connexion.php`, remise à 0 au changement. Couvert par
      `MustChangePasswordTest` + `AccesCabinetTest::testMotDePasseProvisoire`
- [ ] Rappel : `collaborateurs.password_hash` n'est **plus** utilisé pour
      authentifier (tout est passé par `users`), mais `collaborateur_details.php`
      l'écrit encore (l. 279-303). La purge n'est donc pas neutre : elle suppose
      d'abord de retirer ces écritures.

### Backend / Dépendances
- [ ] Installer XAMPP PHP 8.3+ (action manuelle) puis valider avec
      `scripts/verifier_montree_php.ps1` — procédure : `docs/MONTAJEE_PHP_83.md`
      (audit déjà fait : 0 blocage 8.3 ; canari CI 8.3/8.4 en place)
- [x] 2026-10-01 — PHP 8.4 — les 26 paramètres implicitement nullable **étaient
      déjà corrigés** (`docs/MONTAJEE_PHP_83.md` § 1 datait d'avant le correctif).
      Vérifié par tokenizer PHP sur les **207 fichiers** du projet hors `vendor/` :
      `Type $x = null` sans `?` → **0 occurrence**. Les emplacements listés
      (`config_tabs.php:92`, `naming_dossier.php:135,168`, `fonctions.php:21`,
      `service_claude.php:7-8`…) portent tous `?Type $x = null`. Le palier 8.4 est
      donc **levé** : il ne reste que l'installation de XAMPP, ci-dessus.

### Base de données
- [x] 2026-10-01 — Aucune migration en attente : 64 fichiers dans `database/migrations/`,
      64 lignes dans `_migrations`, **0 fichier** non appliqué. Schéma synchronisé
      via le système auto-migration.
- [ ] `database/import.sql` — même dérive que `schema.sql` avant le correctif
      (34 tables, 18 colonnes en retard). Non régénéré : à la différence de
      `schema.sql` le fichier mélange **structure et données de démo**
      (`societes`, `associes`, `collaborateurs`, `contrats` = le dossier « WIZARD
      COMPLET TEST » du poste de dev). Il faut trancher ce que vaut une démo
      versionnée avant de réexporter.

### Frontend / UI
- [x] 2026-10-01 — Lot « Design System », famille 1 (boutons) : hauteurs `--ds-control-h`,
      bordure 1px, survols variante par variante (le bleu `#4a6cf7` survivait à `--primary`)
- [x] 2026-10-01 — Lot « Design System », famille 2 (en-têtes) : `.page-header` empilé
      (sous-titre + compteurs sous le titre, comme `.saas-form__sub`), `.section-header` /
      `.section-title-row` ramenés à la densité `.ds-card__head` (0.72rem, capitales, 600) et
      icône devient la pastille 24×24. **Bug corrigé au passage** : `index.php` inclut
      `entete.php` AVANT la page, donc `$pageSubtitle` posé par une page était mort — le
      sous-titre de « Journal d'activité » et d'« Analyse de couverture » ne s'affichait jamais.
      Le corps de la page est désormais rendu dans un tampon avant `entete.php`.
- [ ] Familles 3 à 6 du lot « Design System » : cartes (`.card` / `.info-grid`), tableaux,
      formulaires (`.form-compact`), stats/badges — RAS en dehors, charte appliquée
      (skills ui-design / awesome-design)

### Suivi administratif
- [x] 2026-08-26 — Vue detail amelioree : stepper vertical, KPIs, auto-scroll vers etape courante
- [x] 2026-08-26 — Vue pipeline Kanban 3 colonnes (En attente / En cours / Termine) avec drag & drop
- [x] 2026-08-26 — Badge "En retard" sur etapes debutees depuis > 7 jours
- [x] 2026-08-26 — Toggle vue Detail / Pipeline dans la barre d'actions
- [x] 2026-08-26 — PDF recapitulatif client (Dompdf) : plan de travail complet, prochaines etapes, delais estimes
- [x] 2026-08-26 — Sidebar : Suivi Creations + Suivi Domiciliations dans section Dossiers
- [x] 2026-08-26 — Navigation supporte params query (type=creation|domiciliation)
- [x] 2026-09-13 — Workflow domiciliation reél en 12 étapes (Récupération docs → Vérification → Remplir → Envoi contrats → Retour légalisés → Légalisation attestations → Appel/remise → Attestation d'enregistrement 48h → Dossier final 10-20j → Impression → Classement → Archivage cloud) : labels/icônes/suggestions docs, seeding wizard, reset des sociétés existantes (migration 20260913_000001), bandeau + puce rouge échéances expirées (CIN gérants, certificat négatif), PDF suivi 12 étapes + délais, fix collaborateur via collaborateurs.societe_id — déployé en prod (10467db), validé visuellement

### Qualité
- [x] Suite de tests PHPUnit 11 sur `src/` + integration tenant : TemplateAnalyzer / DocumentRenderer / TenantIsolation (132 tests, 319 assertions) — `vendor/bin/phpunit`
- [x] 2026-09-27 — Vérification manuelle avant commit (skill manual-test) : `php -l` sur les 7 fichiers modifiés, 101 tests PHPUnit verts, parcours navigateur sur le serveur de dev
- [x] 2026-09-28 — Phase 3 terminée. `php -l` sur 191 fichiers, 132 tests PHPUnit verts (0 skip), parcours HTTP des 14 pages affectées sans erreur PHP.
  Trois bugs **préexistants** trouvés en route, tous invisibles aux tests unitaires (qui n'examinaient que la chaîne du fragment, jamais son exécution) :
  (1) `list_scope()` renvoie un prédicat nu mais 3 pages le concatenaient sans `AND` → erreur 1064, liste des contrats inaccessible à tout adhérent ;
  (2) `contrat_user_filter()` sans alias sur `INNER JOIN societes s` → erreur 1054 sur 2 fiches ;
  (3) `contrats_suivi.php` : colonne `societe_dossier_domicilation_number` mal orthographiée → erreur 1054, page du suivi des contrats hors service.
  Les 27 tests dépendants de MySQL étaient **skippés** en CI locale (base arrêtée) : ils s'exécutent désormais et couvrent cessions, étapes de suivi et documents.
      (modale quick-create collaborateur, dialogues de confirmation, cartes KPI du suivi des contrats, wizard étape 1) — 0 erreur console. Défaut corrigé au passage : la création rapide
      n'insérait jamais de ligne dans les listes (`<template data-row-template>` est un frère de `<table>`, `buildRow` ne le trouvait pas → toast de succès sans ligne visible) ;
      la branche liste recharge désormais la page, la branche `<select>` du wizard reste sans rechargement
- [ ] Vérification manuelle avant commit : skill manual-test (à rejouer à chaque lot)
- [x] 2026-10-01 — Famille 2 du lot « Design System » : `php -l` sur les 4 fichiers touchés,
      240 tests PHPUnit verts (625 assertions), 41 pages parcourues via `fetch` sans aucun
      `Warning`/`Notice`/`Fatal` et avec `</html>` properly fermé, contrôle des styles calculés
      sur `.page-header` / `.section-header` / `.section-title-row` (1440px et 420px, aucun
      débordement horizontal), 0 erreur console. **Second bug préexistant corrigé** :
      `societe_details.php` utilisait `$retourPage` dans sa branche 404 alors qu'il ne le
      définit que 15 lignes plus bas — `app_url(null)` levait un `TypeError` fatal qui coupait
      la page 404 en plein milieu, sans `</html>`. Le repli est désormais fixé avant le test
      d'existence, puis affiné dès que la fiche est connue.

## Tâches terminées

- [x] 2026-10-01 — `$pageSubtitle` rendu vivant : tampon sur le corps de la page dans `index.php`, sinon `entete.php` (inclus avant) ne pouvait jamais voir la variable
- [x] 2026-10-01 — Branche 404 de `societe_details.php` : `$retourPage` défini avant son usage (fatal `TypeError` sur `app_url(null)`)
- [x] 2026-09-27 — Modale de création rapide : en-tête sticky (17 champs pour un collaborateur), icône + libellé de bouton paramétrable (`$quickCreateSubmitLabel`), astérisques sur les champs obligatoires, `role="dialog"` + `aria-modal` + `aria-labelledby`, focus sur le premier champ à l'ouverture et rendu à l'ouvreur à la fermeture
- [x] 2026-09-27 — Erreurs de création rapide affichées **dans** la modale (`.qc-alert`) : le toast était peint sous l'overlay (z-index 2000 < 9999) donc illisible ; le message se masque à la frappe ou via la croix
- [x] 2026-09-27 — `data-confirm-tone="primary"` : les confirmations non destructives (réinitialisation d'assistant, import Excel, rétablissement de contrat) ne sont plus affichées en rouge destructif
- [x] 2026-09-27 — Suivi des contrats : les 4 compteurs `.stat` remplacés par les cartes KPI du tableau de bord (`.dash-metric`), chacune étant un lien vers sa vue + `aria-current` ; nouvelle teinte `.dm-icon-sec`
- [x] 2026-08-21 — Fix collisions de tokens dans le rendu : `_SOCIETE_ADRESSE_` consommait `_SOCIETE_ADRESSE_SIEGE_` (restait « SIEGE_ ») et `_a.NOM_` mangeait `_a.NOM_COMPLET_` — tri par longueur décroissante dans replaceValues + réordonnancement boucle associes ; token malformé corrigé dans SARL AU/2026-07 Statuts ; 2 tests de régression
- [x] 2026-08-21 — Fix generation.php : l'admin (`collaborateur_type` NULL) ne voyait que les templates Domiciliation — les admins/interne voient désormais Creation + Domiciliation
- [x] 2026-08-21 — PHPUnit 11.5.56 (require-dev) + tests TemplateAnalyzer / DocumentRenderer
- [x] 2026-08-21 — Dompdf 3.1.5 → 3.1.6 : corrige 6 CVE (lecture de fichier local via SVG data-URI, fuite filesystem, DoS images surdimensionnées, contournement chroot)
- [x] 2026-08-21 — PhpSpreadsheet 5.8.0 → 5.9.0 (`composer audit` : 0 vulnérabilité restante)
- [x] 2026-08-21 — Conversion des variables templates `{{ VAR }}` → `_VAR_` (29 .docx, 589 variables) + adaptation DocumentRenderer / TemplateAnalyzer / UI
- [x] 2026-09-27 — SaaS multi-tenancy : 9 migrations (cabinets, facturation, users, RBAC, colonnes tenant, vues cloisonnées), RBAC complet (`users`/`user_roles`/`user_permissions`/`roles.scope`), `assert_tenant_access()` + `tenant_scope()`, cloisonnement fetch/listes/API/documents, 2 fichiers de tests integration (121 tests OK)
- [x] 2026-08-21 — Protocole CTO : commandes `/plan` `/execute` `/modify` + skill `protocole-cto`
