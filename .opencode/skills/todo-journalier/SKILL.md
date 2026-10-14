---
name: todo-journalier
description: Génère et met à jour la todo list du jour à partir de docs/ROADMAP.md et TODO.md, consignée dans docs/journal/YYYY-MM-DD.md. À utiliser au démarrage d'une journée de développement, ou quand l'utilisateur demande « la todo du jour », « qu'est-ce qu'on fait aujourd'hui », « mes tâches », « prépare la journée », « clôture la journée » ou veut voir/consolider les tâches en attente avant un commit.
license: MIT
compatibility: opencode
metadata:
  audience: developers
  source: Convention de suivi du projet (protocole CTO — docs/ROADMAP.md comme source de vérité)
---

# Skill Todo journalier

Produit une todo list **datée** au début de chaque journée de développement, à
partir de la feuille de route réelle du projet — jamais d'une liste inventée.

## Principe

`docs/ROADMAP.md` reste **la source de vérité** des tâches (c'est le rôle que lui
donne le protocole CTO). La todo journalière n'est qu'une **vue datée** :

- elle recopie les tâches en attente et on les coche au fil de la journée ;
- aucune tâche n'est marquée « faite » dans le journal sans l'être aussi dans le
  `ROADMAP.md` ;
- le journal ne remplace jamais le ROADMAP, il le reflète.

Objectif : un fichier par jour, `docs/journal/YYYY-MM-DD.md`, qui dit *ce qu'on
fait aujourd'hui*, *ce qui est fait* et *ce qui bloque*.

## Déclencheurs

- Démarrage d'une journée (« on commence », « la todo du jour », « prépare la journée »).
- Fin de journée (« clôture la journée », « bilan »).
- Avant un commit, pour vérifier ce qui reste ouvert.

## Workflow — matin

1. Extraire les tâches réellement en attente :
   ```powershell
   pwsh -File .opencode/skills/todo-journalier/scripts/collecter-taches.ps1 -OutFile docs/journal/_fragment-todo.md
   ```
   Puis **lire** `docs/journal/_fragment-todo.md` (le passage par un fichier évite
   les accents cassés de la console). Alternative : lire directement les lignes
   `- [ ]` de `docs/ROADMAP.md` et `TODO.md`.
2. Créer `docs/journal/YYYY-MM-DD.md` (date du jour) à partir du modèle ci-dessous,
   en recopiant les tâches du fragment. Si le fichier existe déjà, le
   **compléter**, ne pas l'écraser. Supprimer ensuite le fragment temporaire.
3. Prioriser : suivre l'ordre du ROADMAP (P0 → P1 → P2). Ne pas se disperser.
4. Annoncer à l'utilisateur la sélection du jour et le résultat attendu.

## Workflow — pendant la journée

- Cocher les cases **dans le journal** au fur et à mesure.
- Toute tâche terminée doit **aussi** être barrée dans `docs/ROADMAP.md` (règle du
  protocole CTO) : le journal et le ROADMAP restent cohérents.
- Une nouvelle tâche urgente s'ajoute au journal **et** au ROADMAP.
- Cocher = vérifié (preuve réelle : `php -l`, test PHPUnit, parcours navigateur —
  voir le skill `manual-test`), jamais « à peu près fait ».

## Workflow — soir / clôture

- Bilan : ce qui est fait, ce qui reste, les blocages.
- Les tâches non terminées restent ouvertes dans le ROADMAP (elles ne disparaissent
  pas) ; les recopier dans le journal du lendemain.
- Ne jamais clore une journée sur une case cochée sans vérification réelle.

## Modèle de fichier `docs/journal/YYYY-MM-DD.md`

```markdown
# Journal — YYYY-MM-DD

## Objectif du jour
<une phrase : ce que la journée doit produire>

## Todo
- [ ] (ROADMAP) Installer XAMPP PHP 8.3+ — action manuelle
- [ ] (ROADMAP) database/import.sql — trancher le contenu de démo
- [ ] (ROADMAP) Pages users / user / parametres
- [ ] (ROADMAP) Familles 3 à 6 du lot « Design System »
- [ ] (ROADMAP) Revue tenant_scope_sql() fichier par fichier
- [ ] (ROADMAP) Retirer l'écriture de collaborateurs.password_hash
- [ ] (ROADMAP) Vérification manuelle avant commit (skill manual-test)

## Fait
- [x] …

## Blocages
- …

## Notes
- …
```

## Règles

- Source de vérité = `docs/ROADMAP.md` + `TODO.md`. Le journal ne les remplace pas.
- Une tâche non faite **migre**, elle ne disparaît jamais.
- Un fichier par jour, nommé `YYYY-MM-DD.md` dans `docs/journal/`.
- Le journal suit la journée : on l'ouvre le matin, on le met à jour le soir.
- Ne pas inventer de tâches : uniquement ce que le ROADMAP / TODO contiennent (ou
  ce que l'utilisateur ajoute explicitement).

## Script

`scripts/collecter-taches.ps1` — extrait les `- [ ]` non terminés, regroupés par
section, avec leur fichier source. Il est **en lecture seule**.

| Option | Effet |
|---|---|
| `-Root <chemin>` | Racine du projet (auto-détectée par défaut via `AGENTS.md`) |
| `-InclureCode` | Ajoute les commentaires `TODO`/`FIXME`/`HACK` du code source |
| `-OutFile <chemin>` | Écrit le résultat en UTF-8 dans ce fichier (recommandé : évite les accents cassés de la console) |

> Sur cette machine, la sortie **console** d'un script PowerShell peut casser les
> accents (couche de capture shell). Toujours privilégier `-OutFile` puis lire le
> fichier, ou lire la source (`docs/ROADMAP.md`) via l'outil de lecture.
