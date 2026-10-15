<?php

declare(strict_types=1);

/**
 * Administration SaaS - Fiche d'un compte de connexion (`users`), en lecture,
 * creation ou edition.
 *
 * Trois points structurels :
 *
 *  1. Le mot de passe ne s'ecrit JAMAIS en clair : il est hache au moment ou on
 *     le pose (`password_hash`) et n'est plus recuperable. L'ecran ne peut donc
 *     que le remplacer ; le mot de passe genere est montre une seule fois,
 *     via `$_SESSION['_compte_mdp_temporaire']` - exactement le mecanisme deja
 *     utilise par `pages/abonnement/abonnements.php` (`_acces_cree`).
 *
 *  2. `get_user_permissions()` fait l'UNION de `user_roles` et de `users.role_id`.
 *     Ne changer que `role_id` laisserait donc l'ancien role actif via le pivot :
 *     les deux ecritures sont faites dans une meme transaction, comme le fait
 *     deja `creer_acces_cabinet()`.
 *
 *  3. `users` n'est pas dans `tenant_scoped_tables()` : la garde anti-IDOR est
 *     faite ici, a la lecture ET sur chaque POST. Un compte de cabinet ne voit
 *     ni n'edite que les comptes de son propre cabinet.
 */

$editingId = (int) ($_GET['id'] ?? 0);
$creation = $editingId <= 0;

$canCreate = has_permission('users.create');
$canEdit = has_permission('users.edit');
$canDelete = has_permission('users.delete');

$db = ($pdo ?? null) instanceof PDO ? $pdo : null;
$monId = (int) ($_SESSION['user_id'] ?? 0);
$monCabinetId = current_cabinet_id();
$estCentreAdmin = is_centre_admin();

$pageTitle = $creation ? 'Nouvel utilisateur' : 'Fiche utilisateur';
$pageSubtitle = $creation
    ? "Ouvrir un compte de connexion. Le mot de passe provisoire est affiché une seule fois."
    : "Login, rôle et mot de passe du compte. Le mot de passe actuel n'est jamais affichable.";

/* ---------------------------------------------------------------------------
 * Lecture du compte cible + garde anti-IDOR.
 * ------------------------------------------------------------------------ */
