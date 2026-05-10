# PHP/XAMPP Migration Master Prompt

Use this prompt to rebuild the current application as a PHP web app running on XAMPP on Windows, with MySQL managed through phpMyAdmin.

```text
You are a senior full-stack migration engineer. Your task is to transform the existing "Center-Domiciliation-App" project into a PHP application that runs on XAMPP for Windows.

Project goal:
- Replace the current desktop/Tkinter Python application with a PHP-based web application.
- Use `index.php` as the main entry point.
- Use MySQL as the database.
- Manage the database through phpMyAdmin.
- Ensure the project runs locally inside XAMPP on Windows.
- Preserve the existing business logic, workflows, and core data structure as much as possible.

Target stack:
- PHP 8.x compatible with XAMPP
- Apache from XAMPP
- MySQL/MariaDB from XAMPP
- phpMyAdmin for database administration
- HTML5, CSS3, vanilla JavaScript unless a very small library is clearly justified
- No Python runtime in the final app

Main functional expectations:
- A dashboard/home page loaded from `index.php`
- Forms for all current business entities and workflows
- CRUD operations for all relevant data
- Data validation on both client and server side
- Document/template related workflows migrated in a practical web-friendly way
- Clean navigation between pages
- Error handling with useful user-facing messages
- Configuration that works on Windows with XAMPP out of the box

Database expectations:
- Analyze the current SQLite/database structure and map it to MySQL
- Create SQL migration files for MySQL/phpMyAdmin import
- Define tables, primary keys, foreign keys, indexes, and constraints clearly
- Normalize obvious issues where helpful, but do not break current business behavior
- Provide seed or sample data only if needed for local testing

Architecture expectations:
- Organize the app into a simple maintainable PHP structure, for example:
  - `index.php`
  - `config/`
  - `includes/`
  - `pages/`
  - `actions/`
  - `assets/css/`
  - `assets/js/`
  - `database/`
  - `templates/`
- Use reusable includes for header, footer, navigation, and database connection
- Keep code readable and modular
- Avoid overengineering or framework lock-in unless explicitly requested

Migration workflow:
1. Inspect the current repository and summarize:
   - existing features
   - current data model
   - document generation flows
   - critical screens and actions
2. Propose the PHP page map and folder structure.
3. Design the MySQL schema and provide importable SQL files.
4. Implement the PHP application incrementally.
5. Replace desktop-only interactions with browser-friendly UX.
6. Add XAMPP setup instructions for Windows.
7. Validate that the app can run from `htdocs` with `http://localhost/...`

Implementation rules:
- Use `mysqli` or `PDO` consistently; prefer `PDO`.
- Store database connection settings in a dedicated config file.
- Sanitize and validate all user input.
- Use prepared statements everywhere.
- Protect against common web vulnerabilities:
  - SQL injection
  - XSS
  - CSRF for form submissions where appropriate
- Keep styling clean and professional.
- Use UTF-8 everywhere.
- Prefer French labels/messages where the current app already uses French.
- Preserve important existing terminology from the current app.

Windows/XAMPP constraints:
- The final project should be easy to copy into `C:\\xampp\\htdocs\\Center-Domiciliation-App`
- Document how to:
  - start Apache and MySQL in XAMPP
  - create/import the database in phpMyAdmin
  - configure connection credentials
  - open the app in the browser

Required deliverables:
- `index.php` as the entry point
- PHP pages/components for all key workflows
- MySQL SQL schema file(s)
- XAMPP setup instructions
- Notes about any features that cannot be migrated exactly and the chosen replacement

Important behavior:
- Do not delete working functionality unless you provide an equivalent web version.
- If a feature is unclear, inspect the current codebase first and infer the intended behavior from the UI and data flow.
- When making assumptions, state them briefly and continue.
- Prefer shipping a working end-to-end local version before polishing.

Execution style:
- Work in small, verifiable steps.
- After each major step, summarize what changed.
- Show the files created/updated.
- Keep the app runnable throughout the migration whenever possible.
```

Suggested branch purpose:
- Use the `php` branch for the full migration from Python desktop UI to a PHP/XAMPP web application.
