ALTER TABLE ref_ste_adresses DROP INDEX uq_ref_ste_adresses;
ALTER TABLE ref_ste_adresses ADD COLUMN ville VARCHAR(100) DEFAULT '' NOT NULL AFTER ste_adresse;
ALTER TABLE ref_ste_adresses ADD UNIQUE KEY uq_ref_ste_adresses (ste_adresse, ville);
