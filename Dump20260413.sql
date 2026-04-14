-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: task_manager
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `Id` char(36) NOT NULL,
  `Folio` varchar(20) NOT NULL,
  `StageId` char(36) DEFAULT NULL,
  `DependsOnActivityId` varchar(36) DEFAULT NULL,
  `TemplateId` char(36) DEFAULT NULL,
  `RequesterId` char(36) NOT NULL,
  `RequesterDepartmentId` char(36) NOT NULL,
  `PrimaryDepartmentId` char(36) NOT NULL,
  `ResponsibleId` char(36) DEFAULT NULL,
  `Name` varchar(200) NOT NULL,
  `SpecificActionPlan` text DEFAULT NULL,
  `StatusId` int(11) NOT NULL,
  `ProgressPercentage` int(11) DEFAULT 0,
  `PriorityId` int(11) NOT NULL,
  `TaskTypeId` int(11) DEFAULT NULL,
  `ProjectId` varchar(36) DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `CommitmentDate` datetime DEFAULT NULL,
  `CompletedDate` datetime DEFAULT NULL,
  `RowVersion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `DeletedAt` datetime DEFAULT NULL,
  `DeletedBy` char(36) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Folio` (`Folio`),
  KEY `FK_Activities_Requester` (`RequesterId`),
  KEY `FK_Activities_ReqDept` (`RequesterDepartmentId`),
  KEY `FK_Activities_Departments` (`PrimaryDepartmentId`),
  KEY `FK_Activities_Users` (`ResponsibleId`),
  KEY `FK_Activities_Templates` (`TemplateId`),
  KEY `FK_Activities_Statuses` (`StatusId`),
  KEY `FK_Activities_Priorities` (`PriorityId`),
  KEY `FK_Act_Stage` (`StageId`),
  KEY `FK_Act_Project` (`ProjectId`),
  KEY `FK_Act_Depends` (`DependsOnActivityId`),
  CONSTRAINT `FK_Act_Depends` FOREIGN KEY (`DependsOnActivityId`) REFERENCES `activities` (`Id`),
  CONSTRAINT `FK_Act_Project` FOREIGN KEY (`ProjectId`) REFERENCES `projects` (`Id`),
  CONSTRAINT `FK_Act_Stage` FOREIGN KEY (`StageId`) REFERENCES `projectstages` (`Id`),
  CONSTRAINT `FK_Activities_Departments` FOREIGN KEY (`PrimaryDepartmentId`) REFERENCES `departments` (`Id`),
  CONSTRAINT `FK_Activities_Priorities` FOREIGN KEY (`PriorityId`) REFERENCES `priorities` (`Id`),
  CONSTRAINT `FK_Activities_ReqDept` FOREIGN KEY (`RequesterDepartmentId`) REFERENCES `departments` (`Id`),
  CONSTRAINT `FK_Activities_Requester` FOREIGN KEY (`RequesterId`) REFERENCES `users` (`Id`),
  CONSTRAINT `FK_Activities_Statuses` FOREIGN KEY (`StatusId`) REFERENCES `statuses` (`Id`),
  CONSTRAINT `FK_Activities_Templates` FOREIGN KEY (`TemplateId`) REFERENCES `activitytemplates` (`Id`),
  CONSTRAINT `FK_Activities_Users` FOREIGN KEY (`ResponsibleId`) REFERENCES `users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES ('06c37753-3780-11f1-af20-8c881b477a7f','TK-IT-9001',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb5e33e-3779-11f1-af20-8c881b477a7f','Diseño de topología de red Nave 3','Crear diagrama en Visio para la nueva expansión, contemplando 50 nodos.',3,60,3,NULL,NULL,'2026-04-10 00:00:00','2026-04-18 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c3845d-3780-11f1-af20-8c881b477a7f','TK-IT-9002',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb5e33e-3779-11f1-af20-8c881b477a7f','Renovación de licencias Cisco Meraki','Aprobar cotización y aplicar llaves en el dashboard.',5,100,3,1,NULL,'2026-03-25 00:00:00','2026-03-30 00:00:00','2026-03-28 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c3870c-3780-11f1-af20-8c881b477a7f','TK-IT-9003',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb62695-3779-11f1-af20-8c881b477a7f','Pentesting interno Q2','Ejecutar escaneo de vulnerabilidades en servidores de BD.',5,100,2,1,NULL,'2026-04-01 00:00:00','2026-04-05 00:00:00','2026-04-05 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c3896c-3780-11f1-af20-8c881b477a7f','TK-IT-9004',NULL,NULL,NULL,'ddf46ec1-2250-11f1-a380-8c881b477a7f','ddf39fa2-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb62695-3779-11f1-af20-8c881b477a7f','Investigar correo sospechoso en equipo de RH','RH reporta un correo que pide contraseñas. Aislar equipo y revisar logs.',4,100,3,2,NULL,'2026-04-12 00:00:00','2026-04-13 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c38b17-3780-11f1-af20-8c881b477a7f','TK-IT-9005',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddef72e6-2250-11f1-a380-8c881b477a7f','Actualización de Motor SQL Server','Aplicar parche acumulativo de seguridad en el servidor MES principal.',5,100,3,1,NULL,'2026-03-15 00:00:00','2026-03-20 00:00:00','2026-03-19 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c38c75-3780-11f1-af20-8c881b477a7f','TK-IT-9006',NULL,NULL,NULL,'ddf6c971-2250-11f1-a380-8c881b477a7f','ddf61c32-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddef72e6-2250-11f1-a380-8c881b477a7f','Error de sincronización en tabletas de inspección','Calidad reporta que las tabletas no guardan las fotos en el servidor.',3,30,3,2,NULL,'2026-04-12 00:00:00','2026-04-14 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c40862-3780-11f1-af20-8c881b477a7f','TK-IT-9007',NULL,NULL,NULL,'ddf2cfe0-2250-11f1-a380-8c881b477a7f','ddf0edbf-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Caída de switch en taller de Mantenimiento','Revisar switch principal del taller, parece haber perdido energía.',5,100,3,2,NULL,'2026-04-02 00:00:00','2026-04-02 00:00:00','2026-04-02 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c40b87-3780-11f1-af20-8c881b477a7f','TK-IT-9008',NULL,NULL,NULL,'ddf46ec1-2250-11f1-a380-8c881b477a7f','ddf39fa2-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Tirar cable UTP para nueva sala de entrevistas','Se requieren 2 nodos de red Cat6 para los nuevos escritorios de reclutamiento.',3,50,2,NULL,NULL,'2026-04-11 00:00:00','2026-04-15 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c40d7e-3780-11f1-af20-8c881b477a7f','TK-IT-9009',NULL,NULL,NULL,'9eb5e33e-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Peinado y ruteo de cables en Site Principal','Organizar cableado del Rack 2 con velcro y poner etiquetas.',2,0,1,1,NULL,'2026-04-15 00:00:00','2026-04-25 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c40eeb-3780-11f1-af20-8c881b477a7f','TK-IT-9010',NULL,NULL,NULL,'ddf2cfe0-2250-11f1-a380-8c881b477a7f','ddf0edbf-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Reemplazar AP inalámbrico dañado en Almacén','Montacargas golpeó antena WiFi. Instalar equipo de repuesto.',5,100,3,2,NULL,'2026-03-28 00:00:00','2026-03-29 00:00:00','2026-03-29 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c4103b-3780-11f1-af20-8c881b477a7f','TK-IT-9011',NULL,NULL,NULL,'ddf6c971-2250-11f1-a380-8c881b477a7f','ddf61c32-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Configurar VLAN aislada para Auditores Externos','Crear red WiFi temporal solo con salida a internet para ISO9001.',5,100,2,NULL,NULL,'2026-04-05 00:00:00','2026-04-07 00:00:00','2026-04-06 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c4118a-3780-11f1-af20-8c881b477a7f','TK-IT-9012',NULL,NULL,NULL,'9eb5e33e-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Limpieza física de gabinetes IDF-3','Aspirar polvo de switches y revisar ventiladores.',3,10,1,1,NULL,'2026-04-12 00:00:00','2026-04-16 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c412cd-3780-11f1-af20-8c881b477a7f','TK-IT-9013',NULL,NULL,NULL,'9eb62695-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Bloqueo de IPs de ataque DDoS','Ingresar al firewall perimetral y añadir lista negra de IPs de Rusia.',5,100,3,2,NULL,'2026-04-08 00:00:00','2026-04-08 00:00:00','2026-04-08 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c413fe-3780-11f1-af20-8c881b477a7f','TK-IT-9014',NULL,NULL,NULL,'ddf46ec1-2250-11f1-a380-8c881b477a7f','ddf39fa2-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Baja de accesos de ex-empleados masiva','Inhabilitar cuentas de AD y Office 365 de la lista proporcionada por RH.',5,100,2,1,NULL,'2026-04-01 00:00:00','2026-04-02 00:00:00','2026-04-02 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c41537-3780-11f1-af20-8c881b477a7f','TK-IT-9015',NULL,NULL,NULL,'9eb62695-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Configurar alertas SMS de login fallido','Crear regla en SIEM para avisar a gerencia si hay >5 intentos de admin.',3,40,2,NULL,NULL,'2026-04-13 00:00:00','2026-04-17 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c41669-3780-11f1-af20-8c881b477a7f','TK-IT-9016',NULL,NULL,NULL,'ddf6c971-2250-11f1-a380-8c881b477a7f','ddf61c32-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7adf6-3779-11f1-af20-8c881b477a7f','Desinfectar laptop de Inspector de Calidad','Alerta de Windows Defender por USB infectada. Limpiar y generar reporte.',5,100,3,2,NULL,'2026-03-20 00:00:00','2026-03-20 00:00:00','2026-03-20 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c41799-3780-11f1-af20-8c881b477a7f','TK-IT-9017',NULL,NULL,NULL,'9eb62695-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7adf6-3779-11f1-af20-8c881b477a7f','Actualizar firmas de antivirus off-line','Llevar actualización a equipos de piso de producción sin internet.',5,100,2,1,NULL,'2026-04-02 00:00:00','2026-04-05 00:00:00','2026-04-04 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c418cc-3780-11f1-af20-8c881b477a7f','TK-IT-9018',NULL,NULL,NULL,'9eb62695-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7adf6-3779-11f1-af20-8c881b477a7f','Monitoreo de tráfico P2P sospechoso','Analizar logs semanales en busca de descargas no autorizadas.',2,0,1,1,NULL,'2026-04-14 00:00:00','2026-04-18 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('06c419f5-3780-11f1-af20-8c881b477a7f','TK-IT-9019',NULL,NULL,NULL,'ddf2cfe0-2250-11f1-a380-8c881b477a7f','ddf0edbf-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','a51ddb74-b921-4a67-a793-15173304db1d','Formatear PC de Taller de Mantenimiento','Equipo súper lento, respaldar planos de AutoCAD y reinstalar Windows.',5,100,2,2,NULL,'2026-04-01 00:00:00','2026-04-03 00:00:00','2026-04-02 00:00:00','2026-04-13 14:30:43',0,NULL,NULL),('06c41b20-3780-11f1-af20-8c881b477a7f','TK-IT-9020',NULL,NULL,NULL,'ddef72e6-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','a51ddb74-b921-4a67-a793-15173304db1d','Mantenimiento preventivo a impresoras térmicas Zebra','Limpiar cabezales y calibrar sensores de las 5 impresoras de embarques.',3,60,2,1,NULL,'2026-04-10 00:00:00','2026-04-15 00:00:00',NULL,'2026-04-13 14:30:43',0,NULL,NULL),('55298aaf-3781-11f1-af20-8c881b477a7f','RUT-40001',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Revisión de temperatura del Site Principal - 12 Abr',NULL,5,100,3,1,NULL,'2026-04-12 00:00:00','2026-04-12 00:00:00','2026-04-12 10:00:00','2026-04-13 14:40:04',0,NULL,NULL),('55299b88-3781-11f1-af20-8c881b477a7f','RUT-40002',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Verificación de respaldos nocturnos - 12 Abr',NULL,5,100,3,1,NULL,'2026-04-12 00:00:00','2026-04-12 00:00:00','2026-04-12 08:30:00','2026-04-13 14:40:04',0,NULL,NULL),('55299d85-3781-11f1-af20-8c881b477a7f','RUT-40003',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Reinicio preventivo de Print Servers - 11 Abr',NULL,5,100,2,1,NULL,'2026-04-11 00:00:00','2026-04-11 00:00:00','2026-04-11 23:45:00','2026-04-13 14:40:04',0,NULL,NULL),('55299ed2-3781-11f1-af20-8c881b477a7f','RUT-40004',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Limpieza de caché en servidores Edge - Sem 14',NULL,5,100,2,1,NULL,'2026-04-06 00:00:00','2026-04-06 00:00:00','2026-04-06 14:00:00','2026-04-13 14:40:04',0,NULL,NULL),('5529a00b-3781-11f1-af20-8c881b477a7f','RUT-40005',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Auditoría de cuentas AD inactivas - Sem 13',NULL,5,100,3,1,NULL,'2026-03-30 00:00:00','2026-03-30 00:00:00','2026-03-30 11:20:00','2026-04-13 14:40:04',0,NULL,NULL),('5529a34e-3781-11f1-af20-8c881b477a7f','RUT-40006',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Desfragmentación de discos SAN - Sem 14',NULL,5,100,1,1,NULL,'2026-04-07 00:00:00','2026-04-07 00:00:00','2026-04-07 02:15:00','2026-04-13 14:40:04',0,NULL,NULL),('5529a49a-3781-11f1-af20-8c881b477a7f','RUT-40007',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Simulacro de falla eléctrica (UPS) - Marzo',NULL,5,100,3,1,NULL,'2026-03-15 00:00:00','2026-03-15 00:00:00','2026-03-15 15:00:00','2026-04-13 14:40:04',0,NULL,NULL),('5529a6e3-3781-11f1-af20-8c881b477a7f','RUT-40008',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Revisión de caducidad de certificados SSL - Marzo',NULL,5,100,2,1,NULL,'2026-03-01 00:00:00','2026-03-01 00:00:00','2026-03-01 09:10:00','2026-04-13 14:40:04',0,NULL,NULL),('5529a819-3781-11f1-af20-8c881b477a7f','RUT-40009',NULL,NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Parcheo de SO en servidores críticos - Febrero',NULL,5,100,3,1,NULL,'2026-02-28 00:00:00','2026-02-28 00:00:00','2026-02-28 01:30:00','2026-04-13 14:40:04',0,NULL,NULL),('6b43ee13-377f-11f1-af20-8c881b477a7f','PRJ-TSK-10011','6b431300-377f-11f1-af20-8c881b477a7f',NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Tarea 1: Respaldar DB local','Backup full de SQL',5,100,3,NULL,'6b417955-377f-11f1-af20-8c881b477a7f','2026-01-10 00:00:00','2026-01-20 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b4467a6-377f-11f1-af20-8c881b477a7f','PRJ-TSK-10012','6b431300-377f-11f1-af20-8c881b477a7f',NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Tarea 2: Configurar VPC en AWS','Abrir puertos y VPN',5,100,3,NULL,'6b417955-377f-11f1-af20-8c881b477a7f','2026-01-21 00:00:00','2026-02-15 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b47441e-377f-11f1-af20-8c881b477a7f','PRJ-TSK-20021','6b462e67-377f-11f1-af20-8c881b477a7f',NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Tarea 1: Escaneo de red',NULL,5,100,2,NULL,'6b44dbbd-377f-11f1-af20-8c881b477a7f','2026-02-20 00:00:00','2026-03-05 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b475d14-377f-11f1-af20-8c881b477a7f','PRJ-TSK-20022','6b462e67-377f-11f1-af20-8c881b477a7f',NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','Tarea 2: Cierre de puertos',NULL,5,100,2,NULL,'6b44dbbd-377f-11f1-af20-8c881b477a7f','2026-03-06 00:00:00','2026-03-20 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b4c0844-377f-11f1-af20-8c881b477a7f','PRJ-TSK-30031','6b497359-377f-11f1-af20-8c881b477a7f',NULL,NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','a51ddb74-b921-4a67-a793-15173304db1d','Tarea 1: Instalación de servidor principal',NULL,5,100,3,NULL,'6b47e091-377f-11f1-af20-8c881b477a7f','2026-03-01 00:00:00','2026-03-15 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b4cdc5d-377f-11f1-af20-8c881b477a7f','PRJ-TSK-30032','6b497359-377f-11f1-af20-8c881b477a7f','6b4c0844-377f-11f1-af20-8c881b477a7f',NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','Tarea 2: Configuración de red para servidor',NULL,5,100,3,NULL,'6b47e091-377f-11f1-af20-8c881b477a7f','2026-03-16 00:00:00','2026-03-30 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b4d9aa7-377f-11f1-af20-8c881b477a7f','PRJ-TSK-30033','6b4a47b9-377f-11f1-af20-8c881b477a7f','6b4cdc5d-377f-11f1-af20-8c881b477a7f',NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','Tarea 3: Auditoría de seguridad del nuevo nodo',NULL,4,100,3,NULL,'6b47e091-377f-11f1-af20-8c881b477a7f','2026-04-01 00:00:00','2026-04-15 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b4ec0a3-377f-11f1-af20-8c881b477a7f','PRJ-TSK-30034','6b4a47b9-377f-11f1-af20-8c881b477a7f','6b4d9aa7-377f-11f1-af20-8c881b477a7f',NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddf0edbf-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,'Tarea 4: Instalar terminales físicas en piso','Mantenimiento debe instalar los brazos metálicos',2,0,2,NULL,'6b47e091-377f-11f1-af20-8c881b477a7f','2026-04-16 00:00:00','2026-05-15 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL),('6b4fa462-377f-11f1-af20-8c881b477a7f','PRJ-TSK-30035','6b4b1967-377f-11f1-af20-8c881b477a7f','6b4ec0a3-377f-11f1-af20-8c881b477a7f',NULL,'9eb5a834-3779-11f1-af20-8c881b477a7f','ddf39fa2-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,'Tarea 5: Agendar sesiones de capacitación',NULL,2,0,2,NULL,'6b47e091-377f-11f1-af20-8c881b477a7f','2026-05-16 00:00:00','2026-06-30 00:00:00',NULL,'2026-04-13 14:26:22',0,NULL,NULL);
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activitycomments`
--

