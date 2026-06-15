#!/bin/bash
# ==========================================
#  Center Domiciliation - Launcher (macOS/Linux)
#  Démarrage rapide de l'application
# ==========================================

set -e

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
PHP_PORT=8080
URL="http://localhost:$PHP_PORT/"

echo "========================================"
echo "  Center Domiciliation - Launcher"
echo "========================================"
echo "  App   : $PROJECT_ROOT"
echo ""

# ----- Synchronisation Git -----
if command -v git &>/dev/null; then
    if [ -d "$PROJECT_ROOT/.git" ]; then
        echo "[Git] Synchronisation avec le depot distant..."
        pushd "$PROJECT_ROOT" &>/dev/null
        HAS_CHANGES=$(git status --porcelain 2>/dev/null)
        STASHED=false
        if [ -n "$HAS_CHANGES" ]; then
            git stash push -m "auto-stash run.sh $(date '+%Y-%m-%d %H:%M:%S')" 2>/dev/null && STASHED=true
            echo "      Modifications locales mises de cote (stash)"
        fi
        git pull --rebase 2>/dev/null && echo "      Synchronise avec success" || echo "      [ATTENTION] Echec pull --rebase"
        if [ "$STASHED" = true ]; then
            git stash pop 2>/dev/null
            echo "      Modifications locales restaurees"
        fi
        popd &>/dev/null
    fi
else
    echo "[Git] Git non trouve, synchronisation ignoree"
fi
echo ""

# ----- Prerequis -----
echo "[Prerequis] Verification..."

if ! command -v brew &>/dev/null; then
    echo "      Homebrew manquant. Lance d'abord : ./setup.sh"
    exit 1
fi
echo "      Homebrew : present"

if ! command -v php &>/dev/null; then
    echo "      PHP manquant. Lance d'abord : ./setup.sh"
    exit 1
fi
echo "      PHP : $(php -v 2>&1 | head -1)"

if ! command -v mysql &>/dev/null; then
    echo "      MySQL manquant. Lance d'abord : ./setup.sh"
    exit 1
fi
echo "      MySQL : present"

if command -v node &>/dev/null; then
    echo "      Node.js : $(node --version)"
fi
echo ""

# ----- MySQL -----
echo "[MySQL] Demarrage du service..."
if ! brew services list 2>/dev/null | grep -q "mysql.*started"; then
    brew services start mysql &>/dev/null
    sleep 3
    for i in $(seq 1 10); do
        if mysql -u root -e "SELECT 1" &>/dev/null; then
            break
        fi
        sleep 2
    done
    if mysql -u root -e "SELECT 1" &>/dev/null; then
        echo "      [MySQL] Demarre"
    else
        echo "      [MySQL] Echec demarrage. Lance : brew services start mysql"
    fi
else
    echo "      [MySQL] Deja en cours"
fi

# ----- Composer -----
if [ ! -d "$PROJECT_ROOT/vendor" ]; then
    echo "[Composer] Installation des dependances..."
    if command -v composer &>/dev/null; then
        pushd "$PROJECT_ROOT" &>/dev/null
        composer install --no-interaction 2>/dev/null && echo "      Dependances installees"
        popd &>/dev/null
    fi
fi

# ----- LibreOffice -----
if ! command -v soffice &>/dev/null && [ ! -f "/Applications/LibreOffice.app/Contents/MacOS/soffice" ]; then
    echo "[LibreOffice] Recommande pour la conversion DOCX->PDF de qualite."
    echo "  Installation : brew install --cask libreoffice"
    echo "  Sinon, conversion via PHPWord/Dompdf (fallback)."
    echo ""
fi

# ----- Demarrage PHP Server -----
echo "[PHP] Demarrage du serveur..."

# Tuer un eventuel processus existant sur le port
lsof -ti:$PHP_PORT 2>/dev/null | xargs kill -9 2>/dev/null || true
sleep 1

PHP_LOG="$PROJECT_ROOT/php-server.log"
nohup php -S "localhost:$PHP_PORT" -t "$PROJECT_ROOT" "$PROJECT_ROOT/router.php" > "$PHP_LOG" 2>&1 &
PHP_PID=$!
sleep 2

echo "      Serveur PHP lance sur le port $PHP_PORT (PID: $PHP_PID)"
echo ""

echo "Application ouverte : $URL"
open "$URL" 2>/dev/null || xdg-open "$URL" 2>/dev/null || echo "Ouvre ce lien : $URL"

echo ""
echo "Le serveur PHP tourne en arriere-plan."
echo "Logs : $PHP_LOG"
echo ""
echo "Pour arreter :"
echo "  kill $PHP_PID"
echo "  brew services stop mysql"
echo ""
echo "--- Apres avoir travaille, pousse tes changements :"
echo "  git add -A"
echo '  git commit -m "description du changement"'
echo "  git push"
echo ""
