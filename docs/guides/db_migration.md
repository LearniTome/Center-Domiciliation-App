# Migration de la base Excel vers SQL/NoSQL

Ce document décrit une approche simple pour migrer `DataBase_domiciliation.xlsx` vers une base
SQL ou NoSQL tout en conservant les conventions actuelles.

## Convention actuelle

- Les en-têtes Excel sont en `snake_case` (minuscules).
- Les libellés visibles en UI peuvent être différents (ex. `den_ste` → **Dénomination sociale**).
- Le schéma canonique est défini dans `src/utils/constants.py` (listes `*_headers` + alias).

## Tables / collections recommandées

- **societes**: données principales de la société.
- **associes**: associés (FK `id_societe`).
- **contrats**: contrats (FK `id_societe`).
- **collaborateurs**: collaborateurs (FK `id_societe`).
- **référentiels**: `ste_adresses`, `tribunaux`, `activites`, `nationalites`, `lieux_naissance`.

## Clés et relations

- Clés primaires: `id_societe`, `id_associe`, `id_contrat`, `id_collaborateur`.
- Clés étrangères:
  - `associes.id_societe` → `societes.id_societe`
  - `contrats.id_societe` → `societes.id_societe`
  - `collaborateurs.id_societe` → `societes.id_societe`

## Typage conseillé (SQL)

- Dates: colonnes `date_*` et `date_*_contrat`.
- Nombres: `capital`, `parts`, `part_percent`, `montant_*`, `loyer_*`, `taux_tva_*`.
- Booléen: `is_gerant`.

## Stratégie de migration (ETL)

1. **Extraction**: lire chaque feuille Excel avec pandas.
2. **Normalisation**: appliquer les alias (`societe_header_aliases`, etc.) pour couvrir
   les anciens en-têtes (`DEN_STE`, `STE_ADRESS`, etc.).
3. **Nettoyage**: trim des chaînes, conversion des types (dates/nombres/booléens).
4. **Chargement**:
   - SQL: insert avec contraintes PK/FK.
   - NoSQL: embed `associes`/`contrats` dans `societes` ou garder des collections séparées
     en conservant `id_societe`.

## Conseils NoSQL

- **Document unique**: `societe` avec sous‑documents `associes`, `contrats`, `collaborateurs`.
- **Collections séparées**: garder `id_societe` + index par `id_societe` pour les recherches.

## Checklist rapide

- Conserver les champs en `snake_case` (base de vérité).
- Créer des index sur `id_societe` et `den_ste`.
- Conserver un mapping UI (ex. `den_ste` → “Dénomination sociale”).
- Ajouter un script d’import idempotent pour rejouer la migration.
