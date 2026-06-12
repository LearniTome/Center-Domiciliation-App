#!/bin/bash
# ==========================================
#  Center Domiciliation - Setup (macOS/Linux)
#  Détection automatique de Homebrew
# ==========================================

set -e

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
PROJECT_NAME="$(basename "$PROJECT_ROOT")"
PHP_PORT=8080
URL="http://localhost:$PHP_PORT/"

echo "========================================"
echo "  Center Domiciliation - Setup"
echo "========================================"
echo ""

# ----- Détection OS -----
if [[ "$OSTYPE" == "darwin"* ]]; then
    echo "[OS] macOS detecte"
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "[OS] Linux detecte"
else
    echo "[OS] Non supporte : $OSTYPE"
    echo "      Installe PHP + MySQL manuellement"
    exit 1
fi

# ----- Synchronisation Git -----
if command -v git &>/dev/null; then
    if [ -d "$PROJECT_ROOT/.git" ]; then
        echo "[Git] Synchronisation avec le depot distant..."
        pushd "$PROJECT_ROOT" &>/dev/null
        HAS_CHANGES=$(git status --porcelain 2>/dev/null)
        STASHED=false
        if [ -n "$HAS_CHANGES" ]; then
            git stash push -m "auto-stash setup.sh $(date '+%Y-%m-%d %H:%M:%S')" 2>/dev/null && STASHED=true
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

# ----- Homebrew -----
echo "[1/9] Verification de Homebrew..."
if ! command -v brew &>/dev/null; then
    echo "      Homebrew introuvable. Installation..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    if [[ "$OSTYPE" == "darwin"* && "$(uname -m)" == "arm64" ]]; then
        eval "$(/opt/homebrew/bin/brew shellenv)"
    fi
    if ! command -v brew &>/dev/null; then
        echo "[ERREUR] Echec installation Homebrew"
        exit 1
    fi
    echo "      Homebrew installe avec succes"
else
    echo "      Homebrew : present"
fi

echo "      Mise a jour de Homebrew..."
brew update &>/dev/null
echo "      Homebrew a jour"

echo ""

# ----- Installation des paquets -----
echo "[2/9] Installation des prerequis..."

install_pkg() {
    local name="$1"
    local pkg="$2"
    local cask="$3"
    if ! command -v "$name" &>/dev/null; then
        echo "      Installation de $pkg..."
        if [ -n "$cask" ]; then
            brew install --cask "$pkg" &>/dev/null
        else
            brew install "$pkg" &>/dev/null
        fi
        echo "      $pkg installe"
    else
        echo "      $pkg : present"
    fi
}

install_pkg "php" "php"
install_pkg "mysql" "mysql"
install_pkg "node" "node"
install_pkg "composer" "composer"

echo ""

# ----- MySQL -----
echo "[3/9] Demarrage de MySQL..."
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
        exit 1
    fi
else
    echo "      [MySQL] Deja en cours"
fi

# ----- Import base de donnees -----
echo "[4/9] Import de la base de donnees..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS center_domiciliation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" 2>/dev/null

SCHEMA_FILE="$PROJECT_ROOT/database/schema.sql"
SEED_FILE="$PROJECT_ROOT/database/seed.sql"
IMPORT_FILE="$PROJECT_ROOT/database/import.sql"

import_sql() {
    local file="$1"
    if [ ! -f "$file" ]; then return 1; fi
    mysql -u root --default-character-set=utf8mb4 < "$file" 2>/dev/null
}

if import_sql "$IMPORT_FILE"; then
    echo "      Base importee avec succes"
elif [ -f "$IMPORT_FILE" ]; then
    echo "[ERREUR] Echec import"
else
    import_sql "$SCHEMA_FILE" && echo "      Schema OK" || echo "[ERREUR] Echec schema"
    import_sql "$SEED_FILE" && echo "      Seed OK" || echo "[ERREUR] Echec seed"
