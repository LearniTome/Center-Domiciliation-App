# Graph Report - Center-Domiciliation-App  (2026-08-15)

## Corpus Check
- Large corpus: 1989 files À ~1,653,992 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 938 nodes · 1162 edges · 215 communities (177 shown, 38 thin omitted)
- Extraction: 76% EXTRACTED · 22% INFERRED · 2% AMBIGUOUS · INFERRED: 258 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Cessions de parts - Dossiers PDF
- Architecture applicative PHP
- Design system et UI
- Logique JavaScript frontend
- Pages métier et navigation
- Rendu de documents (DocumentRenderer)
- Éditeur de templates DOCX
- Configuration MCP OpenCode
- Dossiers de création de sociétés
- Schéma SQL - dump complet
- Helpers PHP (fonctions.php)
- Tables de référence (import.sql)
- Schéma SQL - tables de référence
- Dashboard - design et captures
- Workflow cession & collaborateurs
- Analyseur de templates DOCX
- Helpers de données (fetch_*)
- Dossier modèle CENTIRIO SARL
- Wizard cession - étapes
- Schéma SQL - dump partiel
- ClaudeService (IA)
- Table editor JS
- Wizard création - captures UI
- Auth, permissions et notifications
- API JSON (api.php)
- Pages associé - design
- Décisions UI - icônes et titres
- Navigation sidebar et topbar
- Contrôle d'accès et sessions
- Dossier EXCELENCIA-TRAV
- Screenshots génération et login
- Juridique PV AGO (lois Maroc)
- Dossier ANALIK
- Dossier TEST-CREATION-SARL
- Dossier BAATRI
- Dossier KAMARAD
- Agents Manus & OpenWolf
- Conversion Word vers PDF
- Dépendances Composer
- Dossier ANFURA-CREATIVE
- Parsing de valeurs (formulaires)
- Filtrage templates par forme
- Serveur dev multi-projets
- Migration RBAC
- Migration RBAC timestampée
- Migration cession_parts
- Migration pv_ago
- Migration cession_suivi
- Design notifications
- Scripts setup macOS
- Guides projet (AGENTS/CLAUDE)
- PV-AGO Test-Import-SARL
- Pages Rôles/Fonctions
- Scroll listes - captures
- Connexion base de données
- Migrations automatiques
- Dev server & MCP
- Page de connexion
- Guide XAMPP
- Migration ref_fonctions
- Migration notifications
- Migration user_sessions
- Migration pv_resolutions
- Guide affectation résultat
- Script run macOS
- Gotchas manipulation DOCX (underscore split, ...
- Collaborateur (entité domaine)
- ref_tribunaux
- collaborateurs
- associes
- contrats
- societes
- ref_tribunaux
- associes
- contrats
- societes
- collaborateurs
- cession_parts
- societes
- societes
- cession_parts
- societes
- documents_generes
- documents_generes
- cessions
- associes
- societes
- Master Prompt : migration PHP/XAMPP
- ref_ste_adresses

## God Nodes (most connected - your core abstractions)
1. `DocumentRenderer` - 30 edges
2. `TemplateEditor` - 29 edges
3. `TemplateAnalyzer` - 20 edges
4. `Tableau de bord (accueil)` - 20 edges
5. `Annonce légale de cession` - 15 edges
6. `Societe Entity` - 15 edges
7. `Acte de cession de parts` - 14 edges
8. `Procès-verbal d'AGE de cession` - 14 edges
9. `Cession de parts sociales` - 14 edges
10. `Déclaration modificative au Registre de Commerce` - 13 edges

## Surprising Connections (you probably didn't know these)
- `CLAUDE.md (Contexte & Mémoire)` --semantically_similar_to--> `AGENTS.md (PHP Project Guide)`  [INFERRED] [semantically similar]
  CLAUDE.md → AGENTS.md
- `Dossier de cession (cessions + cession_parts)` --semantically_similar_to--> `Parties à la cession (cédant / cessionnaire)`  [INFERRED] [semantically similar]
  AGENTS.md → docs/cession/Acte_Cession_SIGMATEX_28102021.md
- `Page de connexion` --conceptually_related_to--> `Interface de generation des documents`  [AMBIGUOUS]
  connexion_page.png → assets/img/generation.png
- `handle_quick_create()` --calls--> `log_activity()`  [INFERRED]
  api.php → includes/fonctions.php
- `Migration desktop Python → web PHP` --rationale_for--> `Front Controller Routing (index.php?page=)`  [INFERRED]
  docs/php-xampp-migration-master-prompt.md → AGENTS.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Gouvernance du design system et validation UI** — opencode_skills_awesome_design_skill, opencode_skills_ui_design_skill, opencode_skills_manual_test_checklist, opencode_skills_screenshot_agent_visual_validation [INFERRED 0.85]
- **Conventions sécurité et formulaires (CSRF, PDO, redirect)** — opencode_skills_security_csrf, opencode_skills_security_sql_injection, opencode_skills_database_pdo_named_params, opencode_skills_manual_test_form_rules [INFERRED 0.85]
- **Skills agents autonomes opencode** — opencode_skills_manus_skill, opencode_skills_openwolf_skill, opencode_skills_screenshot_agent_skill [INFERRED 0.75]
- **Pipeline de génération de documents DOCX** — agents_variables_contexte, agents_templateanalyzer, agents_documentrenderer, agents_boucle_template, agents_docx_to_pdf [EXTRACTED 0.95]
- **Intégration IA Claude dans les pages** — agents_claudeservice, agents_ai_par_page, agents_wizard_creation, agents_analyse_couverture [EXTRACTED 0.95]
- **Socle commun : front controller, includes, RBAC, conventions** — agents_front_controller, agents_includes, agents_rbac, agents_helpers [EXTRACTED 0.90]
- **Dossier de domiciliation GITREIO (2026-06-02)** — dossiers_generer_dossiers_domiciliation_gitreio, dossiers_generer_dossiers_domiciliation_centirio_sarl, dossiers_generer_dossiers_domiciliation_annonce_legale_journal, dossiers_generer_dossiers_domiciliation_attestation_domiciliation_initiale, dossiers_generer_dossiers_domiciliation_contrat_domiciliation, dossiers_generer_dossiers_domiciliation_declaration_immatriculation_rc, dossiers_generer_dossiers_domiciliation_depot_legal_constitution, dossiers_generer_dossiers_domiciliation_statuts [INFERRED 0.85]
- **Dossier de domiciliation AMAR STE (2026-06-07)** — dossiers_generer_dossiers_domiciliation_amar_ste, dossiers_generer_dossiers_domiciliation_centirio_sarl, dossiers_generer_dossiers_domiciliation_annonce_legale_journal, dossiers_generer_dossiers_domiciliation_attestation_domiciliation_initiale, dossiers_generer_dossiers_domiciliation_contrat_domiciliation, dossiers_generer_dossiers_domiciliation_declaration_immatriculation_rc, dossiers_generer_dossiers_domiciliation_depot_legal_constitution, dossiers_generer_dossiers_domiciliation_statuts [INFERRED 0.85]
- **Formalités de constitution et d'immatriculation d'une SARL AU au Maroc** — dossiers_generer_dossiers_domiciliation_sarl_au, dossiers_generer_dossiers_domiciliation_statuts, dossiers_generer_dossiers_domiciliation_declaration_immatriculation_rc, dossiers_generer_dossiers_domiciliation_depot_legal_constitution, dossiers_generer_dossiers_domiciliation_annonce_legale_journal, dossiers_generer_dossiers_domiciliation_registre_commerce_rc [INFERRED 0.75]
- **Dossier de cession FADAA-DOI (2026-06-13)** — dossiers_generer_dossiers_cession_2026_06_13_sarl_au_fadaa_doi_sarl_au_2026_06_13_acte_cession_parts_fadaa_doi, dossiers_generer_dossiers_cession_2026_06_13_sarl_au_fadaa_doi_sarl_au_2026_06_13_annonce_legale_cession_fadaa_doi, dossiers_generer_dossiers_cession_2026_06_13_sarl_au_fadaa_doi_sarl_au_2026_06_13_declaration_modificative_rc_fadaa_doi, dossiers_generer_dossiers_cession_2026_06_13_sarl_au_fadaa_doi_sarl_au_2026_06_13_pv_age_cession_fadaa_doi [EXTRACTED 1.00]
- **Dossier de cession ALMASA (2026-06-15)** — dossiers_generer_dossiers_cession_2026_06_15_sarl_au_almasa_sarl_au_2026_06_15_acte_cession_parts_almasa, dossiers_generer_dossiers_cession_2026_06_15_sarl_au_almasa_sarl_au_2026_06_15_annonce_legale_cession_almasa, dossiers_generer_dossiers_cession_2026_06_15_sarl_au_almasa_sarl_au_2026_06_15_declaration_modificative_rc_almasa, dossiers_generer_dossiers_cession_2026_06_15_sarl_au_almasa_sarl_au_2026_06_15_pv_age_cession_almasa [EXTRACTED 1.00]
- **Dossier de cession MEDITERRANEE-INVEST (2026-06-19)** — dossiers_generer_dossiers_cession_2026_06_19_sarl_au_mediterranee_invest_sarl_au_2026_06_19_acte_cession_parts_mediterranee_invest, dossiers_generer_dossiers_cession_2026_06_19_sarl_au_mediterranee_invest_sarl_au_2026_06_19_annonce_legale_cession_mediterranee_invest, dossiers_generer_dossiers_cession_2026_06_19_sarl_au_mediterranee_invest_sarl_au_2026_06_19_declaration_modificative_rc_mediterranee_invest, dossiers_generer_dossiers_cession_2026_06_19_sarl_au_mediterranee_invest_sarl_au_2026_06_19_pv_age_cession_mediterranee_invest [EXTRACTED 1.00]
- **Dossier de cession NORTH-AFRICA-LOGISTICS (2026-06-19)** — dossiers_generer_dossiers_cession_2026_06_19_succurssale_etrang_re_north_africa_logistics_succurssale_etrangere_2026_06_19_acte_cession_parts_north_africa_logistics, dossiers_generer_dossiers_cession_2026_06_19_succurssale_etrang_re_north_africa_logistics_succurssale_etrangere_2026_06_19_annonce_legale_cession_north_africa_logistics, dossiers_generer_dossiers_cession_2026_06_19_succurssale_etrang_re_north_africa_logistics_succurssale_etrangere_2026_06_19_declaration_modificative_rc_north_africa_logistics, dossiers_generer_dossiers_cession_2026_06_19_succurssale_etrang_re_north_africa_logistics_succurssale_etrangere_2026_06_19_pv_age_cession_north_africa_logistics [EXTRACTED 1.00]
- **Dossier de cession SEO-SERVICES (2026-06-20)** — dossiers_generer_dossiers_cession_2026_06_20_sarl_au_seo_services_sarl_au_2026_06_20_acte_cession_parts_seo_services, dossiers_generer_dossiers_cession_2026_06_20_sarl_au_seo_services_sarl_au_2026_06_20_annonce_legale_cession_seo_services, dossiers_generer_dossiers_cession_2026_06_20_sarl_au_seo_services_sarl_au_2026_06_20_declaration_modificative_rc_seo_services, dossiers_generer_dossiers_cession_2026_06_20_sarl_au_seo_services_sarl_au_2026_06_20_pv_age_cession_seo_services [EXTRACTED 1.00]
- **Dossier de cession Test-Wizard-SARL-AU (2026-06-20)** — dossiers_generer_dossiers_cession_2026_06_20_sarl_au_test_wizard_sarl_au_sarl_au_2026_06_20_acte_cession_parts_test_wizard_sarl_au, dossiers_generer_dossiers_cession_2026_06_20_sarl_au_test_wizard_sarl_au_sarl_au_2026_06_20_annonce_legale_cession_test_wizard_sarl_au [EXTRACTED 1.00]
- **Dossier de cession de parts Test-Wizard-SARL-AU** — dossiers_generer_dossiers_cession_test_wizard_sarl_au, dossiers_generer_dossiers_cession_acte_cession_parts, dossiers_generer_dossiers_cession_annonce_legale_cession, dossiers_generer_dossiers_cession_declaration_modificative_rc, dossiers_generer_dossiers_cession_pv_age_cession [EXTRACTED 1.00]
- **Dossier de cession de parts TECHNOVA-SARL** — dossiers_generer_dossiers_cession_technova_sarl, dossiers_generer_dossiers_cession_acte_cession_parts, dossiers_generer_dossiers_cession_annonce_legale_cession, dossiers_generer_dossiers_cession_declaration_modificative_rc, dossiers_generer_dossiers_cession_pv_age_cession [EXTRACTED 1.00]
- **Dossier de cession de parts Test-Nouvelle-SARL-Cession** — dossiers_generer_dossiers_cession_test_nouvelle_sarl_cession, dossiers_generer_dossiers_cession_acte_cession_parts, dossiers_generer_dossiers_cession_annonce_legale_cession, dossiers_generer_dossiers_cession_declaration_modificative_rc, dossiers_generer_dossiers_cession_pv_age_cession [EXTRACTED 1.00]
- **Dossier de cession de parts SLAIAOBBHG** — dossiers_generer_dossiers_cession_slaiaobbhg, dossiers_generer_dossiers_cession_acte_cession_parts, dossiers_generer_dossiers_cession_annonce_legale_cession, dossiers_generer_dossiers_cession_declaration_modificative_rc, dossiers_generer_dossiers_cession_pv_age_cession [EXTRACTED 1.00]
- **Dossier de cession de parts NORTH-AFRICA-LOGISTICS** — dossiers_generer_dossiers_cession_north_africa_logistics, dossiers_generer_dossiers_cession_acte_cession_parts, dossiers_generer_dossiers_cession_annonce_legale_cession, dossiers_generer_dossiers_cession_declaration_modificative_rc, dossiers_generer_dossiers_cession_pv_age_cession [EXTRACTED 1.00]
- **Dossier création SARL AU — ANALIK** — dossiers_generer_dossiers_creation_2026_05_18_sarl_au_analik, dossiers_generer_dossiers_creation_2026_05_18_sarl_au_analik_cin, dossiers_generer_dossiers_creation_2026_05_18_sarl_au_analik_cn, dossiers_generer_dossiers_creation_2026_05_18_sarl_au_analik_gerant_abolaba_mousa [EXTRACTED 1.00]
- **Dossier création SARL AU — TEST-CREATION-SARL** — dossiers_generer_dossiers_creation_2026_05_18_sarl_au_test_creation_sarl, dossiers_generer_dossiers_creation_2026_05_18_sarl_au_test_creation_sarl_cin, dossiers_generer_dossiers_creation_2026_05_18_sarl_au_test_creation_sarl_cn, dossiers_generer_dossiers_creation_2026_05_18_sarl_au_test_creation_sarl_gerant_alaoui_mehdi [EXTRACTED 1.00]
- **Dossier création SARL — BAATRI** — dossiers_generer_dossiers_creation_2026_05_18_sarl_baatri, dossiers_generer_dossiers_creation_2026_05_18_sarl_baatri_cin, dossiers_generer_dossiers_creation_2026_05_18_sarl_baatri_cn, dossiers_generer_dossiers_creation_2026_05_18_sarl_baatri_gerant_awal_ahmed [EXTRACTED 1.00]
- **Dossier création SARL — EXCELENCIA-TRAV** — dossiers_generer_dossiers_creation_2026_05_18_sarl_excelencia_trav, dossiers_generer_dossiers_creation_2026_05_18_sarl_excelencia_trav_cin, dossiers_generer_dossiers_creation_2026_05_18_sarl_excelencia_trav_cn, dossiers_generer_dossiers_creation_2026_05_18_sarl_excelencia_trav_gerant_chakir_rachid, dossiers_generer_dossiers_creation_2026_05_18_sarl_excelencia_trav_gerant_elkhattaby_brahim [EXTRACTED 1.00]
- **Dossier création SARL — KAMARAD** — dossiers_generer_dossiers_creation_2026_05_18_sarl_kamarad, dossiers_generer_dossiers_creation_2026_05_18_sarl_kamarad_cin, dossiers_generer_dossiers_creation_2026_05_18_sarl_kamarad_cn, dossiers_generer_dossiers_creation_2026_05_18_sarl_kamarad_gerant_benani_ahmed [EXTRACTED 1.00]
- **Dossier création SARL AU — ANFURA-CREATIVE** — dossiers_generer_dossiers_creation_2026_07_07_sarl_au_anfura_creative, dossiers_generer_dossiers_creation_2026_07_07_sarl_au_anfura_creative_contrat_domiciliation, dossiers_generer_dossiers_creation_2026_07_07_sarl_au_anfura_creative_cn [EXTRACTED 1.00]
- **Dossier de domiciliation — PHP-OUTPUT-SARL** — dossiers_dom_2026_05_18_sarl_au_php_output_sarl_sarl_au_2026_06_06_attestation_domiciliation_initiale_php_output_sarl_brouillon_attestation_domiciliation_initiale_brouillon, dossiers_dom_2026_05_18_sarl_au_php_output_sarl__uploads_2026_06_06_cin_php_output_php_output_sarl_cin, dossiers_dom_2026_05_18_sarl_au_php_output_sarl__uploads_2026_06_06_cn_php_output_sarl_cn, concept_php_output_sarl [EXTRACTED 1.00]
- **Dossier de domiciliation — Test-PDF-Karim (dossiers_dom)** — dossiers_dom_2026_06_06_sarl_au_test_pdf_karim_sarl_au_2026_06_06_attestation_domiciliation_initiale_test_pdf_karim_attestation_domiciliation_initiale, dossiers_dom_2026_06_06_sarl_au_test_pdf_karim_sarl_au_2026_06_06_contrat_domiciliation_test_pdf_karim_contrat_domiciliation, dossiers_dom_2026_06_06_sarl_au_test_pdf_karim_sarl_au_2026_06_06_contrat_domiciliation_test_pdf_karim_brouillon_contrat_domiciliation_brouillon, concept_test_pdf_karim [EXTRACTED 1.00]
- **Dossier PV-AGO — Test-Import-SARL** — dossiers_generer_dossiers_pv_ago_sarl_2026_06_28_test_import_sarl_sarl_2026_06_28_pv_ago_test_import_sarl_pv_ago, concept_test_import_sarl [EXTRACTED 1.00]
- **Dossier uploads n°11 — Test_SARL_AU** — uploads_dossiers_11_2026_06_06_cin_test_user_test_sarl_au_cin, uploads_dossiers_11_2026_06_06_cn_test_sarl_au_cn, concept_test_sarl_au [EXTRACTED 1.00]
- **Dossier uploads n°12 — Test_PDF_Karim** — uploads_dossiers_12_2026_06_06_cin_test_pdf_karim_cin, uploads_dossiers_12_2026_06_06_cn_test_pdf_karim_cn, concept_test_pdf_karim [EXTRACTED 1.00]
- **Dossier uploads n°15 — documents wizard (Certificat Négatif + CIN Gérant)** — uploads_dossiers_15_certificat_negatif_20260601_203213_certificat_negatif, uploads_dossiers_15_cin_gerant_0_20260601_203213_cin_gerant [INFERRED 0.85]
- **Dossier uploads n°16 — AMAR-STE** — uploads_dossiers_16_2026_06_01_cin_benani_ahmed_amar_ste_cin_benani_ahmed, uploads_dossiers_16_2026_06_01_cn_amar_ste_cn, concept_amar_ste [EXTRACTED 1.00]
- **Dossier uploads n°17 — GITREIO** — uploads_dossiers_17_2026_06_02_cin_benani_ahmed_gitreio_cin_benani_ahmed, uploads_dossiers_17_2026_06_02_cn_gitreio_cn, concept_gitreio [EXTRACTED 1.00]
- **Identité BENANI Ahmed — présente dans les dossiers AMAR-STE et GITREIO** — person_benani_ahmed, uploads_dossiers_16_2026_06_01_cin_benani_ahmed_amar_ste_cin_benani_ahmed, uploads_dossiers_17_2026_06_02_cin_benani_ahmed_gitreio_cin_benani_ahmed [INFERRED 0.85]
- **Cession Wizard Steps** — docs_screenshots_cession_step0_ui, docs_screenshots_cession_step0, docs_screenshots_cession_step1, docs_screenshots_cession_step2, docs_screenshots_cession_step3, docs_screenshots_cession_step4 [INFERRED 0.85]
- **Cession Wizard v2 Steps** — docs_screenshots_cession_v2_step0, docs_screenshots_cession_v2_step1, docs_screenshots_cession_v2_step2, docs_screenshots_cession_v2_step3, docs_screenshots_cession_v2_step4_table [INFERRED 0.85]
- **Associé Form Variants** — docs_screenshots_associe24_dark_clean, docs_screenshots_associe24_dark_final, docs_screenshots_associe24_final [INFERRED 0.85]
- **Associé Detail Page Variants** — docs_screenshots_associe_A4_dark, docs_screenshots_associe_detail_A4_dark, docs_screenshots_associe_detail_dark [INFERRED 0.85]
- **Dark Mode UI Variants** — docs_screenshots_associe24_dark_clean, docs_screenshots_associe24_dark_final, docs_screenshots_associe_A4_dark, docs_screenshots_associe_detail_dark [AMBIGUOUS 0.30]
- **Wizard cession v2 — captures d'écran du parcours** — docs_screenshots_cession_v2_step4_v2_shot, docs_screenshots_cession_v2_step4_shot, docs_screenshots_cession_nouvelle_societe_shot, docs_screenshots_cession_dossier_shot [INFERRED 0.65]
- **Workflow collaborateur — captures d'écran** — docs_screenshots_collaborateur_page_shot, docs_screenshots_collaborateur_pm_page_shot, docs_screenshots_collaborateur_type_selector_shot, docs_screenshots_collab_edit_grid_shot, docs_screenshots_collaborateur_spacing_shot, docs_screenshots_collaborateur_spacing_after_shot [INFERRED 0.65]
- **Refonte dashboard — captures d'écran et décisions de design** — docs_screenshots_dashboard_shot, docs_screenshots_dashboard_layout_shot, docs_screenshots_dashboard_topbar_fixed_shot, docs_screenshots_dashboard_alignment_shot, docs_screenshots_dashboard_clickable_titles_shot, docs_screenshots_dashboard_journal_final_shot, docs_screenshots_dashboard_stats_design, docs_screenshots_dashboard_layout_design [INFERRED 0.65]
- **Variantes de mise en page du dashboard** — docs_screenshots_dashboard_journal_truncated, docs_screenshots_dashboard_single_column, docs_screenshots_dashboard_tables, docs_screenshots_dashboard_val_compact, docs_screenshots_dashboard_val_right [INFERRED 0.75]
- **Captures de la navigation (sidebar + topbar)** — docs_screenshots_sidebar_fixed, docs_screenshots_sidebar_check, docs_screenshots_nav_current, docs_screenshots_nav_scroll_fixed, docs_screenshots_logout_topbar [INFERRED 0.75]
- **Captures de la page notifications** — docs_screenshots_notifications_fixed, docs_screenshots_notif_manage_fixed [INFERRED 0.75]
- **Captures de la zone société (liste + détail)** — docs_screenshots_societe_full, docs_screenshots_societe_titres, docs_screenshots_societe_view, docs_screenshots_societes_icons [INFERRED 0.75]
- **Captures de la page rôles / fonctions** — docs_screenshots_roles_fixed, docs_screenshots_fonctions_page [INFERRED 0.75]
- **Création Wizard Screenshot Group** — docs_screenshots_wizard_step1, docs_screenshots_wizard_step4_recap_dark, docs_screenshots_wizard_step6, docs_screenshots_wizard_grid, docs_screenshots_wizard_grid_apres, docs_screenshots_wizard_spacing, docs_screenshots_wizard_header [INFERRED 0.95]
- **List Scroll Behavior Screenshot Group** — docs_screenshots_test_scroll, docs_screenshots_test_societes_scroll [INFERRED 0.95]
- **Captures d'ecran du flux de generation DOCX vers PDF** — assets_img_generation_flow, assets_img_generation_flow_step2, assets_img_generation_flow_step3 [INFERRED 0.75]
- **Captures d'ecran de l'interface applicative** — connexion_page, societes_table, assets_img_generation_flow [INFERRED 0.75]
- **Charte de nommage family documents** — templates_guides_charte_nommage_dossiers_docs, templates_guides_charte_nommage_dossiers_docs_v3, templates_guides_charte_nommage_generation_v4 [INFERRED 0.85]
- **Template authoring documentation set** — templates_guides_guide_boucles_templates, docs_variables_formulaires, templates_guides_charte_nommage_dossiers_docs [INFERRED 0.75]
- **Application page a11y snapshots** — docs_screenshots_dashboard_snapshot, docs_screenshots_societes_snapshot, docs_screenshots_cessions_list [INFERRED 0.85]

## Communities (215 total, 38 thin omitted)

### Community 0 - "Cessions de parts - Dossiers PDF"
Cohesion: 0.10
Nodes (24): Annonce légale, Cédant / Cessionnaire, Cession de parts sociales, Acte de cession de parts, ALMASA (SARL AU), Annonce légale, Annonce légale de cession, Cession de parts sociales (+16 more)

### Community 1 - "Architecture applicative PHP"
Cohesion: 0.07
Nodes (44): Fonctionnalités IA par page (wizard, analyse-couverture, ai-assistant), Allowlist de pages + $pageDir mapping, Analyse de couverture des variables, api.php (JSON actions quick_create/inline_update/bulk_update), Boucles template ({%p for c in cession_parts %}), Boutons alignés à droite du titre, Système de boutons (.btn + variantes colorées), Fonctions de distribution capital (repartirCapital, recalc*) (+36 more)

### Community 2 - "Design system et UI"
Cohesion: 0.07
Nodes (38): Snapshot a11y : Fiche collaborateur (externe-PM), Liste des rôles collaborateur externe (Expert-comptable, Notaire, Banque, ...), Formulaire Fiche collaborateur, Identifiants légaux (CODE, ICE, TP, RC, IF), Sidebar de navigation (Dossiers, Outils, Configuration), Système de boutons (.btn, variants couleur par rôle), Thème sombre variables CSS (--primary, --panel, --text), Règles d'or UI (icône MDI, pas de padding inline, thème sombre, e()) (+30 more)

### Community 3 - "Logique JavaScript frontend"
Cohesion: 0.10
Nodes (24): addAssocieButton, associesContainer, associeTemplate, closeModal(), fetchCount(), fetchNotifications(), generateTestData(), getCsrf() (+16 more)

### Community 4 - "Pages métier et navigation"
Cohesion: 0.13
Nodes (34): Cession de Parts Wizard, Cessions List Page, Dashboard (Tableau de bord), Generateur de dossiers Page, Application Sidebar Navigation, Societe Detail (Fiche) Page, Societes List Page, DocxTpl Template Loops ({%p for %}) (+26 more)

### Community 5 - "Rendu de documents (DocumentRenderer)"
Cohesion: 0.10
Nodes (3): fetch_record(), DocumentRenderer, PDO

### Community 7 - "Configuration MCP OpenCode"
Cohesion: 0.09
Nodes (28): command, enabled, type, mcp, chrome-devtools, memory, mysql-dev, mysql-prod (+20 more)

### Community 8 - "Dossiers de création de sociétés"
Cohesion: 0.14
Nodes (26): Société AMAR-STE, Société GITREIO, Société PHP-OUTPUT-SARL, Société Test-PDF-Karim / Test_PDF_Karim, Société Test_SARL_AU, CIN (PHP_OUTPUT_SARL) — dossier PHP-OUTPUT-SARL, CN (PHP_OUTPUT_SARL) — dossier PHP-OUTPUT-SARL, Attestation-Domiciliation-Initiale (Brouillon) — PHP-OUTPUT-SARL (+18 more)

### Community 9 - "Schéma SQL - dump complet"
Cohesion: 0.12
Nodes (24): `activity_logs`, `associes`, `cession_parts`, `cessions`, `collaborateur_permissions`, `collaborateurs`, `contrats`, `documents_generes` (+16 more)

### Community 10 - "Helpers PHP (fonctions.php)"
Cohesion: 0.10
Nodes (9): csrf_input(), csrf_token(), e(), fetch_all_documents(), import_excel_confirm(), import_excel_preview(), is_post(), like_term() (+1 more)

### Community 11 - "Tables de référence (import.sql)"
Cohesion: 0.13
Nodes (21): activity_logs, associes, collaborateur_permissions, collaborateurs, contrats, documents_generes, permissions, ref_activites (+13 more)

### Community 12 - "Schéma SQL - tables de référence"
Cohesion: 0.13
Nodes (21): activity_logs, associes, collaborateur_permissions, collaborateurs, contrats, documents_generes, permissions, ref_activites (+13 more)

### Community 13 - "Dashboard - design et captures"
Cohesion: 0.15
Nodes (22): Tableau de bord (accueil), Dashboard — alignement des grilles (décision de design), Capture écran — Alignement du dashboard, Dashboard — titres cliquables (décision de design), Capture écran — Titres cliquables du dashboard, Capture écran — Dashboard dossiers complets, Capture écran — Grilles alignées du dashboard, Dashboard — journal d'activité (+14 more)

### Community 14 - "Workflow cession & collaborateurs"
Cohesion: 0.12
Nodes (21): Workflow cession de parts sociales, Page détail dossier de cession, Capture écran — Dossier de cession (page détail), Modal création de société dans le wizard cession, Capture écran — Modal Nouvelle société (wizard cession), Wizard cession v2 — Étape 4 (récapitulatif), Capture écran — Étape 4 wizard cession v2, Capture écran — Étape 4 wizard cession v2 (variante 2) (+13 more)

### Community 16 - "Helpers de données (fetch_*)"
Cohesion: 0.11
Nodes (19): count_unread_notifications(), dashboard_count(), fetch_activites_ompic_display(), fetch_activites_ompic_options(), fetch_adresses_all(), fetch_all_doc_types(), fetch_all_records(), fetch_document() (+11 more)

### Community 17 - "Dossier modèle CENTIRIO SARL"
Cohesion: 0.21
Nodes (18): Ahmed BENANI (gérant et associé unique), AMAR STE SARL AU (société client, associé unique), Annonce légale (publication obligatoire de constitution), Annonce Legale Journal (publication d'une constitution de société), Attestation de Domiciliation Initiale (SARL AU), CENTIRIO SARL (Centre d'affaires, société de domiciliation), Code de commerce marocain (loi 15-95), Contrat de Domiciliation (CENTIRIO SARL / client) (+10 more)

### Community 18 - "Wizard cession - étapes"
Cohesion: 0.29
Nodes (17): Cession wizard step 0, Cession wizard step 0 (UI), Cession wizard step 1, Cession wizard step 1 (form), Cession wizard step 1 (full), Cession wizard step 1 (now), Cession wizard step 2, Cession wizard step 3 (+9 more)

### Community 19 - "Schéma SQL - dump partiel"
Cohesion: 0.21
Nodes (12): `activity_logs`, `associes`, `collaborateur_permissions`, `collaborateurs`, `contrats`, `documents_generes`, `_migrations`, `permissions` (+4 more)

### Community 21 - "Table editor JS"
Cohesion: 0.31
Nodes (6): buildRow(), getCsrfToken(), getValue(), initQuickCreate(), save(), showToast()

### Community 22 - "Wizard création - captures UI"
Cohesion: 0.27
Nodes (11): Création Wizard Workflow Screenshots, Dark Récapitulatif UI Design Decision, Wizard Form Grid Layout Pattern, Wizard Form Grid Screenshot, Wizard Form Grid After Screenshot, Wizard Header Screenshot, Wizard Step Header Pattern, Wizard Spacing Screenshot (+3 more)

### Community 23 - "Auth, permissions et notifications"
Cohesion: 0.24
Nodes (10): auto_notify_action(), create_notification(), current_user(), get_role_name(), get_user_permissions(), has_permission(), log_activity(), log_page_view() (+2 more)

### Community 24 - "API JSON (api.php)"
Cohesion: 0.31
Nodes (9): handle_bulk_update(), handle_import_confirm(), handle_import_preview(), handle_inline_update(), handle_quick_create(), loadSpreadsheetData(), PDO, stripXlsxEntityDeclarations() (+1 more)

### Community 25 - "Pages associé - design"
Cohesion: 0.31
Nodes (10): A4 Print Layout, Associé 24 (dark, clean), Associé 24 (dark, final), Associé 24 (final), Associé A4 (dark), Associé Detail Page, Associé detail A4 (dark), Associé detail (dark) (+2 more)

### Community 26 - "Décisions UI - icônes et titres"
Cohesion: 0.33
Nodes (9): Génération - Icônes, Décision UI : boutons d'action à icônes, PV AGO - Génération documents, PV AGO - 3 enregistrements, Société - Vue complète, Pages Société (liste + détail), Société - Titres, Société - Détail (+1 more)

### Community 27 - "Navigation sidebar et topbar"
Cohesion: 0.42
Nodes (9): Déconnexion dans la barre supérieure, Navigation - Élément actif, Décision UI : navigation fixe + état actif, Navigation fixe au scroll, Navigation sidebar + topbar, Sidebar - Vérification, Sidebar fixée, Décision UI : sidebar fixe sans scroll (+1 more)

### Community 28 - "Contrôle d'accès et sessions"
Cohesion: 0.25
Nodes (9): app_url(), generate_auto_notifications(), get_page_permission(), is_logged_in(), redirect_to(), require_auth(), require_page_access(), require_permission() (+1 more)

### Community 29 - "Dossier EXCELENCIA-TRAV"
Cohesion: 0.43
Nodes (8): Dossier création SARL — EXCELENCIA-TRAV, CIN CHAKIR Rachid (EXCELENCIA-TRAV), CIN ELKHATTABY Brahim (EXCELENCIA-TRAV), Certificat négatif (EXCELENCIA-TRAV), CIN (carte d'identité nationale) — EXCELENCIA-TRAV, Certificat négatif — EXCELENCIA-TRAV, CHAKIR Rachid (gérant), ELKHATTABY Brahim (gérant)

### Community 30 - "Screenshots génération et login"
Cohesion: 0.53
Nodes (6): Interface de generation des documents, Etape 2 du flux de generation de documents, Etape 3 du flux de generation de documents, Page de connexion, Scan CIN de Mohamed Zarkoune (piece d'identite), Tableau de gestion des societes

### Community 31 - "Juridique PV AGO (lois Maroc)"
Cohesion: 0.47
Nodes (6): Ordre d'imputation obligatoire du résultat, Alerte juridique : perte des 3/4 du capital social, Loi 17-95 (Sociétés Anonymes), Loi 5-96 (SARL), TPA (Taxe sur les Produits des Actions) — retenue 10%, Résolutions type du PV AGO (approbation, affectation, pouvoirs)

### Community 32 - "Dossier ANALIK"
Cohesion: 0.53
Nodes (6): Dossier création SARL AU — ANALIK, CIN ABOLABA MOUSA (ANALIK), Certificat négatif (ANALIK), CIN (carte d'identité nationale) — ANALIK, Certificat négatif — ANALIK, ABOLABA MOUSA (gérant)

### Community 33 - "Dossier TEST-CREATION-SARL"
Cohesion: 0.53
Nodes (6): Dossier création SARL AU — TEST-CREATION-SARL, CIN ALAOUI Mehdi (TEST-CREATION-SARL), Certificat négatif (TEST-CREATION-SARL), CIN (carte d'identité nationale) — TEST-CREATION-SARL, Certificat négatif — TEST-CREATION-SARL, ALAOUI Mehdi (gérant)

### Community 34 - "Dossier BAATRI"
Cohesion: 0.53
Nodes (6): Dossier création SARL — BAATRI, CIN AWAL Ahmed (BAATRI), Certificat négatif (BAATRI), CIN (carte d'identité nationale) — BAATRI, Certificat négatif — BAATRI, AWAL Ahmed (gérant)

### Community 35 - "Dossier KAMARAD"
Cohesion: 0.53
Nodes (6): Dossier création SARL — KAMARAD, CIN BENANI Ahmed (KAMARAD), Certificat négatif (KAMARAD), CIN (carte d'identité nationale) — KAMARAD, Certificat négatif — KAMARAD, BENANI Ahmed (gérant)

### Community 36 - "Agents Manus & OpenWolf"
Cohesion: 0.33
Nodes (6): Principes d'autonomie (analyse, plan, exécution, vérification, livraison), Agent Manus (autonome, de bout en bout), Prévention d'erreurs via cerbrum.md, Mémoire contextuelle (registre préférences, fichier mémoire), Maintenance automatisée project-map.md + logs tokens, Agent OpenWolf (cognitif, mémoire contextuelle)

### Community 37 - "Conversion Word vers PDF"
Cohesion: 0.60
Nodes (4): pdf_cleanup_word_process(), pdf_convert_shell(), pdf_detect_engine(), pdf_shell_available()

### Community 38 - "Dépendances Composer"
Cohesion: 0.40
Nodes (4): require, dompdf/dompdf, phpoffice/phpspreadsheet, phpoffice/phpword

### Community 39 - "Dossier ANFURA-CREATIVE"
Cohesion: 0.60
Nodes (5): Dossier création SARL AU — ANFURA-CREATIVE, Certificat négatif (ANFURA-CREATIVE), Certificat négatif — ANFURA-CREATIVE, Contrat de domiciliation — ANFURA-CREATIVE, Contrat de domiciliation (ANFURA-CREATIVE)

### Community 40 - "Parsing de valeurs (formulaires)"
Cohesion: 0.40
Nodes (5): date_value(), field_value(), int_value(), money_value(), parse_money()

### Community 41 - "Filtrage templates par forme"
Cohesion: 0.40
Nodes (4): ensure_template_folder(), fetch_legal_form_template_folder(), filterTemplatesByLegalForm(), PDO

### Community 42 - "Serveur dev multi-projets"
Cohesion: 0.40
Nodes (5): Commande opencode /dev (serveur PHP intégré), Vérification HTTP 200/302 via curl.exe, Serveur PHP intégré multi-projets (dev-server.ps1), Vérification du port libre (Get-NetTCPConnection), Lancement détaché du serveur (Start-Process php.exe)

### Community 43 - "Migration RBAC"
Cohesion: 0.83
Nodes (3): permissions, role_permissions, roles

### Community 44 - "Migration RBAC timestampée"
Cohesion: 0.83
Nodes (3): permissions, role_permissions, roles

### Community 45 - "Migration cession_parts"
Cohesion: 0.67
Nodes (3): cession_parts, cessions, societes

### Community 46 - "Migration pv_ago"
Cohesion: 0.50
Nodes (3): pv_ago, collaborateurs, societes

### Community 47 - "Migration cession_suivi"
Cohesion: 0.67
Nodes (3): cession_suivi_documents, cession_suivi_etapes, cessions

### Community 48 - "Design notifications"
Cohesion: 1.00
Nodes (4): Gestion des notifications fixe, Notifications fixées, Décision UI : panneau notifications/gestion fixe, Page Notifications

### Community 49 - "Scripts setup macOS"
Cohesion: 0.83
Nodes (3): import_sql(), install_pkg(), setup.sh script

### Community 50 - "Guides projet (AGENTS/CLAUDE)"
Cohesion: 0.67
Nodes (3): AGENTS.md (PHP Project Guide), CLAUDE.md (Contexte & Mémoire), Analyse du projet (13/05/2026)

### Community 51 - "PV-AGO Test-Import-SARL"
Cohesion: 0.67
Nodes (3): Société Test-Import-SARL, PV-AGO — Procès-Verbal d'Assemblée Générale Ordinaire (dossiers_generer), PV-AGO — Test-Import-SARL

### Community 52 - "Pages Rôles/Fonctions"
Cohesion: 1.00
Nodes (3): Page Fonctions, Page Rôles fixée, Page Rôles / Fonctions

### Community 53 - "Scroll listes - captures"
Cohesion: 1.00
Nodes (3): List Scroll Layout Screenshots, Scroll Behavior Test Screenshot, Sociétés List Scroll Test Screenshot

## Ambiguous Edges - Review These
- `Associé 24 (dark, clean)` → `Dark Mode Design`  [AMBIGUOUS]
  docs/screenshots/associe24_dark_clean.png · relation: references
- `Associé 24 (dark, final)` → `Dark Mode Design`  [AMBIGUOUS]
  docs/screenshots/associe24_dark_final.png · relation: references
- `Associé A4 (dark)` → `A4 Print Layout`  [AMBIGUOUS]
  docs/screenshots/associe_A4_dark.png · relation: references
- `Associé A4 (dark)` → `Dark Mode Design`  [AMBIGUOUS]
  docs/screenshots/associe_A4_dark.png · relation: references
- `Associé detail A4 (dark)` → `A4 Print Layout`  [AMBIGUOUS]
  docs/screenshots/associe_detail_A4_dark.png · relation: references
- `Associé detail (dark)` → `Dark Mode Design`  [AMBIGUOUS]
  docs/screenshots/associe_detail_dark.png · relation: references
- `Cession wizard step 0` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-step0.png · relation: references
- `Cession wizard step 1` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-step1.png · relation: references
- `Cession wizard step 2` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-step2.png · relation: references
- `Cession wizard step 3` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-step3.png · relation: references
- `Cession wizard step 4` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-step4.png · relation: references
- `Cession v2 wizard step 0` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-v2-step0.png · relation: references
- `Cession v2 wizard step 1` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-v2-step1.png · relation: references
- `Cession v2 wizard step 2` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-v2-step2.png · relation: references
- `Cession v2 wizard step 3` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-v2-step3.png · relation: references
- `Cession v2 wizard step 4 (table)` → `Step Wizard Pattern`  [AMBIGUOUS]
  docs/screenshots/cession-v2-step4-table.png · relation: references
- `Associé Form` → `Dark Mode Design`  [AMBIGUOUS]
  docs/screenshots/associe24_dark_final.png · relation: references
- `Wizard Step 1 (Société) Screenshot` → `Wizard Step Header Pattern`  [AMBIGUOUS]
  docs/screenshots/wizard_step1.png · relation: references
- `Interface de generation des documents` → `Page de connexion`  [AMBIGUOUS]
  connexion_page.png · relation: conceptually_related_to
- `Interface de generation des documents` → `Tableau de gestion des societes`  [AMBIGUOUS]
  societes_table.png · relation: conceptually_related_to

## Knowledge Gaps
- **120 isolated node(s):** `randNames`, `associesContainer`, `associeTemplate`, `addAssocieButton`, `phpoffice/phpword` (+115 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **38 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Associé 24 (dark, clean)` and `Dark Mode Design`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Associé 24 (dark, final)` and `Dark Mode Design`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Associé A4 (dark)` and `A4 Print Layout`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Associé A4 (dark)` and `Dark Mode Design`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Associé detail A4 (dark)` and `A4 Print Layout`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Associé detail (dark)` and `Dark Mode Design`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Cession wizard step 0` and `Step Wizard Pattern`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._