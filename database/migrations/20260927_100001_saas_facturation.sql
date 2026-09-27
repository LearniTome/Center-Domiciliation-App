-- SaaS multi-tenancy : abonnements annuels, paiements, factures
-- Phase 1/5 - Fondation donnees
--
-- L'abonnement est porte par le CABINET, jamais par l'utilisateur.
-- Un cabinet peut avoir un historique : un seul abonnement actif a la fois
-- (statut essai|actif), les autres sont expires|resilies|suspendus.

CREATE TABLE IF NOT EXISTS abonnements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED DEFAULT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'actif',
    prix_annuel_negocie DECIMAL(10,2) DEFAULT NULL,
    devise VARCHAR(3) NOT NULL DEFAULT 'MAD',
    auto_renew TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_abonnements_cabinet (cabinet_id),
    KEY idx_abonnements_statut (statut),
    KEY idx_abonnements_fin (date_fin),
    CONSTRAINT fk_abonnements_cabinet FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    CONSTRAINT fk_abonnements_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS paiements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    abonnement_id INT UNSIGNED DEFAULT NULL,
    cabinet_id INT UNSIGNED DEFAULT NULL,
    montant DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    devise VARCHAR(3) NOT NULL DEFAULT 'MAD',
    mode VARCHAR(30) NOT NULL DEFAULT 'virement',
    reference VARCHAR(120) DEFAULT NULL,
    date_paiement DATE NOT NULL,
    periode_debut DATE DEFAULT NULL,
    periode_fin DATE DEFAULT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'encaisse',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_paiements_cabinet (cabinet_id),
    KEY idx_paiements_abonnement (abonnement_id),
    KEY idx_paiements_statut (statut),
    KEY idx_paiements_date (date_paiement),
    CONSTRAINT fk_paiements_abonnement FOREIGN KEY (abonnement_id) REFERENCES abonnements(id) ON DELETE SET NULL,
    CONSTRAINT fk_paiements_cabinet FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS factures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(40) NOT NULL,
    cabinet_id INT UNSIGNED NOT NULL,
    abonnement_id INT UNSIGNED DEFAULT NULL,
    paiement_id INT UNSIGNED DEFAULT NULL,
    date_emission DATE NOT NULL,
    date_echeance DATE DEFAULT NULL,
    montant_ht DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tva_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    montant_ttc DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_factures_numero (numero),
    KEY idx_factures_cabinet (cabinet_id),
    KEY idx_factures_abonnement (abonnement_id),
    KEY idx_factures_statut (statut),
    KEY idx_factures_echeance (date_echeance),
    CONSTRAINT fk_factures_cabinet FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    CONSTRAINT fk_factures_abonnement FOREIGN KEY (abonnement_id) REFERENCES abonnements(id) ON DELETE SET NULL,
    CONSTRAINT fk_factures_paiement FOREIGN KEY (paiement_id) REFERENCES paiements(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
