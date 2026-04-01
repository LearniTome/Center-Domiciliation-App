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

# Associate roles
QualityAssocie = ["Associé", "Associé Unique"]

# Company activities
Activities = [
    "Travaux Divers ou de Construction",
    "Marchand effectuant Import Export",
    "Négociant",
    "Conseil de Gestion"
]

# Excel sheet headers
societe_headers = [
    "id_societe", "dossier_domiciliation", "den_ste", "forme_jur", "ice", "date_ice", "capital",
    "part_social", "valeur_nominale", "date_exp_cert_neg", "ste_adress", "tribunal",
    "type_generation", "procedure_creation", "mode_depot_creation",
]

associe_headers = [
    "id_associe", "id_societe", "den_ste", "civil", "prenom", "nom",
    "nationality", "cin_num", "cin_validaty", "date_naiss",
    "lieu_naiss", "adresse", "phone", "email",
    "part_percent", "parts", "capital_detenu", "is_gerant", "quality"
]

contrat_headers = [
    "id_contrat", "id_societe", "den_ste", "date_contrat", "duree_contrat_mois",
    "type_contrat_domiciliation", "type_contrat_domiciliation_autre",
    "loyer_mensuel_ttc", "frais_intermediaire_contrat", "date_debut_contrat",
    "date_fin_contrat", "taux_tva_pourcent", "loyer_mensuel_ht", "montant_total_ht_contrat",
    "montant_pack_demarrage_ttc", "loyer_mensuel_pack_demarrage_ttc",
    "type_renouvellement", "taux_tva_renouvellement_pourcent",
    "loyer_mensuel_ht_renouvellement", "montant_total_ht_renouvellement",
    "loyer_mensuel_renouvellement_ttc", "loyer_annuel_renouvellement_ttc"
]

collaborateur_headers = [
    "id_collaborateur", "id_societe",
    "collaborateur_type", "collaborateur_code",
    "collaborateur_nom", "collaborateur_ice", "collaborateur_tp",
    "collaborateur_rc", "collaborateur_if",
    "collaborateur_tel_fixe", "collaborateur_tel_mobile",
    "collaborateur_adresse", "collaborateur_email",
]

# Human-readable header labels for Excel output
excel_header_labels = {
    "den_ste": "Dénomination sociale",
    "DEN_STE": "Dénomination sociale",
    "dossier_domiciliation": "N° dossier domiciliation",
    "DOSSIER_DOMICILIATION": "N° dossier domiciliation",
}

# Compatibility aliases to migrate old contract column names.
contrat_header_aliases = {
    "ID_CONTRAT": "id_contrat",
    "ID_SOCIETE": "id_societe",
    "DEN_STE": "den_ste",
    "Dénomination sociale": "den_ste",
    "DATE_CONTRAT": "date_contrat",
    "DUREE_CONTRAT_MOIS": "duree_contrat_mois",
    "TYPE_CONTRAT_DOMICILIATION": "type_contrat_domiciliation",
    "TYPE_CONTRAT_DOMICILIATION_AUTRE": "type_contrat_domiciliation_autre",
    "LOYER_MENSUEL_TTC": "loyer_mensuel_ttc",
    "FRAIS_INTERMEDIAIRE_CONTRAT": "frais_intermediaire_contrat",
    "DATE_DEBUT_CONTRAT": "date_debut_contrat",
    "DATE_FIN_CONTRAT": "date_fin_contrat",
    "TAUX_TVA_POURCENT": "taux_tva_pourcent",
    "LOYER_MENSUEL_HT": "loyer_mensuel_ht",
    "MONTANT_TOTAL_HT_CONTRAT": "montant_total_ht_contrat",
    "MONTANT_PACK_DEMARRAGE_TTC": "montant_pack_demarrage_ttc",
    "LOYER_MENSUEL_PACK_DEMARRAGE_TTC": "loyer_mensuel_pack_demarrage_ttc",
    "TYPE_RENOUVELLEMENT": "type_renouvellement",
    "TAUX_TVA_RENOUVELLEMENT_POURCENT": "taux_tva_renouvellement_pourcent",
    "LOYER_MENSUEL_HT_RENOUVELLEMENT": "loyer_mensuel_ht_renouvellement",
    "MONTANT_TOTAL_HT_RENOUVELLEMENT": "montant_total_ht_renouvellement",
    "LOYER_MENSUEL_RENOUVELLEMENT_TTC": "loyer_mensuel_renouvellement_ttc",
    "LOYER_ANNUEL_RENOUVELLEMENT_TTC": "loyer_annuel_renouvellement_ttc",
    "PERIOD_DOMCIL": "duree_contrat_mois",
    "PRIX_CONTRAT": "loyer_mensuel_ttc",
    "PRIX_INTERMEDIARE_CONTRAT": "frais_intermediaire_contrat",
    "DOM_DATEDEB": "date_debut_contrat",
    "DOM_DATEFIN": "date_fin_contrat",
    "PACK_DEMARRAGE_MONTANT_TTC": "montant_pack_demarrage_ttc",
    "PACK_DEMARRAGE_LOYER_MENSUEL_TTC": "loyer_mensuel_pack_demarrage_ttc",
    "LOYER_RENOUVELLEMENT_MENSUEL_TTC": "loyer_mensuel_renouvellement_ttc",
    "LOYER_RENOUVELLEMENT_ANNUEL_TTC": "loyer_annuel_renouvellement_ttc",
}