$compte = null;
if (!$creation) {
    if (!$db) {
        set_flash('error', 'Erreur de connexion a la base de donnees.');
        redirect_to('utilisateurs');
    }

    try {
        $st = $db->prepare('
            SELECT u.id, u.nom_complet, u.email, u.telephone, u.statut, u.role_id,
                   u.cabinet_id, u.must_change_password, u.last_login, u.created_at,
                   u.collaborateur_id, r.nom AS role_nom, r.scope AS role_scope,
                   cb.code AS cabinet_code, cb.nom AS cabinet_nom
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN cabinets cb ON cb.id = u.cabinet_id
            WHERE u.id = :id
            LIMIT 1
        ');
        $st->execute(['id' => $editingId]);
        $compte = $st->fetch() ?: null;
    } catch (PDOException) {
        $compte = null;
    }

    if ($compte === null) {
        set_flash('error', 'Compte introuvable.');
        redirect_to('utilisateurs');
    }

    if ($monCabinetId !== null && (int) $compte['cabinet_id'] !== $monCabinetId) {
        set_flash('error', 'Ce compte appartient a un autre cabinet.');
        redirect_to('utilisateurs');
    }
}

/* ---------------------------------------------------------------------------
 * Valeurs du formulaire, puis traitement du POST.
 * ------------------------------------------------------------------------ */
$formData = [
    'nom_complet' => $compte !== null ? (string) $compte['nom_complet'] : '',
    'email' => $compte !== null ? (string) $compte['email'] : '',
    'telephone' => $compte !== null ? (string) ($compte['telephone'] ?? '') : '',
    'role_id' => $compte !== null && $compte['role_id'] !== null ? (string) $compte['role_id'] : '',
    'statut' => $compte !== null ? (string) $compte['statut'] : 'actif',
    'cabinet_id' => $compte !== null && $compte['cabinet_id'] !== null ? (string) $compte['cabinet_id'] : '',
    'must_change_password' => $compte !== null && (int) $compte['must_change_password'] === 1 ? '1' : '',
    'mot_de_passe' => '',
];
$fieldErrors = [];

$marquer = static function (string $champ, string $message) use (&$fieldErrors): void {
    $fieldErrors[$champ] ??= $message;
};

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    /* --- Enregistrement de l'identite et de l'acces --------------------- */
    if ($action === 'save') {
        $formData['nom_complet'] = field_value($_POST, 'nom_complet');
        $formData['email'] = field_value($_POST, 'email');
        $formData['telephone'] = field_value($_POST, 'telephone');
        $formData['role_id'] = field_value($_POST, 'role_id');
        $formData['statut'] = field_value($_POST, 'statut');
        $formData['cabinet_id'] = (string) (int_value($_POST, 'cabinet_id') ?? 0);
        $formData['must_change_password'] = isset($_POST['must_change_password']) ? '1' : '';
        $formData['mot_de_passe'] = field_value($_POST, 'mot_de_passe');

        if ($creation ? !$canCreate : !$canEdit) {
            $fieldErrors['global'] = "Vous n'avez pas les droits nécessaires pour enregistrer.";
        } else {
            $nom = $formData['nom_complet'];
            $email = $formData['email'];
            $roleId = int_value($_POST, 'role_id') ?? 0;
            $cabinetSaisi = int_value($_POST, 'cabinet_id');
            // Perimetre effectif du compte : impose par l'editeur s'il est lui-meme
            // un cabinet, sinon choisi dans le formulaire ('' = Centre).
            $cabinetCible = $monCabinetId !== null ? $monCabinetId : ($cabinetSaisi !== null && $cabinetSaisi > 0 ? $cabinetSaisi : null);

            if ($nom === '') {
                $marquer('nom_complet', 'Le nom complet est obligatoire.');
            }
            if ($email === '') {
                $marquer('email', 'Le login (email) est obligatoire.');
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $marquer('email', 'Ce login n\'est pas une adresse email valide.');
            } elseif (user_email_existe($db, $email, $creation ? null : $editingId)) {
                $marquer('email', 'Ce login est déjà utilisé par un autre compte.');
            }

            $role = null;
            if ($roleId <= 0) {
                $marquer('role_id', 'Le rôle est obligatoire.');
            } else {
                try {
                    $stRole = $db->prepare('SELECT id, scope, is_system, nom FROM roles WHERE id = :id LIMIT 1');
                    $stRole->execute(['id' => $roleId]);
                    $role = $stRole->fetch() ?: null;
                } catch (PDOException) {
                    $role = null;
                }
                if ($role === null) {
                    $marquer('role_id', 'Rôle introuvable.');
                }
            }

            if ($monCabinetId !== null && $cabinetSaisi !== null && $cabinetSaisi !== $monCabinetId) {
                $marquer('cabinet_id', 'Vous ne pouvez gérer que les comptes de votre cabinet.');
            }

            if ($role !== null) {
                // Meme regle que creer_acces_cabinet() : un compte rattache a un
                // cabinet ne peut porter qu'un role de scope 'cabinet', sinon il
                // obtient un acces transverse a tous les tenants. Le controle est
                // ici et non dans le <select>, qui est une donnee d'attaque.
                if ($cabinetCible !== null && (string) $role['scope'] !== 'cabinet') {
                    $marquer('role_id', "Ce rôle est réservé aux comptes du Centre : un compte de cabinet ne peut pas en porter un.");
                }
                if ((int) $role['is_system'] === 1 && !$estCentreAdmin) {
                    $marquer('role_id', 'Seul un administrateur système peut attribuer ce rôle.');
                }
            }

            if (!in_array($formData['statut'], ['actif', 'suspendu'], true)) {
                $marquer('statut', 'Statut invalide.');
            }

            if ($creation) {
                if ($formData['mot_de_passe'] !== '' && mb_strlen($formData['mot_de_passe']) < 8) {
                    $marquer('mot_de_passe', 'Le mot de passe doit contenir au moins 8 caractères.');
                }
            } else {
                // Un compte ne se fragilise pas lui-meme : changer son propre role,
                // son propre statut ou son propre cabinet revient a pouvoir se
                // reattribuer des droits - ou a se fermer l'acces a la plateforme.
                if ($editingId === $monId) {
                    if ($roleId !== (int) ($compte['role_id'] ?? 0)) {
                        $marquer('role_id', 'Vous ne pouvez pas modifier votre propre rôle.');
                    }
                    if ($formData['statut'] !== (string) $compte['statut']) {
                        $marquer('statut', 'Vous ne pouvez pas modifier votre propre statut.');
                    }
                    if ($cabinetCible !== ($compte['cabinet_id'] !== null ? (int) $compte['cabinet_id'] : null)) {
                        $marquer('cabinet_id', 'Vous ne pouvez pas modifier votre propre périmètre.');
                    }
                }
            }

            if ($fieldErrors === []) {
                $transactionOuverte = !$db->inTransaction();
                try {
                    if ($transactionOuverte) {
                        $db->beginTransaction();
                    }

                    if ($creation) {
                        // Tout mot de passe pose par un administrateur est
                        // provisoire : le titulaire en choisit le sien a la
                        // premiere connexion, comme dans creer_acces_cabinet().
                        $motDePasse = $formData['mot_de_passe'] !== ''
                            ? $formData['mot_de_passe']
                            : generer_mot_de_passe_provisoire();

                        $db->prepare('
                            INSERT INTO users
                                (nom_complet, email, password_hash, telephone, cabinet_id, role_id, statut, must_change_password)
                            VALUES (:nom, :email, :hash, :tel, :cid, :rid, :statut, 1)
                        ')->execute([
                            'nom' => $nom,
                            'email' => $email,
                            'hash' => password_hash($motDePasse, PASSWORD_DEFAULT),
                            'tel' => $formData['telephone'] !== '' ? $formData['telephone'] : null,
                            'cid' => $cabinetCible,
                            'rid' => $roleId,
                            'statut' => $formData['statut'],
                        ]);
                        $editingId = (int) $db->lastInsertId();

                        $db->prepare('INSERT INTO user_roles (user_id, role_id, is_primary) VALUES (:uid, :rid, 1)')
                            ->execute(['uid' => $editingId, 'rid' => $roleId]);

                        if ($formData['mot_de_passe'] === '') {
                            $_SESSION['_compte_mdp_temporaire'] = [
                                'id' => $editingId,
                                'email' => $email,
                                'mot_de_passe' => $motDePasse,
                                'genere' => true,
                            ];
                        }
                    } else {
                        $db->prepare('
                            UPDATE users
                            SET nom_complet = :nom, email = :email, telephone = :tel,
                                cabinet_id = :cid, role_id = :rid, statut = :statut,
                                must_change_password = :mcp
                            WHERE id = :id
                        ')->execute([
                            'nom' => $nom,
                            'email' => $email,
                            'tel' => $formData['telephone'] !== '' ? $formData['telephone'] : null,
                            'cid' => $cabinetCible,
                            'rid' => $roleId,
                            'statut' => $formData['statut'],
                            'mcp' => $formData['must_change_password'] === '1' ? 1 : 0,
                            'id' => $editingId,
                        ]);

                        // Pivot et role primaire doivent bouger ensemble : les deux
                        // sont retenus en union par get_user_permissions(), laisser
                        // l'ancien en place garderait ses droits au compte.
                        $db->prepare('DELETE FROM user_roles WHERE user_id = :uid')
                            ->execute(['uid' => $editingId]);
                        $db->prepare('INSERT INTO user_roles (user_id, role_id, is_primary) VALUES (:uid, :rid, 1)')
                            ->execute(['uid' => $editingId, 'rid' => $roleId]);

                        if ($editingId === $monId) {
                            // Le compte edite lui-meme : son propre cache de session
                            // devient faux des la ligne suivante.
                            clear_user_cache();
                        }
                    }

                    if ($transactionOuverte) {
                        $db->commit();
                    }
                } catch (PDOException $e) {
                    if ($transactionOuverte && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    // uq_users_email : la verification ci-dessus a echoue (course
                    // ou casse insensible de la collation), on la rend lisible.
                    $marquer('email', 'Ce login est déjà utilisé par un autre compte.');
                    unset($fieldErrors['global']);
                    $fieldErrors['global'] = 'Enregistrement impossible : ' . $e->getMessage();
                }

                if ($fieldErrors === []) {
                    log_activity(
                        $db,
                        $creation ? 'create' : 'update',
                        'user',
                        $editingId,
                        $nom,
                        'role_id=' . $roleId . ', statut=' . $formData['statut']
                    );
                    set_flash('success', $creation ? 'Compte créé.' : 'Compte mis à jour.');
                    redirect_to('utilisateur', ['id' => $editingId]);
                }
            }
        }
    }

    /* --- Reinitialisation du mot de passe ------------------------------ */
    if ($action === 'reset_password') {
        if ($creation) {
            $fieldErrors['nouveau_mot_de_passe'] = 'Enregistrez d\'abord le compte.';
        } elseif (!$canEdit && $editingId !== $monId) {
            $fieldErrors['nouveau_mot_de_passe'] = "Vous n'avez pas les droits nécessaires.";
        } else {
            $nouveau = field_value($_POST, 'nouveau_mot_de_passe');
            if ($nouveau !== '' && mb_strlen($nouveau) < 8) {
                $fieldErrors['nouveau_mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caractères.';
            } else {
                $motDePasse = $nouveau !== '' ? $nouveau : generer_mot_de_passe_provisoire();

                try {
                    $db->prepare('
                        UPDATE users SET password_hash = :hash, must_change_password = 1
                        WHERE id = :id
                    ')->execute([
                        'hash' => password_hash($motDePasse, PASSWORD_DEFAULT),
                        'id' => $editingId,
                    ]);

                    // Les sessions enregistrees du compte sont retireses : le
                    // compte disparait des « utilisateurs en ligne » et son
                    // prochain passage par la page de connexion regenere son etat.
                    $db->prepare('DELETE FROM user_sessions WHERE user_id = :id')
                        ->execute(['id' => $editingId]);

                    if ($editingId === $monId) {
                        clear_user_cache();
                        purge_user_session($db);
                    }

                    $_SESSION['_compte_mdp_temporaire'] = [
                        'id' => $editingId,
                        'email' => (string) $compte['email'],
                        'mot_de_passe' => $motDePasse,
                        'genere' => $nouveau === '',
                    ];

                    log_activity($db, 'update', 'user', $editingId, (string) $compte['nom_complet'], 'mot de passe reinitialise');
                    set_flash('success', 'Mot de passe réinitialisé.');
                    redirect_to('utilisateur', ['id' => $editingId]);
                } catch (PDOException $e) {
                    $fieldErrors['nouveau_mot_de_passe'] = 'Réinitialisation impossible : ' . $e->getMessage();
                }
            }
        }
    }

    /* --- Suppression --------------------------------------------------- */
    if ($action === 'delete') {
        if ($creation) {
            set_flash('error', 'Compte introuvable.');
            redirect_to('utilisateurs');
        }
        if (!$canDelete) {
            set_flash('error', "Vous n'avez pas les droits nécessaires.");
            redirect_to('utilisateurs');
        }
        if ($editingId === $monId) {
            set_flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            redirect_to('utilisateurs');
        }

        try {
            $db->beginTransaction();
            $db->prepare('DELETE FROM user_sessions WHERE user_id = :id')->execute(['id' => $editingId]);
            $db->prepare('DELETE FROM user_permissions WHERE user_id = :id')->execute(['id' => $editingId]);
            $db->prepare('DELETE FROM user_roles WHERE user_id = :id')->execute(['id' => $editingId]);
            $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $editingId]);
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            set_flash('error', 'Suppression impossible : ' . $e->getMessage());
            redirect_to('utilisateur', ['id' => $editingId]);
        }

        log_activity($db, 'delete', 'user', $editingId, (string) $compte['nom_complet']);
        set_flash('success', 'Compte « ' . (string) $compte['nom_complet'] . ' » supprimé.');
        redirect_to('utilisateurs');
    }
}

