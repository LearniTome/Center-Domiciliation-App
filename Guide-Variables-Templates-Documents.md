# Variables disponibles — Guide de reference rapide

Inserez `{{ NOM_VARIABLE }}` dans vos documents Word (.docx).

**Legende Templates :**
- **Creation** : Statuts, Annonce-Legale-Journal, Depot-Legal-Constitution, Declaration-Immatriculation-RC
- **Domiciliation** : Contrat-Domiciliation, Attestation-Domiciliation-Initiale

**Colonne Champ creation :** nom de l'attribut `name=""` dans le formulaire de l'Assistant de creation (`pages/creation.php`). Les champs marques `(calcule)` sont automatiquement calcules par le JS. Ceux marques `—` n'ont pas de champ dedie dans l'assistant.

---

## Societe

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `SOCIETE_RAISON_SOCIALE` | Raison sociale | `societe_raison_sociale` | `societe_raison_sociale` | Creation + Domiciliation |
| `SOCIETE_FORME_JURIDIQUE` | Forme juridique | `societe_forme_juridique` | `societe_forme_juridique` | Creation + Domiciliation |
| `SOCIETE_ICE` | ICE | `societe_ice` | `societe_ice` | Creation + Domiciliation |
| `SOCIETE_RC` | RC | `societe_rc` | `societe_rc` | Creation + Domiciliation |
| `SOCIETE_IF` | IF | `societe_if` | `societe_if` | Creation + Domiciliation |
| `SOCIETE_CAPITAL` | Capital | `societe_capital` | `societe_capital` | Creation (Statuts) |
| `SOCIETE_PART_SOCIAL` | Nombre de parts sociales | `societe_part_social` | `societe_part_social` | Creation (Statuts) |
| `SOCIETE_VALEUR_NOMINALE` | Valeur nominale | `societe_valeur_nominale` | `societe_valeur_nominale` | Creation (Statuts) |
| `SOCIETE_VILLE` | Ville du siege | `societe_ville` | `societe_ville` | Creation (Statuts, Annonce-Legale) |
| `SOCIETE_TRIBUNAL` | Tribunal competent | `societe_tribunal` | `societe_tribunal` | Creation (Depot-Legal, Declaration-RC) |
| `SOCIETE_ADRESSE_SIEGE` | Adresse du siege social | `societe_adresse_siege` | `societe_adresse_siege` | Creation + Domiciliation |
| `SOCIETE_EMAIL` | Email | `societe_email` | `societe_email` | Creation + Domiciliation |
| `SOCIETE_TELEPHONE` | Telephone | `societe_telephone` | `societe_telephone` | Creation + Domiciliation |
| `SOCIETE_DOSSIER` | Dossier domiciliation | `societe_dossier` | `societe_dossier` | Domiciliation |
| `SOCIETE_TYPE_GENERATION` | Type de generation | `societe_type_generation` | `type_generation` | Tous |
| `SOCIETE_PROCEDURE_CREATION` | Procedure de creation | `societe_procedure_creation` | `procedure_creation` | Creation |
| `SOCIETE_MODE_DEPOT` | Mode de depot | `societe_mode_depot` | `mode_depot_creation` | Creation (Depot-Legal) |
| `SOCIETE_DATE_ICE` | Date immatriculation ICE | `societe_date_ice` | `societe_date_ice` | Creation (Declaration-RC) |
| `SOCIETE_DATE_EXP_CERT_NEG` | Date exp. certificat negatif | `societe_date_exp_cert_neg` | `societe_date_exp_cert_neg` | Creation |

---

## Associe (hors boucle)

