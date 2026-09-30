<?php

declare(strict_types=1);

/**
 * Administration SaaS - Cabinets clients.
 *
 * Ecran prealable a la facturation : un abonnement se rattache a un cabinet.
 * Seuls les comptes Centre (cabinet_id IS NULL) possessing cabinets.* accedent
 * a cette page, la garde est faite par get_page_permission() / require_page_access().
 */

$query = search_term();
$canCreate = has_permission('cabinets.create');
$canEdit = has_permission('cabinets.edit');
$canDelete = has_permission('cabinets.delete');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$formOpen = isset($_GET['action']) && $_GET['action'] === 'new';

$formData = [
    'code' => '', 'type_cabinet' => '', 'nom' => '', 'email' => '',
    'telephone' => '', 'telephone_fixe' => '', 'telephone_mobile' => '',
    'adresse' => '', 'ville' => '', 'ice' => '', 'rc' => '',
    'identifiant_fiscal' => '', 'taxe_professionnelle' => '',
    'responsable_nom' => '', 'responsable_fonction' => '',
    'responsable_email' => '', 'responsable_telephone' => '',
    'statut' => 'actif',
];
$fieldErrors = [];
// Etats de validation affiches sous la saisie : erreur (bloquant), avertissement
// (format douteux, enregistrement accepte) et confirmation (saisie valide et
// sans doublon). Les deux derniers ne declenchent pas le retour du formulaire,
// ils n'existent que pour le rendu d'un POST refuse — sans eux, l'utilisateur qui
// corrige un champ ne voit pas que les autres sont deja bons.
$fieldWarnings = [];
$fieldOk = [];

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM cabinets WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Cabinet introuvable.');
        redirect_to('cabinets');
    }
}

