# Copilot Instructions for Center-Domiciliation-App

## Project Overview
This is a Python-based desktop application for managing company domiciliation services. The application uses Tkinter for the GUI and follows a multi-form architecture with three main components:

1. Main Application (`Main_app.py`): Handles data entry and document generation
2. Dashboard (`dashboard.py`): Manages data visualization and record management
3. App Switcher (`app_switcher.py`): Controls navigation between components

## Key Architecture Patterns

### Data Structure
- Data is stored in Excel files using `pandas` and `openpyxl`
- Three main data tables: Societies, Associates, and Contracts
- File path management handled by `PathManager` in `utils.py`

### UI Components
- Uses Tkinter with custom theming (dark/light modes)
- Follows a tabbed interface pattern for data entry
- Implements scrollable TreeViews for data display
- Custom widget factory pattern (`WidgetFactory`) for consistent UI elements

### Document Generation
- Uses `docxtpl` for Word document template processing
- Template files stored in `Models/` directory
- Supports both Word and PDF output formats

## Project Conventions

### File Organization
- Models/ - Document templates (.docx)
- databases/ - Excel data files
- config/ - Application configuration
- utils.py - Shared utilities and managers
- Main components in root directory

### Code Patterns
- Class-based architecture for main components
- Event-driven UI interactions
- Theme management through `ThemeManager`
- Consistent error handling with messagebox dialogs
- Use of StringVar for form field bindings

## Development Workflow
1. Environment Setup: Python 3.x with dependencies in `requirements.txt`
2. Local Development: Ensure Models/ and databases/ directories exist
3. Testing: Manual UI testing recommended before commits
4. Data Validation: Input validation handled at form level

## Common Tasks

### Adding New Fields
1. Add to relevant header list in `Main_app.py`
2. Update form initialization in appropriate section
3. Include in `collect_values()` method
4. Update dashboard column configuration

### Theme Customization
1. Modify `ThemeManager` in `utils.py`
2. Update style configurations in setup methods
3. Use consistent color variables from theme manager

### Document Templates
- Located in `Models/` directory
- Use Jinja2 syntax for variable replacement
- Reference field names from form collections

## Integration Points
- Excel data store: `databases/DataBase_domiciliation.xlsx`
- Word templates: `Models/*.docx`
- Configuration: `config/preferences.json`

## Dependencies
- Core: pandas, openpyxl
- UI: tkinter, tkcalendar
- Documents: python-docx, docxtpl, docx2pdf
- See `requirements.txt` for complete list
