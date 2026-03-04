# 🤝 Guide de Contribution

Merci de votre intérêt à contribuer au projet **Center-Domiciliation-App**! Ce guide explique comment contribuer.

## 📋 Comment Contribuer

### 1. Signaler un Bug

**Vérifiez d'abord** que le bug n'a pas déjà été signalé:
- Consultez les [Issues GitHub](https://github.com/your-repo/issues)
- Cherchez des issues similaires

**Créez une nouvelle Issue** avec:
- **Titre clair**: Exemple: "PDF generation fails on Windows 11"
- **Description**: Qu'avez-vous essayé? Qu'est-il arrivé?
- **Étapes à reproduire**: 
  1. ...
  2. ...
- **Comportement attendu**: Ce qui devrait se passer
- **Comportement réel**: Ce qui s'est passé
- **Logs**: Contenu de `app.log` si applicable
- **Système**: OS, Python version, etc.

### 2. Proposer une Fonctionnalité

**Ouvrez une Discussion** ou une Issue avec:
- **Titre clair**: Exemple: "Add support for Excel export"
- **Description**: Pourquoi cette fonctionnalité?
- **Cas d'usage**: Qui en aurait besoin?
- **Design proposé**: Comment l'implémenter?

### 3. Soumettre du Code

#### a. Configuration Git

```bash
# 1. Fork le repository
# 2. Clone votre fork
git clone https://github.com/YOUR_USERNAME/center-domiciliation-app.git

# 3. Créer une branche feature
git checkout -b feature/your-feature-name
# ou pour un bug
git checkout -b bugfix/your-bug-name
```

#### b. Convention de Nommage des Branches

```
feature/descriptive-name    # Nouvelle fonctionnalité
bugfix/descriptive-name     # Correction de bug
docs/descriptive-name       # Documentation
refactor/descriptive-name   # Refactoring
test/descriptive-name       # Test improvements
```

#### c. Code Style

Suivez la [PEP 8](https://www.python.org/dev/peps/pep-0008/) et les conventions du projet:

```python
# ✓ Bon
def create_company(name: str, legal_form: str) -> dict:
    \"\"\"Create a new company with given details.\"\"\"
    company = {
        'name': name,
        'legal_form': legal_form
    }
    return company

# ✗ Mauvais
def createCompany(n, lf):
    c = {'n': n, 'lf': lf}
    return c
```

**Règles de style**:
- Use 4 spaces for indentation
- Type hints for function parameters
- Docstrings for all functions/classes
- Class names: PascalCase
- Function/variable names: snake_case
- Constants: UPPER_CASE

#### d. Messages de Commit

Suivez [Conventional Commits](https://www.conventionalcommits.org/):

```bash
git commit -m "feat: add export to Excel functionality"
git commit -m "fix: correct PDF conversion for SARL documents"
git commit -m "docs: update API documentation"
git commit -m "test: add integration tests for dashboard"
git commit -m "refactor: simplify template rendering logic"
git commit -m "chore: update dependencies"
```

**Format**:
```
<type>(<scope>): <subject>

<body>

<footer>
```

Types: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`  
Scope: `forms`, `utils`, `templates`, `dashboard`, `generation`, etc.

#### e. Avant de Proposer une Pull Request

```bash
# 1. Mettez à jour depuis main
git fetch origin
git rebase origin/develop

# 2. Testez votre code
python -m pytest -q

# 3. Vérifiez la couverture
python -m pytest --cov=src tests/

# 4. Vérifiez le style
python -m pylint src/
python -m black --check src/

# 5. Nettoyez vos commits
git rebase -i origin/develop
```

#### f. Créer une Pull Request

1. **Push votre branche**
   ```bash
   git push origin feature/your-feature-name
   ```

2. **GitHub créera un PR automatiquement**

3. **Remplissez le template PR**:
   - **Description**: Qu'avez-vous changé?
   - **Type**: Feature/Bug fix/Documentation/Refactor
   - **Testing**: Comment avez-vous testé?
   - **Checklist**: Cochez les items complétés

4. **Attendez la review**

## 📖 Documentation

### Avant de commencer

Consultez:
- [Architecture](../architecture/ARCHITECTURE.md) - Comment le code est organisé
- [API Documentation](../architecture/API.md) - Comment utiliser les classes clés
- [Copilot Instructions](../../.github/copilot-instructions.md) - Patterns du projet

### Commenter votre Code

```python
# ✓ Bon
def calculate_share_percentage(shares: int, total_shares: int) -> float:
    \"\"\"
    Calculate the percentage of shares held by an associate.
    
    Args:
        shares: Number of shares held
        total_shares: Total shares in company
        
    Returns:
        Percentage as float (e.g., 25.5)
        
    Raises:
        ValueError: If total_shares is 0
    \"\"\"
    if total_shares == 0:
        raise ValueError("Total shares cannot be 0")
    return (shares / total_shares) * 100
```

## 🧪 Tests

### Écrire des Tests

```python
# tests/test_example.py
import pytest
from src.utils.example import calculate_share_percentage

def test_calculate_share_percentage():
    \"\"\"Test share percentage calculation.\"\"\"
    assert calculate_share_percentage(50, 200) == 25.0
    
def test_calculate_share_percentage_zero_division():
    \"\"\"Test that zero total shares raises ValueError.\"\"\"
    with pytest.raises(ValueError):
        calculate_share_percentage(50, 0)
```

### Exécuter les Tests

```bash
# Tous les tests
python -m pytest -q

# Tests spécifiques
python -m pytest tests/test_example.py -v

# Avec couverture
python -m pytest --cov=src tests/
```

**Objectifs**:
- ✅ 80%+ code coverage
- ✅ All tests pass
- ✅ New features have tests
- ✅ Bug fixes include regression tests

## 🔄 Processus de Review

1. **Your PR is reviewed** by maintainers
2. **Feedback is provided** if changes needed
3. **You make requested changes**
4. **PR is approved and merged**

**Être patient** - Les reviews prennent du temps!

## 📋 Checklist avant la Soumission

- [ ] Code suit la PEP 8 et le style du projet
- [ ] Tous les tests passent: `pytest -q`
- [ ] Couverture de code: `pytest --cov=src`
- [ ] Docstrings écrites pour toutes les fonctions
- [ ] Messages de commit suivent la convention
- [ ] PR template est rempli complètement
- [ ] Pas de secrets/credentials en dur
- [ ] Branche est à jour avec develop

## 🎓 Ressources

- [PEP 8 Style Guide](https://www.python.org/dev/peps/pep-0008/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Google Python Style Guide](https://google.github.io/styleguide/pyguide.html)
- [Real Python - Writing Comments](https://realpython.com/python-comments-and-docstrings/)

## ❓ Questions?

1. **Avant de commencer**: Ouvrez une Issue pour discuter
2. **Pendant le développement**: Commentez votre code
3. **Lors de la review**: Répondez aux questions
4. **Après la merge**: Aidez avec la maintenance

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026

Merci de contribuer! 🚀
