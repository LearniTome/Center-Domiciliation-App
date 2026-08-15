# Analyse des variables — Correspondance avec les champs formulaire

> Généré le 04/06/2026

## Légende

- ✅ **Oui** → Le champ existe dans un formulaire HTML (`<input>`, `<select>`, etc.)
- ❌ **Non** → Aucun champ HTML trouvé

---

## Variables AVEC champ formulaire (50)

| Variable | Section | Champ |
|---|---|---|
| `ASSOCIE_ADRESSE` | Associe | `associe_adresse` |
| `ASSOCIE_CAPITAL_DETENU` | Associe | `associe_capital_detenu` |
| `ASSOCIE_CIN` | Associe | `associe_cin` |
| `ASSOCIE_CIVILITE` | Associe | `associe_civilite` |
| `ASSOCIE_DATE_NAISSANCE` | Associe | `associe_date_naissance` |
| `ASSOCIE_DATE_VALIDITE_CIN` | Associe | `associe_date_validite_cin` |
| `ASSOCIE_EMAIL` | Associe | `associe_email` |
| `ASSOCIE_EST_GERANT` | Associe | `associe_est_gerant` |
| `ASSOCIE_LIEU_NAISSANCE` | Associe | `associe_lieu_naissance` |
| `ASSOCIE_NATIONALITE` | Associe | `associe_nationalite` |
| `ASSOCIE_NOM` | Associe | `associe_nom` |
| `ASSOCIE_NOM_COMPLET` | Associe | `associe_nom_complet` |
| `ASSOCIE_PARTS` | Associe | `associe_parts` |
| `ASSOCIE_PRENOM` | Associe | `associe_prenom` |
| `ASSOCIE_QUALITE` | Associe | `associe_qualite` |
| `ASSOCIE_TELEPHONE` | Associe | `associe_telephone` |
| `SOCIETE_ADRESSE_SIEGE` | Societe | `societe_adresse_siege` |
| `SOCIETE_CAPITAL` | Societe | `societe_capital` |
| `SOCIETE_DATE_EXP_CERT_NEG` | Societe | `societe_date_exp_cert_neg` |
| `SOCIETE_DATE_ICE` | Societe | `societe_date_ice` |
| `SOCIETE_DOSSIER` | Societe | `societe_dossier` |
| `SOCIETE_DOSSIER_CREATION` | Societe | `societe_dossier_creation` |
| `SOCIETE_EMAIL` | Societe | `societe_email` |
| `SOCIETE_FORME_JURIDIQUE` | Societe | `societe_forme_juridique` |
| `SOCIETE_ICE` | Societe | `societe_ice` |
| `SOCIETE_IF` | Societe | `societe_if` |
| `SOCIETE_MODE_DEPOT` | Societe | `societe_mode_depot` |
| `SOCIETE_PART_SOCIAL` | Societe | `societe_part_social` |
| `SOCIETE_PROCEDURE_CREATION` | Societe | `societe_procedure_creation` |
| `SOCIETE_RAISON_SOCIALE` | Societe | `societe_raison_sociale` |
| `SOCIETE_RC` | Societe | `societe_rc` |
| `SOCIETE_TELEPHONE` | Societe | `societe_telephone` |
| `SOCIETE_TRIBUNAL` | Societe | `societe_tribunal` |
| `SOCIETE_TYPE_GENERATION` | Societe | `societe_type_generation` |
| `SOCIETE_VALEUR_NOMINALE` | Societe | `societe_valeur_nominale` |
| `SOCIETE_VILLE` | Societe | `societe_ville` |
| `CONTRAT_DATE` | Contrat | `contrat_date` |
| `CONTRAT_DATE_DEBUT` | Contrat | `contrat_date_debut` |
| `CONTRAT_DATE_FIN` | Contrat | `contrat_date_fin` |
| `CONTRAT_DUREE_MOIS` | Contrat | `contrat_duree_mois` |
| `CONTRAT_LOYER_HT` | Contrat | `contrat_loyer_ht` |
| `CONTRAT_LOYER_TTC` | Contrat | `contrat_loyer_ttc` |
| `CONTRAT_RENOUV_LOYER_HT` | Contrat | `contrat_renouv_loyer_ht` |
| `CONTRAT_RENOUV_LOYER_TTC` | Contrat | `contrat_renouv_loyer_ttc` |
| `CONTRAT_RENOUV_TVA_POURCENT` | Contrat | `contrat_renouv_tva_pourcent` |
| `CONTRAT_STATUT` | Contrat | `contrat_statut` |
| `CONTRAT_TOTAL_HT` | Contrat | `contrat_total_ht` |
| `CONTRAT_TVA_POURCENT` | Contrat | `contrat_tva_pourcent` |
| `CONTRAT_TYPE` | Contrat | `contrat_type_domiciliation` |
| `CONTRAT_TYPE_DOMICILIATION` | Contrat | `contrat_type_domiciliation` |
| `CONTRAT_TYPE_RENOUVELLEMENT` | Contrat | `contrat_type_renouvellement` |

---

## Variables SANS champ formulaire (22)

| Variable | Section | Note |
|---|---|---|
| **Variables calculées (liste activités)** |||
| `ACTIVITES` | Societe | Générée depuis la liste des activités OMPIC saisies |
| `ACTIVITES_INLINE` | Societe | Format en ligne (;) |
| `ACTIVITES_PLAIN` | Societe | Format en ligne (,) |
| `ACTIVITES_PUCES` | Societe | Format puces HTML |
| `ACTIVITES_SUITE_PUCES` | Societe | Suite des puces |
| `ACTIVITES_OMPIC` | Societe | Liste OMPIC formatée |
| `NB_ACTIVITES` | Societe | Nombre d'activités |
| `OMPIC_INLINE` | Date | Format OMPIC en ligne |
| `OMPIC_PUCES` | Date | Format OMPIC en puces |
| `NB_OMPIC` | Date | Nombre d'activités OMPIC |
| **Dates générées automatiquement** |||
| `DATE` | Date | Date du jour |
| `DATE_LONG` | Date | Date en lettres |
| `ANNEE` | Date | Année courante |
| `MOIS` | Date | Mois courant |
| `JOUR` | Date | Jour courant |
| **Champs manquants dans le formulaire** |||
| `CONTRAT_CAUTION` | Contrat | `contrat_caution` — à ajouter |
| `CONTRAT_FRAIS_INTERMEDIAIRE` | Contrat | `contrat_frais_intermediaire` — à ajouter |
| `CONTRAT_MODE_SIGNATURE` | Contrat | `contrat_mode_signature` — à ajouter |
| `CONTRAT_PACK_MONTANT_TTC` | Contrat | `contrat_pack_montant_ttc` — à ajouter |
| `CONTRAT_PACK_LOYER_TTC` | Contrat | `contrat_pack_loyer_ttc` — à ajouter |
| `CONTRAT_RENOUV_ANNUEL_TTC` | Contrat | `contrat_renouv_annuel_ttc` — à ajouter |
| `SOCIETE_TRIBUNAL_TYPE` | Societe | `societe_tribunal_type` — à ajouter |

---

## Résumé

- **Total variables** : 72
- **Avec champ formulaire** : 50 ✅
- **Sans champ formulaire** : 22 ❌
  - Variables calculées (boucles activités) : 10
  - Dates automatiques : 5
  - Champs à créer : 7
