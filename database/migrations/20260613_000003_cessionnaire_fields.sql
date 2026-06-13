ALTER TABLE cession_parts
  ADD COLUMN cessionnaire_telephone VARCHAR(20) DEFAULT '' NOT NULL AFTER cessionnaire_adresse,
  ADD COLUMN cessionnaire_email VARCHAR(120) DEFAULT '' NOT NULL AFTER cessionnaire_telephone,
  ADD COLUMN cessionnaire_qualite VARCHAR(80) DEFAULT '' NOT NULL AFTER cessionnaire_email,
  ADD COLUMN cessionnaire_parts INT UNSIGNED DEFAULT 0 NOT NULL AFTER cessionnaire_qualite,
  ADD COLUMN cessionnaire_capital_detenu DECIMAL(12,2) DEFAULT 0.00 NOT NULL AFTER cessionnaire_parts,
  ADD COLUMN cessionnaire_est_gerant TINYINT(1) DEFAULT 0 NOT NULL AFTER cessionnaire_capital_detenu;
