-- MySQL dump 10.13  Distrib 5.5.34, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: rut_opdata
-- ------------------------------------------------------
-- Server version	5.5.34-0ubuntu0.12.04.1

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
-- Table structure for table `MasterSuperDepts`
--

DROP TABLE IF EXISTS `MasterSuperDepts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MasterSuperDepts` (
  `superID` int(4) NOT NULL,
  `super_name` varchar(50) DEFAULT NULL,
  `dept_ID` smallint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`superID`,`dept_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MasterSuperDepts`
--

LOCK TABLES `MasterSuperDepts` WRITE;
/*!40000 ALTER TABLE `MasterSuperDepts` DISABLE KEYS */;
/*!40000 ALTER TABLE `MasterSuperDepts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autoCoupons`
--

DROP TABLE IF EXISTS `autoCoupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoCoupons` (
  `coupID` int(11) NOT NULL DEFAULT '0',
  `description` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`coupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autoCoupons`
--

LOCK TABLES `autoCoupons` WRITE;
/*!40000 ALTER TABLE `autoCoupons` DISABLE KEYS */;
INSERT INTO `autoCoupons` VALUES (1,'Supplement Discount');
/*!40000 ALTER TABLE `autoCoupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `couponcodes`
--

DROP TABLE IF EXISTS `couponcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `couponcodes` (
  `Code` varchar(4) NOT NULL DEFAULT '',
  `Qty` int(11) DEFAULT NULL,
  `Value` double DEFAULT NULL,
  PRIMARY KEY (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `couponcodes`
--

LOCK TABLES `couponcodes` WRITE;
/*!40000 ALTER TABLE `couponcodes` DISABLE KEYS */;
INSERT INTO `couponcodes` VALUES ('01',1,0),('02',5,0),('03',1,-1.1),('04',1,-1.35),('05',1,-1.4),('06',1,-1.6),('07',3,-1.5),('08',2,-3),('09',3,-2),('10',1,-0.1),('11',1,-1.85),('12',1,-0.12),('13',4,-1),('14',2,0),('15',1,-0.15),('16',3,0),('18',1,-2.6),('19',4,0),('20',1,-0.2),('21',2,-0.35),('22',2,-0.4),('23',2,-0.45),('24',2,-0.5),('25',1,-0.25),('26',1,-2.85),('28',2,-0.55),('29',1,-0.29),('30',1,-0.3),('31',2,-0.6),('32',2,-0.75),('33',2,-1),('34',2,-1.25),('35',1,-0.35),('36',2,-1.5),('37',3,-0.25),('38',3,-0.3),('39',1,-0.39),('40',1,-0.4),('41',3,-0.5),('42',3,-1),('43',2,-1.1),('44',2,-1.35),('45',1,-0.45),('46',2,-1.6),('47',2,-1.75),('48',2,-1.85),('49',1,-0.49),('50',1,-0.5),('51',2,-2),('52',3,-0.55),('53',2,-0.1),('54',2,-0.15),('55',1,-0.55),('56',2,-0.2),('57',2,-0.25),('58',2,-0.3),('59',1,-0.59),('60',1,-0.6),('61',1,-10),('62',1,-9.5),('63',1,-9),('64',1,-8.5),('65',1,-0.65),('66',1,-8),('67',1,-7.5),('68',1,-7),('69',1,-0.69),('70',1,-0.7),('71',1,-6.5),('72',1,-6),('73',1,-5.5),('74',1,-5),('75',1,-0.75),('76',1,-1),('77',1,-1.25),('78',1,-1.5),('79',1,-0.79),('80',1,-0.8),('81',1,-1.75),('82',1,-2),('83',1,-2.25),('84',1,-2.5),('85',1,-0.85),('86',1,-2.75),('87',1,-3),('88',1,-3.25),('89',1,-0.89),('90',1,-0.9),('91',1,-3.5),('92',1,-3.75),('93',1,-4),('95',1,-0.95),('96',1,-4.5),('98',2,-0.65),('99',1,-0.99);
/*!40000 ALTER TABLE `couponcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custPreferences`
--

DROP TABLE IF EXISTS `custPreferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custPreferences` (
  `card_no` int(11) NOT NULL DEFAULT '0',
  `pref_key` varchar(50) NOT NULL DEFAULT '',
  `pref_value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`card_no`,`pref_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custPreferences`
--

LOCK TABLES `custPreferences` WRITE;
/*!40000 ALTER TABLE `custPreferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `custPreferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custReceiptMessage`
--

DROP TABLE IF EXISTS `custReceiptMessage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custReceiptMessage` (
  `card_no` int(11) DEFAULT NULL,
  `msg_text` varchar(255) DEFAULT NULL,
  `modifier_module` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custReceiptMessage`
--

LOCK TABLES `custReceiptMessage` WRITE;
/*!40000 ALTER TABLE `custReceiptMessage` DISABLE KEYS */;
/*!40000 ALTER TABLE `custReceiptMessage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custdata`
--

DROP TABLE IF EXISTS `custdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custdata` (
  `CardNo` int(11) DEFAULT NULL,
  `personNum` tinyint(4) NOT NULL DEFAULT '1',
  `LastName` varchar(30) DEFAULT NULL,
  `FirstName` varchar(30) DEFAULT NULL,
  `CashBack` double NOT NULL DEFAULT '60',
  `Balance` double NOT NULL DEFAULT '0',
  `Discount` smallint(6) DEFAULT NULL,
  `MemDiscountLimit` double NOT NULL DEFAULT '0',
  `ChargeLimit` double NOT NULL DEFAULT '0',
  `ChargeOk` tinyint(4) NOT NULL DEFAULT '1',
  `WriteChecks` tinyint(4) NOT NULL DEFAULT '1',
  `StoreCoupons` tinyint(4) NOT NULL DEFAULT '1',
  `Type` varchar(10) NOT NULL DEFAULT 'pc',
  `memType` tinyint(4) DEFAULT NULL,
  `staff` tinyint(4) NOT NULL DEFAULT '0',
  `SSI` tinyint(4) NOT NULL DEFAULT '0',
  `Purchases` double NOT NULL DEFAULT '0',
  `NumberOfChecks` smallint(6) NOT NULL DEFAULT '0',
  `memCoupons` int(11) NOT NULL DEFAULT '1',
  `blueLine` varchar(50) DEFAULT NULL,
  `Shown` tinyint(4) NOT NULL DEFAULT '1',
  `LastChange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `CardNo` (`CardNo`),
  KEY `LastName` (`LastName`),
  KEY `LastChange` (`LastChange`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custdata`
--

LOCK TABLES `custdata` WRITE;
/*!40000 ALTER TABLE `custdata` DISABLE KEYS */;
INSERT INTO `custdata` VALUES (3,1,'Test','Mr',60,0,2,0,0,1,1,1,'PC',2,0,1,0,0,1,'3 Test',1,'2013-12-21 20:17:20',1),(4,1,'Citizen Test','Senior ',999.99,0,2,0,0,1,1,1,'PC',2,0,1,0,0,0,'4 Citizen Test',1,'2013-12-21 20:19:03',2);
/*!40000 ALTER TABLE `custdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customReceipt`
--

DROP TABLE IF EXISTS `customReceipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customReceipt` (
  `text` varchar(80) DEFAULT NULL,
  `seq` int(11) NOT NULL DEFAULT '0',
  `type` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`seq`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customReceipt`
--

LOCK TABLES `customReceipt` WRITE;
/*!40000 ALTER TABLE `customReceipt` DISABLE KEYS */;
INSERT INTO `customReceipt` VALUES ('Thanks for shopping!',0,'receiptFooter'),('rut_hdr_inv.bmp',0,'receiptHeader'),('77 Wales St. ',1,'receiptHeader');
/*!40000 ALTER TABLE `customReceipt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dateRestrict`
--

DROP TABLE IF EXISTS `dateRestrict`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dateRestrict` (
  `upc` varchar(13) DEFAULT NULL,
  `dept_ID` int(11) DEFAULT NULL,
  `restrict_date` date DEFAULT NULL,
  `restrict_dow` smallint(6) DEFAULT NULL,
  `restrict_start` time DEFAULT NULL,
  `restrict_end` time DEFAULT NULL,
  KEY `upc` (`upc`),
  KEY `dept_ID` (`dept_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dateRestrict`
--

LOCK TABLES `dateRestrict` WRITE;
/*!40000 ALTER TABLE `dateRestrict` DISABLE KEYS */;
/*!40000 ALTER TABLE `dateRestrict` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `dept_no` smallint(6) NOT NULL DEFAULT '0',
  `dept_name` varchar(30) DEFAULT NULL,
  `dept_tax` tinyint(4) DEFAULT NULL,
  `dept_fs` tinyint(4) DEFAULT NULL,
  `dept_limit` double DEFAULT NULL,
  `dept_minimum` double DEFAULT NULL,
  `dept_discount` tinyint(4) DEFAULT NULL,
  `dept_see_id` tinyint(4) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `modifiedby` int(11) DEFAULT NULL,
  PRIMARY KEY (`dept_no`),
  KEY `dept_name` (`dept_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Beer',3,0,100,0.01,1,NULL,'2013-12-16 20:58:18',1),(2,'Bread',0,1,50,0.01,1,NULL,'2013-12-16 22:45:59',1),(3,'Bulk Repack',1,1,50,0.01,1,NULL,'2013-12-16 22:45:59',1),(4,'Bulk',1,1,75,0.01,1,NULL,'2013-12-17 04:51:23',1),(5,'Coffee',1,1,55,0.01,1,NULL,'2013-12-17 04:51:41',1),(6,'Cooler',1,1,50,0.01,1,NULL,'2013-12-16 22:46:01',1),(7,'Cooler/Cheese Repack Cooler Dr',1,1,50,0.01,1,NULL,'2013-12-16 22:46:01',1),(8,'Clothing',1,0,50,0.01,1,NULL,'2013-12-17 04:54:39',1),(9,'Freezer',1,1,50,0.01,1,NULL,'2013-12-17 04:51:55',1),(10,'Gift',1,0,50,0.01,1,NULL,'2013-12-17 04:51:47',1),(11,'Grocery',1,1,350,0.01,1,NULL,'2013-12-16 20:59:33',1),(12,'HABA-NTX',0,0,50,0.01,1,NULL,'2013-12-16 22:46:03',1),(13,'HABA-TX',1,0,50,0.01,1,NULL,'2013-12-16 22:46:04',1),(14,'Herbs & Spices ',1,1,50,0.01,1,NULL,'2013-12-16 22:46:04',1),(15,'Produce',1,1,50,0.01,1,NULL,'2013-12-16 22:46:05',1),(16,'Prepared Foods',1,0,50,0.01,1,NULL,'2013-12-16 22:46:05',1),(17,'Wine',1,0,50,0.01,1,NULL,'2013-12-16 22:46:06',1),(18,'Household',1,0,50,0.01,1,NULL,'2013-12-16 22:46:06',1),(19,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:07',1),(20,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:07',1),(21,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:08',1),(22,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:08',1),(23,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:08',1),(24,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:09',1),(25,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:09',1),(26,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:10',1),(27,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:10',1),(28,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:11',1),(29,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:11',1),(30,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:12',1),(31,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:12',1),(32,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:13',1),(33,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:13',1),(34,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:14',1),(35,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:14',1),(36,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:15',1),(37,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:15',1),(38,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:15',1),(39,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:16',1),(40,'CO-OP PROMO',0,0,50,0.01,1,NULL,'2013-12-16 22:46:16',1),(41,'PUBLIC TRANSIT',0,0,50,0.01,1,NULL,'2013-12-16 22:46:17',1),(42,'BOTTLE RETURN',0,0,50,0.01,1,NULL,'2013-12-16 22:46:17',1),(43,'BOTTLE DEPOSIT',0,0,50,0.01,1,NULL,'2013-12-16 22:46:18',1),(44,'INSTORE COUPON',0,0,50,0.01,1,NULL,'2013-12-16 22:46:18',1),(45,'GIFT CERTIFICATE SOLD',0,0,50,0.01,1,NULL,'2013-12-16 22:46:19',1),(46,'MEMBERSHIP EQUITY',0,0,50,0.01,1,NULL,'2013-12-16 22:46:19',1),(47,'STAMPS',0,0,50,0.01,1,NULL,'2013-12-16 22:46:20',1),(48,'DONATION',0,0,50,0.01,1,NULL,'2013-12-16 22:46:20',1),(49,'RECEIVED ON ACCOUNT',0,0,50,0.01,1,NULL,'2013-12-16 22:46:21',1),(50,'STORE SUPPLY',0,0,50,0.01,1,NULL,'2013-12-16 22:46:21',1),(51,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:21',1),(52,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:22',1),(53,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:22',1),(54,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:23',1),(55,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:23',1),(56,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:24',1),(57,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:24',1),(58,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:25',1),(59,'',0,0,50,0.01,1,NULL,'2013-12-16 22:46:25',1);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disableCoupon`
--

DROP TABLE IF EXISTS `disableCoupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disableCoupon` (
  `upc` varchar(13) NOT NULL DEFAULT '',
  `threshold` smallint(6) DEFAULT '0',
  `reason` text,
  PRIMARY KEY (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disableCoupon`
--

LOCK TABLES `disableCoupon` WRITE;
/*!40000 ALTER TABLE `disableCoupon` DISABLE KEYS */;
/*!40000 ALTER TABLE `disableCoupon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drawerowner`
--

DROP TABLE IF EXISTS `drawerowner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drawerowner` (
  `drawer_no` tinyint(4) NOT NULL DEFAULT '0',
  `emp_no` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`drawer_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `drawerowner`
--

LOCK TABLES `drawerowner` WRITE;
/*!40000 ALTER TABLE `drawerowner` DISABLE KEYS */;
INSERT INTO `drawerowner` VALUES (1,9999),(2,NULL);
/*!40000 ALTER TABLE `drawerowner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `emp_no` smallint(6) NOT NULL DEFAULT '0',
  `CashierPassword` varchar(50) DEFAULT NULL,
  `AdminPassword` varchar(50) DEFAULT NULL,
  `FirstName` varchar(255) DEFAULT NULL,
  `LastName` varchar(255) DEFAULT NULL,
  `JobTitle` varchar(255) DEFAULT NULL,
  `EmpActive` tinyint(4) DEFAULT NULL,
  `frontendsecurity` smallint(6) DEFAULT NULL,
  `backendsecurity` smallint(6) DEFAULT NULL,
  `birthdate` datetime DEFAULT NULL,
  PRIMARY KEY (`emp_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'9876','9876','Caitlyn','','STAFF',1,30,30,'1970-01-01 00:00:00'),(2,'3573','3573','Caitlin','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(3,'9515','9515','Laura','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(4,'6237','6237','Camille','','STAFF',1,10,10,'1970-01-01 00:00:00'),(5,'1482','1482','Julie','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(6,'5835','5835','Evan','','STAFF',1,10,10,'1970-01-01 00:00:00'),(7,'5491','5491','Taylor','','STAFF',1,10,10,'1970-01-01 00:00:00'),(8,'1983','1983','Anya','','STAFF',1,10,10,'1970-01-01 00:00:00'),(9,'7291','7291','Jenna','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(10,'2849','2849','Leah','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(11,'3864','3864','Kelly','','STAFF',1,10,10,'1970-01-01 00:00:00'),(12,'4769','4769','Meghan','','STAFF',1,10,10,'1970-01-01 00:00:00'),(13,'2167','2167','Steve','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(14,'6817','6817','Justin','','MANAGER',1,30,30,'1970-01-01 00:00:00'),(15,'1510','1510','Katlin','','STAFF',1,10,10,'1970-01-01 00:00:00'),(56,'56','5656','John ','P','Dev',1,11,21,'1983-07-05 00:00:00');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `globalvalues`
--

DROP TABLE IF EXISTS `globalvalues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `globalvalues` (
  `CashierNo` int(11) DEFAULT NULL,
  `Cashier` varchar(30) DEFAULT NULL,
  `LoggedIn` tinyint(4) DEFAULT NULL,
  `TransNo` int(11) DEFAULT NULL,
  `TTLFlag` tinyint(4) DEFAULT NULL,
  `FntlFlag` tinyint(4) DEFAULT NULL,
  `TaxExempt` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `globalvalues`
--

LOCK TABLES `globalvalues` WRITE;
/*!40000 ALTER TABLE `globalvalues` DISABLE KEYS */;
INSERT INTO `globalvalues` VALUES (9999,'Training Mode',1,8,0,0,0);
/*!40000 ALTER TABLE `globalvalues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `houseCouponItems`
--

DROP TABLE IF EXISTS `houseCouponItems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `houseCouponItems` (
  `coupID` int(11) NOT NULL DEFAULT '0',
  `upc` varchar(13) NOT NULL DEFAULT '',
  `type` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`coupID`,`upc`),
  KEY `coupID` (`coupID`),
  KEY `upc` (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `houseCouponItems`
--

LOCK TABLES `houseCouponItems` WRITE;
/*!40000 ALTER TABLE `houseCouponItems` DISABLE KEYS */;
INSERT INTO `houseCouponItems` VALUES (1,'12','BOTH'),(1,'13','BOTH');
/*!40000 ALTER TABLE `houseCouponItems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `houseCoupons`
--

DROP TABLE IF EXISTS `houseCoupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `houseCoupons` (
  `coupID` int(11) NOT NULL DEFAULT '0',
  `endDate` datetime DEFAULT NULL,
  `limit` smallint(6) DEFAULT NULL,
  `memberOnly` smallint(6) DEFAULT NULL,
  `discountType` varchar(2) DEFAULT NULL,
  `discountValue` double DEFAULT NULL,
  `minType` varchar(2) DEFAULT NULL,
  `minValue` double DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  PRIMARY KEY (`coupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `houseCoupons`
--

LOCK TABLES `houseCoupons` WRITE;
/*!40000 ALTER TABLE `houseCoupons` DISABLE KEYS */;
INSERT INTO `houseCoupons` VALUES (1,'2044-12-31 00:00:00',0,0,'%D',0.1,'',0.01,44);
/*!40000 ALTER TABLE `houseCoupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `houseVirtualCoupons`
--

DROP TABLE IF EXISTS `houseVirtualCoupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `houseVirtualCoupons` (
  `card_no` int(11) NOT NULL DEFAULT '0',
  `coupID` int(11) NOT NULL DEFAULT '0',
  `description` varchar(100) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`coupID`,`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `houseVirtualCoupons`
--

LOCK TABLES `houseVirtualCoupons` WRITE;
/*!40000 ALTER TABLE `houseVirtualCoupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `houseVirtualCoupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lane_config`
--

DROP TABLE IF EXISTS `lane_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lane_config` (
  `keycode` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(255) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`keycode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lane_config`
--

LOCK TABLES `lane_config` WRITE;
/*!40000 ALTER TABLE `lane_config` DISABLE KEYS */;
INSERT INTO `lane_config` VALUES ('alertBar','\'POS - Alert\'',NULL),('BottleReturnDept','42',NULL),('browserOnly','0',NULL),('cashOverLimit','1',NULL),('CCintegrate','0',NULL),('CCSigLimit','0',NULL),('CustomerDisplay','0',NULL),('DBMS','\'pdomysql\'',NULL),('defaultNonMem','99999',NULL),('discountEnforced','1',NULL),('DiscountModule','\'DiscountModule\'',NULL),('dollarOver','50',NULL),('dualDrawerMode','0',NULL),('enableFranking','1',NULL),('fntlDefault','1',NULL),('FooterModules','array(\'SavedOrCouldHave\',\'TransPercentDiscount\',\'MemSales\',\'EveryoneSales\',\'MultiTotal\')',NULL),('gcIntegrate','0',NULL),('houseCouponPrefix','\'00499999\'',NULL),('kickerModule','\'Kicker\'',NULL),('LineItemDiscountMem','0.000000',NULL),('LineItemDiscountNonMem','0.000000',NULL),('localhost','\'biggreenhouse.dyndns-remote.com:61006\'',NULL),('localPass','\'coreserver\'',NULL),('localUser','\'coreserver\'',NULL),('lockScreen','1',NULL),('mDatabase','\'rut_log\'',NULL),('mDBMS','\'pdomysql\'',NULL),('member_subtotal','True',NULL),('memlistNonMember','0',NULL),('mPass','\'coreserver\'',NULL),('mServer','\'biggreenhouse.dyndns-remote.com:61006\'',NULL),('mUser','\'coreserver\'',NULL),('newReceipt','1',NULL),('OS','\'other\'',NULL),('pDatabase','\'rut_opdata\'',NULL),('print','1',NULL),('printerPort','\'/dev/usb/lp0\'',NULL),('refundDiscountable','0',NULL),('RegisteredPaycardClasses','array(\'GoEMerchant\',\'MercuryGift\')',NULL),('scaleDriver','\'NewMagellan\'',NULL),('scalePort','\'/dev/ttyS0\'',NULL),('SigCapture','\'\'',NULL),('SpecialDeptMap','array()',NULL),('store','\'rutland\'',NULL),('tDatabase','\'rut_translog\'',NULL),('TenderMap','array(\'\')',NULL),('TenderReportMod','\'DefaultTenderReport\'',NULL),('timeout','180000',NULL),('TotalActions','array(\'AutoCoupon\')',NULL),('touchscreen','False',NULL),('visitingMem','\'\'',NULL);
/*!40000 ALTER TABLE `lane_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `memberCards`
--

DROP TABLE IF EXISTS `memberCards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memberCards` (
  `card_no` int(11) NOT NULL DEFAULT '0',
  `upc` varchar(13) DEFAULT NULL,
  PRIMARY KEY (`card_no`),
  KEY `upc` (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `memberCards`
--

LOCK TABLES `memberCards` WRITE;
/*!40000 ALTER TABLE `memberCards` DISABLE KEYS */;
/*!40000 ALTER TABLE `memberCards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `memberCardsView`
--

DROP TABLE IF EXISTS `memberCardsView`;
/*!50001 DROP VIEW IF EXISTS `memberCardsView`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `memberCardsView` (
  `upc` tinyint NOT NULL,
  `card_no` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `memchargebalance`
--

DROP TABLE IF EXISTS `memchargebalance`;
/*!50001 DROP VIEW IF EXISTS `memchargebalance`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `memchargebalance` (
  `CardNo` tinyint NOT NULL,
  `availBal` tinyint NOT NULL,
  `balance` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `parameters`
--

DROP TABLE IF EXISTS `parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parameters` (
  `store_id` smallint(4) NOT NULL DEFAULT '0',
  `lane_id` smallint(4) NOT NULL DEFAULT '0',
  `param_key` varchar(100) NOT NULL DEFAULT '',
  `param_value` varchar(255) DEFAULT NULL,
  `is_array` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`store_id`,`lane_id`,`param_key`),
  KEY `param_key` (`param_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parameters`
--

LOCK TABLES `parameters` WRITE;
/*!40000 ALTER TABLE `parameters` DISABLE KEYS */;
INSERT INTO `parameters` VALUES (0,1,'alertBar','POS - Alert',0),(0,1,'BottleReturnDept','',0),(0,1,'browserOnly','0',0),(0,1,'cashOverLimit','1',0),(0,1,'CCintegrate','1',0),(0,1,'CCSigLimit','0',0),(0,1,'CustomerDisplay','0',0),(0,1,'defaultNonMem','99999',0),(0,1,'discountEnforced','1',0),(0,1,'DiscountModule','DiscountModule',0),(0,1,'dollarOver','50',0),(0,1,'dualDrawerMode','0',0),(0,1,'emailReceiptFrom','',0),(0,1,'enableFranking','1',0),(0,1,'FooterModules','SavedOrCouldHave,TransPercentDiscount,MemSales,EveryoneSales,MultiTotal',1),(0,1,'gcIntegrate','1',0),(0,1,'kickerModule','Kicker',0),(0,1,'LineItemDiscountMem','0.000000',0),(0,1,'LineItemDiscountNonMem','0.000000',0),(0,1,'lockScreen','1',0),(0,1,'mDatabase','rut_log',0),(0,1,'mDBMS','mysql',0),(0,1,'member_subtotal','True',0),(0,1,'memlistNonMember','0',0),(0,1,'mPass','coreserver',0),(0,1,'mServer','biggreenhouse.dyndns-remote.com:61006',0),(0,1,'mUser','coreserver',0),(0,1,'newReceipt','1',0),(0,1,'OS','other',0),(0,1,'PluginList','PriceCheck,QuickMenus,VirtualCoupon',1),(0,1,'print','1',0),(0,1,'printerPort','/tmp/fakereceipt.txt',0),(0,1,'RBFetchData','DefaultReceiptDataFetch',0),(0,1,'RBFilter','DefaultReceiptFilter',0),(0,1,'RBSort','DefaultReceiptSort',0),(0,1,'RBTag','DefaultReceiptTag',0),(0,1,'ReceiptDriver','ESCPOSPrintHandler',0),(0,1,'ReceiptMessageMods','EquitySoldReceiptMessage',1),(0,1,'refundDiscountable','1',0),(0,1,'RegisteredPaycardClasses','MercuryGift,GoEMerchant',1),(0,1,'scaleDriver','NewMagellan',0),(0,1,'scalePort','/dev/ttyS0',0),(0,1,'SigCapture','',0),(0,1,'SpecialUpcClasses','',1),(0,1,'store','rutland',0),(0,1,'TenderMap','',1),(0,1,'TenderReportMod','DefaultTenderReport',0),(0,1,'timeout','180000',0),(0,1,'touchscreen','False',0),(0,1,'visitingMem','',0),(0,91,'alertBar','POS - Alert',0),(0,91,'BottleReturnDept','42',0),(0,91,'browserOnly','0',0),(0,91,'cashOverLimit','1',0),(0,91,'CCintegrate','0',0),(0,91,'CCSigLimit','0',0),(0,91,'CustomerDisplay','0',0),(0,91,'defaultNonMem','99999',0),(0,91,'discountEnforced','1',0),(0,91,'DiscountModule','DiscountModule',0),(0,91,'dollarOver','50',0),(0,91,'dualDrawerMode','0',0),(0,91,'emailReceiptFrom','',0),(0,91,'enableFranking','1',0),(0,91,'FooterModules','SavedOrCouldHave,TransPercentDiscount,MemSales,EveryoneSales,MultiTotal',1),(0,91,'gcIntegrate','0',0),(0,91,'kickerModule','Kicker',0),(0,91,'LineItemDiscountMem','0.000000',0),(0,91,'LineItemDiscountNonMem','0.000000',0),(0,91,'lockScreen','1',0),(0,91,'mDatabase','rut_log',0),(0,91,'mDBMS','mysql',0),(0,91,'member_subtotal','True',0),(0,91,'memlistNonMember','0',0),(0,91,'mPass','coreserver',0),(0,91,'mServer','biggreenhouse.dyndns-remote.com:61006',0),(0,91,'mUser','coreserver',0),(0,91,'newReceipt','1',0),(0,91,'OS','other',0),(0,91,'print','1',0),(0,91,'printerPort','other',0),(0,91,'RBFetchData','DefaultReceiptDataFetch',0),(0,91,'RBFilter','DefaultReceiptFilter',0),(0,91,'RBSort','DefaultReceiptSort',0),(0,91,'RBTag','DefaultReceiptTag',0),(0,91,'ReceiptDriver','ESCPOSPrintHandler',0),(0,91,'ReceiptMessageMods','',1),(0,91,'refundDiscountable','0',0),(0,91,'RegisteredPaycardClasses','GoEMerchant,MercuryGift',1),(0,91,'scaleDriver','NewMagellan',0),(0,91,'scalePort','/dev/ttyS0',0),(0,91,'SigCapture','',0),(0,91,'store','rutland',0),(0,91,'TenderMap','',1),(0,91,'TenderReportMod','DefaultTenderReport',0),(0,91,'timeout','180000',0),(0,91,'touchscreen','False',0),(0,91,'visitingMem','',0),(0,99,'alertBar','New Pi - Alert',0),(0,99,'BottleReturnDept','',0),(0,99,'browserOnly','0',0),(0,99,'cashOverLimit','1',0),(0,99,'CCintegrate','1',0),(0,99,'CCSigLimit','0',0),(0,99,'CustomerDisplay','0',0),(0,99,'Debug_CoreLocal','',0),(0,99,'Debug_Redirects','',0),(0,99,'defaultNonMem','11',0),(0,99,'discountEnforced','1',0),(0,99,'DiscountModule','DiscountModule',0),(0,99,'dollarOver','50',0),(0,99,'dualDrawerMode','0',0),(0,99,'emailReceiptFrom','',0),(0,99,'enableFranking','0',0),(0,99,'FooterModules','SavedOrCouldHave,TransPercentDiscount,MemSales,EveryoneSales,MultiTotal',1),(0,99,'gcIntegrate','1',0),(0,99,'kickerModule','Kicker',0),(0,99,'LineItemDiscountMem','',0),(0,99,'LineItemDiscountNonMem','',0),(0,99,'lockScreen','1',0),(0,99,'mDatabase','rut_log',0),(0,99,'mDBMS','pdomysql',0),(0,99,'member_subtotal','True',0),(0,99,'memlistNonMember','0',0),(0,99,'mPass','coreserver',0),(0,99,'mServer','biggreenhouse.dyndns-remote.com:61006',0),(0,99,'mUser','coreserver',0),(0,99,'newReceipt','1',0),(0,99,'OS','other',0),(0,99,'print','1',0),(0,99,'printerPort','fakereceipt.txt',0),(0,99,'RBFetchData','DefaultReceiptDataFetch',0),(0,99,'RBFilter','DefaultReceiptFilter',0),(0,99,'RBSort','DefaultReceiptSort',0),(0,99,'RBTag','DefaultReceiptTag',0),(0,99,'ReceiptDriver','ESCPOSPrintHandler',0),(0,99,'ReceiptMessageMods','',1),(0,99,'refundDiscountable','0',0),(0,99,'RegisteredPaycardClasses','MercuryGift,GoEMerchant',1),(0,99,'scaleDriver','',0),(0,99,'scalePort','',0),(0,99,'SecurityCancel','20',0),(0,99,'SecurityLineItemDiscount','20',0),(0,99,'SecurityRefund','20',0),(0,99,'SecuritySR','20',0),(0,99,'SigCapture','',0),(0,99,'SpecialUpcClasses','',1),(0,99,'store','NewPI',0),(0,99,'TenderReportMod','DefaultTenderReport',0),(0,99,'timeout','180000',0),(0,99,'touchscreen','False',0),(0,99,'visitingMem','',0),(0,99,'VoidLimit','0',0);
/*!40000 ALTER TABLE `parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `normal_price` double DEFAULT NULL,
  `pricemethod` smallint(6) DEFAULT NULL,
  `groupprice` double DEFAULT NULL,
  `quantity` smallint(6) DEFAULT NULL,
  `special_price` double DEFAULT NULL,
  `specialpricemethod` smallint(6) DEFAULT NULL,
  `specialgroupprice` double DEFAULT NULL,
  `specialquantity` smallint(6) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `size` varchar(9) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `scaleprice` tinyint(4) DEFAULT '0',
  `mixmatchcode` varchar(13) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `advertised` tinyint(4) DEFAULT NULL,
  `tareweight` double DEFAULT NULL,
  `discount` smallint(6) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `unitofmeasure` varchar(15) DEFAULT NULL,
  `wicable` smallint(6) DEFAULT NULL,
  `qttyEnforced` tinyint(4) DEFAULT NULL,
  `idEnforced` tinyint(4) DEFAULT NULL,
  `cost` double DEFAULT '0',
  `inUse` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `subdept` smallint(4) DEFAULT NULL,
  `deposit` double DEFAULT NULL,
  `local` int(11) DEFAULT '0',
  `store_id` smallint(6) DEFAULT '0',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `upc` (`upc`),
  KEY `description` (`description`),
  KEY `normal_price` (`normal_price`),
  KEY `subdept` (`subdept`),
  KEY `department` (`department`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES ('0000000066666','TEST SUPPLEMENT',19.99,0,0,0,0,0,0,0,NULL,NULL,13,'',1,0,0,0,'','2013-12-21 14:12:42',1,0,1,0,'',0,0,0,0,1,0,0,0,0,0,1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subdepts`
--

DROP TABLE IF EXISTS `subdepts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subdepts` (
  `subdept_no` smallint(4) NOT NULL,
  `subdept_name` varchar(30) DEFAULT NULL,
  `dept_ID` smallint(4) DEFAULT NULL,
  PRIMARY KEY (`subdept_no`),
  KEY `subdept_name` (`subdept_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subdepts`
--

LOCK TABLES `subdepts` WRITE;
/*!40000 ALTER TABLE `subdepts` DISABLE KEYS */;
/*!40000 ALTER TABLE `subdepts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenders`
--

DROP TABLE IF EXISTS `tenders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenders` (
  `TenderID` smallint(6) NOT NULL DEFAULT '0',
  `TenderCode` varchar(255) DEFAULT NULL,
  `TenderName` varchar(255) DEFAULT NULL,
  `TenderType` varchar(255) DEFAULT NULL,
  `ChangeMessage` varchar(255) DEFAULT NULL,
  `MinAmount` double DEFAULT NULL,
  `MaxAmount` double DEFAULT NULL,
  `MaxRefund` double DEFAULT NULL,
  PRIMARY KEY (`TenderID`),
  KEY `TenderCode` (`TenderCode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenders`
--

LOCK TABLES `tenders` WRITE;
/*!40000 ALTER TABLE `tenders` DISABLE KEYS */;
INSERT INTO `tenders` VALUES (1,'CA','Cash','CA','Change',0,500,500),(2,'CK','Check','CKCB','Cash Back',0,500,0),(3,'CC','Credit Card','','',0,500,99999),(4,'DC','Debit Card','DCCB','Cash Back',0,100,0),(5,'FS','EBT','','',0,100,10),(6,'MC','Coupons','','',0,20,0.01),(7,'MI','Instore Charges','','',0,1000,5),(8,'TC','Gift Certificate','TCCB','Change',0.01,0,10),(9,'EC','EBT Cash','ECCB','ECT Cash Back',0,500,0),(10,'WT','WIC','CK','',0.01,900,0),(11,'IC','Instore Coupon','','',0.01,5,0),(12,'CX','Corp. Charge','','',0.01,9999,0),(13,'LN','Tender','','',0.01,9999,0),(14,'CP','Coupons','','',0,20,0.01),(15,'EF','EBT FS','','',0,100,10),(16,'GD','Gift Card','CA','Credit Acct',0,500,10),(17,'TV','Travelers Check','CK','Change',0,500,500),(18,'MA','MAD Coupon','CA','',0.01,2.5,0),(19,'RR','RRR Coupon','CA','',0.01,1,0);
/*!40000 ALTER TABLE `tenders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unpaid_ar_today`
--

DROP TABLE IF EXISTS `unpaid_ar_today`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unpaid_ar_today` (
  `card_no` int(11) NOT NULL DEFAULT '0',
  `old_balance` double DEFAULT NULL,
  `recent_payments` double DEFAULT NULL,
  PRIMARY KEY (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unpaid_ar_today`
--

LOCK TABLES `unpaid_ar_today` WRITE;
/*!40000 ALTER TABLE `unpaid_ar_today` DISABLE KEYS */;
/*!40000 ALTER TABLE `unpaid_ar_today` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `memberCardsView`
--

/*!50001 DROP TABLE IF EXISTS `memberCardsView`*/;
/*!50001 DROP VIEW IF EXISTS `memberCardsView`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `memberCardsView` AS select concat('',`c`.`CardNo`) AS `upc`,`c`.`CardNo` AS `card_no` from `custdata` `c` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `memchargebalance`
--

/*!50001 DROP TABLE IF EXISTS `memchargebalance`*/;
/*!50001 DROP VIEW IF EXISTS `memchargebalance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `memchargebalance` AS select `c`.`CardNo` AS `CardNo`,(`c`.`ChargeLimit` - `c`.`Balance`) AS `availBal`,`c`.`Balance` AS `balance` from `custdata` `c` where (`c`.`personNum` = 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-12-25 18:47:48
