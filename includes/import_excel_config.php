<?php

declare(strict_types=1);

/**
 * Configuration unique de l'import Excel.
 *
 * Source de verite partagee par :
 *   - api.php                     (actions import_preview / import_confirm)
 *   - pages/outils/import-modele.php (telechargement du modele .xlsx)
 *
 * Les entetes Excel sont les cles de `columnMap` : le modele genere doit les
 * reprendre a l'identique, l'import rejette les colonnes attendues manquantes.
 */

/**
 * Colonnes importables par table, en-tete Excel => colonne DB.
 *
 * @return array<string, array{columnMap: array<string,string>, defaults: array<string,string>}>
 */
function import_excel_tables(): array
{
    return [
        'societes' => [
            'columnMap' => [
                'Raison sociale' => 'societe_raison_sociale',
                'Dossier domiciliation' => 'societe_dossier_domiciliation_number',
                'Dossier creation' => 'societe_dossier_creation_number',
                'Forme juridique' => 'societe_forme_juridique',
                'ICE' => 'societe_ice',
                'RC' => 'societe_rc',
                'IF' => 'societe_if',
                'Ville' => 'societe_ville',
                'Email' => 'societe_email',
                'Telephone' => 'societe_telephone',
                'Capital' => 'societe_capital',
            ],
            'defaults' => ['societe_source' => 'import'],
        ],
        'associes' => [
            'columnMap' => [
                'Societe ID' => 'societe_id',
                'Nom complet' => 'associe_nom_complet',
                'CIN' => 'associe_cin',
                'Date naissance' => 'associe_date_naissance',
                'Lieu naissance' => 'associe_lieu_naissance',
                'Nationalite' => 'associete_nationalite',
                'Telephone' => 'associe_telephone',
                'Email' => 'associe_email',
                'Qualite' => 'associe_qualite',
                'Parts' => 'associe_parts',
            ],
            'defaults' => [],
        ],
        'contrats' => [
            'columnMap' => [
                'Societe ID' => 'societe_id',
                'Type contrat' => 'contrat_type',
                'Date contrat' => 'contrat_date',
                'Duree (mois)' => 'contrat_duree_mois',
                'Date debut' => 'contrat_date_debut',
                'Date fin' => 'contrat_date_fin',
                'Loyer TTC/mois' => 'contrat_loyer_ttc',
                'Statut' => 'contrat_statut',
            ],
            'defaults' => [],
        ],
        'collaborateurs' => [
            'columnMap' => [
                'Nom complet' => 'nom_complet',
                'Fonction' => 'fonction',
                'Type' => 'collaborateur_type',
                'Code' => 'collaborateur_code',
                'ICE' => 'collaborateur_ice',
                'Telephone' => 'telephone',
                'Email' => 'email',
                'Statut' => 'statut',
            ],
            'defaults' => [],
        ],
        'cessions' => [
            'columnMap' => [
                'Dossier' => 'cession_dossier',
                'Societe' => 'societe_id',
                'Date' => 'cession_date',
                'Statut' => 'cession_status',
            ],
            'defaults' => [],
        ],
    ];
}

/**
 * Configuration d'une seule table, ou null si la table n'est pas importable.
 *
 * @return array{columnMap: array<string,string>, defaults: array<string,string>}|null
 */
function import_excel_table(string $table): ?array
{
    return import_excel_tables()[$table] ?? null;
}

/**
 * Libelle lisible d'une table importable (affiche dans le modele Excel).
 */
function import_excel_table_label(string $table): string
{
    return [
        'societes' => 'Sociétés',
        'associes' => 'Associés',
        'contrats' => 'Contrats',
        'collaborateurs' => 'Collaborateurs',
        'cessions' => 'Cessions',
    ][$table] ?? $table;
}

/**
 * Notices du modele : description de chaque colonne + valeur d'exemple.
 *
 * La valeur d'exemple alimente a la fois la ligne d'exemple de l'onglet
 * « Données » et la colonne « Exemple » de l'onglet « Consignes ».
 *
 * @return array<string, array<int, array{desc: string, ex: string}>>
 */
