---
description: Todo journalier — génère/actualise la todo du jour depuis docs/ROADMAP.md et TODO.md
agent: build
---

Charge le skill `todo-journalier` et applique son workflow.

**Contexte du jour :** $ARGUMENTS

Étapes :

1. Verifie la journee en cours : si l'argument ci-dessus decrit un objectif, utilise-le comme « objectif du jour ». Sinon, deduis-le des taches prioritaires du ROADMAP.
2. Genere / actualise `docs/journal/YYYY-MM-DD.md` (date du jour) :
   ```powershell
   pwsh -File .opencode/skills/todo-journalier/scripts/collecter-taches.ps1 -OutFile docs/journal/_fragment-todo.md
   ```
   Lis `docs/journal/_fragment-todo.md` (et non la console, qui casse les accents), puis reprends les taches non terminees de `docs/ROADMAP.md` + `TODO.md` (P0 → P1 → P2) dans le journal du jour et remplis « Objectif du jour ». Supprime le fragment ensuite.
   Si le fichier du jour existe deja, le completer sans l'ecraser.
3. Affiche-moi la todo du jour (fichier + resume) et, si je coche des taches au fil de la session, tiens le journal a jour ET barre les memes taches dans `docs/ROADMAP.md`.

Ne jamais inventer de tache : la source de verite reste `docs/ROADMAP.md` + `TODO.md`.
