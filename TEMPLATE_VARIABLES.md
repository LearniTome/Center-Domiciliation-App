# Variables disponibles — Guide de reference rapide

Inserez `{{ NOM_VARIABLE }}` dans vos documents Word (.docx).

**Legende Templates :**
- **Creation** : Statuts, Annonce-Legale-Journal, Depot-Legal-Constitution, Declaration-Immatriculation-RC
- **Domiciliation** : Contrat-Domiciliation, Attestation-Domiciliation-Initiale

---

## Societe

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `SOCIETE_RAISON_SOCIALE` | Raison sociale | `societe_raison_sociale` | Denomination sociale complete | Creation + Domiciliation |
| `SOCIETE_FORME_JURIDIQUE` | Forme juridique | `societe_forme_juridique` | SARL, SARL AU, SA... | Creation + Domiciliation |
| `SOCIETE_ICE` | ICE | `societe_ice` | Identifiant Commercial | Creation + Domiciliation |
| `SOCIETE_RC` | RC | `societe_rc` | Registre de Commerce | Creation + Domiciliation |
| `SOCIETE_IF` | IF | `societe_if` | Identifiant Fiscal | Creation + Domiciliation |
| `SOCIETE_CAPITAL` | Capital | `societe_capital` | Capital social en euros | Creation (Statuts) |
| `SOCIETE_PART_SOCIAL` | Nombre de parts sociales | `societe_part_social` | Nombre total de parts | Creation (Statuts) |
| `SOCIETE_VALEUR_NOMINALE` | Valeur nominale | `societe_valeur_nominale` | Valeur nominale d'une part | Creation (Statuts) |
| `SOCIETE_VILLE` | Ville du siege | `societe_ville` | Ville du siege social | Creation (Statuts, Annonce-Legale) |
| `SOCIETE_TRIBUNAL` | Tribunal competent | `societe_tribunal` | Tribunal de commerce | Creation (Depot-Legal, Declaration-RC) |
| `SOCIETE_ADRESSE_SIEGE` | Adresse du siege social | `societe_adresse_siege` | Adresse complete du siege | Creation + Domiciliation |
| `SOCIETE_EMAIL` | Email | `societe_email` | Email de contact | Creation + Domiciliation |
| `SOCIETE_TELEPHONE` | Telephone | `societe_telephone` | Telephone de contact | Creation + Domiciliation |
| `SOCIETE_DOSSIER` | Dossier domiciliation | `societe_dossier` | Numero de dossier | Domiciliation |
| `SOCIETE_TYPE_GENERATION` | Type de generation | `societe_type_generation` | creation / domiciliation | Tous |
| `SOCIETE_PROCEDURE_CREATION` | Procedure de creation | `societe_procedure_creation` | Procedure (e.g. classique) | Creation |
| `SOCIETE_MODE_DEPOT` | Mode de depot | `societe_mode_depot` | Depot physique / electronique | Creation (Depot-Legal) |
| `SOCIETE_DATE_ICE` | Date immatriculation ICE | `societe_date_ice` | Date d'obtention de l'ICE | Creation (Declaration-RC) |
| `SOCIETE_DATE_EXP_CERT_NEG` | Date exp. certificat negatif | `societe_date_exp_cert_neg` | Expiration du certificat negatif | Creation |

---

