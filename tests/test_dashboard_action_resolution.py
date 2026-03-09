import unittest

import pandas as pd

from src.forms.main_form import MainForm


class DashboardActionResolutionTests(unittest.TestCase):
    def setUp(self):
        self.form = MainForm.__new__(MainForm)

    def test_infer_dashboard_page_key_from_payload(self):
        self.assertEqual(
            MainForm._infer_dashboard_page_key({'ID_ASSOCIE': 'A-1', 'ID_SOCIETE': 'S-1'}, None),
            'associe',
        )
        self.assertEqual(
            MainForm._infer_dashboard_page_key({'ID_CONTRAT': 'C-1', 'ID_SOCIETE': 'S-1'}, None),
            'contrat',
        )
        self.assertEqual(
            MainForm._infer_dashboard_page_key({'ID_SOCIETE': 'S-1', 'DEN_STE': 'ACME'}, None),
            'societe',
        )

    def test_resolve_company_payload_from_associe_row(self):
        soc_df = pd.DataFrame(
            [
                {'ID_SOCIETE': 'S-1', 'DEN_STE': 'ACME', 'FORME_JUR': 'SARL'},
                {'ID_SOCIETE': 'S-2', 'DEN_STE': 'BETA', 'FORME_JUR': 'SARL AU'},
            ]
        )
        payload = {'ID_ASSOCIE': 'A-7', 'ID_SOCIETE': 'S-2', 'PRENOM': 'Sara'}

        resolved = self.form._resolve_company_payload_from_dashboard('associe', payload, soc_df)

        self.assertEqual(resolved['ID_SOCIETE'], 'S-2')
        self.assertEqual(resolved['DEN_STE'], 'BETA')

    def test_build_dashboard_edit_values_collects_related_rows(self):
        company_payload = {
            'ID_SOCIETE': 'S-9',
            'DEN_STE': 'NOVA',
            'FORME_JUR': 'SARL',
            'CAPITAL': '10000',
        }
        assoc_df = pd.DataFrame(
            [
                {'ID_ASSOCIE': 'A-1', 'ID_SOCIETE': 'S-9', 'PRENOM': 'Ali', 'NOM': 'One', 'PARTS': '50'},
                {'ID_ASSOCIE': 'A-2', 'ID_SOCIETE': 'S-9', 'PRENOM': 'Mina', 'NOM': 'Two', 'PARTS': '50'},
                {'ID_ASSOCIE': 'A-3', 'ID_SOCIETE': 'S-8', 'PRENOM': 'Else', 'NOM': 'Other', 'PARTS': '10'},
            ]
        )
        contrat_df = pd.DataFrame(
            [
                {'ID_CONTRAT': 'C-1', 'ID_SOCIETE': 'S-9', 'DATE_CONTRAT': '2026-03-01', 'LOYER_MENSUEL_TTC': '1200'},
                {'ID_CONTRAT': 'C-2', 'ID_SOCIETE': 'S-8', 'DATE_CONTRAT': '2026-02-01', 'LOYER_MENSUEL_TTC': '800'},
            ]
        )

        values = self.form._build_dashboard_edit_values(company_payload, assoc_df, contrat_df)

        self.assertEqual(values['societe']['denomination'], 'NOVA')
        self.assertEqual(values['societe']['capital'], '10000')
        self.assertEqual(len(values['associes']), 2)
        self.assertEqual(values['associes'][0]['prenom'], 'Ali')
        self.assertEqual(values['contrat']['date_contrat'], '2026-03-01')
        self.assertEqual(values['contrat']['prix_mensuel'], '1200')


if __name__ == '__main__':
    unittest.main()
