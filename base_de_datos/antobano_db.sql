-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: antobano_db
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
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Hamburguesas','Hamburguesas artesanales de res, pollo y mixtas'),(2,'Perros Calientes','Perros calientes tradicionales y especiales con tocineta'),(3,'Salchipapas','Porciones personales y familiares con salsas de la casa'),(4,'Bebidas','Gaseosas, jugos naturales y limonadas'),(5,'helados','helados'),(6,'pasta','pastas');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `fk_detalle_pedido` (`id_pedido`),
  KEY `fk_detalle_producto` (`id_producto`),
  CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
INSERT INTO `detalle_pedido` VALUES (1,1,1,2,16000.00,32000.00),(2,1,5,1,4000.00,4000.00),(3,2,1,1,16000.00,16000.00),(4,2,5,1,4000.00,4000.00),(5,2,3,1,14000.00,14000.00),(6,3,1,1,16000.00,16000.00),(7,3,5,1,4000.00,4000.00),(8,3,3,1,14000.00,14000.00),(9,4,1,1,16000.00,16000.00),(10,5,1,1,16000.00,16000.00),(11,5,5,1,4000.00,4000.00),(12,5,2,2,23000.00,46000.00),(13,5,3,1,14000.00,14000.00),(14,5,4,1,18500.00,18500.00),(15,6,5,3,4000.00,12000.00),(16,7,1,2,16000.00,32000.00),(17,7,5,1,4000.00,4000.00),(18,8,6,1,15000.00,15000.00),(19,8,5,1,4000.00,4000.00),(20,9,2,1,23000.00,23000.00),(21,9,3,1,14000.00,14000.00),(22,9,5,1,4000.00,4000.00),(23,10,7,16,20000.00,320000.00);
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_domiciliario` int(11) DEFAULT NULL,
  `direccion_entrega` varchar(200) NOT NULL,
  `metodo_pago` enum('efectivo','nequi','daviplata','tarjeta') NOT NULL DEFAULT 'efectivo',
  `estado` enum('pendiente','preparando','en_camino','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pedido`),
  KEY `fk_pedido_cliente` (`id_cliente`),
  KEY `fk_pedido_domiciliario` (`id_domiciliario`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_domiciliario` FOREIGN KEY (`id_domiciliario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,1,3,'Calle 10 # 4-25, Centro','nequi','entregado',36000.00,'2026-11-18 21:16:22'),(2,9,NULL,'Av. Libertadores # 15-40, Cúcuta','efectivo','pendiente',34000.00,'2026-11-23 01:11:21'),(3,9,NULL,'Av. Libertadores # 15-40, Cúcuta','efectivo','pendiente',34000.00,'2026-11-23 01:11:51'),(4,7,3,'Av. Libertadores # 15-40, Cúcuta','nequi','entregado',16000.00,'2026-11-23 01:31:24'),(5,3,NULL,'Av. Libertadores # 15-40, Cúcuta','tarjeta','preparando',98500.00,'2026-11-23 01:52:42'),(6,7,3,'Av. Libertadores # 15-40, Cúcuta','nequi','entregado',12000.00,'2026-11-23 01:58:56'),(7,7,3,'Av. Libertadores # 15-40, Cúcuta','tarjeta','entregado',36000.00,'2026-11-23 02:14:36'),(8,7,3,'Av. Libertadores # 15-40, Cúcuta','tarjeta','entregado',19000.00,'2026-11-23 03:31:21'),(9,7,3,'Av. Libertadores # 15-40, Cúcuta','efectivo','entregado',41000.00,'2026-11-23 03:42:21'),(10,7,NULL,'Av. Libertadores # 15-40, Cúcuta','efectivo','pendiente',320000.00,'2026-11-23 03:47:28');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto`),
  KEY `fk_producto_categoria` (`id_categoria`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,'Hamburguesa Clásica AntoBano','Carne artesanal 150g, queso doble crema, lechuga, tomate y salsa especial',NULL,16000.00,1),(2,1,'Hamburguesa Doble Tocineta','Doble carne, doble queso cheddar, tocineta ahumada y cebolla caramelizada',NULL,23000.00,1),(3,2,'Perro Especial de la Casa','Salchicha americana, queso fundido, ripio de papa, piña y tocineta',NULL,14000.00,1),(4,3,'Salchipapa Mixta Personal','Papa a la francesa, salchicha ranchera, pollo desmechado y queso costeño',NULL,18500.00,1),(5,4,'Gaseosa 400ml','Bebida refrescante sabor cola o naranja',NULL,4000.00,1),(6,5,'banana split','3 helados dos bananas','plato_bb9d27b611d62af2.jpg',15000.00,1),(7,6,'penne','pasta penne, queso rayado, peregil','plato_763f33afdb5c3783.jpg',20000.00,1);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `direccion_principal` varchar(200) DEFAULT NULL,
  `rol` enum('cliente','domiciliario','administrador') NOT NULL DEFAULT 'cliente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Carlos Pérez','carlos@gmail.com','3101234567','hash_clave_123','Calle 10 # 4-25, Centro','cliente','2026-11-18 21:16:17'),(2,'Laura Gómez','laura@hotmail.com','3159876543','hash_clave_456','Av. Libertadores # 15-40','cliente','2026-11-18 21:16:17'),(3,'Mateo Rodríguez','mateo.reparto@antobano.com','3205551122','$2y$10$MEigeTw2M7.6SyrmpxU53OAVCUPqrGfhWWdMGDBhH/1d5xoGykVOe',NULL,'domiciliario','2026-11-18 21:16:17'),(4,'Admin AntoBano','admin@antobano.com','3001112233','$2y$10$MEigeTw2M7.6SyrmpxU53OAVCUPqrGfhWWdMGDBhH/1d5xoGykVOe',NULL,'administrador','2026-11-18 21:16:17'),(5,'Prueba Linux','prueba@antobano.com',NULL,'$2y$10$1ubormGoc10zf6yUlWW9Yu9o19VQbOru4t3XxDMV0MVzi8lDbdxuW',NULL,'cliente','2026-11-23 00:37:49'),(6,'andres moncada','120508andres@gmail.com',NULL,'$2y$10$ljeG/XWB1L6ChJam7wy0UOdJ6NWlKfW68iZPZN9LjZ3zCbSu84Nsq',NULL,'cliente','2026-11-23 00:41:34'),(7,'andres moncada','120508andresmoncada@gmail.com',NULL,'$2y$10$Ffj.cGjixQQMmLd6Qfbpau/z29CT61YjqOsneXqqWV0jbk4jXyhYS',NULL,'cliente','2026-11-23 00:43:29'),(8,'jun carvajal','123sads@gmail.com',NULL,'$2y$10$DR0HlPYod7sNOAmWI0xoduRvGWygHSaVUOsdAs/1ku4TR/Qb.ng1e',NULL,'cliente','2026-11-23 00:46:28'),(9,'juan perez','120508an@gmail.com',NULL,'$2y$10$qfkuOZxTJ2TALHB/6jPWSOfzNJ8mg3fbchjx/7gYRPdI3W2eGj2fW',NULL,'cliente','2026-11-23 01:11:12'),(10,'juan perez','12345@gmail.com',NULL,'$2y$10$OejtipbyvumH9tz1zOVnj.JUSkdDdkkRBOZw0q00/CENChLO5LG3i',NULL,'cliente','2026-11-23 02:09:56');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-22 22:55:23
