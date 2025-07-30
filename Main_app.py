import os
from datetime import datetime
from pathlib import Path
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from tkcalendar import Calendar
from docxtpl import DocxTemplate
import openpyxl
from docx2pdf import convert
import pandas as pd

# Define headers for the Excel file
excel_headers = [
    "DEN_STE", "FORME_JUR", "ICE", "CAPITAL", "PART_SOCIAL", "NATIONALITY", "STE_ADRESS", "DATE_ICE",
    "CIVIL", "PRENOM","NOM", "DATE_NAISS", "LIEU_NAISS", "GERANT_ADRESS", "CIN_NUM",
    "CIN_VALIDATY", "GERANT_QUALITY", "GERANT_PHONE", "GERANT_EMAIL",
    "TRIBUNAL", "PERIOD_DOMCIL", "DOM_DATEDEB", "DOM_DATEFIN",
    "DATE_CONTRAT", "PRIX_CONTRAT", "PRIX_INTERMEDIARE_CONTRAT", "INTERMEDIARE"
]

from utils import PathManager

# Path of the template files
PathManager.ensure_directories()

docAnnonceJournal = DocxTemplate(PathManager.get_model_path("My_Annonce_Journal.docx"))
docAttestDomicil = DocxTemplate(PathManager.get_model_path("My_Attest_domiciliation.docx"))
docContratDomicil = DocxTemplate(PathManager.get_model_path("My_Contrat_domiciliation.docx"))
docDeclImmRc = DocxTemplate(PathManager.get_model_path("My_Décl_Imm_Rc.docx"))
docDepotLegalRc = DocxTemplate(PathManager.get_model_path("My_Dépot_Légal.docx"))
docStatuts = DocxTemplate(PathManager.get_model_path("My_Statuts_SARL_AU.docx"))

# Identifier Dictionary of input
DenSte = ["ASTRAPIA", "SAOUZ", "F 4", "OLA MOVING", "LOHACOM", "SKY NEST", "SKY MA", "MAROFLEET"]
Civility = ["Monsieur","Madame"]
Formjur = ["SARL AU", "SARL", "SA"]
Nbmois = ["06", "12","15", "24"]
Capital = ["10 000", "50 000", "100 000"]
PartsSocial = ["100", "200", "500", "1000"]
Nationalite = ["Marocaine", "Cameronnie"]
SteAdresse = ["46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA",
             "56  BOULEVARD MOULAY YOUSSEF 3EME ETAGE APPT 14, CASABLANCA",
             "96 BD D'ANFA ETAGE 9 APPT N° 91 RESIDENCE LE PRINTEMPS D'ANFA",
             "61 Av Lalla Yacout Angle Mustapha El Maani 2eme Etage N°62 Centre Riad",
             "Bd Zerktouni Et Angle Rue Ibn Al Moualim N° 4, Etage 2, Appt N°10"]
Tribunnaux = ["Casablanca", "Berrechid", "Mohammedia"]
QualityGerant = ["Associé Gérant", "Associé Unique Gérant", "Associé"]
Activities = ["Travaux Divers ou de Construction", "Marchand effectuant Import Export",
             "Négociant", "Conseil de Gestion"]

# get the date of today
today = datetime.today()
date_inverser = today.strftime("%Y_%m_%d")

from utils import ThemeManager, WidgetFactory, WindowManager

