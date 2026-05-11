<?php

declare(strict_types=1);

return [
    'legal_forms' => [
        '_Racine-Actifs' => 'Generique (toutes formes)',
        'SARL AU' => 'SARL-AU',
        'SARL' => 'SARL',
        'SA' => 'SA',
    ],

    'document_types' => [
        'Annonce-Legale-Journal' => 'Annonce legale journal',
        'Attestation-Domiciliation-Initiale' => 'Attestation domiciliation initiale',
        'Contrat-Domiciliation' => 'Contrat de domiciliation',
        'Declaration-Immatriculation-RC' => 'Declaration immatriculation RC',
        'Depot-Legal-Constitution' => 'Depot legal constitution',
        'Statuts' => 'Statuts',
    ],

    'generation_types' => [
        'creation' => 'Creation',
        'domiciliation' => 'Domiciliation',
    ],

    'template_mapping' => [
        'creation' => ['Statuts', 'Annonce-Legale-Journal', 'Depot-Legal-Constitution', 'Declaration-Immatriculation-RC'],
        'domiciliation' => ['Contrat-Domiciliation', 'Attestation-Domiciliation-Initiale'],
    ],

    'variable_aliases' => [
        'DEN_STE' => 'DENOMINATION_SOCIALE',
        'FORME_JUR' => 'FORME_JURIDIQUE',
        'ICE' => 'NUMERO_ICE',
        'CAPITAL' => 'CAPITAL_SOCIAL',
        'PART_SOCIAL' => 'NOMBRE_PARTS_SOCIALES',
        'STE_ADRESS' => 'ADRESSE_SIEGE_SOCIAL',
        'TRIBUNAL' => 'TRIBUNAL_COMPETENT',
        'CIVIL' => 'CIVILITE_ASSOCIE',
        'PRENOM' => 'PRENOM_ASSOCIE',
        'NOM' => 'NOM_ASSOCIE',
        'NATIONALITY' => 'NATIONALITE_ASSOCIE',
        'CIN_NUM' => 'NUMERO_CIN_ASSOCIE',
        'DATE_NAISS' => 'DATE_NAISSANCE_ASSOCIE',
        'LIEU_NAISS' => 'LIEU_NAISSANCE_ASSOCIE',
        'ADRESSE' => 'ADRESSE_ASSOCIE',
        'PHONE' => 'TELEPHONE_ASSOCIE',
        'EMAIL' => 'EMAIL_ASSOCIE',
        'PARTS' => 'NOMBRE_PARTS_ASSOCIE',
        'IS_GERANT' => 'EST_GERANT',
        'QUALITY' => 'QUALITE_ASSOCIE',
        'PERIOD_DOMCIL' => 'DUREE_CONTRAT_MOIS',
        'PRIX_CONTRAT' => 'LOYER_MENSUEL_TTC',
        'DOM_DATEDEB' => 'DATE_DEBUT_CONTRAT',
        'DOM_DATEFIN' => 'DATE_FIN_CONTRAT',
    ],
];
