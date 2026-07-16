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
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `_migrations`
--

LOCK TABLES `_migrations` WRITE;
/*!40000 ALTER TABLE `_migrations` DISABLE KEYS */;
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (1,'20260401_000001_add_tribunal_type.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (2,'20260401_000002_rename_columns.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (3,'20260401_000003_rbac.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (4,'20260401_000004_template_folder.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (5,'20260608_000005_add_ref_fonctions.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (6,'20260609_000006_create_notifications.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (7,'20260612_000001_cession_parts.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (8,'20260612_000002_cession_permissions.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (9,'20260612_000003_cession_pourcentage_gerant.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (10,'20260612_000004_societe_source.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (11,'20260613_000001_add_societe_tp.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (12,'20260613_000003_cessionnaire_fields.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (13,'20260615_000001_add_societe_cnss.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (14,'20260615_000002_add_cession_id.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (15,'20260621_000001_create_user_sessions.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (16,'20260621_000002_import_permissions.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (17,'20260626_000001_create_pv_ago.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (18,'20260626_000002_fix_pv_ago_exercice_clos_length.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (19,'20260626_000003_add_adresse_ville.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (20,'20260628_000001_cession_status_valider.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (21,'20260628_000002_pv_ago_status_valider.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (22,'20260628_000003_cession_suivi.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (23,'20260628_000004_cession_suivi_permission.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (24,'20260628_000005_pv_resolutions_templates.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (25,'20260628_000006_pv_resolutions_column.sql','2026-07-14 22:22:02');
INSERT INTO `_migrations` (`id`, `filename`, `applied_at`) VALUES (26,'20260714_225720_add_duree_gerance_to_associes.sql','2026-07-14 22:57:36');
/*!40000 ALTER TABLE `_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (1,3,'Super Admin','update','collaborateur',2,'Karim Tazi',NULL,'::1','2026-06-06 19:23:03');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (2,3,'Super Admin','update','collaborateur',2,'Karim Tazi',NULL,'::1','2026-06-06 19:23:42');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (3,3,'Super Admin','update','permissions',2,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:23:42');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (4,3,'Super Admin','update','collaborateur',2,'Karim Tazi',NULL,'::1','2026-06-06 19:24:57');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (5,3,'Super Admin','update','permissions',2,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:24:57');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (6,3,'Super Admin','update','collaborateur',1,'Atlas Domiciliation',NULL,'::1','2026-06-06 19:30:33');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (7,3,'Super Admin','update','permissions',1,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:30:33');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (8,3,'Super Admin','create','collaborateur',4,'Test Collaborateur',NULL,'::1','2026-06-06 19:35:04');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (9,3,'Super Admin','update','permissions',4,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:35:04');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (10,3,'Super Admin','update','collaborateur',4,'Test Collaborateur',NULL,'::1','2026-06-06 19:37:02');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (11,3,'Super Admin','update','permissions',4,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:37:02');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (12,3,'Super Admin','update','collaborateur',4,'Test Collaborateur',NULL,'::1','2026-06-06 19:39:21');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (13,3,'Super Admin','update','permissions',4,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:39:21');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (14,3,'Super Admin','update','collaborateur',4,'Test Collaborateur',NULL,'::1','2026-06-06 19:39:37');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (15,3,'Super Admin','update','permissions',4,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 19:39:37');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (16,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-06-06 19:40:14');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (17,4,'Test Collaborateur','connexion','auth',4,'Test Collaborateur',NULL,'::1','2026-06-06 19:40:24');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (18,4,'Test Collaborateur','upload','document',NULL,'Uploads ├®tape 5 ÔÇö 2 fichier(s)','{\"certificat_negatif\":null,\"cin_gerants\":null}','::1','2026-06-06 19:47:44');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (19,4,'Test Collaborateur','create','dossier',11,'Test SARL AU','{\"forme_juridique\":\"SARL AU\",\"nb_associes\":1,\"type_generation\":\"creation\"}','::1','2026-06-06 19:48:22');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (20,4,'Test Collaborateur','validate','document',11,'Test SARL AU ÔÇö 4 doc(s)','{\"doc_ids\":[4,1,2,3]}','::1','2026-06-06 19:49:05');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (21,4,'Test Collaborateur','deconnexion','auth',4,'Test Collaborateur',NULL,'::1','2026-06-06 20:02:24');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (22,3,'Super Admin','connexion','auth',3,'Super Admin',NULL,'::1','2026-06-06 20:11:38');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (23,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-06-06 20:11:38');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (24,3,'Super Admin','connexion','auth',3,'Super Admin',NULL,'::1','2026-06-06 20:11:58');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (25,3,'Super Admin','update','collaborateur',2,'Karim Tazi',NULL,'::1','2026-06-06 20:13:30');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (26,3,'Super Admin','update','permissions',2,'Permissions mises ├á jour ÔÇö 38 override(s)',NULL,'::1','2026-06-06 20:13:30');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (27,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-06-06 20:15:01');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (28,2,'Karim Tazi','connexion','auth',2,'Karim Tazi',NULL,'::1','2026-06-06 20:16:13');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (29,2,'Karim Tazi','upload','document',NULL,'Uploads ├®tape 5 ÔÇö 2 fichier(s)','{\"certificat_negatif\":null,\"cin_gerants\":null}','::1','2026-06-06 20:18:29');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (30,2,'Karim Tazi','create','dossier',12,'Test PDF Karim','{\"forme_juridique\":\"SARL AU\",\"nb_associes\":1,\"type_generation\":\"domiciliation\"}','::1','2026-06-06 20:18:37');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (31,2,'Karim Tazi','validate','document',12,'Test PDF Karim ÔÇö 6 doc(s)','{\"doc_ids\":[10,9,7,8,5,6]}','::1','2026-06-06 20:37:34');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (32,2,'Karim Tazi','generate','document',12,'G├®n├®ration ÔÇö 2 doc(s)','{\"doc_types\":[\"\",\"\"]}','::1','2026-06-06 20:43:58');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (33,2,'Karim Tazi','validate','document_genere',12,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-06-06 20:44:55');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (34,2,'Karim Tazi','validate','document_genere',12,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-06-06 20:45:01');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (35,2,'Karim Tazi','delete','document_genere',12,'Suppression ÔÇö 8 doc(s)',NULL,'::1','2026-06-06 20:53:49');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (36,2,'Karim Tazi','generate','document',12,'G├®n├®ration ÔÇö 2 doc(s)','{\"doc_types\":[\"\",\"\"]}','::1','2026-06-06 20:54:23');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (37,2,'Karim Tazi','validate','document_genere',12,'Validation ÔÇö 2 doc(s)',NULL,'::1','2026-06-06 20:54:32');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (38,2,'Karim Tazi','validate','document',NULL,'2 doc(s)',NULL,'::1','2026-06-06 20:58:28');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (39,2,'Karim Tazi','delete','document_genere',12,'Suppression ÔÇö 2 doc(s)',NULL,'::1','2026-06-06 21:05:59');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (40,2,'Karim Tazi','generate','document',12,'G├®n├®ration ÔÇö 2 doc(s)','{\"doc_types\":[\"\",\"\"]}','::1','2026-06-06 21:06:06');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (41,2,'Karim Tazi','validate','document_genere',12,'Validation ÔÇö 2 doc(s)',NULL,'::1','2026-06-06 21:06:13');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (42,2,'Karim Tazi','delete','document_genere',12,'Suppression ÔÇö 2 doc(s)',NULL,'::1','2026-06-06 21:06:41');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (43,2,'Karim Tazi','generate','document',12,'G├®n├®ration ÔÇö 2 doc(s)','{\"doc_types\":[\"\",\"\"]}','::1','2026-06-06 21:06:44');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (44,2,'Karim Tazi','validate','document_genere',12,'Validation ÔÇö 2 doc(s)',NULL,'::1','2026-06-06 21:07:43');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (45,2,'Karim Tazi','delete','document_genere',12,'Suppression ÔÇö 2 doc(s)',NULL,'::1','2026-06-06 21:07:47');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (46,2,'Karim Tazi','generate','document',12,'G├®n├®ration ÔÇö 2 doc(s)','{\"doc_types\":[\"\",\"\"]}','::1','2026-06-06 21:09:05');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (47,2,'Karim Tazi','validate','document_genere',12,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-06-06 21:09:16');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (48,2,'Karim Tazi','validate','document',NULL,'1 doc(s)',NULL,'::1','2026-06-06 21:14:51');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (49,2,'Karim Tazi','upload','document',NULL,'Uploads ├®tape 5 ÔÇö 2 fichier(s)','{\"certificat_negatif\":null,\"cin_gerants\":null}','::1','2026-06-06 21:29:49');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (50,2,'Karim Tazi','create','dossier',13,'PHP OUTPUT SARL','{\"forme_juridique\":\"SARL AU\",\"nb_associes\":1,\"type_generation\":\"domiciliation\"}','::1','2026-06-06 21:29:56');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (51,2,'Karim Tazi','validate','document_genere',13,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-06-06 21:31:42');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (52,2,'Karim Tazi','validate','document_genere',13,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-06-06 21:36:18');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (53,3,'Super Admin','view','page',NULL,'dashboard',NULL,'::1','2026-07-14 21:22:02');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (54,3,'Super Admin','view','page',NULL,'templates',NULL,'::1','2026-07-14 21:22:21');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (55,2,'Karim Tazi','view','page',NULL,'templates',NULL,'::1','2026-07-14 21:26:53');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (56,3,'Super Admin','connexion','auth',3,'Super Admin (auto)',NULL,'::1','2026-07-14 21:28:11');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (57,3,'Super Admin','view','page',NULL,'dashboard',NULL,'::1','2026-07-14 21:28:11');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (58,3,'Super Admin','view','page',NULL,'templates',NULL,'::1','2026-07-14 21:28:19');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (59,3,'Super Admin','view','page',NULL,'analyse-couverture',NULL,'::1','2026-07-14 21:41:12');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (60,3,'Super Admin','view','page',NULL,'defaults',NULL,'::1','2026-07-14 21:41:23');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (61,3,'Super Admin','view','page',NULL,'variables',NULL,'::1','2026-07-14 21:41:25');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (62,3,'Super Admin','view','page',NULL,'creation',NULL,'::1','2026-07-14 21:46:17');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (63,3,'Super Admin','upload','document',NULL,'Uploads ├®tape 5 ÔÇö 2 fichier(s)','{\"certificat_negatif\":null,\"cin_gerants\":null}','::1','2026-07-14 22:27:24');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (64,3,'Super Admin','create','dossier',14,'KAMARAD','{\"forme_juridique\":\"SARL\",\"nb_associes\":2,\"type_generation\":\"creation\"}','::1','2026-07-14 22:27:35');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (65,3,'Super Admin','view','page',NULL,'societe',NULL,'::1','2026-07-14 22:28:48');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (66,3,'Super Admin','validate','document',14,'KAMARAD ÔÇö 1 doc(s)','{\"doc_ids\":[23]}','::1','2026-07-14 22:30:52');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (67,3,'Super Admin','create','dossier',15,'Dossier #15','{\"forme_juridique\":\"SARL AU\",\"nb_associes\":1,\"type_generation\":\"\"}','::1','2026-07-14 22:37:23');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (68,3,'Super Admin','validate','document',15,' ÔÇö 3 doc(s)','{\"doc_ids\":[25,26,24]}','::1','2026-07-14 22:39:50');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (69,3,'Super Admin','view','page',NULL,'notifications',NULL,'::1','2026-07-14 22:40:40');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (70,3,'Super Admin','view','page',NULL,'societes',NULL,'::1','2026-07-14 22:40:53');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (71,3,'Super Admin','delete','document',14,'KAMARAD ÔÇö 1 doc(s)','{\"doc_ids\":[23]}','::1','2026-07-14 22:41:02');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (72,3,'Super Admin','view','page',NULL,'generation',NULL,'::1','2026-07-14 22:41:03');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (73,3,'Super Admin','generate','document',14,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx',NULL,'::1','2026-07-14 22:41:06');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (74,3,'Super Admin','validate','document_genere',14,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 22:41:11');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (75,3,'Super Admin','delete','document_genere',14,'Suppression ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 22:41:57');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (76,3,'Super Admin','generate','document',14,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx',NULL,'::1','2026-07-14 22:43:42');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (77,3,'Super Admin','validate','document_genere',14,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 22:43:46');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (78,3,'Super Admin','delete','document_genere',14,'Suppression ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 22:47:06');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (79,3,'Super Admin','generate','document',14,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx',NULL,'::1','2026-07-14 22:47:10');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (80,3,'Super Admin','validate','document_genere',14,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 22:47:15');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (81,3,'Super Admin','delete','document_genere',14,'Suppression ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:04:03');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (82,3,'Super Admin','generate','document',14,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx',NULL,'::1','2026-07-14 23:04:07');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (83,3,'Super Admin','validate','document_genere',14,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:04:11');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (84,3,'Super Admin','delete','document_genere',14,'Suppression ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:06:59');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (85,3,'Super Admin','generate','document',14,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx',NULL,'::1','2026-07-14 23:07:03');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (86,3,'Super Admin','validate','document_genere',14,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:07:07');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (87,3,'Super Admin','delete','document_genere',14,'Suppression ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:14:49');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (88,3,'Super Admin','generate','document',14,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx',NULL,'::1','2026-07-14 23:14:51');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (89,3,'Super Admin','validate','document_genere',14,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:14:59');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (90,3,'Super Admin','delete','societe',15,NULL,NULL,'::1','2026-07-14 23:15:21');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (91,3,'Super Admin','update','societe',13,'PHP OUTPUT SARL',NULL,'::1','2026-07-14 23:16:58');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (92,3,'Super Admin','delete','document',13,'PHP OUTPUT SARL ÔÇö 2 doc(s)','{\"doc_ids\":[22,21]}','::1','2026-07-14 23:17:12');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (93,3,'Super Admin','generate','document',13,'Generation AJAX ÔÇö SARL_2026-07-15_Statuts_PHP-OUTPUT-SARL_Brouillon.docx',NULL,'::1','2026-07-14 23:17:21');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (94,3,'Super Admin','validate','document_genere',13,'Validation ÔÇö 1 doc(s)',NULL,'::1','2026-07-14 23:17:27');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (95,3,'Super Admin','connexion','auth',3,'Super Admin (auto)',NULL,'::1','2026-07-16 09:46:03');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (96,3,'Super Admin','view','page',NULL,'dashboard',NULL,'::1','2026-07-16 09:46:03');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (97,3,'Super Admin','view','page',NULL,'deconnexion',NULL,'::1','2026-07-16 10:26:35');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (98,3,'Super Admin','deconnexion','auth',3,'Super Admin',NULL,'::1','2026-07-16 10:26:35');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (99,3,'Super Admin','connexion','auth',3,'Super Admin (auto)',NULL,'::1','2026-07-16 15:23:15');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (100,3,'Super Admin','view','page',NULL,'dashboard',NULL,'::1','2026-07-16 15:23:15');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (101,3,'Super Admin','connexion','auth',3,'Super Admin (auto)',NULL,'::1','2026-07-16 15:24:22');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (102,3,'Super Admin','view','page',NULL,'dashboard',NULL,'::1','2026-07-16 15:24:22');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_nom`, `action`, `entity_type`, `entity_id`, `entity_label`, `details`, `ip_address`, `created_at`) VALUES (103,3,'Super Admin','view','page',NULL,'societes',NULL,'::1','2026-07-16 15:24:36');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `associes`
--

LOCK TABLES `associes` WRITE;
/*!40000 ALTER TABLE `associes` DISABLE KEYS */;
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (4,5,NULL,NULL,NULL,'Youssef El Idrissi','BK123456',NULL,'1990-01-01','Casablanca','Marocaine','Casablanca','+212600000101','youssef@atlas.test','Associ├Ü majoritaire',60,NULL,NULL,1,'','2026-05-21 19:53:44','2026-05-21 19:53:44');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (5,5,NULL,NULL,NULL,'Salma Bennani','BE654321',NULL,'1992-04-10','Casablanca','Marocaine','Casablanca','+212600000102','salma@atlas.test','Associ├Ü minoritaire',40,NULL,NULL,0,'','2026-05-21 19:53:44','2026-05-21 19:53:44');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (6,6,NULL,NULL,NULL,'Imane Alaoui','CD987654',NULL,'1988-09-15','Rabat','Marocaine','Rabat','+212600000103','imane@maghreb.test','Associ├Ü unique',100,NULL,NULL,1,'','2026-05-21 19:53:44','2026-05-21 19:53:44');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (7,5,NULL,NULL,NULL,'Youssef El Idrissi','BK123456',NULL,'1990-01-01','Casablanca','Marocaine','Casablanca','+212600000101','youssef@atlas.test','Associ├Ü majoritaire',60,NULL,NULL,1,'','2026-05-21 19:53:52','2026-05-21 19:53:52');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (8,5,NULL,NULL,NULL,'Salma Bennani','BE654321',NULL,'1992-04-10','Casablanca','Marocaine','Casablanca','+212600000102','salma@atlas.test','Associ├Ü minoritaire',40,NULL,NULL,0,'','2026-05-21 19:53:52','2026-05-21 19:53:52');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (9,6,NULL,NULL,NULL,'Imane Alaoui','CD987654',NULL,'1988-09-15','Rabat','Marocaine','Rabat','+212600000103','imane@maghreb.test','Associ├Ü unique',100,NULL,NULL,1,'','2026-05-21 19:53:52','2026-05-21 19:53:52');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (12,8,'M.','Benali','Omar','Omar Benali','BK789012','2027-06-01','1985-03-15','Casablanca','Marocaine','Casablanca','+212600000201','omar@techsolutions.ma','Associe majoritaire',120,120000.00,60.00,1,'','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (13,8,'Mme','Fassi','Nadia','Nadia Fassi','BE345678','2027-06-01','1988-07-22','Rabat','Marocaine','Rabat','+212600000202','nadia@techsolutions.ma','Associe minoritaire',80,80000.00,40.00,0,'','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (14,9,'M.','Moutawakkil','Karim','Karim Moutawakkil','AD901234','2027-05-01','1982-11-30','Marrakech','Marocaine','Marrakech','+212600000301','karim@greenenergy.ma','President directeur general',300,300000.00,60.00,1,'','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (15,9,'M.','El Hassani','Younes','Younes El Hassani','BE567890','2027-05-01','1986-05-18','Fes','Marocaine','Fes','+212600000302','younes@greenenergy.ma','Actionnaire',200,200000.00,40.00,0,'','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (16,10,'Mme','El Alami','Sanaa','Sanaa El Alami','BK112233','2027-07-01','1990-01-10','Casablanca','Marocaine','Casablanca','+212600000401','sanaa@cpi.ma','Gerante',100,100000.00,100.00,1,'','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (17,11,'Mr','Test','User','Mr User Test','CIN-123456',NULL,NULL,'','','123 Rue Test, Casablanca','061111111','associe@test.ma','Gerant',1000,100000.00,100.00,1,'','2026-06-06 19:48:22','2026-06-06 19:48:22');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (18,12,'','','','','',NULL,NULL,'','','','','','Gerant',1000,100000.00,100.00,1,'','2026-06-06 20:18:36','2026-06-06 20:18:36');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (19,13,'Mr','PHP','Output','Mr Output PHP','CIN123456',NULL,NULL,'Casablanca','','Casablanca, Maroc','0600000000','php@output.ma','Gerant',1000,100000.00,100.00,1,'','2026-06-06 21:29:56','2026-06-06 21:29:56');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (20,14,'Mr','BENANI','Ahmed','Mr Ahmed BENANI','AB123456','2028-05-15','1990-05-15','Casablanca','Marocaine','123 Rue Mohammed V, Casablanca','0612345678','ahmed.benani@test.ma','Associ├® G├®rant',500,50000.00,50.00,1,'3 ans','2026-07-14 22:27:35','2026-07-14 22:27:35');
INSERT INTO `associes` (`id`, `societe_id`, `associe_civilite`, `associe_nom`, `associe_prenom`, `associe_nom_complet`, `associe_cin`, `associe_date_validite_cin`, `associe_date_naissance`, `associe_lieu_naissance`, `associe_nationalite`, `associe_adresse`, `associe_telephone`, `associe_email`, `associe_qualite`, `associe_parts`, `associe_capital_detenu`, `associe_part_percent`, `associe_est_gerant`, `associe_duree_gerance`, `created_at`, `updated_at`) VALUES (21,14,'Mr','TAZI','Sara','Mr Sara TAZI','AB123456','2028-05-15','1990-05-15','Casablanca','Marocaine','123 Rue Mohammed V, Casablanca','0612345678','ahmed.benani@test.ma','Associ├®',500,50000.00,50.00,0,'Indeterminee','2026-07-14 22:27:35','2026-07-14 22:27:35');
/*!40000 ALTER TABLE `associes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cession_parts`
--

LOCK TABLES `cession_parts` WRITE;
/*!40000 ALTER TABLE `cession_parts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cession_parts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cession_suivi_documents`
--

LOCK TABLES `cession_suivi_documents` WRITE;
/*!40000 ALTER TABLE `cession_suivi_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `cession_suivi_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cession_suivi_etapes`
--

LOCK TABLES `cession_suivi_etapes` WRITE;
/*!40000 ALTER TABLE `cession_suivi_etapes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cession_suivi_etapes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `cessions`
--

LOCK TABLES `cessions` WRITE;
/*!40000 ALTER TABLE `cessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `cessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `collaborateur_log`
--

LOCK TABLES `collaborateur_log` WRITE;
/*!40000 ALTER TABLE `collaborateur_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaborateur_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `collaborateur_permissions`
--

LOCK TABLES `collaborateur_permissions` WRITE;
/*!40000 ALTER TABLE `collaborateur_permissions` DISABLE KEYS */;
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,1,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,2,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,3,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,4,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,5,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,6,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,7,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,8,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,9,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,10,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,11,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,12,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,13,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,14,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,15,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,16,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,17,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,18,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,19,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,20,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,21,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,22,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,23,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,24,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,25,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,26,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,27,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,28,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,29,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,30,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,31,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,32,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,33,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,34,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,35,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,36,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,37,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (1,38,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,1,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,2,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,3,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,4,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,5,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,6,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,7,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,8,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,9,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,10,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,11,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,12,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,13,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,14,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,15,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,16,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,17,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,18,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,19,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,20,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,21,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,22,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,23,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,24,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,25,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,26,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,27,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,28,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,29,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,30,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,31,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,32,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,33,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,34,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,35,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,36,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,37,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (2,38,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,1,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,2,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,3,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,4,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,5,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,6,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,7,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,8,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,9,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,10,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,11,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,12,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,13,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,14,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,15,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,16,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,17,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,18,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,19,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,20,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,21,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,22,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,23,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,24,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,25,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,26,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,27,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,28,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,29,1);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,30,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,31,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,32,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,33,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,34,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,35,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,36,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,37,0);
INSERT INTO `collaborateur_permissions` (`collaborateur_id`, `permission_id`, `granted`) VALUES (4,38,0);
/*!40000 ALTER TABLE `collaborateur_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `collaborateurs`
--

LOCK TABLES `collaborateurs` WRITE;
/*!40000 ALTER TABLE `collaborateurs` DISABLE KEYS */;
INSERT INTO `collaborateurs` (`id`, `den_ste`, `nom_complet`, `fonction`, `collaborateur_type`, `collaborateur_code`, `collaborateur_nom`, `collaborateur_ice`, `collaborateur_tp`, `collaborateur_rc`, `collaborateur_if`, `collaborateur_tel_fixe`, `collaborateur_tel_mobile`, `collaborateur_adresse`, `collaborateur_email`, `email`, `telephone`, `date_debut`, `statut`, `notes`, `password_hash`, `role_id`, `can_login`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES (1,'Atlas Domiciliation','Atlas Domiciliation','','externe-pm','EXP','','ICE-COL-001','TP001','RC-C001','IF-C001','0522000001','+212600000010','Casablanca','nadia@atlas.test','','',NULL,'actif','Suivi dossiers clients',NULL,8,1,NULL,NULL,'2026-05-21 19:53:03','2026-06-06 19:30:33');
INSERT INTO `collaborateurs` (`id`, `den_ste`, `nom_complet`, `fonction`, `collaborateur_type`, `collaborateur_code`, `collaborateur_nom`, `collaborateur_ice`, `collaborateur_tp`, `collaborateur_rc`, `collaborateur_if`, `collaborateur_tel_fixe`, `collaborateur_tel_mobile`, `collaborateur_adresse`, `collaborateur_email`, `email`, `telephone`, `date_debut`, `statut`, `notes`, `password_hash`, `role_id`, `can_login`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES (2,'','Karim Tazi','','externe-pp','','','','','','','','+212600000011','Casablanca','karim@center.test','karim@center.test','',NULL,'actif','Appui polyvalent','$2y$10$wGFppfOMY0PjrLJmgjPsAOGFL7qO3jYCjkj5an79ggfQ0eNZFplXK',10,1,'2026-06-06 21:16:13',NULL,'2026-05-21 19:53:03','2026-06-06 20:16:13');
INSERT INTO `collaborateurs` (`id`, `den_ste`, `nom_complet`, `fonction`, `collaborateur_type`, `collaborateur_code`, `collaborateur_nom`, `collaborateur_ice`, `collaborateur_tp`, `collaborateur_rc`, `collaborateur_if`, `collaborateur_tel_fixe`, `collaborateur_tel_mobile`, `collaborateur_adresse`, `collaborateur_email`, `email`, `telephone`, `date_debut`, `statut`, `notes`, `password_hash`, `role_id`, `can_login`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES (3,NULL,'Super Admin','Administrateur syst├¿me','interne',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin@center.test','admin@center.test',NULL,NULL,'actif','Compte super admin par defaut ÔÇö changer le mot de passe','$2y$10$QOZo9.7oOayIbJEsGwRxLuuS6BvQ9rJT6oX1rAsQoFG4cAvwyHZBG',1,1,'2026-07-16 16:24:22',NULL,'2026-06-03 14:51:19','2026-07-16 15:24:22');
INSERT INTO `collaborateurs` (`id`, `den_ste`, `nom_complet`, `fonction`, `collaborateur_type`, `collaborateur_code`, `collaborateur_nom`, `collaborateur_ice`, `collaborateur_tp`, `collaborateur_rc`, `collaborateur_if`, `collaborateur_tel_fixe`, `collaborateur_tel_mobile`, `collaborateur_adresse`, `collaborateur_email`, `email`, `telephone`, `date_debut`, `statut`, `notes`, `password_hash`, `role_id`, `can_login`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES (4,'','Test Collaborateur','','interne','','','','','','','','','','test@test.com','test@test.com','',NULL,'actif','','$2y$10$ZXYJ3wYa/gZXYdOrzgvdz.jkqCn3hjbsLcuzO912WV1bBFB2BJfja',4,1,'2026-06-06 20:40:24',NULL,'2026-06-06 19:35:04','2026-06-06 19:40:24');
/*!40000 ALTER TABLE `collaborateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `contrats`
--

LOCK TABLES `contrats` WRITE;
/*!40000 ALTER TABLE `contrats` DISABLE KEYS */;
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (3,5,'Domiciliation commerciale','2026-01-01',12,'Personne Morale',NULL,'2026-01-01','2026-12-31',1200.00,300.00,1200.00,20.00,1000.00,12000.00,1500.00,1250.00,'Annuel',20.00,1000.00,12000.00,1200.00,14400.00,'actif','Contrat annuel standard',NULL,'2026-05-21 19:54:08','2026-05-21 19:54:08');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (4,8,'Domiciliation commerciale','2026-05-15',12,'Personne Morale',NULL,'2026-05-15','2027-05-14',1500.00,350.00,1500.00,20.00,1250.00,15000.00,1800.00,1500.00,'Annuel',20.00,1250.00,15000.00,1500.00,18000.00,'actif',NULL,'Electronique','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (5,9,'Pack premium','2026-04-01',24,'Personne Morale',NULL,'2026-04-01','2028-03-31',2500.00,500.00,2500.00,20.00,2083.33,25000.00,2800.00,2500.00,'Annuel',20.00,2083.33,25000.00,2500.00,30000.00,'actif',NULL,'Physique','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (6,10,'Domiciliation commerciale','2026-06-01',12,'Personne Morale',NULL,'2026-06-01','2027-05-31',1200.00,300.00,1200.00,20.00,1000.00,12000.00,1400.00,1200.00,'Annuel',20.00,1000.00,12000.00,1200.00,14400.00,'actif',NULL,'Electronique','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (7,11,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-06 19:48:22','2026-06-06 19:48:22');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (8,12,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-06 20:18:36','2026-06-06 20:18:36');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (9,13,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-06-06 21:29:56','2026-06-06 21:29:56');
INSERT INTO `contrats` (`id`, `societe_id`, `contrat_type`, `contrat_date`, `contrat_duree_mois`, `contrat_type_domiciliation`, `contrat_type_domiciliation_autre`, `contrat_date_debut`, `contrat_date_fin`, `contrat_loyer_ttc`, `contrat_frais_intermediaire`, `contrat_caution`, `contrat_tva_pourcent`, `contrat_loyer_ht`, `contrat_total_ht`, `contrat_pack_montant_ttc`, `contrat_pack_loyer_ttc`, `contrat_type_renouvellement`, `contrat_renouv_tva_pourcent`, `contrat_renouv_loyer_ht`, `contrat_renouv_total_ht`, `contrat_renouv_loyer_ttc`, `contrat_renouv_annuel_ttc`, `contrat_statut`, `contrat_notes`, `contrat_mode_signature`, `created_at`, `updated_at`) VALUES (10,14,'Domiciliation commerciale','2026-05-18',12,'Personne Morale',NULL,'2026-05-18','2027-05-18',100.00,NULL,NULL,20.00,83.33,1.00,NULL,NULL,'Annuel',20.00,166.67,2.00,200.00,NULL,'actif','',NULL,'2026-07-14 22:27:35','2026-07-14 22:27:35');
/*!40000 ALTER TABLE `contrats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `documents_generes`
--

LOCK TABLES `documents_generes` WRITE;
/*!40000 ALTER TABLE `documents_generes` DISABLE KEYS */;
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (1,11,NULL,NULL,'SARL-AU_2026-03_Annonce-Legale-Journal_Template.docx','Annonce-Legale-Journal','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-SARL-AU\\SARL AU_2026-06-06_Annonce-Legale-Journal_Test-SARL-AU.docx',NULL,20.3,1,'2026-06-06 19:48:53');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (2,11,NULL,NULL,'SARL-AU_2026-03_Declaration-Immatriculation-RC_Template.docx','Declaration-Immatriculation-RC','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-SARL-AU\\SARL AU_2026-06-06_Declaration-Immatriculation-RC_Test-SARL-AU.docx',NULL,283.4,1,'2026-06-06 19:48:53');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (3,11,NULL,NULL,'SARL-AU_2026-03_Depot-Legal-Constitution_Template.docx','Depot-Legal-Constitution','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-SARL-AU\\SARL AU_2026-06-06_Depot-Legal-Constitution_Test-SARL-AU.docx',NULL,282.2,1,'2026-06-06 19:48:53');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (4,11,NULL,NULL,'SARL-AU_2026-03_Statuts_Template.docx','Statuts','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_Test-SARL-AU\\SARL AU_2026-06-06_Statuts_Test-SARL-AU.docx',NULL,34.5,1,'2026-06-06 19:48:54');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (19,12,NULL,NULL,NULL,'Attestation-Domiciliation-Initiale','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-06_SARL-AU_Test-PDF-Karim\\SARL AU_2026-06-06_Attestation-Domiciliation-Initiale_Test-PDF-Karim.docx','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-06_SARL-AU_Test-PDF-Karim\\SARL AU_2026-06-06_Attestation-Domiciliation-Initiale_Test-PDF-Karim.pdf',105.5,1,'2026-06-06 21:09:05');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (20,12,NULL,NULL,NULL,'Contrat-Domiciliation','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-06_SARL-AU_Test-PDF-Karim\\SARL AU_2026-06-06_Contrat-Domiciliation_Test-PDF-Karim.docx','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-06-06_SARL-AU_Test-PDF-Karim\\SARL AU_2026-06-06_Contrat-Domiciliation_Test-PDF-Karim.pdf',231.6,1,'2026-06-06 21:09:05');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (32,14,NULL,NULL,'D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages\\templates/../../templates\\SARL\\SARL_2026-07_Statuts_Template.docx','Statuts','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\ajax/../dossiers_generer/dossiers_domiciliation/2026-07-15_SARL_KAMARAD\\SARL_2026-07-15_Statuts_KAMARAD.docx',NULL,43.5,1,'2026-07-14 23:14:51');
INSERT INTO `documents_generes` (`id`, `societe_id`, `cession_id`, `pv_ago_id`, `template_source`, `doc_type`, `fichier_docx`, `fichier_pdf`, `taille_ko`, `valide`, `created_at`) VALUES (33,13,NULL,NULL,'D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages\\templates/../../templates\\SARL\\SARL_2026-07_Statuts_Template.docx','Statuts','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\ajax/../dossiers_generer/dossiers_domiciliation/2026-07-15_SARL_PHP-OUTPUT-SARL\\SARL_2026-07-15_Statuts_PHP-OUTPUT-SARL.docx',NULL,46.5,1,'2026-07-14 23:17:21');
/*!40000 ALTER TABLE `documents_generes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (1,NULL,1,'interne','warning','Societe sans contrat','Maghreb Services n\'a pas encore de contrat.','/Center-Domiciliation-App/index.php?page=societe&id=6','societe',6,0,0,3,'2026-07-14 21:22:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (2,NULL,1,'interne','info','Documents manquants','Atlas Domiciliation a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=5','societe',5,0,0,3,'2026-07-14 21:22:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (3,NULL,1,'interne','info','Documents manquants','Tech Solutions Maroc a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=8','societe',8,0,0,3,'2026-07-14 21:22:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (4,NULL,1,'interne','info','Documents manquants','Green Energy Africa a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=9','societe',9,0,0,3,'2026-07-14 21:22:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (5,NULL,1,'interne','info','Documents manquants','Consulting Pro International a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=10','societe',10,0,0,3,'2026-07-14 21:22:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (6,NULL,1,'interne','info','Document upload├®','Le document Uploads ├®tape 5 ÔÇö 2 fichier(s) a ├®t├® upload├®. ÔÇö par Super Admin',NULL,'document',NULL,0,0,3,'2026-07-14 22:27:24',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (7,NULL,1,'interne','success','Nouveau dossier cr├®├®','Le dossier complet de KAMARAD a ├®t├® cr├®├®. ÔÇö par Super Admin','index.php?page=societe&id=14','dossier',14,0,0,3,'2026-07-14 22:27:35',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (8,NULL,1,'interne','success','Document valid├®','Le document KAMARAD ÔÇö 1 doc(s) a ├®t├® valid├®. ÔÇö par Super Admin',NULL,'document',14,0,0,3,'2026-07-14 22:30:52',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (9,NULL,1,'interne','success','Nouveau dossier cr├®├®','Le dossier complet de Dossier #15 a ├®t├® cr├®├®. ÔÇö par Super Admin','index.php?page=societe&id=15','dossier',15,1,0,3,'2026-07-14 22:37:23','2026-07-14 23:40:49');
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (10,NULL,1,'interne','success','Document valid├®','Le document  ÔÇö 3 doc(s) a ├®t├® valid├®. ÔÇö par Super Admin',NULL,'document',15,0,0,3,'2026-07-14 22:39:50',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (11,NULL,1,'interne','success','Documents g├®n├®r├®s','Des documents ont ├®t├® g├®n├®r├®s pour Generation AJAX ÔÇö SARL_2026-07-15_Statuts_KAMARAD_Brouillon.docx. ÔÇö par Super Admin','index.php?page=societe&id=14','document',14,0,0,3,'2026-07-14 22:41:06',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (12,NULL,1,'interne','danger','Soci├®t├® supprim├®e','La soci├®t├® #15 a ├®t├® supprim├®e. ÔÇö par Super Admin',NULL,'societe',15,0,0,3,'2026-07-14 23:15:21',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (13,NULL,1,'interne','warning','Soci├®t├® modifi├®e','La soci├®t├® PHP OUTPUT SARL a ├®t├® modifi├®e. ÔÇö par Super Admin','index.php?page=societe&id=13','societe',13,0,0,3,'2026-07-14 23:16:58',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (14,NULL,1,'interne','success','Documents g├®n├®r├®s','Des documents ont ├®t├® g├®n├®r├®s pour Generation AJAX ÔÇö SARL_2026-07-15_Statuts_PHP-OUTPUT-SARL_Brouillon.docx. ÔÇö par Super Admin','index.php?page=societe&id=13','document',13,0,0,3,'2026-07-14 23:17:21',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (15,NULL,1,'interne','warning','Societe sans contrat','Maghreb Services n\'a pas encore de contrat.','/Center-Domiciliation-App/index.php?page=societe&id=6','societe',6,0,0,3,'2026-07-16 09:46:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (16,NULL,1,'interne','info','Documents manquants','Atlas Domiciliation a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=5','societe',5,0,0,3,'2026-07-16 09:46:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (17,NULL,1,'interne','info','Documents manquants','Tech Solutions Maroc a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=8','societe',8,0,0,3,'2026-07-16 09:46:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (18,NULL,1,'interne','info','Documents manquants','Green Energy Africa a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=9','societe',9,0,0,3,'2026-07-16 09:46:03',NULL);
INSERT INTO `notifications` (`id`, `target_user_id`, `target_role_id`, `target_type`, `type`, `title`, `message`, `link`, `entity_type`, `entity_id`, `is_read`, `is_global`, `created_by`, `created_at`, `read_at`) VALUES (19,NULL,1,'interne','info','Documents manquants','Consulting Pro International a des associes et un contrat mais aucun document genere.','/Center-Domiciliation-App/index.php?page=generation&societe_id=10','societe',10,0,0,3,'2026-07-16 09:46:03',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (1,'Voir le tableau de bord','dashboard.view','dashboard','Acc├®der au tableau de bord','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (2,'Voir les soci├®t├®s','societes.view','societes','Consulter la liste et les fiches soci├®t├®s','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (3,'Cr├®er une soci├®t├®','societes.create','societes','Cr├®er une nouvelle soci├®t├®','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (4,'Modifier une soci├®t├®','societes.edit','societes','Modifier les informations d une soci├®t├®','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (5,'Supprimer une soci├®t├®','societes.delete','societes','Supprimer une soci├®t├®','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (6,'Exporter les soci├®t├®s','societes.export','societes','Exporter la liste des soci├®t├®s en CSV','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (7,'Voir les associ├®s','associes.view','associes','Consulter la liste des associ├®s','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (8,'Cr├®er un associ├®','associes.create','associes','Ajouter un associ├®','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (9,'Modifier un associ├®','associes.edit','associes','Modifier les informations d un associ├®','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (10,'Supprimer un associ├®','associes.delete','associes','Supprimer un associ├®','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (11,'Exporter les associ├®s','associes.export','associes','Exporter la liste des associ├®s en CSV','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (12,'Voir les contrats','contrats.view','contrats','Consulter la liste des contrats','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (13,'Cr├®er un contrat','contrats.create','contrats','Ajouter un contrat','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (14,'Modifier un contrat','contrats.edit','contrats','Modifier les informations d un contrat','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (15,'Supprimer un contrat','contrats.delete','contrats','Supprimer un contrat','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (16,'Exporter les contrats','contrats.export','contrats','Exporter la liste des contrats en CSV','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (17,'Voir les collaborateurs','collaborateurs.view','collaborateurs','Consulter la liste des collaborateurs','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (18,'Cr├®er un collaborateur','collaborateurs.create','collaborateurs','Ajouter un collaborateur','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (19,'Modifier un collaborateur','collaborateurs.edit','collaborateurs','Modifier les informations d un collaborateur','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (20,'Supprimer un collaborateur','collaborateurs.delete','collaborateurs','Supprimer un collaborateur','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (21,'Exporter les collaborateurs','collaborateurs.export','collaborateurs','Exporter la liste des collaborateurs en CSV','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (22,'Utiliser l assistant de cr├®ation','wizard.create','wizard','Acc├®der au wizard de cr├®ation de dossier','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (23,'Voir les templates','templates.view','templates','Consulter la liste des templates','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (24,'Cr├®er un template','templates.create','templates','Ajouter un nouveau template','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (25,'Modifier un template','templates.edit','templates','Modifier un template existant','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (26,'Supprimer un template','templates.delete','templates','Supprimer un template','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (27,'Utiliser le g├®n├®rateur de dossiers','generation.use','generation','G├®n├®rer les documents d un dossier','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (28,'Voir les documents g├®n├®r├®s','documents.view','documents','Consulter la liste des documents','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (29,'T├®l├®charger les documents','documents.download','documents','T├®l├®charger les fichiers g├®n├®r├®s','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (30,'Voir la configuration','configuration.view','configuration','Acc├®der ├á la page de configuration','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (31,'Modifier la configuration','configuration.edit','configuration','Modifier les donn├®es de configuration','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (32,'Voir l analyse de couverture','analyse.view','analyse','Acc├®der ├á l analyse de couverture','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (33,'Voir les variables','variables.view','variables','Consulter la gestion des variables','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (34,'Modifier les variables','variables.edit','variables','Renommer et supprimer des variables','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (35,'Modifier les valeurs par d├®faut','defaults.edit','defaults','Configurer les valeurs par d├®faut','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (36,'Utiliser la conversion Word ÔåÆ PDF','convert.use','convert','Acc├®der ├á l outil de conversion','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (37,'Utiliser l assistant IA','ai.use','ai','Acc├®der ├á l assistant IA','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (38,'G├®rer les r├┤les et permissions','roles.manage','roles','Cr├®er, modifier et supprimer des r├┤les','2026-06-03 14:51:19');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (39,'Voir les cessions','cessions.view','cessions','Consulter la liste des cessions de parts','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (40,'Creer une cession','cessions.create','cessions','Creer une nouvelle cession de parts','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (41,'Modifier une cession','cessions.edit','cessions','Modifier une cession existante','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (42,'Supprimer une cession','cessions.delete','cessions','Supprimer une cession','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (43,'Exporter les cessions','cessions.export','cessions','Exporter la liste des cessions','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (44,'','societes.import','societes','Importer la liste des societes depuis Excel','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (45,'','associes.import','associes','Importer la liste des associ├®s depuis Excel','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (46,'','contrats.import','contrats','Importer la liste des contrats depuis Excel','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (47,'','collaborateurs.import','collaborateurs','Importer la liste des collaborateurs depuis Excel','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (48,'','cessions.import','cessions','Importer la liste des cessions depuis Excel','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (49,'','pv_ago.view','pv_ago','Consulter les PV d assemblee generale ordinaire','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (50,'','pv_ago.create','pv_ago','Creer un PV d assemblee generale ordinaire','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (51,'','pv_ago.edit','pv_ago','Modifier un PV d assemblee generale ordinaire','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (52,'','pv_ago.delete','pv_ago','Supprimer un PV d assemblee generale ordinaire','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (53,'Suivi administratif des cessions','cessions.suivi','cessions','Consulter et gerer le suivi administratif des cessions','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (54,'Voir les mod├¿les de r├®solutions','pv_resolutions.view','configuration','Consulter les mod├¿les de r├®solutions PV','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (55,'Cr├®er un mod├¿le de r├®solution','pv_resolutions.create','configuration','Cr├®er un nouveau mod├¿le','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (56,'Modifier un mod├¿le de r├®solution','pv_resolutions.edit','configuration','Modifier un mod├¿le existant','2026-07-14 21:22:02');
INSERT INTO `permissions` (`id`, `nom`, `permission_key`, `category`, `description`, `created_at`) VALUES (57,'Supprimer un mod├¿le de r├®solution','pv_resolutions.delete','configuration','Supprimer un mod├¿le','2026-07-14 21:22:02');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pv_ago`
--

LOCK TABLES `pv_ago` WRITE;
/*!40000 ALTER TABLE `pv_ago` DISABLE KEYS */;
/*!40000 ALTER TABLE `pv_ago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pv_resolutions_templates`
--

LOCK TABLES `pv_resolutions_templates` WRITE;
/*!40000 ALTER TABLE `pv_resolutions_templates` DISABLE KEYS */;
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Cession de parts sociales','L\'Assembl├®e G├®n├®rale, **┬½ **[C├®dant]** ┬╗** d├®clare c├®der ├á **┬½ [Cessionnaire] ┬╗** **[NbParts]** parts sociales de **[ValeurNominale]** DH chacune, pour un montant total de **[PrixTotal]** DH.\n\nL\'Assembl├®e accepte express├®ment cette cession et reconna├«t que le prix de cession a ├®t├® r├®gl├® entre les parties.','cession',10,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Agr├®ment du cessionnaire','L\'Assembl├®e G├®n├®rale agr├®e la cession susmentionn├®e et accepte l\'entr├®e de **┬½ [Cessionnaire] ┬╗** en qualit├® d\'associ├® au sein de la soci├®t├®.','cession',20,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Modification des statuts ÔÇö Capital','En cons├®quence de la cession, l\'article relatif au capital social est modifi├® comme suit :\n\nLe capital social de **[Capital]** DH est divis├® en **[NbPartsTotal]** parts de **[ValeurNominale]** DH chacune, r├®parties entre les associ├®s.\n\nTous pouvoirs sont donn├®s au g├®rant pour accomplir les formalit├®s de modification.','cession',30,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'D├®mission du g├®rant','L\'Assembl├®e G├®n├®rale prend acte de la d├®mission de **┬½ [AncienG├®rant] ┬╗** de ses fonctions de g├®rant de la soci├®t├®, avec effet ├á compter de ce jour, et le remercie pour les services rendus.','cession',40,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Nomination du g├®rant','L\'Assembl├®e G├®n├®rale d├®cide de nommer **┬½ [NouveauG├®rant] ┬╗** en qualit├® de g├®rant de la soci├®t├®, pour une dur├®e ind├®termin├®e, avec tous les pouvoirs n├®cessaires ├á l\'exercice de ses fonctions.','cession',50,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Transformation SARL AU ÔåÆ SARL','L\'Assembl├®e G├®n├®rale d├®cide de transformer la forme juridique de la soci├®t├® de SARL ├á Associ├® Unique (SARL AU) en SARL ├á associ├®s multiples, conform├®ment aux dispositions de la loi 5-96 modifi├®e.\n\nLes statuts seront modifi├®s en cons├®quence.','cession',60,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Pouvoirs pour formalit├®s','Tous pouvoirs sont donn├®s ├á **┬½ [C├®dant] ┬╗** pour effectuer toutes formalit├®s de d├®p├┤t et d\'inscription modificative aupr├¿s du greffe du tribunal de commerce, ainsi que toutes autres d├®marches requises par la loi.','cession',70,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Changement d\'objet social','L\'Assembl├®e G├®n├®rale d├®cide de modifier l\'article relatif ├á l\'objet social, qui sera d├®sormais r├®dig├® comme suit :\n\n┬½ La soci├®t├® a pour objet : **[d├®tailler le nouvel objet]** ┬╗\n\nTous pouvoirs sont donn├®s au g├®rant pour accomplir les formalit├®s de d├®p├┤t et d\'inscription modificative.','general',80,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Transfert du si├¿ge social','L\'Assembl├®e G├®n├®rale d├®cide de transf├®rer le si├¿ge social de **[AncienneAdresse]** ├á **[NouvelleAdresse]**.\n\nL\'article relatif au si├¿ge social sera modifi├® en cons├®quence.\n\nTous pouvoirs sont donn├®s au g├®rant pour effectuer les formalit├®s l├®gales.','general',90,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Changement de d├®nomination sociale','L\'Assembl├®e G├®n├®rale d├®cide de modifier la d├®nomination sociale, qui sera d├®sormais : **┬½ [NouvelleD├®nomination] ┬╗**.\n\nL\'article relatif ├á la d├®nomination sera modifi├® en cons├®quence.\n\nTous pouvoirs sont donn├®s au g├®rant pour effectuer les formalit├®s.','general',100,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Augmentation du capital social','L\'Assembl├®e G├®n├®rale d├®cide d\'augmenter le capital social d\'un montant de **[MontantAugmentation]** DH par cr├®ation de **[NbPartsNouvelles]** parts sociales nouvelles de **[ValeurNominale]** DH chacune.\n\nLe capital social sera ainsi port├® de **[CapitalAvant]** DH ├á **[CapitalApr├¿s]** DH.\n\nL\'article relatif au capital social sera modifi├® en cons├®quence.','general',110,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Modification de la dur├®e de la soci├®t├®','L\'Assembl├®e G├®n├®rale d├®cide de proroger la dur├®e de la soci├®t├® de **[NbAnn├®es]** ann├®es, soit jusqu\'au **[NouvelleDateExpiration]**.\n\nL\'article relatif ├á la dur├®e sera modifi├® en cons├®quence.\n\nTous pouvoirs sont donn├®s au g├®rant pour effectuer les formalit├®s.','general',120,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Clause d\'agr├®ment','L\'Assembl├®e G├®n├®rale d├®cide d\'ins├®rer une clause d\'agr├®ment dans les statuts, soumettant toute cession de parts ├á un agr├®ment pr├®alable de la g├®rance.\n\nUn nouvel article sera ins├®r├® dans les statuts ├á cet effet.','general',130,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'Nomination d\'un commissaire aux comptes','L\'Assembl├®e G├®n├®rale d├®cide de nommer **[NomCAC]** en qualit├® de commissaire aux comptes titulaire, pour un mandat de six exercices, conform├®ment aux dispositions l├®gales en vigueur.','general',140,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'R├®vocation du g├®rant','L\'Assembl├®e G├®n├®rale d├®cide de r├®voquer **┬½ [AncienG├®rant] ┬╗** de ses fonctions de g├®rant, avec effet imm├®diat, et de nommer **┬½ [NouveauG├®rant] ┬╗** en qualit├® de nouveau g├®rant pour une dur├®e ind├®termin├®e.','general',150,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `pv_resolutions_templates` (`id`, `title`, `content`, `category`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Adoption de nouveaux statuts','L\'Assembl├®e G├®n├®rale d├®cide d\'adopter les nouveaux statuts de la soci├®t├®, lesquels annulent et remplacent les pr├®c├®dents statuts.\n\nUne copie des nouveaux statuts sera annex├®e au pr├®sent proc├¿s-verbal.\n\nTous pouvoirs sont donn├®s au g├®rant pour effectuer les formalit├®s de d├®p├┤t.','general',160,'2026-07-14 21:22:02','2026-07-14 21:22:02');
/*!40000 ALTER TABLE `pv_resolutions_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_activites`
--

LOCK TABLES `ref_activites` WRITE;
/*!40000 ALTER TABLE `ref_activites` DISABLE KEYS */;
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Commerce de gros',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Commerce de detail',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Restauration',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Hotel',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Transport',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Logistique',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Consulting',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Services IT',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Services de sante',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Education',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Immobilier',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Construction',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Manufacture',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'Agriculture',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'Peche',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Energie',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'Telecommunications',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'Banque et Finance',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'Assurance',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'Tourisme',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (41,'Travaux Divers ou de Construction',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (42,'Marchand effectuant Import Export',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (43,'N├®gociant',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_activites` (`id`, `activite`, `sort_order`, `created_at`, `updated_at`) VALUES (44,'Conseil de Gestion',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
/*!40000 ALTER TABLE `ref_activites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_activites_ompic`
--

LOCK TABLES `ref_activites_ompic` WRITE;
/*!40000 ALTER TABLE `ref_activites_ompic` DISABLE KEYS */;
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'A','AGRICULTURE, SYLVICULTURE ET PECHE',1,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'B','INDUSTRIES EXTRACTIVES',2,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'C','INDUSTRIE MANUFACTURIERE',3,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'D','PRODUCTION ET DISTRIBUTION D\'ELECTRICITE, DE GAZ, DE VAPEUR ET D\'AIR CONDITIONNE',4,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'E','PRODUCTION ET DISTRIBUTION D\'EAU; ASSAINISSEMENT, GESTION DES DECHETS ET DEPOLLUTION',5,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'F','CONSTRUCTION',6,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'G','COMMERCE; REPARATION D\'AUTOMOBILES ET DE MOTOCYCLES',7,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'H','TRANSPORT ET ENTREPOSAGE',8,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'I','HEBERGEMENT ET RESTAURATION',9,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'J','INFORMATION ET COMMUNICATION',10,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'K','ACTIVITES FINANCIERES ET D\'ASSURANCE',11,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'L','ACTIVITES IMMOBILIERES',12,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'M','ACTIVITES SPECIALISEES, SCIENTIFIQUES ET TECHNIQUES',13,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'N','ACTIVITES DE SERVICES ADMINISTRATIFS ET DE SOUTIEN',14,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'P','ENSEIGNEMENT',15,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Q','SANTE HUMAINE ET ACTION SOCIALE',16,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'R','ARTS, SPECTACLES ET ACTIVITES RECREATIVES',17,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'S','AUTRES ACTIVITES DE SERVICES',18,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'46','Commerce de gros',19,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'47','Commerce de detail',20,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (21,'49','Transports terrestres',21,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (22,'55','Hebergement',22,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (23,'56','Restauration',23,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (24,'58','Edition',24,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (25,'62','Programmation, conseil et autres activites informatiques',25,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (26,'68','Activites immobilieres',26,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (27,'69','Activites juridiques et comptables',27,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (28,'70','Activites des sieges sociaux; conseil de gestion',28,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (29,'71','Activites d\'architecture et d\'ingenierie',29,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (30,'73','Publicite et etudes de marche',30,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (31,'77','Activites de location et location-bail',31,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (32,'79','Agences de voyage',32,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (33,'85','Enseignement',33,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (34,'86','Activites pour la sante humaine',34,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (35,'93','Activites sportives, recreatives et de loisirs',35,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (36,'96','Autres services personnels',36,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (37,'4711','Commerce de detail alimentaire',37,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (38,'6201','Programmation informatique',38,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (39,'6202','Conseil informatique',39,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (40,'6910','Activites juridiques',40,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (41,'6920','Activites comptables',41,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (42,'7010','Activites des sieges sociaux',42,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (43,'7022','Conseil pour les affaires et autres conseils de gestion',43,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (44,'7111','Activites d\'architecture',44,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (45,'7112','Activites d\'ingenierie',45,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (46,'7311','Activites des agences de publicite',46,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (47,'8299','Autres activites de soutien aux entreprises',47,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (48,'9602','Coiffure et soins de beaute',48,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_activites_ompic` (`id`, `code`, `libelle`, `sort_order`, `created_at`, `updated_at`) VALUES (49,'9609','Autres services personnels',49,'2026-05-21 19:06:27','2026-05-21 19:06:27');
/*!40000 ALTER TABLE `ref_activites_ompic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_fonctions`
--

LOCK TABLES `ref_fonctions` WRITE;
/*!40000 ALTER TABLE `ref_fonctions` DISABLE KEYS */;
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Gerant',1,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'President',2,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Directeur General',3,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Directeur General Delegue',4,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Directeur Administratif',5,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Expert Comptable',6,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Commissaire aux Comptes',7,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Avocat',8,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Secretaire',9,'2026-06-06 19:17:41','2026-06-06 19:17:41');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Gestion administrative',1,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Support operationnel',2,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Agent de traitement',3,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Chef d\'├®quipe',4,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'Superviseur',5,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'Comptable',6,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Assistant juridique',7,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'Responsable client├¿le',8,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'Coursier',9,'2026-07-14 21:22:02','2026-07-14 21:22:02');
INSERT INTO `ref_fonctions` (`id`, `fonction`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'Autre',99,'2026-07-14 21:22:02','2026-07-14 21:22:02');
/*!40000 ALTER TABLE `ref_fonctions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_formes_juridiques`
--

LOCK TABLES `ref_formes_juridiques` WRITE;
/*!40000 ALTER TABLE `ref_formes_juridiques` DISABLE KEYS */;
INSERT INTO `ref_formes_juridiques` (`id`, `forme_juridique`, `template_folder`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'SARL AU','SARL AU',0,'2026-05-21 19:06:27','2026-06-03 14:56:01');
INSERT INTO `ref_formes_juridiques` (`id`, `forme_juridique`, `template_folder`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'SARL','SARL',0,'2026-05-21 19:06:27','2026-06-03 14:56:01');
INSERT INTO `ref_formes_juridiques` (`id`, `forme_juridique`, `template_folder`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Personne Physique','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_formes_juridiques` (`id`, `forme_juridique`, `template_folder`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'SA','SA',0,'2026-05-21 19:06:27','2026-06-03 14:56:01');
INSERT INTO `ref_formes_juridiques` (`id`, `forme_juridique`, `template_folder`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Succurssale Etrang├¿re','',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_formes_juridiques` (`id`, `forme_juridique`, `template_folder`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Succurssale Marocaine','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
/*!40000 ALTER TABLE `ref_formes_juridiques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_lieux_naissance`
--

LOCK TABLES `ref_lieux_naissance` WRITE;
/*!40000 ALTER TABLE `ref_lieux_naissance` DISABLE KEYS */;
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Casablanca',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Rabat',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Marrakech',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Fes',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Agadir',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Tangier',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Meknes',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Tetouan',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Oujda',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Beni Mellal',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Khouribga',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Essaouira',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Safi',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'Azemmour',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'Ouezzane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Sefrou',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'Taza',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'Nador',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'Hoceima',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'Driouch',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_lieux_naissance` (`id`, `lieu_naissance`, `sort_order`, `created_at`, `updated_at`) VALUES (41,'Mohammedia',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
/*!40000 ALTER TABLE `ref_lieux_naissance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_nationalites`
--

LOCK TABLES `ref_nationalites` WRITE;
/*!40000 ALTER TABLE `ref_nationalites` DISABLE KEYS */;
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Marocaine',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Fran├ºaise',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Belge',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Suisse',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Allemande',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Italienne',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Espagnole',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Portugaise',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Britannique',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Am├®ricaine',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Canadienne',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Alg├®rienne',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Tunisienne',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'S├®n├®galaise',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'Camerounaise',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Gabonaise',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'Ivoirienne',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'Congolaise',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'Guin├®enne',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'Malienne',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_nationalites` (`id`, `nationalite`, `sort_order`, `created_at`, `updated_at`) VALUES (41,'Cameronnie',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
/*!40000 ALTER TABLE `ref_nationalites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_qualites_associe`
--

LOCK TABLES `ref_qualites_associe` WRITE;
/*!40000 ALTER TABLE `ref_qualites_associe` DISABLE KEYS */;
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Gerant',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Associe unique',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Associe majoritaire',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Associe minoritaire',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'President',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Directeur General',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Actionnaire',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Porteur de parts',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'Associ├®',9,'2026-07-14 22:20:22','2026-07-14 22:20:22');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'Associ├®e Unique et G├®rant',10,'2026-07-14 22:20:22','2026-07-14 22:20:22');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'Associ├® G├®rant',11,'2026-07-14 22:20:22','2026-07-14 22:20:22');
INSERT INTO `ref_qualites_associe` (`id`, `qualite_associe`, `sort_order`, `created_at`, `updated_at`) VALUES (21,'Associ├® Co-g├®rant',12,'2026-07-14 22:20:22','2026-07-14 22:20:22');
/*!40000 ALTER TABLE `ref_qualites_associe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_ste_adresses`
--

LOCK TABLES `ref_ste_adresses` WRITE;
/*!40000 ALTER TABLE `ref_ste_adresses` DISABLE KEYS */;
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'123 Boulevard Hassan II','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'45 Avenue Mohammed V','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'12 Rue Dar El Baraka','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'78 Avenue des FAR','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'34 Rue Ghandouri','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'56 Boulevard de la Corniche','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'89 Place de la Concordance','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'11 Rue Ibn Sina','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'25 Avenue de Marrakech','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'67 Boulevard de Paris','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'43 Route de Meknes','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'55 Boulevard Allal El Fassi','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'88 Rue Ahmed Chaouki','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'22 Avenue Hassan II (Downtown)','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'99 Boulevard Moulay Ismail','',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (31,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (32,'46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA','',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_ste_adresses` (`id`, `ste_adresse`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (33,'56 BOULEVARD MOULAY YOUSSEF 3EME ETAGE APPT 14, CASABLANCA','',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
/*!40000 ALTER TABLE `ref_ste_adresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_tribunaux`
--

LOCK TABLES `ref_tribunaux` WRITE;
/*!40000 ALTER TABLE `ref_tribunaux` DISABLE KEYS */;
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Casablanca','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Rabat','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Marrakech','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Fes','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Agadir','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Tangier','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Meknes','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Tetouan','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Oujda','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Beni Mellal','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Khouribga','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Settat','Tribunal de commerce',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Casablanca','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'Rabat','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'Marrakech','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Fes','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'Agadir','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'Tangier','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'Meknes','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'Tetouan','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (21,'Oujda','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (22,'Beni Mellal','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (23,'Khouribga','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (24,'Oulad Teima','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (25,'Settat','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (26,'Khemisset','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (27,'Tiflet','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (28,'Skhirat-Temara','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (29,'Sidi Kacem','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (30,'Sidi Slimane','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (31,'Souk El Arbaa','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (32,'Taourirt','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:06:27','2026-06-03 15:00:22');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (65,'Berrechid','Tribunal de commerce',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (66,'Mohammedia','Tribunal de commerce',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (67,'Berrechid','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `ref_tribunaux` (`id`, `tribunal`, `tribunal_type`, `sort_order`, `created_at`, `updated_at`) VALUES (68,'Mohammedia','Tribunal de Premi├¿re Instance',0,'2026-05-21 19:53:03','2026-05-21 19:53:03');
/*!40000 ALTER TABLE `ref_tribunaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ref_villes`
--

LOCK TABLES `ref_villes` WRITE;
/*!40000 ALTER TABLE `ref_villes` DISABLE KEYS */;
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Agadir',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (2,'Ait Melloul',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (3,'Al Hoceima',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (4,'Asilah',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (5,'Azemmour',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (6,'Azrou',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (7,'Beni Mellal',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (8,'Beni Ansar',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (9,'Berrechid',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (10,'Berkane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (11,'Boujdour',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (12,'Boulemane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (13,'Casablanca',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (14,'Chefchaouen',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (15,'Chichaoua',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (16,'Dakhla',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (17,'El Hajeb',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (18,'El Jadida',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (19,'El Kelaa Des Sraghna',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (20,'Errachidia',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (21,'Essaouira',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (22,'Fes',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (23,'Figuig',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (24,'Fnideq',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (25,'Guelmim',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (26,'Guercif',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (27,'Ifrane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (28,'Inezgane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (29,'Jerada',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (30,'Kelaat Mgouna',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (31,'Khemisset',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (32,'Khenifra',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (33,'Khouribga',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (34,'Ksar El Kebir',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (35,'Laayoune',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (36,'Larache',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (37,'Marrakech',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (38,'Martil',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (39,'Meknes',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (40,'Midelt',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (41,'Mohammedia',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (42,'Nador',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (43,'Ouarzazate',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (44,'Ouezzane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (45,'Oujda',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (46,'Oulad Teima',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (47,'Rabat',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (48,'Safi',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (49,'Sale',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (50,'Sefrou',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (51,'Settat',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (52,'Sidi Bennour',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (53,'Sidi Ifni',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (54,'Sidi Kacem',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (55,'Sidi Slimane',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (56,'Skhirat',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (57,'Souk El Arbaa',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (58,'Tanger',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (59,'Tan-Tan',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (60,'Taourirt',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (61,'Taroudant',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (62,'Tata',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (63,'Taza',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (64,'Temara',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (65,'Tetouan',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (66,'Tiflet',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (67,'Tinghir',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (68,'Tiznit',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (69,'Youssoufia',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
INSERT INTO `ref_villes` (`id`, `ville`, `sort_order`, `created_at`, `updated_at`) VALUES (70,'Zagora',0,'2026-05-21 19:06:27','2026-05-21 19:06:27');
/*!40000 ALTER TABLE `ref_villes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,3);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,4);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,5);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,6);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,8);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,9);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,10);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,11);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,13);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,14);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,15);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,16);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,17);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,18);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,19);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,20);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,21);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,22);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,23);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,24);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,25);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,26);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,27);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,30);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,31);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,32);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,33);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,34);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,35);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,36);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,37);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,38);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,44);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,45);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,46);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,47);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,48);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,49);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,50);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,51);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1,52);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,3);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,4);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,5);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,6);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,8);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,9);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,10);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,11);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,13);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,14);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,15);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,16);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,17);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,18);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,19);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,20);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,21);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,22);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,23);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,24);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,25);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,26);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,27);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,30);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,31);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,32);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,33);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,34);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,35);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,36);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,37);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,39);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,40);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,41);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,42);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,43);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,44);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,45);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,46);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,47);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,48);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,49);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,50);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,51);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,52);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (2,53);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,3);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,4);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,5);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,6);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,8);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,9);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,10);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,11);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,13);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,14);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,15);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,16);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,17);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,22);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,23);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,27);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,32);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,33);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,39);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,40);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,41);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,42);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,43);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (3,53);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,3);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,4);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,8);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,9);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,13);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,14);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,22);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,23);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,27);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,39);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,40);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,41);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4,53);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,3);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,8);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,13);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,22);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,23);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,27);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,39);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5,40);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,23);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6,39);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (7,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (7,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (7,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (7,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (7,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (7,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (8,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (8,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (8,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (8,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (8,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (8,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (9,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (9,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (9,7);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (9,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (9,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (9,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (10,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (11,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (11,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (11,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (11,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (11,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (12,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (12,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (12,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (12,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (12,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (13,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (13,2);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (13,12);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (13,28);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (13,29);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (14,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (15,1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (16,1);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (1,'Super Admin','Acc├¿s total au syst├¿me',1,1,1,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (2,'Admin','Administrateur avec presque tous les droits',1,0,2,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (3,'Chef d ├®quipes','G├¿re les dossiers et son ├®quipe',1,0,3,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (4,'Employ├®','Agent de traitement des dossiers',1,0,4,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (5,'Assistante','Support administratif et documentaire',1,0,5,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (6,'Stagiaire','Acc├¿s lecture seule',1,0,6,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (7,'Expert-comptable','Expert-comptable externe',0,0,10,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (8,'Comptable agr├®├®','Comptable agr├®├® externe',0,0,11,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (9,'Commissaire aux comptes','Commissaire aux comptes',0,0,12,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (10,'Coursier','Coursier / livreur',0,0,13,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (11,'Avocat','Avocat externe',0,0,14,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (12,'Notaire','Notaire externe',0,0,15,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (13,'Conseil juridique','Conseil juridique externe',0,0,16,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (14,'Banque','Repr├®sentant bancaire',0,0,17,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (15,'Assurance','Repr├®sentant assurance',0,0,18,'2026-06-03 14:51:19');
INSERT INTO `roles` (`id`, `nom`, `description`, `is_internal`, `is_system`, `sort_order`, `created_at`) VALUES (16,'Autre','Autre type de collaborateur',0,0,99,'2026-06-03 14:51:19');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `societes`
--

LOCK TABLES `societes` WRITE;
/*!40000 ALTER TABLE `societes` DISABLE KEYS */;
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (5,NULL,'DOM-2026-001','Atlas Domiciliation',NULL,'SARL','creation','001122334455667','2026-01-10','RC12345','IF778899','','',NULL,NULL,100000.00,100,1000.00,'2026-12-31','123 Boulevard Hassan II','123 Boulevard Hassan II','Casablanca','Casablanca',NULL,'contact@atlas.test','+212600000001','Standard','Creation','Electronique','2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (6,NULL,'DOM-2026-002','Maghreb Services',NULL,'SARL AU','creation','998877665544332','2026-03-15','RC54321','IF665544','','',NULL,NULL,50000.00,100,500.00,'2027-03-14','45 Avenue Mohammed V','45 Avenue Mohammed V','Rabat','Casablanca',NULL,'admin@maghreb.test','+212600000002','Standard','Creation','Physique','2026-05-21 19:53:03','2026-05-21 19:53:03');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (8,NULL,'DOM-2026-003','Tech Solutions Maroc','Tech Solutions SARL','SARL','creation','112233445566778','2026-05-01','RC67890','IF112233','','',NULL,NULL,200000.00,200,1000.00,'2027-04-30','15 Rue Ibn Sina, Casablanca','15 Rue Ibn Sina, Casablanca','Casablanca','Casablanca','Tribunal de commerce','contact@techsolutions.ma','+212600000003','Standard','Creation','Electronique','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (9,NULL,'DOM-2026-004','Green Energy Africa','Green Energy SA','SA','creation','998877665544331','2026-04-10','RC11223','IF998877','','',NULL,NULL,500000.00,500,1000.00,'2027-03-31','Angle Bd Zerktouni, Rabat','Angle Bd Zerktouni, Rabat','Rabat','Rabat','Tribunal de commerce','info@greenenergy.ma','+212600000004','Premium','Creation','Physique','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (10,NULL,'DOM-2026-005','Consulting Pro International','CPI SARL AU','SARL AU','creation','556677889900112','2026-06-01','RC44556','IF556677','','','Conseil en gestion et strategie d entreprise',NULL,100000.00,100,1000.00,'2027-05-31','Technopark, Casablanca','Technopark, Casablanca','Casablanca','Casablanca','Tribunal de commerce','contact@cpi.ma','+212600000005','Standard','Creation','Electronique','2026-06-03 15:07:59','2026-06-03 15:07:59');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (11,4,'DOM-2026-006','Test SARL AU',NULL,'SARL AU','creation','001234567',NULL,'RC-123','IF-456','','','','M',100000.00,1000,100.00,NULL,NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','test@sarl.ma','060000001','creation','','','2026-06-06 19:48:22','2026-06-06 19:48:22');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (12,2,'DOM-2026-007','Test PDF Karim',NULL,'SARL AU','creation','12345678',NULL,'RC12345','IF12345','','','','',100000.00,1000,100.00,NULL,NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','test@test.com','0612345678','domiciliation','','','2026-06-06 20:18:36','2026-06-06 20:18:36');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (13,2,'DOM-2026-008','PHP OUTPUT SARL',NULL,'SARL','creation','12345678',NULL,'RC12345','IF12345','','','Services IT','J',100000.00,1000,100.00,NULL,NULL,'HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA','Casablanca','Casablanca','Tribunal de commerce','php@output.ma','0600000000','domiciliation','','','2026-06-06 21:29:56','2026-07-14 23:16:58');
INSERT INTO `societes` (`id`, `created_by`, `societe_dossier`, `societe_raison_sociale`, `den_ste`, `societe_forme_juridique`, `societe_source`, `societe_ice`, `societe_date_ice`, `societe_rc`, `societe_if`, `societe_tp`, `societe_cnss`, `societe_activites_statuts`, `societe_activites_ompic`, `societe_capital`, `societe_part_social`, `societe_valeur_nominale`, `societe_date_exp_cert_neg`, `societe_adresse`, `societe_adresse_siege`, `societe_ville`, `societe_tribunal`, `societe_tribunal_type`, `societe_email`, `societe_telephone`, `societe_type_generation`, `societe_procedure_creation`, `societe_mode_depot`, `created_at`, `updated_at`) VALUES (14,3,'DOM-2026-009','KAMARAD',NULL,'SARL','creation','123456789000012','2025-01-15','123456','12345678','','','Immobilier','I',100000.00,1000,100.00,'2027-01-15',NULL,'','Casablanca','Casablanca','Tribunal de commerce','contact@test-sarl.ma','0522123456','creation','normal','depot_physique','2026-07-14 22:27:35','2026-07-14 22:27:35');
/*!40000 ALTER TABLE `societes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `uploaded_docs`
--

LOCK TABLES `uploaded_docs` WRITE;
/*!40000 ALTER TABLE `uploaded_docs` DISABLE KEYS */;
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (1,11,'certificat_negatif',NULL,'2026-06-06_CN_Test_SARL_AU.pdf','2026-06-06_CN_Test_SARL_AU.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../uploads/dossiers/11/2026-06-06_CN_Test_SARL_AU.pdf',0.0,'2026-06-06 19:48:22');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (2,11,'cin_gerant',0,'2026-06-06_CIN_Test_User_Test_SARL_AU.pdf','2026-06-06_CIN_Test_User_Test_SARL_AU.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../uploads/dossiers/11/2026-06-06_CIN_Test_User_Test_SARL_AU.pdf',0.0,'2026-06-06 19:48:22');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (3,12,'certificat_negatif',NULL,'2026-06-06_CN_Test_PDF_Karim.pdf','2026-06-06_CN_Test_PDF_Karim.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../uploads/dossiers/12/2026-06-06_CN_Test_PDF_Karim.pdf',0.2,'2026-06-06 20:18:37');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (4,12,'cin_gerant',0,'2026-06-06_CIN___Test_PDF_Karim.pdf','2026-06-06_CIN___Test_PDF_Karim.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../uploads/dossiers/12/2026-06-06_CIN___Test_PDF_Karim.pdf',0.2,'2026-06-06 20:18:37');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (5,13,'certificat_negatif',NULL,'2026-06-06_CN_PHP_OUTPUT_SARL.pdf','2026-06-06_CN_PHP_OUTPUT_SARL.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_PHP-OUTPUT-SARL/_uploads/2026-06-06_CN_PHP_OUTPUT_SARL.pdf',0.0,'2026-06-06 21:29:56');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (6,13,'cin_gerant',0,'2026-06-06_CIN_PHP_Output_PHP_OUTPUT_SARL.pdf','2026-06-06_CIN_PHP_Output_PHP_OUTPUT_SARL.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages/../dossiers_dom/2026-05-18_SARL-AU_PHP-OUTPUT-SARL/_uploads/2026-06-06_CIN_PHP_Output_PHP_OUTPUT_SARL.pdf',0.0,'2026-06-06 21:29:56');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (7,14,'certificat_negatif',NULL,'2026-07-15_CN_KAMARAD.pdf','2026-07-15_CN_KAMARAD.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages\\dossiers\\creation_steps/../../../dossiers_generer/dossiers_creation/2026-05-18_SARL_KAMARAD/_uploads/2026-07-15_CN_KAMARAD.pdf',0.0,'2026-07-14 22:27:35');
INSERT INTO `uploaded_docs` (`id`, `societe_id`, `doc_type`, `associe_idx`, `filename_original`, `filename_stored`, `filepath`, `taille_ko`, `uploaded_at`) VALUES (8,14,'cin_gerant',0,'2026-07-15_CIN_BENANI_Ahmed_KAMARAD.pdf','2026-07-15_CIN_BENANI_Ahmed_KAMARAD.pdf','D:\\SSD_2T\\04_Dev\\05_Programming Projects\\PHP_Projects\\Center-Domiciliation-App\\pages\\dossiers\\creation_steps/../../../dossiers_generer/dossiers_creation/2026-05-18_SARL_KAMARAD/_uploads/2026-07-15_CIN_BENANI_Ahmed_KAMARAD.pdf',0.0,'2026-07-14 22:27:35');
/*!40000 ALTER TABLE `uploaded_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` (`id`, `user_id`, `last_active`, `current_page`, `ip_address`, `user_agent`, `session_id`) VALUES (151,3,'2026-07-16 15:24:15','dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','h1ctunfpgmlk853jrmajie1020');
INSERT INTO `user_sessions` (`id`, `user_id`, `last_active`, `current_page`, `ip_address`, `user_agent`, `session_id`) VALUES (153,3,'2026-07-16 15:35:01','societes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','gnm17uj4hii34io857mqjlkkna');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-16 16:45:24