class DomiciliationApp:
    def __init__(self, root):
        self.root = root
        if isinstance(root, tk.Tk):
            self.root.title("Genérateurs Docs Juridiques")

            # Configuration du thème
            self.theme_manager = ThemeManager(self.root)
            self.style = self.theme_manager.style

            # Configuration des styles personnalisés pour les onglets
            self.style.configure('TNotebook.Tab',
                               padding=(15, 5),
                               font=('Segoe UI', 10, 'bold'))

        self.values = {}
        self.setup_gui()

    def setup_gui(self):
        # Create notebook for tabs
        self.notebook = ttk.Notebook(self.root)
        self.notebook.pack(fill='both', expand=True)

        # Create frames for each section
        self.frame_ste = ttk.Frame(self.notebook)
        self.frame_gerant = ttk.Frame(self.notebook)
        self.frame_contrat = ttk.Frame(self.notebook)

        # Add frames to notebook
        self.notebook.add(self.frame_ste, text='Infos Société')
        self.notebook.add(self.frame_gerant, text='Infos Gérant')
        self.notebook.add(self.frame_contrat, text='Infos Contrat')

        # Initialize all frames
        self.initialize_ste_section()  # Initialize société section
        self.initialize_gerant_frame() # Initialize gérant section
        self.initialize_contrat_frame() # Initialize contrat section

        # Create buttons frame at the bottom
        self.setup_buttons()

    def setup_buttons(self):
        # Conteneur principal pour les boutons
        self.buttons_frame = ttk.Frame(self.root)
        self.buttons_frame.pack(pady=15, side=tk.BOTTOM, fill=tk.X, padx=20)

        # Configuration de la grille pour l'alignement
        self.buttons_frame.grid_columnconfigure(2, weight=1)  # Espace flexible au milieu

        # Boutons principaux (à gauche)
        WidgetFactory.create_button(
            self.buttons_frame,
            text="📄 Documents Word",
            command=self.generer_docs,
            style='Action.TButton',
            tooltip="Générer tous les documents au format Word"
        ).grid(row=0, column=0, padx=5)

        WidgetFactory.create_button(
            self.buttons_frame,
            text="📑 Word et PDF",
            command=self.save_as_pdf,
            style='Action.TButton',
            tooltip="Générer tous les documents en Word et PDF"
        ).grid(row=0, column=1, padx=5)

        # Boutons de contrôle (à droite)
        WidgetFactory.create_button(
            self.buttons_frame,
            text="🆕 Nouvelle",
            command=self.clear_form,
            style='Secondary.TButton',
            tooltip="Effacer tous les champs pour une nouvelle société"
        ).grid(row=0, column=3, padx=5)

        WidgetFactory.create_button(
            self.buttons_frame,
            text="💾 Sauvegarder",
            command=self.save_to_database,
            style='Secondary.TButton',
            tooltip="Sauvegarder les informations dans la base de données"
        ).grid(row=0, column=4, padx=5)

        WidgetFactory.create_button(
            self.buttons_frame,
            text="❌ Quitter",
            command=self.root.quit,
            style='Secondary.TButton',
            tooltip="Fermer l'application"
        ).grid(row=0, column=5, padx=5)

    def initialize_ste_section(self):
        row = 0
        ttk.Label(self.frame_ste, text="Dénomination Société").grid(row=row, column=0, padx=5, pady=5)
        self.den_ste_var = tk.StringVar()
        self.den_ste_combo = ttk.Combobox(self.frame_ste, textvariable=self.den_ste_var, values=DenSte, width=40)
        self.den_ste_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        ttk.Label(self.frame_ste, text="Forme Juridique").grid(row=row, column=0, padx=5, pady=5)
        self.forme_jur_var = tk.StringVar()
        self.forme_jur_combo = ttk.Combobox(self.frame_ste, textvariable=self.forme_jur_var, values=Formjur, width=40)
        self.forme_jur_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        ttk.Label(self.frame_ste, text="Ice").grid(row=row, column=0, padx=5, pady=5)
        self.ice_var = tk.StringVar()
        ttk.Entry(self.frame_ste, textvariable=self.ice_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        ttk.Label(self.frame_ste, text="Date Ice").grid(row=row, column=0, padx=5, pady=5)
        self.date_ice_var = tk.StringVar()
        self.date_ice_entry = ttk.Entry(self.frame_ste, textvariable=self.date_ice_var, width=42)
        self.date_ice_entry.grid(row=row, column=1, padx=5, pady=5)
        ttk.Button(self.frame_ste, text="Choisir Date",
                  command=lambda: self.show_calendar(self.date_ice_var)).grid(row=row, column=2, padx=5, pady=5)
        row += 1

        # Capital
        ttk.Label(self.frame_ste, text="Capital").grid(row=row, column=0, padx=5, pady=5)
        self.capital_var = tk.StringVar()
        self.capital_combo = ttk.Combobox(self.frame_ste, textvariable=self.capital_var, values=Capital, width=40)
        self.capital_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Part Social
        ttk.Label(self.frame_ste, text="Part Social").grid(row=row, column=0, padx=5, pady=5)
        self.parts_social_var = tk.StringVar()
        self.parts_social_combo = ttk.Combobox(self.frame_ste, textvariable=self.parts_social_var,
                                             values=PartsSocial, width=40)
        self.parts_social_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Adresse Société
        ttk.Label(self.frame_ste, text="Adresse Société").grid(row=row, column=0, padx=5, pady=5)
        self.ste_adress_var = tk.StringVar()
        self.ste_adress_combo = ttk.Combobox(self.frame_ste, textvariable=self.ste_adress_var,
                                           values=SteAdresse, width=40)
        self.ste_adress_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Tribunal
        ttk.Label(self.frame_ste, text="Tribunal").grid(row=row, column=0, padx=5, pady=5)
        self.tribunal_var = tk.StringVar()
        self.tribunal_combo = ttk.Combobox(self.frame_ste, textvariable=self.tribunal_var,
                                        values=Tribunnaux, width=40)
        self.tribunal_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Activities
        for i in range(1, 7):
            ttk.Label(self.frame_ste, text=f"Activité {i}").grid(row=row, column=0, padx=5, pady=5)
            var_name = f"activity{i}_var"
            setattr(self, var_name, tk.StringVar())
            combo = ttk.Combobox(self.frame_ste, textvariable=getattr(self, var_name),
                               values=Activities, width=40)
            combo.grid(row=row, column=1, padx=5, pady=5)
            row += 1

    def show_calendar(self, var):
        top = tk.Toplevel(self.root)
        cal = Calendar(top, selectmode='day', date_pattern='dd-mm-yyyy')
        cal.pack(padx=5, pady=5)

        def set_date():
            var.set(cal.get_date())
            top.destroy()

        ttk.Button(top, text="OK", command=set_date).pack(pady=5)

    def initialize_gerant_frame(self):
        row = 0
        # Civilité Gérant
        ttk.Label(self.frame_gerant, text="Civilité Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.civil_var = tk.StringVar()
        self.civil_combo = ttk.Combobox(self.frame_gerant, textvariable=self.civil_var, values=Civility, width=40)
        self.civil_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Prénom Gérant
        ttk.Label(self.frame_gerant, text="Prénom Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.prenom_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.prenom_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Nom Gérant
        ttk.Label(self.frame_gerant, text="Nom Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.nom_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.nom_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # CIN Gérant
        ttk.Label(self.frame_gerant, text="N° CIN Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.cin_num_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.cin_num_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Validité CIN
        ttk.Label(self.frame_gerant, text="Validité du CIN").grid(row=row, column=0, padx=5, pady=5)
        self.cin_validaty_var = tk.StringVar()
        self.cin_validaty_entry = ttk.Entry(self.frame_gerant, textvariable=self.cin_validaty_var, width=42)
        self.cin_validaty_entry.grid(row=row, column=1, padx=5, pady=5)
        ttk.Button(self.frame_gerant, text="Choisir Date",
                  command=lambda: self.show_calendar(self.cin_validaty_var)).grid(row=row, column=2, padx=5, pady=5)
        row += 1

        # Qualité du Gérant
        ttk.Label(self.frame_gerant, text="Qualité du Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.gerant_quality_var = tk.StringVar()
        self.gerant_quality_combo = ttk.Combobox(self.frame_gerant, textvariable=self.gerant_quality_var,
                                                values=QualityGerant, width=40)
        self.gerant_quality_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Date Naissance
        ttk.Label(self.frame_gerant, text="Date de Naissance").grid(row=row, column=0, padx=5, pady=5)
        self.date_naiss_var = tk.StringVar()
        self.date_naiss_entry = ttk.Entry(self.frame_gerant, textvariable=self.date_naiss_var, width=42)
        self.date_naiss_entry.grid(row=row, column=1, padx=5, pady=5)
        ttk.Button(self.frame_gerant, text="Choisir Date",
                  command=lambda: self.show_calendar(self.date_naiss_var)).grid(row=row, column=2, padx=5, pady=5)
        row += 1

        # Lieu de Naissance
        ttk.Label(self.frame_gerant, text="Lieu de Naissance").grid(row=row, column=0, padx=5, pady=5)
        self.lieu_naiss_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.lieu_naiss_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Adresse Gérant
        ttk.Label(self.frame_gerant, text="Adresse Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.gerant_adress_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.gerant_adress_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Nationalité
        ttk.Label(self.frame_gerant, text="Nationalité").grid(row=row, column=0, padx=5, pady=5)
        self.nationality_var = tk.StringVar()
        self.nationality_combo = ttk.Combobox(self.frame_gerant, textvariable=self.nationality_var,
                                            values=Nationalite, width=40)
        self.nationality_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Téléphone Gérant
        ttk.Label(self.frame_gerant, text="Téléphone Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.gerant_phone_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.gerant_phone_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Email Gérant
        ttk.Label(self.frame_gerant, text="Email Gérant").grid(row=row, column=0, padx=5, pady=5)
        self.gerant_email_var = tk.StringVar()
        ttk.Entry(self.frame_gerant, textvariable=self.gerant_email_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

    def initialize_contrat_frame(self):
        row = 0
        # Date Contrat
        ttk.Label(self.frame_contrat, text="Date Contrat").grid(row=row, column=0, padx=5, pady=5)
        self.date_contrat_var = tk.StringVar()
        self.date_contrat_entry = ttk.Entry(self.frame_contrat, textvariable=self.date_contrat_var, width=42)
        self.date_contrat_entry.grid(row=row, column=1, padx=5, pady=5)
        ttk.Button(self.frame_contrat, text="Choisir Date",
                  command=lambda: self.show_calendar(self.date_contrat_var)).grid(row=row, column=2, padx=5, pady=5)
        row += 1

        # Période de Contrat
        ttk.Label(self.frame_contrat, text="Période de Contrat").grid(row=row, column=0, padx=5, pady=5)
        self.period_var = tk.StringVar()
        self.period_combo = ttk.Combobox(self.frame_contrat, textvariable=self.period_var, values=Nbmois, width=40)
        self.period_combo.grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Prix mensuel
        ttk.Label(self.frame_contrat, text="Prix mensuel de Contrat").grid(row=row, column=0, padx=5, pady=5)
        self.prix_mensuel_var = tk.StringVar()
        ttk.Entry(self.frame_contrat, textvariable=self.prix_mensuel_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Prix intermediaire
        ttk.Label(self.frame_contrat, text="Prix intermediaire Contrat").grid(row=row, column=0, padx=5, pady=5)
        self.prix_inter_var = tk.StringVar()
        ttk.Entry(self.frame_contrat, textvariable=self.prix_inter_var, width=42).grid(row=row, column=1, padx=5, pady=5)
        row += 1

        # Date Début Contrat
        ttk.Label(self.frame_contrat, text="Date Début Contrat").grid(row=row, column=0, padx=5, pady=5)
        self.date_debut_var = tk.StringVar()
        self.date_debut_entry = ttk.Entry(self.frame_contrat, textvariable=self.date_debut_var, width=42)
        self.date_debut_entry.grid(row=row, column=1, padx=5, pady=5)
        ttk.Button(self.frame_contrat, text="Choisir Date",
                  command=lambda: self.show_calendar(self.date_debut_var)).grid(row=row, column=2, padx=5, pady=5)
        row += 1

        # Date Fin Contrat
        ttk.Label(self.frame_contrat, text="Date Fin Contrat").grid(row=row, column=0, padx=5, pady=5)
        self.date_fin_var = tk.StringVar()
        self.date_fin_entry = ttk.Entry(self.frame_contrat, textvariable=self.date_fin_var, width=42)
        self.date_fin_entry.grid(row=row, column=1, padx=5, pady=5)
        ttk.Button(self.frame_contrat, text="Choisir Date",
                  command=lambda: self.show_calendar(self.date_fin_var)).grid(row=row, column=2, padx=5, pady=5)
        row += 1

    def collect_values(self):
        """Collect all values from the form fields"""
        self.values = {
            # Infos Société
            "DEN_STE": self.den_ste_var.get(),
            "FORME_JUR": self.forme_jur_var.get(),
            "ICE": self.ice_var.get(),
            "DATE_ICE": self.date_ice_var.get(),
            "CAPITAL": self.capital_var.get(),
            "PART_SOCIAL": self.parts_social_var.get(),
            "STE_ADRESS": self.ste_adress_var.get(),
            "TRIBUNAL": self.tribunal_var.get(),

            # Infos Gérant
            "CIVIL": self.civil_var.get(),
            "PRENOM": self.prenom_var.get(),
            "NOM": self.nom_var.get(),
            "CIN_NUM": self.cin_num_var.get(),
            "CIN_VALIDATY": self.cin_validaty_var.get(),
            "GERANT_QUALITY": self.gerant_quality_var.get(),
            "DATE_NAISS": self.date_naiss_var.get(),
            "LIEU_NAISS": self.lieu_naiss_var.get(),
            "GERANT_ADRESS": self.gerant_adress_var.get(),
            "NATIONALITY": self.nationality_var.get(),
            "GERANT_PHONE": self.gerant_phone_var.get(),
            "GERANT_EMAIL": self.gerant_email_var.get(),

            # Infos Contrat
            "DATE_CONTRAT": self.date_contrat_var.get(),
            "PERIOD_DOMCIL": self.period_var.get(),
            "PRIX_CONTRAT": self.prix_mensuel_var.get(),
            "PRIX_INTERMEDIARE_CONTRAT": self.prix_inter_var.get(),
            "DOM_DATEDEB": self.date_debut_var.get(),
            "DOM_DATEFIN": self.date_fin_var.get()
        }

        # Add activities
        for i in range(1, 7):
            var_name = f"activity{i}_var"
            self.values[f"ACTIVITY{i}"] = getattr(self, var_name).get()

    def insert_data_to_excel(self):
        """Insert form data into Excel file"""
        try:
            excel_file_path = os.path.join(os.path.dirname(__file__), 'databases', 'DataBase_domiciliation.xlsx')
            os.makedirs(os.path.dirname(excel_file_path), exist_ok=True)

            if not os.path.exists(excel_file_path):
                workbook = openpyxl.Workbook()
                workbook.save(excel_file_path)

            workbook = openpyxl.load_workbook(excel_file_path)
            if "DataBaseDom" not in workbook.sheetnames:
                workbook.create_sheet("DataBaseDom")
            sheet = workbook["DataBaseDom"]

            if sheet.max_row == 1:
                sheet.append(excel_headers)

            row_data = [self.values.get(key, '') for key in excel_headers]
            sheet.append(row_data)
            workbook.save(excel_file_path)

        except Exception as e:
            messagebox.showerror("Erreur",
                f"Erreur lors de l'insertion dans Excel : {str(e)}")

    def generer_docs(self):
        """Handle document generation with custom save location"""
        self.collect_values()

        try:
            if not self.values['DEN_STE']:
                messagebox.showerror("Erreur", "Veuillez entrer un nom de société!")
                return

            # Ask user for save location
            folder_name = f"{date_inverser}_DCons_{self.values['DEN_STE']}"
            folder_path = tk.filedialog.askdirectory(
                title="Choisir l'emplacement pour sauvegarder les documents Word"
            )

            if not folder_path:  # User cancelled
                return

            folder_path = os.path.join(folder_path, folder_name)
            os.makedirs(folder_path, exist_ok=True)

            # Generate DOCX files
            documents = {
                'Annonce_légale': docAnnonceJournal,
                'Attestation_Domiciliation': docAttestDomicil,
                'Contrat_Domiciliation': docContratDomicil,
                'Déclaration_Imm_Rc': docDeclImmRc,
                'Dépot_légal': docDepotLegalRc,
                'Statuts': docStatuts
            }

            for doc_name, template in documents.items():
                template.render(self.values)
                output_path = os.path.join(folder_path,
                    f"{date_inverser}_{doc_name}_{self.values['DEN_STE']}.docx")
                template.save(output_path)

            messagebox.showinfo("Succès",
                f"Documents créés avec succès dans le dossier:\n{folder_path}")

        except Exception as e:
            messagebox.showerror("Erreur", str(e))

    def save_as_pdf(self):
        """Generate or convert documents to PDF with custom save location"""
        try:
            self.collect_values()

            if not self.values['DEN_STE']:
                messagebox.showerror("Erreur", "Veuillez entrer un nom de société!")
                return

            # Ask user for save location
            folder_name = f"{date_inverser}_DCons_{self.values['DEN_STE']}"
            folder_path = tk.filedialog.askdirectory(
                title="Choisir l'emplacement pour sauvegarder les documents PDF"
            )

            if not folder_path:  # User cancelled
                return

            folder_path = os.path.join(folder_path, folder_name)
            os.makedirs(folder_path, exist_ok=True)

            # First generate DOCX files if they don't exist
            documents = {
                'Annonce_légale': docAnnonceJournal,
                'Attestation_Domiciliation': docAttestDomicil,
                'Contrat_Domiciliation': docContratDomicil,
                'Déclaration_Imm_Rc': docDeclImmRc,
                'Dépot_légal': docDepotLegalRc,
                'Statuts': docStatuts
            }

            for doc_name, template in documents.items():
                # Create DOCX
                template.render(self.values)
                docx_path = os.path.join(folder_path,
                    f"{date_inverser}_{doc_name}_{self.values['DEN_STE']}.docx")
                template.save(docx_path)

                # Convert to PDF
                pdf_path = docx_path.replace('.docx', '.pdf')
                convert(docx_path, pdf_path)

            messagebox.showinfo("Succès",
                f"Documents PDF créés avec succès dans le dossier:\n{folder_path}")

        except Exception as e:
            messagebox.showerror("Erreur", f"Erreur lors de la génération des PDF : {str(e)}")

    def save_to_database(self):
        """Save current form data to database"""
        try:
            self.collect_values()

            if not self.values['DEN_STE']:
                messagebox.showerror("Erreur", "Veuillez entrer un nom de société!")
                return

            # Vérifier si une société avec le même nom existe déjà
            excel_file_path = os.path.join(os.path.dirname(__file__), 'databases', 'DataBase_domiciliation.xlsx')
            if os.path.exists(excel_file_path):
                existing_df = pd.read_excel(excel_file_path, sheet_name="DataBaseDom")
                if not existing_df.empty and self.values['DEN_STE'] in existing_df['DEN_STE'].values:
                    messagebox.showerror("Erreur", "Une société avec ce nom existe déjà dans la base de données!")
                    return

            self.insert_data_to_excel()
            messagebox.showinfo("Succès", "Données sauvegardées avec succès dans la base de données!")

        except Exception as e:
            messagebox.showerror("Erreur",
                f"Erreur lors de la sauvegarde dans la base de données : {str(e)}")

    def clear_form(self):
        """Clear all form fields"""
        # Clear main fields
        for var_name in ['den_ste_var', 'forme_jur_var', 'ice_var', 'date_ice_var',
                        'capital_var', 'parts_social_var', 'ste_adress_var', 'tribunal_var',
                        'civil_var', 'prenom_var', 'nom_var', 'cin_num_var', 'cin_validaty_var',
                        'gerant_quality_var', 'date_naiss_var', 'lieu_naiss_var', 'gerant_adress_var',
                        'nationality_var', 'gerant_phone_var', 'gerant_email_var', 'date_contrat_var',
                        'period_var', 'prix_mensuel_var', 'prix_inter_var', 'date_debut_var',
                        'date_fin_var']:
            if hasattr(self, var_name):
                getattr(self, var_name).set('')

        # Clear activities
        for i in range(1, 7):
            var_name = f"activity{i}_var"
            if hasattr(self, var_name):
                getattr(self, var_name).set('')

# Create and run the application
if __name__ == "__main__":
    root = tk.Tk()
    root.geometry("1200x800")
    app = DomiciliationApp(root)
    root.mainloop()
