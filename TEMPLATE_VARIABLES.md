# Variables disponibles pour les templates Word

Utilisez `{{ }}}}` dans vos documents Word pour insérer une variable.

---

## Societe

| Variable | Description |
|----------|-------------|
| `{{ RAISON_SOCIALE }}` | Raison sociale |
| `{{ DENOMINATION_SOCIALE }}` | Denomination sociale |
| `{{ DEN_STE }}` | Denomination interne |
| `{{ name }}` | Raison sociale |
| `{{ FORME_JURIDIQUE }}` | Forme juridique |
| `{{ FORME_JUR }}` | Forme juridique (abrege) |
| `{{ ICE }}` | ICE |
| `{{ NUMERO_ICE }}` | Numero ICE |
| `{{ RC }}` | RC |
| `{{ IF }}` | IF |
| `{{ CAPITAL }}` | Capital social |
| `{{ CAPITAL_SOCIAL }}` | Capital social |
| `{{ PART_SOCIAL }}` | Nombre de parts sociales |
| `{{ NOMBRE_PARTS_SOCIALES }}` | Nombre de parts sociales |
| `{{ VALEUR_NOMINALE }}` | Valeur nominale |
| `{{ VILLE }}` | Ville |
| `{{ TRIBUNAL }}` | Tribunal |
| `{{ TRIBUNAL_COMPETENT }}` | Tribunal competent |
| `{{ ADRESSE_SIEGE_SOCIAL }}` | Adresse du siege social |
| `{{ STE_ADRESS }}` | Adresse (court) |
| `{{ adresse }}` | Adresse |
| `{{ EMAIL }}` | Email |
| `{{ TELEPHONE }}` | Telephone |
| `{{ DOSSIER_DOMICILIATION }}` | Numero de dossier de domiciliation |
| `{{ TYPE_GENERATION }}` | Type de generation |
| `{{ PROCEDURE_CREATION }}` | Procedure de creation |
| `{{ MODE_DEPOT_CREATION }}` | Mode de depot creation |
| `{{ DATE_ICE }}` | Date d'immatriculation ICE |
| `{{ DATE_IMMATRICULATION_ICE }}` | Date d'immatriculation ICE |
| `{{ DATE_EXP_CERT_NEG }}` | Date expiration certificat negatif |
| `{{ date_expiration_certificat_negatif }}` | Date expiration certificat negatif |

---

## Associe (champs simples)

Utilisables en dehors d'une boucle (premier associe uniquement) :

| Variable | Description |
|----------|-------------|
| `{{ CIVILITE_ASSOCIE }}` | Civilite (M./Mme) |
| `{{ NOM_ASSOCIE }}` | Nom de l'associe |
| `{{ PRENOM_ASSOCIE }}` | Prenom de l'associe |
| `{{ NOM_COMPLET }}` | Nom complet (civilite + prenom + nom) |
| `{{ CIN }}` | CIN |
| `{{ NUMERO_CIN_ASSOCIE }}` | Numero CIN |
| `{{ DATE_VALIDITE_CIN_ASSOCIE }}` | Date validite CIN |
| `{{ DATE_NAISSANCE }}` | Date de naissance |
| `{{ DATE_NAISSANCE_ASSOCIE }}` | Date de naissance associe |
| `{{ LIEU_NAISSANCE }}` | Lieu de naissance |
| `{{ LIEU_NAISSANCE_ASSOCIE }}` | Lieu de naissance associe |
| `{{ NATIONALITE }}` | Nationalite |
| `{{ NATIONALITE_ASSOCIE }}` | Nationalite associe |
| `{{ ADRESSE_ASSOCIE }}` | Adresse de l'associe |
| `{{ TELEPHONE_ASSOCIE }}` | Telephone de l'associe |
| `{{ EMAIL_ASSOCIE }}` | Email de l'associe |
| `{{ QUALITE_ASSOCIE }}` | Qualite de l'associe |
| `{{ QUALITE_GERANT }}` | Qualite du gerant |
| `{{ NOMBRE_PARTS }}` | Nombre de parts |
| `{{ NOMBRE_PARTS_ASSOCIE }}` | Nombre de parts associe |
| `{{ CAPITAL_DETENU_ASSOCIE }}` | Capital detenu par l'associe |
| `{{ EST_GERANT }}` | Est gerant (Oui/Non) |

---

## Associe (boucle `{%p for a in associes %}`)

Pour les documents qui listent plusieurs associes :

```
{%p for a in associes %}
  {{ a.NOM_COMPLET }} - {{ a.QUALITE }} - {{ a.PARTS }} parts
{%p endfor %}
```

