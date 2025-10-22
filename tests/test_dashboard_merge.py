import pandas as pd


def test_dashboard_merge_row_structure():
    """Ensure that combining societe/associes/contrat into a single DataFrame row
    produces a DataFrame with the expected keys and preserves associes as a list."""
    societe_vals = {'denomination': 'ACME SARL', 'siren': '123456789'}
    associes_vals = [
        {'nom': 'Dupont', 'prenom': 'Jean', 'parts': '50'},
        {'nom': 'Martin', 'prenom': 'Alice', 'parts': '50'},
    ]
    contrat_vals = {'date_debut': '01/01/2025', 'duree': '99'}

    all_values = {
        'societe': societe_vals,
        'associes': associes_vals,
        'contrat': contrat_vals,
    }

    df = pd.DataFrame([all_values])

    assert 'societe' in df.columns
    assert 'associes' in df.columns
    assert 'contrat' in df.columns

    # The associes cell should contain the original list
    assert isinstance(df.loc[0, 'associes'], list)
    assert df.loc[0, 'societe']['denomination'] == 'ACME SARL'
