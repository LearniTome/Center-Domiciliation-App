# List of constants for the domiciliation app

# Company names
DenSte = ["ASTRAPIA", "SAOUZ", "F 4", "OLA MOVING", "LOHACOM", "SKY NEST", "SKY MA", "MAROFLEET"]

# Person titles
Civility = ["Monsieur", "Madame"]

# Company legal forms
Formjur = ["SARL AU", "SARL", "SA"]

# Contract durations in months
Nbmois = ["06", "12", "15", "24"]

# Company capital options
Capital = ["10 000", "50 000", "100 000"]

# Social parts options
PartsSocial = ["100", "200", "500", "1000"]

# Nationalities
Nationalite = ["Marocaine", "Cameronnie"]

# Company addresses
SteAdresse = [
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
    "PART_SOCIAL", "STE_ADRESS", "TRIBUNAL"
]

associe_headers = [
    "ID_ASSOCIE", "ID_SOCIETE", "CIVIL", "PRENOM", "NOM",
    "NATIONALITY", "CIN_NUM", "CIN_VALIDATY", "DATE_NAISS",
    "LIEU_NAISS", "ADRESSE", "PHONE", "EMAIL",
    "PARTS", "IS_GERANT", "QUALITY"
]

contrat_headers = [
    "ID_CONTRAT", "ID_SOCIETE", "DATE_CONTRAT", "PERIOD_DOMCIL",
    "PRIX_CONTRAT", "PRIX_INTERMEDIARE_CONTRAT", "DOM_DATEDEB",
    "DOM_DATEFIN"
]

# Dictionary for Excel sheets
excel_sheets = {
    "Societes": societe_headers,
    "Associes": associe_headers,
    "Contrats": contrat_headers
}
