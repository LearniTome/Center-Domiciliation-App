---
description: Modification chirurgicale — ajout d'une fonctionnalité sans casser l'existant (analyse d'impact, règles de chirurgie, anti-régression)
agent: build
---

Nous voulons modifier le projet pour ajouter la fonctionnalité suivante : $ARGUMENTS

Pour éviter tout effondrement ou destruction du code existant, respecte les règles de chirurgie logicielle :

1. **Analyse d'impact** : avant de toucher un fichier, lis `AGENTS.md` et le code concerné pour identifier les dépendances impactées (pages, helpers, tables, permissions, navigation, templates DOCX). Présente-moi un rapport rapide des fichiers qui seront touchés AVANT de commencer.
2. **Règles de chirurgie** : modification précise et confinée aux fichiers concernés uniquement. Interdiction de réécrire ou « améliorer » du code stable sans rapport avec la fonctionnalité. Respecte les patterns existants (boutons `.btn-next`/`.btn-info` avec Material Symbols, `data-sortable`, `.section-title-row`, `$pageActions`, etc.).
3. **Anti-régression** : après le modifications, vérifie avec `php -l` chaque fichier touché, puis teste via chrome-devtools MCP que la nouvelle fonctionnalité fonctionne ET que les fonctionnalités existantes de la zone modifiée n'ont pas régressé (navigation, formulaires, listes).
4. **Mise à jour mémoire** : documente la nouvelle fonctionnalité dans `AGENTS.md` (section appropriée) et mets à jour `docs/ROADMAP.md`.

Commence maintenant par l'analyse d'impact.
