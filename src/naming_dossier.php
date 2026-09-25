<?php

declare(strict_types=1);

/**
 * Source unique de verite pour le nommage des dossiers et documents generes.
 *
 * Objectif : une seule convention, une seule fonction de truncation, un seul
 * calcul de code collaborateur. Le chemin est fige en base a la premiere
 * generation (cf. migration 20260925_000003) afin qu'une evolution ulterieure
 * du collaborateur, de la raison sociale ou de la date ne cree pas de doublon.
 *
 * Convention :
 *   dossier  DOM-2026-042_CPT-AGR-FIDBA_TECH-SOLUTIONS-MAROC_SARL-AU
 *   document 2026-09-25_Contrat-Domiciliation_TECH-SOLUTIONS-MAROC_SARL-AU.docx
 *   '_' separe les champs, '-' remplace les espaces a l'interieur d'une valeur.
 */
final class DossierNaming
{
    /** Budget d'un segment de nom (raison sociale, forme juridique, ...). */
    public const MAX_SEGMENT = 60;

    /** Budget du nom complet du dossier. */
    public const MAX_DOSSIER = 160;

    /** Budget du nom complet du document, extension comprise. */
    public const MAX_DOCUMENT = 160;

    /**
     * Longueur maximale du chemin complet (repertoire + separateur + nom).
     * Windows limite un chemin a 260 caracteres : au-dela, ZipArchive::close()
     * echoue en ecrivant l'archive ("Failure to create temporary file"), donc
     * le document n'est pas produit. On reste sous la limite avec une marge.
     */
    public const LONGUEUR_CHEMIN_MAX = 250;

    /** Racine de tous les dossiers generes, relative au projet. */
    public const DOSSIER_RACINE = 'dossiers_generer';

    /** Dossiers d'une societe en creation de société. */
    public const DOSSIER_BASE_CREATION = 'dossiers_generer/dossiers_creation';

    /** Dossiers d'une societe en domiciliation. */
    public const DOSSIER_BASE_DOMICILIATION = 'dossiers_generer/dossiers_domiciliation';

    /** Dossiers d'une cession de parts sociales. */
    public const DOSSIER_BASE_CESSION = 'dossiers_generer/dossiers_cession';

    /** Dossiers d'un PV d'assemblee generale extraordinaire. */
    public const DOSSIER_BASE_PV_AGO = 'dossiers_generer/dossiers_pv_ago';

    /** Selectionne la base de dossiers d'un type de generation. */
    public static function basePourType(string $typeGeneration): string
    {
        return ($typeGeneration === 'creation')
            ? self::DOSSIER_BASE_CREATION
            : self::DOSSIER_BASE_DOMICILIATION;
    }

