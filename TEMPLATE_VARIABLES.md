# Variables disponibles — Guide de reference rapide

Inserez `{{ NOM_VARIABLE }}` dans vos documents Word (.docx).

---

## Societe

| Variable | Description |
|----------|-------------|
| `{{ RAISON_SOCIALE }}` | Raison sociale / Denomination |
| `{{ FORME_JURIDIQUE }}` | Forme juridique (SARL, SAS, etc.) |
| `{{ ICE }}` | Identifiant Commercial |
| `{{ RC }}` | Registre de Commerce |
| `{{ IF }}` | Identifiant Fiscal |
| `{{ CAPITAL }}` | Capital social |
| `{{ PART_SOCIAL }}` | Nombre de parts sociales |
| `{{ VALEUR_NOMINALE }}` | Valeur nominale d'une part |
| `{{ VILLE }}` | Ville du siege |
| `{{ TRIBUNAL }}` | Tribunal competent |
| `{{ ADRESSE_SIEGE }}` | Adresse du siege social |
| `{{ EMAIL }}` | Email de contact |
| `{{ TELEPHONE }}` | Telephone de contact |
| `{{ DOSSIER }}` | Numero de dossier domiciliation |
| `{{ TYPE_GENERATION }}` | Type de generation |
| `{{ PROCEDURE_CREATION }}` | Procedure de creation |
| `{{ MODE_DEPOT }}` | Mode de depot |
| `{{ DATE_ICE }}` | Date d'immatriculation ICE |
| `{{ DATE_EXP_CERT_NEG }}` | Date expiration certificat negatif |

---

## Associes (hors boucle)

Ces variables utilisent le premier associe du dossier.

| Variable | Description |
|----------|-------------|
| `{{ NOM_COMPLET }}` | Nom complet (civilite + prenom + nom) |
| `{{ NOM }}` | Nom de famille |
| `{{ PRENOM }}` | Prenom |
| `{{ CIVILITE }}` | Civilite (M. / Mme) |
| `{{ CIN }}` | Numero CIN |
| `{{ DATE_NAISSANCE }}` | Date de naissance |
| `{{ LIEU_NAISSANCE }}` | Lieu de naissance |
| `{{ NATIONALITE }}` | Nationalite |
| `{{ ADRESSE }}` | Adresse |
| `{{ TELEPHONE }}` | Telephone |
| `{{ EMAIL }}` | Email |
| `{{ QUALITE }}` | Qualite (Gerant, Associe, etc.) |
| `{{ PARTS }}` | Nombre de parts |
| `{{ CAPITAL_DETENU }}` | Capital detenu |
| `{{ EST_GERANT }}` | Gerant (Oui / Non) |

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
| `{{ a.CIN }}` | CIN |
| `{{ a.NATIONALITE }}` | Nationalite |
| `{{ a.QUALITE }}` | Qualite |
| `{{ a.PARTS }}` | Nombre de parts |
| `{{ a.CAPITAL_DETENU }}` | Capital detenu |
| `{{ a.EST_GERANT }}` | Gerant (Oui/Non) |
| `{{ a.ADRESSE }}` | Adresse |
| `{{ a.EMAIL }}` | Email |
| `{{ a.TELEPHONE }}` | Telephone |
| `{{ a.DATE_NAISSANCE }}` | Date de naissance |
| `{{ a.LIEU_NAISSANCE }}` | Lieu de naissance |

---

## Contrat

| Variable | Description |
|----------|-------------|
| `{{ TYPE_CONTRAT }}` | Type de contrat |
| `{{ TYPE_DOMICILIATION }}` | Type de domiciliation |
| `{{ DATE_CONTRAT }}` | Date du contrat |
| `{{ DATE_DEBUT }}` | Date de debut |
| `{{ DATE_FIN }}` | Date de fin |
| `{{ DUREE_MOIS }}` | Duree en mois |
| `{{ LOYER_TTC }}` | Loyer mensuel TTC |
| `{{ LOYER_HT }}` | Loyer mensuel HT |
| `{{ TVA_POURCENT }}` | Taux TVA % |
| `{{ TOTAL_HT }}` | Montant total HT |
| `{{ FRAIS_INTERMEDIAIRE }}` | Frais intermediaire |
| `{{ CAUTION }}` | Montant de la caution |
| `{{ STATUT }}` | Statut du contrat |
| `{{ MODE_SIGNATURE }}` | Mode de signature gerance |

