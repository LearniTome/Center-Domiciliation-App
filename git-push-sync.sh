#!/bin/bash
# Auto-export DB after git push
# Usage: git ps (push-sync)

PROJECT_DIR="$(git rev-parse --show-toplevel)"

# Real push
git.exe push "$@"
PUSH_EXIT=$?

if [ $PUSH_EXIT -eq 0 ]; then
    echo "[Sync] Export de la base..."
    powershell.exe -ExecutionPolicy Bypass -NoProfile -File "$PROJECT_DIR/post-push-sync.ps1"
fi

exit $PUSH_EXIT
