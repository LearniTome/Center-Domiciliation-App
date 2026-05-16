# Champs de l'Assistant de creation — Guide complet

Tous les champs du formulaire de creation de dossier (`pages/creation.php`), etape par etape.

**Legende :**
- **Type** : type d'input HTML (text, number, date, select, hidden, textarea)
- **Variable template** : nom a utiliser dans les documents Word `{{ VAR }}`
- **Col DB** : colonne dans la base de donnees (peut differer du `name=""`)
- **`(calcule)`** : valeur calculee automatiquement par le JS, pas de saisie directe
- **`—`** : pas de variable template ni de colonne DB (champ interne au wizard)

---

## Etape 1 — Societe

### Procedure

| Champ name | Libellé | Type | Valeurs possibles | Variable template | Col DB |
|---|---|---|---|---|---|
| `type_generation` | Type generation | select | `creation`, `domiciliation` | `SOCIETE_TYPE_GENERATION` | `societe_type_generation` |
| `procedure_creation` | Procedure creation | select | `normal`, `acceleree` | `SOCIETE_PROCEDURE_CREATION` | `societe_procedure_creation` |
| `mode_depot_creation` | Mode depot creation | select | `depot_physique`, `depot_en_ligne` | `SOCIETE_MODE_DEPOT` | `societe_mode_depot` |

### Identifiants

| Champ name | Libellé | Type | Exemple | Variable template | Col DB |
|---|---|---|---|---|---|
| `societe_dossier` | Dossier domiciliation | text | `DOM-2026-001` | `SOCIETE_DOSSIER` | `societe_dossier` |
| `societe_raison_sociale` | Raison sociale | text | `MA SOCIETE SARL` | `SOCIETE_RAISON_SOCIALE` | `societe_raison_sociale` |
| `societe_forme_juridique` | Forme juridique | select | `SARL AU`, `SARL`, `SA` | `SOCIETE_FORME_JURIDIQUE` | `societe_forme_juridique` |
| `societe_ice` | ICE | text | `003829326000013` | `SOCIETE_ICE` | `societe_ice` |
| `societe_date_ice` | Date obtention ICE | date | `2026-05-15` | `SOCIETE_DATE_ICE` | `societe_date_ice` |
| `societe_date_exp_cert_neg` | Date exp. certificat negatif | date | `2026-08-15` | `SOCIETE_DATE_EXP_CERT_NEG` | `societe_date_exp_cert_neg` |
| `societe_rc` | RC | text | `705161` | `SOCIETE_RC` | `societe_rc` |
| `societe_if` | IF | text | `68834734` | `SOCIETE_IF` | `societe_if` |

### Activites

| Champ name | Libellé | Type | Details | Variable template | Col DB |
|---|---|---|---|---|---|
| `societe_activites_ompic` | Activite certificat negatif | select | Liste OMPIC (code + libelle) | `ACTIVITES_OMPIC`, `OMPIC_INLINE`, `OMPIC_PUCES`, `NB_OMPIC` | `societe_activites_ompic` |
| `societe_activites_statuts[]` | Activites statuts | select multiple | `ref_activites`, ajout dynamique | `ACTIVITES`, `ACTIVITES_INLINE`, `ACTIVITES_PLAIN`, `ACTIVITES_PUCES`, `ACTIVITES_SUITE_PUCES`, `NB_ACTIVITES`, `ACTIVITY1`-`ACTIVITY5` | `societe_activites_statuts` |

### Capital

| Champ name | Libellé | Type | Exemple | Variable template | Col DB |
|---|---|---|---|---|---|
| `societe_capital` | Capital | number | `100000` | `SOCIETE_CAPITAL` | `societe_capital` |
| `societe_part_social` | Part social | number | `1000` | `SOCIETE_PART_SOCIAL` | `societe_part_social` |
| `societe_valeur_nominale` | Valeur nominale | number | `100` | `SOCIETE_VALEUR_NOMINALE` | `societe_valeur_nominale` |

### Adresse

| Champ name | Libellé | Type | Details | Variable template | Col DB |
|---|---|---|---|---|---|
| `societe_adresse_siege` | Adresse de reference | select | `ref_ste_adresses` | `SOCIETE_ADRESSE_SIEGE` | `societe_adresse_siege` |
| `societe_ville` | Ville | select | `ref_villes` | `SOCIETE_VILLE` | `societe_ville` |
| `tribunal_type` | Type de tribunal | select | `ref_tribunaux.type`, ex: Tribunal de commerce | — | — |
| `societe_tribunal` | Tribunal | select | `ref_tribunaux.nom`, ex: Casablanca | `SOCIETE_TRIBUNAL` | `societe_tribunal` |

### Contact

| Champ name | Libellé | Type | Exemple | Variable template | Col DB |
|---|---|---|---|---|---|
| `societe_email` | Email | email | `contact@masociete.ma` | `SOCIETE_EMAIL` | `societe_email` |
| `societe_telephone` | Telephone | text | `0522000000` | `SOCIETE_TELEPHONE` | `societe_telephone` |