fi

for m in migration_add_tribunal_type.sql migration_rename_columns.sql migration_rbac.sql; do
    M_PATH="$PROJECT_ROOT/database/$m"
    if [ -f "$M_PATH" ]; then
        echo -n "      Migration : $m... "
        if import_sql "$M_PATH"; then
            echo "OK"
        else
            echo "Ignoree"
        fi
    fi
done

# ----- Dossiers -----
echo "[5/9] Verification des dossiers..."
for d in uploads output dossiers_dom templates; do
    if [ ! -d "$PROJECT_ROOT/$d" ]; then
        mkdir -p "$PROJECT_ROOT/$d"
        echo "      $d/ cree"
    fi
done
echo "      OK"

# ----- Composer -----
echo "[6/9] Installation des dependances Composer..."
if [ ! -d "$PROJECT_ROOT/vendor" ]; then
    if command -v composer &>/dev/null; then
        pushd "$PROJECT_ROOT" &>/dev/null
        composer install --no-interaction 2>/dev/null && echo "      Dependances installees (phpword + dompdf)"
        popd &>/dev/null
    fi
else
    echo "      Dependances deja presentes"
fi

# ----- LibreOffice -----
echo "[7/9] Verification de LibreOffice..."
if ! command -v soffice &>/dev/null && [ ! -f "/Applications/LibreOffice.app/Contents/MacOS/soffice" ]; then
    echo "      LibreOffice non trouve. Installation via Homebrew Cask..."
    brew install --cask libreoffice &>/dev/null || true
    if command -v soffice &>/dev/null; then
        echo "      LibreOffice : present"
    else
        echo "      [INFO] LibreOffice non installe. Conversion PDF via PHPWord/Dompdf (fallback)."
    fi
else
    echo "      LibreOffice : present"
fi

# ----- MCP Servers -----
echo "[8/9] Preparation des serveurs MCP..."
if command -v node &>/dev/null; then
    echo "      Node.js : $(node --version)"
    npx -y @modelcontextprotocol/server-memory --version &>/dev/null 2>&1 || true
    npx -y @berthojoris/mcp-mysql-server "mysql://root@127.0.0.1:3306/center_domiciliation" "list,read" --version &>/dev/null 2>&1 || true
    echo "      Packages MCP pre-caches"
fi

# ----- Demarrage PHP Server -----
echo "[9/9] Demarrage du serveur PHP..."
# Tuer un eventuel processus existant
lsof -ti:$PHP_PORT 2>/dev/null | xargs kill -9 2>/dev/null || true
sleep 1

PHP_LOG="$PROJECT_ROOT/php-server.log"
nohup php -S "localhost:$PHP_PORT" -t "$PROJECT_ROOT" > "$PHP_LOG" 2>&1 &
PHP_PID=$!
sleep 2

echo "      Serveur PHP lance sur le port $PHP_PORT (PID: $PHP_PID)"
echo "      Logs : $PHP_LOG"

# ----- Verification finale -----
echo ""
echo "--- Verification finale ---"
if command -v curl &>/dev/null; then
    if curl -s -o /dev/null -w "%{http_code}" "$URL" 2>/dev/null | grep -q 200; then
        echo "[HTTP] Application repond (200 OK)"
    else
        echo "[HTTP] Application non accessible"
    fi
fi

echo ""
echo "========================================"
echo "  Setup termine avec succes !"
echo "========================================"
echo ""
echo "Application :  $URL"
echo "phpMyAdmin :   (non disponible - utilise TablePlus ou Sequel Ace)"
echo ""
echo "Prochaine etape :"
echo "  1. Configure l'API Claude dans config/ai.local.php (optionnel)"
echo "  2. Connecte-toi avec les identifiants de seed.sql"
echo ""

open "$URL" 2>/dev/null || xdg-open "$URL" 2>/dev/null || echo "Ouvre ce lien : $URL"
