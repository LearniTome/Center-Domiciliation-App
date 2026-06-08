-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: center_domiciliation
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `_migrations`
--

DROP TABLE IF EXISTS `_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_migrations`
--

LOCK TABLES `_migrations` WRITE;
/*!40000 ALTER TABLE `_migrations` DISABLE KEYS */;
INSERT INTO `_migrations` VALUES (1,'20260401_000001_add_tribunal_type.sql','2026-06-07 12:45:56'),(2,'20260401_000002_rename_columns.sql','2026-06-07 12:45:56'),(3,'20260401_000003_rbac.sql','2026-06-07 12:45:56'),(4,'20260401_000004_template_folder.sql','2026-06-07 12:46:50'),(5,'20260608_000005_add_ref_fonctions.sql','2026-06-08 17:11:03');
/*!40000 ALTER TABLE `_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_nom` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `entity_label` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-06-04 16:54:41'),(2,3,'Super Admin','connexion','auth',3,'Super Admin',NULL,'::1','2026-06-04 16:54:50'),(3,NULL,NULL,'test','test_entity',999,'Test label','{}','127.0.0.1','2026-06-04 16:57:45'),(4,3,'Super Admin','create','societe',123,'Test SARL','{\"forme\":\"SARL\"}','127.0.0.1','2026-06-04 16:57:58'),(5,3,'Super Admin','update','collaborateur',2,'Karim Tazi',NULL,'::1','2026-06-07 11:38:45'),(6,3,'Super Admin','upload','document',NULL,'Uploads étape 5 — 2 fichier(s)','{\"certificat_negatif\":null,\"cin_gerants\":null}','::1','2026-06-07 12:04:20'),(7,3,'Super Admin','create','dossier',18,'Test Wizard SARL AU','{\"forme_juridique\":\"SARL AU\",\"nb_associes\":1,\"type_generation\":\"\"}','::1','2026-06-07 12:04:27'),(8,3,'Super Admin','validate','document_genere',18,'Validation — 1 doc(s)',NULL,'::1','2026-06-07 12:05:17'),(9,3,'Super Admin','validate','document_genere',18,'Validation — 6 doc(s)',NULL,'::1','2026-06-07 12:13:09'),(10,3,'Super Admin','restore','document_genere',18,'Restauration brouillon — 6 doc(s)',NULL,'::1','2026-06-07 12:19:37'),(11,3,'Super Admin','validate','document_genere',18,'Validation — 6 doc(s)',NULL,'::1','2026-06-07 12:19:50'),(12,3,'Super Admin','generate','document',16,'Generation AJAX — SARL AU_2026-06-07_Annonce-Legale-Journal_AMAR-STE_Brouillon.docx',NULL,'::1','2026-06-07 12:35:01'),(13,3,'Super Admin','validate','document_genere',16,'Validation — 1 doc(s)',NULL,'::1','2026-06-07 12:35:43'),(14,3,'Super Admin','convert_pdf','document',16,'Conversion PDF AJAX — SARL AU_2026-06-07_Annonce-Legale-Journal_AMAR-STE.pdf',NULL,'::1','2026-06-07 12:36:05'),(15,3,'Super Admin','delete','document_genere',16,'Suppression — 1 doc(s)',NULL,'::1','2026-06-07 12:47:47'),(16,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-06-07 12:57:44'),(17,6,'Admin Test','connexion','auth',6,'Admin Test',NULL,'::1','2026-06-07 12:59:17'),(18,3,'Super Admin','connexion','auth',3,'Super Admin',NULL,'::1','2026-06-07 15:59:57'),(19,3,'Super Admin','validate','document',NULL,'6 doc(s)',NULL,'::1','2026-06-07 16:04:44'),(20,3,'Super Admin','validate','document',NULL,'6 doc(s)',NULL,'::1','2026-06-07 16:05:54'),(21,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Annonce-Legale-Journal_GITREIO.pdf',NULL,'::1','2026-06-07 16:06:53'),(22,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Declaration-Immatriculation-RC_GITREIO.pdf',NULL,'::1','2026-06-07 16:06:58'),(23,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Depot-Legal-Constitution_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:03'),(24,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Statuts_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:08'),(25,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Attestation-Domiciliation-Initiale_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:13'),(26,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Contrat-Domiciliation_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:19'),(27,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Statuts_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:24'),(28,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Depot-Legal-Constitution_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:28'),(29,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Declaration-Immatriculation-RC_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:33'),(30,3,'Super Admin','convert_pdf','document',17,'Conversion PDF AJAX — SARL AU_2026-06-02_Annonce-Legale-Journal_GITREIO.pdf',NULL,'::1','2026-06-07 16:07:42'),(31,3,'Super Admin','delete','document_genere',17,'Suppression — 4 doc(s)',NULL,'::1','2026-06-07 16:08:06'),(32,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-06-07 16:10:54');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `associes`
--

DROP TABLE IF EXISTS `associes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `associes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `societe_id` int(10) unsigned NOT NULL,
  `associe_civilite` varchar(10) DEFAULT NULL,
  `associe_prenom` varchar(120) DEFAULT NULL,
  `associe_nom` varchar(120) DEFAULT NULL,
  `associe_nom_complet` varchar(255) NOT NULL,
  `associe_cin` varchar(100) DEFAULT NULL,
  `associe_date_validite_cin` date DEFAULT NULL,
  `associe_adresse` text DEFAULT NULL,
  `associe_date_naissance` date DEFAULT NULL,
  `associe_lieu_naissance` varchar(120) DEFAULT NULL,
  `associe_nationalite` varchar(120) DEFAULT NULL,
  `associe_telephone` varchar(60) DEFAULT NULL,
  `associe_email` varchar(190) DEFAULT NULL,
  `associe_qualite` varchar(150) DEFAULT NULL,
  `associe_parts` int(11) DEFAULT NULL,
  `associe_capital_detenu` decimal(15,2) DEFAULT NULL,
  `associe_part_percent` decimal(7,2) DEFAULT NULL,
  `associe_est_gerant` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_associes_societe` (`societe_id`),
  KEY `idx_associes_nom_complet` (`associe_nom_complet`),
  CONSTRAINT `fk_associes_societe` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `associes`
--

LOCK TABLES `associes` WRITE;
/*!40000 ALTER TABLE `associes` DISABLE KEYS */;
INSERT INTO `associes` VALUES (1,1,'Monsieur','Youssef','El Idrissi','Youssef El Idrissi','BK123456','2026-05-18','Casablanca','2026-05-18','Casablanca','Marocaine','+212600000101','youssef@atlas.test','Associé majoritaire',60,NULL,NULL,1,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(2,1,'Madame','Salma','Bennani','Salma Bennani','BE654321','2026-05-18','Casablanca','2026-05-18','Casablanca','Marocaine','+212600000102','salma@atlas.test','Associé minoritaire',40,NULL,NULL,0,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(3,2,'Madame','Imane','Alaoui','Imane Alaoui','CD987654','2026-05-18','Rabat','2026-05-18','Rabat','Marocaine','+212600000103','imane@maghreb.test','Associé unique',100,NULL,NULL,1,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(10,9,'Mr','Soufiane','BENANI','Mr Soufiane BENANI','AB149856','2026-05-18','123 Rue Mohammed V, Casablanca','2026-05-18','Casablanca','Marocaine','0612345678','','Gerant',1000,100000.00,100.00,1,'2026-05-16 15:31:01','2026-05-18 19:08:10'),(13,12,'Mme','Sabah','AMLOUR','Mme Sabah AMLOUR','BG14517','2028-03-10','N 44 Rue Al fatouaki, Casablanca','1993-07-15','Casablanca','Marocaine','0661587840','','Gerant',1000,100000.00,100.00,1,'2026-05-18 22:09:50','2026-05-18 22:09:50'),(14,13,'Mr','Ahmed','BENANI','Mr Ahmed BENANI','AB123456','2028-05-15','123 Rue Mohammed V, Casablanca','1990-05-15','Casablanca','','0612345678','ahmed.benani@test.ma','Gerant',1000,100000.00,100.00,1,'2026-05-31 18:31:07','2026-05-31 18:31:07'),(15,14,'Mr','Ahmed','BENANI','Mr Ahmed BENANI','AB123456','2028-05-15','123 Rue Mohammed V, Casablanca','1990-05-15','Casablanca','','0612345678','ahmed.benani@test.ma','Gerant',1000,100000.00,100.00,1,'2026-05-31 20:26:52','2026-05-31 20:26:52'),(16,15,'Mr','Ahmed','BENANI','Mr Ahmed BENANI','AB123456','2028-05-15','123 Rue Mohammed V, Casablanca','1990-05-15','Casablanca','','0612345678','ahmed.benani@test.ma','Gerant',1000,100000.00,100.00,1,'2026-06-01 18:32:20','2026-06-01 18:32:20'),(17,16,'Mr','Ahmed','BENANI','Mr Ahmed BENANI','AB123456','2028-05-15','123 Rue Mohammed V, Casablanca','1990-05-15','Casablanca','','0612345678','ahmed.benani@test.ma','Gerant',1000,100000.00,100.00,1,'2026-06-01 18:38:35','2026-06-01 18:38:35'),(18,17,'Mr','Ahmed','BENANI','Mr Ahmed BENANI','AB123456','2028-05-15','123 Rue Mohammed V, Casablanca','1990-05-15','Casablanca','','0612345678','ahmed.benani@test.ma','Gerant',1000,100000.00,100.00,1,'2026-06-02 14:12:30','2026-06-02 14:12:30'),(19,18,'','User','Test','User Test','AB123456',NULL,'123 Rue Test, Casablanca',NULL,'','','0612345678','test@user.com','Gerant',1000,100000.00,100.00,1,'2026-06-07 12:04:27','2026-06-07 12:04:27'),(20,1,NULL,NULL,NULL,'Youssef El Idrissi','BK123456',NULL,'Casablanca','1990-01-01','Casablanca','Marocaine','+212600000101','youssef@atlas.test','Associé majoritaire',60,NULL,NULL,1,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(21,1,NULL,NULL,NULL,'Salma Bennani','BE654321',NULL,'Casablanca','1992-04-10','Casablanca','Marocaine','+212600000102','salma@atlas.test','Associé minoritaire',40,NULL,NULL,0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(22,2,NULL,NULL,NULL,'Imane Alaoui','CD987654',NULL,'Rabat','1988-09-15','Rabat','Marocaine','+212600000103','imane@maghreb.test','Associé unique',100,NULL,NULL,1,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `associes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaborateur_permissions`
--

DROP TABLE IF EXISTS `collaborateur_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaborateur_permissions` (
  `collaborateur_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`collaborateur_id`,`permission_id`),
  KEY `fk_cp_permission` (`permission_id`),
  CONSTRAINT `fk_cp_collaborateur` FOREIGN KEY (`collaborateur_id`) REFERENCES `collaborateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborateur_permissions`
--

LOCK TABLES `collaborateur_permissions` WRITE;
/*!40000 ALTER TABLE `collaborateur_permissions` DISABLE KEYS */;
INSERT INTO `collaborateur_permissions` VALUES (2,1,1),(2,2,0),(2,3,0),(2,4,0),(2,5,0),(2,6,0),(2,7,1),(2,8,1),(2,9,1),(2,10,1),(2,11,1),(2,12,0),(2,13,0),(2,14,0),(2,15,0),(2,16,0),(2,17,0),(2,18,0),(2,19,0),(2,20,0),(2,21,0),(2,22,0),(2,23,0),(2,24,0),(2,25,0),(2,26,0),(2,27,0),(2,28,0),(2,29,0),(2,30,0),(2,31,0),(2,32,0),(2,33,0),(2,34,0),(2,35,0),(2,36,0),(2,37,0),(2,38,0);
/*!40000 ALTER TABLE `collaborateur_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaborateurs`
--

DROP TABLE IF EXISTS `collaborateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaborateurs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `societe_id` int(10) unsigned DEFAULT NULL,
  `den_ste` varchar(255) DEFAULT NULL,
  `nom_complet` varchar(255) NOT NULL,
  `fonction` varchar(150) DEFAULT NULL,
  `collaborateur_type` varchar(120) DEFAULT NULL,
  `collaborateur_code` varchar(120) DEFAULT NULL,
  `collaborateur_nom` varchar(255) DEFAULT NULL,
  `collaborateur_ice` varchar(100) DEFAULT NULL,
  `collaborateur_tp` varchar(100) DEFAULT NULL,
  `collaborateur_rc` varchar(100) DEFAULT NULL,
  `collaborateur_if` varchar(100) DEFAULT NULL,
  `collaborateur_tel_fixe` varchar(60) DEFAULT NULL,
  `collaborateur_tel_mobile` varchar(60) DEFAULT NULL,
  `collaborateur_adresse` text DEFAULT NULL,
  `collaborateur_email` varchar(190) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `telephone` varchar(60) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `statut` varchar(80) DEFAULT 'actif',
  `notes` text DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role_id` int(10) unsigned DEFAULT NULL,
  `can_login` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_collaborateurs_societe` (`societe_id`),
  KEY `idx_collaborateurs_role_id` (`role_id`),
  KEY `idx_collaborateurs_can_login` (`can_login`),
  CONSTRAINT `fk_collaborateurs_societe` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborateurs`
--

LOCK TABLES `collaborateurs` WRITE;
/*!40000 ALTER TABLE `collaborateurs` DISABLE KEYS */;
INSERT INTO `collaborateurs` VALUES (1,1,'Atlas Domiciliation','Atlas Domiciliation','','externe-pm','EXP','','ICE-COL-001','TP001','RC-C001','IF-C001','0522000001','+212600000010','Casablanca','nadia@atlas.test','','','0000-00-00','actif','Suivi dossiers clients',NULL,7,0,NULL,NULL,'2026-05-11 09:26:26','2026-06-04 15:21:25'),(2,NULL,'','Karim Tazi','','externe-pp','','','','','','','','+212600000011','Casablanca','karim@center.test','','',NULL,'actif','Appui polyvalent - MAJ test','$2y$10$L1poXUyMj.klKJh9eVZgNOzpjU9y1i.jGB5ij5jSmQg5epRarzrYW',10,1,'2026-06-04 17:15:12',NULL,'2026-05-11 09:26:26','2026-06-07 11:38:45'),(3,NULL,NULL,'Super Admin','Administrateur système','interne',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin@center.test','admin@center.test',NULL,NULL,'actif','Compte super admin par défaut — changer le mot de passe','$2y$10$ooRmdBViRMS7E5g0d1nLye2VyhCSn8Wvao9g9sxDS4iUqB/GDSqeC',1,1,'2026-06-07 16:59:57',NULL,'2026-06-02 12:02:28','2026-06-07 15:59:57'),(5,NULL,NULL,'Test User','Gérant','interne',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'test@center.test','test@center.test',NULL,'2026-06-02','actif','Compte de test cree le 02/06/2026','$2y$10$RuMksG/nNrOxCYbRR1/DAuY/2M5niyw/nmNrO1tRUpqly.yFakb4y',4,1,NULL,NULL,'2026-06-02 17:07:21','2026-06-02 18:14:23'),(6,NULL,NULL,'Admin Test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin2@center.test',NULL,NULL,'actif',NULL,'$2y$10$6lfRCFxgmt2Gt020ChBJze9qOibyu1vHV.iybSqGLEuyfvRVrQUGm',2,1,'2026-06-07 13:59:17',NULL,'2026-06-07 12:55:23','2026-06-07 12:59:17'),(7,NULL,'Atlas Domiciliation','Nadia Chraibi','Gestion administrative','externe-pm','EXP','Nadia Chraibi','ICE-COL-001','TP001','RC-C001','IF-C001','0522000001','+212600000010','Casablanca','nadia@atlas.test','nadia@atlas.test','+212600000010','2026-01-05','actif','Suivi dossiers clients',NULL,NULL,0,NULL,NULL,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(8,NULL,NULL,'Karim Tazi','Support operationnel','externe-pp','CLTD','Karim Tazi','ICE-COL-002','TP002','RC-C002','IF-C002','0522000002','+212600000011','Casablanca','karim@center.test','karim@center.test','+212600000011','2026-02-01','actif','Appui polyvalent',NULL,NULL,0,NULL,NULL,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `collaborateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contrats`
--

DROP TABLE IF EXISTS `contrats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `societe_id` int(10) unsigned NOT NULL,
  `contrat_type` varchar(120) NOT NULL,
  `contrat_date` date DEFAULT NULL,
  `contrat_duree_mois` int(11) DEFAULT NULL,
  `contrat_type_domiciliation` varchar(120) DEFAULT NULL,
  `contrat_type_domiciliation_autre` varchar(190) DEFAULT NULL,
  `contrat_date_debut` date DEFAULT NULL,
  `contrat_date_fin` date DEFAULT NULL,
  `contrat_loyer_ttc` decimal(15,2) DEFAULT NULL,
  `contrat_frais_intermediaire` decimal(15,2) DEFAULT NULL,
  `contrat_caution` decimal(15,2) DEFAULT NULL,
  `contrat_tva_pourcent` decimal(7,2) DEFAULT NULL,
  `contrat_loyer_ht` decimal(15,2) DEFAULT NULL,
  `contrat_total_ht` decimal(15,2) DEFAULT NULL,
  `contrat_pack_montant_ttc` decimal(15,2) DEFAULT NULL,
  `contrat_pack_loyer_ttc` decimal(15,2) DEFAULT NULL,
  `contrat_type_renouvellement` varchar(120) DEFAULT NULL,
  `contrat_renouv_tva_pourcent` decimal(7,2) DEFAULT NULL,
  `contrat_renouv_loyer_ht` decimal(15,2) DEFAULT NULL,
  `contrat_renouv_total_ht` decimal(15,2) DEFAULT NULL,
  `contrat_renouv_loyer_ttc` decimal(15,2) DEFAULT NULL,
  `contrat_renouv_annuel_ttc` decimal(15,2) DEFAULT NULL,
  `contrat_statut` varchar(80) DEFAULT 'actif',
  `contrat_notes` text DEFAULT NULL,
  `contrat_mode_signature` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_contrats_societe` (`societe_id`),
  KEY `idx_contrats_type` (`contrat_type`),
  CONSTRAINT `fk_contrats_societe` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contrats`
--

LOCK TABLES `contrats` WRITE;
/*!40000 ALTER TABLE `contrats` DISABLE KEYS */;
INSERT INTO `contrats` VALUES (1,1,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2026-05-18',1200.00,300.00,1200.00,20.00,1000.00,12000.00,1500.00,1250.00,'Annuel',20.00,1000.00,12000.00,1200.00,14400.00,'actif','Contrat annuel standard',NULL,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(2,2,'Pack lancement','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2026-05-18',900.00,250.00,900.00,20.00,750.00,9000.00,1000.00,900.00,'Annuel',20.00,750.00,9000.00,900.00,10800.00,'actif','Pack simplifie',NULL,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(9,9,'Domiciliation simple','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2026-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,0.00,200.00,NULL,'actif','',NULL,'2026-05-16 15:31:01','2026-05-18 19:08:10'),(12,12,'Domiciliation simple','2026-05-18',24,'Personne Morale',NULL,'2026-05-18','2028-05-18',100.00,NULL,NULL,20.00,83.33,2.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-05-18 22:09:50','2026-05-18 22:09:50'),(13,13,'Domiciliation simple','2026-01-01',12,'Personne Morale',NULL,'2026-01-01','2027-01-01',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','Contrat de test pour validation',NULL,'2026-05-31 18:31:07','2026-05-31 18:31:07'),(14,14,'Domiciliation simple','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-05-31 20:26:52','2026-05-31 20:26:52'),(15,15,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-01 18:32:20','2026-06-01 18:32:20'),(16,16,'Domiciliation simple','2026-05-18',24,'Personne Morale',NULL,'2026-05-18','2028-05-18',100.00,NULL,NULL,20.00,83.33,2.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-01 18:38:35','2026-06-01 18:38:35'),(17,17,'Domiciliation commerciale','2026-05-18',36,'Personne Morale',NULL,'2026-05-18','2029-05-18',100.00,NULL,NULL,20.00,83.33,3.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-02 14:12:30','2026-06-02 14:12:30'),(18,18,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-07 12:04:27','2026-06-07 12:04:27'),(19,1,'Domiciliation commerciale','2026-01-01',12,'Personne Morale',NULL,'2026-01-01','2026-12-31',1200.00,300.00,1200.00,20.00,1000.00,12000.00,1500.00,1250.00,'Annuel',20.00,1000.00,12000.00,1200.00,14400.00,'actif','Contrat annuel standard',NULL,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(20,2,'Pack lancement','2026-03-01',12,'Personne Morale',NULL,'2026-03-01','2027-02-28',900.00,250.00,900.00,20.00,750.00,9000.00,1000.00,900.00,'Annuel',20.00,750.00,9000.00,900.00,10800.00,'actif','Pack simplifie',NULL,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `contrats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents_generes`
--

DROP TABLE IF EXISTS `documents_generes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents_generes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `societe_id` int(10) unsigned NOT NULL,
  `template_source` varchar(255) DEFAULT NULL,
  `doc_type` varchar(100) DEFAULT NULL,
  `fichier_docx` varchar(500) NOT NULL,
  `fichier_pdf` varchar(500) DEFAULT NULL,
  `taille_ko` decimal(10,1) DEFAULT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_documents_societe_id` (`societe_id`),
  KEY `idx_documents_doc_type` (`doc_type`),
  KEY `idx_documents_valide` (`valide`),
  CONSTRAINT `fk_documents_societe` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents_generes`
--

LOCK TABLES `documents_generes` WRITE;
/*!40000 ALTER TABLE `documents_generes` DISABLE KEYS */;
INSERT INTO `documents_generes` VALUES (19,1,NULL,'Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL_2026-05-14_Annonce-Legale-Journal_Atlas-Domiciliation.docx',NULL,20.7,1,'2026-05-14 14:56:24'),(20,1,NULL,'Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL_2026-05-14_Attestation-Domiciliation-Initiale_Atlas-Domiciliation.docx',NULL,105.9,1,'2026-05-14 14:56:24'),(21,1,NULL,'Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL_2026-05-14_Contrat-Domiciliation_Atlas-Domiciliation.docx',NULL,214.6,1,'2026-05-14 14:56:24'),(22,1,NULL,'Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL_2026-05-14_Declaration-Immatriculation-RC_Atlas-Domiciliation.docx',NULL,284.0,1,'2026-05-14 14:56:24'),(23,1,NULL,'Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL_2026-05-14_Depot-Legal-Constitution_Atlas-Domiciliation.docx',NULL,282.7,1,'2026-05-14 14:56:24'),(24,1,NULL,'Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL_2026-05-14_Statuts_Atlas-Domiciliation.docx',NULL,35.6,1,'2026-05-14 14:56:24'),(25,2,NULL,'Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-14_Annonce-Legale-Journal_Maghreb-Services.docx',NULL,20.7,1,'2026-05-14 15:01:28'),(26,2,NULL,'Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-14_Attestation-Domiciliation-Initiale_Maghreb-Services.docx',NULL,105.9,1,'2026-05-14 15:01:28'),(27,2,NULL,'Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-14_Contrat-Domiciliation_Maghreb-Services.docx',NULL,214.6,1,'2026-05-14 15:01:28'),(28,2,NULL,'Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-14_Declaration-Immatriculation-RC_Maghreb-Services.docx',NULL,284.0,1,'2026-05-14 15:01:28'),(29,2,NULL,'Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-14_Depot-Legal-Constitution_Maghreb-Services.docx',NULL,282.7,1,'2026-05-14 15:01:28'),(30,2,NULL,'Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-14_Statuts_Maghreb-Services.docx',NULL,35.6,1,'2026-05-14 15:01:28'),(117,9,NULL,'Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-16_Annonce-Legale-Journal_MOUAIOA.docx',NULL,20.2,1,'2026-05-16 15:31:01'),(118,9,NULL,'Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-16_Attestation-Domiciliation-Initiale_MOUAIOA.docx',NULL,105.5,1,'2026-05-16 15:31:01'),(119,9,NULL,'Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-16_Contrat-Domiciliation_MOUAIOA.docx',NULL,213.5,1,'2026-05-16 15:31:01'),(120,9,NULL,'Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-16_Declaration-Immatriculation-RC_MOUAIOA.docx',NULL,283.5,1,'2026-05-16 15:31:01'),(121,9,NULL,'Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-16_Depot-Legal-Constitution_MOUAIOA.docx',NULL,282.2,1,'2026-05-16 15:31:01'),(122,9,NULL,'Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../output\\SARL AU_2026-05-16_Statuts_MOUAIOA.docx',NULL,34.5,1,'2026-05-16 15:31:01'),(153,12,NULL,'Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-19_SARL-AU_DOURIYA\\SARL AU_2026-05-19_Annonce-Legale-Journal_DOURIYA.docx',NULL,20.2,1,'2026-05-18 22:35:15'),(154,12,NULL,'Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-19_SARL-AU_DOURIYA\\SARL AU_2026-05-19_Declaration-Immatriculation-RC_DOURIYA.docx',NULL,283.5,1,'2026-05-18 22:35:15'),(155,12,NULL,'Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-19_SARL-AU_DOURIYA\\SARL AU_2026-05-19_Depot-Legal-Constitution_DOURIYA.docx',NULL,282.2,1,'2026-05-18 22:35:15'),(156,12,NULL,'Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-19_SARL-AU_DOURIYA\\SARL AU_2026-05-19_Statuts_DOURIYA.docx',NULL,34.5,1,'2026-05-18 22:35:15'),(157,12,NULL,'Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-19_SARL-AU_DOURIYA\\SARL AU_2026-05-19_Attestation-Domiciliation-Initiale_DOURIYA.docx',NULL,105.5,1,'2026-05-18 22:35:15'),(158,12,NULL,'Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-19_SARL-AU_DOURIYA\\SARL AU_2026-05-19_Contrat-Domiciliation_DOURIYA.docx',NULL,213.5,1,'2026-05-18 22:35:15'),(159,13,NULL,'Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-05-31_Annonce-Legale-Journal_TRABAKHANDO.docx',NULL,20.2,1,'2026-05-31 18:33:40'),(160,13,NULL,'Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-05-31_Attestation-Domiciliation-Initiale_TRABAKHANDO.docx',NULL,105.5,1,'2026-05-31 18:33:40'),(161,13,NULL,'Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-05-31_Contrat-Domiciliation_TRABAKHANDO.docx',NULL,213.5,1,'2026-05-31 18:33:40'),(162,13,NULL,'Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-05-31_Declaration-Immatriculation-RC_TRABAKHANDO.docx',NULL,283.5,1,'2026-05-31 18:33:40'),(163,13,NULL,'Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-05-31_Depot-Legal-Constitution_TRABAKHANDO.docx',NULL,282.2,1,'2026-05-31 18:33:40'),(164,13,NULL,'Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-05-31_Statuts_TRABAKHANDO.docx',NULL,34.5,1,'2026-05-31 18:33:40'),(165,14,'2026-03_Annonce-Legale-Journal_Template.docx','Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL_2026-05-31_Annonce-Legale-Journal_MUTRLIO.docx',NULL,20.2,1,'2026-05-31 20:37:44'),(166,14,'2026-03_Attestation-Domiciliation-Initiale_Template.docx','Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL_2026-05-31_Attestation-Domiciliation-Initiale_MUTRLIO.docx',NULL,105.5,1,'2026-05-31 20:37:48'),(167,14,'2026-03_Contrat-Domiciliation_Template.docx','Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL_2026-05-31_Contrat-Domiciliation_MUTRLIO.docx',NULL,213.5,1,'2026-05-31 20:37:50'),(168,14,'2026-03_Declaration-Immatriculation-RC_Template.docx','Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL_2026-05-31_Declaration-Immatriculation-RC_MUTRLIO.docx',NULL,283.4,1,'2026-05-31 20:37:56'),(169,14,'2026-03_Depot-Legal-Constitution_Template.docx','Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL_2026-05-31_Depot-Legal-Constitution_MUTRLIO.docx',NULL,282.2,1,'2026-05-31 20:38:00'),(170,14,'2026-03_Statuts_Template.docx','Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL_2026-05-31_Statuts_MUTRLIO.docx',NULL,34.5,1,'2026-05-31 20:38:04'),(177,15,'SARL-AU_2026-03_Annonce-Legale-Journal_Template.docx','Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Annonce-Legale-Journal_SEBTIYA_Brouillon.docx',NULL,20.2,1,'2026-06-01 18:32:35'),(178,15,'SARL-AU_2026-03_Attestation-Domiciliation-Initiale_Template.docx','Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Attestation-Domiciliation-Initiale_SEBTIYA_Brouillon.docx',NULL,103.3,1,'2026-06-01 18:32:39'),(179,15,'SARL-AU_2026-03_Contrat-Domiciliation_Template.docx','Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Contrat-Domiciliation_SEBTIYA_Brouillon.docx',NULL,231.6,1,'2026-06-01 18:32:43'),(180,15,'SARL-AU_2026-03_Declaration-Immatriculation-RC_Template.docx','Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Declaration-Immatriculation-RC_SEBTIYA_Brouillon.docx',NULL,283.5,1,'2026-06-01 18:32:47'),(181,15,'SARL-AU_2026-03_Depot-Legal-Constitution_Template.docx','Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Depot-Legal-Constitution_SEBTIYA_Brouillon.docx',NULL,282.2,1,'2026-06-01 18:32:51'),(182,15,'SARL-AU_2026-03_Statuts_Template.docx','Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Statuts_SEBTIYA_Brouillon.docx',NULL,34.5,1,'2026-06-01 18:32:57'),(184,16,'SARL-AU_2026-03_Attestation-Domiciliation-Initiale_Template.docx','Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Attestation-Domiciliation-Initiale_AMAR-STE.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Attestation-Domiciliation-Initiale_AMAR-STE.pdf',103.3,1,'2026-06-01 18:38:44'),(185,16,'SARL-AU_2026-03_Contrat-Domiciliation_Template.docx','Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Contrat-Domiciliation_AMAR-STE.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Contrat-Domiciliation_AMAR-STE.pdf',231.6,1,'2026-06-01 18:38:48'),(186,16,'SARL-AU_2026-03_Declaration-Immatriculation-RC_Template.docx','Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Declaration-Immatriculation-RC_AMAR-STE.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Declaration-Immatriculation-RC_AMAR-STE.pdf',283.5,1,'2026-06-01 18:38:52'),(187,16,'SARL-AU_2026-03_Depot-Legal-Constitution_Template.docx','Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Depot-Legal-Constitution_AMAR-STE.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Depot-Legal-Constitution_AMAR-STE.pdf',282.2,1,'2026-06-01 18:38:56'),(188,16,'SARL-AU_2026-03_Statuts_Template.docx','Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Statuts_AMAR-STE.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom\\SARL AU_2026-06-01_Statuts_AMAR-STE.pdf',34.5,1,'2026-06-01 18:39:00'),(193,17,NULL,'Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Annonce-Legale-Journal_GITREIO.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Annonce-Legale-Journal_GITREIO.pdf',20.1,1,'2026-06-02 14:31:00'),(194,17,NULL,'Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Declaration-Immatriculation-RC_GITREIO.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Declaration-Immatriculation-RC_GITREIO.pdf',283.5,1,'2026-06-02 14:31:00'),(195,17,NULL,'Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Depot-Legal-Constitution_GITREIO.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Depot-Legal-Constitution_GITREIO.pdf',282.2,1,'2026-06-02 14:31:00'),(196,17,NULL,'Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Statuts_GITREIO.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Statuts_GITREIO.pdf',34.5,1,'2026-06-02 14:31:00'),(197,17,NULL,'Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Attestation-Domiciliation-Initiale_GITREIO.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Attestation-Domiciliation-Initiale_GITREIO.pdf',103.3,1,'2026-06-02 14:31:00'),(198,17,NULL,'Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Contrat-Domiciliation_GITREIO.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-02_SARL-AU_GITREIO\\SARL AU_2026-06-02_Contrat-Domiciliation_GITREIO.pdf',231.6,1,'2026-06-02 14:31:00'),(199,18,'SARL-AU_2026-03_Annonce-Legale-Journal_Template.docx','Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Annonce-Legale-Journal_Test-Wizard-SARL-AU.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Annonce-Legale-Journal_Test-Wizard-SARL-AU_Brouillon.pdf',20.2,1,'2026-06-07 12:04:39'),(200,18,'SARL-AU_2026-03_Attestation-Domiciliation-Initiale_Template.docx','Attestation-Domiciliation-Initiale','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Attestation-Domiciliation-Initiale_Test-Wizard-SARL-AU.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Attestation-Domiciliation-Initiale_Test-Wizard-SARL-AU_Brouillon.pdf',105.5,1,'2026-06-07 12:04:39'),(201,18,'SARL-AU_2026-03_Contrat-Domiciliation_Template.docx','Contrat-Domiciliation','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Contrat-Domiciliation_Test-Wizard-SARL-AU.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Contrat-Domiciliation_Test-Wizard-SARL-AU_Brouillon.pdf',231.6,1,'2026-06-07 12:04:39'),(202,18,'SARL-AU_2026-03_Declaration-Immatriculation-RC_Template.docx','Declaration-Immatriculation-RC','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Declaration-Immatriculation-RC_Test-Wizard-SARL-AU.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Declaration-Immatriculation-RC_Test-Wizard-SARL-AU_Brouillon.pdf',283.4,1,'2026-06-07 12:04:39'),(203,18,'SARL-AU_2026-03_Depot-Legal-Constitution_Template.docx','Depot-Legal-Constitution','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Depot-Legal-Constitution_Test-Wizard-SARL-AU.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Depot-Legal-Constitution_Test-Wizard-SARL-AU_Brouillon.pdf',282.2,1,'2026-06-07 12:04:39'),(204,18,'SARL-AU_2026-03_Statuts_Template.docx','Statuts','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Statuts_Test-Wizard-SARL-AU.docx','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU\\SARL AU_2026-06-07_Statuts_Test-Wizard-SARL-AU_Brouillon.pdf',34.5,1,'2026-06-07 12:04:39'),(205,16,'E:\\Dev_Project\\Center-Domiciliation-App\\pages/../templates\\SARL AU\\SARL-AU_2026-03_Annonce-Legale-Journal_Template.docx','Annonce-Legale-Journal','E:\\Dev_Project\\Center-Domiciliation-App\\ajax/../dossiers_dom/2026-06-07_SARL-AU_AMAR-STE\\SARL AU_2026-06-07_Annonce-Legale-Journal_AMAR-STE.docx','E:\\Dev_Project\\Center-Domiciliation-App\\ajax/../dossiers_dom/2026-06-07_SARL-AU_AMAR-STE\\SARL AU_2026-06-07_Annonce-Legale-Journal_AMAR-STE.pdf',20.2,1,'2026-06-07 12:35:01');
/*!40000 ALTER TABLE `documents_generes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Voir le tableau de bord','dashboard.view','dashboard','Accéder au tableau de bord','2026-06-02 12:02:28'),(2,'Voir les sociétés','societes.view','societes','Consulter la liste et les fiches sociétés','2026-06-02 12:02:28'),(3,'Créer une société','societes.create','societes','Créer une nouvelle société','2026-06-02 12:02:28'),(4,'Modifier une société','societes.edit','societes','Modifier les informations d une société','2026-06-02 12:02:28'),(5,'Supprimer une société','societes.delete','societes','Supprimer une société','2026-06-02 12:02:28'),(6,'Exporter les sociétés','societes.export','societes','Exporter la liste des sociétés en CSV','2026-06-02 12:02:28'),(7,'Voir les associés','associes.view','associes','Consulter la liste des associés','2026-06-02 12:02:28'),(8,'Créer un associé','associes.create','associes','Ajouter un associé','2026-06-02 12:02:28'),(9,'Modifier un associé','associes.edit','associes','Modifier les informations d un associé','2026-06-02 12:02:28'),(10,'Supprimer un associé','associes.delete','associes','Supprimer un associé','2026-06-02 12:02:28'),(11,'Exporter les associés','associes.export','associes','Exporter la liste des associés en CSV','2026-06-02 12:02:28'),(12,'Voir les contrats','contrats.view','contrats','Consulter la liste des contrats','2026-06-02 12:02:28'),(13,'Créer un contrat','contrats.create','contrats','Ajouter un contrat','2026-06-02 12:02:28'),(14,'Modifier un contrat','contrats.edit','contrats','Modifier les informations d un contrat','2026-06-02 12:02:28'),(15,'Supprimer un contrat','contrats.delete','contrats','Supprimer un contrat','2026-06-02 12:02:28'),(16,'Exporter les contrats','contrats.export','contrats','Exporter la liste des contrats en CSV','2026-06-02 12:02:28'),(17,'Voir les collaborateurs','collaborateurs.view','collaborateurs','Consulter la liste des collaborateurs','2026-06-02 12:02:28'),(18,'Créer un collaborateur','collaborateurs.create','collaborateurs','Ajouter un collaborateur','2026-06-02 12:02:28'),(19,'Modifier un collaborateur','collaborateurs.edit','collaborateurs','Modifier les informations d un collaborateur','2026-06-02 12:02:28'),(20,'Supprimer un collaborateur','collaborateurs.delete','collaborateurs','Supprimer un collaborateur','2026-06-02 12:02:28'),(21,'Exporter les collaborateurs','collaborateurs.export','collaborateurs','Exporter la liste des collaborateurs en CSV','2026-06-02 12:02:28'),(22,'Utiliser l assistant de création','wizard.create','wizard','Accéder au wizard de création de dossier','2026-06-02 12:02:28'),(23,'Voir les templates','templates.view','templates','Consulter la liste des templates','2026-06-02 12:02:28'),(24,'Créer un template','templates.create','templates','Ajouter un nouveau template','2026-06-02 12:02:28'),(25,'Modifier un template','templates.edit','templates','Modifier un template existant','2026-06-02 12:02:28'),(26,'Supprimer un template','templates.delete','templates','Supprimer un template','2026-06-02 12:02:28'),(27,'Utiliser le générateur de dossiers','generation.use','generation','Générer les documents d un dossier','2026-06-02 12:02:28'),(28,'Voir les documents générés','documents.view','documents','Consulter la liste des documents','2026-06-02 12:02:28'),(29,'Télécharger les documents','documents.download','documents','Télécharger les fichiers générés','2026-06-02 12:02:28'),(30,'Voir la configuration','configuration.view','configuration','Accéder à la page de configuration','2026-06-02 12:02:28'),(31,'Modifier la configuration','configuration.edit','configuration','Modifier les données de configuration','2026-06-02 12:02:28'),(32,'Voir l analyse de couverture','analyse.view','analyse','Accéder à l analyse de couverture','2026-06-02 12:02:28'),(33,'Voir les variables','variables.view','variables','Consulter la gestion des variables','2026-06-02 12:02:28'),(34,'Modifier les variables','variables.edit','variables','Renommer et supprimer des variables','2026-06-02 12:02:28'),(35,'Modifier les valeurs par défaut','defaults.edit','defaults','Configurer les valeurs par défaut','2026-06-02 12:02:28'),(36,'Utiliser la conversion Word → PDF','convert.use','convert','Accéder à l outil de conversion','2026-06-02 12:02:28'),(37,'Utiliser l assistant IA','ai.use','ai','Accéder à l assistant IA','2026-06-02 12:02:28'),(38,'Gérer les rôles et permissions','roles.manage','roles','Créer, modifier et supprimer des rôles','2026-06-02 12:02:28');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_activites`
--

DROP TABLE IF EXISTS `ref_activites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_activites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activite` varchar(190) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_activites` (`activite`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_activites`
--

LOCK TABLES `ref_activites` WRITE;
/*!40000 ALTER TABLE `ref_activites` DISABLE KEYS */;
INSERT INTO `ref_activites` VALUES (1,'Travaux Divers ou de Construction',1,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(2,'Marchand effectuant Import Export',2,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(3,'Négociant',3,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(4,'Conseil de Gestion',4,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(5,'Communication Digital',5,'2026-05-15 17:19:38','2026-05-15 17:19:38'),(6,'Commerce de gros',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(7,'Commerce de detail',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(8,'Restauration',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(9,'Hotel',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(10,'Transport',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(11,'Logistique',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(12,'Consulting',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(13,'Services IT',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(14,'Services de sante',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(15,'Education',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(16,'Immobilier',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(17,'Construction',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(18,'Manufacture',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(19,'Agriculture',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(20,'Peche',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(21,'Energie',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(22,'Telecommunications',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(23,'Banque et Finance',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(24,'Assurance',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(25,'Tourisme',0,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `ref_activites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_activites_ompic`
--

DROP TABLE IF EXISTS `ref_activites_ompic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_activites_ompic` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_nma2010_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_activites_ompic`
--

LOCK TABLES `ref_activites_ompic` WRITE;
/*!40000 ALTER TABLE `ref_activites_ompic` DISABLE KEYS */;
INSERT INTO `ref_activites_ompic` VALUES (1,'A','AGRICULTURE, SYLVICULTURE ET PECHE',1,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(2,'B','INDUSTRIES EXTRACTIVES',2,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(3,'C','INDUSTRIE MANUFACTURIERE',3,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(4,'F','CONSTRUCTION',6,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(5,'G','COMMERCE; REPARATION D\'AUTOMOBILES ET DE MOTOCYCLES',7,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(6,'H','TRANSPORT ET ENTREPOSAGE',8,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(7,'I','HEBERGEMENT ET RESTAURATION',9,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(8,'J','INFORMATION ET COMMUNICATION',10,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(9,'K','ACTIVITES FINANCIERES ET D\'ASSURANCE',11,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(10,'L','ACTIVITES IMMOBILIERES',12,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(11,'M','ACTIVITES SPECIALISEES, SCIENTIFIQUES ET TECHNIQUES',13,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(12,'N','ACTIVITES DE SERVICES ADMINISTRATIFS ET DE SOUTIEN',14,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(13,'P','ENSEIGNEMENT',15,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(14,'Q','SANTE HUMAINE ET ACTION SOCIALE',16,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(15,'R','ARTS, SPECTACLES ET ACTIVITES RECREATIVES',17,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(16,'S','AUTRES ACTIVITES DE SERVICES',18,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(17,'46','Commerce de gros',19,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(18,'47','Commerce de detail',20,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(19,'49','Transports terrestres',21,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(20,'56','Restauration',23,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(21,'62','Programmation, conseil et autres activites informatiques',25,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(22,'68','Activites immobilieres',26,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(23,'69','Activites juridiques et comptables',27,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(24,'70','Activites des sieges sociaux; conseil de gestion',28,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(25,'85','Enseignement',33,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(26,'86','Activites pour la sante humaine',34,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(27,'96','Autres services personnels',36,'2026-05-15 17:52:45','2026-05-15 19:20:33'),(28,'D','PRODUCTION ET DISTRIBUTION D\'ELECTRICITE, DE GAZ, DE VAPEUR ET D\'AIR CONDITIONNE',4,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(29,'E','PRODUCTION ET DISTRIBUTION D\'EAU; ASSAINISSEMENT, GESTION DES DECHETS ET DEPOLLUTION',5,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(30,'55','Hebergement',22,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(31,'58','Edition',24,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(32,'71','Activites d\'architecture et d\'ingenierie',29,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(33,'73','Publicite et etudes de marche',30,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(34,'77','Activites de location et location-bail',31,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(35,'79','Agences de voyage',32,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(36,'93','Activites sportives, recreatives et de loisirs',35,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(37,'4711','Commerce de detail alimentaire',37,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(38,'6201','Programmation informatique',38,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(39,'6202','Conseil informatique',39,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(40,'6910','Activites juridiques',40,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(41,'6920','Activites comptables',41,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(42,'7010','Activites des sieges sociaux',42,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(43,'7022','Conseil pour les affaires et autres conseils de gestion',43,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(44,'7111','Activites d\'architecture',44,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(45,'7112','Activites d\'ingenierie',45,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(46,'7311','Activites des agences de publicite',46,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(47,'8299','Autres activites de soutien aux entreprises',47,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(48,'9602','Coiffure et soins de beaute',48,'2026-05-15 19:18:58','2026-05-15 19:20:33'),(49,'9609','Autres services personnels',49,'2026-05-15 19:18:58','2026-05-15 19:20:33');
/*!40000 ALTER TABLE `ref_activites_ompic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_fonctions`
--

DROP TABLE IF EXISTS `ref_fonctions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_fonctions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fonction` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_fonctions` (`fonction`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_fonctions`
--

LOCK TABLES `ref_fonctions` WRITE;
/*!40000 ALTER TABLE `ref_fonctions` DISABLE KEYS */;
INSERT INTO `ref_fonctions` VALUES (4,'Gérant',1,'2026-06-02 18:24:33','2026-06-02 18:24:33'),(5,'Associé',2,'2026-06-02 18:24:36','2026-06-02 18:24:36'),(9,'Gestion administrative',1,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(10,'Support operationnel',2,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(11,'Agent de traitement',3,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(12,'Chef d\'équipe',4,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(13,'Superviseur',5,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(14,'Comptable',6,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(15,'Assistant juridique',7,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(16,'Responsable clientèle',8,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(17,'Coursier',9,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(18,'Autre',99,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `ref_fonctions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_formes_juridiques`
--

DROP TABLE IF EXISTS `ref_formes_juridiques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_formes_juridiques` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forme_juridique` varchar(120) NOT NULL,
  `template_folder` varchar(120) NOT NULL DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_formes_juridiques` (`forme_juridique`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_formes_juridiques`
--

LOCK TABLES `ref_formes_juridiques` WRITE;
/*!40000 ALTER TABLE `ref_formes_juridiques` DISABLE KEYS */;
INSERT INTO `ref_formes_juridiques` VALUES (1,'SARL AU','SARL AU',10,'2026-05-13 11:21:45','2026-05-31 19:08:07'),(2,'SARL','SARL',20,'2026-05-13 11:21:45','2026-05-31 19:08:07'),(3,'Personne Physique','PP',30,'2026-05-13 11:21:45','2026-05-31 19:08:32'),(4,'SA','SA',60,'2026-05-13 11:21:45','2026-05-31 19:08:07'),(5,'Succurssale Etrangère','',40,'2026-05-13 11:21:45','2026-05-13 17:39:14'),(6,'Succurssale Marocaine','',50,'2026-05-13 11:21:45','2026-05-13 17:39:15');
/*!40000 ALTER TABLE `ref_formes_juridiques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_lieux_naissance`
--

DROP TABLE IF EXISTS `ref_lieux_naissance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_lieux_naissance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lieu_naissance` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_lieux_naissance` (`lieu_naissance`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_lieux_naissance`
--

LOCK TABLES `ref_lieux_naissance` WRITE;
/*!40000 ALTER TABLE `ref_lieux_naissance` DISABLE KEYS */;
INSERT INTO `ref_lieux_naissance` VALUES (1,'Casablanca',1,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(2,'Rabat',2,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(3,'Mohammedia',3,'2026-05-13 11:21:45','2026-05-13 11:21:45');
/*!40000 ALTER TABLE `ref_lieux_naissance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_nationalites`
--

DROP TABLE IF EXISTS `ref_nationalites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_nationalites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nationalite` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_nationalites` (`nationalite`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_nationalites`
--

LOCK TABLES `ref_nationalites` WRITE;
/*!40000 ALTER TABLE `ref_nationalites` DISABLE KEYS */;
INSERT INTO `ref_nationalites` VALUES (1,'Marocaine',1,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(2,'Cameronnie',2,'2026-05-13 11:21:45','2026-05-13 11:21:45');
/*!40000 ALTER TABLE `ref_nationalites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_qualites_associe`
--

DROP TABLE IF EXISTS `ref_qualites_associe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_qualites_associe` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qualite_associe` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_qualites_associe` (`qualite_associe`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_qualites_associe`
--

LOCK TABLES `ref_qualites_associe` WRITE;
/*!40000 ALTER TABLE `ref_qualites_associe` DISABLE KEYS */;
INSERT INTO `ref_qualites_associe` VALUES (1,'Gerant',1,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(2,'Associe unique',2,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(3,'Associe majoritaire',3,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(4,'Associe minoritaire',4,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(5,'President',5,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(6,'Directeur General',6,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(7,'Actionnaire',7,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(8,'Porteur de parts',8,'2026-05-13 11:21:45','2026-05-13 11:21:45');
/*!40000 ALTER TABLE `ref_qualites_associe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_ste_adresses`
--

DROP TABLE IF EXISTS `ref_ste_adresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_ste_adresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ste_adresse` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_ste_adresses` (`ste_adresse`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_ste_adresses`
--

LOCK TABLES `ref_ste_adresses` WRITE;
/*!40000 ALTER TABLE `ref_ste_adresses` DISABLE KEYS */;
INSERT INTO `ref_ste_adresses` VALUES (1,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA',1,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(2,'46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA',2,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(3,'56 BOULEVARD MOULAY YOUSSEF 3EME ETAGE APPT 14, CASABLANCA',3,'2026-05-13 11:21:45','2026-05-13 11:21:45'),(4,'123 Boulevard Hassan II',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(5,'45 Avenue Mohammed V',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(6,'12 Rue Dar El Baraka',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(7,'78 Avenue des FAR',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(8,'34 Rue Ghandouri',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(9,'56 Boulevard de la Corniche',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(10,'89 Place de la Concordance',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(11,'11 Rue Ibn Sina',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(12,'25 Avenue de Marrakech',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(13,'67 Boulevard de Paris',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(14,'43 Route de Meknes',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(15,'55 Boulevard Allal El Fassi',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(16,'88 Rue Ahmed Chaouki',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(17,'22 Avenue Hassan II (Downtown)',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(18,'99 Boulevard Moulay Ismail',0,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `ref_ste_adresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_tribunaux`
--

DROP TABLE IF EXISTS `ref_tribunaux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_tribunaux` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tribunal` varchar(120) NOT NULL,
  `tribunal_type` varchar(60) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_tribunaux` (`tribunal`,`tribunal_type`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_tribunaux`
--

LOCK TABLES `ref_tribunaux` WRITE;
/*!40000 ALTER TABLE `ref_tribunaux` DISABLE KEYS */;
INSERT INTO `ref_tribunaux` VALUES (4,'Casablanca','Tribunal de commerce',4,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(5,'Rabat','Tribunal de commerce',5,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(6,'Fes','Tribunal de commerce',6,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(7,'Marrakech','Tribunal de commerce',7,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(8,'Tangier','Tribunal de commerce',8,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(9,'Agadir','Tribunal de commerce',9,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(10,'Meknes','Tribunal de commerce',10,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(11,'Oujda','Tribunal de commerce',11,'2026-05-13 11:21:45','2026-05-15 18:30:13'),(12,'Casablanca','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(13,'Rabat','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(14,'Marrakech','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(15,'Fes','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(16,'Agadir','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(17,'Tangier','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(18,'Meknes','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(19,'Tetouan','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(20,'Oujda','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(21,'Beni Mellal','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(22,'Khouribga','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(23,'Oulad Teima','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(24,'Settat','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(25,'Khemisset','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(26,'Tiflet','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(27,'Skhirat-Temara','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(28,'Sidi Kacem','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(29,'Sidi Slimane','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(30,'Souk El Arbaa','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(31,'Taourirt','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(32,'Berrechid','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(33,'Mohammedia','Tribunal de Première Instance',0,'2026-05-15 18:26:58','2026-05-15 18:26:58'),(66,'Berrechid','Tribunal de commerce',0,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(67,'Mohammedia','Tribunal de commerce',0,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `ref_tribunaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ref_villes`
--

DROP TABLE IF EXISTS `ref_villes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_villes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ville` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_villes` (`ville`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ref_villes`
--

LOCK TABLES `ref_villes` WRITE;
/*!40000 ALTER TABLE `ref_villes` DISABLE KEYS */;
INSERT INTO `ref_villes` VALUES (1,'Agadir',20,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(2,'Ait Melloul',30,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(3,'Al Hoceima',40,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(4,'Asilah',50,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(5,'Azemmour',60,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(6,'Azrou',70,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(7,'Beni Mellal',90,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(8,'Beni Ansar',80,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(9,'Berrechid',110,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(10,'Berkane',100,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(11,'Boujdour',120,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(12,'Boulemane',130,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(13,'Casablanca',10,'2026-05-13 11:21:45','2026-05-13 11:27:03'),(14,'Chefchaouen',140,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(15,'Chichaoua',150,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(16,'Dakhla',160,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(17,'El Hajeb',170,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(18,'El Jadida',180,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(19,'El Kelaa Des Sraghna',190,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(20,'Errachidia',200,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(21,'Essaouira',210,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(22,'Fes',220,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(23,'Figuig',230,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(24,'Fnideq',240,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(25,'Guelmim',250,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(26,'Guercif',260,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(27,'Ifrane',270,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(28,'Inezgane',280,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(29,'Jerada',290,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(30,'Kelaat Mgouna',300,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(31,'Khemisset',310,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(32,'Khenifra',320,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(33,'Khouribga',330,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(34,'Ksar El Kebir',340,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(35,'Laayoune',350,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(36,'Larache',360,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(37,'Marrakech',370,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(38,'Martil',380,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(39,'Meknes',390,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(40,'Midelt',400,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(41,'Mohammedia',410,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(42,'Nador',420,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(43,'Ouarzazate',430,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(44,'Ouezzane',440,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(45,'Oujda',450,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(46,'Oulad Teima',460,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(47,'Rabat',470,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(48,'Safi',480,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(49,'Sale',490,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(50,'Sefrou',500,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(51,'Settat',510,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(52,'Sidi Bennour',520,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(53,'Sidi Ifni',530,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(54,'Sidi Kacem',540,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(55,'Sidi Slimane',550,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(56,'Skhirat',560,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(57,'Souk El Arbaa',570,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(58,'Tanger',590,'2026-05-13 11:21:45','2026-05-13 11:26:56'),(59,'Tan-Tan',580,'2026-05-13 11:21:45','2026-05-13 11:26:56'),(60,'Taourirt',600,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(61,'Taroudant',610,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(62,'Tata',620,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(63,'Taza',630,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(64,'Temara',640,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(65,'Tetouan',650,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(66,'Tiflet',660,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(67,'Tinghir',670,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(68,'Tiznit',680,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(69,'Youssoufia',690,'2026-05-13 11:21:45','2026-05-13 11:25:01'),(70,'Zagora',700,'2026-05-13 11:21:45','2026-05-13 11:25:01');
/*!40000 ALTER TABLE `ref_villes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(1,33),(1,34),(1,35),(1,36),(1,37),(1,38),(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13),(2,14),(2,15),(2,16),(2,17),(2,18),(2,19),(2,20),(2,21),(2,22),(2,23),(2,24),(2,25),(2,26),(2,27),(2,28),(2,29),(2,30),(2,31),(2,32),(2,33),(2,34),(2,35),(2,36),(2,37),(3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(3,7),(3,8),(3,9),(3,10),(3,11),(3,12),(3,13),(3,14),(3,15),(3,16),(3,17),(3,22),(3,23),(3,27),(3,28),(3,29),(3,32),(3,33),(4,1),(4,2),(4,3),(4,4),(4,7),(4,8),(4,9),(4,12),(4,13),(4,14),(4,22),(4,27),(4,28),(4,29),(5,1),(5,2),(5,3),(5,7),(5,8),(5,12),(5,13),(5,22),(5,23),(5,27),(5,28),(5,29),(6,1),(6,2),(6,7),(6,12),(6,23),(6,28),(7,1),(7,2),(7,7),(7,12),(7,28),(7,29),(8,1),(8,2),(8,7),(8,12),(8,28),(8,29),(9,1),(9,2),(9,7),(9,12),(9,28),(9,29),(10,1),(11,1),(11,2),(11,12),(11,28),(11,29),(12,1),(12,2),(12,12),(12,28),(12,29),(13,1),(13,2),(13,12),(13,28),(13,29),(14,1),(15,1),(16,1);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','Accès total au système',1,1,1,'2026-06-02 12:02:28'),(2,'Admin','Administrateur avec presque tous les droits',1,0,2,'2026-06-02 12:02:28'),(3,'Chef d équipes','Gère les dossiers et son équipe',1,0,3,'2026-06-02 12:02:28'),(4,'Employé','Agent de traitement des dossiers',1,0,4,'2026-06-02 12:02:28'),(5,'Assistante','Support administratif et documentaire',1,0,5,'2026-06-02 12:02:28'),(6,'Stagiaire','Accès lecture seule',1,0,6,'2026-06-02 12:02:28'),(7,'Expert-comptable','Expert-comptable externe',0,0,10,'2026-06-02 12:02:28'),(8,'Comptable agréé','Comptable agréé externe',0,0,11,'2026-06-02 12:02:28'),(9,'Commissaire aux comptes','Commissaire aux comptes',0,0,12,'2026-06-02 12:02:28'),(10,'Coursier','Coursier / livreur',0,0,13,'2026-06-02 12:02:28'),(11,'Avocat','Avocat externe',0,0,14,'2026-06-02 12:02:28'),(12,'Notaire','Notaire externe',0,0,15,'2026-06-02 12:02:28'),(13,'Conseil juridique','Conseil juridique externe',0,0,16,'2026-06-02 12:02:28'),(14,'Banque','Représentant bancaire',0,0,17,'2026-06-02 12:02:28'),(15,'Assurance','Représentant assurance',0,0,18,'2026-06-02 12:02:28'),(16,'Autre','Autre type de collaborateur',0,0,99,'2026-06-02 12:02:28');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `societes`
--

DROP TABLE IF EXISTS `societes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `societes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `societe_dossier` varchar(120) DEFAULT NULL,
  `societe_raison_sociale` varchar(255) NOT NULL,
  `den_ste` varchar(255) DEFAULT NULL,
  `societe_forme_juridique` varchar(120) DEFAULT NULL,
  `societe_ice` varchar(100) DEFAULT NULL,
  `societe_date_ice` date DEFAULT NULL,
  `societe_rc` varchar(100) DEFAULT NULL,
  `societe_if` varchar(100) DEFAULT NULL,
  `societe_activites_statuts` text DEFAULT NULL,
  `societe_capital` decimal(15,2) DEFAULT NULL,
  `societe_activites_ompic` text DEFAULT NULL,
  `societe_part_social` int(11) DEFAULT NULL,
  `societe_valeur_nominale` decimal(15,2) DEFAULT NULL,
  `societe_date_exp_cert_neg` date DEFAULT NULL,
  `societe_adresse` text DEFAULT NULL,
  `societe_adresse_siege` text DEFAULT NULL,
  `societe_ville` varchar(120) DEFAULT NULL,
  `societe_tribunal` varchar(120) DEFAULT NULL,
  `societe_tribunal_type` varchar(60) DEFAULT NULL,
  `societe_email` varchar(190) DEFAULT NULL,
  `societe_telephone` varchar(60) DEFAULT NULL,
  `societe_type_generation` varchar(120) DEFAULT NULL,
  `societe_procedure_creation` varchar(120) DEFAULT NULL,
  `societe_mode_depot` varchar(120) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_societes_ice` (`societe_ice`),
  KEY `idx_societes_ville` (`societe_ville`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `societes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `collaborateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `societes`
--

LOCK TABLES `societes` WRITE;
/*!40000 ALTER TABLE `societes` DISABLE KEYS */;
INSERT INTO `societes` VALUES (1,'DOM-2026-001','Atlas Domiciliation','Atlas Domiciliation','SARL','001122334455667','2026-05-18','RC12345','IF778899',NULL,100000.00,NULL,100,1000.00,'2026-05-18','123 Boulevard Hassan II','123 Boulevard Hassan II','Casablanca','Casablanca',NULL,'contact@atlas.test','+212600000001','Standard','Creation','Electronique',NULL,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(2,'DOM-2026-002','Maghreb Services','Maghreb Services','SARL AU','998877665544332','2026-05-18','RC54321','IF665544',NULL,50000.00,NULL,100,500.00,'2026-05-18','45 Avenue Mohammed V','45 Avenue Mohammed V','Rabat','Casablanca',NULL,'admin@maghreb.test','+212600000002','Standard','Creation','Physique',NULL,'2026-05-11 09:26:26','2026-05-18 19:08:10'),(9,'DOM-2026-003','MOUAIOA',NULL,'SARL AU','123456789000012','2026-05-18','123456','12345678','',100000.00,'79',1000,100.00,'2026-05-18',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca',NULL,'contact@test-sarl.ma','0522123456','domiciliation','','',NULL,'2026-05-16 15:31:01','2026-05-18 19:08:10'),(12,'DOM-2026-006','DOURIYA',NULL,'SARL AU','123456789000012','2025-01-15','123456','12345678','',100000.00,'',1000,100.00,'2027-01-15',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca',NULL,'contact@test-sarl.ma','0522123456','creation','normal','depot_physique',NULL,'2026-05-18 22:09:50','2026-05-18 22:09:50'),(13,'DOM-2026-007','TRABAKHANDO',NULL,'SARL AU','123456789000012','2025-01-15','123456','12345678','',100000.00,'7022',1000,100.00,'2027-01-15',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','contact@test-sarl.ma','0522123456','domiciliation','','',NULL,'2026-05-31 18:31:07','2026-05-31 18:31:07'),(14,'DOM-2026-008','TEST SARL AU',NULL,'SARL AU','123456789000012','2025-01-15','123456','12345678','Communication Digital',100000.00,'56',1000,100.00,'2027-01-15',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Rabat','Tribunal de commerce','contact@test-sarl.ma','0522123456','creation','normal','depot_physique',NULL,'2026-05-31 20:26:52','2026-05-31 20:26:52'),(15,'DOM-2026-009','SEBTIYA',NULL,'SARL AU','123456789000012','2025-01-15','123456','12345678','',100000.00,'7112',1000,100.00,'2027-01-15',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','contact@test-sarl.ma','0522123456','domiciliation','','',NULL,'2026-06-01 18:32:20','2026-06-01 18:32:20'),(16,'DOM-2026-010','AMAR STE',NULL,'SARL AU','123456789000012','2025-01-15','123456','12345678','',100000.00,'9602',1000,100.00,'2027-01-15',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','contact@test-sarl.ma','0522123456','domiciliation','','',NULL,'2026-06-01 18:38:35','2026-06-01 18:38:35'),(17,'DOM-2026-011','GITREIO',NULL,'SARL AU','123456789000012','2025-01-15','123456','12345678','Négociant',100000.00,'4711',1000,100.00,'2027-01-15',NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','contact@test-sarl.ma','0522123456','creation','normal','depot_physique',NULL,'2026-06-02 14:12:30','2026-06-02 14:12:30'),(18,'DOM-2026-012','Test Wizard SARL AU',NULL,'SARL AU','',NULL,'','','',100000.00,'',1000,100.00,NULL,NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','test@wizard.com','0612345678','','','',3,'2026-06-07 12:04:27','2026-06-07 12:04:27'),(19,'DOM-2026-001','Atlas Domiciliation',NULL,'SARL','001122334455667','2026-01-10','RC12345','IF778899',NULL,100000.00,NULL,100,1000.00,'2026-12-31','123 Boulevard Hassan II','123 Boulevard Hassan II','Casablanca','Casablanca',NULL,'contact@atlas.test','+212600000001','Standard','Creation','Electronique',NULL,'2026-06-08 16:05:40','2026-06-08 16:05:40'),(20,'DOM-2026-002','Maghreb Services',NULL,'SARL AU','998877665544332','2026-03-15','RC54321','IF665544',NULL,50000.00,NULL,100,500.00,'2027-03-14','45 Avenue Mohammed V','45 Avenue Mohammed V','Rabat','Casablanca',NULL,'admin@maghreb.test','+212600000002','Standard','Creation','Physique',NULL,'2026-06-08 16:05:40','2026-06-08 16:05:40');
/*!40000 ALTER TABLE `societes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploaded_docs`
--

DROP TABLE IF EXISTS `uploaded_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploaded_docs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `societe_id` int(10) unsigned NOT NULL,
  `doc_type` varchar(50) NOT NULL COMMENT 'certificat_negatif or cin_gerant',
  `associe_idx` int(10) unsigned DEFAULT NULL COMMENT 'Index in associes array for cin_gerant',
  `filename_original` varchar(255) NOT NULL,
  `filename_stored` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `taille_ko` decimal(10,1) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uploaded_docs_societe_id` (`societe_id`),
  KEY `idx_uploaded_docs_type` (`doc_type`),
  CONSTRAINT `fk_uploaded_docs_societe` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploaded_docs`
--

LOCK TABLES `uploaded_docs` WRITE;
/*!40000 ALTER TABLE `uploaded_docs` DISABLE KEYS */;
INSERT INTO `uploaded_docs` VALUES (1,15,'certificat_negatif',NULL,'2026-01-09_CN_TACHA CLEAN.pdf','certificat_negatif_20260601_203213.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../uploads/dossiers/15/certificat_negatif_20260601_203213.pdf',54.6,'2026-06-01 18:32:20'),(2,15,'cin_gerant',0,'2026-01-05_CIN N&B Youssef EL BETIOUI Gérant Légalisé.pdf','cin_gerant_0_20260601_203213.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../uploads/dossiers/15/cin_gerant_0_20260601_203213.pdf',5327.4,'2026-06-01 18:32:20'),(3,16,'certificat_negatif',NULL,'2026-06-01_CN_AMAR_STE.pdf','2026-06-01_CN_AMAR_STE.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../uploads/dossiers/16/2026-06-01_CN_AMAR_STE.pdf',54.6,'2026-06-01 18:38:35'),(4,16,'cin_gerant',0,'2026-06-01_CIN_BENANI_Ahmed_AMAR_STE.pdf','2026-06-01_CIN_BENANI_Ahmed_AMAR_STE.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../uploads/dossiers/16/2026-06-01_CIN_BENANI_Ahmed_AMAR_STE.pdf',5327.4,'2026-06-01 18:38:35'),(5,17,'certificat_negatif',NULL,'2026-06-02_CN_GITREIO.pdf','2026-06-02_CN_GITREIO.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../uploads/dossiers/17/2026-06-02_CN_GITREIO.pdf',54.6,'2026-06-02 14:12:30'),(6,17,'cin_gerant',0,'2026-06-02_CIN_BENANI_Ahmed_GITREIO.pdf','2026-06-02_CIN_BENANI_Ahmed_GITREIO.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../uploads/dossiers/17/2026-06-02_CIN_BENANI_Ahmed_GITREIO.pdf',5327.4,'2026-06-02 14:12:30'),(7,18,'certificat_negatif',NULL,'2026-06-07_CN_Test_Wizard_SARL_AU.pdf','2026-06-07_CN_Test_Wizard_SARL_AU.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU/_uploads/2026-06-07_CN_Test_Wizard_SARL_AU.pdf',0.0,'2026-06-07 12:04:27'),(8,18,'cin_gerant',0,'2026-06-07_CIN_Test_User_Test_Wizard_SARL_AU.pdf','2026-06-07_CIN_Test_User_Test_Wizard_SARL_AU.pdf','E:\\Dev_Project\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-Wizard-SARL-AU/_uploads/2026-06-07_CIN_Test_User_Test_Wizard_SARL_AU.pdf',0.0,'2026-06-07 12:04:27');
/*!40000 ALTER TABLE `uploaded_docs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-08 17:15:08
