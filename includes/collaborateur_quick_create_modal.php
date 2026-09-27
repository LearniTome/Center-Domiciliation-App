<?php
declare(strict_types=1);

/**
 * Modale de creation rapide d'un collaborateur, partagee entre :
 *   - pages/dossiers/collaborateurs_liste.php (bouton « Nouveau collaborateur »)
 *   - pages/dossiers/creation_steps/step_01_Societe.php (champ « Collaborateur
 *     en charge du dossier »)
 *
 * Extraite de la page liste pour que les deux declarations ne puissent pas
 * diverger. Delegue le rendu a includes/quick_create_modal.php.
 *
 * Variables optionnelles definies avant l'inclusion :
 *   $quickCreateModalKey        cle du modal (defaut '' : modal unique)
 *   $collabDefaults             valeurs par defaut du collaborateur
 *   $quickCreateTargetSelect    nom du <select> a alimenter apres creation
 *   $quickCreateTargetLabel     cles de la reponse concatenees pour le libelle
 */

if (!isset($collabTypeOptions)) {
    $collabTypeOptions = ['interne', 'externe-pm', 'externe-pp'];
}
if (!isset($collabStatutOptions)) {
    $collabStatutOptions = ['actif', 'inactif', 'suspendu'];
}
/**
 * Transforme une liste de valeurs brutes en couples valeur => libelle lisible
 * ( premiere lettre en capitale ). La valeur envoyee a l'API reste la valeur
 * brute stockee en base : on ne change que ce qui est affiche.
 */
$collaborateurHumanize = static function (array $options): array {
    if ($options === [] || !array_is_list($options)) {
        return $options;
    }
    $libelles = [];
    foreach ($options as $value) {
        $libelles[(string) $value] = ucfirst((string) $value);
    }
    return $libelles;
};
$collabTypeOptions = $collaborateurHumanize($collabTypeOptions);
$collabStatutOptions = $collaborateurHumanize($collabStatutOptions);
if (!isset($rolesOptions) || $rolesOptions === []) {
    $rolesOptions = [];
    if (($pdo ?? null) instanceof PDO) {
        $stmt = $pdo->query('SELECT id, nom FROM roles ORDER BY nom ASC');
        while ($row = $stmt->fetch()) {
            $rolesOptions[(int) $row['id']] = $row['nom'];
        }
    }
}
if (!isset($qualiteOptions) || $qualiteOptions === []) {
    $qualiteOptions = [];
    if (($pdo ?? null) instanceof PDO) {
        $qualStmt = $pdo->query('SELECT id, libelle FROM ref_qualites_intermediaire ORDER BY sort_order ASC, libelle ASC');
        foreach ($qualStmt->fetchAll() as $qualRow) {
            $qualiteOptions[(int) $qualRow['id']] = $qualRow['libelle'];
        }
    }
}

$quickCreateTitle = 'Nouveau collaborateur';
$quickCreateTable = 'collaborateurs';
$quickCreateSubmitLabel = 'Créer le collaborateur';
// Les deux champs generes ne sont pas saisissables : ils sont rendus dans
// l'apercu du pied collant plutot que dans la grille (cf. $quickCreatePreview).
$quickCreatePreview = ['nom_complet', 'collaborateur_code'];
// 17 champs : la modale occupe la largeur de la fenetre au lieu d'etre cantonnee
// a 960 px, et la grille s'adapte (voir .qc-wide dans app.css).
$quickCreateWide = true;
// Champ identifiant l'enregistrement dans le message de confirmation apres
// rechargement de la page liste.
$quickCreateLabelField = 'nom_complet';
$quickCreateDefaults = isset($collabDefaults) && is_array($collabDefaults) ? $collabDefaults : load_defaults('collaborateur');
$quickCreateFields = [
    ['type' => 'title', 'label' => 'Identité & Rôle'],
    ['name' => 'collaborateur_type', 'label' => 'Type', 'type' => 'select', 'options' => $collabTypeOptions, 'required' => true],
    ['name' => 'qualite_intermediaire_id', 'label' => 'Qualification', 'type' => 'select', 'options' => $qualiteOptions, 'placeholder' => 'Non qualifie'],
    ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'options' => $rolesOptions],
    ['name' => 'collaborateur_nom', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'data-code-part' => 'nom'],
    ['name' => 'collaborateur_prenom', 'label' => 'Prenom', 'type' => 'text', 'data-code-part' => 'prenom'],
    ['name' => 'den_ste', 'label' => 'Cabinet', 'type' => 'text'],
    ['name' => 'fonction', 'label' => 'Fonction', 'type' => 'text'],
    ['name' => 'statut', 'label' => 'Statut', 'type' => 'select', 'options' => $collabStatutOptions],
    ['type' => 'title', 'label' => 'Contact'],
    ['name' => 'collaborateur_email', 'label' => 'Email', 'type' => 'email'],
    ['name' => 'collaborateur_tel_mobile', 'label' => 'Telephone mobile', 'type' => 'text'],
    ['name' => 'collaborateur_tel_fixe', 'label' => 'Telephone fixe', 'type' => 'text'],
    ['name' => 'collaborateur_adresse', 'label' => 'Adresse', 'type' => 'textarea', 'full' => true],
    ['type' => 'title', 'label' => 'Identifiants legaux'],
    ['name' => 'collaborateur_ice', 'label' => 'ICE', 'type' => 'text'],
    ['name' => 'collaborateur_tp', 'label' => 'TP', 'type' => 'text'],
    ['name' => 'collaborateur_rc', 'label' => 'RC', 'type' => 'text'],
    ['name' => 'collaborateur_if', 'label' => 'IF', 'type' => 'text'],
    // Champs calcules : declares ici comme les autres, mais rendus dans
    // l'apercu du pied collant via $quickCreatePreview.
    ['name' => 'nom_complet', 'label' => 'Nom complet (généré)', 'type' => 'text', 'data-derived' => 'nom-complet', 'readonly' => true],
    ['name' => 'collaborateur_code', 'label' => 'Code (généré)', 'type' => 'text', 'data-derived' => 'code', 'readonly' => true],
];

require __DIR__ . '/quick_create_modal.php';