/* ---------------------------------------------------------------------------
 * Mot de passe a afficher une seule fois.
 *
 * Lu puis retire du tampon : laisser la variable en session la ferait reapparaitre
 * a chaque rechargement de la fiche, ce qui transformerait un affichage unique en
 * fuite durable du secret.
 * ------------------------------------------------------------------------ */
$motDePasseRevele = null;
if (!empty($_SESSION['_compte_mdp_temporaire'])
    && (int) ($_SESSION['_compte_mdp_temporaire']['id'] ?? 0) === $editingId) {
    $motDePasseRevele = (string) $_SESSION['_compte_mdp_temporaire']['mot_de_passe'];
    unset($_SESSION['_compte_mdp_temporaire']);
}

/* ---------------------------------------------------------------------------
 * Options de roles et de cabinets.
 * ------------------------------------------------------------------------ */
$rolesDispo = [];
if ($db) {
    try {
        // Un editeur de cabinet ne voit QUE les roles de cabinet : proposer un
        // role de scope 'centre' lui ouvrirait la plateforme entiere. Pour un
        // editeur Centre, les deux portees sont proposees et le JavaScript n'affiche
        // que celles qui correspondent au cabinet choisi.
        $sqlRoles = $monCabinetId !== null
            ? "SELECT id, nom, scope, is_system FROM roles WHERE scope = 'cabinet' ORDER BY sort_order ASC, nom ASC"
            : 'SELECT id, nom, scope, is_system FROM roles ORDER BY sort_order ASC, nom ASC';
        foreach ($db->query($sqlRoles) as $row) {
            $rolesDispo[(int) $row['id']] = [
                'nom' => (string) $row['nom'],
                'portee' => (string) $row['scope'] === 'cabinet' ? 'cabinet' : 'centre',
                'systeme' => (int) $row['is_system'],
            ];
        }
    } catch (PDOException) {
        $rolesDispo = [];
    }
}

