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

from utils import PathManager, ErrorHandler
from constants import (
    societe_headers, associe_headers, contrat_headers,
    excel_sheets, DenSte, Civility, Formjur, Nbmois,
    Capital, PartsSocial, Nationalite, SteAdresse,
    Tribunnaux, QualityGerant, Activities
)

# Configuration des chemins
PathManager.ensure_directories()

# Chargement des templates
docAnnonceJournal = DocxTemplate(PathManager.get_model_path("My_Annonce_Journal.docx"))
docAttestDomicil = DocxTemplate(PathManager.get_model_path("My_Attest_domiciliation.docx"))
docContratDomicil = DocxTemplate(PathManager.get_model_path("My_Contrat_domiciliation.docx"))
docDeclImmRc = DocxTemplate(PathManager.get_model_path("My_Décl_Imm_Rc.docx"))
docDepotLegalRc = DocxTemplate(PathManager.get_model_path("My_Dépot_Légal.docx"))
docStatuts = DocxTemplate(PathManager.get_model_path("My_Statuts_SARL_AU.docx"))

# get the date of today
today = datetime.today()
date_inverser = today.strftime("%Y_%m_%d")
