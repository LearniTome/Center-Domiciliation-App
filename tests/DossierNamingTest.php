<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DossierNamingTest extends TestCase
{
    // ------------------------------------------------------------------
    // Normalisation
    // ------------------------------------------------------------------

    public function testLettresIgnoreSeparateursEtAccents(): void
    {
        $this->assertSame('MHAMED', DossierNaming::lettres("M'Hamed"));
        $this->assertSame('ELAMRANI', DossierNaming::lettres('El Amrani'));
        $this->assertSame('SOCIETEGENERALE', DossierNaming::lettres('Société Générale'));
        $this->assertSame('', DossierNaming::lettres('   '));
        $this->assertSame('', DossierNaming::lettres(null));
    }

    public function testEtiquetteTransformeLesEspacesEnTirets(): void
    {
        $this->assertSame('M-HAMED-TECH-CO', DossierNaming::etiquette("M'Hamed Tech & Co"));
        $this->assertSame('SOCIETE-GENERALE-MAROC', DossierNaming::etiquette('Société Générale Maroc'));
    }

    public function testEtiquetteSupprimeLesTiretsEnBord(): void
    {
        $this->assertSame('SA-MAROC', DossierNaming::etiquette('-- SA Maroc --'));
        $this->assertSame('SARL', DossierNaming::etiquette('  SARL  '));
    }

    // ------------------------------------------------------------------
    // Regle H2 : 3 lettres du nom + 2 du prenom
    // ------------------------------------------------------------------

    public function testCodePersonneSuitLaRegleH2(): void
    {
        $this->assertSame('FIDBA', DossierNaming::codePersonne('Fiducaire', 'Basma'));
        $this->assertSame('BENYO', DossierNaming::codePersonne('Benali', 'Youssef'));
    }

    public function testCodePersonneTolereLesNomsCourts(): void
    {
        // Nom de moins de 3 lettres : on ne complete pas artificiellement.
        $this->assertSame('ABC', DossierNaming::codePersonne('AB', 'C'));
        // Prenom absent : seuls les 3 lettres du nom subsistent.
        $this->assertSame('BEN', DossierNaming::codePersonne('Benali', ''));
    }

    public function testCodePersonneReplieSurUneValeurNeutre(): void
    {
        $this->assertSame('COLLAB', DossierNaming::codePersonne('', ''));
        $this->assertSame('COLLAB', DossierNaming::codePersonne(null, null));
        $this->assertSame('COLLAB', DossierNaming::codePersonne('--', '...'));
    }

    public function testCodePersonneIgnoreLesApostrophes(): void
    {
        $this->assertSame('MHAAL', DossierNaming::codePersonne("M'Hamed", 'Ali'));
    }

    // ------------------------------------------------------------------
    // Troncature deterministe
    // ------------------------------------------------------------------

    public function testTronquerRespecteLeBudget(): void
    {
        $long = "STE-MAROC-INTERNATIONAL-SERVICES-DE-CONSEIL-ET-D'AUDIT-DANS-LES-AFFAIRES";
        $court = DossierNaming::tronquer($long, 60);

        $this->assertSame(60, strlen($court));
        $this->assertNotSame($long, $court);
    }

    public function testTronquerEstDeterministe(): void
    {
        $long = "STE-MAROC-INTERNATIONAL-SERVICES-DE-CONSEIL-ET-D'AUDIT-DANS-LES-AFFAIRES";

        $this->assertSame(DossierNaming::tronquer($long, 60), DossierNaming::tronquer($long, 60));
    }

    public function testTronquerNeCoupeJamaisSurUnSeparateur(): void
    {
        $long = 'ABCDEFGH-IJ-KLMNOPQ-RSTUVWX-YZ';

        $this->assertDoesNotMatchRegularExpression('/[-_]$/', DossierNaming::tronquer($long, 20));
    }

    public function testTronquerLaissePasserUneChaineCourte(): void
    {
        $this->assertSame('COURT', DossierNaming::tronquer('COURT', 60));
    }

    public function testDeuxNomsDifferentsRestentDistinctsApresTroncature(): void
    {
        $a = DossierNaming::tronquer('RAISON-SOCIALE-UNE-SUFFIXE-XXX', 30);
        $b = DossierNaming::tronquer('RAISON-SOCIALE-DEUX-SUFFIXE-XXX', 30);

        $this->assertNotSame($a, $b);
    }

    // ------------------------------------------------------------------
    // Nom de dossier
    // ------------------------------------------------------------------

    public function testNomDossierComplet(): void
    {
        $this->assertSame(
            'DOM-2026-042_CPT-AGR-FIDBA_TECH-SOLUTIONS-MAROC_SARL-AU',
            DossierNaming::nomDossier('DOM-2026-042', 'CPT-AGR-FIDBA', 'Tech Solutions Maroc', 'SARL AU')
        );
    }

    public function testNomDossierOmetLesSegmentsVides(): void
    {
        $this->assertSame(
            'DOM-2026-042_TECH-SOLUTIONS-MAROC_SARL-AU',
            DossierNaming::nomDossier('DOM-2026-042', null, 'Tech Solutions Maroc', 'SARL AU')
        );
        $this->assertSame(
            'TECH-SOLUTIONS-MAROC',
            DossierNaming::nomDossier(null, null, 'Tech Solutions Maroc', null)
        );
    }

    public function testNomDossierNeDoublejamaisLesSeparateurs(): void
    {
        $nom = DossierNaming::nomDossier('DOM-2026-042', null, 'Tech Solutions', null);

        $this->assertDoesNotMatchRegularExpression('/_{2,}/', $nom, 'Pas de double separateur');
        $this->assertDoesNotMatchRegularExpression('/^_|_$/', $nom, 'Pas de separateur en bord');
        $this->assertStringNotContainsString('--', $nom, 'Pas de tiret double');
    }

    // ------------------------------------------------------------------
    // Nom de document
    // ------------------------------------------------------------------

    public function testNomDocumentAvecStatut(): void
    {
        $this->assertSame(
            '2026-09-25_CONTRAT-DOMICILIATION_TECH-SOLUTIONS-MAROC_SARL-AU_BROUILLON.docx',
            DossierNaming::nomDocument('2026-09-25', 'Contrat-Domiciliation', 'Tech Solutions Maroc', 'SARL AU', 'Brouillon')
        );
    }

    public function testNomDocumentSansStatutNiFormeJuridique(): void
    {
        $this->assertSame(
            '2026-09-25_ANNUET-STATUT_TECH-SOLUTIONS-MAROC.docx',
            DossierNaming::nomDocument('2026-09-25', 'Annuet Statut', 'Tech Solutions Maroc')
        );
    }

    public function testNomDocumentAccepteUneAutreExtension(): void
    {
        $this->assertSame(
            '2026-09-25_PV-AGE_TECH-SOLUTIONS-MAROC.pdf',
            DossierNaming::nomDocument('2026-09-25', 'PV-AGE', 'Tech Solutions Maroc', null, null, '.pdf')
        );
    }

    public function testNomDocumentUneValeurVideEstOmise(): void
    {
        $nom = DossierNaming::nomDocument('2026-09-25', 'Contrat', 'Tech Solutions', '  ', null);

        $this->assertSame('2026-09-25_CONTRAT_TECH-SOLUTIONS.docx', $nom);
    }

    // ------------------------------------------------------------------
    // Chemin relatif
    // ------------------------------------------------------------------

    public function testCheminRelatifUtiliseToujoursLaBarreOblique(): void
    {
        $this->assertSame(
            'dossiers_generer/dossiers_creation/DOM-2026-042',
            DossierNaming::cheminRelatif('dossiers_generer/dossiers_creation', 'DOM-2026-042')
        );
    }

    public function testCheminRelatifPreserveLesSeparateursDuNom(): void
    {
        // Regression : cheminRelatif() ne doit pas renormaliser le nom via
        // etiquette(), sinon les separateurs '_' du nom de dossier se
        // transformeraient en '-' et casseraient la convention.
        $nom = DossierNaming::nomDossier('DOM-2026-042', 'CPT-AGR-FIDBA', 'Tech Solutions Maroc', 'SARL AU');

        $this->assertSame(
            'dossiers_generer/dossiers_creation/DOM-2026-042_CPT-AGR-FIDBA_TECH-SOLUTIONS-MAROC_SARL-AU',
            DossierNaming::cheminRelatif('dossiers_generer/dossiers_creation', $nom)
        );
    }

    public function testCheminRelatifNeutraliseUneTentativeDeTraversee(): void
    {
        $this->assertSame(
            'dossiers_generer/dossiers_creation/etc-passwd',
            DossierNaming::cheminRelatif('dossiers_generer/dossiers_creation', '../../etc/passwd')
        );
    }

    public function testCheminRelatifTolereUneBaseVide(): void
    {
        $this->assertSame('DOM-2026-042', DossierNaming::cheminRelatif('', 'DOM-2026-042'));
        $this->assertSame('', DossierNaming::cheminRelatif('', ''));
    }

    // ------------------------------------------------------------------
    // Budget de chemin (regression : ZipArchive echoue au-dela de 260
    // caracteres sous Windows, sans le moindre message exploitable)
    // ------------------------------------------------------------------

    public function testBudgetNomFichierTientCompteDuRepertoire(): void
    {
        $court = DossierNaming::budgetNomFichier('C:/dossiers_generer/dossiers_creation');
        $long = DossierNaming::budgetNomFichier(str_repeat('dossier_tres_long_', 12) . '/');

        $this->assertSame(
            DossierNaming::LONGUEUR_CHEMIN_MAX - strlen('C:/dossiers_generer/dossiers_creation') - 1,
            $court
        );
        $this->assertLessThan($court, $long);
        $this->assertGreaterThanOrEqual(24, $long, 'Le budget ne descend jamais sous un minimum utile');
    }

    public function testNomDocumentRespecteLeBudgetDeChemin(): void
    {
        $repertoire = str_repeat('dossier_tres_long_', 12) . '/';
        $nom = DossierNaming::nomDocument(
            '2026-09-25',
            'Attestation-Domiciliation_Template v2',
            'Tech Solutions Maroc',
            'SARL',
            'Brouillon',
            'docx',
            DossierNaming::budgetNomFichier($repertoire)
        );

        $this->assertLessThanOrEqual(
            DossierNaming::LONGUEUR_CHEMIN_MAX,
            strlen($repertoire) + 1 + strlen($nom),
            'Le chemin complet doit rester sous la limite Windows'
        );
    }

    public function testNomDocumentConserveLExtensionMalgreLaTroncature(): void
    {
        $nom = DossierNaming::nomDocument(
            '2026-09-25',
            'Attestation-Domiciliation_Template v2',
            'Tech Solutions Maroc',
            'SARL',
            'Brouillon',
            'docx',
            75
        );

        $this->assertStringEndsWith('.docx', $nom, 'La troncature ne doit jamais couper l extension');
        $this->assertLessThanOrEqual(75, strlen($nom));
    }

    public function testNomDocumentResteDeterministeSousContrainteDeBudget(): void
    {
        $args = ['2026-09-25', 'Contrat-Domiciliation', 'Tech Solutions Maroc', 'SARL', 'Brouillon', 'docx', 60];

        $this->assertSame(
            DossierNaming::nomDocument(...$args),
            DossierNaming::nomDocument(...$args),
            'Meme entree, meme sortie, meme budget'
        );
    }

    // ------------------------------------------------------------------
    // Code collaborateur ( necessite la base )
    // ------------------------------------------------------------------

    public function testCodeCollaborateurSansBaseRenvoieLeCodeBrut(): void
    {
        $this->assertSame('CPT-AGR-FIDBA', DossierNaming::codeCollaborateur(null, 'CPT-AGR', 'Fiducaire', 'Basma'));
    }

    public function testTypeExisteValideLeReferentiel(): void
    {
        $pdo = $this->pdo();

        $this->assertTrue(DossierNaming::typeExiste($pdo, 'CPT-EXP'));
        $this->assertTrue(DossierNaming::typeExiste($pdo, 'cpt-exp'), 'Insensible a la casse');
        $this->assertFalse(DossierNaming::typeExiste($pdo, 'EXP-CPT'), 'Ancienne convention corrigee en CPT-EXP');
        $this->assertFalse(DossierNaming::typeExiste($pdo, ''));
    }

    public function testReferentielContientLesSeptQualificationsAttendues(): void
    {
        $codes = array_column(DossierNaming::listeTypes($this->pdo()), 'code');

        $this->assertSame([
            'CPT-EXP',
            'CPT-AGR',
            'CPT-IND',
            'COU-EXP',
            'COU-AGR',
            'COU-IND',
            'CLT-DIR',
        ], $codes);
    }

    public function testListeTypesFiltreParAxe(): void
    {
        $pdo = $this->pdo();

        $this->assertCount(3, DossierNaming::listeTypes($pdo, 'coursier'));
        $this->assertCount(3, DossierNaming::listeTypes($pdo, 'direct'));
        $this->assertCount(1, DossierNaming::listeTypes($pdo, 'client'));
    }

    public function testCollisionAjouteUnSuffixeIncremental(): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $this->inserer($pdo, 'CPT-AGR-FIDBA');
            $this->assertSame('CPT-AGR-FIDBA2', DossierNaming::codeCollaborateur($pdo, 'CPT-AGR', 'Fiducaire', 'Basma'));

            $this->inserer($pdo, 'CPT-AGR-FIDBA2');
            $this->assertSame('CPT-AGR-FIDBA3', DossierNaming::codeCollaborateur($pdo, 'CPT-AGR', 'Fiducaire', 'Basma'));
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLeCollaborateurEnCoursDEditionEstExcluDeLaCollision(): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $id = $this->inserer($pdo, 'CPT-AGR-FIDBA');

            $this->assertSame(
                'CPT-AGR-FIDBA',
                DossierNaming::codeCollaborateur($pdo, 'CPT-AGR', 'Fiducaire', 'Basma', $id),
                'Regenerer le code de la ligne en cours ne doit pas ajouter de suffixe'
            );
        } finally {
            $pdo->rollBack();
        }
    }

    // ------------------------------------------------------------------
    // Gel du chemin
    // ------------------------------------------------------------------

    public function testCheminDossierEstFigeALaPremiereGeneration(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);

        $pdo->beginTransaction();

        try {
            $premier = DossierNaming::figerCheminDossier(
                $pdo,
                $societeId,
                'DOM-2026-042',
                'CPT-AGR-FIDBA',
                'Tech Solutions Maroc',
                'SARL AU',
                DossierNaming::DOSSIER_BASE_CREATION
            );

            $this->assertSame(
                'dossiers_generer/dossiers_creation/DOM-2026-042_CPT-AGR-FIDBA_TECH-SOLUTIONS-MAROC_SARL-AU',
                $premier['path']
            );
            $this->assertTrue($premier['fige']);
            $this->assertSame('convention', $premier['source'], 'Aucun dossier pre-existant : convention appliquee');

            // Un second appel avec des parametres completement differents doit
            // renvoyer le chemin deja fige, et non en calculer un nouveau.
            $second = DossierNaming::figerCheminDossier(
                $pdo,
                $societeId,
                'DOM-2026-099',
                'COU-IND-AUTRE',
                'Autre Societe',
                'SA',
                DossierNaming::DOSSIER_BASE_CREATION
            );

            $this->assertSame($premier['path'], $second['path']);
            $this->assertSame('fige', $second['source'], 'Le chemin deja fige prime sur tout le reste');
            $this->assertSame(
                $premier['path'],
                (string) $pdo->query("SELECT dossier_output_path FROM societes WHERE id = {$societeId}")->fetchColumn()
            );
        } finally {
            $pdo->rollBack();
        }
    }

    public function testUnDossierDejaSurDisqueEstRecupereEtNonRenomme(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);
        $racine = dirname(__DIR__);

        // Reproduit l'etat d'un dossier genere avant la convention : les
        // documents_generes pointent vers un dossier a l'ancienne nomenclature.
        $ancienNom = '2026-01-15_SARL-AU_Tech-Solutions-Maroc';
        $dossierRelatif = DossierNaming::DOSSIER_BASE_DOMICILIATION . '/' . $ancienNom;
        $absolu = DossierNaming::cheminAbsolu($racine, $dossierRelatif);

        if (!is_dir($absolu)) {
            mkdir($absolu, 0777, true);
        }
        $fichier = $absolu . '/doc.docx';
        file_put_contents($fichier, 'x');

        $pdo->beginTransaction();

        try {
            $ins = $pdo->prepare(
                'INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko)
                 VALUES (:soc, :tpl, :type, :docx, NULL, 0)'
            );
            $ins->execute([
                'soc' => $societeId,
                'tpl' => 'test',
                'type' => 'TEST',
                'docx' => $fichier,
            ]);

            $resultat = DossierNaming::figerCheminDossier(
                $pdo,
                $societeId,
                'DOM-2026-042',
                'CPT-AGR-FIDBA',
                'Tech Solutions Maroc',
                'SARL AU',
                DossierNaming::DOSSIER_BASE_DOMICILIATION,
                $racine
            );

            $this->assertSame('recupere', $resultat['source']);
            $this->assertSame($dossierRelatif, $resultat['path'], 'Le dossier existant garde son nom');
        } finally {
            $pdo->rollBack();
            @unlink($fichier);
            @rmdir($absolu);
        }
    }

    public function testUnDossierAbsentDuDisqueNEstPasRecupere(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);

        $pdo->beginTransaction();

        try {
            $ins = $pdo->prepare(
                'INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko)
                 VALUES (:soc, :tpl, :type, :docx, NULL, 0)'
            );
            // Chemin pointant vers un dossier fantome : aucune Recuperation possible.
            $ins->execute([
                'soc' => $societeId,
                'tpl' => 'test',
                'type' => 'TEST',
                'docx' => DossierNaming::cheminAbsolu(
                    dirname(__DIR__),
                    DossierNaming::DOSSIER_BASE_DOMICILIATION . '/dossier-qui-nexiste-pas'
                ) . '/doc.docx',
            ]);

            $resultat = DossierNaming::figerCheminDossier(
                $pdo,
                $societeId,
                'DOM-2026-042',
                'CPT-AGR-FIDBA',
                'Tech Solutions Maroc',
                'SARL AU',
                DossierNaming::DOSSIER_BASE_DOMICILIATION,
                dirname(__DIR__)
            );

            $this->assertSame('convention', $resultat['source'], 'Un dossier fantome ne doit pas etre fige');
        } finally {
            $pdo->rollBack();
        }
    }

    public function testCheminDossierIgnoreUneInexistanteSociete(): void
    {
        $vide = DossierNaming::figerCheminDossier($this->pdo(), 0, 'DOM-2026-042', null, 'X', 'SA');

        $this->assertSame('', $vide['path']);
        $this->assertFalse($vide['fige']);
    }

    // ------------------------------------------------------------------
    // Nom d'archive (telechargement)
    // ------------------------------------------------------------------

    public function testLeNomDArchiveReprendLeDossierFige(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);

        $pdo->beginTransaction();

        try {
            // Un dossier au nom fige, incoherent avec la convention courante :
            // c'est le cas reel d'une societe renommee apres sa generation.
            $nomFige = '2026-01-15_SARL-AU_Tech-Solutions-Maroc';
            $pdo->prepare(
                'UPDATE societes
                    SET dossier_output_path = :chemin, dossier_output_nom = :nom
                  WHERE id = :id'
            )->execute([
                'chemin' => DossierNaming::DOSSIER_BASE_DOMICILIATION . '/' . $nomFige,
                'nom' => $nomFige,
                'id' => $societeId,
            ]);

            $soc = $this->ligneSociete($pdo, $societeId);

            $this->assertSame(
                $nomFige,
                DossierNaming::nomDossierArchive($pdo, $societeId, $soc, 'DOM-2026-042'),
                'Le nom fige prime : l archive doit reproduire le dossier sur disque'
            );
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLeNomDArchiveRetombeSurLeCheminFigeQuandLeNomManque(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);

        $pdo->beginTransaction();

        try {
            $pdo->prepare(
                'UPDATE societes
                    SET dossier_output_path = :chemin, dossier_output_nom = NULL
                  WHERE id = :id'
            )->execute([
                'chemin' => DossierNaming::DOSSIER_BASE_CREATION . '/DOM-2026-042_TEST',
                'id' => $societeId,
            ]);

            $this->assertSame(
                'DOM-2026-042_TEST',
                DossierNaming::nomDossierArchive(
                    $pdo,
                    $societeId,
                    $this->ligneSociete($pdo, $societeId),
                    'DOM-2026-042'
                ),
                'Un chemin fige sans nom doit suffire a retrouver le dossier'
            );
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLeNomDArchiveAppliqueLaConventionSansFigerLaBase(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);

        $pdo->beginTransaction();

        try {
            $pdo->prepare(
                'UPDATE societes
                    SET dossier_output_path = NULL, dossier_output_nom = NULL
                  WHERE id = :id'
            )->execute(['id' => $societeId]);

            $soc = $this->ligneSociete($pdo, $societeId);
            $nom = DossierNaming::nomDossierArchive($pdo, $societeId, $soc, 'DOM-2026-042');

            $this->assertStringStartsWith('DOM-2026-042', $nom, 'La convention est appliquee a defaut');
            $this->assertNull(
                $pdo->query("SELECT dossier_output_path FROM societes WHERE id = {$societeId}")->fetchColumn(),
                'Un telechargement est un GET : il ne doit jamais figer un chemin'
            );
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLeNomDArchiveNeutraliseUneTentativeDeTraversee(): void
    {
        $pdo = $this->pdo();
        $societeId = $this->premierSocieteId($pdo);

        $soc = $this->ligneSociete($pdo, $societeId);
        $soc['dossier_output_nom'] = '../../evil';

        $nom = DossierNaming::nomDossierArchive($pdo, $societeId, $soc, 'DOM-2026-042');

        $this->assertStringNotContainsString('..', $nom);
        $this->assertStringNotContainsString('/', $nom);
    }

    // ------------------------------------------------------------------
    // Utilitaires
    // ------------------------------------------------------------------

    private function pdo(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'center_domiciliation';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '';

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            $this->markTestSkipped('Base de donnees indisponible : ' . $e->getMessage());
        }

        return $pdo;
    }

    private function inserer(PDO $pdo, string $code): int
    {
        $stmt = $pdo->prepare(
            "INSERT INTO collaborateurs
                (nom_complet, collaborateur_type, collaborateur_code, collaborateur_nom, collaborateur_prenom)
             VALUES ('Basma Fiducaire', 'externe-pp', :code, 'Fiducaire', 'Basma')"
        );
        $stmt->execute(['code' => $code]);

        return (int) $pdo->lastInsertId();
    }

    private function premierSocieteId(PDO $pdo): int
    {
        $id = $pdo->query('SELECT id FROM societes ORDER BY id LIMIT 1')->fetchColumn();

        if (!$id) {
            $this->markTestSkipped('Aucune societe en base.');
        }

        return (int) $id;
    }

    private function ligneSociete(PDO $pdo, int $societeId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM societes WHERE id = :id');
        $stmt->execute(['id' => $societeId]);

        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Code dossier d'un intermediaire calcule cote serveur
    // ------------------------------------------------------------------

    public function testLeCodeIntermediaireEstRecalculeParLeServeur(): void
    {
        // La modale n'affiche le code qu'en lecture seule et le remplit en
        // JavaScript : c'est un confort, pas une regle. Un POST direct ne
        // doit pas pouvoir imposer un code arbitraire.
        $pdo = $this->pdo();
        $id = $this->inserer($pdo, 'CPT-INV-TEST');

        try {
            $data = [
                'collaborateur_nom' => 'Fiducaire',
                'collaborateur_prenom' => 'Basma',
                'qualite_intermediaire_id' => $this->qualiteId($pdo, 'CPT-AGR'),
                'collaborateur_code' => 'CODE-FAUSSE-ENVOYE',
            ];
            $this->assertSame('CPT-AGR-FIDBA', code_collaborateur_intermediaire($pdo, $data, $id));
        } finally {
            $pdo->prepare('DELETE FROM collaborateurs WHERE id = :id')->execute(['id' => $id]);
        }
    }

    public function testUnCompteInterneConserveLeCodeFourni(): void
    {
        // Les comptes internes (can_login) et les gens morales ne sont pas
        // des intermediaires nommes : les nommer par le code generique
        // "COLLAB" serait une invention.
        $this->assertSame('', code_collaborateur_intermediaire(null, [
            'nom_complet' => 'Super Admin',
            'collaborateur_type' => 'interne',
            'collaborateur_code' => '',
        ]));
        $this->assertSame('EXP', code_collaborateur_intermediaire(null, [
            'nom_complet' => 'Atlas Domiciliation',
            'den_ste' => 'Atlas Domiciliation',
            'collaborateur_code' => 'EXP',
        ]));
    }

    private function qualiteId(PDO $pdo, string $code): int
    {
        $stmt = $pdo->prepare('SELECT id FROM ref_qualites_intermediaire WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $this->markTestSkipped('Qualification ' . $code . ' absente de la base.');
        }

        return (int) $id;
    }
}