### Pack demarrage

| Variable | Description |
|----------|-------------|
| `{{ PACK_MONTANT_TTC }}` | Montant pack demarrage TTC |
| `{{ PACK_LOYER_TTC }}` | Loyer mensuel pack demarrage TTC |

### Renouvellement

| Variable | Description |
|----------|-------------|
| `{{ TYPE_RENOUVELLEMENT }}` | Type de renouvellement |
| `{{ RENOUV_TVA_POURCENT }}` | TVA renouvellement % |
| `{{ RENOUV_LOYER_HT }}` | Loyer mensuel HT renouvellement |
| `{{ RENOUV_LOYER_TTC }}` | Loyer mensuel TTC renouvellement |
| `{{ RENOUV_ANNUEL_TTC }}` | Loyer annuel TTC renouvellement |

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
- Les accolades ouvrantes `{{` doivent TOUJOURS etre collees au contenu dans le document Word (pas d'espace apres `{{` ni avant `}}`).

---

## Anciens noms (encore fonctionnels, deconseilles)

Ces alias fonctionnent toujours mais ne sont plus documentes :

| Ancien nom | Remplacer par |
|---|---|
| `{{ CAPITAL_SOCIAL }}` | `{{ CAPITAL }}` |
| `{{ NUMERO_ICE }}` | `{{ ICE }}` |
| `{{ DENOMINATION_SOCIALE }}` / `{{ DEN_STE }}` / `{{ name }}` | `{{ RAISON_SOCIALE }}` |
| `{{ FORME_JUR }}` | `{{ FORME_JURIDIQUE }}` |
| `{{ ADRESSE_SIEGE_SOCIAL }}` / `{{ STE_ADRESS }}` / `{{ adresse }}` | `{{ ADRESSE_SIEGE }}` |
| `{{ NUMERO_CIN_ASSOCIE }}` / `{{ CIN_NUM }}` | `{{ CIN }}` |
| `{{ DATE_VALIDITE_CIN_ASSOCIE }}` | `{{ CIN_VALIDITE }}` |
| `{{ DATE_NAISSANCE_ASSOCIE }}` / `{{ DATE_NAISS }}` | `{{ DATE_NAISSANCE }}` |
| `{{ LIEU_NAISSANCE_ASSOCIE }}` / `{{ LIEU_NAISS }}` | `{{ LIEU_NAISSANCE }}` |
| `{{ NATIONALITE_ASSOCIE }}` / `{{ NATIONALITY }}` | `{{ NATIONALITE }}` |
| `{{ NOMBRE_PARTS }}` / `{{ NOMBRE_PARTS_ASSOCIE }}` | `{{ PARTS }}` |
| `{{ NOMBRE_PARTS_SOCIALES }}` | `{{ PART_SOCIAL }}` |
| `{{ CAPITAL_DETENU_ASSOCIE }}` | `{{ CAPITAL_DETENU }}` |
| `{{ EST_GERANT }}` / `{{ IS_GERANT }}` | `{{ EST_GERANT }}` |
| `{{ QUALITE_ASSOCIE }}` / `{{ QUALITY }}` | `{{ QUALITE }}` |
| `{{ ADRESSE_ASSOCIE }}` | `{{ ADRESSE }}` |
| `{{ TELEPHONE_ASSOCIE }}` / `{{ PHONE }}` | `{{ TELEPHONE }}` |
| `{{ EMAIL_ASSOCIE }}` | `{{ EMAIL }}` |
| `{{ TRIBUNAL_COMPETENT }}` | `{{ TRIBUNAL }}` |
| `{{ DOSSIER_DOMICILIATION }}` | `{{ DOSSIER }}` |
| `{{ TYPE_CONTRAT_DOMICILIATION }}` | `{{ TYPE_DOMICILIATION }}` |
| `{{ DATE_DEBUT_CONTRAT }}` / `{{ DOM_DATEDEB }}` | `{{ DATE_DEBUT }}` |
| `{{ DATE_FIN_CONTRAT }}` / `{{ DOM_DATEFIN }}` | `{{ DATE_FIN }}` |
| `{{ DUREE_CONTRAT_MOIS }}` / `{{ PERIOD_DOMCIL }}` | `{{ DUREE_MOIS }}` |
| `{{ LOYER_MENSUEL_TTC }}` / `{{ PRIX_CONTRAT }}` | `{{ LOYER_TTC }}` |
| `{{ LOYER_MENSUEL_HT }}` / `{{ DH_HT }}` | `{{ LOYER_HT }}` |
| `{{ TAUX_TVA_POURCENT }}` / `{{ TVA }}` | `{{ TVA_POURCENT }}` |
| `{{ MONTANT_TOTAL_HT_CONTRAT }}` / `{{ MONTANT_HT }}` | `{{ TOTAL_HT }}` |
| `{{ FRAIS_INTERMEDIAIRE_CONTRAT }}` / `{{ PRIX_INTERMEDIARE_CONTRAT }}` | `{{ FRAIS_INTERMEDIAIRE }}` |
| `{{ MONTANT_PACK_DEMARRAGE_TTC }}` / `{{ PACK_DEMARRAGE_MONTANT_TTC }}` | `{{ PACK_MONTANT_TTC }}` |
| `{{ LOYER_MENSUEL_PACK_DEMARRAGE_TTC }}` / `{{ PACK_DEMARRAGE_LOYER_MENSUEL_TTC }}` | `{{ PACK_LOYER_TTC }}` |
| `{{ TAUX_TVA_RENOUVELLEMENT_POURCENT }}` | `{{ RENOUV_TVA_POURCENT }}` |
| `{{ LOYER_MENSUEL_HT_RENOUVELLEMENT }}` | `{{ RENOUV_LOYER_HT }}` |
| `{{ LOYER_MENSUEL_RENOUVELLEMENT_TTC }}` | `{{ RENOUV_LOYER_TTC }}` |
| `{{ LOYER_ANNUEL_RENOUVELLEMENT_TTC }}` | `{{ RENOUV_ANNUEL_TTC }}` |
| `{{ MODE_SIGNATURE_GERANCE }}` | `{{ MODE_SIGNATURE }}` |
| `{{ PROCEDURE_CREATION }}` | `{{ PROCEDURE_CREATION }}` |
| `{{ MODE_DEPOT_CREATION }}` | `{{ MODE_DEPOT }}` |
| `{{ DATE_IMMATRICULATION_ICE }}` | `{{ DATE_ICE }}` |
| `{{ date_expiration_certificat_negatif }}` | `{{ DATE_EXP_CERT_NEG }}` |
| `{{ ACTIVITIES }}` / `{{ ACTIVITIES_LIST }}` / `{{ LISTE_ACTIVITES }}` | `{{ ACTIVITES }}` |
| `{{ ACTIVITIES_INLINE }}` | `{{ ACTIVITES_INLINE }}` |
| `{{ ACTIVITIES_PLAIN }}` | `{{ ACTIVITES_PLAIN }}` |
| `{{ ACTIVITIES_BULLETS }}` / `{{ ACTIVITES_PUCES }}` | `{{ ACTIVITES_PUCES }}` |
| `{{ ACTIVITIES_CONTINUATION_BULLETS }}` | `{{ ACTIVITES_SUITE_PUCES }}` |
| `{{ ACTIVITY_COUNT }}` | `{{ NB_ACTIVITES }}` |
| `{{ activites_ompic }}` / `{{ ACTIVITES_CERT_NEG }}` / `{{ ACTIVITES_CERTIFICAT_NEGATIF }}` | `{{ ACTIVITES_OMPIC }}` |
| `{{ ACTIVITES_CERT_NEG_INLINE }}` | `{{ OMPIC_INLINE }}` |
| `{{ ACTIVITES_CERT_NEG_BULLETS }}` | `{{ OMPIC_PUCES }}` |
| `{{ CERT_NEG_COUNT }}` | `{{ NB_OMPIC }}` |
| `{{ QUALITE_GERANT }}` | `{{ QUALITE }}` |
| `{{ CIN_VALIDATY }}` / `{{ DATE_VALIDITE_CIN_ASSOCIE }}` | `{{ CIN_VALIDITE }}` |
| `{{ DTAE_CONTRAT }}` | `{{ DATE_CONTRAT }}` |
