<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Non-regression sur l'erreur "Incorrect integer value" remontee a l'ecran.
 *
 * Cause : les listes d'options des modales sont parfois indexees par un
 * identifiant ([8 => 'Comptable agree']). Le rendu faisait
 * is_int($cle) ? $libelle : $cle, ce qui soumettait le libelle 'Comptable
 * agree' dans la colonne entiere collaborateurs.role_id.
 */
final class ApiValeursTest extends TestCase
{
    public function testUneListeParCleSoumetLaCleEtNonLeLibelle(): void
    {
        $options = [1 => 'Super Admin', 8 => 'Comptable agree', 16 => 'Autre'];

        $this->assertSame('8', $this->valeurOption($options, 'Comptable agree'));
        $this->assertSame('1', $this->valeurOption($options, 'Super Admin'));
    }

    public function testUneListeIndexeeSoumetLeLibelle(): void
    {
        $options = ['interne', 'externe-pm', 'externe-pp'];

        $this->assertSame('externe-pp', $this->valeurOption($options, 'externe-pp'));
        $this->assertSame('interne', $this->valeurOption($options, 'interne'));
    }

    public function testUneListeParCleChaineSoumetLaCle(): void
    {
        $options = ['a' => 'Alpha', 'b' => 'Beta'];

        $this->assertSame('b', $this->valeurOption($options, 'Beta'));
    }

    public function testUneCleEntiereNeDoitJamaisEtreConfondueAvecUnIndex(): void
    {
        // Le piege exact : une liste dont les cles sont 1, 8, 16 - donc des
        // entiers - doit rester une liste par cle, pas une liste indexee.
        $options = [];
        foreach ([1 => 'Super Admin', 8 => 'Comptable agree', 16 => 'Autre'] as $id => $nom) {
            $options[(int) $id] = $nom;
        }

        $this->assertFalse(array_is_list($options), 'La liste par cle ne doit pas passer pour une liste indexee');
        $this->assertSame('8', $this->valeurOption($options, 'Comptable agree'));
    }

    // ------------------------------------------------------------------
    // Normalisation selon le type de colonne
    // ------------------------------------------------------------------

    public function testUnLibelleDansUneColonneEntiereEstRefuseAvecUnMessageLisible(): void
    {
        $result = api_normaliser_valeur('Comptable agree', 'int(10) unsigned');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Valeur numerique attendue', $result['message']);
        $this->assertStringContainsString('Comptable agree', $result['message']);
    }

    public function testUnIdentifiantEnChaineEstConvertiEnEntier(): void
    {
        $result = api_normaliser_valeur('8', 'int(10) unsigned');

        $this->assertTrue($result['ok']);
        $this->assertSame(8, $result['value']);
    }

    public function testUneChaineVideDevientNullPourUneColonneEntiere(): void
    {
        $this->assertNull(api_normaliser_valeur('', 'int(10) unsigned')['value']);
        $this->assertNull(api_normaliser_valeur('   ', 'decimal(10,2)')['value']);
    }

    public function testUneChaineVideResteUneChainePourDuTexte(): void
    {
        $result = api_normaliser_valeur('', 'varchar(255)');

        $this->assertTrue($result['ok']);
        $this->assertSame('', $result['value']);
    }

    public function testUneDateInvalideEstRefuseeAvantMySQL(): void
    {
        // MySQL rejette 2026-13-45 en mode strict ; on le refuse ici avec un
        // message comprehensible plutot qu'une exception SQL.
        $result = api_normaliser_valeur('2026-13-45', 'date');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Date invalide', $result['message']);
    }

    public function testUneDateFrancaiseEstConvertieEnIso(): void
    {
        $result = api_normaliser_valeur('25/09/2026', 'date');

        $this->assertTrue($result['ok']);
        $this->assertSame('2026-09-25', $result['value']);
    }

    public function testUnHorodatageIsoEstAccepte(): void
    {
        $result = api_normaliser_valeur('2026-09-25 14:30:00', 'datetime');

        $this->assertTrue($result['ok']);
        $this->assertSame('2026-09-25 14:30:00', $result['value']);
    }

    public function testUneDateIsoDansUneColonneHorodateeEstAcceptee(): void
    {
        // MySQL interprete une date nue comme 00:00:00 : on ne la refuse pas.
        $result = api_normaliser_valeur('2026-09-25', 'datetime');

        $this->assertTrue($result['ok']);
        $this->assertSame('2026-09-25', $result['value']);
    }

    public function testUneHeureHorsPlageEstRefusee(): void
    {
        $this->assertFalse(api_normaliser_valeur('2026-09-25 25:00', 'datetime')['ok']);
        $this->assertFalse(api_normaliser_valeur('2026-09-25 10:75', 'datetime')['ok']);
        $this->assertFalse(api_normaliser_valeur('2026-09-25 10:30:99', 'timestamp')['ok']);
    }

    public function testUneDateInexistanteDansUnHorodatageEstRefusee(): void
    {
        $this->assertFalse(api_normaliser_valeur('2026-02-31 10:00:00', 'datetime')['ok']);
    }

    public function testUnDecimalAccepteLaVirgule(): void
    {
        $result = api_normaliser_valeur('1500,75', 'decimal(10,2)');

        $this->assertTrue($result['ok']);
        $this->assertSame(1500.75, $result['value']);
    }

    public function testUnTexteDansUneColonneDecimaleEstRefuse(): void
    {
        $result = api_normaliser_valeur('beaucoup', 'decimal(10,2)');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Nombre attendu', $result['message']);
    }

    public function testUnNullEtUnNonChainePassentSansEtreConvertis(): void
    {
        $this->assertNull(api_normaliser_valeur(null, 'int(10)')['value']);
        $this->assertTrue(api_normaliser_valeur(true, 'varchar(5)')['ok']);
    }

    public function testUnTypeInconnuLaisseLaValeurIntacte(): void
    {
        $result = api_normaliser_valeur('Comptable agree', '');

        $this->assertTrue($result['ok']);
        $this->assertSame('Comptable agree', $result['value']);
    }

    private function valeurOption(array $options, string $label): string
    {
        $estListe = array_is_list($options);

        foreach ($options as $cle => $libelle) {
            if ((string) $libelle === $label) {
                return (string) ($estListe ? $libelle : $cle);
            }
        }

        $this->fail('Option introuvable : ' . $label);
    }
}
