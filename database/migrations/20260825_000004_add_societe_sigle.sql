-- Ajout de la colonne societe_sigle pour l'abreviation / sigle de la societe
ALTER TABLE societes ADD COLUMN societe_sigle VARCHAR(100) DEFAULT NULL AFTER societe_raison_sociale;
