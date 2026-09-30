<?php

declare(strict_types=1);

/**
 * Administration SaaS - Abonnements des cabinets.
 *
 * Vue transverse : un Super Admin voit tous les cabinets. Aucun filtre tenant
 * ici, volontairement -- c'est la seule page ou le cloisonnement cabinet_id
 * ne doit pas s'appliquer. Les ecrans adherents cloisonnes sont dans
 * mon_abonnement.php.
 *
 * Troisieme etape de la souscription : une fois l'abonnement enregistre et
 * actif, l'action "Creer un acces" ouvre le compte administrateur du cabinet
 * (voir creer_acces_cabinet()). Le rattachement au cabinet n'est jamais choisi
 * dans le formulaire : il decoule de l'abonnement, donc un acces ne peut pas
 * atterrir chez un tenant qui n'a pas souscrit.
 */

$query = search_term();
$canCreate = has_permission('abonnements.create');
$canEdit = has_permission('abonnements.edit');
$canDelete = has_permission('abonnements.delete');
// Ouvrir un compte pour un cabinet est une gestion d'utilisateur, pas une
// ecriture d'abonnement : le droit se prend donc sur `users.create`. Un
// administrateur de cabinet possede ce droit mais pas `abonnements.view`, il
// n'atteint donc jamais cette page par le routage.
$canCreateAccess = has_permission('users.create');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$cabinetOptions = fetch_cabinets_options($db);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
// Le formulaire s'ouvre sur `?action=new` ET sur `?edit=<id>` : le bouton
// "Modifier" de la liste renvoie la seconde forme, qui resterait invisible si
// l'ouverture ne dependait que du parametre `action`.
$formOpen = (isset($_GET['action']) && $_GET['action'] === 'new') || $editId > 0;
// En creation on ne propose que les plans actifs ; en edition on charge aussi
// les plans inactifs, sinon un abonnement rattache a un plan desactive verrait
// "Sur mesure (sans plan)" dans le select et perdrait sa formule et son prix
// negocie a la premiere sauvegarde, sans lever la moindre erreur.
$planOptions = fetch_plans_options($db, $editId === 0);

// Panneau "Creer un acces" : ouvert par `?acces=<id abonnement>` sur un
// abonnement actif, et automatiquement propose juste apres la creation d'un
// abonnement actif. Il est exclusif du formulaire d'abonnement -- deux
// formulaires ouverts simultanement se concurrencerait l'ecran.
$accesId = isset($_GET['acces']) ? (int) $_GET['acces'] : 0;
$accesOuvert = $accesId > 0;
if ($accesOuvert) {
    // `?acces=<id>` est un point d'entree comme un autre : sans le droit
    // `users.create`, le panneau ne serait pas rendu et l'admin comprendrait
    // qu'un bouton a disparu. require_permission() repond explicitement.
    if (!$canCreateAccess) {
        require_permission('users.create');
    }

    $formOpen = false;
}

// Valeurs du formulaire d'acces, et ses erreurs : deux jeux distincts de
// `$fieldErrors`, l'un pour l'abonnement, l'autre pour le compte.
$accesData = ['nom_complet' => '', 'email' => '', 'role_id' => ''];
$accesErrors = [];

// Role par defaut : "Administrateur Cabinet" si present, sinon le premier role
// de cabinet. Un cabinet sans administrateur ne pourrait rien piloter.
$rolesCabinet = $canCreateAccess ? fetch_roles_cabinet_options($db) : [];
$roleDefautId = 0;
foreach ($rolesCabinet as $rid => $rnom) {
    if ($rnom === 'Administrateur Cabinet') {
        $roleDefautId = (int) $rid;
        break;
    }
}
if ($roleDefautId === 0 && $rolesCabinet !== []) {
    $roleDefautId = (int) array_key_first($rolesCabinet);
}
if ($accesData['role_id'] === '') {
    $accesData['role_id'] = (string) $roleDefautId;
}

// Abonnement cible du panneau d'acces + ses comptes. Le panneau ne s'ouvre
// que sur un abonnement VIVANT (actif ou essai non echu) : creer un compte
// pour un abonnement resilie ou expire produirait un adherent que la porte de
// connexion refuserait aussitot, donc un acces mort-nais.
// La regle est celle de la connexion (abonnement_autorise_acces) : l'ecran et
// la porte ne peuvent pas diverger sur ce qui autorise un acces.
$accesAbo = null;
$accesComptes = [];

