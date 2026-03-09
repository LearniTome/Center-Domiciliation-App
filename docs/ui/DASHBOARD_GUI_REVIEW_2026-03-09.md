# Revue GUI Dashboard - 2026-03-09

## Objet

Documenter les constats relevés sur le dashboard GUI avant correction de la logique des boutons.

## Portée

- Interface du dashboard
- Comportement des boutons `Ajouter`, `Modifier`, `Supprimer`, `Actualiser`
- Cohérence entre `DashboardView` et `MainForm`
- Fiabilité des tests existants

## Constats principaux

### 1. Incohérence fonctionnelle sur `Modifier` et `Supprimer`

Le dashboard envoie bien la ligne sélectionnée de la page courante, mais le handler parent reste principalement pensé pour une action centrée sur la société.

Conséquences observées :

- depuis la page `Associés`, un `Modifier` peut ouvrir un formulaire société partiellement vide ou incomplet
- depuis la page `Contrats`, un `Modifier` ne respecte pas l'intention utilisateur de modifier un contrat directement
- depuis `Associés` ou `Contrats`, un `Supprimer` peut lancer une suppression basée sur `ID_SOCIETE` alors que l'utilisateur pense supprimer l'enregistrement affiché
- le message de confirmation de suppression peut être vide ou peu explicite si `DEN_STE` n'est pas présent dans le payload

Fichiers concernés :

- `src/forms/dashboard_view.py`
- `src/forms/main_form.py`
- `src/utils/constants.py`

### 2. Le bouton `Ajouter` ignore l'onglet courant

Le bouton `Ajouter` renvoie toujours vers la logique de création d'une société, même si l'utilisateur se trouve sur `Associés` ou `Contrats`.

Effet UX :

- le comportement est surprenant
- le bouton semble contextuel dans l'interface, mais ne l'est pas réellement

### 3. Feedback trompeur après `Supprimer`

Le dashboard affiche un toast de type `Suppression demandée` puis recharge les données après l'appel du handler, même si l'utilisateur a annulé la confirmation.

Effet UX :

- faux sentiment d'action effectuée
- confusion sur l'état réel des données

### 4. Tri potentiellement faux sur dates et montants

Les données sont chargées en chaînes de caractères, puis triées telles quelles.

Risques :

- tri lexical au lieu d'un tri métier
- ordre incorrect pour les montants
- ordre incorrect pour certaines dates si le format n'est pas ISO

### 5. Couverture de tests faible sur les boutons

Plusieurs fichiers de tests dashboard ressemblent à des scripts de validation manuelle plus qu'à de vrais tests automatisés.

Limites constatées :

- certains tests retournent `False` au lieu d'utiliser des assertions robustes
- certains tests appellent directement des mocks sans exercer réellement `DashboardView._action`
- la confiance apportée par ces tests est donc limitée

### 6. Fragilité du chargement du thème

L'initialisation réelle du dashboard échoue actuellement dans l'environnement local à cause d'un chargement `ttkthemes`.

Erreur observée :

- échec de lecture de `scid.tcl` via `ThemedStyle`

Impact :

- certains tests GUI ne peuvent pas démarrer
- la validation réelle du dashboard est bloquée par le thème avant même la logique métier

## Avis global

Le dashboard est utile et déjà bien structuré sur la partie navigation, recherche, filtre, pagination et export CSV.

Le principal point faible n'est pas la table elle-même, mais le contrat fonctionnel des boutons :

- l'interface suggère une gestion multi-entités
- la logique backend reste surtout centrée sur la société

Cette divergence crée le risque principal d'erreur utilisateur.

## Recommandations

### Priorité haute

1. Décider clairement le modèle fonctionnel :
   - soit dashboard centré société
   - soit dashboard réellement multi-entités

2. Si le dashboard reste centré société :
   - limiter `Ajouter`, `Modifier`, `Supprimer` à l'onglet `Sociétés`
   - désactiver ou masquer ces boutons sur `Associés` et `Contrats`

3. Si le dashboard devient multi-entités :
   - transmettre explicitement `current_page` au handler
   - implémenter des handlers distincts pour société, associé et contrat

4. Faire retourner un statut métier au handler de suppression :
   - `deleted`
   - `cancelled`
   - `error`

5. N'afficher le toast de suppression qu'après confirmation réelle du résultat

### Priorité moyenne

1. Désactiver `Modifier` et `Supprimer` tant qu'aucune ligne n'est sélectionnée
2. Afficher des libellés plus lisibles dans le filtre par colonne
3. Introduire un tri typé pour dates et montants
4. Ajouter un fallback de thème si `ttkthemes` échoue

### Qualité et tests

1. Remplacer les scripts de démonstration par de vrais tests automatisés
2. Tester directement `DashboardView._action(...)`
3. Valider les scénarios suivants :
   - édition depuis `Sociétés`
   - édition depuis `Associés`
   - édition depuis `Contrats`
   - suppression confirmée
   - suppression annulée
   - bouton `Ajouter` selon l'onglet courant

## Références code

- `src/forms/dashboard_view.py`
- `src/forms/main_form.py`
- `src/utils/styles.py`
- `tests/test_dashboard_actions.py`
- `tests/test_dashboard_actions_integration.py`
- `tests/test_dashboard_integration.py`
- `tests/test_dashboard_headless.py`
- `tests/test_dashboard_final.py`

## Statut

Constat documenté avant correction de la logique des boutons.
