#Requires -Version 7.0
<#
.SYNOPSIS
    Extrait les tâches non terminées du projet Center Domiciliation.

.DESCRIPTION
    Lit docs/ROADMAP.md et TODO.md, collecte les lignes "- [ ]" (non cochées),
    les regroupe par section et préfixe chaque tâche de son fichier source.
    La sortie est du markdown, à coller dans docs/journal/YYYY-MM-DD.md.

    Outil du skill todo-journalier. Il ne modifie AUCUN fichier existant :
    avec -OutFile il écrit le fragment dans un fichier (UTF-8), sinon il l'affiche.

    Conseils d'usage : pour un résultat garanti sans mojibake, passer -OutFile
    (écriture UTF-8 directe, sans passage par la console), puis lire le fichier.

.PARAMETER Root
    Racine du projet. Par défaut : remonte depuis ce script jusqu'à trouver AGENTS.md.

.PARAMETER InclureCode
    Ajoute aussi les commentaires TODO / FIXME / HACK du code source (php, js, sql, css).

.PARAMETER OutFile
    Écrit le markdown dans ce fichier en UTF-8 (sans BOM). Sans ce paramètre,
    le résultat est écrit sur la sortie standard.

.EXAMPLE
    pwsh -File .opencode/skills/todo-journalier/scripts/collecter-taches.ps1

.EXAMPLE
    pwsh -File .opencode/skills/todo-journalier/scripts/collecter-taches.ps1 -InclureCode -OutFile docs/journal/todo-fragment.md
#>
[CmdletBinding()]
param(
    [string]$Root,
    [switch]$InclureCode,
    [string]$OutFile
)

$ErrorActionPreference = 'Stop'

function Get-ProjectRoot {
    param([string]$Start)
    $dir = $Start
    while ($dir) {
        if (Test-Path -LiteralPath (Join-Path $dir 'AGENTS.md')) {
            return (Resolve-Path -LiteralPath $dir).Path
        }
        $parent = Split-Path -Parent $dir
        if ([string]::IsNullOrEmpty($parent) -or $parent -eq $dir) { break }
        $dir = $parent
    }
    throw "Racine du projet introuvable (aucun AGENTS.md en remontant depuis $Start)."
}

if (-not $Root) { $Root = Get-ProjectRoot -Start $PSScriptRoot }
$Root = (Resolve-Path -LiteralPath $Root).Path

$out = [System.Collections.Generic.List[string]]::new()

function Get-UncheckedTasks {
    param([string]$Path)

    $rel = [System.IO.Path]::GetRelativePath($Root, $Path).Replace('\', '/')
    $lines = Get-Content -LiteralPath $Path
    $section = '(racine)'
    $current = $null
    $entries = [System.Collections.Generic.List[object]]::new()

    foreach ($line in $lines) {
        $right = $line.TrimEnd()

        if ([string]::IsNullOrWhiteSpace($right)) { continue }

        if ($right -match '^#{1,6}\s+(.*)$') {
            if ($current) { $current.Text = (($current.Text -join ' ') -replace '\s+', ' ').Trim(); $entries.Add($current); $current = $null }
            $section = $matches[1].Trim()
            continue
        }

        if ($right -match '^\s*-\s\[ \]\s+(.*)$') {
            if ($current) { $current.Text = (($current.Text -join ' ') -replace '\s+', ' ').Trim(); $entries.Add($current) }
            $current = [pscustomobject]@{ Text = @($matches[1].Trim()); Section = $section }
            continue
        }

        if ($current -and $right -match '^\s+\S') {
            $current.Text += $right.Trim()
            continue
        }

        if ($current) {
            $current.Text = (($current.Text -join ' ') -replace '\s+', ' ').Trim()
            $entries.Add($current)
            $current = $null
        }
    }

    if ($current) { $current.Text = (($current.Text -join ' ') -replace '\s+', ' ').Trim(); $entries.Add($current) }

    return [pscustomobject]@{ Rel = $rel; Entries = $entries }
}

$today = Get-Date -Format 'yyyy-MM-dd'
$sources = @(
    (Join-Path $Root 'docs/ROADMAP.md'),
    (Join-Path $Root 'TODO.md')
)

$out.Add("# Todo — $today")
$out.Add('')
$out.Add('> Généré par le skill `todo-journalier`. Source de vérité : `docs/ROADMAP.md` + `TODO.md`.')
$out.Add('')

$total = 0
foreach ($src in $sources) {
    if (-not (Test-Path -LiteralPath $src)) { continue }

    $result = Get-UncheckedTasks -Path $src
    if ($result.Entries.Count -eq 0) { continue }

    $total += $result.Entries.Count
    $out.Add("## $($result.Rel)")
    $out.Add('')
    foreach ($group in ($result.Entries | Group-Object Section)) {
        $out.Add("### $($group.Name)")
        $out.Add('')
        foreach ($entry in $group.Group) {
            $out.Add("- [ ] $($entry.Text)")
        }
        $out.Add('')
    }
}

if ($InclureCode) {
    $codeExt = @('*.php', '*.js', '*.sql', '*.css')
    $exclude = '\\(vendor|node_modules|\.git|dossiers_generer|uploads|graphify-out|assets\\fonts)\\'
    $files = Get-ChildItem -LiteralPath $Root -Recurse -File -Include $codeExt -ErrorAction SilentlyContinue |
        Where-Object { $_.FullName -notmatch $exclude }

    $hits = $files | Select-String -Pattern '(//|#|/\*)\s*(TODO|FIXME|HACK|XXX)\b' -ErrorAction SilentlyContinue
    if ($hits) {
        $out.Add('## Commentaires TODO / FIXME dans le code')
        $out.Add('')
        foreach ($hit in $hits) {
            $rel = [System.IO.Path]::GetRelativePath($Root, $hit.Path).Replace('\', '/')
            $out.Add("- [ ] ``$rel`:$($hit.LineNumber)`` — $(($hit.Line).Trim())")
        }
        $out.Add('')
    }
}

$out.Add('---')
$out.Add('')
$out.Add("**$total tâche(s) non terminée(s)** au $today.")

if ($OutFile) {
    if (-not [System.IO.Path]::IsPathRooted($OutFile)) {
        $OutFile = Join-Path $Root $OutFile
    }
    $dir = Split-Path -Parent $OutFile
    if ($dir -and -not (Test-Path -LiteralPath $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    Set-Content -LiteralPath $OutFile -Value $out -Encoding utf8NoBOM
    Write-Host "Écrit : $OutFile ($total tâches)"
} else {
    $out | ForEach-Object { Write-Output $_ }
}
