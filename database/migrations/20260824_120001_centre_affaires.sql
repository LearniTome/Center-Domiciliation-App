-- Centre d'affaires : fiche singleton (id = 1) avec logo
CREATE TABLE IF NOT EXISTS centre_affaires (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    denomination VARCHAR(190) NOT NULL DEFAULT '',
    adresse VARCHAR(255) NOT NULL DEFAULT '',
    numero_if VARCHAR(50) NOT NULL DEFAULT '',
    numero_ice VARCHAR(50) NOT NULL DEFAULT '',
    numero_rc VARCHAR(50) NOT NULL DEFAULT '',
    numero_tp VARCHAR(50) NOT NULL DEFAULT '',
    numero_cnss VARCHAR(50) NOT NULL DEFAULT '',
    adresse_dgi VARCHAR(255) NOT NULL DEFAULT '',
    adresse_cnss VARCHAR(255) NOT NULL DEFAULT '',
    logo_path VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NULL DEFAULT NULL,
    updated_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO centre_affaires (id, denomination, created_at, updated_at)
VALUES (1, '', NOW(), NOW());