    /**
     * Translitteration des caracteres accentues susceptibles d'apparaitre dans
     * une raison sociale ou un nom de personne. Table statique plutot que
     * Normalizer/intl : l'hebergement mutualise de production n'expose pas
     * toujours l'extension intl, et le resultat doit etre identique partout.
     */
    private const TRANSLIT = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
        'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Œ' => 'OE',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ý' => 'Y', 'Ÿ' => 'Y', 'Þ' => 'TH', 'ß' => 'SS',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'œ' => 'oe',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'þ' => 'th', 'ß' => 'ss',
    ];

    /**
     * Chaine pour un code : majuscules, sans accent, uniquement des lettres et
     * chiffres, sans aucun separateur. Utilise pour deriver un code personne
     * (les separateurs sont interdits dans un code).
     */
    public static function lettres(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $value = strtr($value, self::TRANSLIT);

        return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
    }

    /**
     * Chaine pour un segment de nom : majuscules, sans accent, les suites de
     * caracteres non alphanumeriques deviennent un tiret unique, sans tiret en
     * bord. "M'Hamed Tech & Co" devient "M-HAMED-TECH-CO".
     */
    public static function etiquette(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $value = strtr($value, self::TRANSLIT);
        $value = (string) preg_replace('/[^A-Z0-9]+/', '-', strtoupper($value));

        return trim($value, '-');
    }

    /**
     * Regle H2 : 3 lettres du nom + 2 lettres du prenom.
     * "Fiducaire" / "Basma" donne "FIDBA".
     */
    public static function codePersonne(?string $nom, ?string $prenom): string
    {
        $code = self::lettres($nom);
        $code = substr($code, 0, 3) . substr(self::lettres($prenom), 0, 2);

        return $code !== '' ? $code : 'COLLAB';
    }

    /**
     * Verifie si un code collaborateur est deja pris.
     *
     * @param int|null $excludeId Collaborateur a ignorer (edition en cours).
     */
    public static function codeExiste(?PDO $pdo, string $code, ?int $excludeId = null): bool
    {
        if (!$pdo || $code === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM collaborateurs WHERE collaborateur_code = :code';
        $params = ['code' => $code];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Code collaborateur complet : "CPT-AGR-FIDBA".
     *
     * En cas de collision on suffixe incrementally (FIDBA, FIDBA2, FIDBA3...)
     * jusqu'a 50 tentatives. L'index unique uk_collaborateur_code reste le
     * garde-fou final : en cas de course concurrente entre deux insertions,
     * l'INSERT echoue et l'appelant peut reessayer.
     */
    public static function codeCollaborateur(
        ?PDO $pdo,
        string $typeCode,
        ?string $nom,
        ?string $prenom,
        ?int $excludeId = null
    ): string {
        $type = self::etiquette($typeCode);
        $personne = self::codePersonne($nom, $prenom);
        $base = $type !== '' ? $type . '-' . $personne : $personne;

        if (!$pdo) {
            return $base;
        }

        $tentative = $base;
        for ($n = 2; $n <= 50; $n++) {
            if (!self::codeExiste($pdo, $tentative, $excludeId)) {
                return $tentative;
            }
            $tentative = $base . $n;
        }

        return $base . '_' . substr(sha1($base . microtime(true)), 0, 6);
    }

    /**
     * Troncature deterministe : au-dela du budget, on conserve un prefixe et
     * on ajoute un suffixe hash pour ne pas perdre l'unicite. Deux entrees
     * differentes tronquees a la meme longueur produisent donc deux noms
     * differents, et le meme entrees produit toujours le meme nom.
     */
    public static function tronquer(string $value, int $max): string
    {
        if ($max < 8 || strlen($value) <= $max) {
            return $value;
        }

        $suffixe = '_' . substr(sha1($value), 0, 6);
        $tete = rtrim(substr($value, 0, $max - strlen($suffixe)), '-_ ');

        return $tete . $suffixe;
    }

    /**
     * Nom du dossier de sortie.
     *
     * Un segment vide est simply omis, ce qui evite les doubles separateurs
     * quand un collaborateur n'est pas encore renseigne.
     */
    public static function nomDossier(
        ?string $numeroDossier,
        ?string $codeCollaborateur,
        ?string $raisonSociale,
        ?string $formeJuridique,
        string $separateur = '_'
    ): string {
        $segments = [];

        foreach ([
            self::etiquette($numeroDossier),
            self::etiquette($codeCollaborateur),
            self::tronquer(self::etiquette($raisonSociale), self::MAX_SEGMENT),
            self::tronquer(self::etiquette($formeJuridique), 40),
        ] as $segment) {
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        return self::tronquer(implode($separateur, $segments), self::MAX_DOSSIER);
    }

    /**
     * Nom du fichier genere : "2026-09-25_Contrat-Domiciliation_Raison_Forme.docx".
     * Le statut (Brouillon, Signe, ...) est un segment optionnel en fin.
     *
     * $longueurMax borne le nom complet (extension comprise) selon le budget de
     * chemin disponible (voir budgetNomFichier). La troncature porte sur la
     * partie sans extension, pour que l'extension ne soit jamais coupee.
     */
    public static function nomDocument(
        string $date,
        string $typeDocument,
        ?string $raisonSociale,
        ?string $formeJuridique = null,
        ?string $statut = null,
        string $extension = 'docx',
        ?int $longueurMax = null
    ): string {
        $extension = ltrim(strtolower($extension), '.');

        $segments = [
            self::etiquette($date),
            self::etiquette($typeDocument),
            self::tronquer(self::etiquette($raisonSociale), self::MAX_SEGMENT),
        ];

        if (($forme = self::tronquer(self::etiquette($formeJuridique), 40)) !== '') {
            $segments[] = $forme;
        }

        if (($etat = self::etiquette($statut)) !== '') {
            $segments[] = $etat;
        }

        $nom = implode('_', array_filter($segments, static fn (string $s): bool => $s !== ''));

        $max = self::MAX_DOCUMENT;
        if ($longueurMax !== null && $longueurMax > 0) {
            $max = min($max, $longueurMax);
        }
        if ($extension !== '') {
            $max -= strlen($extension) + 1;
        }

        $nom = self::tronquer($nom, max(12, $max));

        return $extension !== '' ? $nom . '.' . $extension : $nom;
    }

    /**
     * Nombre de caracteres disponibles pour un nom de fichier dans le
     * repertoire donne, sans jamais depasser LONGUEUR_CHEMIN_MAX.
     */
    public static function budgetNomFichier(string $repertoire): int
    {
        return max(24, self::LONGUEUR_CHEMIN_MAX - strlen($repertoire) - 1);
    }

    /**
     * Chemin relatif du dossier d'un dossier genere, avec separateur '/'
     * quelle que soit la plateforme (la colonne stocke toujours du '/', pas
     * du '\' de Windows).
     */
    public static function cheminRelatif(string $dossierBase, string $nomDossier): string
    {
        $base = trim(str_replace('\\', '/', $dossierBase), '/ ');

        // Le nom provient deja de nomDossier() : on ne le renormalise PAS ici,
        // sous peine de transformer les separateurs '_' en '-' et de casser la
        // convention. On se limite a le securiser pour un chemin de systeme
        // de fichiers (pas de separateur, pas de '.' de navigation).
        $nom = trim(str_replace(['\\', '/'], '-', $nomDossier), "-./ \0");

        if ($base === '' && $nom === '') {
            return '';
        }
        if ($base === '') {
            return $nom;
        }

        return $nom === '' ? $base : $base . '/' . $nom;
    }

    /**
     * Liste des qualifications d'intermediaire pour les listes deroulantes.
     */
    public static function listeTypes(?PDO $pdo, string $axeMode = ''): array
    {
        if (!$pdo) {
            return [];
        }

        $sql = 'SELECT id, code, axe_mode, axe_qualif, libelle, icon
                FROM ref_qualites_intermediaire';
        $params = [];

        if ($axeMode !== '') {
            $sql .= ' WHERE axe_mode = :axe';
            $params['axe'] = $axeMode;
        }

        $sql .= ' ORDER BY sort_order, libelle';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifie qu'un code existe bien dans le referentiel des qualifications.
     * Utilise pour valider une saisie manuelle du type avant de l'utiliser
     * dans un code collaborateur ou un nom de dossier.
     */
    public static function typeExiste(?PDO $pdo, string $code): bool
    {
        if (!$pdo || trim($code) === '') {
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM ref_qualites_intermediaire WHERE code = :code'
        );
        $stmt->execute(['code' => self::etiquette($code)]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Code collaborateur retenu pour nommer le dossier d'une societe.
     *
     * Regle metier : le collaborateur MARQUE PRINCIPAL dans la table de liaison
     * collaborateur_societes. C'est lui qui est porte par le nom du dossier.
     * L'unicite "un principal par societe" n'est pas garantie par un index
     * (MySQL ne sait pas indexer "une seule ligne a 1"), donc le tri par id
     * rend la lecture deterministe si une donnee se trouve en double.
     *
     * Repli historique : les societes creees avant la table de liaison n'ont
     * aucun lien enregistre. On conserve alors l'ancienne regle provisoire
     * (premier collaborateur externe avec code, le plus ancien d'abord) plutot
     * que d'omettre le segment du nom : un code existant vaut mieux qu'un trou,
     * et ces societes seront rattachees a un collaborateur au prochain passage
     * en base.
     */
    public static function codeCollaborateurDossier(?PDO $pdo, int $societeId): string
    {
        if (!$pdo || $societeId <= 0) {
            return '';
        }

        $stmt = $pdo->prepare(
            "SELECT c.collaborateur_code
               FROM collaborateur_societes cs
               JOIN collaborateurs c ON c.id = cs.collaborateur_id
              WHERE cs.societe_id = :id
                AND cs.is_principal = 1
                AND c.collaborateur_code IS NOT NULL
                AND TRIM(c.collaborateur_code) <> ''
              ORDER BY cs.id
              LIMIT 1"
        );
        $stmt->execute(['id' => $societeId]);
        $principal = trim((string) ($stmt->fetchColumn() ?: ''));
        if ($principal !== '') {
            return $principal;
        }

        $legacy = $pdo->prepare(
            "SELECT collaborateur_code
               FROM collaborateurs
              WHERE societe_id = :id
                AND collaborateur_type LIKE 'externe%'
                AND collaborateur_code IS NOT NULL
                AND TRIM(collaborateur_code) <> ''
              ORDER BY id
              LIMIT 1"
        );
        $legacy->execute(['id' => $societeId]);

        return trim((string) ($legacy->fetchColumn() ?: ''));
    }

    /**
     * Collaborateur responsable d'une societe, tel que renseigne dans le wizard.
     * Retourne null quand aucun principal n'est enregistre.
     */
    public static function collaborateurPrincipal(?PDO $pdo, int $societeId): ?array
    {
        if (!$pdo || $societeId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare(
            "SELECT c.id, c.nom_complet, c.collaborateur_code, c.collaborateur_type,
                    q.code AS qualite_code, q.libelle AS qualite_libelle,
                    cs.role_dossier, cs.date_debut
               FROM collaborateur_societes cs
               JOIN collaborateurs c ON c.id = cs.collaborateur_id
               LEFT JOIN ref_qualites_intermediaire q ON q.id = c.qualite_intermediaire_id
              WHERE cs.societe_id = :id
                AND cs.is_principal = 1
              ORDER BY cs.id
              LIMIT 1"
        );
        $stmt->execute(['id' => $societeId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Retrouve un dossier deja genere sur le disque pour cette societe, a partir
     * des chemins deja enregistres dans documents_generes.
     *
     * Indispensable lors du passage a la convention : sans cette recuperation,
     * un dossier genere avant le gel recevrait un nom neuf, ses fichiers
     * existants deviendraient invisibles et le dossier se retrouverait orphelin.
     * Seuls les dossiers reellement presents sur le disque sont acceptes.
     */
    public static function recupererDossierExistant(
        ?PDO $pdo,
        int $societeId,
        string $dossierBase,
        string $racineProjet
    ): string {
        if (!$pdo || $societeId <= 0 || trim($racineProjet) === '') {
            return '';
        }

        $stmt = $pdo->prepare(
            "SELECT fichier_docx
               FROM documents_generes
              WHERE societe_id = :id
                AND fichier_docx IS NOT NULL
                AND fichier_docx <> ''
              ORDER BY id
              LIMIT 50"
        );
        $stmt->execute(['id' => $societeId]);

        $base = trim(str_replace('\\', '/', $dossierBase), '/');
        $racine = rtrim(str_replace('\\', '/', $racineProjet), '/');
        $ancre = '/' . $base . '/';

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $chemin) {
            $dossier = str_replace('\\', '/', dirname((string) $chemin));
            $nom = basename($dossier);

            if ($nom === '' || $nom === '.' || !str_contains($dossier, $ancre)) {
                continue;
            }

            if (is_dir($racine . '/' . $base . '/' . $nom)) {
                return $base . '/' . $nom;
            }
        }

        return '';
    }

    /**
     * Fige le chemin de sortie d'une societe, ou renvoie celui deja fige.
     *
     * Trois etats dans l'ordre :
     *   1. un chemin deja fige en base, reutilise tel quel ;
     *   2. un dossier existant retrouve sur disque, fige sous son nom actuel ;
     *   3. sinon le nom est calcule selon la convention puis fige.
     *
     * Appele a la premiere generation. Toute regeneration ulterieure lit
     * societes.dossier_output_path et n'ecrit plus rien, ce qui garantit qu'un
     * dossier ne se deplace jamais apres sa creation.
     *
     * @return array{path: string, nom: string, fige: bool, source: string}
     */
    public static function figerCheminDossier(
        ?PDO $pdo,
        int $societeId,
        ?string $numeroDossier,
        ?string $codeCollaborateur,
        ?string $raisonSociale,
        ?string $formeJuridique,
        string $dossierBase = self::DOSSIER_BASE_DOMICILIATION,
        string $racineProjet = ''
    ): array {
        $vide = ['path' => '', 'nom' => '', 'fige' => false, 'source' => 'aucun'];

        if (!$pdo || $societeId <= 0) {
            return $vide;
        }

        $stmt = $pdo->prepare(
            'SELECT dossier_output_path, dossier_output_nom FROM societes WHERE id = :id'
        );
        $stmt->execute(['id' => $societeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $vide;
        }

        $existant = (string) ($row['dossier_output_path'] ?? '');
        if ($existant !== '') {
            return [
                'path' => $existant,
                'nom' => (string) ($row['dossier_output_nom'] ?? basename(str_replace('\\', '/', $existant))),
                'fige' => true,
                'source' => 'fige',
            ];
        }

        $source = 'convention';
        $chemin = self::recupererDossierExistant($pdo, $societeId, $dossierBase, $racineProjet);

        if ($chemin !== '') {
            $source = 'recupere';
        } else {
            $nom = self::nomDossier($numeroDossier, $codeCollaborateur, $raisonSociale, $formeJuridique);
            $chemin = self::cheminRelatif($dossierBase, $nom);
        }

        $nom = basename(str_replace('\\', '/', $chemin));

        $update = $pdo->prepare(
            'UPDATE societes
                SET dossier_output_path = :path, dossier_output_nom = :nom
              WHERE id = :id AND (dossier_output_path IS NULL OR dossier_output_path = \'\')'
        );
        $update->execute(['path' => $chemin, 'nom' => $nom, 'id' => $societeId]);

        return ['path' => $chemin, 'nom' => $nom, 'fige' => true, 'source' => $source];
    }

    /**
     * Nom de dossier a utiliser pour une archive, SANS ecrire en base.
     *
     * Les pages de telechargement (dossier_download, download_all) doivent
     * nommer leur ZIP comme le dossier reellement present sur le disque, sinon
     * l'archive extraite cree un doublon du dossier genere au lieu de le
     * reproduire. On lit donc le nom fige s'il existe, et on ne fige rien ici :
     * un telechargement est un GET sans effet de bord, il ne doit pas figer un
     * chemin pour une societe qui n'a jamais ete generee.
     *
     * @param array $soc Ligne societes deja chargee (raison sociale, forme, numeros).
     */
    public static function nomDossierArchive(
        ?PDO $pdo,
        int $societeId,
        array $soc,
        ?string $numeroDossier = null
    ): string {
        $fige = self::segmentSure((string) ($soc['dossier_output_nom'] ?? ''));
        if ($fige !== '') {
            return $fige;
        }

        $chemin = (string) ($soc['dossier_output_path'] ?? '');
        if (trim($chemin) !== '') {
            $fige = self::segmentSure(basename(str_replace('\\', '/', $chemin)));
            if ($fige !== '') {
                return $fige;
            }
        }

        // Aucune generation a ce jour : on applique la convention, comme le
        // ferait une premiere generation, sans rien inscrire en base.
        return self::segmentSure(self::nomDossier(
            $numeroDossier,
            self::codeCollaborateurDossier($pdo, $societeId),
            (string) ($soc['societe_raison_sociale'] ?? ''),
            (string) ($soc['societe_forme_juridique'] ?? '')
        ));
    }

    /**
     * Neutralise un nom venant de la base avant de l'utiliser comme segment.
     *
     * Le nom fige alimente une entree d'archive ZIP et un en-tete
     * Content-Disposition. Une valeur de base inattendue (ou alteree) ne doit
     * pas pouvoir produire de '..', un separateur de chemin, un retour a la
     * ligne ou un octet nul. '_' et '-' sont conserves : ce sont des
     * separateurs legitimes de la convention, absents des bords.
     */
    private static function segmentSure(string $nom): string
    {
        $nom = str_replace(['\\', '/', "\r", "\n", "\0"], '-', $nom);
        $nom = str_replace('..', '-', $nom);

        return trim($nom, "-./ \t");
    }

    /**
     * Convertit un chemin relatif stocke en base en chemin absolu, en refusant
     * toute sortie de la racine du projet.
     */
    public static function cheminAbsolu(string $racineProjet, string $cheminRelatif): string
    {
        $racine = rtrim(str_replace('\\', '/', $racineProjet), '/');
        $relatif = ltrim(str_replace('\\', '/', $cheminRelatif), '/');

        return $racine . '/' . $relatif;
    }
}
