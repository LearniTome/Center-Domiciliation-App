# Variables disponibles — Guide de reference rapide

Inserez `{{ NOM_VARIABLE }}` dans vos documents Word (.docx).

---

## Societe

| Variable | Description |
|----------|-------------|
| `{{ SOCIETE_RAISON_SOCIALE }}` | Raison sociale / Denomination |
| `{{ SOCIETE_FORME_JURIDIQUE }}` | Forme juridique (SARL, SAS, etc.) |
| `{{ SOCIETE_ICE }}` | Identifiant Commercial |
| `{{ SOCIETE_RC }}` | Registre de Commerce |
| `{{ SOCIETE_IF }}` | Identifiant Fiscal |
| `{{ SOCIETE_CAPITAL }}` | Capital social |
| `{{ SOCIETE_PART_SOCIAL }}` | Nombre de parts sociales |
| `{{ SOCIETE_VALEUR_NOMINALE }}` | Valeur nominale d'une part |
| `{{ SOCIETE_VILLE }}` | Ville du siege |
| `{{ SOCIETE_TRIBUNAL }}` | Tribunal competent |
| `{{ SOCIETE_ADRESSE_SIEGE }}` | Adresse du siege social |
| `{{ SOCIETE_EMAIL }}` | Email de contact |
| `{{ SOCIETE_TELEPHONE }}` | Telephone de contact |
| `{{ SOCIETE_DOSSIER }}` | Numero de dossier domiciliation |
| `{{ SOCIETE_TYPE_GENERATION }}` | Type de generation |
| `{{ SOCIETE_PROCEDURE_CREATION }}` | Procedure de creation |
| `{{ SOCIETE_MODE_DEPOT }}` | Mode de depot |
| `{{ SOCIETE_DATE_ICE }}` | Date d'immatriculation ICE |
| `{{ SOCIETE_DATE_EXP_CERT_NEG }}` | Date expiration certificat negatif |

---

## Associes (hors boucle)

Ces variables utilisent le premier associe du dossier.

| Variable | Description |
|----------|-------------|
| `{{ ASSOCIE_NOM_COMPLET }}` | Nom complet (civilite + prenom + nom) |
| `{{ ASSOCIE_NOM }}` | Nom de famille |
| `{{ ASSOCIE_PRENOM }}` | Prenom |
| `{{ ASSOCIE_CIVILITE }}` | Civilite (M. / Mme) |
| `{{ ASSOCIE_CIN }}` | Numero CIN |
| `{{ ASSOCIE_DATE_NAISSANCE }}` | Date de naissance |
| `{{ ASSOCIE_LIEU_NAISSANCE }}` | Lieu de naissance |
| `{{ ASSOCIE_NATIONALITE }}` | Nationalite |
| `{{ ASSOCIE_ADRESSE }}` | Adresse |
| `{{ ASSOCIE_TELEPHONE }}` | Telephone |
| `{{ ASSOCIE_EMAIL }}` | Email |
| `{{ ASSOCIE_QUALITE }}` | Qualite (Gerant, Associe, etc.) |
| `{{ ASSOCIE_PARTS }}` | Nombre de parts |
| `{{ ASSOCIE_CAPITAL_DETENU }}` | Capital detenu |
| `{{ ASSOCIE_EST_GERANT }}` | Gerant (Oui / Non) |

---

## Associes (boucle multi-associes)

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

| Variable | Description |
|----------|-------------|
| `{{ CONTRAT_TYPE }}` | Type de contrat |
| `{{ CONTRAT_TYPE_DOMICILIATION }}` | Type de domiciliation |
| `{{ CONTRAT_DATE }}` | Date du contrat |
| `{{ CONTRAT_DATE_DEBUT }}` | Date de debut |
| `{{ CONTRAT_DATE_FIN }}` | Date de fin |
| `{{ CONTRAT_DUREE_MOIS }}` | Duree en mois |
| `{{ CONTRAT_LOYER_TTC }}` | Loyer mensuel TTC |
| `{{ CONTRAT_LOYER_HT }}` | Loyer mensuel HT |
| `{{ CONTRAT_TVA_POURCENT }}` | Taux TVA % |
| `{{ CONTRAT_TOTAL_HT }}` | Montant total HT |
| `{{ CONTRAT_FRAIS_INTERMEDIAIRE }}` | Frais intermediaire |
| `{{ CONTRAT_CAUTION }}` | Montant de la caution |
| `{{ CONTRAT_STATUT }}` | Statut du contrat |
| `{{ CONTRAT_MODE_SIGNATURE }}` | Mode de signature gerance |

### Pack demarrage

| Variable | Description |
|----------|-------------|
| `{{ CONTRAT_PACK_MONTANT_TTC }}` | Montant pack demarrage TTC |
| `{{ CONTRAT_PACK_LOYER_TTC }}` | Loyer mensuel pack demarrage TTC |

### Renouvellement

| Variable | Description |
|----------|-------------|
| `{{ CONTRAT_TYPE_RENOUVELLEMENT }}` | Type de renouvellement |
| `{{ CONTRAT_RENOUV_TVA_POURCENT }}` | TVA renouvellement % |
| `{{ CONTRAT_RENOUV_LOYER_HT }}` | Loyer mensuel HT renouvellement |
| `{{ CONTRAT_RENOUV_LOYER_TTC }}` | Loyer mensuel TTC renouvellement |
| `{{ CONTRAT_RENOUV_ANNUEL_TTC }}` | Loyer annuel TTC renouvellement |

---

## Activites (Statuts)

| Variable | Description |
|----------|-------------|
| `{{ ACTIVITES }}` | Liste des activites (texte) |
| `{{ ACTIVITES_INLINE }}` | Activites en ligne (separes par `; `) |
| `{{ ACTIVITES_PLAIN }}` | Activites en ligne (separes par `, `) |
| `{{ ACTIVITES_PUCES }}` | Activites en puces (avec tirets) |
| `{{ ACTIVITES_SUITE_PUCES }}` | Suite des puces (sans titre) |
| `{{ NB_ACTIVITES }}` | Nombre d'activites |

---

## Activites OMPIC

| Variable | Description |
|----------|-------------|
| `{{ ACTIVITES_OMPIC }}` | Liste des activites OMPIC |
| `{{ OMPIC_INLINE }}` | Activites OMPIC en ligne |
| `{{ OMPIC_PUCES }}` | Activites OMPIC en puces |
| `{{ NB_OMPIC }}` | Nombre d'activites OMPIC |

---

## Dates automatiques

| Variable | Description | Exemple |
|----------|-------------|---------|
| `{{ DATE }}` | Date du jour (court) | `15/05/2026` |
| `{{ DATE_LONG }}` | Date du jour (long) | `15 mai 2026` |
| `{{ ANNEE }}` | Annee en cours | `2026` |
| `{{ MOIS }}` | Mois en cours | `05` |
| `{{ JOUR }}` | Jour en cours | `15` |

---

## Notes

- Les noms de variables sont insensibles a la casse dans le code : `{{ capital }}`, `{{ CAPITAL }}`, `{{ Capital }}` fonctionnent tous.
- Les variables en boucle (`a.*`) restent sans prefixe et ne sont pas impactees par le renommage.
