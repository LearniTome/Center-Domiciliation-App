ALTER TABLE cession_parts
  ADD COLUMN pourcentage DECIMAL(7,2) DEFAULT NULL AFTER prix_total,
  ADD COLUMN nommer_gerant TINYINT(1) NOT NULL DEFAULT 0 AFTER pourcentage;