// Creation : on propose le prochain code de sequence, que l'utilisateur peut
// remplacer. Place AVANT le handler POST pour qu'un code deja saisi, ou une
// erreur de validation, ne soit jamais ecrase par une nouvelle proposition
// (`$formData` est fusionne avec $_POST juste apres, en cas d'erreur).
if ($formOpen && $editId === 0 && (string) $formData['code'] === '') {
    $formData['code'] = next_cabinet_code($db);
}

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        if (!$canDelete) {
            require_permission('cabinets.delete');
        }

        $targetId = (int) ($_POST['id'] ?? 0);

        // MySQL refuserait la suppression via les cles etrangeres
        // fk_users_cabinet / fk_abonnements_cabinet / fk_factures_cabinet /
        // fk_paiements_cabinet. On verifie en amont pour donner un message
        // exploitable au lieu d'une exception SQL.
        $deps = [];
        foreach (['users' => 'utilisateurs', 'abonnements' => 'abonnements', 'factures' => 'factures', 'paiements' => 'paiements'] as $table => $label) {
            $check = $db->prepare("SELECT COUNT(*) FROM `$table` WHERE cabinet_id = :cid");
            $check->execute(['cid' => $targetId]);
            $count = (int) $check->fetchColumn();
            if ($count > 0) {
                $deps[] = $count . ' ' . $label;
            }
        }

        if ($deps !== []) {
            set_flash('error', 'Suppression impossible : ce cabinet est encore rattache a ' . implode(', ', $deps) . '. Supprimez ou reassignez ces elements d\'abord.');
        } else {
            $stmt = $db->prepare('DELETE FROM cabinets WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'cabinet', $targetId);
            set_flash('success', 'Cabinet supprime.');
        }

        redirect_to('cabinets');
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId > 0 && !$canEdit) {
            require_permission('cabinets.edit');
        }
        if ($targetId === 0 && !$canCreate) {
            require_permission('cabinets.create');
        }

        $code = strtoupper(field_value($_POST, 'code'));
        $typeCabinet = field_value($_POST, 'type_cabinet');
        $nom = field_value($_POST, 'nom');
        $email = field_value($_POST, 'email');
        $responsableNom = field_value($_POST, 'responsable_nom');
        $responsableEmail = field_value($_POST, 'responsable_email');
        $statut = field_value($_POST, 'statut', 'actif');

        if (!in_array($statut, cabinet_statut_options(), true)) {
            $statut = 'actif';
        }

        // Les erreurs sont indexees par champ : le formulaire les rend sous la
        // saisie concernee. Une seule message par champ, le premier compte
        // (`??=`), pour qu'une regle de longueur ne soit pas recouverte par une
        // regle de format plus tard dans le meme POST.
        $marquer = static function (string $champ, string $message) use (&$fieldErrors): void {
            $fieldErrors[$champ] ??= $message;
        };

        // Avertissements : le champ est douteux mais recevable. Un ICE a 14
        // chiffres est presque toujours une faute de frappe, pas un cabinet
        // valide ; bloquer l'enregistrement pour cela serait pire que de le
        // signaler, l'utilisateur voit l'ecart et decide.
        $avertir = static function (string $champ, string $message) use (&$fieldWarnings): void {
            $fieldWarnings[$champ] ??= $message;
        };

        // Le champ est vide pour les cabinets deja enregistres, dont le type
        // est ignore ; la colonne reste donc nullable, mais tout enregistrement
        // passe par cette porte.
        if (!in_array($typeCabinet, cabinet_type_options(), true)) {
            $marquer('type_cabinet', $typeCabinet === ''
                ? 'Le type de cabinet est obligatoire.'
                : 'Ce type de cabinet est inconnu.');
        }

        if ($responsableNom === '') {
            $marquer('responsable_nom', 'Le responsable principal est obligatoire.');
        }

        // `maxlength` n'est respectable que par le navigateur : un POST direct
        // tronquerait silencieusement en base. On refuse plutot que d'ecrire
        // une valeur amputee. Le libelle n'est pas repris dans le message, il
        // est deja porte par l'intitule du champ, au-dessus de la saisie.
        $lengths = [
            'code' => 40,
            'nom' => 150,
            'email' => 190,
            'telephone' => 40,
            'telephone_fixe' => 60,
            'telephone_mobile' => 60,
            'adresse' => 255,
            'ville' => 120,
            'ice' => 40,
            'rc' => 60,
            'identifiant_fiscal' => 100,
            'taxe_professionnelle' => 100,
            'responsable_nom' => 150,
            'responsable_fonction' => 120,
            'responsable_email' => 190,
            'responsable_telephone' => 40,
        ];
        foreach ($lengths as $field => $max) {
            if (mb_strlen(field_value($_POST, $field)) > $max) {
                $marquer($field, 'Maximum ' . $max . ' caractères.');
            }
        }

        if ($code === '') {
            $marquer('code', 'Le code du cabinet est obligatoire.');
        }
        if ($nom === '') {
            $marquer('nom', 'Le nom du cabinet est obligatoire.');
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $marquer('email', 'Adresse email invalide.');
        }
        if ($responsableEmail !== '' && filter_var($responsableEmail, FILTER_VALIDATE_EMAIL) === false) {
            $marquer('responsable_email', 'Adresse email invalide.');
        }

        // Doublons.
        //
        // Un cabinet est identifie legalement par son ICE, son RC, son IF et sa
        // TP : deux cabinets qui partagent la meme valeur designent le meme
        // client, et le second eclaterait la facturation entre deux rows. L'email
        // et le code sont uniques de la meme facon, et le code est deja verifie
        // depuis toujours.
        //
        // La colonne est en `utf8mb4_unicode_ci` : la comparaison ignore la
        // casse et les accents, donc `Contact@Cabinet.ma` est refuse contre
        // `contact@cabinet.ma` sans traitement particulier.
        //
        // Le message est_unique porte le nom du cabinet deja enregistre : c'est
        // l'information qui manque pour trancher, et elle est deja affichee
        // dans la colonne Code de la liste situee juste sous le formulaire.
        $doubles = [
            'code' => ['colonne' => 'code', 'message' => 'Ce code de cabinet est déjà utilisé.'],
            'email' => ['colonne' => 'email', 'message' => 'Cet email est déjà utilisé par un autre cabinet.'],
            'ice' => ['colonne' => 'ice', 'message' => 'Cet ICE est déjà attribué à un autre cabinet.'],
            'rc' => ['colonne' => 'rc', 'message' => 'Ce RC est déjà attribué à un autre cabinet.'],
            'identifiant_fiscal' => ['colonne' => 'identifiant_fiscal', 'message' => 'Cet IF est déjà attribué à un autre cabinet.'],
            'taxe_professionnelle' => ['colonne' => 'taxe_professionnelle', 'message' => 'Cette TP est déjà attribuée à un autre cabinet.'],
        ];

        foreach ($doubles as $field => $regle) {
            $valeur = field_value($_POST, $field);
            if ($valeur === '' || isset($fieldErrors[$field])) {
                continue;
            }

            $dup = $db->prepare(
                'SELECT id FROM cabinets WHERE ' . $regle['colonne'] . ' = :valeur AND id <> :id LIMIT 1'
            );
            $dup->execute(['valeur' => $valeur, 'id' => $targetId]);

            if ($dup->fetch()) {
                $marquer($field, $regle['message']);
            }
        }

        // Formats des identifiants legaux.
        //
        // Les longueurs sont celles de l'administration fiscale marocaine : ICE
        // a 15 chiffres, IF a 8. Un ecart n'est pas une erreur — certains
        // cabinets saisisissent une reference interne, et le registre de commerce
        // peut porter un suffixe — mais il doit etre visible, sinon la facture
        // part avec un identifiant faux sans que personne ne le voie.
        $formats = [
            'ice' => ['/^\d{15}$/', 'Un ICE comporte 15 chiffres.'],
            'rc' => ['/^\d+$/', 'Un RC est normalement composé de chiffres uniquement.'],
            'identifiant_fiscal' => ['/^\d{8}$/', 'Un IF comporte 8 chiffres.'],
            'taxe_professionnelle' => ['/^\d+$/', 'Une TP est normalement composee uniquement de chiffres.'],
        ];

        foreach ($formats as $field => [$motif, $message]) {
            $valeur = field_value($_POST, $field);
            if ($valeur === '' || isset($fieldErrors[$field])) {
                continue;
            }

            if (preg_match($motif, $valeur) === 1) {
                $fieldOk[$field] = true;
            } else {
                $avertir($field, $message . ' Saisi : « ' . $valeur . ' ».');
            }
        }

        // Un email valide et non duplique est confirme de meme facon, pour que
        // la section Coordonnees affiche le meme code retour que les identifiants.
        if ($email !== '' && !isset($fieldErrors['email']) && !isset($fieldWarnings['email'])) {
            $fieldOk['email'] = true;
        }

        if ($fieldErrors !== []) {
            $formOpen = true;
            $formData = array_merge($formData, $_POST);
        } else {
            // NULL plutot que '' pour les colonnes optionnelles : les listes et
            // les exports affichent alors '-' via `?? '-'` au lieu d'un trou.
            $opt = static fn (string $value): ?string => $value === '' ? null : $value;

            // `raison_sociale` et `notes` sont volontairement absents du
            // payload : le formulaire ne les propose plus. Les laisser hors
            // ecriture les preserve telles quelles en mise a jour (elles
            // sortent de l'ecran, elles ne sont pas effacees) et les laissent a
            // NULL sur creation, ou le nom unique porte la denomination.
            $payload = [
                'code' => $code,
                'type_cabinet' => $typeCabinet,
                'nom' => $nom,
                'email' => $opt($email),
                'telephone' => $opt(field_value($_POST, 'telephone')),
                'telephone_fixe' => $opt(field_value($_POST, 'telephone_fixe')),
                'telephone_mobile' => $opt(field_value($_POST, 'telephone_mobile')),
                'adresse' => $opt(field_value($_POST, 'adresse')),
                'ville' => $opt(field_value($_POST, 'ville')),
                'ice' => $opt(field_value($_POST, 'ice')),
                'rc' => $opt(field_value($_POST, 'rc')),
                'identifiant_fiscal' => $opt(field_value($_POST, 'identifiant_fiscal')),
                'taxe_professionnelle' => $opt(field_value($_POST, 'taxe_professionnelle')),
                'responsable_nom' => $opt($responsableNom),
                'responsable_fonction' => $opt(field_value($_POST, 'responsable_fonction')),
                'responsable_email' => $opt($responsableEmail),
                'responsable_telephone' => $opt(field_value($_POST, 'responsable_telephone')),
                'statut' => $statut,
            ];

            if ($targetId > 0) {
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE cabinets SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'cabinet', $targetId, $nom);
                set_flash('success', 'Cabinet « ' . $nom . ' » mis a jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO cabinets (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $newId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'cabinet', $newId, $nom);
                set_flash('success', 'Cabinet « ' . $nom . ' » cree.');
            }

            redirect_to('cabinets');
        }
    }
}

