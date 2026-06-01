$templatesDir = Join-Path $PSScriptRoot "templates"

$mapping = @{
    'DENOMINATION_SOCIALE'    = 'SOCIETE_RAISON_SOCIALE'
    'RAISON_SOCIALE'         = 'SOCIETE_RAISON_SOCIALE'
    'DEN_STE'                = 'SOCIETE_RAISON_SOCIALE'
    'FORME_JURIDIQUE'        = 'SOCIETE_FORME_JURIDIQUE'
    'FORME_JUR'              = 'SOCIETE_FORME_JURIDIQUE'
    'CONTRAT_FORME_JURIDIQUE' = 'SOCIETE_FORME_JURIDIQUE'
    'CAPITAL_SOCIAL'         = 'SOCIETE_CAPITAL'
    'NOMBRE_PARTS'           = 'SOCIETE_PART_SOCIAL'
    'NOMBRE_PARTS_SOCIALES'  = 'SOCIETE_PART_SOCIAL'
    'NUMERO_ICE'             = 'SOCIETE_ICE'
    'ICE'                    = 'SOCIETE_ICE'
    'VILLE'                  = 'SOCIETE_VILLE'
    'ADRESSE_SIEGE_SOCIAL'   = 'SOCIETE_ADRESSE_SIEGE'
    'TRIBUNAL_COMPETENT'     = 'SOCIETE_TRIBUNAL'
    'DATE_IMMATRICULATION_ICE' = 'SOCIETE_DATE_ICE'
    'NUM_RC'                 = 'SOCIETE_RC'
    'NUM_DEPOT_RC'           = 'SOCIETE_RC'
    'NOM_ASSOCIE'            = 'ASSOCIE_NOM'
    'NOM'                    = 'ASSOCIE_NOM'
    'PRENOM_ASSOCIE'         = 'ASSOCIE_PRENOM'
    'PRENOM'                 = 'ASSOCIE_PRENOM'
    'CIVILITE_ASSOCIE'       = 'ASSOCIE_CIVILITE'
    'CIVIL'                  = 'ASSOCIE_CIVILITE'
    'NOM_COMPLET'            = 'ASSOCIE_NOM_COMPLET'
    'NUMERO_CIN_ASSOCIE'     = 'ASSOCIE_CIN'
    'DATE_NAISSANCE_ASSOCIE' = 'ASSOCIE_DATE_NAISSANCE'
    'LIEU_NAISSANCE_ASSOCIE' = 'ASSOCIE_LIEU_NAISSANCE'
    'NATIONALITE_ASSOCIE'    = 'ASSOCIE_NATIONALITE'
    'ADRESSE_ASSOCIE'        = 'ASSOCIE_ADRESSE'
    'EMAIL_ASSOCIE'          = 'ASSOCIE_EMAIL'
    'TELEPHONE_ASSOCIE'      = 'ASSOCIE_TELEPHONE'
    'QUALITE_ASSOCIE'        = 'ASSOCIE_QUALITE'
    'DATE_VALIDITE_CIN_ASSOCIE' = 'ASSOCIE_DATE_VALIDITE_CIN'
    'DATE_CONTRAT'           = 'CONTRAT_DATE'
    'DATE_DEBUT_CONTRAT'     = 'CONTRAT_DATE_DEBUT'
    'DATE_FIN_CONTRAT'       = 'CONTRAT_DATE_FIN'
    'DUREE_CONTRAT_MOIS'     = 'CONTRAT_DUREE_MOIS'
    'PACK_DEMARRAGE_LOYER_MENSUEL' = 'CONTRAT_PACK_LOYER_TTC'
    'PACK_DEMARRAGE_TOTAL_TTC'     = 'CONTRAT_PACK_MONTANT_TTC'
    'RENOUVELLEMENT_LOYER_ANNUEL'  = 'CONTRAT_RENOUV_ANNUEL_TTC'
    'RENOUVELLEMENT_LOYER_MENSUEL' = 'CONTRAT_RENOUV_LOYER_TTC'
    'DTAE_CONTRAT'           = 'CONTRAT_TYPE'
}

$replacements = @{}
foreach ($old in $mapping.Keys) {
    $new = $mapping[$old]
    # 4 variantes d'entree -> 1 sortie avec espaces
    $replacements["{{ $old }}"]  = "{{ $new }}"
    $replacements["{{$old}}"]    = "{{ $new }}"
    $replacements["{{$old }}"]   = "{{ $new }}"
    $replacements["{{ $old}}"]   = "{{ $new }}"
}

if (-not (Test-Path $templatesDir)) {
    Write-Host "ERREUR : dossier templates introuvable : $templatesDir"
    exit 1
}

$modifiedCount = 0
$docxFiles = Get-ChildItem -Path $templatesDir -Recurse -Filter "*.docx"

foreach ($file in $docxFiles) {
    $path = $file.FullName
    Write-Host "Traitement : $($file.Name)"

    $backup = "$path.bak"
    if (-not (Test-Path $backup)) {
        Copy-Item -LiteralPath $path -Destination $backup
    }

    $tempDir = Join-Path ([System.IO.Path]::GetTempPath()) "docx_$(Get-Random)"
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

    try {
        Expand-Archive -LiteralPath $path -DestinationPath $tempDir -Force

        $wordDir = Join-Path $tempDir "word"
        $xmlParts = @("word/document.xml")
        if (Test-Path $wordDir) {
            Get-ChildItem -Path $wordDir -Filter "header*.xml" | ForEach-Object { $xmlParts += "word/$($_.Name)" }
            Get-ChildItem -Path $wordDir -Filter "footer*.xml" | ForEach-Object { $xmlParts += "word/$($_.Name)" }
        }

        $modified = $false
        foreach ($part in $xmlParts) {
            $partPath = Join-Path $tempDir $part
            if (-not (Test-Path $partPath)) { continue }

            $content = [System.IO.File]::ReadAllText($partPath, [Text.Encoding]::UTF8)
            $newContent = $content
            foreach ($oldPattern in $replacements.Keys) {
                $newContent = $newContent.Replace($oldPattern, $replacements[$oldPattern])
            }
            if ($newContent -ne $content) {
                [System.IO.File]::WriteAllText($partPath, $newContent, [Text.Encoding]::UTF8)
                $modified = $true
            }
        }

        if ($modified) {
            Remove-Item -LiteralPath $path -Force
            Compress-Archive -Path (Join-Path $tempDir "*") -DestinationPath $path
            $modifiedCount++
            Write-Host "  Variables renommees"
        } else {
            Write-Host "  Aucune variable ancienne trouvee"
        }
    }
    finally {
        Remove-Item -LiteralPath $tempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`nTermine. $modifiedCount fichier(s) modifie(s)."
Write-Host "Les fichiers .bak sont conserves en sauvegarde."
