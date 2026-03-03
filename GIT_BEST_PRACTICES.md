📚 BONNES PRATIQUES GIT - GESTION DES BRANCHES
═════════════════════════════════════════════════════════════════

## 🎯 STRATÉGIE DE BRANCHING RECOMMANDÉE
═════════════════════════════════════════════════════════════════

### Git Flow (Approche Recommandée)

Structure:
```
main ────────────── Production (Stable)
  └─ release/* ─── Pre-production (Testing)
       └─ develop ─ Development (Actif)
            ├─ feature/* ─ Nouvelles features
            ├─ bugfix/*  ─ Corrections bugs
            └─ hotfix/*  ─ Urgent fixes
```

Cycle de vie:
1. Créer feature depuis `develop`
2. Faire PR vers `develop`
3. Code review & tests
4. Merge vers `develop`
5. Quand prêt: créer `release/vX.Y.Z` depuis `develop`
6. Tester et corriger sur `release/vX.Y.Z`
7. Merger vers `main` et `develop`
8. Tagger comme `vX.Y.Z`


## 📋 ANALYSE DE VOTRE PROJET
═════════════════════════════════════════════════════════════════

BRANCHES ACTUELLES:

1. ✅ main
   Type: Production
   Statut: GARDER - Branche principale stable
   Raison: Point de référence pour les releases

2. ✅ develop/v2.2.0
   Type: Development
   Statut: GARDER - Branche active de développement
   Raison: Où vous travaillez maintenant
   Action: À renommer en `develop` (sans version)

3. ⚠️ chore/migration-backup
   Type: Maintenance/Feature ancien
   Statut: GARDER ou ARCHIVER
   Raison: Contient v2.1.0, utile comme référence
   Action: Garder comme branche de release stable

4. ❌ chore/cleanup
   Type: Maintenance
   Statut: SUPPRIMER - Pas utilisée, obsolète
   Raison: Inactif depuis longtemps
   Action: Supprimer localement et à distance

5. ❌ chore/auto
   Type: Maintenance/Automation
   Statut: SUPPRIMER - Pas utilisée, obsolète
   Raison: Inactif depuis longtemps
   Action: Supprimer localement et à distance


## 🏗️ STRUCTURE RECOMMANDÉE POUR VOTRE PROJET
═════════════════════════════════════════════════════════════════

AVANT (Actuel):
```
main
├─ chore/auto              ❌ Supprimer
├─ chore/cleanup           ❌ Supprimer
├─ chore/migration-backup  ⚠️ Archiver
└─ develop/v2.2.0          ⚠️ Renommer
```

APRÈS (Recommandé):
```
main                           ✅ Production
├─ release/v2.1.0             ✅ Archived release
├─ release/v2.2.0             ✅ Current release prep
└─ develop                     ✅ Active development
    ├─ feature/search-filter
    ├─ feature/export-csv
    └─ bugfix/dark-mode
```


## 📌 BONNES PRATIQUES PAR BRANCHE
═════════════════════════════════════════════════════════════════

### MAIN BRANCH
✅ DO:
  • Toujours stable et testée
  • Seulement des commits de merge depuis release
  • Toujours avec tags de version
  • Accessible en production

❌ DON'T:
  • Committer directement (sauf hotfix)
  • Push de code non testé
  • Supprimer cette branche!

Bonnes pratiques:
```bash
# Créer release depuis develop
git checkout -b release/v2.2.0 develop

# Après testing, merger vers main
git checkout main
git merge --no-ff release/v2.2.0

# Tagger la version
git tag -a v2.2.0 -m "Release v2.2.0"

# Anche merger les fixes vers develop
git checkout develop
git merge --no-ff release/v2.2.0
```


### DEVELOP BRANCH
✅ DO:
  • Intégration de toutes les features
  • Base pour les nouvelles branches
  • Toujours prêt pour release
  • Tests et CI/CD passants

❌ DON'T:
  • Commits directs (utiliser feature branches)
  • Code non-reviewed
  • Breaking changes sans planning

Bonnes pratiques:
```bash
# Créer feature depuis develop
git checkout -b feature/search-filter develop

# Faire le travail, commits réguliers
git commit -m "feat: add search filter"

# Pousser la feature
git push origin feature/search-filter

# Créer Pull Request
# - Code review
# - Tests passent
# - Approuvé

# Merger vers develop
git checkout develop
git pull origin develop
git merge --no-ff feature/search-filter
git push origin develop

# Supprimer la branche feature
git branch -d feature/search-filter
git push origin --delete feature/search-filter
```


### FEATURE BRANCHES
✅ DO:
  • Une feature = une branche
  • Nommer clairement: feature/nom-feature
  • Commits logiques et testables
  • Supprimer après merge

❌ DON'T:
  • Branches longues durée (>2 semaines)
  • Mélanger plusieurs features
  • Laisser des branches orphelines

Bonnes pratiques:
```bash
# Nommage clair
git checkout -b feature/dashboard-search develop
git checkout -b feature/export-excel develop
git checkout -b bugfix/dark-mode-combobox develop
git checkout -b hotfix/critical-bug main
```