// Filtre par type, valide contre la liste figee : une valeur bricolee dans
// l'URL ne doit pas non plus devenir une colonne du SQL.
$typeFilter = (string) ($_GET['type'] ?? '');
if (!in_array($typeFilter, cabinet_type_options(), true)) {
    $typeFilter = '';
}

// La recherche couvre le `telephone` historique comme les deux lignes dediees :
// la colonne Contact les affiche les trois, une recherche qui ignorerait le
// numero principal serait incoherent avec l'ecran.
//
// `raison_sociale` n'y figure plus : la migration a reporte sa valeur dans
// `nom`, qui est donc cherche a sa place. `qualification` et `fonction` ont ete
// supprimees, et les lister ici ferait echouer la requete sur une colonne
// inconnue.
$searchedColumns = [
    'code', 'nom', 'email', 'ville', 'ice', 'rc',
    'telephone', 'telephone_fixe', 'telephone_mobile',
    'identifiant_fiscal', 'taxe_professionnelle',
    'responsable_nom', 'responsable_email',
];

$cabinets = [];
if ($db) {
    // Les conditions sont assemblees puis IMPLIQUEES entre elles. Un AND ecrit
    // a la suite d'une serie de OR ne porterait que sur le dernier OR :
    // AND lie plus fort que OR, le filtre type disparaitrait de fait.
    $where = [];
    $params = [];

    if ($query !== '') {
        $like = like_term($query);
        $ors = [];
        foreach ($searchedColumns as $i => $column) {
            $param = 'like' . $i;
            $ors[] = "c.$column LIKE :$param";
            $params[$param] = $like;
        }
        $where[] = '(' . implode(' OR ', $ors) . ')';
    }

    if ($typeFilter !== '') {
        $where[] = 'c.type_cabinet = :filtre_type';
        $params['filtre_type'] = $typeFilter;
    }

    $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

    $stmt = $db->prepare('
        SELECT c.*,
               (SELECT COUNT(*) FROM abonnements a WHERE a.cabinet_id = c.id) AS nb_abonnements,
               (SELECT COUNT(*) FROM users u WHERE u.cabinet_id = c.id) AS nb_utilisateurs
        FROM cabinets c
        ' . $whereSql . '
        ORDER BY c.nom ASC
    ');
    $stmt->execute($params);
    $cabinets = $stmt->fetchAll();

    // Total non filtre : il alimente le sous-titre de la liste, qui doit
    // distinguer « 3 cabinets sur 12 » de « 12 cabinets ». La requete
    // supplementaire n'est lancee que si un filtre est pose — sans filtre, la
    // liste filtrée fait deja foi.
    $filtreActif = $whereSql !== '';
    $totalCabinets = $filtreActif
        ? (int) $db->query('SELECT COUNT(*) FROM cabinets')->fetchColumn()
        : count($cabinets);

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static fn (array $r): array => [
            $r['code'],
            cabinet_type_label((string) ($r['type_cabinet'] ?? '')),
            $r['nom'],
            $r['responsable_nom'] ?? '-',
            $r['responsable_fonction'] ?? '-',
            $r['responsable_email'] ?? '-',
            $r['responsable_telephone'] ?? '-',
            $r['email'] ?? '-',
            $r['telephone'] ?? '-',
            $r['telephone_fixe'] ?? '-',
            $r['telephone_mobile'] ?? '-',
            $r['adresse'] ?? '-',
            $r['ville'] ?? '-',
            $r['ice'] ?? '-',
            $r['rc'] ?? '-',
            $r['identifiant_fiscal'] ?? '-',
            $r['taxe_professionnelle'] ?? '-',
            cabinet_statut_label((string) $r['statut']),
            $r['nb_utilisateurs'] ?? 0,
            $r['nb_abonnements'] ?? 0,
        ], $cabinets);

        $headers = [
            'Code', 'Type de cabinet', 'Nom du cabinet',
            'Responsable', 'Fonction', 'Email responsable', 'Téléphone responsable',
            'Email', 'Téléphone', 'Téléphone fixe', 'Téléphone mobile', 'Adresse', 'Ville',
            'ICE', 'RC', 'Identifiant fiscal (IF)', 'Taxe professionnelle (TP)',
            'Statut', 'Utilisateurs', 'Abonnements',
        ];

        if ($exportType === 'csv') {
            export_csv('cabinets.csv', $headers, $rows);
        } else {
            export_excel('cabinets.xlsx', $headers, $rows);
        }
    }
}