if ($accesOuvert && $db) {
    $stmt = $db->prepare('
        SELECT a.*, c.nom AS cabinet_nom, c.code AS cabinet_code, p.nom AS plan_nom
        FROM abonnements a
        LEFT JOIN cabinets c ON c.id = a.cabinet_id
        LEFT JOIN plans p ON p.id = a.plan_id
        WHERE a.id = :id
    ');
    $stmt->execute(['id' => $accesId]);
    $accesAbo = $stmt->fetch() ?: null;

    if ($accesAbo === null) {
        set_flash('error', 'Abonnement introuvable.');
        redirect_to('abonnements');
    }

    $etatAbo = [
        'statut' => (string) $accesAbo['statut'],
        'jours_restants' => abonnement_jours_restants($accesAbo),
        'libelle' => abonnement_statut_label(abonnement_display_statut($accesAbo)),
    ];

    if (!abonnement_autorise_acces($etatAbo)) {
        set_flash('error', 'Impossible de creer un acces : cet abonnement est ' . strtolower(abonnement_statut_label(abonnement_display_statut($accesAbo))) . '. Renouvelez-le d\'abord.');
        redirect_to('abonnements');
    }

    $accesComptes = fetch_comptes_cabinet($db, (int) $accesAbo['cabinet_id']);
}

// Mot de passe provisoire affiche UNE seule fois : il transite par la session
// puis il est detruit. Il n'est jamais journalise ni stocke en clair.
$accesCree = null;
if (isset($_SESSION['_acces_cree'])) {
    $accesCree = $_SESSION['_acces_cree'];
    unset($_SESSION['_acces_cree']);
}

$formData = [
    'cabinet_id' => '', 'plan_id' => '', 'date_debut' => date('Y-m-d'), 'date_fin' => '',
    'statut' => 'actif', 'prix_annuel_negocie' => '', 'devise' => 'MAD',
    'auto_renew' => '1', 'notes' => '',
];
// Erreurs de validation indexees par nom de champ, alimentees par le handler
// POST puis rendues sous chaque saisie (`.saas-msg`).
$fieldErrors = [];

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM abonnements WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Abonnement introuvable.');
        redirect_to('abonnements');
    }
}

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        if (!$canDelete) {
            require_permission('abonnements.delete');
        }

        $targetId = (int) ($_POST['id'] ?? 0);

        // fk_factures_abonnement : on refuse proprement si l'abonnement a deja
        // produit des factures, plutot que de laisser une exception SQL monter.
        $check = $db->prepare('SELECT COUNT(*) FROM factures WHERE abonnement_id = :aid');
        $check->execute(['aid' => $targetId]);
        $linked = (int) $check->fetchColumn();

        if ($linked > 0) {
            set_flash('error', 'Suppression impossible : ' . $linked . ' facture(s) sont rattachées à cet abonnement. Marquez-le résilié plutôt.');
        } else {
            $stmt = $db->prepare('DELETE FROM abonnements WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'abonnement', $targetId);
            set_flash('success', 'Abonnement supprime.');
        }

        redirect_to('abonnements');
    }

    if ($action === 'renew') {
        if (!$canEdit) {
            require_permission('abonnements.edit');
        }

        $targetId = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT id, date_fin, cabinet_id FROM abonnements WHERE id = :id');
        $stmt->execute(['id' => $targetId]);
        $row = $stmt->fetch();

        if (!$row) {
            set_flash('error', 'Abonnement introuvable.');
            redirect_to('abonnements');
        }

        // On prolonge d'un an a partir de la fin en cours si elle est encore
        // devant nous, sinon a partir d'aujourd'hui : sans cela, renouveler un
        // abonnement deja echu le laisserait immediatement a nouveau expire.
        $base = (string) $row['date_fin'] > date('Y-m-d') ? (string) $row['date_fin'] : date('Y-m-d');
        $newEnd = date('Y-m-d', strtotime($base . ' +1 year'));

        $upd = $db->prepare("UPDATE abonnements SET date_fin = :fin, statut = 'actif' WHERE id = :id");
        $upd->execute(['fin' => $newEnd, 'id' => $targetId]);
        log_activity($db, 'update', 'abonnement', $targetId, 'Renouvellement jusqu au ' . $newEnd);
        set_flash('success', 'Abonnement renouvele jusqu\'au ' . date('d/m/Y', strtotime($newEnd)) . '.');

        redirect_to('abonnements');
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId > 0 && !$canEdit) {
            require_permission('abonnements.edit');
        }
        if ($targetId === 0 && !$canCreate) {
            require_permission('abonnements.create');
        }

        $cabinetId = (int) (int_value($_POST, 'cabinet_id') ?? 0);
        $planId = int_value($_POST, 'plan_id');
        $dateDebut = field_value($_POST, 'date_debut');
        $dateFin = field_value($_POST, 'date_fin');
        $statut = field_value($_POST, 'statut', 'actif');

        if (!in_array($statut, abonnement_statut_options(), true)) {
            $statut = 'actif';
        }

        // Les erreurs sont indexees par champ : le formulaire SaaS affiche le
        // message sous la saisie concernee, comme sur la fiche cabinet. Une
        // seule chaine concatenee dans une alerte en haut de page obligeait a
        // relire la liste pour retrouver le champ fautif.
        $fieldErrors = [];

        if ($cabinetId <= 0) {
            $fieldErrors['cabinet_id'] = 'Sélectionnez un cabinet.';
        } else {
            $exists = $db->prepare('SELECT id FROM cabinets WHERE id = :id');
            $exists->execute(['id' => $cabinetId]);
            if (!$exists->fetch()) {
                // Un cabinet forge ne doit jamais atteindre la table.
                $fieldErrors['cabinet_id'] = 'Ce cabinet est introuvable.';
            }
        }

        $plan = null;
        if ($planId !== null && $planId > 0) {
            $pstmt = $db->prepare('SELECT id, prix_annuel, devise, trial_jours, actif FROM plans WHERE id = :id');
            $pstmt->execute(['id' => $planId]);
            $plan = $pstmt->fetch();
            if (!$plan) {
                $fieldErrors['plan_id'] = 'Ce plan tarifaire est introuvable.';
            }
            // On permet d'editer un abonnement dont le plan est devenu inactif
            // (retrocompatibilite historique), mais on exige qu'il existe.
        }

        if ($dateDebut === '' || strtotime($dateDebut) === false) {
            $fieldErrors['date_debut'] = 'La date de début est obligatoire.';
        }
        if ($dateFin === '' || strtotime($dateFin) === false) {
            // Essai : si la fin n'est pas saisie, on la derive de la duree du plan.
            if ($statut === 'essai' && $dateDebut !== '' && strtotime($dateDebut) !== false && $plan !== null && (int) $plan['trial_jours'] > 0) {
                $dateFin = date('Y-m-d', strtotime($dateDebut . ' +' . (int) $plan['trial_jours'] . ' days'));
            }
            if ($dateFin === '' || strtotime($dateFin) === false) {
                $fieldErrors['date_fin'] = 'La date de fin est obligatoire.';
            }
        }

        if ($fieldErrors !== []) {
            $formOpen = true;
            $formData = array_merge($formData, $_POST);
        } else {
            $prix = money_value($_POST, 'prix_annuel_negocie');
            if ($prix === null) {
                $prix = $plan !== null ? (float) $plan['prix_annuel'] : 0.0;
            }

            $devise = strtoupper(field_value($_POST, 'devise', ''));
            if ($devise === '') {
                $devise = $plan !== null ? (string) $plan['devise'] : 'MAD';
            }

            $payload = [
                'cabinet_id' => $cabinetId,
                'plan_id' => $planId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'statut' => $statut,
                'prix_annuel_negocie' => number_format($prix, 2, '.', ''),
                'devise' => $devise,
                'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0,
                'notes' => field_value($_POST, 'notes'),
            ];

            $insertedId = 0;

            if ($targetId > 0) {
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE abonnements SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'abonnement', $targetId);
                set_flash('success', 'Abonnement mis à jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO abonnements (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $insertedId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'abonnement', $insertedId);
                set_flash('success', 'Abonnement enregistré.');
            }

            // Avertissement non bloquant : plusieurs abonnements en cours sur le
            // meme cabinet rendent current_abonnement_state() ambigu.
            $chevauchement = false;
            if ($targetId === 0 && $statut !== 'resilie') {
                $check = $db->prepare("SELECT COUNT(*) FROM abonnements WHERE cabinet_id = :cid AND statut <> 'resilie' AND id <> :id");
                $check->execute(['cid' => $cabinetId, 'id' => $insertedId]);
                $others = (int) $check->fetchColumn();
                if ($others > 0) {
                    $chevauchement = true;
                    set_flash('error', 'Ce cabinet a déjà ' . $others . ' autre(s) abonnement(s) non résilié(s). Le bandeau adhérents retiendra celui dont la date de fin est la plus éloignée.');
                }
            }

            // Abonnement neuf ET vivant : la porte de connexion acceptera les
            // comptes de ce cabinet. On enchaîne donc sur la creation de l'acces
            // plutot que de renvoyer l'admin dans une liste ou il doit
            // retrouver la ligne qu'il vient de creer. Un abonnement
            // chevauchant un autre reste renvoye vers la liste : le bandeau
            // adherents retiendra l'autre, et l'acces cree ici pourrait etre
            // refuse a la connexion.
            // La decision passe par abonnement_autorise_acces() : c'est la meme
            // regle que la porte de connexion, donc l'ecran ne peut pas proposer
            // d'ouvrir un compte que la connexion refuserait aussitot.
            $nouvelAboVivant = $targetId === 0
                && !$chevauchement
                && abonnement_autorise_acces([
                    'statut' => $statut,
                    'jours_restants' => abonnement_jours_restants(['date_fin' => $dateFin]),
                ]);

            if ($nouvelAboVivant && $canCreateAccess) {
                redirect_to('abonnements', ['acces' => $insertedId]);
            }

            redirect_to('abonnements');
        }
    }

    /* ------------------------------------------------------------------
     * Creation du compte administrateur du cabinet
     * ------------------------------------------------------------------
     *
     * L'identifiant de l'abonnement (donc le cabinet) transite par POST mais
     * n'est JAMAIS lu comme une cible libre : on recharge la ligne en base et
     * on en deduit le cabinet. Un `abonnement_id` forge ne permet donc pas
     * d'ouvrir un compte chez un tenant sans abonnement actif.
     */
    if ($action === 'create_access') {
        if (!$canCreateAccess) {
            require_permission('users.create');
        }

        $targetAboId = int_value($_POST, 'abonnement_id') ?? 0;
        $accesOuvert = true;
        $accesId = $targetAboId;

        if ($targetAboId <= 0 || !$db) {
            set_flash('error', 'Abonnement introuvable.');
            redirect_to('abonnements');
        }

        $stmt = $db->prepare('SELECT * FROM abonnements WHERE id = :id');
        $stmt->execute(['id' => $targetAboId]);
        $cible = $stmt->fetch();

        if (!$cible) {
            set_flash('error', 'Abonnement introuvable.');
            redirect_to('abonnements');
        }

        $statutAbo = (string) $cible['statut'];
        $etat = [
            'statut' => $statutAbo,
            'jours_restants' => abonnement_jours_restants($cible),
            'libelle' => abonnement_statut_label(abonnement_display_statut($cible)),
        ];

        if (!abonnement_autorise_acces($etat)) {
            set_flash('error', 'Impossible de creer un acces : cet abonnement est ' . strtolower(abonnement_statut_label(abonnement_display_statut($cible))) . '. Renouvelez-le d\'abord.');
            redirect_to('abonnements');
        }

        $cabinetCibleId = (int) $cible['cabinet_id'];

        // L'ecran ne propose le formulaire que si le cabinet n'a pas encore de
        // compte. On reverifie ici : `?acces=` se tape en dur et un formulaire
        // peut etre rejoue. Le message renvoie vers Utilisateurs, ou les
        // comptes supplementaires se gerent dans la limite du quota du plan.
        if (cabinet_a_un_compte($db, $cabinetCibleId)) {
            set_flash('error', 'Ce cabinet possède déjà un compte. Ajoutez d\'autres utilisateurs depuis la page Utilisateurs.');
            redirect_to('abonnements', ['acces' => $targetAboId]);
        }

        // Meme plafond que la creation d'un utilisateur ordinaire : un plan
        // qui n'autorise aucun utilisateur ne doit pas pouvoir se voir ouvrir
        // un acces par cette porte de côté.
        $quotaUsers = quota_state('utilisateurs', $db, $cabinetCibleId);
        if ($quotaUsers['atteint']) {
            set_flash('error', 'Quota utilisateurs atteint (' . $quotaUsers['utilise'] . '/' . (int) $quotaUsers['limite'] . '). Modifiez la formule du cabinet avant d\'ouvrir un accès.');
            redirect_to('abonnements');
        }

        $nomComplet = field_value($_POST, 'nom_complet');
        $email = field_value($_POST, 'email');
        $roleId = int_value($_POST, 'role_id') ?? 0;
        $motDePasseSaisi = field_value($_POST, 'mot_de_passe');

        $accesErrors = [];

        if ($nomComplet === '') {
            $accesErrors['nom_complet'] = 'Le nom complet est obligatoire.';
        }

        if ($email === '') {
            $accesErrors['email'] = 'L\'adresse email est obligatoire.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $accesErrors['email'] = 'Cette adresse email n\'est pas valide.';
        } elseif (user_email_existe($db, $email)) {
            // uq_users_email est UNIQUE sur toute la table : le dire sous le
            // champ evite une erreur SQL qui, elle, ne dirait pas quel compte
            // est en conflit.
            $accesErrors['email'] = 'Un compte utilise deja cette adresse email.';
        }

        if ($roleId <= 0 || !isset($rolesCabinet[$roleId])) {
            // Le select n'est pas une securite : le role doit exister ET etre
            // de scope 'cabinet' (cf. creer_acces_cabinet()).
            $accesErrors['role_id'] = 'Selectionnez un role de cabinet.';
        }

        if ($motDePasseSaisi !== '' && strlen($motDePasseSaisi) < 8) {
            $accesErrors['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }

        if ($accesErrors === []) {
            $cree = creer_acces_cabinet(
                $db,
                $cabinetCibleId,
                $nomComplet,
                $email,
                $roleId,
                $motDePasseSaisi !== '' ? $motDePasseSaisi : null
            );

            if ($cree !== null) {
                // Le mot de passe transite une seule fois par la session : il
                // est affiche sur l'ecran suivant puis detruit. Jamais stocke
                // en clair, jamais dans le journal d'activite.
                $_SESSION['_acces_cree'] = [
                    'nom_complet' => $nomComplet,
                    'email' => $cree['email'],
                    'mot_de_passe' => $cree['mot_de_passe'],
                ];

                log_activity($db, 'create', 'utilisateur', $cree['id'], $nomComplet, 'Acces cabinet cree pour l\'abonnement #' . $targetAboId);
                redirect_to('abonnements', ['acces' => $targetAboId]);
            }

            // L'ecran est rejoue, pas une page d'erreur : l'email peut avoir
            // ete pris entre la validation et l'INSERT, et le role avoir ete
            // desactive dans la foulure.
            $accesErrors['email'] = user_email_existe($db, $email)
                ? 'Un compte utilise deja cette adresse email.'
                : 'Ce role n\'est pas un role de cabinet.';
        }

        // Rehydratation du panneau apres un echec : on repart de la ligne
        // d'abonnement et du cabinet relus en base, pas de ce que le POST
        // transportait.
        $accesData = [
            'nom_complet' => $nomComplet,
            'email' => $email,
            'role_id' => (string) $roleId,
        ];

        $stmtC = $db->prepare('SELECT nom, code FROM cabinets WHERE id = :id');
        $stmtC->execute(['id' => $cabinetCibleId]);
        $cab = $stmtC->fetch() ?: [];

        $accesAbo = array_merge($cible, [
            'cabinet_nom' => (string) ($cab['nom'] ?? '-'),
            'cabinet_code' => (string) ($cab['code'] ?? ''),
            'plan_nom' => null,
        ]);

        $accesComptes = fetch_comptes_cabinet($db, $cabinetCibleId);
    }
}

$abonnements = [];
if ($db) {
    $baseSql = 'SELECT a.*, c.nom AS cabinet_nom, c.code AS cabinet_code, p.nom AS plan_nom, p.code AS plan_code
                FROM abonnements a
                LEFT JOIN cabinets c ON c.id = a.cabinet_id
                LEFT JOIN plans p ON p.id = a.plan_id';

    if ($query !== '') {
        $like = like_term($query);
        $stmt = $db->prepare($baseSql . '
            WHERE c.nom LIKE :t1 OR c.code LIKE :t2 OR p.nom LIKE :t3 OR a.statut LIKE :t4
            ORDER BY a.date_fin DESC, a.id DESC
        ');
        $stmt->execute(['t1' => $like, 't2' => $like, 't3' => $like, 't4' => $like]);
        $abonnements = $stmt->fetchAll();
    } else {
        $stmt = $db->prepare($baseSql . ' ORDER BY a.date_fin DESC, a.id DESC');
        $stmt->execute();
        $abonnements = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $r): array {
            return [
                $r['id'],
                (string) ($r['cabinet_code'] ?? '-') . ' ' . (string) ($r['cabinet_nom'] ?? '-'),
                $r['plan_nom'] ?? 'Sur mesure',
                format_date($r['date_debut'] ?? null),
                format_date($r['date_fin'] ?? null),
                abonnement_statut_label(abonnement_display_statut($r)),
                number_format((float) ($r['prix_annuel_negocie'] ?? 0), 2, ',', ' ') . ' ' . $r['devise'],
                (int) $r['auto_renew'] === 1 ? 'oui' : 'non',
            ];
        }, $abonnements);

        $headers = ['ID', 'Cabinet', 'Plan', 'Début', 'Fin', 'Statut', 'Prix annuel', 'Renouvellement auto'];

        if ($exportType === 'csv') {
            export_csv('abonnements.csv', $headers, $rows);
        } else {
            export_excel('abonnements.xlsx', $headers, $rows);
        }
    }
}

