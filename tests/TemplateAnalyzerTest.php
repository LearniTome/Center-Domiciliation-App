<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TemplateAnalyzerTest extends TestCase
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

    public function testExtractVariablesUnderscoreFormat(): void
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'test.docx';
        DocxFixture::create(
            $path,
            'Raison sociale : _SOCIETE_RAISON_SOCIALE_',
            'Gerant : _ASSOCIE_NOM_ _ASSOCIE_PRENOM_'
        );

        $vars = TemplateAnalyzer::extractVariables($path);

        $this->assertContains('SOCIETE_RAISON_SOCIALE', $vars);
        $this->assertContains('ASSOCIE_NOM', $vars);
        $this->assertContains('ASSOCIE_PRENOM', $vars);
    }

    public function testExtractVariablesLegacyBracesStillSupported(): void
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'legacy.docx';
        DocxFixture::create($path, 'ICE : {{ SOCIETE_ICE }}');

        $vars = TemplateAnalyzer::extractVariables($path);

        $this->assertContains('SOCIETE_ICE', $vars);
    }

    public function testExtractVariablesSurvivesCellGluing(): void
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'glued.docx';
        DocxFixture::createRaw(
            $path,
            DocxFixture::wrapBody(DocxFixture::paragraph('commerciale ', '_SOCIETE_RAISON_SOCIALE_Sigle'))
        );

        $vars = TemplateAnalyzer::extractVariables($path);

        $this->assertContains('SOCIETE_RAISON_SOCIALE', $vars);
    }

    public function testExtractVariablesSortedAndUnique(): void
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'dup.docx';
        DocxFixture::create($path, '_B_VAR_ _A_VAR_', '_A_VAR_');

        $vars = TemplateAnalyzer::extractVariables($path);

        $this->assertSame(['A_VAR', 'B_VAR'], array_values(array_intersect($vars, ['A_VAR', 'B_VAR'])));
    }

    public function testRenameVariableInDocx(): void
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'rename.docx';
        DocxFixture::create($path, 'Valeur : _ANCIENNE_VAR_ fin');

        $result = TemplateAnalyzer::renameVariable('ANCIENNE_VAR', 'NOUVELLE_VAR', $this->tmpDir);

        $this->assertSame([], $result['errors']);
        $this->assertGreaterThanOrEqual(1, $result['modified']);
        $this->assertStringNotContainsString('_ANCIENNE_VAR_', DocxFixture::readDocumentXml($path));
        $this->assertStringContainsString('_NOUVELLE_VAR_', DocxFixture::readDocumentXml($path));
        $this->assertContains('NOUVELLE_VAR', TemplateAnalyzer::extractVariables($path));
    }

    public function testDeleteVariableFromDocx(): void
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'delete.docx';
        DocxFixture::create($path, 'Avant _VAR_A_SUPPRIMER_ apres');

        $result = TemplateAnalyzer::deleteVariable('VAR_A_SUPPRIMER', $this->tmpDir);

        $this->assertSame([], $result['errors']);
        $xml = DocxFixture::readDocumentXml($path);
        $this->assertStringNotContainsString('_VAR_A_SUPPRIMER_', $xml);
        $this->assertStringContainsString('Avant  apres', $xml);
    }

    public function testRenameVariableRejectsInvalidNames(): void
    {
        $result = TemplateAnalyzer::renameVariable('SAME', 'SAME', $this->tmpDir);

        $this->assertSame(0, $result['modified']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testInferSectionMapping(): void
    {
        $this->assertSame('societe', TemplateAnalyzer::inferSection('SOCIETE_RAISON_SOCIALE'));
        $this->assertSame('associe', TemplateAnalyzer::inferSection('ASSOCIE_NOM'));
        $this->assertSame('contrat', TemplateAnalyzer::inferSection('CONTRAT_LOYER'));
        $this->assertSame('cession', TemplateAnalyzer::inferSection('CESSION_DATE'));
        $this->assertSame('autre', TemplateAnalyzer::inferSection('VARIABLE_INCONNUE'));
    }

    public function testExpectedContextKeysContainCoreKeys(): void
    {
        $keys = TemplateAnalyzer::getExpectedContextKeys();

        $this->assertNotEmpty($keys);
        $this->assertContains('SOCIETE_RAISON_SOCIALE', $keys);
        $this->assertContains('ASSOCIE_NOM', $keys);
    }
}
