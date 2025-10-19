# Change list / recommended cleanup for Center-Domiciliation-App

This file lists recommended changes, removals and minor refactors to clean up
the repository and improve maintainability. Apply these as small patches or
commits so they are reviewable.

## 1) Remove temporary / backup files

- Delete any `*_new.py` files created during refactoring (e.g. `associe_form_new.py`, `societe_form_new.py`) if they are not intended to remain.
- Remove backup files like `Main_app.py.bak`.
- Remove `app.log` from the repo and add it to `.gitignore` (already done).

## 2) Ignore build artifacts and environment

- Add `.gitignore` entries for `__pycache__`, `*.pyc`, venv directories, and `databases/*.xlsx`.

## 3) Consolidate and apply consistent layout/styling

- Ensure forms use a single layout manager (prefer `grid` inside each section) to avoid packing issues.
- Apply `ThemeManager.apply_widget_styles()` or `apply_style()` for each widget where appropriate so theme is consistent.

## 4) Templates and assets

- Keep the `Models/` templates in repo; consider moving example templates to `Models/examples/` and document which templates are used by which generator.

## 5) Small code refactors

- Move large form building code into small helper methods (already applied for `associe_form.py` and `societe_form.py` new versions).
- Add a small smoke test script under `tests/` to instantiate `MainApp` and ensure forms construct without exceptions.

## 6) Next steps

1. Run the app locally and validate UI flows.
2. Remove leftover temporary files after validation.
3. Add unit tests for `PathManager`, `ErrorHandler`, and `ThemeManager` helpers.

---
Applied patches recommended in this file are low risk; perform in small commits and run the app to validate.
