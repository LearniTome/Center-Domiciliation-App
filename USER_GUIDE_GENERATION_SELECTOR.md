# 📚 User Guide: Document Generation with Selection Dialog

## Quick Start

### How to Generate Documents

1. **Open the Application**
   ```bash
   uv run main.py
   ```

2. **Fill in Company Information**
   - Complete all required fields on the form pages
   - Use the **Précédent/Suivant** buttons to navigate between pages
   - Click **Sauvegarder** to save progress at any time

3. **Click "Générer les documents"**
   - A dialog will appear asking you to select the document type

---

## The Generation Selector Dialog

When you click "Générer les documents", you'll see the **Generation Selector Dialog** with these sections:

### 📋 Section 1: Document Type Selection

Choose ONE of these options:

#### Option A: **Créations de Société** (Company Registration)
- Select this if you need documents for creating a new company
- After selecting, you'll see two subtype options:
  - ✅ **SARL** - For SARL (Limited Liability Company)
  - ✅ **SARL_AU** - For SARL with single member

#### Option B: **Domiciliation** (Business Address Services)
- Select this if you need documents related to business address registration
- No subtypes for this option

### 🎯 Section 2: Template Management

Below the type selection, you'll see a **Template List** showing all available document templates:

**Example templates:**
```
□ My_Annonce_Journal.docx
□ My_Attest_domiciliation.docx
□ My_Contrat_domiciliation.docx
□ My_Décl_Imm_Rc.docx
□ My_Dépot_Légal.docx
□ My_Statuts_SARL_AU.docx
```

**Three action buttons below the list:**

#### 📁 **View Templates** (Voir modèles)
- Opens your system file explorer
- Shows the `Models/` folder with all templates
- Useful if you want to:
  - See template content before generating
  - Manually delete templates
  - Check file sizes/dates

#### ⬆️ **Upload Template** (Uploader un modèle)
- Lets you add new document templates to the system
- **How to use:**
  1. Click the button
  2. A file picker opens
  3. Select a `.docx` file from your computer
  4. **Important:** Only Word documents (`.docx`) are accepted
  5. If the template already exists, you'll be asked to confirm overwrite
  6. The template list automatically updates
- **Tips:**
  - Download templates from your document sources
  - Name them clearly (e.g., `SARL_Statuts.docx`)
  - Test the template before uploading if possible

#### 🔄 **Refresh List** (Actualiser)
- Updates the template list
- Use if you added templates outside the application
- Shows any new `.docx` files in the `Models/` folder

---

## Step-by-Step Example

### Example 1: Creating SARL Company Documents

```
1. Fill company information in the form
   └─ Company name: "Acme SARL"
   └─ Owners, addresses, etc.

2. Click "Générer les documents" button

3. Generation Selector appears:
   ✓ Select: "Créations de Société" (Company Creation)
   
4. Subtype selection appears:
   ✓ Select: "SARL" (for standard SARL)
   
5. Template list shows ONLY SARL-related templates:
   ☑ My_Annonce_Journal.docx
   ☑ My_Dépot_Légal.docx
   ☑ My_Statuts_SARL.docx
   ☐ [SARL_AU templates hidden]

6. Click "Confirmer" to proceed

7. System asks: "Save to database before generating?"
   ✓ Yes → saves data + generates documents
   ✗ No → generates without saving
   ⊘ Cancel → stop process

8. Template selection dialog appears
   └─ Only SARL templates shown (pre-filtered)
   └─ Choose which ones to generate
   └─ Select format: Word / PDF / Both

9. Choose output directory where to save files

10. Progress window shows generation status
    └─ Real-time updates on each document
    └─ Success/error messages

11. Done! ✅
    └─ Files saved in your chosen directory
```

### Example 2: Using Custom Template

```
1. Click "Générer les documents"

2. Selection dialog shows

3. Before confirming, you want to add a custom template:
   ├─ Click "⬆️ Upload Template"
   ├─ Select: "My_Custom_SARL_Template.docx" from your Downloads
   ├─ Confirm: "Yes, upload"
   └─ Template list updates immediately
   
4. Your custom template appears in the list:
   └─ ☑ My_Custom_SARL_Template.docx (newly added!)

5. Proceed with generation as normal
```