// Le role deja attribue reste affiche, meme s'il n'appartient pas a la portee
// proposee : un compte Centre porte parfois un role de cabinet (donnee existante),
// et le masquer le rendrait sans role d'un simple enregistrement.
$roleIdCourant = int_value($formData, 'role_id') ?? 0;
if ($roleIdCourant > 0 && !isset($rolesDispo[$roleIdCourant]) && $compte !== null && $db) {
    try {
        $stRole = $db->prepare('SELECT id, nom, scope, is_system FROM roles WHERE id = :id LIMIT 1');
        $stRole->execute(['id' => $roleIdCourant]);
        $row = $stRole->fetch();
        if ($row !== false) {
            $rolesDispo[(int) $row['id']] = [
                'nom' => (string) $row['nom'],
                'portee' => (string) $row['scope'] === 'cabinet' ? 'cabinet' : 'centre',
                'systeme' => (int) $row['is_system'],
            ];
        }
    } catch (PDOException) {
        // Role disparu : aucun repli a construire, la validation bloquera.
    }
}

$cabinetsOptions = $db && $monCabinetId === null ? fetch_cabinets_options($db) : [];

// Le role choisi correspond-il a la portee du cabinet choisi ? Sans ce controle,
// un enregistrement passerait la validation de forme puis echouerait au controle
// de portee - l'utilisateur ne saurait pas lequel des deux a joue.
$porteeFormulaire = $monCabinetId !== null
    ? 'cabinet'
    : ($formData['cabinet_id'] !== '' && (int) $formData['cabinet_id'] > 0 ? 'cabinet' : 'centre');
