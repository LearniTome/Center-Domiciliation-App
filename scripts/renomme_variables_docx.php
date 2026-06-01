<?php

declare(strict_types=1);

/**
 * Renomme les anciennes variables dans les fichiers DOCX templates
 * pour utiliser les nouveaux noms préfixés.
 *
 * Usage : php renomme_variables_docx.php
 * Le script modifie les fichiers sur place (backup .bak créé).
 */

$templatesDir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

$replacements = [
    // Societe
    '{{ DENOMINATION_SOCIALE }}' => '{{ SOCIETE_RAISON_SOCIALE }}',
    '{{ RAISON_SOCIALE }}'      => '{{ SOCIETE_RAISON_SOCIALE }}',
    '{{ DEN_STE }}'             => '{{ SOCIETE_RAISON_SOCIALE }}',
    '{{ FORME_JURIDIQUE }}'     => '{{ SOCIETE_FORME_JURIDIQUE }}',
    '{{ FORME_JUR }}'           => '{{ SOCIETE_FORME_JURIDIQUE }}',
    '{{ CONTRAT_FORME_JURIDIQUE }}' => '{{ SOCIETE_FORME_JURIDIQUE }}',
    '{{ CAPITAL_SOCIAL }}'      => '{{ SOCIETE_CAPITAL }}',
    '{{ NOMBRE_PARTS }}'        => '{{ SOCIETE_PART_SOCIAL }}',
    '{{ NOMBRE_PARTS_SOCIALES }}' => '{{ SOCIETE_PART_SOCIAL }}',
    '{{ NUMERO_ICE }}'          => '{{ SOCIETE_ICE }}',
    '{{ ICE }}'                 => '{{ SOCIETE_ICE }}',
    '{{ VILLE }}'               => '{{ SOCIETE_VILLE }}',
    '{{ ADRESSE_SIEGE_SOCIAL }}' => '{{ SOCIETE_ADRESSE_SIEGE }}',
    '{{ TRIBUNAL_COMPETENT }}'  => '{{ SOCIETE_TRIBUNAL }}',
    '{{ DATE_IMMATRICULATION_ICE }}' => '{{ SOCIETE_DATE_ICE }}',
    '{{ NUM_RC }}'              => '{{ SOCIETE_RC }}',
    '{{ NUM_DEPOT_RC }}'        => '{{ SOCIETE_RC }}',
    // Associe
    '{{ NOM_ASSOCIE }}'         => '{{ ASSOCIE_NOM }}',
    '{{ NOM }}'                 => '{{ ASSOCIE_NOM }}',
    '{{ PRENOM_ASSOCIE }}'      => '{{ ASSOCIE_PRENOM }}',
    '{{ PRENOM }}'              => '{{ ASSOCIE_PRENOM }}',
    '{{ CIVILITE_ASSOCIE }}'    => '{{ ASSOCIE_CIVILITE }}',
    '{{ CIVIL }}'               => '{{ ASSOCIE_CIVILITE }}',
    '{{ NOM_COMPLET }}'         => '{{ ASSOCIE_NOM_COMPLET }}',
    '{{ NUMERO_CIN_ASSOCIE }}'  => '{{ ASSOCIE_CIN }}',
    '{{ DATE_NAISSANCE_ASSOCIE }}' => '{{ ASSOCIE_DATE_NAISSANCE }}',
    '{{ LIEU_NAISSANCE_ASSOCIE }}' => '{{ ASSOCIE_LIEU_NAISSANCE }}',
    '{{ NATIONALITE_ASSOCIE }}' => '{{ ASSOCIE_NATIONALITE }}',
    '{{ ADRESSE_ASSOCIE }}'     => '{{ ASSOCIE_ADRESSE }}',
    '{{ EMAIL_ASSOCIE }}'       => '{{ ASSOCIE_EMAIL }}',
    '{{ TELEPHONE_ASSOCIE }}'   => '{{ ASSOCIE_TELEPHONE }}',
    '{{ QUALITE_ASSOCIE }}'     => '{{ ASSOCIE_QUALITE }}',
    '{{ DATE_VALIDITE_CIN_ASSOCIE }}' => '{{ ASSOCIE_DATE_VALIDITE_CIN }}',
    // Contrat
    '{{ DATE_CONTRAT }}'        => '{{ CONTRAT_DATE }}',
    '{{ DATE_DEBUT_CONTRAT }}'  => '{{ CONTRAT_DATE_DEBUT }}',
    '{{ DATE_FIN_CONTRAT }}'    => '{{ CONTRAT_DATE_FIN }}',
    '{{ DUREE_CONTRAT_MOIS }}'  => '{{ CONTRAT_DUREE_MOIS }}',
    '{{ PACK_DEMARRAGE_LOYER_MENSUEL }}' => '{{ CONTRAT_PACK_LOYER_TTC }}',
    '{{ PACK_DEMARRAGE_TOTAL_TTC }}'     => '{{ CONTRAT_PACK_MONTANT_TTC }}',
    '{{ RENOUVELLEMENT_LOYER_ANNUEL }}'  => '{{ CONTRAT_RENOUV_ANNUEL_TTC }}',
    '{{ RENOUVELLEMENT_LOYER_MENSUEL }}' => '{{ CONTRAT_RENOUV_LOYER_TTC }}',
    '{{ DTAE_CONTRAT }}'        => '{{ CONTRAT_TYPE }}',
];

// Même mapping sans espace après {{
$noSpace = [];
foreach ($replacements as $old => $new) {
    $noSpace[str_replace('{{ ', '{{', $old)] = str_replace('{{ ', '{{', $new);
}
$replacements = array_merge($replacements, $noSpace);

if (!is_dir($templatesDir)) {
    echo "ERREUR : dossier templates introuvable : $templatesDir\n";
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templatesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$count = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'docx') continue;
    if (!class_exists('ZipArchive')) {
        echo "ERREUR : ZipArchive requis\n";
        exit(1);
    }

    $path = $file->getPathname();
    echo "Traitement : " . $file->getFilename() . "\n";

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        echo "  Erreur : impossible d'ouvrir le fichier\n";
        continue;
    }

    // Backup
    $backup = $path . '.bak';
    if (!file_exists($backup)) {
        copy($path, $backup);
    }

    $modified = false;
    $parts = ['word/document.xml'];

    // Chercher aussi en-têtes et pieds de page
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name !== false && (
            str_starts_with($name, 'word/header') ||
            str_starts_with($name, 'word/footer')
        )) {
            $parts[] = $name;
        }
    }

    $parts = array_unique($parts);

    foreach ($parts as $part) {
        $xml = $zip->getFromName($part);
        if ($xml === false) continue;

        $newXml = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $xml
        );

        if ($newXml !== $xml) {
            $zip->deleteName($part);
            $zip->addFromString($part, $newXml);
            $modified = true;
        }
    }

    $zip->close();

    if ($modified) {
        $count++;
        echo "  Variables renommees\n";
    } else {
        echo "  Aucune variable ancienne trouvee\n";
    }
}

echo "\nTermine. $count fichier(s) modifie(s).\n";
echo "Les fichiers .bak sont conserves en sauvegarde.\n";