La variable `ASSOCIE_*` renvoie toujours les donnees du **premier associe** uniquement. Pour les autres associes, utilisez la boucle `a.*`.

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `ASSOCIE_NOM_COMPLET` | Nom complet | `associe_nom_complet` | `associes[][nom_complet]` (calcule) | Creation (Statuts, Depot-Legal) |
| `ASSOCIE_NOM` | Nom de famille | `associe_nom` | `associes[][nom]` | Creation (Statuts) |
| `ASSOCIE_PRENOM` | Prenom | `associe_prenom` | `associes[][prenom]` | Creation (Statuts) |
| `ASSOCIE_CIVILITE` | Civilite | `associe_civilite` | `associes[][civilite]` | Creation (Statuts) |
| `ASSOCIE_CIN` | CIN | `associe_cin` | `associes[][cin]` | Creation (Statuts, Declaration-RC) |
| `ASSOCIE_DATE_VALIDITE_CIN` | Date validite CIN | `associe_date_validite_cin` | `associes[][date_validite_cin]` | Creation (Declaration-RC) |
| `ASSOCIE_DATE_NAISSANCE` | Date naissance | `associe_date_naissance` | `associes[][date_naiss]` | Creation (Statuts) |
| `ASSOCIE_LIEU_NAISSANCE` | Lieu naissance | `associe_lieu_naissance` | `associes[][lieu_naiss]` | Creation (Statuts) |
| `ASSOCIE_NATIONALITE` | Nationalite | `associe_nationalite` | `associes[][nationalite]` | Creation (Statuts) |
| `ASSOCIE_ADRESSE` | Adresse | `associe_adresse` | `associes[][adresse]` | Creation (Statuts) |
| `ASSOCIE_TELEPHONE` | Telephone | `associe_telephone` | `associes[][phone]` | Creation |
| `ASSOCIE_EMAIL` | Email | `associe_email` | `associes[][societe_email]` | Creation |
| `ASSOCIE_QUALITE` | Qualite | `associe_qualite` | `associes[][qualite_associe]` | Creation (Statuts) |
| `ASSOCIE_PARTS` | Nombre de parts | `associe_parts` | `associes[][parts]` | Creation (Statuts) |
| `ASSOCIE_CAPITAL_DETENU` | Capital detenu | `associe_capital_detenu` | `associes[][capital_detenu]` | Creation (Statuts) |
| `ASSOCIE_EST_GERANT` | Est gerant | `associe_est_gerant` | `associes[][is_gerant]` | Creation (Statuts, Depot-Legal) |

---

## Associe (boucle multi-associes)

Encadrez le bloc avec `{%p for a in associes %}` ... `{%p endfor %}`.

```
{%p for a in associes %}
  {{ a.PRENOM }} {{ a.NOM }} — {{ a.QUALITE }} — {{ a.PARTS }} parts
{%p endfor %}
```

| Variable | Description | Champ creation correspondant |
|---|---|---|
| `{{ a.NOM_COMPLET }}` | Nom complet | `associes[][nom_complet]` (calcule) |
| `{{ a.NOM }}` | Nom de famille | `associes[][nom]` |
| `{{ a.PRENOM }}` | Prenom | `associes[][prenom]` |
| `{{ a.CIVILITE }}` | Civilite | `associes[][civilite]` |
| `{{ a.CIN }}` | CIN | `associes[][cin]` |
| `{{ a.DATE_NAISSANCE }}` | Date de naissance | `associes[][date_naiss]` |
| `{{ a.LIEU_NAISSANCE }}` | Lieu de naissance | `associes[][lieu_naiss]` |
| `{{ a.NATIONALITE }}` | Nationalite | `associes[][nationalite]` |
| `{{ a.ADRESSE }}` | Adresse | `associes[][adresse]` |
| `{{ a.TELEPHONE }}` | Telephone | `associes[][phone]` |
| `{{ a.EMAIL }}` | Email | `associes[][societe_email]` |
| `{{ a.QUALITE }}` | Qualite | `associes[][qualite_associe]` |
| `{{ a.PARTS }}` | Nombre de parts | `associes[][parts]` |
| `{{ a.CAPITAL_DETENU }}` | Capital detenu | `associes[][capital_detenu]` |
| `{{ a.EST_GERANT }}` | Gerant (Oui/Non) | `associes[][is_gerant]` |

---