---

## Etape 2 — Associes

Chaque associe est dans un bloc `associes[INDEX][...]`. Le template du premier associe est duplique dynamiquement.

### Identite

| Champ name | Libellé | Type | Exemple | Variable template (hors boucle) | Variable template (boucle `a.`) | Col DB |
|---|---|---|---|---|---|---|
| `associes[][civilite]` | Civilite | select | `Mr`, `Mme`, `Mlle` | `ASSOCIE_CIVILITE` | `a.CIVILITE` | `associe_civilite` |
| `associes[][nom]` | Nom | text | `DUPONT` | `ASSOCIE_NOM` | `a.NOM` | `associe_nom` |
| `associes[][prenom]` | Prenom | text | `Jean` | `ASSOCIE_PRENOM` | `a.PRENOM` | `associe_prenom` |
| `associes[][nom_complet]` | Nom complet | hidden (calcule) | `Mr Jean DUPONT` | `ASSOCIE_NOM_COMPLET` | `a.NOM_COMPLET` | `associe_nom_complet` |
| `associes[][cin]` | N CIN/Sejour/Passport | text | `BH584028` | `ASSOCIE_CIN` | `a.CIN` | `associe_cin` |
| `associes[][date_validite_cin]` | Date validite CIN | date | `2030-01-01` | `ASSOCIE_DATE_VALIDITE_CIN` | — | `associe_date_validite_cin` |
| `associes[][nationalite]` | Nationalite | select | `ref_nationalites` | `ASSOCIE_NATIONALITE` | `a.NATIONALITE` | `associe_nationalite` |
| `associes[][date_naiss]` | Date naissance | date | `1990-01-15` | `ASSOCIE_DATE_NAISSANCE` | `a.DATE_NAISSANCE` | `associe_date_naissance` |
| `associes[][lieu_naiss]` | Lieu naissance | select | `ref_lieux_naissance` | `ASSOCIE_LIEU_NAISSANCE` | `a.LIEU_NAISSANCE` | `associe_lieu_naissance` |

### Contact

| Champ name | Libellé | Type | Variable template (hors boucle) | Variable template (boucle `a.`) | Col DB |
|---|---|---|---|---|---|
| `associes[][phone]` | Telephone | text | `ASSOCIE_TELEPHONE` | `a.TELEPHONE` | `associe_telephone` |
| `associes[][societe_email]` | Email | email | `ASSOCIE_EMAIL` | `a.EMAIL` | `associe_email` |
| `associes[][adresse]` | Adresse | textarea | `ASSOCIE_ADRESSE` | `a.ADRESSE` | `associe_adresse` |

### Participation

| Champ name | Libellé | Type | Exemple | Variable template (hors boucle) | Variable template (boucle `a.`) | Col DB |
|---|---|---|---|---|---|---|
| `associes[][qualite_associe]` | Qualite associe | select | `ref_qualites_associe` | `ASSOCIE_QUALITE` | `a.QUALITE` | `associe_qualite` |
| `associes[][parts]` | Parts | number (SARL) | `100` | `ASSOCIE_PARTS` | `a.PARTS` | `associe_parts` |
| `associes[][capital_detenu]` | Capital detenu (DH) | number (SARL) | `50000` | `ASSOCIE_CAPITAL_DETENU` | `a.CAPITAL_DETENU` | `associe_capital_detenu` |
| `associes[][part_percent]` | % Capital social | number (SARL) | `50` | — | — | `associe_part_percent` |
| `associes[][is_gerant]` | Gerant | select | `Oui`, `Non` | `ASSOCIE_EST_GERANT` | `a.EST_GERANT` | `associe_est_gerant` |

---

## Etape 3 — Contrat

### Type de contrat

| Champ name | Libellé | Type | Valeurs possibles | Variable template | Col DB |
|---|---|---|---|---|---|
| `contrat_type` | Type de contrat | select | `Domiciliation commerciale`, `Domiciliation professionnelle`, `Domiciliation simple`, `Autre` | `CONTRAT_TYPE` | `contrat_type` |
| `contrat_type_autre` | Autre type (specifier) | text (visible si "Autre") | `Domiciliation mixte` | — | — |
| `contrat_type_domiciliation` | Type contrat domiciliation | select | `Personne Morale`, `Personne Physique`, `Association`, `Fondation`, `Autres` | `CONTRAT_TYPE_DOMICILIATION` | `contrat_type_domiciliation` |
| `contrat_date` | Date du contrat | date | defaut: aujourd'hui | `CONTRAT_DATE` | `contrat_date` |

### Periode

