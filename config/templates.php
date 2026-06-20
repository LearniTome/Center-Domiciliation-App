<?php

declare(strict_types=1);

return [
    'folder_labels' => [
        '_Racine-Actifs' => 'Generique (toutes formes)',
        'SARL AU' => 'SARL-AU',
        'SARL' => 'SARL',
        'SA' => 'SA',
        '_Cession' => 'Cession (generique)',
        '_Cession_SARL' => 'Cession SARL',
        '_Cession_SARLAU' => 'Cession SARL AU',
    ],

    'document_types' => [
        'Annonce-Legale-Journal' => 'Annonce legale journal',
        'Attestation-Domiciliation-Initiale' => 'Attestation domiciliation initiale',
        'Contrat-Domiciliation' => 'Contrat de domiciliation',
        'Declaration-Immatriculation-RC' => 'Declaration immatriculation RC',
        'Depot-Legal-Constitution' => 'Depot legal constitution',
        'Statuts' => 'Statuts',
        'Acte-Cession-Parts' => 'Acte de cession de parts',
        'PV-AGE-Cession' => "PV d'assemblee generale cession",
        'Declaration-Modificative-RC' => 'Declaration modificative RC',
        'Annonce-Legale-Cession' => 'Annonce legale cession',
    ],

    'generation_types' => [
        'creation' => 'Creation',
        'domiciliation' => 'Domiciliation',
        'cession' => 'Cession de parts',
    ],

    'template_mapping' => [
        'creation' => ['Statuts', 'Annonce-Legale-Journal', 'Depot-Legal-Constitution', 'Declaration-Immatriculation-RC', 'Attestation-Domiciliation-Initiale', 'Contrat-Domiciliation'],
        'domiciliation' => ['Contrat-Domiciliation', 'Attestation-Domiciliation-Initiale'],
        'cession' => ['Acte-Cession-Parts', 'PV-AGE-Cession', 'Declaration-Modificative-RC', 'Annonce-Legale-Cession'],
    ],
];