$roleCourantPortee = $rolesDispo[$roleIdCourant]['portee'] ?? null;
$roleIncoherent = $roleIdCourant > 0
    && $roleCourantPortee !== null
    && $roleCourantPortee !== $porteeFormulaire;

$erreurResume = $fieldErrors;

/* ---------------------------------------------------------------------------
 * Actions de l'en-tete (H1). Les boutons sont hors du <form>, qui est rendu
 * plus bas : ils le pilotent par l'attribut HTML5 `form` (meme montage que
 * `centre.php`). Sans droit d'enregistrement, le bouton n'est pas rendu - la
 * verification serveur, elle, reste inchangee.
 * ------------------------------------------------------------------------ */
$pageActions = '';
if (!$creation) {
    $pageActions .= '<a class="btn btn-cancel" href="' . e(app_url('utilisateurs')) . '"><span class="material-symbols-outlined">arrow_back</span> Comptes</a>';
}
$pageActions .= '<a class="btn btn-cancel" href="' . e(app_url('utilisateurs')) . '"><span class="material-symbols-outlined">close</span> Annuler</a>';
if ($creation ? $canCreate : $canEdit) {
    $pageActions .= '<button type="submit" form="compte-form" class="btn btn-next"><span class="material-symbols-outlined">save</span> '
        . ($creation ? 'Créer le compte' : 'Enregistrer les modifications') . '</button>';
}
?>
<style>
/* ===== Page « Fiche utilisateur » : mise en page locale ====================
   Patch purement visuel, limite a cette page : aucune palette ni composant
   global n'est touche. Trois objectifs seulement - occuper la largeur
   disponible (grille de champs sur 2 colonnes plutot qu'une pile), aligner a
   gauche les en-tetes de carte, resserrer les zones « mot de passe » et
   « supprimer ». Tout repasse sur une seule colonne sous 768 px.
   ========================================================================== */

/* --- Conteneur : largeur maximale coherente avec les autres fiches -------- */
.compte-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* --- Grille de champs : 2 colonnes sur desktop, 1 seule sur mobile -------- */
.compte-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 16px;
}
.compte-grid > .full {
    grid-column: 1 / -1;
}

/* `.field` est une grille : ses lignes s'ETIRENT par defaut, donc un champ
   place a cote d'un voisin plus haut (qui porte un texte d'aide) voyait sa
   saisie s'allonger. Deux saisies cote a cote doivent avoir la meme hauteur :
   on fige les lignes du champ sur leur contenu. */
.compte-grid > .field {
    align-content: start;
}