function import_excel_column_notices(): array
{
    return [
        'societes' => [
            'Raison sociale' => ['desc' => 'Dénomination sociale complète de la société.', 'ex' => 'EXEMPLE - ATLAS CONSULTING'],
            'Dossier domiciliation' => ['desc' => 'Laissez vide : le numéro est généré automatiquement (DOM-AAAA-NNN).', 'ex' => ''],
            'Dossier creation' => ['desc' => 'Laissez vide : le numéro est généré automatiquement (CRE-AAAA-NNN).', 'ex' => ''],
            'Forme juridique' => ['desc' => 'Libellé exact du référentiel Formes juridiques.', 'ex' => 'SARL AU'],
            'ICE' => ['desc' => 'Identifiant Commun de l\'Entreprise, 15 chiffres.', 'ex' => '002345678000067'],
            'RC' => ['desc' => 'Registre de commerce.', 'ex' => 'RC 12345'],
            'IF' => ['desc' => 'Identifiant Fiscal, 8 ou 9 chiffres.', 'ex' => '40521398'],
            'Ville' => ['desc' => 'Libellé de la ville (référentiel Villes).', 'ex' => 'Casablanca'],
            'Email' => ['desc' => 'Adresse de contact.', 'ex' => 'contact@exemple.ma'],
            'Telephone' => ['desc' => 'Format libre.', 'ex' => '+212 522 00 00 00'],
            'Capital' => ['desc' => 'Montant du capital, chiffres (séparateur décimal accepté).', 'ex' => '500000'],
        ],
        'associes' => [
            'Societe ID' => ['desc' => 'Identifiant numérique de la société (colonne ID de la liste Sociétés). Rattache l\'associé à une société.', 'ex' => '1'],
            'Nom complet' => ['desc' => 'Nom et prénom de l\'associé.', 'ex' => 'EXEMPLE - Amrani Salma'],
            'CIN' => ['desc' => 'Numéro de carte d\'identité.', 'ex' => 'AB123456'],
            'Date naissance' => ['desc' => 'Date de naissance (JJ/MM/AAAA ou AAAA-MM-JJ).', 'ex' => '12/05/1988'],
            'Lieu naissance' => ['desc' => 'Ville de naissance.', 'ex' => 'Rabat'],
            'Nationalite' => ['desc' => 'Nationalité de l\'associé.', 'ex' => 'Marocaine'],
            'Telephone' => ['desc' => 'Format libre.', 'ex' => '+212 661 00 00 00'],
            'Email' => ['desc' => 'Adresse email de l\'associé.', 'ex' => 's.amrani@exemple.ma'],
            'Qualite' => ['desc' => 'Gérant, Associé, Gérant unique...', 'ex' => 'Gérant'],
            'Parts' => ['desc' => 'Nombre de parts sociales (entier).', 'ex' => '500'],
        ],
        'contrats' => [
            'Societe ID' => ['desc' => 'Identifiant numérique de la société (colonne ID de la liste Sociétés).', 'ex' => '1'],
            'Type contrat' => ['desc' => 'Libellé du type de contrat.', 'ex' => 'Domiciliation'],
            'Date contrat' => ['desc' => 'Date de signature du contrat (JJ/MM/AAAA ou AAAA-MM-JJ).', 'ex' => '15/01/2026'],
            'Duree (mois)' => ['desc' => 'Durée en mois (entier).', 'ex' => '12'],
            'Date debut' => ['desc' => 'Date de début de validité.', 'ex' => '01/02/2026'],
            'Date fin' => ['desc' => 'Date de fin de validité.', 'ex' => '31/01/2027'],
            'Loyer TTC/mois' => ['desc' => 'Montant mensuel TTC, chiffres.', 'ex' => '1500'],
            'Statut' => ['desc' => 'Actif, Suspendu, Résilié...', 'ex' => 'actif'],
        ],
        'collaborateurs' => [
            'Nom complet' => ['desc' => 'Nom et prénom du collaborateur.', 'ex' => 'EXEMPLE - Amrani Salma'],
            'Fonction' => ['desc' => 'Libellé de la fonction (référentiel Fonctions).', 'ex' => 'Assistance comptable'],
            'Type' => ['desc' => 'Collaborateur interne (interne) ou externe (externe / externe-pm).', 'ex' => 'interne'],
            'Code' => ['desc' => 'Laissez vide : le code est généré automatiquement.', 'ex' => ''],
            'ICE' => ['desc' => 'ICE du cabinet ou de la société du collaborateur.', 'ex' => '002345678000067'],
            'Telephone' => ['desc' => 'Format libre.', 'ex' => '+212 661 00 00 00'],
            'Email' => ['desc' => 'Adresse email professionnelle.', 'ex' => 's.amrani@exemple.ma'],
            'Statut' => ['desc' => 'Actif, Inactif, Suspendu...', 'ex' => 'actif'],
        ],
        'cessions' => [
            'Dossier' => ['desc' => 'Numéro de dossier de cession. Laissez vide : il est généré automatiquement (CES-AAAA-NNN).', 'ex' => ''],
            'Societe' => ['desc' => 'Identifiant numérique de la société concernée.', 'ex' => '1'],
            'Date' => ['desc' => 'Date de la cession (JJ/MM/AAAA ou AAAA-MM-JJ).', 'ex' => '15/01/2026'],
            'Statut' => ['desc' => 'Brouillon, Validé, Enregistré...', 'ex' => 'brouillon'],
        ],
    ];
}
