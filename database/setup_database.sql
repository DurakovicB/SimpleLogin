CREATE DATABASE  IF NOT EXISTS `simple_login` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `simple_login`;
-- MySQL dump 10.13  Distrib 8.0.33, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: simple_login
-- ------------------------------------------------------
-- Server version	8.0.33

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('deposit','withdraw') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,'deposit',500.00,'2025-04-09 13:07:55'),(2,1,'deposit',200.00,'2025-04-09 13:07:55'),(3,1,'withdraw',150.00,'2025-04-09 13:07:55'),(4,1,'deposit',300.00,'2025-04-09 13:07:55'),(5,1,'withdraw',100.00,'2025-04-09 13:07:55'),(6,1,'deposit',450.00,'2025-04-09 13:07:55'),(7,1,'withdraw',50.00,'2025-04-09 13:07:55'),(8,1,'deposit',120.00,'2025-04-09 13:07:55'),(9,1,'withdraw',300.00,'2025-04-09 13:07:55'),(10,1,'deposit',100.00,'2025-04-09 13:07:55'),(11,2,'deposit',1000.00,'2025-04-09 13:07:55'),(12,2,'deposit',300.00,'2025-04-09 13:07:55'),(13,2,'withdraw',250.00,'2025-04-09 13:07:55'),(14,2,'deposit',400.00,'2025-04-09 13:07:55'),(15,2,'withdraw',50.00,'2025-04-09 13:07:55'),(16,2,'deposit',200.00,'2025-04-09 13:07:55'),(17,2,'withdraw',350.00,'2025-04-09 13:07:55'),(18,2,'deposit',500.00,'2025-04-09 13:07:55'),(19,2,'withdraw',100.00,'2025-04-09 13:07:55'),(20,2,'deposit',600.00,'2025-04-09 13:07:55'),(21,3,'deposit',700.00,'2025-04-09 13:07:55'),(22,3,'deposit',450.00,'2025-04-09 13:07:55'),(23,3,'withdraw',200.00,'2025-04-09 13:07:55'),(24,3,'deposit',500.00,'2025-04-09 13:07:55'),(25,3,'withdraw',150.00,'2025-04-09 13:07:55'),(26,3,'deposit',700.00,'2025-04-09 13:07:55'),(27,3,'withdraw',250.00,'2025-04-09 13:07:55'),(28,3,'deposit',100.00,'2025-04-09 13:07:55'),(29,3,'withdraw',400.00,'2025-04-09 13:07:55'),(30,3,'deposit',350.00,'2025-04-09 13:07:55'),(31,4,'deposit',800.00,'2025-04-09 13:07:55'),(32,4,'deposit',600.00,'2025-04-09 13:07:55'),(33,4,'withdraw',350.00,'2025-04-09 13:07:55'),(34,4,'deposit',150.00,'2025-04-09 13:07:55'),(35,4,'withdraw',100.00,'2025-04-09 13:07:55'),(36,4,'deposit',200.00,'2025-04-09 13:07:55'),(37,4,'withdraw',500.00,'2025-04-09 13:07:55'),(38,4,'deposit',300.00,'2025-04-09 13:07:55'),(39,4,'withdraw',450.00,'2025-04-09 13:07:55'),(40,4,'deposit',600.00,'2025-04-09 13:07:55'),(41,5,'deposit',200.00,'2025-04-09 13:07:55'),(42,5,'deposit',500.00,'2025-04-09 13:07:55'),(43,5,'withdraw',150.00,'2025-04-09 13:07:55'),(44,5,'deposit',400.00,'2025-04-09 13:07:55'),(45,5,'withdraw',250.00,'2025-04-09 13:07:55'),(46,5,'deposit',700.00,'2025-04-09 13:07:55'),(47,5,'withdraw',350.00,'2025-04-09 13:07:55'),(48,5,'deposit',450.00,'2025-04-09 13:07:55'),(49,5,'withdraw',100.00,'2025-04-09 13:07:55'),(50,5,'deposit',600.00,'2025-04-09 13:07:55');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'alice_smith','alice.smith@example.com','$2y$10$PjFrNNeBzT/k.bslT1dqJu.sYDJ6Sxe5ntH9DEUTy7hHAYSABapGC','2025-04-09 13:05:37'),(2,'bob_jones','bob.jones@example.com','$2y$10$PjFrNNeBzT/k.bslT1dqJu.sYDJ6Sxe5ntH9DEUTy7hHAYSABapGC','2025-04-09 13:05:37'),(3,'carol_davis','carol.davis@example.com','$2y$10$PjFrNNeBzT/k.bslT1dqJu.sYDJ6Sxe5ntH9DEUTy7hHAYSABapGC','2025-04-09 13:05:37'),(4,'dave_white','dave.white@example.com','$2y$10$PjFrNNeBzT/k.bslT1dqJu.sYDJ6Sxe5ntH9DEUTy7hHAYSABapGC','2025-04-09 13:05:37'),(5,'eve_black','eve.black@example.com','$2y$10$PjFrNNeBzT/k.bslT1dqJu.sYDJ6Sxe5ntH9DEUTy7hHAYSABapGC','2025-04-09 13:05:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-14 10:14:26