---

## Template Filtering Explained

The system **automatically filters** templates based on your selection:

### SARL Documents
When you select: **Créations de Société → SARL**

Shown templates include: SARL, SARL_2024, etc.
Hidden templates include: SARL_AU, Domiciliation, etc.

### SARL_AU Documents
When you select: **Créations de Société → SARL_AU**

Shown templates include: SARL_AU, SARL_AU_Statuts, etc.
Hidden templates include: SARL (non-AU), Domiciliation, etc.

### Domiciliation Documents
When you select: **Domiciliation**

Shown templates include: Domiciliation, Attest_domiciliation, etc.
Hidden templates include: SARL, SARL_AU, Company-specific templates

**Why filtering?**
- Prevents confusion by showing only relevant documents
- Reduces errors from selecting wrong document types
- Makes the list cleaner and easier to navigate

---

## Format Selection

After choosing templates, you'll select the output format:

### 📄 **Word uniquement** (Word Only)
- Generates `.docx` files only
- Fastest option
- Compatible with all Office versions

### 📕 **PDF uniquement** (PDF Only)
- Generates `.pdf` files only
- Better for sharing/printing
- Requires LibreOffice or Word to be installed

### 📄📕 **Word & PDF** (Both)
- Generates both `.docx` and `.pdf` files
- Best option for complete documentation
- Takes longer to process

---

## Troubleshooting

### Issue: "Aucun modèle trouvé" (No templates found)

**Cause:** No templates match your selection type

**Solution:**
1. Check that you selected the right document type
2. Upload templates using the "⬆️ Upload Template" button
3. Click "🔄 Refresh List" to update
4. Verify template names contain correct keywords:
   - SARL templates contain "SARL"
   - SARL_AU templates contain "SARL_AU"
   - Domiciliation templates contain "domiciliation"

### Issue: Upload fails with ".docx only" message

**Cause:** Selected file is not a Word document

**Solution:**
- Only `.docx` files are accepted
- If you have an older `.doc` file, open it in Word and save as `.docx`

### Issue: "File already exists" when uploading

**Cause:** Template with same name already exists

**Solution:**
1. Click "Yes" to overwrite and update the template
2. Or give the new template a different name
3. Use "📁 View Templates" to see existing names first

### Issue: PDF generation doesn't work

**Cause:** PDF converter not installed

**Solution:**
1. Ensure Word or LibreOffice is installed
2. Try "Word uniquement" format instead
3. Contact system administrator if issue persists

---

## Tips & Best Practices

### ✅ Do's:
- ✅ Name templates clearly (e.g., `SARL_Statuts.docx`)
- ✅ Test new templates before generating official documents
- ✅ Save data to database before generating (safer)
- ✅ Use "Word & PDF" when you need both formats
- ✅ Keep templates in simple folders without special characters

### ❌ Don'ts:
- ❌ Don't use non-Word files for templates
- ❌ Don't skip the company information form
- ❌ Don't select wrong document type for company
- ❌ Don't delete templates from Models/ folder without backup
- ❌ Don't use special characters in template names (é, &, etc.)

---

## Getting Help

### Common Questions

**Q: Can I have multiple versions of the same template?**  
A: Yes! Upload templates with different names:
- `My_Statuts_SARL_v1.docx`
- `My_Statuts_SARL_v2.docx`

**Q: Where are generated documents saved?**  
A: Wherever you choose in step 9 of generation process. They're organized in folders like:
```
2026-03-03_Acme_SARL_Constitution/
├── My_Statuts_SARL_filled.docx
├── My_Statuts_SARL_filled.pdf
└── My_Dépot_Légal_filled.docx
```

**Q: Can I reuse templates I create?**  
A: Yes! Use "⬆️ Upload Template" to add them. Once uploaded, they appear in every generation.

**Q: What if I make a mistake during generation?**  
A: Just click "Generate" again. You can overwrite previous files or save to a different folder.

---

## Contact Support

If you encounter issues not covered here:
1. Check `app.log` for error details
2. Note the exact error message
3. Contact your system administrator
4. Include the company name and document type used

---

**Last Updated:** March 2026  
**Version:** 1.0