| Variable | Description |
|----------|-------------|
| `{{ a.NOM_COMPLET }}` | Nom complet (civilite + prenom + nom) |
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
| `{{ TYPE_CONTRAT_DOMICILIATION }}` | Type de contrat de domiciliation |
| `{{ DATE_CONTRAT }}` | Date du contrat |
| `{{ DTAE_CONTRAT }}` | Date du contrat (alias) |
| `{{ DATE_DEBUT_CONTRAT }}` | Date de debut |
| `{{ DOM_DATEDEB }}` | Date de debut (alias) |
| `{{ DATE_FIN_CONTRAT }}` | Date de fin |
| `{{ DOM_DATEFIN }}` | Date de fin (alias) |
| `{{ DUREE_CONTRAT_MOIS }}` | Duree en mois |
| `{{ PERIOD_DOMCIL }}` | Duree en mois (alias) |
| `{{ LOYER_MENSUEL_TTC }}` | Loyer mensuel TTC |
| `{{ PRIX_CONTRAT }}` | Loyer mensuel TTC (alias) |
| `{{ LOYER_MENSUEL_HT }}` | Loyer mensuel HT |
| `{{ DH_HT }}` | Loyer mensuel HT (alias) |
| `{{ TAUX_TVA_POURCENT }}` | Taux TVA % |
| `{{ TVA }}` | Taux TVA % (alias) |
| `{{ MONTANT_TOTAL_HT_CONTRAT }}` | Montant total HT du contrat |
| `{{ MONTANT_HT }}` | Montant total HT (alias) |
| `{{ FRAIS_INTERMEDIAIRE_CONTRAT }}` | Frais intermediaire |
| `{{ PRIX_INTERMEDIARE_CONTRAT }}` | Frais intermediaire (alias) |
| `{{ MONTANT_PACK_DEMARRAGE_TTC }}` | Montant pack demarrage TTC |
| `{{ PACK_DEMARRAGE_MONTANT_TTC }}` | Montant pack demarrage TTC (alias) |
| `{{ LOYER_MENSUEL_PACK_DEMARRAGE_TTC }}` | Loyer mensuel pack demarrage TTC |
| `{{ PACK_DEMARRAGE_LOYER_MENSUEL_TTC }}` | Loyer mensuel pack demarrage TTC (alias) |
| `{{ TYPE_RENOUVELLEMENT }}` | Type de renouvellement |
| `{{ TAUX_TVA_RENOUVELLEMENT_POURCENT }}` | TVA renouvellement % |
| `{{ LOYER_MENSUEL_HT_RENOUVELLEMENT }}` | Loyer mensuel HT renouvellement |
| `{{ LOYER_MENSUEL_RENOUVELLEMENT_TTC }}` | Loyer mensuel TTC renouvellement |
| `{{ PACK_DEMARRAGE_LOYER_MENSUEL_TTC }}` | Loyer mensuel TTC renouvellement (alias) |
| `{{ LOYER_ANNUEL_RENOUVELLEMENT_TTC }}` | Loyer annuel TTC renouvellement |
| `{{ PACK_DEMARRAGE_LOYER_ANNUEL_TTC }}` | Loyer annuel TTC renouvellement (alias) |
| `{{ CAUTION }}` | Montant de la caution |
| `{{ STATUT }}` | Statut du contrat |
| `{{ MODE_SIGNATURE_GERANCE }}` | Mode de signature gerance |

---

## Activites (Statuts)

| Variable | Description |
|----------|-------------|
| `{{ ACTIVITIES }}` | Liste d'activites (texte) |
| `{{ ACTIVITES }}` | Liste d'activites (texte) |
| `{{ ACTIVITIES_LIST }}` | Liste d'activites (texte) |
| `{{ LISTE_ACTIVITES }}` | Liste d'activites (texte) |
| `{{ ACTIVITIES_INLINE }}` | Activites en ligne (separes par `; `) |
| `{{ ACTIVITES_INLINE }}` | Activites en ligne (separes par `; `) |
| `{{ ACTIVITIES_PLAIN }}` | Activites en ligne (separes par `, `) |
| `{{ ACTIVITES_PLAIN }}` | Activites en ligne (separes par `, `) |
| `{{ ACTIVITIES_BULLETS }}` | Activites en puces (format Word avec tirets) |
| `{{ ACTIVITES_PUCES }}` | Activites en puces (format Word avec tirets) |
| `{{ ACTIVITIES_CONTINUATION_BULLETS }}` | Activites suite puces (sans titre) |
| `{{ ACTIVITES_CONTINUATION_PUCES }}` | Activites suite puces (sans titre) |
| `{{ ACTIVITY_COUNT }}` | Nombre d'activites |

---

## Activites OMPIC

| Variable | Description |
|----------|-------------|
| `{{ activites_ompic }}` | Activites OMPIC (texte) |
| `{{ ACTIVITES_CERT_NEG }}` | Activites OMPIC (texte) |
| `{{ ACTIVITES_CERTIFICAT_NEGATIF }}` | Activites OMPIC (alias) |
| `{{ ACTIVITES_CERT_NEG_INLINE }}` | Activites OMPIC en ligne |
| `{{ ACTIVITES_CERT_NEG_BULLETS }}` | Activites OMPIC en puces |
| `{{ CERT_NEG_COUNT }}` | Nombre d'activites OMPIC |

---

## Dates

| Variable | Description | Exemple |
|----------|-------------|---------|
| `{{ DATE }}` | Date du jour (court) | `15/05/2026` |
| `{{ DATE_LONG }}` | Date du jour (long) | `15 May 2026` |
| `{{ ANNEE }}` | Annee en cours | `2026` |
| `{{ MOIS }}` | Mois en cours | `05` |
| `{{ JOUR }}` | Jour en cours | `15` |

---

## Notes importantes

- Les alias (`{{ CAPITAL }}` = `{{ CAPITAL_SOCIAL }}`) fonctionnent tous. Utilisez l'un ou l'autre.
- Les accolades ouvertes `{{ }}}}` sont les delimiteurs.
- Pour les associes multiples, utilisez la boucle `{%p for a in associes %}` ... `{%p endfor %}`
- Les variables inconnues restent telles quelles dans le document genere.
