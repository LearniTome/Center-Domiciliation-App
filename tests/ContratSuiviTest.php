<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests du vocabulaire et des calculs de suivi des contrats.
 *
 * Ces fonctions sont le point de partage entre la liste, la page de suivi,
 * la fiche contrat et le tableau de bord : une divergence ici fausse les
 * compteurs affiches partout ailleurs.
 */
final class ContratSuiviTest extends TestCase
{
    // ------------------------------------------------------------------
    // Statuts
    // ------------------------------------------------------------------

    public function testLesQuatreStatutsDuVocabulaire(): void
    {
        $this->assertSame(
            ['brouillon', 'actif', 'expire', 'resilie'],
            array_keys(contrat_statuts())
        );
    }

    public function testLibelleTraduitLesStatutsConnus(): void
    {
        $this->assertSame('Brouillon', contrat_statut_libelle('brouillon'));
        $this->assertSame('Actif', contrat_statut_libelle('actif'));
        $this->assertSame('Echu', contrat_statut_libelle('expire'));
        $this->assertSame('Resilie', contrat_statut_libelle('resilie'));
    }

    public function testLibelleReplieSurLeCodeBrutPourUnStatutInconnu(): void
    {
        // Ne pas masquer une valeur aberante : la page doit la montrer telle
        // quelle plutot que d'afficher un libelle trompeur.
        $this->assertSame('suspendu', contrat_statut_libelle('suspendu'));
    }

    public function testLibellePourUneValeurVide(): void
    {
        $this->assertSame('Non renseigne', contrat_statut_libelle(null));
        $this->assertSame('Non renseigne', contrat_statut_libelle(''));
        $this->assertSame('Non renseigne', contrat_statut_libelle('   '));
    }

    // ------------------------------------------------------------------
    // Echeance
    // ------------------------------------------------------------------

    public function testEcheanceDansLeFutur(): void
    {
        $jours = contrat_jours_avant_echeance((new DateTime('+10 days'))->format('Y-m-d'));
        $this->assertSame(10, $jours);
    }

    public function testEcheanceAujourdhuiVautZero(): void
    {
        $this->assertSame(0, contrat_jours_avant_echeance(date('Y-m-d')));
    }

    public function testEcheanceDepasseeEstNegative(): void
    {
        $jours = contrat_jours_avant_echeance((new DateTime('-5 days'))->format('Y-m-d'));
        $this->assertSame(-5, $jours);
    }

    public function testLeSeuilDeRenouvellementEstAtteignable(): void
    {
        // 30 jours inclus : le contrat du jour J+30 doit apparaitre dans
        // l'onglet "A renouveler", sinon le seuil depended de l'heure de
        // consultation et le compteur oscille d'un jour a l'autre.
        $jours = contrat_jours_avant_echeance((new DateTime('+30 days'))->format('Y-m-d'));
        $this->assertSame(30, $jours);
        $this->assertLessThanOrEqual(30, $jours);
    }

    public function testEcheanceAbsenteOuInvalideRetourneNull(): void
    {
        // Null signifie "inconnu" : la page ne doit pas fabriquer un badge
        // d'urgence pour un contrat sans date de fin.
        $this->assertNull(contrat_jours_avant_echeance(null));
        $this->assertNull(contrat_jours_avant_echeance(''));
        $this->assertNull(contrat_jours_avant_echeance('   '));
        $this->assertNull(contrat_jours_avant_echeance('pas-une-date'));
    }

    // ------------------------------------------------------------------
    // Vues du suivi
    // ------------------------------------------------------------------

    public function testLesQuatreOngletsDuSuivi(): void
    {
        $this->assertSame(
            ['actifs', 'renouvellement', 'echus', 'resilies'],
            array_keys(contrat_vues())
        );
    }

    public function testVueActifsExclutLesResiliesEtLesEchus(): void
    {
        $this->assertTrue(contrat_dans_vue('actifs', 'actif', 120));
        $this->assertTrue(contrat_dans_vue('actifs', 'actif', null), 'Pas de date de fin : le contrat reste visible.');
        $this->assertFalse(contrat_dans_vue('actifs', 'actif', -1), 'Un actif dont la date est depassee bascule en echu.');
        $this->assertFalse(contrat_dans_vue('actifs', 'resilie', 10));
        $this->assertFalse(contrat_dans_vue('actifs', 'brouillon', 10));
    }

    public function testVueRenouvellementBorneAuSeuilEtALeJour(): void
    {
        $this->assertTrue(contrat_dans_vue('renouvellement', 'actif', 0), 'Echeance du jour : a renouveler.');
        $this->assertTrue(contrat_dans_vue('renouvellement', 'actif', 30));
        $this->assertFalse(contrat_dans_vue('renouvellement', 'actif', 31));
        $this->assertFalse(contrat_dans_vue('renouvellement', 'actif', -1), 'Un echu n est pas a renouveler.');
        $this->assertFalse(contrat_dans_vue('renouvellement', 'actif', null), 'Sans date, pas d echeance a suivre.');
    }

