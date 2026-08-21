<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DocumentRendererTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cd_tests_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        DocxFixture::removeDir($this->tmpDir);
    }

    public function testRenderReplacesUnderscoreVariables(): void
    {
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'tpl.docx';
        DocxFixture::create($template, 'Societe : _SOCIETE_RAISON_SOCIALE_, ICE : _SOCIETE_ICE_');
        $outDir = $this->tmpDir . DIRECTORY_SEPARATOR . 'out';
        $renderer = new DocumentRenderer($template, $outDir);

        $outPath = $renderer->render([
            'societe' => ['raison_sociale' => 'ACME SARL AU', 'ice' => '001234567000089'],
        ]);

        $this->assertFileExists($outPath);
        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringContainsString('ACME SARL AU', $xml);
        $this->assertStringContainsString('001234567000089', $xml);
        $this->assertStringNotContainsString('_SOCIETE_RAISON_SOCIALE_', $xml);
        $this->assertStringNotContainsString('_SOCIETE_ICE_', $xml);
    }

    public function testRenderReplacesLegacyBracesVariables(): void
    {
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'legacy.docx';
        DocxFixture::create($template, 'Raison : {{ SOCIETE_RAISON_SOCIALE }}');
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        $outPath = $renderer->render(['societe' => ['raison_sociale' => 'LEGACY SARL']]);

        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringContainsString('LEGACY SARL', $xml);
        $this->assertStringNotContainsString('{{', $xml);
    }

    public function testRenderLongerKeysReplacedBeforeTheirPrefixes(): void
    {
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'prefix.docx';
        DocxFixture::create(
            $template,
            'Siege : _SOCIETE_ADRESSE_SIEGE_.',
            'Debut : _CONTRAT_DATE_DEBUT_, date : _CONTRAT_DATE_.'
        );
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        // Reproduit le contexte reel : societe.societe_adresse vide genere la cle bare SOCIETE_ADRESSE
        $outPath = $renderer->render([
            'societe' => ['societe_adresse' => '', 'societe_adresse_siege' => '123 Boulevard Hassan II'],
            'contrat' => ['contrat_date' => '21/08/2026', 'contrat_date_debut' => '01/09/2026'],
        ]);

        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringContainsString('123 Boulevard Hassan II', $xml);
        $this->assertStringNotContainsString('SIEGE_', $xml);
        $this->assertStringContainsString('Debut : 01/09/2026, date : 21/08/2026.', $xml);
        $this->assertStringNotContainsString('_CONTRAT_DATE_', str_replace('_CONTRAT_DATE_DEBUT_', '', $xml));
    }

    public function testRenderMergesVariableSplitAcrossRuns(): void
    {
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'split.docx';
        DocxFixture::createRaw(
            $template,
            DocxFixture::wrapBody(DocxFixture::paragraph('_SOCIETE_RAISON_', 'SOCIALE_ et suite'))
        );
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        $outPath = $renderer->render(['societe' => ['raison_sociale' => 'FUSION SARL']]);

        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringContainsString('FUSION SARL', $xml);
        $this->assertStringContainsString('suite', $xml);
        $this->assertStringNotContainsString('_SOCIETE_RAISON_SOCIALE_', $xml);
    }

    public function testCessionPartsLoopExpandsEachPart(): void
    {
        $block = '{%p for c in cession_parts %}Cedant _c.CEDANT_NOM_COMPLET_ cede _c.PARTS_CEDEES_ parts.{%p endfor %}';
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'cession.docx';
        DocxFixture::createRaw(
            $template,
            DocxFixture::wrapBody(DocxFixture::paragraph($block))
        );
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        $outPath = $renderer->render([
            'cession_parts' => [
                ['cedant_nom_complet' => 'ALAMI Ahmed', 'parts_cedees' => '50'],
                ['cedant_nom_complet' => 'IDRISSI Sara', 'parts_cedees' => '30'],
            ],
        ]);

        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringContainsString('ALAMI Ahmed', $xml);
        $this->assertStringContainsString('IDRISSI Sara', $xml);
        $this->assertStringContainsString('50', $xml);
        $this->assertStringContainsString('30', $xml);
        $this->assertStringNotContainsString('{%p', $xml);
        $this->assertStringNotContainsString('_c.', $xml);
    }

    public function testCessionPartsLoopWithEmptyListRemovesBlock(): void
    {
        $block = '{%p for c in cession_parts %}Ligne _c.CEDANT_NOM_COMPLET_.{%p endfor %}';
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'cession_vide.docx';
        DocxFixture::createRaw(
            $template,
            DocxFixture::wrapBody(DocxFixture::paragraph($block))
        );
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        $outPath = $renderer->render(['cession_parts' => []]);

        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringNotContainsString('Ligne', $xml);
        $this->assertStringNotContainsString('{%p', $xml);
    }

    public function testAssocieLoopLongerTokensReplacedBeforeTheirPrefixes(): void
    {
        $block = '{%p for a in associes %}Associe _a.NOM_COMPLET_ (nom : _a.NOM_, prenom : _a.PRENOM_).{%p endfor %}';
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'associes.docx';
        DocxFixture::createRaw(
            $template,
            DocxFixture::wrapBody(DocxFixture::paragraph($block))
        );
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        $outPath = $renderer->render([
            'associes' => [
                ['associe_nom' => 'ABOLABA', 'associe_prenom' => 'Karim', 'associe_civilite' => 'M.'],
            ],
        ]);

        $xml = DocxFixture::readDocumentXml($outPath);
        $this->assertStringContainsString('Associe M. Karim ABOLABA (nom : ABOLABA, prenom : Karim).', str_replace("\n", '', $xml));
        $this->assertStringNotContainsString('COMPLET_', $xml);
    }

    public function testRenderGeneratesDefaultOutputName(): void
    {
        $template = $this->tmpDir . DIRECTORY_SEPARATOR . 'Contrat-Template.docx';
        DocxFixture::create($template, 'Texte statique.');
        $renderer = new DocumentRenderer($template, $this->tmpDir);

        $outPath = $renderer->render([]);

        $this->assertFileExists($outPath);
        $this->assertStringEndsWith('Contrat_genere.docx', $outPath);
    }
}