societe_header_aliases = {
    "ID_SOCIETE": "id_societe",
    "DEN_STE": "den_ste",
    "Dénomination sociale": "den_ste",
    "FORME_JUR": "forme_jur",
    "ICE": "ice",
    "DATE_ICE": "date_ice",
    "CAPITAL": "capital",
    "PART_SOCIAL": "part_social",
    "VALEUR_NOMINALE": "valeur_nominale",
    "DATE_EXP_CERT_NEG": "date_exp_cert_neg",
    "STE_ADRESS": "ste_adress",
    "TRIBUNAL": "tribunal",
    "TYPE_GENERATION": "type_generation",
    "PROCEDURE_CREATION": "procedure_creation",
    "MODE_DEPOT_CREATION": "mode_depot_creation",
    "DOSSIER_DOMICILIATION": "dossier_domiciliation",
    "NUM_DOSSIER_DOMICILIATION": "dossier_domiciliation",
    "N° dossier domiciliation": "dossier_domiciliation",
    "N° dossier": "dossier_domiciliation",
    "Numero dossier domiciliation": "dossier_domiciliation",
}

associe_header_aliases = {
    "ID_ASSOCIE": "id_associe",
    "ID_SOCIETE": "id_societe",
    "DEN_STE": "den_ste",
    "Dénomination sociale": "den_ste",
    "CIVIL": "civil",
    "PRENOM": "prenom",
    "NOM": "nom",
    "NATIONALITY": "nationality",
    "CIN_NUM": "cin_num",
    "CIN_VALIDATY": "cin_validaty",
    "DATE_NAISS": "date_naiss",
    "LIEU_NAISS": "lieu_naiss",
    "ADRESSE": "adresse",
    "PHONE": "phone",
    "EMAIL": "email",
    "PART_PERCENT": "part_percent",
    "PARTS": "parts",
    "CAPITAL_DETENU": "capital_detenu",
    "IS_GERANT": "is_gerant",
    "QUALITY": "quality",
}

collaborateur_header_aliases = {
    "ID_COLLABORATEUR": "id_collaborateur",
    "ID_SOCIETE": "id_societe",
    "COLLABORATEUR_TYPE": "collaborateur_type",
    "COLLABORATEUR_CODE": "collaborateur_code",
    "COLLABORATEUR_NOM": "collaborateur_nom",
    "COLLABORATEUR_ICE": "collaborateur_ice",
    "COLLABORATEUR_TP": "collaborateur_tp",
    "COLLABORATEUR_RC": "collaborateur_rc",
    "COLLABORATEUR_IF": "collaborateur_if",
    "COLLABORATEUR_TEL_FIXE": "collaborateur_tel_fixe",
    "COLLABORATEUR_TEL_MOBILE": "collaborateur_tel_mobile",
    "COLLABORATEUR_ADRESSE": "collaborateur_adresse",
    "COLLABORATEUR_EMAIL": "collaborateur_email",
}

# Reference sheets for dropdown lists and lookups
ste_adresses_headers = ["ste_adresse"]
tribunaux_headers = ["tribunal"]
activites_headers = ["activite"]
nationalites_headers = ["nationalite"]
lieux_naissance_headers = ["lieu_naissance"]

reference_header_aliases = {
    "SteAdresses": {"STE_ADRESSE": "ste_adresse", "STE_ADRESS": "ste_adresse"},
    "Tribunaux": {"TRIBUNAL": "tribunal"},
    "Activites": {"ACTIVITE": "activite"},
    "Nationalites": {"NATIONALITE": "nationalite"},
    "LieuxNaissance": {"LIEU_NAISSANCE": "lieu_naissance", "LIEU_NAISS": "lieu_naissance"},
}

# Dictionary for Excel sheets (includes both data sheets and reference sheets)
excel_sheets = {
    "Societes": societe_headers,
    "Associes": associe_headers,
    "Contrats": contrat_headers,
    "Collaborateurs": collaborateur_headers,
    "SteAdresses": ste_adresses_headers,
    "Tribunaux": tribunaux_headers,
    "Activites": activites_headers,
    "Nationalites": nationalites_headers,
    "LieuxNaissance": lieux_naissance_headers
}

# Default database filename used across the app
DB_FILENAME = "DataBase_domiciliation.xlsx"