    public function testVueEchusRegroupeLesEchusEtLesActifsDepasses(): void
    {
        $this->assertTrue(contrat_dans_vue('echus', 'expire', -30));
        $this->assertTrue(contrat_dans_vue('echus', 'actif', -1));
        $this->assertFalse(contrat_dans_vue('echus', 'actif', 10));
        $this->assertFalse(contrat_dans_vue('echus', 'resilie', null), 'Un resilie a ete traite.');
    }

    public function testVueResiliesEstExcluante(): void
    {
        $this->assertTrue(contrat_dans_vue('resilies', 'resilie', -30));
        $this->assertFalse(contrat_dans_vue('resilies', 'actif', 10));
        $this->assertFalse(contrat_dans_vue('resilies', 'expire', -5));
    }

    public function testUneVueInconnueTombeSurLesActifs(): void
    {
        // Le lien est construit a partir d'une constante, mais un ancien
        // signet ou une URL saisie a la main ne doit pas produire une page vide.
        $this->assertTrue(contrat_dans_vue('nimporte-quoi', 'actif', 10));
        $this->assertFalse(contrat_dans_vue('nimporte-quoi', 'resilie', 10));
    }

    public function testLesSeuilsSontChargesDepuisLaConfiguration(): void
    {
        $seuils = contrat_seuils();
        $this->assertSame(30, $seuils['renouvellement']);
        $this->assertSame(90, $seuils['alerte']);
        $this->assertSame(15, $seuils['critique']);
    }

    // ------------------------------------------------------------------
    // Montants issus de la base
    // ------------------------------------------------------------------

    public function testMoneyFromConvertitLesChainesNumeriques(): void
    {
        // PDO renvoie les DECIMAL en chaine : avec declare(strict_types=1),
        // format_money() refuserait une chaine, d'ou ce convertisseur.
        $this->assertSame(83.33, money_from('83.33'));
        $this->assertSame(100.0, money_from('100.00'));
        $this->assertSame(0.0, money_from('0'));
    }

    public function testMoneyFromRetourneNullPourUneValeurAbsente(): void
    {
        $this->assertNull(money_from(null));
        $this->assertNull(money_from(''));
        $this->assertNull(money_from('inconnu'));
    }

    // ------------------------------------------------------------------
    // Validation des dates saisies sur la fiche contrat
    // ------------------------------------------------------------------

    public function testDateIsoValideAccepteUneVraieDate(): void
    {
        $this->assertTrue(date_iso_valide('2026-09-25'));
        $this->assertTrue(date_iso_valide('2024-02-29'));  // bissextile
    }

    public function testDateIsoValideRejetteLesDatesImpossibles(): void
    {
        // Le seul test /^\d{4}-\d{2}-\d{2}$/ laissait passer ces valeurs ;
        // MySQL les stockait alors en 0000-00-00, ce qui cassait ensuite
        // tous les calculs d'ecart de la page de suivi.
        $this->assertFalse(date_iso_valide('2026-13-45'));
        $this->assertFalse(date_iso_valide('2026-02-30'));
        $this->assertFalse(date_iso_valide('2025-02-29')); // non bissextile
        $this->assertFalse(date_iso_valide('25/09/2026'));
        $this->assertFalse(date_iso_valide(''));
        $this->assertFalse(date_iso_valide(null));
    }

    // ------------------------------------------------------------------
    // Isolation des donnees par utilisateur
    // ------------------------------------------------------------------

    public function testUnAdministrateurNEstPasFiltre(): void
    {
        // Rôles 1 et 2 : la liste des contrats montre déjà tout.
        foreach ([1, 2] as $roleId) {
            $filtre = contrat_user_filter(['id' => 7, 'role_id' => $roleId]);
            $this->assertSame('', $filtre['sql']);
            $this->assertSame([], $filtre['params']);
        }
    }

    public function testUnUtilisateurStandardEstRestreintASesSocietes(): void
    {
        $filtre = contrat_user_filter(['id' => 7, 'role_id' => 5]);
        $this->assertStringContainsString('societes.created_by = :user_id', $filtre['sql']);
        $this->assertSame(['user_id' => 7], $filtre['params']);
    }

    public function testUnUtilisateurNonConnecteNAucuneRestrictionSql(): void
    {
        // Sans utilisateur, has_permission() a deja refuse l'acces : le filtre
        // ne doit surtout pas produire un ":user_id" sans parametre lie, ce
        // qui ferait echouer la requete.
        $filtre = contrat_user_filter(null);
        $this->assertIsArray($filtre);
        $this->assertArrayHasKey('sql', $filtre);
        $this->assertArrayHasKey('params', $filtre);
    }
}
