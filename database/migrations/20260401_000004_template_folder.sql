SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'center_domiciliation' AND TABLE_NAME = 'ref_formes_juridiques' AND COLUMN_NAME = 'template_folder');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE ref_formes_juridiques ADD COLUMN template_folder VARCHAR(120) DEFAULT \'\' NOT NULL AFTER forme_juridique',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE ref_formes_juridiques SET template_folder = 'SARL AU' WHERE forme_juridique = 'SARL AU' AND (template_folder IS NULL OR template_folder = '');
UPDATE ref_formes_juridiques SET template_folder = 'SARL' WHERE forme_juridique = 'SARL' AND (template_folder IS NULL OR template_folder = '');
UPDATE ref_formes_juridiques SET template_folder = 'SA' WHERE forme_juridique = 'SA' AND (template_folder IS NULL OR template_folder = '');