$statutOptions = [];
foreach (cabinet_statut_options() as $s) {
    $statutOptions[$s] = cabinet_statut_label($s);
}

$typeOptions = [];
foreach (cabinet_type_options() as $t) {
    $typeOptions[$t] = cabinet_type_label($t);
}

// Rendus d'etat par champ, reutilises sur les vingt saisies : sans eux le
// gabarit du formulaire triple la condition a chaque fois, et le message finit
// systematiquement par ne plus etre pose sur la bonne ligne.
//
// L'ordre de priorite est fixe — erreur, puis avertissement, puis confirmation :
// un champ en erreur ne peut pas afficher la coche verte a cote de son message.
$etat = static function (string $champ) use ($fieldErrors, $fieldWarnings, $fieldOk): string {
    if (isset($fieldErrors[$champ])) {
        return ' is-error';
    }
    if (isset($fieldWarnings[$champ])) {
        return ' is-warning';
    }
    if (isset($fieldOk[$champ])) {
        return ' is-ok';
    }
    return '';
};

// Les classes sont prefixees `saas-msg` et non `field-error` : `.field-error`
// existe deja dans la feuille globale, stylisee pour la grille horizontale du
// formulaire collaborateur (`flex: 1 1 100%`). La reutiliser ici lui ferait
// heriter une base flex qui n'a pas de sens dans une etiquette empilee, et le
// message gagnerait une hauteur fantome.
$msg = static function (string $champ) use ($fieldErrors, $fieldWarnings): string {
    $texte = $fieldErrors[$champ] ?? $fieldWarnings[$champ] ?? '';
    if ($texte === '') {
        return '';
    }

    $erreur = isset($fieldErrors[$champ]);

    return '<small class="saas-msg ' . ($erreur ? 'saas-msg--error' : 'saas-msg--warning') . '">'
        . '<span class="material-symbols-outlined">' . ($erreur ? 'error' : 'warning') . '</span>'
        . e($texte)
        . '</small>';
};

$ok = static function (string $champ) use ($fieldErrors, $fieldWarnings, $fieldOk): string {
    if (!isset($fieldOk[$champ]) || isset($fieldErrors[$champ]) || isset($fieldWarnings[$champ])) {
        return '';
    }

    return '<small class="saas-msg saas-msg--ok">'
        . '<span class="material-symbols-outlined">check_circle</span>'
        . 'Format valide, aucun doublon'
        . '</small>';
};

