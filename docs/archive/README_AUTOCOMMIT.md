Auto commit watcher
===================

This small script watches the repository for file changes and runs `git add --all`, `git commit` and `git push origin <branch>` automatically.

Usage
-----
1. Activate your virtualenv

```powershell
& .\venv\Scripts\Activate.ps1
```

2. Install the dependency

```powershell
pip install watchdog
```

3. Run the watcher (example)

```powershell
python .\scripts\auto_commit.py --message "auto-save" --branch chore/auto --exclude venv --exclude .git
```

Notes & security
----------------
- This will commit all changes. Do NOT run on repositories that contain secrets or production assets.
- Use a dedicated branch (like `chore/auto`) and create a PR for review rather than pushing directly to `main` or `master`.
- The script uses 'git' on PATH. Ensure your authentication (SSH key or credential helper) is configured.

Customization
-------------
- You can change the debounce interval by editing `DEBOUNCE` in the script.
- Exclusions can be passed with `--exclude` or edited in the defaults.
