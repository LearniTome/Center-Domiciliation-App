<?php

declare(strict_types=1);

return [
    'folder_labels' => [
        '_Racine-Actifs' => 'Generique (toutes formes)',
        'SARL AU' => 'SARL-AU',
        'SARL' => 'SARL',
        'SA' => 'SA',
        '_Cession_SARL' => 'Cession SARL',
        '_Cession_SARLAU' => 'Cession SARL AU',
        '_PV_AGO' => 'PV Assemblee Generale Ordinaire',
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
        'PV-AGO' => "PV d'assemblee generale ordinaire annuelle",
    ],

    'generation_types' => [
        'creation' => 'Creation',
        'domiciliation' => 'Domiciliation',
        'cession' => 'Cession de parts',
        'pv_ago' => 'PV Assemblee Generale Ordinaire',
    ],

    'template_mapping' => [
        'creation' => ['Statuts', 'Annonce-Legale-Journal', 'Depot-Legal-Constitution', 'Declaration-Immatriculation-RC', 'Attestation-Domiciliation-Initiale', 'Contrat-Domiciliation'],
        'domiciliation' => ['Contrat-Domiciliation', 'Attestation-Domiciliation-Initiale'],
        'cession' => ['Acte-Cession-Parts', 'PV-AGE-Cession', 'Declaration-Modificative-RC', 'Annonce-Legale-Cession'],
        'pv_ago' => ['PV-AGO'],
    ],

    // Motifs de matching tolerants (prefixe, insensible a la casse/ponctuation/variantes "_Template v2")
    'template_matching_patterns' => [
        'creation' => ['Statuts', 'Annonce-Legale-Journal', 'Depot-Legal-Constitution', 'Declaration-Immatriculation-RC', 'Attestation-Domiciliation', 'Contrat-Domiciliation'],
        'domiciliation' => ['Contrat-Domiciliation', 'Attestation-Domiciliation'],
        'cession' => ['Acte-Cession-Parts', 'PV-AGE-Cession', 'Declaration-Modificative-RC', 'Annonce-Legale-Cession'],
        'pv_ago' => ['PV-AGO'],
    ],

    // Sections de la page Gestionnaire : regroupement visuel des dossiers de templates.
    // 'folders' = liste explicite ; 'match' = regex sur les dossiers physiques non encore classes ;
    // les formes juridiques (ref_formes_juridiques) rejoignent automatiquement la section 'creation'.
    'template_sections' => [
        [
            'key' => 'creation',
            'label' => 'Creation & Domiciliation',
            'description' => 'Constitution de societes : statuts, annonces legales, contrat et attestation de domiciliation.',
            'icon' => 'domain_add',
            'folders' => ['_Racine-Actifs'],
            'match' => [],
        ],
        [
            'key' => 'modification',
            'label' => 'Modification juridique',
            'description' => 'Cession de parts sociales et autres modifications (dossiers par forme juridique).',
            'icon' => 'edit_document',
            'folders' => ['_Cession_SARL', '_Cession_SARLAU'],
            'match' => ['/^_Cession_/i', '/^_Modif_/i'],
        ],
        [
            'key' => 'juridique',
            'label' => 'Actes juridiques',
            'description' => "Actes recurrents independants du dossier : PV d'assemblees (AGO, AGE...).",
            'icon' => 'gavel',
            'folders' => ['_PV_AGO'],
            'match' => ['/^_PV_/i', '/^_Juridique/i'],
        ],
    ],
];
