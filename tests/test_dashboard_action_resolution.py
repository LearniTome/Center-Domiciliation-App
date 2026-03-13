import unittest

import pandas as pd

from src.forms.main_form import MainForm


class DashboardActionResolutionTests(unittest.TestCase):
    def setUp(self):
        self.form = MainForm.__new__(MainForm)

    def test_infer_dashboard_page_key_from_payload(self):
        self.assertEqual(
            MainForm._infer_dashboard_page_key({'id_associe': 'A-1', 'id_societe': 'S-1'}, None),
            'associe',
        )
        self.assertEqual(
            MainForm._infer_dashboard_page_key({'id_contrat': 'C-1', 'id_societe': 'S-1'}, None),
            'contrat',
        )
        self.assertEqual(
            MainForm._infer_dashboard_page_key({'id_societe': 'S-1', 'den_ste': 'ACME'}, None),
            'societe',
        )

    def test_resolve_company_payload_from_associe_row(self):
        soc_df = pd.DataFrame(
            [
                {'id_societe': 'S-1', 'den_ste': 'ACME', 'forme_jur': 'SARL'},
                {'id_societe': 'S-2', 'den_ste': 'BETA', 'forme_jur': 'SARL AU'},
            ]
        )
        payload = {'id_associe': 'A-7', 'id_societe': 'S-2', 'prenom': 'Sara'}

        resolved = self.form._resolve_company_payload_from_dashboard('associe', payload, soc_df)

        self.assertEqual(resolved['id_societe'], 'S-2')
        self.assertEqual(resolved['den_ste'], 'BETA')

    def test_build_dashboard_edit_values_collects_related_rows(self):
        company_payload = {
            'id_societe': 'S-9',
            'den_ste': 'NOVA',
            'forme_jur': 'SARL',
            'capital': '10000',
        }
        assoc_df = pd.DataFrame(
            [
                {'id_associe': 'A-1', 'id_societe': 'S-9', 'prenom': 'Ali', 'nom': 'One', 'parts': '50'},
                {'id_associe': 'A-2', 'id_societe': 'S-9', 'prenom': 'Mina', 'nom': 'Two', 'parts': '50'},
                {'id_associe': 'A-3', 'id_societe': 'S-8', 'prenom': 'Else', 'nom': 'Other', 'parts': '10'},
            ]
        )
        contrat_df = pd.DataFrame(
            [
                {'id_contrat': 'C-1', 'id_societe': 'S-9', 'date_contrat': '2026-03-01', 'loyer_mensuel_ttc': '1200'},
                {'id_contrat': 'C-2', 'id_societe': 'S-8', 'date_contrat': '2026-02-01', 'loyer_mensuel_ttc': '800'},
            ]
        )

        values = self.form._build_dashboard_edit_values(company_payload, assoc_df, contrat_df, pd.DataFrame())

        self.assertEqual(values['societe']['denomination'], 'NOVA')
        self.assertEqual(values['societe']['capital'], '10000')
        self.assertEqual(len(values['associes']), 2)
        self.assertEqual(values['associes'][0]['prenom'], 'Ali')
        self.assertEqual(values['contrat']['date_contrat'], '2026-03-01')
        self.assertEqual(values['contrat']['prix_mensuel'], '1200')


if __name__ == '__main__':
    unittest.main()
