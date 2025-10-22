#!/usr/bin/env bash
# POSIX setup script for macOS / Linux
# Usage: ./scripts/setup_env.sh [--recreate] [--python python3]
set -euo pipefail

RECREATE=0
PYTHON=python3
NO_INSTALL=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --recreate) RECREATE=1; shift;;
    --python) PYTHON="$2"; shift 2;;
    --no-install) NO_INSTALL=1; shift;;
    -h|--help)
      echo "Usage: $0 [--recreate] [--python python3] [--no-install]"
      exit 0
      ;;
    *) echo "Unknown arg: $1"; exit 1;;
  esac
done

if ! command -v "$PYTHON" >/dev/null 2>&1; then
  echo "Python not found: $PYTHON. Please install Python 3.10+ or pass --python /path/to/python3"
  exit 1
fi

VENV_DIR="venv"

if [ -d "$VENV_DIR" ]; then
  if [ "$RECREATE" -eq 1 ]; then
    echo "Removing existing venv..."
    rm -rf "$VENV_DIR"
  else
    echo "Virtual environment already exists at $VENV_DIR"
  fi
fi

if [ ! -d "$VENV_DIR" ]; then
  echo "Creating virtual environment..."
  "$PYTHON" -m venv "$VENV_DIR"
fi

ACTIVATE_CMD="source $VENV_DIR/bin/activate"
echo "To activate the venv in this shell run: $ACTIVATE_CMD"

if [ "$NO_INSTALL" -eq 1 ]; then
  echo "Skipping dependency installation (--no-install)."
  exit 0
fi

PIP="$VENV_DIR/bin/pip"
if [ ! -x "$PIP" ]; then
  echo "pip not found inside venv. Exiting."
  exit 1
fi

REQ_FILE="requirements.txt"
if [ -f "requirements-windows.txt" ] && [[ "$(uname -s)" == MINGW* || "$(uname -s)" == MSYS* || "$(uname -s)" == CYGWIN* ]]; then
  REQ_FILE="requirements-windows.txt"
fi

if [ -f "requirements-windows.txt" ] && [ "$(uname -s)" = "Linux" ]; then
  # prefer normal requirements on Linux
  REQ_FILE="requirements.txt"
fi

if [ -f "$REQ_FILE" ]; then
  echo "Upgrading pip and installing dependencies from $REQ_FILE"
  "$PIP" install --upgrade pip
  "$PIP" install -r "$REQ_FILE"
else
  echo "No requirements file found. Skipping pip install."
fi

echo "Setup complete. Activate the venv with: $ACTIVATE_CMD"
