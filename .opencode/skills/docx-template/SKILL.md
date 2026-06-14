---
name: docx-template
description: Manipule les fichiers .docx (analyse, renommage de variables, extraction) via TemplateAnalyzer et ZipArchive
license: MIT
compatibility: opencode
---

# Skill DOCX Template

Utilise cette skill pour toute manipulation de fichiers .docx (templates de documents juridiques).

## Classes
- `src/analyseur_templates.php` — classe statique pour analyse et modification des .docx
- Chargement : `require_once __DIR__ . '/../src/analyseur_templates.php';`

## Fonctions principales
- `TemplateAnalyzer::extractVariables(path)` — lit `word/document.xml` via ZipArchive, regex `{{ VAR }}`
- `TemplateAnalyzer::scanTemplates(dir)` — scan récursif des .docx, retourne infos (nom, date, type, variables)
- `TemplateAnalyzer::analyzeCoverage(templates)` — croise variables templates vs `getExpectedContextKeys()`
- `TemplateAnalyzer::renameVariable(old, new, dir)` — renomme `{{ OLD }}` → `{{ NEW }}` dans tous les .docx
- `TemplateAnalyzer::deleteVariable(name, dir)` / `deleteVariables(names, dir)` — supprime des variables
- `TemplateAnalyzer::replaceInDocxXml(xml, pattern, replacement)` — remplacement dans le XML DOM
- `TemplateAnalyzer::getExpectedContextKeys()` — variables canoniques (SOCIETE_*, ASSOCIE_*, etc.)
- `TemplateAnalyzer::groupByFolder(templates)` — groupes par dossier parent

## Structure des templates
- Dossier racine : `templates/`
- Sous-dossiers par forme juridique (ex: `templates/SARL/`, `templates/SA/`)
- Les variables sont notées `{{ NOM_VARIABLE }}` dans le document

## Pièges
- **Underscore split** : `{{ CIVILITE_ASSOCIE }}` peut être coupé entre `<w:t>` en `{{ CIVILITE` + ` ` + `ASSOCIE }}`. Utiliser `[\s_]*` dans les regex, pas `_` littéral
- **Headers/footers** : toujours scanner `word/header*.xml` et `word/footer*.xml`, pas seulement `word/document.xml`
- **ZipArchive** : doit être activé dans `C:\xampp\php\php.ini` (`extension=zip`) + redémarrage Apache

## Pages liées
- `pages/analyse-couverture.php` — analyse de couverture des variables
- `pages/template_edit.php` — éditeur WYSIWYG de templates
- `pages/templates.php` — liste des templates avec accordéon
- `pages/variables.php` — gestion des variables (mapping, renommage)