## Associe (hors boucle)

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `ASSOCIE_NOM_COMPLET` | Nom complet | `associe_nom_complet` | Civilite + Prenom + Nom | Creation (Statuts, Depot-Legal) |
| `ASSOCIE_NOM` | Nom de famille | `associe_nom` | Nom patronymique | Creation (Statuts) |
| `ASSOCIE_PRENOM` | Prenom | `associe_prenom` | Prenom | Creation (Statuts) |
| `ASSOCIE_CIVILITE` | Civilite | `associe_civilite` | M. / Mme | Creation (Statuts) |
| `ASSOCIE_CIN` | CIN | `associe_cin` | Numero de CIN | Creation (Statuts, Declaration-RC) |
| `ASSOCIE_DATE_NAISSANCE` | Date naissance | `associe_date_naissance` | Date de naissance | Creation (Statuts) |
| `ASSOCIE_LIEU_NAISSANCE` | Lieu naissance | `associe_lieu_naissance` | Ville de naissance | Creation (Statuts) |
| `ASSOCIE_NATIONALITE` | Nationalite | `associe_nationalite` | Nationalite | Creation (Statuts) |
| `ASSOCIE_ADRESSE` | Adresse | `associe_adresse` | Adresse personnelle | Creation (Statuts) |
| `ASSOCIE_TELEPHONE` | Telephone | `associe_telephone` | Telephone personnel | Creation |
| `ASSOCIE_EMAIL` | Email | `associe_email` | Email personnel | Creation |
| `ASSOCIE_QUALITE` | Qualite | `associe_qualite` | Gerant, Associe... | Creation (Statuts) |
| `ASSOCIE_PARTS` | Nombre de parts | `associe_parts` | Parts detenues par l'associe | Creation (Statuts) |
| `ASSOCIE_CAPITAL_DETENU` | Capital detenu | `associe_capital_detenu` | Capital apporte par l'associe | Creation (Statuts) |
| `ASSOCIE_EST_GERANT` | Est gerant | `associe_est_gerant` | Oui / Non | Creation (Statuts, Depot-Legal) |

---

## Associe (boucle multi-associes)

Encadrez le bloc avec `{%p for a in associes %}` ... `{%p endfor %}`.

```
{%p for a in associes %}
  {{ a.PRENOM }} {{ a.NOM }} — {{ a.QUALITE }} — {{ a.PARTS }} parts
{%p endfor %}
```

| Variable | Description |
|----------|-------------|
| `{{ a.NOM_COMPLET }}` | Nom complet |
| `{{ a.NOM }}` | Nom de famille |
| `{{ a.PRENOM }}` | Prenom |
| `{{ a.CIVILITE }}` | Civilite |
| `{{ a.CIN }}` | CIN |
| `{{ a.DATE_NAISSANCE }}` | Date de naissance |
| `{{ a.LIEU_NAISSANCE }}` | Lieu de naissance |
| `{{ a.NATIONALITE }}` | Nationalite |
| `{{ a.ADRESSE }}` | Adresse |
| `{{ a.TELEPHONE }}` | Telephone |
| `{{ a.EMAIL }}` | Email |
| `{{ a.QUALITE }}` | Qualite |
| `{{ a.PARTS }}` | Nombre de parts |
| `{{ a.CAPITAL_DETENU }}` | Capital detenu |
| `{{ a.EST_GERANT }}` | Gerant (Oui/Non) |

---

## Contrat

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `CONTRAT_TYPE` | Type de contrat | `contrat_type` | Type du contrat | Domiciliation |
| `CONTRAT_TYPE_DOMICILIATION` | Type de domiciliation | `contrat_type_domiciliation` | Type de domiciliation | Domiciliation |
| `CONTRAT_DATE` | Date du contrat | `contrat_date` | Date de signature | Domiciliation |
| `CONTRAT_DATE_DEBUT` | Date de debut | `contrat_date_debut` | Debut de validite | Domiciliation |
| `CONTRAT_DATE_FIN` | Date de fin | `contrat_date_fin` | Fin de validite | Domiciliation |
| `CONTRAT_DUREE_MOIS` | Duree en mois | `contrat_duree_mois` | Duree du contrat en mois | Domiciliation |
| `CONTRAT_LOYER_TTC` | Loyer mensuel TTC | `contrat_loyer_ttc` | Loyer mensuel TTC | Domiciliation (Contrat) |
| `CONTRAT_LOYER_HT` | Loyer mensuel HT | `contrat_loyer_ht` | Loyer mensuel HT | Domiciliation |
| `CONTRAT_TVA_POURCENT` | Taux TVA | `contrat_tva_pourcent` | Taux TVA applicable | Domiciliation |
| `CONTRAT_TOTAL_HT` | Montant total HT | `contrat_total_ht` | Total HT du contrat | Domiciliation |
| `CONTRAT_FRAIS_INTERMEDIAIRE` | Frais intermediaire | `contrat_frais_intermediaire` | Frais intermediaires | Domiciliation |
| `CONTRAT_CAUTION` | Caution | `contrat_caution` | Montant de la caution | Domiciliation |
| `CONTRAT_STATUT` | Statut | `contrat_statut` | Statut (actif, resilie...) | Domiciliation |
| `CONTRAT_MODE_SIGNATURE` | Mode de signature | `contrat_mode_signature` | Signature electronique / papier | Domiciliation |
| `CONTRAT_PACK_MONTANT_TTC` | Pack demarrage montant TTC | `contrat_pack_montant_ttc` | Montant du pack TTC | Domiciliation |
| `CONTRAT_PACK_LOYER_TTC` | Pack demarrage loyer TTC | `contrat_pack_loyer_ttc` | Loyer du pack TTC | Domiciliation |
| `CONTRAT_TYPE_RENOUVELLEMENT` | Type de renouvellement | `contrat_type_renouvellement` | Tacite / Express | Domiciliation |
| `CONTRAT_RENOUV_TVA_POURCENT` | TVA renouvellement | `contrat_renouv_tva_pourcent` | TVA sur renouvellement | Domiciliation |
| `CONTRAT_RENOUV_LOYER_HT` | Loyer HT renouvellement | `contrat_renouv_loyer_ht` | Loyer HT apres renouvellement | Domiciliation |
| `CONTRAT_RENOUV_LOYER_TTC` | Loyer TTC renouvellement | `contrat_renouv_loyer_ttc` | Loyer TTC apres renouvellement | Domiciliation |
| `CONTRAT_RENOUV_ANNUEL_TTC` | Loyer annuel TTC renouvellement | `contrat_renouv_annuel_ttc` | Loyer annuel TTC renouvele | Domiciliation |

