USE `center_domiciliation`;

INSERT INTO societes (raison_sociale, forme_juridique, ice, rc, if_number, adresse, ville, email, telephone, capital)
VALUES
('Atlas Domiciliation', 'SARL', '001122334455667', 'RC12345', 'IF778899', '123 Boulevard Hassan II', 'Casablanca', 'contact@atlas.test', '+212600000001', 100000.00),
('Maghreb Services', 'SARL AU', '998877665544332', 'RC54321', 'IF665544', '45 Avenue Mohammed V', 'Rabat', 'admin@maghreb.test', '+212600000002', 50000.00);

INSERT INTO associes (societe_id, nom_complet, cin, adresse, nationalite, parts, is_gerant)
VALUES
(1, 'Youssef El Idrissi', 'BK123456', 'Casablanca', 'Marocaine', 60, 1),
(1, 'Salma Bennani', 'BE654321', 'Casablanca', 'Marocaine', 40, 0),
(2, 'Imane Alaoui', 'CD987654', 'Rabat', 'Marocaine', 100, 1);

INSERT INTO contrats (societe_id, type_contrat, date_debut, date_fin, loyer_mensuel_ttc, caution_montant, statut, notes)
VALUES
(1, 'Domiciliation commerciale', '2026-01-01', '2026-12-31', 1200.00, 1200.00, 'actif', 'Contrat annuel standard'),
(2, 'Pack lancement', '2026-03-01', '2027-02-28', 900.00, 900.00, 'actif', 'Pack simplifie');

INSERT INTO collaborateurs (societe_id, nom_complet, fonction, email, telephone, date_debut, statut, notes)
VALUES
(1, 'Nadia Chraibi', 'Gestion administrative', 'nadia@atlas.test', '+212600000010', '2026-01-05', 'actif', 'Suivi dossiers clients'),
(NULL, 'Karim Tazi', 'Support operationnel', 'karim@center.test', '+212600000011', '2026-02-01', 'actif', 'Appui polyvalent');