## 🔄 WORKFLOW RECOMMANDÉ POUR VOUS
═════════════════════════════════════════════════════════════════

ÉTAPE 1: Nettoyer et renommer les branches

```bash
# Supprimer les branches inutiles localement
git branch -d chore/cleanup
git branch -d chore/auto

# Supprimer à distance
git push origin --delete chore/cleanup
git push origin --delete chore/auto

# Renommer develop/v2.2.0 en develop
git branch -m develop/v2.2.0 develop
git push origin -u develop
git push origin --delete develop/v2.2.0

# Renommer chore/migration-backup en release/v2.1.0
git branch -m chore/migration-backup release/v2.1.0
git push origin -u release/v2.1.0
git push origin --delete chore/migration-backup
```

ÉTAPE 2: Vérifier la structure

```bash
git branch -a
# Résultat attendu:
# * develop
#   main
#   remotes/origin/develop
#   remotes/origin/main
#   remotes/origin/release/v2.1.0
```

ÉTAPE 3: Continuer le développement

```bash
# Créer une feature pour v2.2.0
git checkout -b feature/search-filter develop

# Faire le travail...
# Commit régulièrement
git commit -m "feat: add search functionality to dashboard"
git commit -m "feat: add filter by date"

# Pousser
git push origin feature/search-filter

# Créer PR, faire review, merger
git checkout develop
git merge --no-ff feature/search-filter
git push origin develop

# Nettoyer
git branch -d feature/search-filter
git push origin --delete feature/search-filter
```


## 📊 RÉSUMÉ DES ACTIONS
═════════════════════════════════════════════════════════════════

BRANCHES À GARDER:
✅ main                  - Production (JAMAIS supprimer!)
✅ develop               - Development (renommer develop/v2.2.0)
✅ release/v2.1.0        - Archived release (renommer chore/migration-backup)

BRANCHES À SUPPRIMER:
❌ chore/cleanup         - Inutile, obsolète
❌ chore/auto            - Inutile, obsolète


## 🎯 BONNES PRATIQUES ESSENTIELLES
═════════════════════════════════════════════════════════════════

1. ✅ NOMMAGE CLAIR
   • main, develop, release/*, feature/*, bugfix/*, hotfix/*
   • Pas de numéros de version dans feature branches
   • Utiliser des tirets: feature/mon-feature (pas feature_mon_feature)

2. ✅ COMMITS LOGIQUES
   • Un commit = une logique/fix
   • Messages clairs: "feat:", "fix:", "docs:", "refactor:"
   • Exemple: "feat: add search filter to dashboard"

3. ✅ PULL REQUESTS
   • Toujours faire PR pour develop
   • Code review obligatoire
   • Tests passants
   • Une approuvation avant merge

4. ✅ MERGE STRATEGY
   • Utiliser --no-ff pour garder l'historique: git merge --no-ff
   • Permet de voir les features distinctes
   • Facile à reverter si besoin

5. ✅ TAGGING
   • Tagger les releases: git tag -a v2.2.0 -m "Release v2.2.0"
   • Format: vMAJOR.MINOR.PATCH (v2.1.0)
   • Push tags: git push origin v2.2.0

6. ✅ NETTOYAGE RÉGULIER
   • Supprimer branches mergées
   • git branch -d feature-branch (local)
   • git push origin --delete feature-branch (remote)

7. ✅ COMMUNICATION
   • Décider ensemble avant gros changements
   • Documenter les breaking changes
   • Notifier l'équipe des nouvelles releases


## 📈 EXEMPLE DE WORKFLOW COMPLET
═════════════════════════════════════════════════════════════════

Développer v2.2.0 avec 3 features:

1. Créer branche feature
   git checkout -b feature/search develop

2. Développer et tester
   git commit -m "feat: add search box"
   git commit -m "test: add search tests"
   git push origin feature/search

3. Code review (PR)
   • Vérifier code
   • Tests passants
   • Approuver

4. Merger dans develop
   git checkout develop
   git merge --no-ff feature/search

5. Supprimer feature branch
   git branch -d feature/search
   git push origin --delete feature/search

6. Répéter pour feature/filter et feature/export

7. Quand toutes les features sont prêtes
   git checkout -b release/v2.2.0 develop

8. Faire les derniers tests et fixes
   git commit -m "fix: adjust search styling"

9. Merger release dans main
   git checkout main
   git merge --no-ff release/v2.2.0
   git tag -a v2.2.0 -m "Release v2.2.0"
   git push origin main
   git push origin v2.2.0

10. Merger release fixes dans develop
    git checkout develop
    git merge --no-ff release/v2.2.0
    git push origin develop

11. Supprimer release branch (optionnel, garder pour historique)
    git branch -d release/v2.2.0
    git push origin --delete release/v2.2.0


═════════════════════════════════════════════════════════════════

✨ RÉSULTAT FINAL SOUHAITÉ:

Branches permanentes:
  • main       - Stable, production-ready
  • develop    - Intégration continue
  • release/* - Branches de release archivées

Branches temporaires:
  • feature/* - Créées et supprimées après merge
  • bugfix/*  - Créées et supprimées après merge
  • hotfix/*  - Créées et supprimées après merge

═════════════════════════════════════════════════════════════════