DROP TABLE IF EXISTS `activitycomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activitycomments` (
  `Id` char(36) NOT NULL,
  `ActivityId` char(36) NOT NULL,
  `UserId` char(36) NOT NULL,
  `CommentText` text NOT NULL,
  `ImagePath` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `FK_Comments_Activities` (`ActivityId`),
  KEY `FK_Comments_Users` (`UserId`),
  CONSTRAINT `FK_Comments_Activities` FOREIGN KEY (`ActivityId`) REFERENCES `activities` (`Id`),
  CONSTRAINT `FK_Comments_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activitycomments`
--

LOCK TABLES `activitycomments` WRITE;
/*!40000 ALTER TABLE `activitycomments` DISABLE KEYS */;
INSERT INTO `activitycomments` VALUES ('06c48af8-3780-11f1-af20-8c881b477a7f','06c40b87-3780-11f1-af20-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','AVANCE 50%: Ya tiré el cable por el plafón, me falta ponchar los jacks en la pared pero se me terminaron los conectores.',NULL,'2026-04-12 14:30:00'),('06c4f83d-3780-11f1-af20-8c881b477a7f','06c412cd-3780-11f1-af20-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','AVANCE 100%: Direcciones agregadas a la regla \"Drop_GeoIP\" en el Fortinet. El tráfico malicioso ya cayó a cero.',NULL,'2026-04-08 10:15:00'),('552a267f-3781-11f1-af20-8c881b477a7f','55298aaf-3781-11f1-af20-8c881b477a7f','9eb79b77-3779-11f1-af20-8c881b477a7f','VISTO BUENO / CIERRE: Temperatura estable en 18.5°C. Sin alarmas en el panel.',NULL,'2026-04-12 10:00:00'),('552a31a0-3781-11f1-af20-8c881b477a7f','5529a00b-3781-11f1-af20-8c881b477a7f','9eb7abd0-3779-11f1-af20-8c881b477a7f','VISTO BUENO / CIERRE: Se inhabilitaron 4 cuentas de ex-empleados y contratistas.',NULL,'2026-03-30 11:20:00'),('552a32e9-3781-11f1-af20-8c881b477a7f','5529a819-3781-11f1-af20-8c881b477a7f','9eb7ad47-3779-11f1-af20-8c881b477a7f','VISTO BUENO / CIERRE: Parches KB5034441 instalados. Servidores reiniciados correctamente sin downtime prolongado.',NULL,'2026-02-28 01:30:00');
/*!40000 ALTER TABLE `activitycomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activitytemplates`
--

DROP TABLE IF EXISTS `activitytemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activitytemplates` (
  `Id` char(36) NOT NULL,
  `PrimaryDepartmentId` char(36) NOT NULL,
  `Name` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `SpecificActionPlan` text DEFAULT NULL,
  `PriorityId` int(11) NOT NULL,
  `DefaultTaskTypeId` int(11) DEFAULT 1,
  `RecurrenceType` varchar(50) DEFAULT NULL,
  `TargetShifts` varchar(255) DEFAULT NULL,
  `NextRunDate` date DEFAULT NULL,
  `CronExpression` varchar(50) NOT NULL,
  `DefaultResponsibleId` char(36) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`Id`),
  KEY `FK_ActivityTemplates_Departments` (`PrimaryDepartmentId`),
  KEY `FK_ActivityTemplates_Users` (`DefaultResponsibleId`),
  KEY `FK_ActivityTemplates_Priorities` (`PriorityId`),
  CONSTRAINT `FK_ActivityTemplates_Departments` FOREIGN KEY (`PrimaryDepartmentId`) REFERENCES `departments` (`Id`),
  CONSTRAINT `FK_ActivityTemplates_Priorities` FOREIGN KEY (`PriorityId`) REFERENCES `priorities` (`Id`),
  CONSTRAINT `FK_ActivityTemplates_Users` FOREIGN KEY (`DefaultResponsibleId`) REFERENCES `users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activitytemplates`
--

LOCK TABLES `activitytemplates` WRITE;
/*!40000 ALTER TABLE `activitytemplates` DISABLE KEYS */;
INSERT INTO `activitytemplates` VALUES ('552076fa-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Revisión de temperatura del Site Principal',NULL,'Verificar que el aire acondicionado esté en 18°C y no haya alarmas en el panel.',3,1,'Diaria','Todos los turnos','2026-04-14','',NULL,1),('5521bad5-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Verificación de respaldos nocturnos',NULL,'Entrar a Veeam Backup y confirmar que los jobs de las 3:00 AM terminaron con Success.',3,1,'Diaria','Turno Día (12h)','2026-04-14','',NULL,1),('5522ab00-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Reinicio preventivo de Print Servers',NULL,'Reiniciar servicios de spooler en los 3 servidores de impresión para liberar memoria.',2,1,'Diaria','Turno Noche (12h)','2026-04-14','',NULL,1),('552382f2-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Limpieza de caché en servidores Edge',NULL,'Ejecutar script de limpieza de temporales en los servidores DMZ.',2,1,'Semanal','Todos los turnos','2026-04-20','',NULL,1),('55245b5b-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Auditoría de cuentas AD inactivas',NULL,'Generar reporte de usuarios de Active Directory sin login en 30 días y deshabilitar.',3,1,'Semanal','Turno Día (12h)','2026-04-20','',NULL,1),('55254a89-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Desfragmentación de discos SAN',NULL,'Programar y monitorear tarea de optimización de almacenamiento en la SAN principal.',1,1,'Semanal','Turno Noche (12h)','2026-04-20','',NULL,1),('552628c4-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Simulacro de falla eléctrica (UPS)',NULL,'Cortar energía comercial al rack 1 y cronometrar tiempo de entrada de baterías.',3,1,'Mensual','Todos los turnos','2026-05-13','',NULL,1),('55272584-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Revisión de caducidad de certificados SSL',NULL,'Ingresar al portal de GoDaddy y renovar certificados que venzan en < 30 días.',2,1,'Mensual','Turno Día (12h)','2026-05-13','',NULL,1),('55285c75-3781-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','Parcheo de SO en servidores críticos',NULL,'Aplicar Windows Updates a servidores ERP y reiniciar equipos uno por uno.',3,1,'Mensual','Turno Noche (12h)','2026-05-13','',NULL,1);
/*!40000 ALTER TABLE `activitytemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditlogs`
--

DROP TABLE IF EXISTS `auditlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditlogs` (
  `Id` bigint(20) NOT NULL AUTO_INCREMENT,
  `UserId` char(36) DEFAULT NULL,
  `ActionType` varchar(20) NOT NULL,
  `TableName` varchar(50) NOT NULL,
  `RecordId` char(36) NOT NULL,
  `OldValues` text DEFAULT NULL,
  `NewValues` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditlogs`
--

LOCK TABLES `auditlogs` WRITE;
/*!40000 ALTER TABLE `auditlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `auditlogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `Id` char(36) NOT NULL,
  `PlantId` char(36) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  KEY `FK_Departments_Plants` (`PlantId`),
  CONSTRAINT `FK_Departments_Plants` FOREIGN KEY (`PlantId`) REFERENCES `plants` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES ('ddeea870-2250-11f1-a380-8c881b477a7f','ddede862-2250-11f1-a380-8c881b477a7f','IT',0),('ddf0edbf-2250-11f1-a380-8c881b477a7f','ddede862-2250-11f1-a380-8c881b477a7f','Mantenimiento',0),('ddf39fa2-2250-11f1-a380-8c881b477a7f','ddede862-2250-11f1-a380-8c881b477a7f','Recursos Humanos',0),('ddf61c32-2250-11f1-a380-8c881b477a7f','ddede862-2250-11f1-a380-8c881b477a7f','Calidad',0);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `Id` bigint(20) NOT NULL AUTO_INCREMENT,
  `UserId` char(36) NOT NULL,
  `ActivityId` char(36) DEFAULT NULL,
  `Message` varchar(255) NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `FK_Notifications_Users` (`UserId`),
  KEY `FK_Notifications_Activities` (`ActivityId`),
  CONSTRAINT `FK_Notifications_Activities` FOREIGN KEY (`ActivityId`) REFERENCES `activities` (`Id`),
  CONSTRAINT `FK_Notifications_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plants`
--

DROP TABLE IF EXISTS `plants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plants` (
  `Id` char(36) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `DeletedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plants`
--

LOCK TABLES `plants` WRITE;
/*!40000 ALTER TABLE `plants` DISABLE KEYS */;
INSERT INTO `plants` VALUES ('ddede862-2250-11f1-a380-8c881b477a7f','Planta Matriz InnoviTech',0,NULL);
/*!40000 ALTER TABLE `plants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `priorities`
--

DROP TABLE IF EXISTS `priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `priorities` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `priorities`
--

LOCK TABLES `priorities` WRITE;
/*!40000 ALTER TABLE `priorities` DISABLE KEYS */;
INSERT INTO `priorities` VALUES (3,'Alta'),(1,'Baja'),(4,'Crítica'),(2,'Media');
/*!40000 ALTER TABLE `priorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projectleaders`
--

DROP TABLE IF EXISTS `projectleaders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projectleaders` (
  `ProjectId` varchar(36) NOT NULL,
  `UserId` varchar(36) NOT NULL,
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ProjectId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `projectleaders_ibfk_1` FOREIGN KEY (`ProjectId`) REFERENCES `projects` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `projectleaders_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projectleaders`
--

LOCK TABLES `projectleaders` WRITE;
/*!40000 ALTER TABLE `projectleaders` DISABLE KEYS */;
INSERT INTO `projectleaders` VALUES ('6b417955-377f-11f1-af20-8c881b477a7f','9eb5a834-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b417955-377f-11f1-af20-8c881b477a7f','9eb5e33e-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b417955-377f-11f1-af20-8c881b477a7f','9eb62695-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b417955-377f-11f1-af20-8c881b477a7f','ddef72e6-2250-11f1-a380-8c881b477a7f','2026-04-13 21:26:22'),('6b44dbbd-377f-11f1-af20-8c881b477a7f','9eb5a834-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b44dbbd-377f-11f1-af20-8c881b477a7f','9eb5e33e-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b44dbbd-377f-11f1-af20-8c881b477a7f','9eb62695-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b44dbbd-377f-11f1-af20-8c881b477a7f','ddef72e6-2250-11f1-a380-8c881b477a7f','2026-04-13 21:26:22'),('6b47e091-377f-11f1-af20-8c881b477a7f','9eb5a834-3779-11f1-af20-8c881b477a7f','2026-04-13 21:26:22'),('6b47e091-377f-11f1-af20-8c881b477a7f','ddf1ad22-2250-11f1-a380-8c881b477a7f','2026-04-13 21:26:22'),('6b47e091-377f-11f1-af20-8c881b477a7f','ddf46ec1-2250-11f1-a380-8c881b477a7f','2026-04-13 21:26:22');
/*!40000 ALTER TABLE `projectleaders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `Id` varchar(36) NOT NULL,
  `Folio` varchar(50) NOT NULL,
  `Name` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `Objective` text DEFAULT NULL,
  `ExpectedResult` text DEFAULT NULL,
  `PrimaryDepartmentId` varchar(36) NOT NULL,
  `StatusId` int(11) NOT NULL DEFAULT 2,
  `PriorityId` int(11) NOT NULL,
  `StartDate` date DEFAULT NULL,
  `CommitmentDate` date DEFAULT NULL,
  `CompletedDate` datetime DEFAULT NULL,
  `ProgressPercentage` int(11) DEFAULT 0,
  `RowVersion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsDeleted` tinyint(1) DEFAULT 0,
  `DeletedAt` datetime DEFAULT NULL,
  `DeletedBy` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Folio` (`Folio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES ('6b417955-377f-11f1-af20-8c881b477a7f','PRJ-2601-1001','Migración de Servidores a AWS','Movimiento de infraestructura local a la nube.','Reducir costos operativos.','Infraestructura 100% en la nube','ddeea870-2250-11f1-a380-8c881b477a7f',5,3,'2026-01-10','2026-02-15','2026-02-14 00:00:00',100,'2026-04-13 21:26:22',0,NULL,NULL),('6b44dbbd-377f-11f1-af20-8c881b477a7f','PRJ-2602-2002','Auditoría Interna SOC 2026','Revisión de políticas y pentesting.','Detectar vulnerabilidades.','Reporte cero brechas críticas','ddeea870-2250-11f1-a380-8c881b477a7f',5,2,'2026-02-20','2026-03-20','2026-03-18 00:00:00',100,'2026-04-13 21:26:22',0,NULL,NULL),('6b47e091-377f-11f1-af20-8c881b477a7f','PRJ-2603-3003','Implementación de SAP S/4HANA','Despliegue del nuevo ERP para toda la planta.','Unificar las finanzas, RH y producción.','Go-Live Exitoso sin detener producción','ddeea870-2250-11f1-a380-8c881b477a7f',3,3,'2026-03-01','2026-06-30',NULL,50,'2026-04-13 21:27:09',0,NULL,NULL);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projectstages`
--

DROP TABLE IF EXISTS `projectstages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projectstages` (
  `Id` varchar(36) NOT NULL,
  `ProjectId` varchar(36) NOT NULL,
  `OrderIndex` int(11) NOT NULL,
  `Name` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `AcceptanceCriteria` text DEFAULT NULL,
  `StatusId` int(11) NOT NULL DEFAULT 2,
  `StartDate` date DEFAULT NULL,
  `CommitmentDate` date DEFAULT NULL,
  `CompletedDate` datetime DEFAULT NULL,
  `ProgressPercentage` int(11) DEFAULT 0,
  `RowVersion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IsDeleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`Id`),
  KEY `ProjectId` (`ProjectId`),
  CONSTRAINT `projectstages_ibfk_1` FOREIGN KEY (`ProjectId`) REFERENCES `projects` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projectstages`
--

LOCK TABLES `projectstages` WRITE;
/*!40000 ALTER TABLE `projectstages` DISABLE KEYS */;
INSERT INTO `projectstages` VALUES ('6b431300-377f-11f1-af20-8c881b477a7f','6b417955-377f-11f1-af20-8c881b477a7f',1,'Fase Única: Migración','Ejecución del movimiento',NULL,5,'2026-01-10','2026-02-15',NULL,100,'2026-04-13 21:26:22',0),('6b462e67-377f-11f1-af20-8c881b477a7f','6b44dbbd-377f-11f1-af20-8c881b477a7f',1,'Auditoría',NULL,NULL,5,'2026-02-20','2026-03-20',NULL,100,'2026-04-13 21:26:22',0),('6b497359-377f-11f1-af20-8c881b477a7f','6b47e091-377f-11f1-af20-8c881b477a7f',1,'Fase 1: Levantamiento y Servidores',NULL,NULL,5,'2026-03-01','2026-03-30',NULL,100,'2026-04-13 21:26:22',0),('6b4a47b9-377f-11f1-af20-8c881b477a7f','6b47e091-377f-11f1-af20-8c881b477a7f',2,'Fase 2: Mapeo de Procesos y Nodos',NULL,NULL,3,'2026-04-01','2026-05-15',NULL,50,'2026-04-13 21:26:22',0),('6b4b1967-377f-11f1-af20-8c881b477a7f','6b47e091-377f-11f1-af20-8c881b477a7f',3,'Fase 3: Capacitación a Usuarios',NULL,NULL,2,'2026-05-16','2026-06-30',NULL,0,'2026-04-13 21:26:22',0);
/*!40000 ALTER TABLE `projectstages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  `Permissions` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrativo','{\"access\":\"all\"}'),(2,'Tecnico','{\"access\":\"execute_only\"}'),(3,'Auditor','{\"access\":\"read_only\"}');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shifts` (
  `Id` varchar(36) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `PatternDescription` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES ('7f273d04-36f3-11f1-85ae-8c881b477a7f','Turno Día (12h)','06:00:00','18:00:00','Esquema 4x4. De 6:00 AM a 6:00 PM.',1),('7f2746e4-36f3-11f1-85ae-8c881b477a7f','Turno Noche (12h)','18:00:00','06:00:00','Esquema 4x3. De 6:00 PM a 6:00 AM.',1);
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statuses`
--

DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statuses`
--

LOCK TABLES `statuses` WRITE;
/*!40000 ALTER TABLE `statuses` DISABLE KEYS */;
INSERT INTO `statuses` VALUES (6,'Cancelado'),(3,'En proceso'),(4,'En revisión'),(5,'Finalizado'),(2,'No iniciado'),(1,'Pendiente de Aprobación');
/*!40000 ALTER TABLE `statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `systemattachments`
--

DROP TABLE IF EXISTS `systemattachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `systemattachments` (
  `Id` char(36) NOT NULL,
  `EntityId` char(36) NOT NULL,
  `EntityType` varchar(50) NOT NULL,
  `UserId` char(36) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `FileExtension` varchar(10) NOT NULL,
  `StoragePath` varchar(500) NOT NULL,
  `UploadedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `FK_SystemAttachments_Users` (`UserId`),
  KEY `IX_SystemAttachments_Entity` (`EntityId`,`EntityType`),
  CONSTRAINT `FK_SystemAttachments_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `systemattachments`
--

LOCK TABLES `systemattachments` WRITE;
/*!40000 ALTER TABLE `systemattachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `systemattachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasktypes`
--

DROP TABLE IF EXISTS `tasktypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasktypes` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasktypes`
--

LOCK TABLES `tasktypes` WRITE;
/*!40000 ALTER TABLE `tasktypes` DISABLE KEYS */;
INSERT INTO `tasktypes` VALUES (1,'Mantenimiento Preventivo'),(2,'Mantenimiento Correctivo'),(3,'Proyecto');
/*!40000 ALTER TABLE `tasktypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `Id` char(36) NOT NULL,
  `DepartmentId` char(36) NOT NULL,
  `ShiftId` varchar(36) DEFAULT NULL,
  `RoleId` int(11) NOT NULL,
  `SupervisorId` char(36) DEFAULT NULL,
  `JobTitle` varchar(100) NOT NULL,
  `FullName` varchar(150) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Email` (`Email`),
  KEY `FK_Users_Departments` (`DepartmentId`),
  KEY `FK_Users_Roles` (`RoleId`),
  KEY `FK_User_Supervisor` (`SupervisorId`),
  CONSTRAINT `FK_User_Supervisor` FOREIGN KEY (`SupervisorId`) REFERENCES `users` (`Id`),
  CONSTRAINT `FK_Users_Departments` FOREIGN KEY (`DepartmentId`) REFERENCES `departments` (`Id`),
  CONSTRAINT `FK_Users_Roles` FOREIGN KEY (`RoleId`) REFERENCES `roles` (`Id`),
  CONSTRAINT `FK_Users_Supervisor` FOREIGN KEY (`SupervisorId`) REFERENCES `users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('695a9f4b-018d-4669-af7d-7390cd0137e9','ddf39fa2-2250-11f1-a380-8c881b477a7f',NULL,2,'ddf46ec1-2250-11f1-a380-8c881b477a7f','','Fabricio Fauna','fabricio.fauna@innovitech.edu.mx','$2y$10$lEBqQ/v4bcOkYPbDk7AYU.icmOG0fSLu1o2P39Vn0i8uqxQkeS.dS',1),('8adb93ec-e06e-4a55-a7e5-fecf472ece8b','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,3,NULL,'','Antonio Estrella','antonio.estrella@innovitech.com','$2y$10$EscvXym3cow56poI1F2leOg627tFtkUdFxLe0N..mjw6suhqQZTXu',1),('9eb5a834-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,1,NULL,'Gerente IT','Erick Apodaca','erickapodaca@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('9eb5e33e-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,1,'9eb5a834-3779-11f1-af20-8c881b477a7f','Senior Infraestructura de Red','Kevin Cervantes','kevincervantes@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('9eb62695-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,1,'9eb5a834-3779-11f1-af20-8c881b477a7f','Senior Ciberseguridad','Luis Torres','luistorres@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('9eb79b77-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,2,'9eb5e33e-3779-11f1-af20-8c881b477a7f','Técnico de Redes','Carlos Mendieta','cmendieta@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('9eb7abd0-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,2,'9eb5e33e-3779-11f1-af20-8c881b477a7f','Técnico de Redes','Ana Ruiz','aruiz@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('9eb7ad47-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,2,'9eb62695-3779-11f1-af20-8c881b477a7f','Analista SOC','Roberto Vega','rvega@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('9eb7adf6-3779-11f1-af20-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,2,'9eb62695-3779-11f1-af20-8c881b477a7f','Analista SOC','Sofia Larios','slarios@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('a51ddb74-b921-4a67-a793-15173304db1d','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,2,'ddef72e6-2250-11f1-a380-8c881b477a7f','','Manuel Angaspilco','manuel.angaspilco@innovitech.com','$2y$10$ccxw2OEvDBv2uwzhSFh6Y.MqHevgYRQml.Lwe8X3.neXvxIM.s9aK',1),('ddef72e6-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f',NULL,1,'9eb5a834-3779-11f1-af20-8c881b477a7f','Senior Desarrollo MES','Julio Aguilar','julio.aguilar@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf03b26-2250-11f1-a380-8c881b477a7f','ddeea870-2250-11f1-a380-8c881b477a7f','7f2746e4-36f3-11f1-85ae-8c881b477a7f',2,'ddef72e6-2250-11f1-a380-8c881b477a7f','','Luis Ramirez','luis.ramirez@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf1ad22-2250-11f1-a380-8c881b477a7f','ddf0edbf-2250-11f1-a380-8c881b477a7f',NULL,1,NULL,'','Roberto Gomez','roberto.gomez@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf2cfe0-2250-11f1-a380-8c881b477a7f','ddf0edbf-2250-11f1-a380-8c881b477a7f',NULL,2,'ddf1ad22-2250-11f1-a380-8c881b477a7f','','Juan Perez','juan.perez@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf46ec1-2250-11f1-a380-8c881b477a7f','ddf39fa2-2250-11f1-a380-8c881b477a7f',NULL,1,NULL,'','Laura Martinez','laura.martinez@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf561b2-2250-11f1-a380-8c881b477a7f','ddf39fa2-2250-11f1-a380-8c881b477a7f',NULL,2,'ddf46ec1-2250-11f1-a380-8c881b477a7f','','Ana Soto','ana.soto@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf6c971-2250-11f1-a380-8c881b477a7f','ddf61c32-2250-11f1-a380-8c881b477a7f',NULL,1,NULL,'','Sofia Castro','sofia.castro@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('ddf78a49-2250-11f1-a380-8c881b477a7f','ddf61c32-2250-11f1-a380-8c881b477a7f',NULL,2,'ddf6c971-2250-11f1-a380-8c881b477a7f','','Diego Luna','diego.luna@innovitech.com','$2y$10$vTZnoag5n5409UFgP9ub2OQ.1KdN9PrNMQontN.gAPgz0y.qU8LZS',1),('f29607ab-a90d-425d-9618-c4ec8385da16','ddeea870-2250-11f1-a380-8c881b477a7f','7f273d04-36f3-11f1-85ae-8c881b477a7f',2,'ddef72e6-2250-11f1-a380-8c881b477a7f','','Pedro Perez','pedro.perez@innovitech.com','$2y$10$XyEvKEE4/oYM9kbJybPDX.GKBAmMl4Fn16sjBluiVewWLfGkGoa2S',1);
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

-- Dump completed on 2026-04-13 15:14:31
