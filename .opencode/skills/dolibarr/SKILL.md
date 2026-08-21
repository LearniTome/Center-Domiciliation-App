---
name: dolibarr
description: Implémente des fonctionnalités de type ERP/Dolibarr (modules, hooks, triggers, permissions, tiers, factures, devis, workflow statuts, numérotation, API REST) en PHP vanilla. Utiliser quand l'utilisateur demande d'ajouter un module ERP, une gestion de tiers/facturation/devis/stocks, des points d'extension (hooks/triggers), ou une intégration avec Dolibarr via son API REST.
license: MIT
compatibility: opencode
metadata:
  audience: developers
  source: Dolibarr ERP/CRM v23 (GPL-3+) — patterns réimplémentés, aucun code copié
---

# Skill Dolibarr — Fonctionnalités ERP en PHP vanilla

Utilise cette skill pour ajouter des fonctionnalités inspirées de Dolibarr ERP/CRM dans ce projet et tout projet PHP vanilla. Dolibarr est écrit en PHP sans framework lourd : ses patterns (modules activables, hooks, triggers, permissions fines, workflows) se transposent directement à ce projet.

## Fichiers de référence

| Fichier | Contenu |
|---|---|
| `architecture.md` | Architecture Dolibarr complète : modules, hooks, triggers, CommonObject, permissions, extrafields, documents |
| `api-rest.md` | API REST Dolibarr (endpoints, token) + pattern d'intégration entre deux apps PHP |
| `patterns-vanilla-php.md` | Mapping concept Dolibarr → implémentation concrète dans CE projet |

## Les 10 concepts Dolibarr à connaître

1. **Module** = unité fonctionnelle activable/désactivable (descripteur + pages + permissions + menus). Ici : groupe de pages `pages/{groupe}/` + entrée sidebar + permissions.
2. **Hook** = point d'accroche permettant d'injecter du contenu/comportement dans les pages existantes sans les modifier (`formObjectOptions`, `addMoreActionsButtons`, `printObjectLine`...).
3. **Trigger** = callback exécuté APRÈS un événement métier (`COMPANY_CREATE`, `FACTURE_VALIDATE`, `CONTRACT_MODIFY`...) pour propager les effets (notification, log, mise à jour liée).
4. **CommonObject** = classe de base des objets métier (tiers, produit, facture...) avec CRUD, statuts, notes, documents attachés, extrafields.
5. **Permissions fines** = droits par action (`{module}.read/create/edit/delete/export`) vérifiés partout, pas seulement au menu.
6. **Workflow statuts** = cycle de vie validé par étapes (brouillon → validé → payé/facturé → clôturé) avec transitions contrôlées et journalisées.
7. **Numérotation automatique** = séquences par type et par année (`FA-YYYY-NNN`) avec masque configurable.
8. **Documents générés** = templates PDF/ODT remplis depuis le contexte objet, stockés dans une GED hiérarchique.
9. **Extrafields** = champs personnalisés ajoutables par l'admin sur chaque entité sans toucher au code.
10. **API REST** = chaque module expose ses objets en JSON (`GET/POST/PUT /api/index.php/{objet}`), auth par token.

## Règles d'or (adaptées à ce projet)

- Un nouveau domaine fonctionnel = **un groupe de pages** dans `pages/{groupe}/` + entrée `includes/navigation.php` + permissions `{groupe}.{action}` dans la table des rôles.
- Toute action métier passe par un **trigger centralisé** : appeler `auto_notify_action()` / `log_activity()` après chaque INSERT/UPDATE/DELETE significatif — jamais de log dispersé dans les pages.
- Toute sortie HTML : `e()` ; tout POST : `csrf_input()` + `verify_csrf()` + redirect-after-POST ; toute requête : PDO préparé nommé. Voir skill `security`.
- Statuts : colonnes `statut`/`date_*` en DB + constantes PHP (`const STATUT_BROUILLON = 0;`) + badge coloré UI + timeline `activity_logs`.
- Numérotation : toujours via `next_dossier_number(?PDO, string $prefix, string $column)` (whitelist colonnes obligatoire).
- API JSON : étendre `api.php` existant (auth + CSRF + whitelist tables/colonnes), jamais de endpoint ad-hoc.

## Checklist avant livraison

- [ ] Permissions vérifiées sur boutons ET handlers (pas que le menu)
- [ ] Triggers/log sur toutes les actions métier (création, modification, validation, suppression)
- [ ] Workflow statuts : transitions interdites rejetées avec flash error
- [ ] Export CSV/Excel disponible sur les listes (pattern `export_csv()`/`export_excel()`)
- [ ] Tables : `data-sortable` + `data-col` sur `<th>`, recherche + column toggle
- [ ] Boutons : Material Symbols + couleur par rôle (voir AGENTS.md Button System)
- [ ] Lint PHP : `C:\xampp\php\php.exe -l <fichier>` sur chaque fichier modifié
- [ ] Test manuel navigation : voir skill `manual-test`