// Compteurs du bandeau de synthese.
$stats = ['actifs' => 0, 'essais' => 0, 'expires' => 0, 'suspendus' => 0, 'revenu' => 0.0];
foreach ($abonnements as $row) {
    $display = abonnement_display_statut($row);
    if ($display === 'actif') {
        $stats['actifs']++;
        $stats['revenu'] += (float) ($row['prix_annuel_negocie'] ?? 0);
    } elseif ($display === 'essai') {
        $stats['essais']++;
    } elseif ($display === 'expire') {
        $stats['expires']++;
    } elseif ($display === 'suspendu') {
        $stats['suspendus']++;
    }
}

$statutOptions = [];
foreach (abonnement_statut_options() as $s) {
    $statutOptions[$s] = abonnement_statut_label($s);
}

// Total hors recherche : le sous-titre de la liste annonce l'effectif de la
// table, comme sur la page cabinets. Cette vue est volontairement transverse
// (aucun cloisonnement cabinet), le compte porte donc sur toute la table.
$totalAbonnements = 0;
if ($db) {
    $totalAbonnements = (int) $db->query('SELECT COUNT(*) FROM abonnements')->fetchColumn();
}
$filtreActif = $query !== '';

// Aides de rendu du formulaire SaaS, memes conventions que la fiche cabinet :
// l'etat visuel est porte par l'enveloppe `.saas-field`, l announcing lecteur
// d'ecran par `aria-invalid`, et le message par `.saas-msg` sous la saisie.
$etat = static function (string $champ) use ($fieldErrors): string {
    return isset($fieldErrors[$champ]) ? ' is-error' : '';
};

