# Ajout des étapes 4 et 5 à l'assistant de création

## Architecture du wizard

```
pages/creation.php   — Contrôleur principal du wizard (steps, POST handling, rendering)
src/DocumentRenderer.php — Contexte templates (buildContextFromDb)
assets/js/app.js     — JS dynamique (capital, associés, etc.)
```

---

## Étape 4 — Récapitulatif complet avec modification

**Fichier : `pages/creation.php`**

### Todo 4.1 — Étendre le wizard de 3 à 5 étapes

- [ ] Changer `$step = max(1, min(3, ...))` → `$step = max(1, min(5, ...))`
- [ ] Mettre à jour le rendu des `wizard-steps` : 5 barres au lieu de 3
- [ ] Ajouter la gestion `nav_action` pour `back` depuis steps 4 et 5

### Todo 4.2 — Afficher le récapitulatif (step 4)

- [ ] Créer le bloc `<?php if ($step === 4): ?>`
- [ ] Afficher 3 sections organisées :
  - **Société** : info-grid avec tous les champs (raison sociale, forme juridique, ICE, capital, ville, adresse, etc.)
  - **Associés** : boucle sur chaque associé avec ses infos (nom, CIN, nationalité, parts, gérant, etc.)
  - **Contrat** : info-grid (type contrat, dates, loyers, TVA, renouvellement, etc.)
- [ ] Chaque section a un bouton **Modifier** → `redirect_to('creation', ['step' => 1|2|3])`

### Todo 4.3 — Navigation step 4

- [ ] Bouton **Retour** → `redirect_to('creation', ['step' => 3])`
- [ ] Bouton **Suivant** → `redirect_to('creation', ['step' => 5])`

---

## Étape 5 — Générateur de documents intégré

### Todo 5.1 — Contexte templates depuis session

**Fichier : `src/DocumentRenderer.php`**

- [ ] Ajouter méthode statique `buildContextFromSession(array $wizard): array`
  - Mapper `$wizard['societe']` → clés : `DEN_STE`, `FORME_JUR`, `ICE`, `CAPITAL`, `STE_ADRESS`, `TRIBUNAL`, `VILLE`...
  - Mapper `$wizard['associes']` → liste associés avec nom, prénom, CIN, nationalité, parts, etc.
  - Mapper `$wizard['contrat']` → clés contrat : loyers, TVA, dates, renouvellement...
  - Générer `DATE`, `ANNEE`, `MOIS`, `JOUR` courants
  - Charger `ref_activites` depuis PDO pour `ACTIVITIES_LIST` (ou fallback vide)

### Todo 5.2 — Interface de génération (step 5)

**Fichier : `pages/creation.php`**

- [ ] Créer le bloc `<?php if ($step === 5): ?>`
- [ ] Adapter le code de `pages/generation.php` pour fonctionner sans DB (societe pas encore créée)
  - Scanner les templates via `TemplateAnalyzer::scanTemplates()`
  - Filtrer par `forme_juridique` (depuis session step 1)
  - Afficher les checkboxes par type de document
  - Toggle PDF
  - Bouton **Générer les documents** (POST avec `nav_action=generate`)
- [ ] Traitement POST `nav_action === 'generate'` :
  - Construire contexte via `DocumentRenderer::buildContextFromSession($wizard)`
  - Itérer sur les templates sélectionnés
  - Stocker les fichiers générés dans `$_SESSION['creation_wizard']['generated_files']`
  - Afficher la liste des fichiers avec téléchargement

### Todo 5.3 — Navigation step 5

- [ ] Bouton **Retour** → `redirect_to('creation', ['step' => 4])`
- [ ] Bouton **Créer le dossier complet** (`nav_action=finish`) → validation finale

### Todo 5.4 — Validation finale et commit BDD

**Fichier : `pages/creation.php`**

- [ ] Déplacer le bloc de validation (INSERT) du step 3 actuel vers `$postedStep === 5`
- [ ] Après l'insertion réussie (societe + associes + contrat) :
  - Lier les fichiers générés dans `documents_generes` avec `societe_id`
  - Vider `$_SESSION['creation_wizard']`
  - `set_flash('success', 'Le dossier a été créé avec succès.')`
  - `redirect_to('societe', ['id' => $societeId])`

---

## Modifications supports

### Todo 5.5 — Supprimer le bouton "Creer le dossier complet" du step 3 actuel

**Fichier : `pages/creation.php`**

- [ ] Remplacer `nav_action="finish"` par `nav_action="next"` dans le formulaire step 3
- [ ] Supprimer la logique de validation/INSERT du step 3

### Todo 5.6 — JS select-all pour templates (step 5)

**Fichier : `assets/js/app.js`**

- [ ] Ajouter un écouteur pour le bouton `#select-all-wizard` (select-all/deselect-all des checkboxes templates)

---

## Flux final

```
Step 1 (Société) → Step 2 (Associés) → Step 3 (Contrat)
       ↓
Step 4 (Récap — 3 sections + Modifier)
       ↓
Step 5 (Templates → Génération → Téléchargement)
       ↓
"Créer le dossier complet" → INSERT BDD + documents → fiche société
```