/* --- Espacements des textes d'aide --------------------------------------- */
.compte-page .help-text {
    margin: 2px 0 0;
}
.compte-page .compte-help {
    margin: 0 0 10px;
}
.compte-page .compte-value {
    font-size: 0.85rem;
    color: var(--text);
}

/* --- Case a cocher : elle herite du `width: 100%` des champs texte, ce qui
       la detachait de son libelle. On la remet dans son flux, a hauteur du
       controle voisin. ------------------------------------------------------ */
.compte-check {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: var(--ds-control-h, 34px);
    cursor: pointer;
}
.compte-check input[type="checkbox"] {
    width: auto;
    flex: 0 0 auto;
    margin: 0;
    accent-color: var(--primary);
}
.compte-check span {
    /* `.field span` met les libelles en capitales : la phrase de la case doit
       rester en casse normale, sinon elle se lit comme un titre. */
    text-transform: none;
    letter-spacing: 0;
    font-size: 0.8rem;
    color: var(--text);
}

/* --- Pastille d'en-tete en rouge pour la carte destructive ---------------- */
.compte-page .section-header h2.is-danger > .material-symbols-outlined {
    color: var(--danger);
    background: rgba(252, 66, 74, 0.12);
    border-color: rgba(252, 66, 74, 0.25);
}

/* --- Zone « nouveau mot de passe » : champ et bouton sur une meme ligne,
       alignes sur le bas (le bouton se cale sous la saisie, pas sous l'aide). */
.compte-reset {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 6px 10px;
}
.compte-reset .compte-reset-msg {
    grid-column: 1 / -1;
}

.compte-global-error {
    margin: 8px 0 0;
    font-size: 0.8rem;
    color: var(--danger);
}

/* La carte « Mot de passe » suit la fermeture du formulaire, pas une autre
   carte : `article.card + article.card` ne l'espace donc pas. */
.compte-page form + .card {
    margin-top: 0.75rem;
}

@media (max-width: 768px) {
    .compte-grid {
        grid-template-columns: 1fr;
    }
    .compte-reset {
        grid-template-columns: 1fr;
        align-items: stretch;
    }
}
</style>

