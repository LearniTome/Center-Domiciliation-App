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
];
