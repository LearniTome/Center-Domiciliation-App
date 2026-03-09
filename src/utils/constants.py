# List of constants for the domiciliation app

# Company names
DenSte = ["ASTRAPIA", "SAOUZ", "F 4", "OLA MOVING", "LOHACOM", "SKY NEST", "SKY MA", "MAROFLEET"]

# Person titles
Civility = ["Monsieur", "Madame"]

# Company legal forms
Formjur = ["SARL AU", "SARL", "Personne Physique", "SA"]

# Contract durations in months
Nbmois = ["06", "12", "15", "24"]

# Renewal types
TypeRenouvellement = ["Mensuel", "Trimestriel", "Annuel", "2 ans", "3 ans", "4 ans", "5 ans"]

# Contract domiciliation types
TypeContratDomiciliation = [
    "Personne Morale",
    "Personne Physique",
    "Association",
    "Fondation",
    "Autres",
]

# Collaborateurs (code + libelle) used for domiciliation folder naming
Collaborateurs = [
    "EXP -- Expert Comptable",
    "AGR -- Comptable Agréé",
    "IND -- Comptable Indépendant",
    "COAG -- Coursier Comptable Agréé",
    "COIND -- Coursier Indépendant",
    "COEXP -- Coursier Expert Comptable",
    "CLTD -- Client Direct",
]

# Company capital options
Capital = ["10 000", "50 000", "100 000"]

# Social parts options
PartsSocial = ["100", "200", "500", "1000"]

# Nationalities
Nationalite = ["Marocaine", "Cameronnie"]

# Company addresses
SteAdresse = [
    "HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA",
    "46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA",
    "56  BOULEVARD MOULAY YOUSSEF 3EME ETAGE APPT 14, CASABLANCA",
    "96 BD D'ANFA ETAGE 9 APPT N° 91 RESIDENCE LE PRINTEMPS D'ANFA",
    "61 Av Lalla Yacout Angle Mustapha El Maani 2eme Etage N°62 Centre Riad",
    "Bd Zerktouni Et Angle Rue Ibn Al Moualim N° 4, Etage 2, Appt N°10"
]

# Court locations
Tribunnaux = ["Casablanca", "Berrechid", "Mohammedia"]

# Manager roles
QualityGerant = ["Associé Gérant", "Associé Unique Gérant", "Associé"]

# Company activities
Activities = [
    "Travaux Divers ou de Construction",
    "Marchand effectuant Import Export",
    "Négociant",
    "Conseil de Gestion"
]

# Excel sheet headers
societe_headers = [
    "ID_SOCIETE", "DEN_STE", "FORME_JUR", "ICE", "DATE_ICE", "CAPITAL",
    "PART_SOCIAL", "VALEUR_NOMINALE", "DATE_EXP_CERT_NEG", "STE_ADRESS", "TRIBUNAL",
    "TYPE_GENERATION", "PROCEDURE_CREATION", "MODE_DEPOT_CREATION"
]

associe_headers = [
    "ID_ASSOCIE", "ID_SOCIETE", "CIVIL", "PRENOM", "NOM",
    "NATIONALITY", "CIN_NUM", "CIN_VALIDATY", "DATE_NAISS",
    "LIEU_NAISS", "ADRESSE", "PHONE", "EMAIL",
    "PART_PERCENT", "PARTS", "CAPITAL_DETENU", "IS_GERANT", "QUALITY"
]

contrat_headers = [
    "ID_CONTRAT", "ID_SOCIETE", "DATE_CONTRAT", "DUREE_CONTRAT_MOIS",
    "TYPE_CONTRAT_DOMICILIATION", "TYPE_CONTRAT_DOMICILIATION_AUTRE",
    "LOYER_MENSUEL_TTC", "FRAIS_INTERMEDIAIRE_CONTRAT", "DATE_DEBUT_CONTRAT",
    "DATE_FIN_CONTRAT", "TAUX_TVA_POURCENT", "LOYER_MENSUEL_HT", "MONTANT_TOTAL_HT_CONTRAT",
    "MONTANT_PACK_DEMARRAGE_TTC", "LOYER_MENSUEL_PACK_DEMARRAGE_TTC",
    "TYPE_RENOUVELLEMENT", "TAUX_TVA_RENOUVELLEMENT_POURCENT",
    "LOYER_MENSUEL_HT_RENOUVELLEMENT", "MONTANT_TOTAL_HT_RENOUVELLEMENT",
    "LOYER_MENSUEL_RENOUVELLEMENT_TTC", "LOYER_ANNUEL_RENOUVELLEMENT_TTC"
]

# Compatibility aliases to migrate old contract column names.
contrat_header_aliases = {
    "PERIOD_DOMCIL": "DUREE_CONTRAT_MOIS",
    "PRIX_CONTRAT": "LOYER_MENSUEL_TTC",
    "PRIX_INTERMEDIARE_CONTRAT": "FRAIS_INTERMEDIAIRE_CONTRAT",
    "DOM_DATEDEB": "DATE_DEBUT_CONTRAT",
    "DOM_DATEFIN": "DATE_FIN_CONTRAT",
    "PACK_DEMARRAGE_MONTANT_TTC": "MONTANT_PACK_DEMARRAGE_TTC",
    "PACK_DEMARRAGE_LOYER_MENSUEL_TTC": "LOYER_MENSUEL_PACK_DEMARRAGE_TTC",
    "LOYER_RENOUVELLEMENT_MENSUEL_TTC": "LOYER_MENSUEL_RENOUVELLEMENT_TTC",
    "LOYER_RENOUVELLEMENT_ANNUEL_TTC": "LOYER_ANNUEL_RENOUVELLEMENT_TTC",
}

# Reference sheets for dropdown lists and lookups
ste_adresses_headers = ["STE_ADRESSE"]
tribunaux_headers = ["TRIBUNAL"]
activites_headers = ["ACTIVITE"]
nationalites_headers = ["NATIONALITE"]
lieux_naissance_headers = ["LIEU_NAISSANCE"]

# Dictionary for Excel sheets (includes both data sheets and reference sheets)
excel_sheets = {
    "Societes": societe_headers,
    "Associes": associe_headers,
    "Contrats": contrat_headers,
    "SteAdresses": ste_adresses_headers,
    "Tribunaux": tribunaux_headers,
    "Activites": activites_headers,
    "Nationalites": nationalites_headers,
    "LieuxNaissance": lieux_naissance_headers
}

# Default database filename used across the app
DB_FILENAME = "DataBase_domiciliation.xlsx"