$msg = static function (string $champ) use ($fieldErrors): string {
    if (!isset($fieldErrors[$champ])) {
        return '';
    }

    return '<small class="saas-msg saas-msg--error">'
        . '<span class="material-symbols-outlined">error</span>'
        . e($fieldErrors[$champ])
        . '</small>';
};

$invalide = static function (string $champ) use ($fieldErrors): string {
    return isset($fieldErrors[$champ]) ? ' aria-invalid="true"' : '';
};

// Mêmes aides pour le formulaire d'accès, sur son propre jeu d'erreurs
// (`$accesErrors`) : les deux formulaires ne doivent pas se contaminer.
$accesEtat = static function (string $champ) use ($accesErrors): string {
    return isset($accesErrors[$champ]) ? ' is-error' : '';
};

$accesMsg = static function (string $champ) use ($accesErrors): string {
    if (!isset($accesErrors[$champ])) {
        return '';
    }

    return '<small class="saas-msg saas-msg--error">'
        . '<span class="material-symbols-outlined">error</span>'
        . e($accesErrors[$champ])
        . '</small>';
};

$accesInvalide = static function (string $champ) use ($accesErrors): string {
    return isset($accesErrors[$champ]) ? ' aria-invalid="true"' : '';
};

// Cabinets de la liste qui possedent deja au moins un compte. Une seule
// requete pour toute la page : l'action « Créer un accès » n'a de sens que
// pour un cabinet sans compte, et une requete par ligne alourdit des que la
// liste depasse quelques dizaines d'abonnements.
$cabinetsAvecCompte = [];
if ($db && $abonnements !== []) {
    $ids = [];
    foreach ($abonnements as $row) {
        $cid = (int) ($row['cabinet_id'] ?? 0);
        if ($cid > 0) {
            $ids[$cid] = $cid;
        }
    }

    if ($ids !== []) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmtComptes = $db->prepare("SELECT DISTINCT cabinet_id FROM users WHERE cabinet_id IN ($marks)");
            $stmtComptes->execute(array_values($ids));
            foreach ($stmtComptes->fetchAll(PDO::FETCH_COLUMN) as $cid) {
                $cabinetsAvecCompte[(int) $cid] = true;
            }
        } catch (PDOException) {
            $cabinetsAvecCompte = [];
        }
    }
}
?>
<div class="saas-canvas">
    <div class="saas-page">

    <?php /* Le bandeau resume la liste : il s'efface pendant la saisie pour
            que le formulaire occupe le haut de l'ecran, exactement comme sur
            la page cabinets. Le compteur reste visible sur la liste elle-meme. */ ?>
    <?php if (!$formOpen && !$accesOuvert): ?>
        <section class="stats">
            <article class="stat">
                <span>Abonnements actifs</span>
                <strong><?= $stats['actifs'] ?></strong>
            </article>
            <article class="stat">
                <span>Essais en cours</span>
                <strong><?= $stats['essais'] ?></strong>
            </article>
            <article class="stat">
                <span>Expirés</span>
                <strong><?= $stats['expires'] ?></strong>
            </article>
            <article class="stat">
                <span>Suspendus</span>
                <strong><?= $stats['suspendus'] ?></strong>
            </article>
            <article class="stat">
                <span>Revenu annuel actif</span>
                <strong><?= e(number_format($stats['revenu'], 2, ',', ' ')) ?> <small style="font-size:1rem">MAD</small></strong>
            </article>
        </section>
    <?php endif; ?>

    <?php /* Troisième étape de la souscription : le compte du cabinet.
            L'identifiant de l'abonnement voyage en champ caché, mais le cabinet
            n'est jamais choisi ici — il découle de l'abonnement, donc un accès
            ne peut pas atterrir chez un tenant qui n'a pas souscrit. */ ?>
    <?php if ($accesOuvert && $accesAbo !== null && $canCreateAccess): ?>
        <form method="post" class="saas-form">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_access">
            <input type="hidden" name="abonnement_id" value="<?= e((string) $accesAbo['id']) ?>">

            <div class="saas-form__head">
                <div>
                    <h2 class="saas-form__title">
                        <span class="material-symbols-outlined">person_add</span>
                        Créer un accès
                    </h2>
                    <p class="saas-form__sub">
                        <?= e((string) ($accesAbo['cabinet_nom'] ?? '-')) ?>
                        <?php if (!empty($accesAbo['cabinet_code'])): ?>
                            <small>(<?= e((string) $accesAbo['cabinet_code']) ?>)</small>
                        <?php endif; ?>
                        &middot; <?= e(abonnement_statut_label(abonnement_display_statut($accesAbo))) ?>
                        <?php if (!empty($accesAbo['plan_nom'])): ?>
                            &middot; <?= e((string) $accesAbo['plan_nom']) ?>
                        <?php endif; ?>
                        &middot; jusqu'au <?= e(format_date($accesAbo['date_fin'] ?? null)) ?>
                    </p>
                </div>
                <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>">
                    <span class="material-symbols-outlined">close</span> Fermer
                </a>
            </div>

            <?php /* Affichage unique du mot de passe provisoire. Il n'est stocke
                    ni en base ni ailleurs : la session le porte le temps d'un
                    aller-retour, puis il est detruit. Le renvoyer dans le
                    journal d'activite le rendrait lisible a tout auditeur. */ ?>
            <?php if (is_array($accesCree) && !empty($accesCree['email'])): ?>
                <div class="saas-alert is-success" role="status">
                    <span class="material-symbols-outlined">check_circle</span>
                    <div>
                        <strong>Accès créé pour <?= e((string) $accesCree['email']) ?></strong>
                        <p>Communiquez ces identifiants au cabinet, puis cliquez sur « J'ai noté » pour les faire disparaître de cet écran.</p>
                        <p class="saas-card__note">
                            Adresse : <strong><?= e((string) $accesCree['email']) ?></strong><br>
                            Mot de passe provisoire : <code><?= e((string) ($accesCree['mot_de_passe'] ?? '')) ?></code>
                        </p>
                        <p class="saas-card__note">
                            Le mot de passe provisoire n'est plus affiché après ce message. Le titulaire devra le changer
                            à sa première connexion.
                        </p>
                        <a class="btn btn-info" href="<?= e(app_url('abonnements')) ?>">
                            <span class="material-symbols-outlined">visibility_off</span> J'ai noté
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($accesErrors !== []): ?>
                <div class="saas-alert is-error" role="alert">
                    <span class="material-symbols-outlined">error</span>
                    <div>
                        <strong><?= count($accesErrors) ?> champ(s) à corriger</strong>
                        <p>L'accès n'a pas été créé. Corrigez les champs en rouge ci-dessous.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($rolesCabinet === []): ?>
                <div class="saas-alert is-error" role="alert">
                    <span class="material-symbols-outlined">error</span>
                    <div>
                        Aucun rôle de cabinet n'existe en base. Créez d'abord un rôle
                        <strong>de type cabinet</strong> dans
                        <a href="<?= e(app_url('roles')) ?>">Configuration &rsaquo; Rôles</a> :
                        un rôle du Centre donnerait au cabinet un accès transverse à tous les tenants.
                    </div>
                </div>
            <?php elseif ($accesComptes !== []): ?>
                <?php /* Garde-fou du `?acces=` tapé en dur : le bouton n'est pas
                        proposé une fois le compte ouvert, mais on ne peut pas
                        empêcher l'URL. */ ?>
                <div class="saas-alert is-warning">
                    <span class="material-symbols-outlined">warning</span>
                    <div>
                        <strong>Ce cabinet possède déjà un compte</strong>
                        <p>Un seul accès administrateur est ouvert par cabinet. Pour en créer un autre, utilisez
                            <a href="<?= e(app_url('utilisateurs', ['q' => (string) ($accesAbo['cabinet_code'] ?? '')])) ?>">Utilisateurs</a>.</p>
                    </div>
                </div>

                <div class="saas-form__grid">
                    <section class="saas-card" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">group</span>
                            <h3>Comptes du cabinet</h3>
                        </header>
                        <div class="saas-card__body">
                            <ul class="saas-list">
                                <?php foreach ($accesComptes as $compte): ?>
                                    <li>
                                        <strong><?= e((string) ($compte['nom_complet'] ?? '-')) ?></strong>
                                        <small><?= e((string) ($compte['email'] ?? '')) ?></small>
                                        &middot; <?= e((string) ($compte['role_nom'] ?? '-')) ?>
                                        &middot; <span class="badge badge-<?= (string) ($compte['statut'] ?? '') === 'actif' ? 'success' : 'secondary' ?>"><?= e(cabinet_statut_label((string) ($compte['statut'] ?? ''))) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                </div>
            <?php else: ?>
                <div class="saas-alert is-info">
                    <span class="material-symbols-outlined">info</span>
                    <div>
                        Cet abonnement autorise l'accès. Créez le compte de l'adhérent : il sera rattaché à
                        <strong><?= e((string) ($accesAbo['cabinet_nom'] ?? '-')) ?></strong> et son mot de passe devra être
                        changé à la première connexion.
                    </div>
                </div>

                <div class="saas-form__grid">
                    <section class="saas-card" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">badge</span>
                            <h3>Identité de l'adhérent</h3>
                            <button type="button" class="saas-card__toggle" data-saas-toggle
                                    aria-expanded="true" aria-controls="acces-corps-identite">
                                <span class="material-symbols-outlined">expand_more</span>
                                <span class="saas-card__sr">Replier la section Identité de l'adhérent</span>
                            </button>
                        </header>

                        <div class="saas-card__body" id="acces-corps-identite">
                            <p class="saas-card__note">
                                Le compte est créé actif, rattaché à ce cabinet, avec un mot de passe provisoire.
                            </p>

                            <label class="saas-field<?= $accesEtat('nom_complet') ?>">
                                <span class="saas-field__label">Nom complet <em class="req-mark">*</em></span>
                                <input type="text" name="nom_complet" required
                                       value="<?= e((string) ($accesData['nom_complet'] ?? '')) ?>"
                                       placeholder="Ex. Amrani Salma"<?= $accesInvalide('nom_complet') ?>>
                                <?= $accesMsg('nom_complet') ?>
                            </label>

                            <label class="saas-field<?= $accesEtat('email') ?>">
                                <span class="saas-field__label">Adresse email <em class="req-mark">*</em></span>
                                <input type="email" name="email" required
                                       value="<?= e((string) ($accesData['email'] ?? '')) ?>"
                                       placeholder="contact@cabinet.ma"<?= $accesInvalide('email') ?>>
                                <small class="saas-field__hint">Identifiant de connexion, unique dans toute la plateforme</small>
                                <?= $accesMsg('email') ?>
                            </label>

                            <label class="saas-field<?= $accesEtat('mot_de_passe') ?>">
                                <span class="saas-field__label">Mot de passe <small>facultatif</small></span>
                                <input type="text" name="mot_de_passe" autocomplete="off"
                                       value="" placeholder="Généré automatiquement"
                                       aria-describedby="hint-mdp-acces"<?= $accesInvalide('mot_de_passe') ?>>
                                <small class="saas-field__hint" id="hint-mdp-acces">
                                    Laissez vide pour obtenir un mot de passe à 14 caractères. S'il est saisi, 8 caractères minimum.
                                </small>
                                <?= $accesMsg('mot_de_passe') ?>
                            </label>
                        </div>
                    </section>

                    <section class="saas-card" data-saas-card>
                        <header class="saas-card__head">
                            <span class="saas-card__icon material-symbols-outlined">admin_panel_settings</span>
                            <h3>Rôle et périmètre</h3>
                            <button type="button" class="saas-card__toggle" data-saas-toggle
                                    aria-expanded="true" aria-controls="acces-corps-role">
                                <span class="material-symbols-outlined">expand_more</span>
                                <span class="saas-card__sr">Replier la section Rôle et périmètre</span>
                            </button>
                        </header>

                        <div class="saas-card__body" id="acces-corps-role">
                            <label class="saas-field<?= $accesEtat('role_id') ?>">
                                <span class="saas-field__label">Rôle <em class="req-mark">*</em></span>
                                <select name="role_id" required<?= $accesInvalide('role_id') ?>>
                                    <?php foreach ($rolesCabinet as $rid => $rnom): ?>
                                        <option value="<?= e((string) $rid) ?>"<?= (int) ($accesData['role_id'] ?? 0) === (int) $rid ? ' selected' : '' ?>><?= e($rnom) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="saas-field__hint">Seuls les rôles de type <em>cabinet</em> sont proposés</small>
                                <?= $accesMsg('role_id') ?>
                            </label>

                            <p class="saas-card__note">
                                Le compte est rattaché à
                                <strong><?= e((string) ($accesAbo['cabinet_nom'] ?? '-')) ?></strong> et ne verra que ses propres dossiers.
                            </p>
                        </div>
                    </section>
                </div>

                <div class="saas-form__footer">
                    <span class="saas-form__legend">
                        <em class="req-mark">*</em> Champs obligatoires
                    </span>
                    <div class="saas-form__buttons">
                        <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>">
                            <span class="material-symbols-outlined">close</span> Annuler
                        </a>
                        <button class="btn btn-next" type="submit">
                            <span class="material-symbols-outlined">person_add</span> Créer l'accès
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>

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
                        <span class="material-symbols-outlined"><?= $editId > 0 ? 'edit' : 'add_card' ?></span>
                        <?= $editId > 0 ? 'Modifier l\'abonnement' : 'Nouvel abonnement' ?>
                    </h2>
                    <p class="saas-form__sub">
                        <?= $editId > 0
                            ? 'Mettez à jour la période, le statut et la tarification de cet abonnement.'
                            : 'Rattachez un plan tarifaire à un cabinet client pour la période concernée.' ?>
                    </p>
                </div>
                <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>">
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

            <?php if ($cabinetOptions === []): ?>
                <div class="saas-alert is-warning">
                    <span class="material-symbols-outlined">warning</span>
                    <div>
                        Aucun cabinet n'est enregistré. <a href="<?= e(app_url('cabinets', ['action' => 'new'])) ?>">Créez d'abord un cabinet</a> pour pouvoir y rattacher un abonnement.
                    </div>
                </div>
            <?php elseif ($planOptions === []): ?>
                <div class="saas-alert is-warning">
                    <span class="material-symbols-outlined">warning</span>
                    <div>
                        Aucun plan tarifaire actif. <a href="<?= e(app_url('plans', ['action' => 'new'])) ?>">Créez d'abord un plan</a>, ou enregistrez cet abonnement sur mesure en laissant le plan vide.
                    </div>
                </div>
            <?php endif; ?>

            <div class="saas-form__grid">

                <!-- 1 — Cabinet et plan -->
                <section class="saas-card" data-saas-card>
                    <header class="saas-card__head">
                        <span class="saas-card__icon material-symbols-outlined">link</span>
                        <h3>Cabinet et plan</h3>
                        <button type="button" class="saas-card__toggle" data-saas-toggle
                                aria-expanded="true" aria-controls="abo-corps-rattachement">
                            <span class="material-symbols-outlined">expand_more</span>
                            <span class="saas-card__sr">Replier la section Cabinet et plan</span>
                        </button>
                    </header>

                    <div class="saas-card__body" id="abo-corps-rattachement">
                        <p class="saas-card__note">
                            Le plan porte le prix annuel et la durée d'essai. Laissez-le vide pour un abonnement sur mesure.
                        </p>

                        <label class="saas-field<?= $etat('cabinet_id') ?>">
                            <span class="saas-field__label">Cabinet <em class="req-mark">*</em></span>
                            <select name="cabinet_id" required<?= $cabinetOptions === [] ? ' disabled' : '' ?><?= $invalide('cabinet_id') ?>>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($cabinetOptions as $id => $lbl): ?>
                                    <option value="<?= e((string) $id) ?>"<?= (int) ($formData['cabinet_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $msg('cabinet_id') ?>
                        </label>

                        <label class="saas-field<?= $etat('plan_id') ?>">
                            <span class="saas-field__label">Plan <small>facultatif</small></span>
                            <select name="plan_id"<?= $invalide('plan_id') ?>>
                                <option value="">Sur mesure (sans plan)</option>
                                <?php foreach ($planOptions as $id => $lbl): ?>
                                    <option value="<?= e((string) $id) ?>"<?= (int) ($formData['plan_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $msg('plan_id') ?>
                        </label>
                    </div>
                </section>

                <!-- 2 — Periode de validite -->
                <section class="saas-card" data-saas-card>
                    <header class="saas-card__head">
                        <span class="saas-card__icon material-symbols-outlined">date_range</span>
                        <h3>Période de validité</h3>
                        <button type="button" class="saas-card__toggle" data-saas-toggle
                                aria-expanded="true" aria-controls="abo-corps-periode">
                            <span class="material-symbols-outlined">expand_more</span>
                            <span class="saas-card__sr">Replier la section Période de validité</span>
                        </button>
                    </header>

                    <div class="saas-card__body" id="abo-corps-periode">
                        <p class="saas-card__note">
                            La période sert au calcul des jours restants et au renouvellement.
                        </p>

                        <label class="saas-field<?= $etat('date_debut') ?>">
                            <span class="saas-field__label">Date de début <em class="req-mark">*</em></span>
                            <input type="date" name="date_debut" required
                                   value="<?= e((string) ($formData['date_debut'] ?? date('Y-m-d'))) ?>"
                                   aria-describedby="hint-date-debut"<?= $invalide('date_debut') ?>>
                            <small class="saas-field__hint" id="hint-date-debut">Point de départ de la période facturée</small>
                            <?= $msg('date_debut') ?>
                        </label>

                        <label class="saas-field<?= $etat('date_fin') ?>">
                            <span class="saas-field__label">Date de fin</span>
                            <input type="date" name="date_fin"
                                   value="<?= e((string) ($formData['date_fin'] ?? '')) ?>"
                                   aria-describedby="hint-date-fin"<?= $invalide('date_fin') ?>>
                            <small class="saas-field__hint" id="hint-date-fin">Obligatoire, sauf essai : déduite du plan</small>
                            <?= $msg('date_fin') ?>
                        </label>

                        <label class="saas-field">
                            <span class="saas-field__label">Statut</span>
                            <select name="statut">
                                <?php foreach ($statutOptions as $val => $lbl): ?>
                                    <option value="<?= e($val) ?>"<?= (string) ($formData['statut'] ?? 'actif') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="saas-field saas-field--check">
                            <span class="saas-field__label">Renouvellement automatique</span>
                            <span class="saas-field__box">
                                <input type="checkbox" name="auto_renew" value="1"<?= (int) ($formData['auto_renew'] ?? 1) === 1 ? ' checked' : '' ?>>
                                <span class="saas-field__hint">Prolonge de 12 mois à l'échéance</span>
                            </span>
                        </label>
                    </div>
                </section>

                <!-- 3 — Tarification -->
                <section class="saas-card" data-saas-card>
                    <header class="saas-card__head">
                        <span class="saas-card__icon material-symbols-outlined">payments</span>
                        <h3>Tarification</h3>
                        <button type="button" class="saas-card__toggle" data-saas-toggle
                                aria-expanded="true" aria-controls="abo-corps-tarification">
                            <span class="material-symbols-outlined">expand_more</span>
                            <span class="saas-card__sr">Replier la section Tarification</span>
                        </button>
                    </header>

                    <div class="saas-card__body" id="abo-corps-tarification">
                        <p class="saas-card__note">
                            Prix négocié hors taxes. Vide : le tarif du plan est repris à l'enregistrement.
                        </p>

                        <label class="saas-field saas-field--mono<?= $etat('prix_annuel_negocie') ?>">
                            <span class="saas-field__label">Prix annuel négocié</span>
                            <input type="text" name="prix_annuel_negocie" inputmode="decimal" data-decimal
                                   value="<?= e((string) ($formData['prix_annuel_negocie'] ?? '')) ?>"
                                   placeholder="Hérité du plan"<?= $invalide('prix_annuel_negocie') ?>>
                            <?= $msg('prix_annuel_negocie') ?>
                        </label>

                        <label class="saas-field saas-field--mono">
                            <span class="saas-field__label">Devise</span>
                            <input type="text" name="devise" maxlength="3"
                                   value="<?= e((string) ($formData['devise'] ?? 'MAD')) ?>"
                                   placeholder="MAD">
                        </label>
                    </div>
                </section>

                <!-- 4 — Notes -->
                <section class="saas-card" data-saas-card>
                    <header class="saas-card__head">
                        <span class="saas-card__icon material-symbols-outlined">notes</span>
                        <h3>Notes</h3>
                        <button type="button" class="saas-card__toggle" data-saas-toggle
                                aria-expanded="true" aria-controls="abo-corps-notes">
                            <span class="material-symbols-outlined">expand_more</span>
                            <span class="saas-card__sr">Replier la section Notes</span>
                        </button>
                    </header>

                    <div class="saas-card__body" id="abo-corps-notes">
                        <p class="saas-card__note">
                            Conditions négociées, référence de contrat, points d'attention.
                        </p>

                        <label class="saas-field saas-field--wide">
                            <span class="saas-field__label">Notes internes</span>
                            <textarea name="notes" rows="3" placeholder="Conditions négociées, référence de contrat, points d'attention…"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
                        </label>
                    </div>
                </section>
            </div>

            <div class="saas-form__footer">
                <span class="saas-form__legend">
                    <em class="req-mark">*</em> Champs obligatoires
                </span>
                <div class="saas-form__buttons">
                    <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>">
                        <span class="material-symbols-outlined">close</span> Annuler
                    </a>
                    <?php if ($editId > 0): ?>
                        <button class="btn btn-next" type="submit">
                            <span class="material-symbols-outlined">save</span> Mettre à jour
                        </button>
                    <?php else: ?>
                        <button class="btn btn-next" type="submit">
                            <span class="material-symbols-outlined">add</span> Créer l'abonnement
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <!-- Titre de la liste. Meme construction que l'en-tete du formulaire et
         que la page cabinets : icone + titre + sous-titre a gauche, exports a
         droite. Le total vient de la table entiere, le filtre ne porte que sur
         le chiffre affiche. -->
    <div class="saas-form__head">
        <div>
            <h2 class="saas-form__title">
                <span class="material-symbols-outlined">list_alt</span>
                Liste des abonnements
            </h2>
            <p class="saas-form__sub">
                <strong><?= $totalAbonnements ?></strong> abonnement<?= $totalAbonnements > 1 ? 's' : '' ?> au total
                <?php if ($filtreActif): ?>
                    &middot; <strong><?= count($abonnements) ?></strong> affich&eacute;<?= count($abonnements) > 1 ? 's' : '' ?> par le filtre
                <?php endif; ?>
                &middot; filtre disponible : recherche libre
            </p>
        </div>
        <div class="table-actions">
            <a class="btn btn-info" href="<?= e(app_url('abonnements', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
            <a class="btn btn-info" href="<?= e(app_url('abonnements', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
        </div>
    </div>

    <article class="card saas-table">
        <form method="get" class="search-bar">
            <input type="hidden" name="page" value="abonnements">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par cabinet, code, plan ou statut" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('abonnements')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$abonnements): ?>
            <p class="table-empty">
                <?= $query !== ''
                    ? 'Aucun abonnement ne correspond à cette recherche.'
                    : 'Aucun abonnement enregistré. Créez un cabinet puis souscrivez son premier abonnement.' ?>
            </p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="abonnements">
                    <thead>
                        <tr>
                            <th data-col="cabinet">Cabinet</th>
                            <th data-col="plan">Plan</th>
                            <th data-col="debut">Début</th>
                            <th data-col="fin">Fin</th>
                            <th data-col="restants">Jours restants</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="prix">Prix annuel</th>
                            <th data-col="renew">Renouvellement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($abonnements as $abo): ?>
                        <?php
                        $display = abonnement_display_statut($abo);
                        $jours = abonnement_jours_restants($abo);
                        ?>
                        <tr>
                            <td>
                                <strong><?= e((string) ($abo['cabinet_nom'] ?? '-')) ?></strong>
                                <br><small><?= e((string) ($abo['cabinet_code'] ?? '')) ?></small>
                            </td>
                            <td><?= e((string) ($abo['plan_nom'] ?? 'Sur mesure')) ?></td>
                            <td><?= e(format_date($abo['date_debut'] ?? null)) ?></td>
                            <td><?= e(format_date($abo['date_fin'] ?? null)) ?></td>
                            <td>
                                <?php if ($jours === null): ?>
                                    -
                                <?php elseif ($jours < 0): ?>
                                    <span style="color:var(--danger)">expire depuis <?= abs($jours) ?> j</span>
                                <?php else: ?>
                                    <?= $jours ?> j
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= e(abonnement_statut_tone($display)) ?>"><?= e(abonnement_statut_label($display)) ?></span></td>
                            <td><?= e(number_format((float) ($abo['prix_annuel_negocie'] ?? 0), 2, ',', ' ')) ?> <?= e((string) $abo['devise']) ?></td>
                            <td><?= (int) $abo['auto_renew'] === 1 ? 'Auto' : 'Manuel' ?></td>
                            <td class="table-actions">
                                <?php /* L'action n'apparait que sur un abonnement
                                        qui autorise l'accès ET pour un cabinet sans
                                        compte : c'est la troisième et dernière étape
                                        de la souscription, elle n'a plus d'objet une
                                        fois le compte ouvert. */ ?>
                                <?php if ($canCreateAccess && !$accesOuvert): ?>
                                    <?php
                                    $accesAutorise = abonnement_autorise_acces([
                                        'statut' => $display === 'expire' ? 'actif' : (string) $abo['statut'],
                                        'jours_restants' => $jours,
                                    ]);
                                    $peutCreerAcces = $accesAutorise
                                        && !isset($cabinetsAvecCompte[(int) $abo['cabinet_id']]);
                                    ?>
                                    <?php if ($peutCreerAcces): ?>
                                        <a class="btn-icon primary" href="<?= e(app_url('abonnements', ['acces' => (int) $abo['id']])) ?>" title="Créer le compte de ce cabinet"><span class="material-symbols-outlined">person_add</span></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($canEdit): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="renew">
                                        <input type="hidden" name="id" value="<?= e((string) $abo['id']) ?>">
                                        <button class="btn-icon primary" type="submit" data-confirm="Prolonger cet abonnement d'un an ?" data-confirm-title="Renouveler l'abonnement" data-confirm-ok="Renouveler" data-confirm-tone="primary" title="Renouveler d'un an"><span class="material-symbols-outlined">autorenew</span></button>
                                    </form>
                                    <a class="btn-icon info" href="<?= e(app_url('abonnements', ['edit' => (int) $abo['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $abo['id']) ?>">
                                        <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet abonnement ?" data-confirm-title="Supprimer l'abonnement" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
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
</div>