// `aria-invalid` est porte par la saisie, l'etat visuel par l'enveloppe
// `.saas-field` : les deux doivent calendrier ensemble, sinon le lecteur d'ecran
// annonce un champ invalide que la couleur ne signale pas.
$invalide = static function (string $champ) use ($fieldErrors): string {
    return isset($fieldErrors[$champ]) ? ' aria-invalid="true"' : '';
};

// Le statut est choisi dans le formulaire : prevenir avant l'enregistrement
// vaut mieux qu'un cabinet suspendu decouvert a la facturation. On lit
// `$formData` et non `$statut`, qui n'existe qu'apres le handler POST — sans
// cela l'avertissement ne s'afficherait que sur un retour de formulaire, et
// le premier affichage de la page emettait un avertissement PHP.
$alerteStatut = match ((string) ($formData['statut'] ?? 'actif')) {
    'suspendu' => ['is-warning', 'Ce cabinet sera enregistré en suspendu : il ne pourra pas soutenir un abonnement actif.'],
    'ferme' => ['is-error', 'Ce cabinet sera enregistré comme fermé : ses utilisateurs perdront l\'accès à leurs dossiers.'],
    default => null,
};

$recherchePlaceholder = 'Rechercher par code, nom, ville, email, ICE, RC, IF, TP ou responsable';
?>
<div class="saas-canvas">
    <div class="saas-page">

        <?php if ($formOpen && ($canCreate || $canEdit)): ?>
            <form method="post" class="saas-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editId > 0): ?>
                    <input type="hidden" name="id" value="<?= e((string) $editId) ?>">
                <?php endif; ?>

                <div class="saas-form__head">
                    <div>
                        <h2 class="saas-form__title">
                            <span class="material-symbols-outlined"><?= $editId > 0 ? 'edit' : 'add_business' ?></span>
                            <?= $editId > 0 ? 'Modifier le cabinet' : 'Nouveau cabinet client' ?>
                        </h2>
                        <p class="saas-form__sub">
                            <?= $editId > 0
                                ? 'Mettez à jour les informations de ce cabinet.'
                                : 'Renseignez la dénomination, les coordonnées et les identifiants légaux du cabinet.' ?>
                        </p>
                    </div>
                    <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>">
                        <span class="material-symbols-outlined">close</span> Fermer
                    </a>
                </div>

                <?php if ($fieldErrors !== []): ?>
                    <div class="saas-alert is-error" role="alert">
                        <span class="material-symbols-outlined">error</span>
                        <div>
                            <strong><?= count($fieldErrors) ?> champ(s) à corriger</strong>
                            <p>Les champs en rouge ci-dessous empêchent l'enregistrement. Les autres sont acceptés tels quels.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($alerteStatut !== null): ?>
                    <div class="saas-alert <?= e($alerteStatut[0]) ?>">
                        <span class="material-symbols-outlined">warning</span>
                        <div><?= e($alerteStatut[1]) ?></div>
                    </div>
                <?php endif; ?>

                <div class="saas-form__grid">

                    <!-- 1 — Informations generales -->
                    <section class="saas-card" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">business</span>
                            <h3>Informations générales</h3>
                            <button type="button" class="saas-card__toggle" data-saas-toggle
                                    aria-expanded="true" aria-controls="cab-corps-infos">
                                <span class="material-symbols-outlined">expand_more</span>
                                <span class="saas-card__sr">Replier la section Informations générales</span>
                            </button>
                        </header>

                        <div class="saas-card__body" id="cab-corps-infos">
                            <label class="saas-field<?= $etat('code') ?> saas-field--auto">
                                <span class="saas-field__label">Code cabinet <span class="saas-field__req">auto</span></span>
                                <input type="text" name="code" maxlength="40" readonly
                                       value="<?= e((string) ($formData['code'] ?? '')) ?>"
                                       aria-describedby="hint-code"<?= $invalide('code') ?>>
                                <small class="saas-field__hint" id="hint-code">Généré automatiquement, non modifiable</small>
                                <?= $msg('code') ?>
                            </label>

                            <label class="saas-field<?= $etat('type_cabinet') ?>">
                                <span class="saas-field__label">Type de cabinet <em class="req-mark">*</em></span>
                                <select name="type_cabinet" required<?= $invalide('type_cabinet') ?>>
                                    <option value="" disabled<?= (string) ($formData['type_cabinet'] ?? '') === '' ? ' selected' : '' ?>>Choisir un type</option>
                                    <?php foreach ($typeOptions as $val => $lbl): ?>
                                        <option value="<?= e($val) ?>"<?= (string) ($formData['type_cabinet'] ?? '') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $msg('type_cabinet') ?>
                            </label>

                            <label class="saas-field saas-field--wide<?= $etat('nom') ?>">
                                <span class="saas-field__label">Nom du cabinet / Raison sociale <em class="req-mark">*</em></span>
                                <input type="text" name="nom" required maxlength="150"
                                       value="<?= e((string) ($formData['nom'] ?? '')) ?>"
                                       placeholder="Cabinet Exemple"<?= $invalide('nom') ?>>
                                <?= $msg('nom') ?>
                            </label>

                            <label class="saas-field">
                                <span class="saas-field__label">Statut</span>
                                <select name="statut">
                                    <?php foreach ($statutOptions as $val => $lbl): ?>
                                        <option value="<?= e($val) ?>"<?= (string) ($formData['statut'] ?? 'actif') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </section>

                    <!-- 2 — Coordonnees -->
                    <section class="saas-card" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">contact_mail</span>
                            <h3>Coordonnées</h3>
                            <button type="button" class="saas-card__toggle" data-saas-toggle
                                    aria-expanded="true" aria-controls="cab-corps-coordonnees">
                                <span class="material-symbols-outlined">expand_more</span>
                                <span class="saas-card__sr">Replier la section Coordonnées</span>
                            </button>
                        </header>

                        <div class="saas-card__body" id="cab-corps-coordonnees">
                            <label class="saas-field<?= $etat('email') ?>">
                                <span class="saas-field__label">Email du cabinet</span>
                                <input type="email" name="email" maxlength="190"
                                       value="<?= e((string) ($formData['email'] ?? '')) ?>"
                                       placeholder="contact@cabinet.ma"<?= $invalide('email') ?>>
                                <?= $msg('email') ?>
                                <?= $ok('email') ?>
                            </label>

                            <label class="saas-field<?= $etat('telephone') ?>">
                                <span class="saas-field__label">Téléphone principal</span>
                                <input type="text" name="telephone" maxlength="40" inputmode="tel"
                                       value="<?= e((string) ($formData['telephone'] ?? '')) ?>"
                                       placeholder="0522 00 00 00"<?= $invalide('telephone') ?>>
                                <?= $msg('telephone') ?>
                            </label>

                            <label class="saas-field">
                                <span class="saas-field__label">Téléphone fixe</span>
                                <input type="text" name="telephone_fixe" maxlength="60" inputmode="tel"
                                       value="<?= e((string) ($formData['telephone_fixe'] ?? '')) ?>"
                                       placeholder="0522 00 00 00">
                            </label>

                            <label class="saas-field">
                                <span class="saas-field__label">Téléphone mobile</span>
                                <input type="text" name="telephone_mobile" maxlength="60" inputmode="tel"
                                       value="<?= e((string) ($formData['telephone_mobile'] ?? '')) ?>"
                                       placeholder="0661 00 00 00">
                            </label>

                            <label class="saas-field saas-field--wide">
                                <span class="saas-field__label">Adresse</span>
                                <input type="text" name="adresse" maxlength="255"
                                       value="<?= e((string) ($formData['adresse'] ?? '')) ?>"
                                       placeholder="Rue, numéro, quartier">
                            </label>

                            <label class="saas-field">
                                <span class="saas-field__label">Ville</span>
                                <input type="text" name="ville" maxlength="120"
                                       value="<?= e((string) ($formData['ville'] ?? '')) ?>"
                                       placeholder="Casablanca">
                            </label>
                        </div>
                    </section>

                    <!-- 3 — Identifiants legaux : carte accentuee, elle porte l'identite
                         fiscale du cabinet qui apparait sur les factures. -->
                    <section class="saas-card saas-card--legal" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">verified</span>
                            <h3>Identifiants légaux</h3>
                            <span class="saas-card__tag">Facturation</span>
                            <button type="button" class="saas-card__toggle" data-saas-toggle
                                    aria-expanded="true" aria-controls="cab-corps-legaux">
                                <span class="material-symbols-outlined">expand_more</span>
                                <span class="saas-card__sr">Replier la section Identifiants légaux</span>
                            </button>
                        </header>

                        <div class="saas-card__body saas-card__body--legal" id="cab-corps-legaux">
                            <p class="saas-card__note">
                                Ces quatre identifiants figurent sur les factures et les déclarations.
                                Un doublon est refusé : chaque valeur doit désigner un seul cabinet.
                            </p>
                            <label class="saas-field saas-field--mono<?= $etat('ice') ?>">
                                <span class="saas-field__label">ICE <small>Identifiant Commun de l'Entreprise</small></span>
                                <input type="text" name="ice" maxlength="40" inputmode="numeric" data-numeric
                                       value="<?= e((string) ($formData['ice'] ?? '')) ?>"
                                       placeholder="001234567890123"<?= $invalide('ice') ?>>
                                <?= $msg('ice') ?>
                                <?= $ok('ice') ?>
                            </label>

                            <label class="saas-field saas-field--mono<?= $etat('rc') ?>">
                                <span class="saas-field__label">RC <small>Registre de Commerce</small></span>
                                <input type="text" name="rc" maxlength="60" inputmode="numeric" data-numeric
                                       value="<?= e((string) ($formData['rc'] ?? '')) ?>"
                                       placeholder="123456"<?= $invalide('rc') ?>>
                                <?= $msg('rc') ?>
                                <?= $ok('rc') ?>
                            </label>

                            <label class="saas-field saas-field--mono<?= $etat('identifiant_fiscal') ?>">
                                <span class="saas-field__label">IF <small>Identifiant Fiscal</small></span>
                                <input type="text" name="identifiant_fiscal" maxlength="100" inputmode="numeric" data-numeric
                                       value="<?= e((string) ($formData['identifiant_fiscal'] ?? '')) ?>"
                                       placeholder="40312785"<?= $invalide('identifiant_fiscal') ?>>
                                <?= $msg('identifiant_fiscal') ?>
                                <?= $ok('identifiant_fiscal') ?>
                            </label>

                            <label class="saas-field saas-field--mono<?= $etat('taxe_professionnelle') ?>">
                                <span class="saas-field__label">TP <small>Taxe Professionnelle</small></span>
                                <input type="text" name="taxe_professionnelle" maxlength="100" inputmode="numeric" data-numeric
                                       value="<?= e((string) ($formData['taxe_professionnelle'] ?? '')) ?>"
                                       placeholder="27185403"<?= $invalide('taxe_professionnelle') ?>>
                                <?= $msg('taxe_professionnelle') ?>
                                <?= $ok('taxe_professionnelle') ?>
                            </label>
                        </div>
                    </section>

                    <!-- 4 — Responsable principal -->
                    <section class="saas-card" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">person</span>
                            <h3>Responsable principal</h3>
                            <button type="button" class="saas-card__toggle" data-saas-toggle
                                    aria-expanded="true" aria-controls="cab-corps-responsable">
                                <span class="material-symbols-outlined">expand_more</span>
                                <span class="saas-card__sr">Replier la section Responsable principal</span>
                            </button>
                        </header>

                        <div class="saas-card__body" id="cab-corps-responsable">
                            <p class="saas-card__note">
                                Interlocuteur du cabinet pour la facturation et les relances.
                            </p>
                            <label class="saas-field saas-field--wide<?= $etat('responsable_nom') ?>">
                                <span class="saas-field__label">Nom et prénom <em class="req-mark">*</em></span>
                                <input type="text" name="responsable_nom" required maxlength="150"
                                       value="<?= e((string) ($formData['responsable_nom'] ?? '')) ?>"
                                       placeholder="Karim Bennani"<?= $invalide('responsable_nom') ?>>
                                <?= $msg('responsable_nom') ?>
                            </label>

                            <label class="saas-field<?= $etat('responsable_fonction') ?>">
                                <span class="saas-field__label">Fonction</span>
                                <input type="text" name="responsable_fonction" maxlength="120"
                                       value="<?= e((string) ($formData['responsable_fonction'] ?? '')) ?>"
                                       placeholder="Associé gérant">
                                <?= $msg('responsable_fonction') ?>
                            </label>

                            <label class="saas-field<?= $etat('responsable_email') ?>">
                                <span class="saas-field__label">Email</span>
                                <input type="email" name="responsable_email" maxlength="190"
                                       value="<?= e((string) ($formData['responsable_email'] ?? '')) ?>"
                                       placeholder="k.bennani@cabinet.ma"<?= $invalide('responsable_email') ?>>
                                <?= $msg('responsable_email') ?>
                            </label>

                            <label class="saas-field<?= $etat('responsable_telephone') ?>">
                                <span class="saas-field__label">Téléphone</span>
                                <input type="text" name="responsable_telephone" maxlength="40" inputmode="tel"
                                       value="<?= e((string) ($formData['responsable_telephone'] ?? '')) ?>"
                                       placeholder="0661 00 00 00"<?= $invalide('responsable_telephone') ?>>
                                <?= $msg('responsable_telephone') ?>
                            </label>
                        </div>
                    </section>
                </div>

                <div class="saas-form__footer">
                    <span class="saas-form__legend">
                        <em class="req-mark">*</em> Champs obligatoires
                    </span>
                    <div class="saas-form__buttons">
                        <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>">
                            <span class="material-symbols-outlined">close</span> Annuler
                        </a>
                        <?php if ($editId > 0): ?>
                            <button class="btn btn-next" type="submit">
                                <span class="material-symbols-outlined">save</span> Mettre à jour
                            </button>
                        <?php else: ?>
                            <button class="btn btn-next" type="submit">
                                <span class="material-symbols-outlined">add</span> Créer le cabinet
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- Titre de la liste. On reutilise volontairement les classes de
             l'en-tete du formulaire (icone + titre + sous-titre + actions a
             droite) : meme niveau de hierarchie, meme rhythmme, et la seule
             difference avec le bloc du dessus est la taille du contenu. -->
        <div class="saas-form__head">
            <div>
                <h2 class="saas-form__title">
                    <span class="material-symbols-outlined">list_alt</span>
                    Liste des cabinets clients
                </h2>
                <p class="saas-form__sub">
                    <strong><?= $totalCabinets ?></strong> cabinet<?= $totalCabinets > 1 ? 's' : '' ?> au total
                    <?php if ($filtreActif): ?>
                        &middot; <strong><?= count($cabinets) ?></strong> affich&eacute;<?= count($cabinets) > 1 ? 's' : '' ?> par le filtre
                    <?php endif; ?>
                    &middot; filtres disponibles : recherche libre et type de cabinet
                </p>
            </div>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('cabinets', ['export' => 'csv', 'q' => $query, 'type' => $typeFilter])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-info" href="<?= e(app_url('cabinets', ['export' => 'xlsx', 'q' => $query, 'type' => $typeFilter])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <article class="card saas-table">
            <form method="get" class="search-bar">
                <input type="hidden" name="page" value="cabinets">
                <div class="inline-form">
                    <input type="search" name="q" placeholder="<?= e($recherchePlaceholder) ?>" value="<?= e($query) ?>">
                    <select name="type" aria-label="Filtrer par type de cabinet">
                        <option value="">Tous les types</option>
                        <?php foreach ($typeOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>"<?= $typeFilter === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                    <?php if ($query !== '' || $typeFilter !== ''): ?>
                        <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!$cabinets): ?>
                <p class="table-empty">
                    <?php if ($query !== '' || $typeFilter !== ''): ?>
                        Aucun cabinet ne correspond à cette recherche.
                    <?php else: ?>
                        Aucun cabinet enregistré. Les abonnements se rattachent à un cabinet : créez d'abord votre premier client.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <div class="table-scroll">
                    <table data-sortable data-table="cabinets">
                        <thead>
                            <tr>
                                <th data-col="code">Code</th>
                                <th data-col="type">Type</th>
                                <th data-col="nom">Nom</th>
                                <th data-col="responsable">Responsable</th>
                                <th data-col="identifiants">Identifiants</th>
                                <th data-col="contact">Contact</th>
                                <th data-col="ville">Ville</th>
                                <th data-col="statut">Statut</th>
                                <th data-col="users">Utilisateurs</th>
                                <th data-col="abos">Abonnements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cabinets as $cab): ?>
                            <?php
                            // Les identifiants sont empiles en petits caracteres : sur
                            // une ligne horizontale, quatre colonnes distinctes
                            // repoussaient les compteurs hors de l'ecran.
                            $identifiants = array_filter([
                                'ICE' => $cab['ice'] ?? '',
                                'RC' => $cab['rc'] ?? '',
                                'IF' => $cab['identifiant_fiscal'] ?? '',
                                'TP' => $cab['taxe_professionnelle'] ?? '',
                            ], static fn ($v): bool => $v !== null && $v !== '');
                            ?>
                            <tr>
                                <td><strong><?= e((string) $cab['code']) ?></strong></td>
                                <td><span class="badge <?= e(cabinet_type_tone((string) ($cab['type_cabinet'] ?? ''))) ?>"><?= e(cabinet_type_label((string) ($cab['type_cabinet'] ?? ''))) ?></span></td>
                                <td><?= e((string) $cab['nom']) ?></td>
                                <td>
                                    <?php if (empty($cab['responsable_nom'])): ?>
                                        <span class="text-muted">—</span>
                                    <?php else: ?>
                                        <?= e((string) $cab['responsable_nom']) ?>
                                        <?php if (!empty($cab['responsable_fonction'])): ?>
                                            <br><small><?= e((string) $cab['responsable_fonction']) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($identifiants === []): ?>
                                        <?= '-' ?>
                                    <?php else: ?>
                                        <?php foreach ($identifiants as $sigle => $value): ?>
                                            <small><?= e($sigle) ?> : <?= e((string) $value) ?></small><br>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e((string) ($cab['email'] ?? '-')) ?>
                                    <?php foreach (['telephone', 'telephone_fixe', 'telephone_mobile'] as $phoneField): ?>
                                        <?php if (!empty($cab[$phoneField])): ?>
                                            <br><small><?= e((string) $cab[$phoneField]) ?></small>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td><?= e((string) ($cab['ville'] ?? '-')) ?></td>
                                <td><span class="badge <?= e(cabinet_statut_tone((string) $cab['statut'])) ?>"><?= e(cabinet_statut_label((string) $cab['statut'])) ?></span></td>
                                <td><?= (int) ($cab['nb_utilisateurs'] ?? 0) ?></td>
                                <td><?= (int) ($cab['nb_abonnements'] ?? 0) ?></td>
                                <td class="table-actions">
                                    <?php if ($canEdit): ?>
                                        <a class="btn-icon info" href="<?= e(app_url('cabinets', ['edit' => (int) $cab['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <form method="post">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= e((string) $cab['id']) ?>">
                                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer le cabinet « <?= e((string) $cab['nom']) ?> » ?" data-confirm-title="Supprimer le cabinet" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </div>