---

## Activites (Statuts)

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `ACTIVITES` | Liste des activites (boucle) | `societe_activites_statuts` | Liste complete des activites | Creation (Statuts) |
| `ACTIVITES_INLINE` | Activites en ligne (;) | — (calculee) | Activites separees par `; ` | Creation (Statuts) |
| `ACTIVITES_PLAIN` | Activites en ligne (,) | — (calculee) | Activites separees par `, ` | Creation (Statuts) |
| `ACTIVITES_PUCES` | Activites en puces | — (calculee) | Activites avec tirets | Creation (Statuts) |
| `ACTIVITES_SUITE_PUCES` | Suite des puces | — (calculee) | Suite sans titre | Creation (Statuts) |
| `NB_ACTIVITES` | Nombre d'activites | — (calcule) | Compteur | Creation (Statuts) |

---

## Activites OMPIC

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `ACTIVITES_OMPIC` | Liste des activites OMPIC | `societe_activites_ompic` | Activites certificat negatif | Creation |
| `OMPIC_INLINE` | Activites OMPIC en ligne | — (calculee) | OMPIC separees par `; ` | Creation |
| `OMPIC_PUCES` | Activites OMPIC en puces | — (calculee) | OMPIC avec tirets | Creation |
| `NB_OMPIC` | Nombre d'activites OMPIC | — (calcule) | Compteur OMPIC | Creation |

---

## Activites individuelles

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `ACTIVITY1` | Activite 1 | — (calcule) | Premiere activite | Creation (Statuts) |
| `ACTIVITY2` | Activite 2 | — (calcule) | Deuxieme activite | Creation (Statuts) |
| `ACTIVITY3` | Activite 3 | — (calcule) | Troisieme activite | Creation (Statuts) |
| `ACTIVITY4` | Activite 4 | — (calcule) | Quatrieme activite | Creation (Statuts) |
| `ACTIVITY5` | Activite 5 | — (calcule) | Cinquieme activite | Creation (Statuts) |

---

## Dates automatiques

| Variable | Libellé champ | Nom db | Description | Utiliser dans Templates |
|---|---|---|---|---|
| `DATE` | Date du jour (court) | — (auto) | `15/05/2026` | Tous |
| `DATE_LONG` | Date du jour (long) | — (auto) | `15 mai 2026` | Tous |
| `ANNEE` | Annee en cours | — (auto) | `2026` | Tous |
| `MOIS` | Mois en cours | — (auto) | `05` | Tous |
| `JOUR` | Jour en cours | — (auto) | `15` | Tous |

---

## Notes

- Les noms de variables sont insensibles a la casse dans le code : `{{ capital }}`, `{{ CAPITAL }}`, `{{ Capital }}` ne fonctionnent plus. Utilisez exclusivement les noms ci-dessus.
- Les variables en boucle (`a.*`) restent sans prefixe et ne sont pas impactees par le renommage.