| Champ name | Libellé | Type | Details | Variable template | Col DB |
|---|---|---|---|---|---|
| `contrat_date_debut` | Date de debut | date | defaut: aujourd'hui | `CONTRAT_DATE_DEBUT` | `contrat_date_debut` |
| `contrat_duree_mois` | Duree (mois) | number | 12, 24, 36... | `CONTRAT_DUREE_MOIS` | `contrat_duree_mois` |
| `contrat_date_fin` | Date de fin | date (calcule, readonly) | debut + duree | `CONTRAT_DATE_FIN` | `contrat_date_fin` |
| `contrat_statut` | Statut | select | `actif`, `expire`, `brouillon` | `CONTRAT_STATUT` | `contrat_statut` |

### Loyer initial

| Champ name | Libellé | Type | Details | Variable template | Col DB |
|---|---|---|---|---|---|
| `contrat_loyer_ht` | Loyer HT (Mois) | number | ex: `83.33` | `CONTRAT_LOYER_HT` | `contrat_loyer_ht` |
| `contrat_tva_pourcent` | TVA % | select | `7`, `10`, `14`, `20` | `CONTRAT_TVA_POURCENT` | `contrat_tva_pourcent` |
| `contrat_loyer_ttc` | Loyer TTC (Mois) | text (calcule, readonly) | HT + TVA | `CONTRAT_LOYER_TTC` | `contrat_loyer_ttc` |
| `contrat_total_ht` | Montant Total du Loyer | text (calcule, readonly) | Loyer TTC x duree | `CONTRAT_TOTAL_HT` | `contrat_total_ht` |

### Renouvellement

| Champ name | Libellé | Type | Details | Variable template | Col DB |
|---|---|---|---|---|---|
| `contrat_type_renouvellement` | Type renouvellement | select | `Mensuel`, `Trimestriel`, `Annuel`, `2 ans`, `3 ans`, `4 ans`, `5 ans` | `CONTRAT_TYPE_RENOUVELLEMENT` | `contrat_type_renouvellement` |
| `contrat_renouv_loyer_ht` | Loyer HT (Mois) | number | ex: `166.67` | `CONTRAT_RENOUV_LOYER_HT` | `contrat_renouv_loyer_ht` |
| `contrat_renouv_tva_pourcent` | TVA % | select | `7`, `10`, `14`, `20` | `CONTRAT_RENOUV_TVA_POURCENT` | `contrat_renouv_tva_pourcent` |
| `contrat_renouv_loyer_ttc` | Loyer TTC (Mois) | text (calcule, readonly) | HT + TVA | `CONTRAT_RENOUV_LOYER_TTC` | `contrat_renouv_loyer_ttc` |
| `contrat_renouv_total_ht` | Montant Total Loyer renouv. | text (calcule, readonly) | TTC x 12 | — | `contrat_renouv_total_ht` |

### Notes

| Champ name | Libellé | Type | Variable template | Col DB |
|---|---|---|---|---|
| `contrat_notes` | Notes | textarea | — | `contrat_notes` |

---

## Etape 4 — Recapitulatif

Pas de champ de formulaire. Affiche toutes les donnees en read-only avec des boutons "Modifier" pour revenir aux etapes 1-3.

Donnees affichees :
- **Societe** : raison sociale, forme juridique, dossier, ICE, RC, IF, capital, part social, valeur nominale, adresse, ville, tribunal, email, telephone, activites statuts, activites OMPIC, type generation, procedure, mode depot
- **Associes** (pour chaque) : nom complet, CIN, nationalite, date naissance, lieu naissance, qualite, parts, capital detenu, gerant
- **Contrat** : type, type domiciliation, date contrat, date debut, date fin, duree mois, loyer HT, TVA, loyer TTC, total loyer, renouvellement, statut

---

## Etape 5 — Generation

Pas de champ de donnees metier.

| Champ name | Libellé | Type | Details |
|---|---|---|---|
| `templates[]` | Selection templates | checkbox | Path du template, filtre par forme juridique |
| `pdf` | Generer PDF | checkbox | Convertit le DOCX en PDF via LibreOffice |
| `nav_action` | Action | hidden | `generate` (previsualisation) ou `finish` (sauvegarde en base) |

Action **Creer le dossier complet** (`nav_action=finish`) : insere en base (table `societes`, `associes`, `contrats`, `documents_generes`) et redirige vers la fiche societe.

---

## Notes

- Les champs marques `(calcule)` sont automatiquement mis a jour par le JS cote navigateur. Ils sont en readonly.
- `associes[][nom_complet]` est un champ hidden calcule a partir de `civilite` + `prenom` + `nom`.
- `contrat_loyer_ttc` = `contrat_loyer_ht` x (1 + `contrat_tva_pourcent` / 100)
- `contrat_total_ht` = `contrat_loyer_ttc` x `contrat_duree_mois`
- `contrat_date_fin` = `contrat_date_debut` + `contrat_duree_mois` mois
- Les champs Parts/Capital/`%` (`data-capital-field`) ne sont affiches que pour les formes juridiques SARL / SARL AU (avec repartition automatique).
- Le `part_percent` (% Capital social) n'a pas de variable template associee — il sert uniquement au calcul de repartition dans le wizard.