<section class="compte-page">

    <?php if ($motDePasseRevele !== null): ?>
        <div style="background:rgba(255,107,53,0.08);border:1px solid rgba(255,107,53,0.25);border-radius:6px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;color:var(--warning);font-size:0.82rem;" role="status">
            <span class="material-symbols-outlined" style="font-size:1.1rem">key</span>
            <div>
                <strong>Nouveau mot de passe pour <?= e((string) $formData['email']) ?> :</strong>
                <code style="display:block;margin-top:6px;background:var(--panel);border:1px solid var(--line);border-radius:4px;padding:8px 10px;font-size:0.95rem;color:var(--text);user-select:all;"><?= e($motDePasseRevele) ?></code>
                <span style="display:block;margin-top:6px">Copiez-le maintenant : il ne sera plus jamais affiché.</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($erreurResume !== []): ?>
        <div style="background:rgba(252,66,74,0.08);border:1px solid rgba(252,66,74,0.25);border-radius:6px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:flex-start;gap:10px;color:var(--danger);font-size:0.8rem;" role="alert">
            <span class="material-symbols-outlined" style="font-size:1.1rem">error</span>
            <div>
                <strong><?= count($erreurResume) ?> champ(s) à corriger</strong>
                <p style="margin:4px 0 0">Les champs en rouge ci-dessous empêchent l'enregistrement.</p>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" id="compte-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="save">
        <?php if (!$creation): ?>
            <input type="hidden" name="id" value="<?= e((string) $editingId) ?>">
        <?php endif; ?>

        <article class="card">
            <div class="section-header">
                <h2><span class="material-symbols-outlined">badge</span> <?= $creation ? 'Identité du compte' : 'Identité' ?></h2>
            </div>

            <div class="compte-grid">
                <label class="field<?= isset($fieldErrors['nom_complet']) ? ' field-error' : '' ?>">
                    <span>Nom complet <i class="req-mark">*</i></span>
                    <input name="nom_complet" required maxlength="190"
                           value="<?= e($formData['nom_complet']) ?>"
                           <?= isset($fieldErrors['nom_complet']) ? 'aria-invalid="true"' : '' ?>>
                    <?php if (isset($fieldErrors['nom_complet'])): ?>
                        <small class="field-error"><?= e($fieldErrors['nom_complet']) ?></small>
                    <?php endif; ?>
                </label>

                <label class="field<?= isset($fieldErrors['email']) ? ' field-error' : '' ?>">
                    <span>Login (email) <i class="req-mark">*</i></span>
                    <input type="email" name="email" required maxlength="190" autocomplete="username"
                           value="<?= e($formData['email']) ?>"
                           placeholder="prenom.nom@exemple.com"
                           <?= isset($fieldErrors['email']) ? 'aria-invalid="true"' : '' ?>>
                    <?php if (isset($fieldErrors['email'])): ?>
                        <small class="field-error"><?= e($fieldErrors['email']) ?></small>
                    <?php else: ?>
                        <small class="help-text">Identifiant de connexion.</small>
                    <?php endif; ?>
                </label>

                <label class="field">
                    <span>Téléphone</span>
                    <input name="telephone" maxlength="40" value="<?= e($formData['telephone']) ?>">
                </label>

                <?php if (!$creation): ?>
                    <div class="field">
                        <span>Dernière connexion</span>
                        <strong class="compte-value">
                            <?= $compte['last_login'] !== null && $compte['last_login'] !== ''
                                ? e(date('d/m/Y H:i', strtotime((string) $compte['last_login'])))
                                : 'Jamais connecté' ?>
                        </strong>
                        <small class="help-text">Compte créé le <?= e(format_date($compte['created_at'] ?? null)) ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <article class="card">
            <div class="section-header">
                <h2><span class="material-symbols-outlined">admin_panel_settings</span> Accès à l'application</h2>
            </div>

            <div class="compte-grid">
                <label class="field<?= isset($fieldErrors['role_id']) ? ' field-error' : '' ?>">
                    <span>Rôle <i class="req-mark">*</i></span>
                    <select name="role_id" id="compte-role" required
                            <?= isset($fieldErrors['role_id']) ? 'aria-invalid="true"' : '' ?>>
                        <option value="">— Choisir un rôle —</option>
                        <?php foreach ($rolesDispo as $rid => $r): ?>
                            <option value="<?= e((string) $rid) ?>"
                                data-portee="<?= e($r['portee']) ?>"
                                <?= $roleIdCourant === $rid ? 'selected' : '' ?>>
                                <?= e($r['nom']) ?><?= $r['systeme'] === 1 ? ' (système)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($fieldErrors['role_id'])): ?>
                        <small class="field-error"><?= e($fieldErrors['role_id']) ?></small>
                    <?php elseif ($roleIncoherent): ?>
                        <small class="field-error">Le rôle sélectionné n'appartient pas au périmètre choisi.</small>
                    <?php else: ?>
                        <small class="help-text">Le rôle détermine les pages et les actions autorisées.</small>
                    <?php endif; ?>
                </label>

                <?php if ($monCabinetId === null): ?>
                    <label class="field<?= isset($fieldErrors['cabinet_id']) ? ' field-error' : '' ?>">
                        <span>Périmètre</span>
                        <select name="cabinet_id" id="compte-cabinet">
                            <option value="">Centre (compte interne)</option>
                            <?php foreach ($cabinetsOptions as $cid => $libelle): ?>
                                <option value="<?= e((string) $cid) ?>"
                                    <?= (int) $formData['cabinet_id'] === $cid ? 'selected' : '' ?>>
                                    <?= e($libelle) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($fieldErrors['cabinet_id'])): ?>
                            <small class="field-error"><?= e($fieldErrors['cabinet_id']) ?></small>
                        <?php else: ?>
                            <small class="help-text">Cloisonne les dossiers et comptes visibles.</small>
                        <?php endif; ?>
                    </label>
                <?php else: ?>
                    <div class="field">
                        <span>Périmètre</span>
                        <strong class="compte-value">
                            <?= e((string) ($compte['cabinet_code'] ?: $compte['cabinet_nom'])) ?>
                            (votre cabinet)
                        </strong>
                        <small class="help-text">Vous ne pouvez gérer que les comptes de votre cabinet.</small>
                    </div>
                <?php endif; ?>

                <label class="field<?= isset($fieldErrors['statut']) ? ' field-error' : '' ?>">
                    <span>Statut</span>
                    <select name="statut" <?= isset($fieldErrors['statut']) ? 'aria-invalid="true"' : '' ?>>
                        <option value="actif" <?= $formData['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                        <option value="suspendu" <?= $formData['statut'] === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                    </select>
                    <?php if (isset($fieldErrors['statut'])): ?>
                        <small class="field-error"><?= e($fieldErrors['statut']) ?></small>
                    <?php else: ?>
                        <small class="help-text">Un compte suspendu ne peut plus se connecter (prochaine connexion).</small>
                    <?php endif; ?>
                </label>

                <div class="field">
                    <span>Mot de passe</span>
                    <label class="compte-check">
                        <input type="checkbox" name="must_change_password" value="1"
                               <?= $formData['must_change_password'] === '1' ? 'checked' : '' ?>>
                        <span>Imposer un changement à la prochaine connexion</span>
                    </label>
                </div>

                <?php if ($creation): ?>
                    <label class="field full<?= isset($fieldErrors['mot_de_passe']) ? ' field-error' : '' ?>">
                        <span>Mot de passe <span class="help-text">(laisser vide pour en générer un)</span></span>
                        <input type="text" name="mot_de_passe" minlength="8" maxlength="255"
                               autocomplete="new-password" placeholder="Généré automatiquement"
                               value="<?= e($formData['mot_de_passe']) ?>">
                        <?php if (isset($fieldErrors['mot_de_passe'])): ?>
                            <small class="field-error"><?= e($fieldErrors['mot_de_passe']) ?></small>
                        <?php endif; ?>
                    </label>
                <?php endif; ?>
            </div>
        </article>

        <?php if (isset($fieldErrors['global'])): ?>
            <p class="compte-global-error">
                <span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:-2px">error</span>
                <?= e($fieldErrors['global']) ?>
            </p>
        <?php endif; ?>
    </form>

    <?php if (!$creation && ($canEdit || $editingId === $monId)): ?>
        <article class="card">
            <div class="section-header">
                <h2><span class="material-symbols-outlined">lock_reset</span> Mot de passe</h2>
            </div>

            <p class="help-text compte-help">
                Le mot de passe actuel est chiffré et ne peut pas être relu : la réinitialisation
                en attribue un nouveau, affiché une seule fois.
            </p>

            <form method="post" class="compte-reset">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" value="<?= e((string) $editingId) ?>">

                <label class="field<?= isset($fieldErrors['nouveau_mot_de_passe']) ? ' field-error' : '' ?>">
                    <span>Nouveau mot de passe</span>
                    <input type="text" name="nouveau_mot_de_passe" minlength="8" maxlength="255"
                           autocomplete="new-password"
                           placeholder="Laisser vide pour en générer un"
                           value="">
                </label>

                <button type="submit" class="btn btn-next">
                    <span class="material-symbols-outlined">password</span> Réinitialiser le mot de passe
                </button>

                <?php if (isset($fieldErrors['nouveau_mot_de_passe'])): ?>
                    <small class="field-error compte-reset-msg"><?= e($fieldErrors['nouveau_mot_de_passe']) ?></small>
                <?php else: ?>
                    <small class="help-text compte-reset-msg">8 caractères minimum, saisi ou généré.</small>
                <?php endif; ?>
            </form>
        </article>
    <?php endif; ?>

    <?php if (!$creation && $canDelete && $editingId !== $monId): ?>
        <article class="card">
            <div class="section-header">
                <h2 class="is-danger"><span class="material-symbols-outlined">delete_forever</span> Supprimer le compte</h2>
                <div class="table-actions">
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= e((string) $editingId) ?>">
                        <button type="submit" class="btn btn-danger"
                                data-confirm="Supprimer le compte &quot;<?= e((string) $formData['nom_complet']) ?>&quot; ? Il perdra définitivement l'accès à l'application.">
                            <span class="material-symbols-outlined">delete</span> Supprimer le compte
                        </button>
                    </form>
                </div>
            </div>

            <p class="help-text compte-help">
                Définitif : le compte, ses rôles, ses droits et ses sessions sont supprimés.
                Les dossiers créés par ce compte sont conservés.
            </p>
        </article>
    <?php endif; ?>
</section>

<?php if ($monCabinetId === null): ?>
<script>
(function () {
    var cabinet = document.getElementById('compte-cabinet');
    var role = document.getElementById('compte-role');
    if (!cabinet || !role) {
        return;
    }

    // Un cabinet choisi n'accepte qu'un rôle de cabinet, et inversement : les
    // options de l'autre portee sont retires du menu. L'option deja selectionnee
    // reste proposee - sinon changer de cabinet viderait silencieusement le champ,
    // et c'est le controle serveur qui tranche en dernier ressort.
    function majRoles() {
        var estCabinet = cabinet.value !== '';
        var options = role.querySelectorAll('option[data-portee]');
        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            var correspond = option.getAttribute('data-portee') === (estCabinet ? 'cabinet' : 'centre');
            option.disabled = !correspond && !option.selected;
        }
    }

    cabinet.addEventListener('change', majRoles);
    majRoles();
})();
</script>
<?php endif; ?>