## Contrat

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `CONTRAT_TYPE` | Type de contrat | `contrat_type` | `contrat_type` | Domiciliation |
| `CONTRAT_TYPE_DOMICILIATION` | Type de domiciliation | `contrat_type_domiciliation` | `contrat_type_domiciliation` | Domiciliation |
| `CONTRAT_DATE` | Date du contrat | `contrat_date` | `contrat_date` | Domiciliation |
| `CONTRAT_DATE_DEBUT` | Date de debut | `contrat_date_debut` | `contrat_date_debut` | Domiciliation |
| `CONTRAT_DATE_FIN` | Date de fin | `contrat_date_fin` | `contrat_date_fin` (calcule) | Domiciliation |
| `CONTRAT_DUREE_MOIS` | Duree en mois | `contrat_duree_mois` | `contrat_duree_mois` | Domiciliation |
| `CONTRAT_LOYER_TTC` | Loyer mensuel TTC | `contrat_loyer_ttc` | `contrat_loyer_ttc` (calcule) | Domiciliation (Contrat) |
| `CONTRAT_LOYER_HT` | Loyer mensuel HT | `contrat_loyer_ht` | `contrat_loyer_ht` | Domiciliation |
| `CONTRAT_TVA_POURCENT` | Taux TVA | `contrat_tva_pourcent` | `contrat_tva_pourcent` | Domiciliation |
| `CONTRAT_TOTAL_HT` | Montant total HT | `contrat_total_ht` | `contrat_total_ht` (calcule) | Domiciliation |
| `CONTRAT_FRAIS_INTERMEDIAIRE` | Frais intermediaire | `contrat_frais_intermediaire` | — | Domiciliation |
| `CONTRAT_CAUTION` | Caution | `contrat_caution` | — | Domiciliation |
| `CONTRAT_STATUT` | Statut | `contrat_statut` | `contrat_statut` | Domiciliation |
| `CONTRAT_MODE_SIGNATURE` | Mode de signature | `contrat_mode_signature` | — | Domiciliation |
| `CONTRAT_PACK_MONTANT_TTC` | Pack demarrage montant TTC | `contrat_pack_montant_ttc` | — | Domiciliation |
| `CONTRAT_PACK_LOYER_TTC` | Pack demarrage loyer TTC | `contrat_pack_loyer_ttc` | — | Domiciliation |
| `CONTRAT_TYPE_RENOUVELLEMENT` | Type de renouvellement | `contrat_type_renouvellement` | `contrat_type_renouvellement` | Domiciliation |
| `CONTRAT_RENOUV_TVA_POURCENT` | TVA renouvellement | `contrat_renouv_tva_pourcent` | `contrat_renouv_tva_pourcent` | Domiciliation |
| `CONTRAT_RENOUV_LOYER_HT` | Loyer HT renouvellement | `contrat_renouv_loyer_ht` | `contrat_renouv_loyer_ht` | Domiciliation |
| `CONTRAT_RENOUV_LOYER_TTC` | Loyer TTC renouvellement | `contrat_renouv_loyer_ttc` | `contrat_renouv_loyer_ttc` (calcule) | Domiciliation |
| `CONTRAT_RENOUV_ANNUEL_TTC` | Loyer annuel TTC renouvellement | `contrat_renouv_annuel_ttc` | — | Domiciliation |

---

## Activites (Statuts)

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `ACTIVITES` | Liste des activites (boucle) | `societe_activites_statuts` | `societe_activites_statuts[]` | Creation (Statuts) |
| `ACTIVITES_INLINE` | Activites en ligne (;) | — (calculee) | — | Creation (Statuts) |
| `ACTIVITES_PLAIN` | Activites en ligne (,) | — (calculee) | — | Creation (Statuts) |
| `ACTIVITES_PUCES` | Activites en puces | — (calculee) | — | Creation (Statuts) |
| `ACTIVITES_SUITE_PUCES` | Suite des puces | — (calculee) | — | Creation (Statuts) |
| `NB_ACTIVITES` | Nombre d'activites | — (calcule) | — | Creation (Statuts) |

---

## Activites OMPIC

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `ACTIVITES_OMPIC` | Liste des activites OMPIC | `societe_activites_ompic` | `societe_activites_ompic` | Creation |
| `OMPIC_INLINE` | Activites OMPIC en ligne | — (calculee) | — | Creation |
| `OMPIC_PUCES` | Activites OMPIC en puces | — (calculee) | — | Creation |
| `NB_OMPIC` | Nombre d'activites OMPIC | — (calcule) | — | Creation |

---

## Activites individuelles

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `ACTIVITY1` | Activite 1 | — (calcule) | — | Creation (Statuts) |
| `ACTIVITY2` | Activite 2 | — (calcule) | — | Creation (Statuts) |
| `ACTIVITY3` | Activite 3 | — (calcule) | — | Creation (Statuts) |
| `ACTIVITY4` | Activite 4 | — (calcule) | — | Creation (Statuts) |
| `ACTIVITY5` | Activite 5 | — (calcule) | — | Creation (Statuts) |

---

## Dates automatiques

| Variable | Libellé champ | Nom db | Champ creation | Utiliser dans Templates |
|---|---|---|---|---|
| `DATE` | Date du jour (court) | — (auto) | — | Tous |
| `DATE_LONG` | Date du jour (long) | — (auto) | — | Tous |
| `ANNEE` | Annee en cours | — (auto) | — | Tous |
| `MOIS` | Mois en cours | — (auto) | — | Tous |
| `JOUR` | Jour en cours | — (auto) | — | Tous |

---

## Notes

- Les noms de variables sont insensibles a la casse dans le code : `{{ capital }}`, `{{ CAPITAL }}`, `{{ Capital }}` ne fonctionnent plus. Utilisez exclusivement les noms ci-dessus.
- Les variables en boucle (`a.*`) restent sans prefixe et ne sont pas impactees par le renommage.
- La colonne **Champ creation** correspond au `name=""` du formulaire dans l'Assistant de creation (`pages/creation.php`). Les champs calcules par le JS sont marques `(calcule)`. Les champs sans champ dedie sont marques `—`.
