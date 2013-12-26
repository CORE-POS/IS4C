-- MySQL dump 10.13  Distrib 5.5.34, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: rut_translog
-- ------------------------------------------------------
-- Server version	5.5.34-0ubuntu0.12.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES latin1 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `CapturedSignature`
--

DROP TABLE IF EXISTS `CapturedSignature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CapturedSignature` (
  `tdate` datetime DEFAULT NULL,
  `emp_no` int(11) DEFAULT NULL,
  `register_no` int(11) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `filetype` char(3) DEFAULT NULL,
  `filecontents` blob
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CapturedSignature`
--

LOCK TABLES `CapturedSignature` WRITE;
/*!40000 ALTER TABLE `CapturedSignature` DISABLE KEYS */;
/*!40000 ALTER TABLE `CapturedSignature` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `ccReceiptView`
--

DROP TABLE IF EXISTS `ccReceiptView`;
/*!50001 DROP VIEW IF EXISTS `ccReceiptView`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ccReceiptView` (
  `tranType` tinyint NOT NULL,
  `amount` tinyint NOT NULL,
  `PAN` tinyint NOT NULL,
  `entryMethod` tinyint NOT NULL,
  `issuer` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `xResultMessage` tinyint NOT NULL,
  `xApprovalNumber` tinyint NOT NULL,
  `xTransactionID` tinyint NOT NULL,
  `date` tinyint NOT NULL,
  `cashierNo` tinyint NOT NULL,
  `laneNo` tinyint NOT NULL,
  `transNo` tinyint NOT NULL,
  `transID` tinyint NOT NULL,
  `datetime` tinyint NOT NULL,
  `sortorder` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `couponApplied`
--

DROP TABLE IF EXISTS `couponApplied`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `couponApplied` (
  `emp_no` int(11) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `quantity` float DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `couponApplied`
--

LOCK TABLES `couponApplied` WRITE;
/*!40000 ALTER TABLE `couponApplied` DISABLE KEYS */;
/*!40000 ALTER TABLE `couponApplied` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dtransactions`
--

DROP TABLE IF EXISTS `dtransactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dtransactions` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dtransactions`
--

LOCK TABLES `dtransactions` WRITE;
/*!40000 ALTER TABLE `dtransactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `dtransactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `efsnetRequest`
--

DROP TABLE IF EXISTS `efsnetRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `efsnetRequest` (
  `date` int(11) DEFAULT NULL,
  `cashierNo` int(11) DEFAULT NULL,
  `laneNo` int(11) DEFAULT NULL,
  `transNo` int(11) DEFAULT NULL,
  `transID` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `refNum` varchar(50) DEFAULT NULL,
  `live` tinyint(4) DEFAULT NULL,
  `mode` varchar(32) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `PAN` varchar(19) DEFAULT NULL,
  `issuer` varchar(16) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `manual` tinyint(4) DEFAULT NULL,
  `sentPAN` tinyint(4) DEFAULT NULL,
  `sentExp` tinyint(4) DEFAULT NULL,
  `sentTr1` tinyint(4) DEFAULT NULL,
  `sentTr2` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `efsnetRequest`
--

LOCK TABLES `efsnetRequest` WRITE;
/*!40000 ALTER TABLE `efsnetRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `efsnetRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `efsnetRequestMod`
--

DROP TABLE IF EXISTS `efsnetRequestMod`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `efsnetRequestMod` (
  `date` int(11) DEFAULT NULL,
  `cashierNo` int(11) DEFAULT NULL,
  `laneNo` int(11) DEFAULT NULL,
  `transNo` int(11) DEFAULT NULL,
  `transID` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `origRefNum` varchar(50) DEFAULT NULL,
  `origAmount` double DEFAULT NULL,
  `origTransactionID` varchar(12) DEFAULT NULL,
  `mode` varchar(32) DEFAULT NULL,
  `altRoute` tinyint(4) DEFAULT NULL,
  `seconds` float DEFAULT NULL,
  `commErr` int(11) DEFAULT NULL,
  `httpCode` int(11) DEFAULT NULL,
  `validResponse` smallint(6) DEFAULT NULL,
  `xResponseCode` varchar(4) DEFAULT NULL,
  `xResultCode` varchar(4) DEFAULT NULL,
  `xResultMessage` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `efsnetRequestMod`
--

LOCK TABLES `efsnetRequestMod` WRITE;
/*!40000 ALTER TABLE `efsnetRequestMod` DISABLE KEYS */;
/*!40000 ALTER TABLE `efsnetRequestMod` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `efsnetResponse`
--

DROP TABLE IF EXISTS `efsnetResponse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `efsnetResponse` (
  `date` int(11) DEFAULT NULL,
  `cashierNo` int(11) DEFAULT NULL,
  `laneNo` int(11) DEFAULT NULL,
  `transNo` int(11) DEFAULT NULL,
  `transID` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `refNum` varchar(50) DEFAULT NULL,
  `seconds` float DEFAULT NULL,
  `commErr` int(11) DEFAULT NULL,
  `httpCode` int(11) DEFAULT NULL,
  `validResponse` smallint(6) DEFAULT NULL,
  `xResponseCode` varchar(4) DEFAULT NULL,
  `xResultCode` varchar(8) DEFAULT NULL,
  `xResultMessage` varchar(100) DEFAULT NULL,
  `xTransactionID` varchar(12) DEFAULT NULL,
  `xApprovalNumber` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `efsnetResponse`
--

LOCK TABLES `efsnetResponse` WRITE;
/*!40000 ALTER TABLE `efsnetResponse` DISABLE KEYS */;
/*!40000 ALTER TABLE `efsnetResponse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `efsnetTokens`
--

DROP TABLE IF EXISTS `efsnetTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `efsnetTokens` (
  `expireDay` datetime DEFAULT NULL,
  `refNum` varchar(50) NOT NULL DEFAULT '',
  `token` varchar(100) NOT NULL DEFAULT '',
  `processData` varchar(255) DEFAULT NULL,
  `acqRefData` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`refNum`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `efsnetTokens`
--

LOCK TABLES `efsnetTokens` WRITE;
/*!40000 ALTER TABLE `efsnetTokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `efsnetTokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `gcReceiptView`
--

DROP TABLE IF EXISTS `gcReceiptView`;
/*!50001 DROP VIEW IF EXISTS `gcReceiptView`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `gcReceiptView` (
  `tranType` tinyint NOT NULL,
  `amount` tinyint NOT NULL,
  `terminalID` tinyint NOT NULL,
  `PAN` tinyint NOT NULL,
  `entryMethod` tinyint NOT NULL,
  `xAuthorizationCode` tinyint NOT NULL,
  `xBalance` tinyint NOT NULL,
  `xVoidCode` tinyint NOT NULL,
  `date` tinyint NOT NULL,
  `cashierNo` tinyint NOT NULL,
  `laneNo` tinyint NOT NULL,
  `transNo` tinyint NOT NULL,
  `transID` tinyint NOT NULL,
  `datetime` tinyint NOT NULL,
  `sortorder` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `localtemptrans`
--

DROP TABLE IF EXISTS `localtemptrans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localtemptrans` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`trans_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localtemptrans`
--

LOCK TABLES `localtemptrans` WRITE;
/*!40000 ALTER TABLE `localtemptrans` DISABLE KEYS */;
INSERT INTO `localtemptrans` VALUES ('2013-12-21 13:58:32',91,9999,8,'1DP11','Grocery','D',' ',' ',11,1,0,0.00,1.00,1.00,1.00,1,1,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1);
/*!40000 ALTER TABLE `localtemptrans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localtrans`
--

DROP TABLE IF EXISTS `localtrans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localtrans` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localtrans`
--

LOCK TABLES `localtrans` WRITE;
/*!40000 ALTER TABLE `localtrans` DISABLE KEYS */;
INSERT INTO `localtrans` VALUES ('2013-12-21 11:24:07',91,9999,1,'1DP1','Beer','D',' ',' ',1,1,0,0.00,1.00,1.00,1.00,3,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:37:16',91,9999,1,'20DP12','HABA-NTX','D',' ',' ',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,2),('2013-12-21 11:40:13',91,9999,1,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,3),('2013-12-21 11:40:25',91,9999,1,'0','Subtotal 21.00, Tax 0.00 #3','C','0','D',0,0,0,0.00,21.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 11:41:29',91,9999,1,'0','Credit Card','T','CC','0',0,0,0,0.00,0.00,-21.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,0,'',3,5),('2013-12-21 11:41:30',91,9999,1,'0','Change','T','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,8,0,0,0,0,0.00,'0',0,0,0,0,'',3,6),('2013-12-21 11:41:32',91,9999,1,'DISCOUNT','Discount','I','0','0',0,1,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,7),('2013-12-21 11:41:32',91,9999,1,'TAX','Tax','A','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,0,'',3,8),('2013-12-21 11:42:08',91,9999,2,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:42:22',91,9999,2,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 11:42:25',91,9999,2,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 11:43:28',91,9999,2,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 11:49:46',91,9999,3,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:49:52',91,9999,3,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 11:49:55',91,9999,3,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 11:51:08',91,9999,4,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:51:14',91,9999,4,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 11:51:18',91,9999,4,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 12:00:37',91,9999,5,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 12:00:43',91,9999,5,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 12:03:19',91,9999,5,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,3),('2013-12-21 12:03:23',91,9999,5,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 12:03:37',91,9999,5,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,5),('2013-12-21 12:03:49',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,6),('2013-12-21 12:03:59',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,7),('2013-12-21 12:04:42',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,8),('2013-12-21 12:08:31',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,9),('2013-12-21 12:09:05',91,9999,6,'10DP12','HABA-NTX','D',' ','X',12,1,0,0.00,10.00,10.00,10.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 12:09:11',91,9999,6,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 12:09:16',91,9999,6,'0','Subtotal 10.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,10.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 12:11:30',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,-1.00,-1.00,-1.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 12:11:32',91,9999,6,'0','Subtotal 9.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,9.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,5),('2013-12-21 12:15:34',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,1,0,1,0,0,0.00,'0',0,0,0,0,'',3,6),('2013-12-21 12:15:43',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,7),('2013-12-21 12:15:47',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,8),('2013-12-21 12:15:56',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,-6.00,-6.00,-6.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,9),('2013-12-21 12:15:58',91,9999,6,'0','Subtotal 62.97, Tax 0.00 #3','C','0','X',0,0,0,0.00,62.97,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,10),('2013-12-21 12:17:15',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,-1,0,0.00,19.99,-19.99,19.99,1,0,0.00,0.00,1,0,1,0,-1,0,0,0.00,'0',0,0,0,0,'',3,11),('2013-12-21 12:17:23',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,2.00,2.00,2.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,12),('2013-12-21 12:17:25',91,9999,6,'0','Subtotal 44.98, Tax 0.00 #3','C','0','X',0,0,0,0.00,44.98,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,13),('2013-12-21 13:55:42',91,9999,7,'10DP13','HABA-TX','D',' ',' ',13,1,0,0.00,10.00,10.00,10.00,1,0,0.00,0.00,1,0,0,2,1,0,0,0.00,'0',0,2,0,0,'',4,1),('2013-12-21 13:55:45',91,9999,7,'0000000000004','BADSCAN','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,'',4,2),('2013-12-21 13:55:55',91,9999,7,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,4,'',4,3),('2013-12-21 13:55:58',91,9999,7,'0049999900001','Supplement Discount','T','IC','C',44,1,0,0.00,-1.00,-1.00,-1.00,0,0,0.00,0.00,0,0,0,2,1,0,0,0.00,'0',0,2,0,0,'',4,4),('2013-12-21 13:55:59',91,9999,7,'0','** 2% Discount Applied **','0','0','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,4,2,0,0,0,0.00,'0',0,2,0,0,'',4,5),('2013-12-21 13:56:00',91,9999,7,'0','2% Discount','C','0','D',0,0,0,0.00,-0.20,0.00,0.00,0,0,0.00,0.00,0,0,5,2,0,0,0,0.00,'0',0,2,0,0,'',4,6),('2013-12-21 13:56:01',91,9999,7,'0','Subtotal 8.80, Tax 0.00 #4','C','0','D',0,0,0,0.00,8.80,0.00,0.00,0,0,0.00,0.00,0,0,3,2,0,0,0,0.00,'0',0,2,0,0,'',4,7),('2013-12-21 13:56:12',91,9999,7,'0','Credit Card','T','CC','0',0,0,0,0.00,0.00,-8.80,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,'',4,8),('2013-12-21 13:56:13',91,9999,7,'0','Change','T','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,8,2,0,0,0,0.00,'0',0,2,0,0,'',4,9),('2013-12-21 13:56:15',91,9999,7,'DISCOUNT','Discount','I','0','0',0,1,0,0.00,-0.20,-0.20,0.00,0,0,0.00,0.00,0,0,0,2,1,0,0,0.00,'0',0,2,0,0,'',4,10),('2013-12-21 13:56:15',91,9999,7,'TAX','Tax','A','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,'',4,11);
/*!40000 ALTER TABLE `localtrans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localtrans_today`
--

DROP TABLE IF EXISTS `localtrans_today`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localtrans_today` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL,
  KEY `trans_no` (`trans_no`),
  KEY `datetime` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localtrans_today`
--

LOCK TABLES `localtrans_today` WRITE;
/*!40000 ALTER TABLE `localtrans_today` DISABLE KEYS */;
INSERT INTO `localtrans_today` VALUES ('2013-12-21 11:24:07',91,9999,1,'1DP1','Beer','D',' ',' ',1,1,0,0.00,1.00,1.00,1.00,3,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:37:16',91,9999,1,'20DP12','HABA-NTX','D',' ',' ',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,2),('2013-12-21 11:40:13',91,9999,1,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,3),('2013-12-21 11:40:25',91,9999,1,'0','Subtotal 21.00, Tax 0.00 #3','C','0','D',0,0,0,0.00,21.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 11:41:29',91,9999,1,'0','Credit Card','T','CC','0',0,0,0,0.00,0.00,-21.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,0,'',3,5),('2013-12-21 11:41:30',91,9999,1,'0','Change','T','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,8,0,0,0,0,0.00,'0',0,0,0,0,'',3,6),('2013-12-21 11:41:32',91,9999,1,'DISCOUNT','Discount','I','0','0',0,1,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,7),('2013-12-21 11:41:32',91,9999,1,'TAX','Tax','A','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,0,'',3,8),('2013-12-21 11:42:08',91,9999,2,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:42:22',91,9999,2,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 11:42:25',91,9999,2,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 11:43:28',91,9999,2,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 11:49:46',91,9999,3,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:49:52',91,9999,3,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 11:49:55',91,9999,3,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 11:51:08',91,9999,4,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 11:51:14',91,9999,4,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 11:51:18',91,9999,4,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 12:00:37',91,9999,5,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 12:00:43',91,9999,5,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 12:03:19',91,9999,5,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,3),('2013-12-21 12:03:23',91,9999,5,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 12:03:37',91,9999,5,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,5),('2013-12-21 12:03:49',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,6),('2013-12-21 12:03:59',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,7),('2013-12-21 12:04:42',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,8),('2013-12-21 12:08:31',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,9),('2013-12-21 12:09:05',91,9999,6,'10DP12','HABA-NTX','D',' ','X',12,1,0,0.00,10.00,10.00,10.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',0,1),('2013-12-21 12:09:11',91,9999,6,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,'',3,2),('2013-12-21 12:09:16',91,9999,6,'0','Subtotal 10.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,10.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,3),('2013-12-21 12:11:30',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,-1.00,-1.00,-1.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,4),('2013-12-21 12:11:32',91,9999,6,'0','Subtotal 9.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,9.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,5),('2013-12-21 12:15:34',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,1,0,1,0,0,0.00,'0',0,0,0,0,'',3,6),('2013-12-21 12:15:43',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,7),('2013-12-21 12:15:47',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,8),('2013-12-21 12:15:56',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,-6.00,-6.00,-6.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,9),('2013-12-21 12:15:58',91,9999,6,'0','Subtotal 62.97, Tax 0.00 #3','C','0','X',0,0,0,0.00,62.97,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,10),('2013-12-21 12:17:15',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,-1,0,0.00,19.99,-19.99,19.99,1,0,0.00,0.00,1,0,1,0,-1,0,0,0.00,'0',0,0,0,0,'',3,11),('2013-12-21 12:17:23',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,2.00,2.00,2.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,'',3,12),('2013-12-21 12:17:25',91,9999,6,'0','Subtotal 44.98, Tax 0.00 #3','C','0','X',0,0,0,0.00,44.98,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,'',3,13),('2013-12-21 13:55:42',91,9999,7,'10DP13','HABA-TX','D',' ',' ',13,1,0,0.00,10.00,10.00,10.00,1,0,0.00,0.00,1,0,0,2,1,0,0,0.00,'0',0,2,0,0,'',4,1),('2013-12-21 13:55:45',91,9999,7,'0000000000004','BADSCAN','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,'',4,2),('2013-12-21 13:55:55',91,9999,7,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,4,'',4,3),('2013-12-21 13:55:58',91,9999,7,'0049999900001','Supplement Discount','T','IC','C',44,1,0,0.00,-1.00,-1.00,-1.00,0,0,0.00,0.00,0,0,0,2,1,0,0,0.00,'0',0,2,0,0,'',4,4),('2013-12-21 13:55:59',91,9999,7,'0','** 2% Discount Applied **','0','0','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,4,2,0,0,0,0.00,'0',0,2,0,0,'',4,5),('2013-12-21 13:56:00',91,9999,7,'0','2% Discount','C','0','D',0,0,0,0.00,-0.20,0.00,0.00,0,0,0.00,0.00,0,0,5,2,0,0,0,0.00,'0',0,2,0,0,'',4,6),('2013-12-21 13:56:01',91,9999,7,'0','Subtotal 8.80, Tax 0.00 #4','C','0','D',0,0,0,0.00,8.80,0.00,0.00,0,0,0.00,0.00,0,0,3,2,0,0,0,0.00,'0',0,2,0,0,'',4,7),('2013-12-21 13:56:12',91,9999,7,'0','Credit Card','T','CC','0',0,0,0,0.00,0.00,-8.80,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,'',4,8),('2013-12-21 13:56:13',91,9999,7,'0','Change','T','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,8,2,0,0,0,0.00,'0',0,2,0,0,'',4,9),('2013-12-21 13:56:15',91,9999,7,'DISCOUNT','Discount','I','0','0',0,1,0,0.00,-0.20,-0.20,0.00,0,0,0.00,0.00,0,0,0,2,1,0,0,0.00,'0',0,2,0,0,'',4,10),('2013-12-21 13:56:15',91,9999,7,'TAX','Tax','A','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,'',4,11);
/*!40000 ALTER TABLE `localtrans_today` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localtransarchive`
--

DROP TABLE IF EXISTS `localtransarchive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localtransarchive` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localtransarchive`
--

LOCK TABLES `localtransarchive` WRITE;
/*!40000 ALTER TABLE `localtransarchive` DISABLE KEYS */;
/*!40000 ALTER TABLE `localtransarchive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localtranstoday`
--

DROP TABLE IF EXISTS `localtranstoday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localtranstoday` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(4) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT NULL,
  `charflag` int(11) DEFAULT NULL,
  `card_no` int(11) DEFAULT NULL,
  `trans_id` tinyint(4) DEFAULT NULL,
  KEY `datetime` (`datetime`),
  KEY `register_no` (`register_no`),
  KEY `emp_no` (`emp_no`),
  KEY `trans_no` (`trans_no`),
  KEY `upc` (`upc`),
  KEY `trans_type` (`trans_type`),
  KEY `department` (`department`),
  KEY `card_no` (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localtranstoday`
--

LOCK TABLES `localtranstoday` WRITE;
/*!40000 ALTER TABLE `localtranstoday` DISABLE KEYS */;
INSERT INTO `localtranstoday` VALUES ('2013-12-21 11:24:07',91,9999,1,'1DP1','Beer','D',' ',' ',1,1,0,0.00,1.00,1.00,1.00,3,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,1),('2013-12-21 11:37:16',91,9999,1,'20DP12','HABA-NTX','D',' ',' ',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,2),('2013-12-21 11:40:13',91,9999,1,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,3),('2013-12-21 11:40:25',91,9999,1,'0','Subtotal 21.00, Tax 0.00 #3','C','0','D',0,0,0,0.00,21.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,4),('2013-12-21 11:41:29',91,9999,1,'0','Credit Card','T','CC','0',0,0,0,0.00,0.00,-21.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,0,0,3,5),('2013-12-21 11:41:30',91,9999,1,'0','Change','T','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,8,0,0,0,0,0.00,'0',0,0,0,0,0,3,6),('2013-12-21 11:41:32',91,9999,1,'DISCOUNT','Discount','I','0','0',0,1,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,7),('2013-12-21 11:41:32',91,9999,1,'TAX','Tax','A','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,0,0,3,8),('2013-12-21 11:42:08',91,9999,2,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,1),('2013-12-21 11:42:22',91,9999,2,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,2),('2013-12-21 11:42:25',91,9999,2,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,3),('2013-12-21 11:43:28',91,9999,2,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,4),('2013-12-21 11:49:46',91,9999,3,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,1),('2013-12-21 11:49:52',91,9999,3,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,2),('2013-12-21 11:49:55',91,9999,3,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,3),('2013-12-21 11:51:08',91,9999,4,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,1),('2013-12-21 11:51:14',91,9999,4,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,2),('2013-12-21 11:51:18',91,9999,4,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,3),('2013-12-21 12:00:37',91,9999,5,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,1),('2013-12-21 12:00:43',91,9999,5,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,2),('2013-12-21 12:03:19',91,9999,5,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,3),('2013-12-21 12:03:23',91,9999,5,'0','Subtotal 20.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,20.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,4),('2013-12-21 12:03:37',91,9999,5,'20DP12','HABA-NTX','D',' ','X',12,1,0,0.00,20.00,20.00,20.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,5),('2013-12-21 12:03:49',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,6),('2013-12-21 12:03:59',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,7),('2013-12-21 12:04:42',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,8),('2013-12-21 12:08:31',91,9999,5,'0','Subtotal 40.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,40.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,9),('2013-12-21 12:09:05',91,9999,6,'10DP12','HABA-NTX','D',' ','X',12,1,0,0.00,10.00,10.00,10.00,0,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,0,1),('2013-12-21 12:09:11',91,9999,6,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','X',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,0,0,0,0,0.00,'0',0,0,0,3,0,3,2),('2013-12-21 12:09:16',91,9999,6,'0','Subtotal 10.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,10.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,3),('2013-12-21 12:11:30',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,-1.00,-1.00,-1.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,4),('2013-12-21 12:11:32',91,9999,6,'0','Subtotal 9.00, Tax 0.00 #3','C','0','X',0,0,0,0.00,9.00,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,5),('2013-12-21 12:15:34',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,1,0,1,0,0,0.00,'0',0,0,0,0,0,3,6),('2013-12-21 12:15:43',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,7),('2013-12-21 12:15:47',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,1,0,0.00,19.99,19.99,19.99,1,0,0.00,0.00,1,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,8),('2013-12-21 12:15:56',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,-6.00,-6.00,-6.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,9),('2013-12-21 12:15:58',91,9999,6,'0','Subtotal 62.97, Tax 0.00 #3','C','0','X',0,0,0,0.00,62.97,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,10),('2013-12-21 12:17:15',91,9999,6,'0000000066666','TEST SUPPLEMENT','I',' ','X',13,-1,0,0.00,19.99,-19.99,19.99,1,0,0.00,0.00,1,0,1,0,-1,0,0,0.00,'0',0,0,0,0,0,3,11),('2013-12-21 12:17:23',91,9999,6,'0049999900001','Supplement Discount','T','IC','X',44,1,0,0.00,2.00,2.00,2.00,0,0,0.00,0.00,0,0,0,0,1,0,0,0.00,'0',0,0,0,0,0,3,12),('2013-12-21 12:17:25',91,9999,6,'0','Subtotal 44.98, Tax 0.00 #3','C','0','X',0,0,0,0.00,44.98,0.00,0.00,0,0,0.00,0.00,0,0,3,0,0,0,0,0.00,'0',0,0,0,0,0,3,13),('2013-12-21 13:55:42',91,9999,7,'10DP13','HABA-TX','D',' ',' ',13,1,0,0.00,10.00,10.00,10.00,1,0,0.00,0.00,1,0,0,2,1,0,0,0.00,'0',0,2,0,0,0,4,1),('2013-12-21 13:55:45',91,9999,7,'0000000000004','BADSCAN','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,0,4,2),('2013-12-21 13:55:55',91,9999,7,'MEMENTRY','CARDNO IN NUMFLAG','L','OG','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,4,0,4,3),('2013-12-21 13:55:58',91,9999,7,'0049999900001','Supplement Discount','T','IC','C',44,1,0,0.00,-1.00,-1.00,-1.00,0,0,0.00,0.00,0,0,0,2,1,0,0,0.00,'0',0,2,0,0,0,4,4),('2013-12-21 13:55:59',91,9999,7,'0','** 2% Discount Applied **','0','0','D',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,4,2,0,0,0,0.00,'0',0,2,0,0,0,4,5),('2013-12-21 13:56:00',91,9999,7,'0','2% Discount','C','0','D',0,0,0,0.00,-0.20,0.00,0.00,0,0,0.00,0.00,0,0,5,2,0,0,0,0.00,'0',0,2,0,0,0,4,6),('2013-12-21 13:56:01',91,9999,7,'0','Subtotal 8.80, Tax 0.00 #4','C','0','D',0,0,0,0.00,8.80,0.00,0.00,0,0,0.00,0.00,0,0,3,2,0,0,0,0.00,'0',0,2,0,0,0,4,7),('2013-12-21 13:56:12',91,9999,7,'0','Credit Card','T','CC','0',0,0,0,0.00,0.00,-8.80,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,0,4,8),('2013-12-21 13:56:13',91,9999,7,'0','Change','T','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,8,2,0,0,0,0.00,'0',0,2,0,0,0,4,9),('2013-12-21 13:56:15',91,9999,7,'DISCOUNT','Discount','I','0','0',0,1,0,0.00,-0.20,-0.20,0.00,0,0,0.00,0.00,0,0,0,2,1,0,0,0.00,'0',0,2,0,0,0,4,10),('2013-12-21 13:56:15',91,9999,7,'TAX','Tax','A','0','0',0,0,0,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0,0,0,2,0,0,0,0.00,'0',0,2,0,0,0,4,11);
/*!40000 ALTER TABLE `localtranstoday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `ltt_grouped`
--

DROP TABLE IF EXISTS `ltt_grouped`;
/*!50001 DROP VIEW IF EXISTS `ltt_grouped`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ltt_grouped` (
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `itemqtty` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `unitprice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `charflag` tinyint NOT NULL,
  `grouper` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `ltt_receipt`
--

DROP TABLE IF EXISTS `ltt_receipt`;
/*!50001 DROP VIEW IF EXISTS `ltt_receipt`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ltt_receipt` (
  `description` tinyint NOT NULL,
  `comment` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `Status` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `ltt_receipt_reorder_g`
--

DROP TABLE IF EXISTS `ltt_receipt_reorder_g`;
/*!50001 DROP VIEW IF EXISTS `ltt_receipt_reorder_g`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ltt_receipt_reorder_g` (
  `description` tinyint NOT NULL,
  `comment` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `sequence` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `lttsubtotals`
--

DROP TABLE IF EXISTS `lttsubtotals`;
/*!50001 DROP VIEW IF EXISTS `lttsubtotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `lttsubtotals` (
  `tdate` tinyint NOT NULL,
  `taxTotal` tinyint NOT NULL,
  `fsTendered` tinyint NOT NULL,
  `fsEligible` tinyint NOT NULL,
  `fsTax` tinyint NOT NULL,
  `transDiscount` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `lttsummary`
--

DROP TABLE IF EXISTS `lttsummary`;
/*!50001 DROP VIEW IF EXISTS `lttsummary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `lttsummary` (
  `tdate` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `runningTotal` tinyint NOT NULL,
  `discountTTL` tinyint NOT NULL,
  `discTaxable` tinyint NOT NULL,
  `memSpecial` tinyint NOT NULL,
  `staffSpecial` tinyint NOT NULL,
  `discountableTTL` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `paymentTotal` tinyint NOT NULL,
  `tenderTotal` tinyint NOT NULL,
  `fsTendered` tinyint NOT NULL,
  `fsNoDiscTTL` tinyint NOT NULL,
  `fsDiscTTL` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `localTotal` tinyint NOT NULL,
  `voidTotal` tinyint NOT NULL,
  `LastID` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `memdiscountadd`
--

DROP TABLE IF EXISTS `memdiscountadd`;
/*!50001 DROP VIEW IF EXISTS `memdiscountadd`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `memdiscountadd` (
  `datetime` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `cost` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `discount` tinyint NOT NULL,
  `memDiscount` tinyint NOT NULL,
  `discountable` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `ItemQtty` tinyint NOT NULL,
  `volDiscType` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `VolSpecial` tinyint NOT NULL,
  `mixMatch` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `memType` tinyint NOT NULL,
  `staff` tinyint NOT NULL,
  `numflag` tinyint NOT NULL,
  `charflag` tinyint NOT NULL,
  `card_no` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `memdiscountremove`
--

DROP TABLE IF EXISTS `memdiscountremove`;
/*!50001 DROP VIEW IF EXISTS `memdiscountremove`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `memdiscountremove` (
  `datetime` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `cost` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `discount` tinyint NOT NULL,
  `memDiscount` tinyint NOT NULL,
  `discountable` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `ItemQtty` tinyint NOT NULL,
  `volDiscType` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `VolSpecial` tinyint NOT NULL,
  `mixMatch` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `memType` tinyint NOT NULL,
  `staff` tinyint NOT NULL,
  `numflag` tinyint NOT NULL,
  `charflag` tinyint NOT NULL,
  `card_no` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `receipt`
--

DROP TABLE IF EXISTS `receipt`;
/*!50001 DROP VIEW IF EXISTS `receipt`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `receipt` (
  `linetoprint` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `receipt_reorder_g`
--

DROP TABLE IF EXISTS `receipt_reorder_g`;
/*!50001 DROP VIEW IF EXISTS `receipt_reorder_g`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `receipt_reorder_g` (
  `linetoprint` tinyint NOT NULL,
  `sequence` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `dept_name` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `upc` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `receipt_reorder_unions_g`
--

DROP TABLE IF EXISTS `receipt_reorder_unions_g`;
/*!50001 DROP VIEW IF EXISTS `receipt_reorder_unions_g`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `receipt_reorder_unions_g` (
  `linetoprint` tinyint NOT NULL,
  `sequence` tinyint NOT NULL,
  `dept_name` tinyint NOT NULL,
  `ordered` tinyint NOT NULL,
  `upc` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_ltt_grouped`
--

DROP TABLE IF EXISTS `rp_ltt_grouped`;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_grouped`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_ltt_grouped` (
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `itemqtty` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `unitprice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `grouper` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_ltt_receipt`
--

DROP TABLE IF EXISTS `rp_ltt_receipt`;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_receipt`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_ltt_receipt` (
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `comment` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `Status` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_ltt_receipt_reorder_g`
--

DROP TABLE IF EXISTS `rp_ltt_receipt_reorder_g`;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_receipt_reorder_g`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_ltt_receipt_reorder_g` (
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `comment` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `sequence` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_lttsubtotals`
--

DROP TABLE IF EXISTS `rp_lttsubtotals`;
/*!50001 DROP VIEW IF EXISTS `rp_lttsubtotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_lttsubtotals` (
  `emp_no` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `tdate` tinyint NOT NULL,
  `taxTotal` tinyint NOT NULL,
  `fsTendered` tinyint NOT NULL,
  `fsEligible` tinyint NOT NULL,
  `fsTax` tinyint NOT NULL,
  `transDiscount` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_lttsummary`
--

DROP TABLE IF EXISTS `rp_lttsummary`;
/*!50001 DROP VIEW IF EXISTS `rp_lttsummary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_lttsummary` (
  `emp_no` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `tdate` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `runningTotal` tinyint NOT NULL,
  `discountTTL` tinyint NOT NULL,
  `discTaxable` tinyint NOT NULL,
  `memSpecial` tinyint NOT NULL,
  `staffSpecial` tinyint NOT NULL,
  `discountableTTL` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `paymentTotal` tinyint NOT NULL,
  `tenderTotal` tinyint NOT NULL,
  `fsTendered` tinyint NOT NULL,
  `fsNoDiscTTL` tinyint NOT NULL,
  `fsDiscTTL` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `localTotal` tinyint NOT NULL,
  `voidTotal` tinyint NOT NULL,
  `LastID` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_receipt`
--

DROP TABLE IF EXISTS `rp_receipt`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_receipt` (
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `linetoprint` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_receipt_header`
--

DROP TABLE IF EXISTS `rp_receipt_header`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_header`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_receipt_header` (
  `dateTimeStamp` tinyint NOT NULL,
  `memberID` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `discountTTL` tinyint NOT NULL,
  `memSpecial` tinyint NOT NULL,
  `staffSpecial` tinyint NOT NULL,
  `couponTotal` tinyint NOT NULL,
  `memCoupon` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `transDiscount` tinyint NOT NULL,
  `tenderTotal` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_receipt_reorder_g`
--

DROP TABLE IF EXISTS `rp_receipt_reorder_g`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_reorder_g`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_receipt_reorder_g` (
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `linetoprint` tinyint NOT NULL,
  `sequence` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `dept_name` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `upc` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_receipt_reorder_unions_g`
--

DROP TABLE IF EXISTS `rp_receipt_reorder_unions_g`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_reorder_unions_g`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_receipt_reorder_unions_g` (
  `linetoprint` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `sequence` tinyint NOT NULL,
  `dept_name` tinyint NOT NULL,
  `ordered` tinyint NOT NULL,
  `upc` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_subtotals`
--

DROP TABLE IF EXISTS `rp_subtotals`;
/*!50001 DROP VIEW IF EXISTS `rp_subtotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_subtotals` (
  `emp_no` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `LastID` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `runningTotal` tinyint NOT NULL,
  `discountableTotal` tinyint NOT NULL,
  `tenderTotal` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `paymentTotal` tinyint NOT NULL,
  `discountTTL` tinyint NOT NULL,
  `memSpecial` tinyint NOT NULL,
  `staffSpecial` tinyint NOT NULL,
  `fsEligible` tinyint NOT NULL,
  `fsTaxExempt` tinyint NOT NULL,
  `taxTotal` tinyint NOT NULL,
  `transDiscount` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `localTotal` tinyint NOT NULL,
  `voidTotal` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `screendisplay`
--

DROP TABLE IF EXISTS `screendisplay`;
/*!50001 DROP VIEW IF EXISTS `screendisplay`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `screendisplay` (
  `description` tinyint NOT NULL,
  `comment` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `lineColor` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `staffdiscountadd`
--

DROP TABLE IF EXISTS `staffdiscountadd`;
/*!50001 DROP VIEW IF EXISTS `staffdiscountadd`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `staffdiscountadd` (
  `datetime` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `cost` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `discount` tinyint NOT NULL,
  `memDiscount` tinyint NOT NULL,
  `discountable` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `ItemQtty` tinyint NOT NULL,
  `volDiscType` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `VolSpecial` tinyint NOT NULL,
  `mixMatch` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `memType` tinyint NOT NULL,
  `staff` tinyint NOT NULL,
  `numflag` tinyint NOT NULL,
  `charflag` tinyint NOT NULL,
  `card_no` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `staffdiscountremove`
--

DROP TABLE IF EXISTS `staffdiscountremove`;
/*!50001 DROP VIEW IF EXISTS `staffdiscountremove`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `staffdiscountremove` (
  `datetime` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `cost` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `discount` tinyint NOT NULL,
  `memDiscount` tinyint NOT NULL,
  `discountable` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `ItemQtty` tinyint NOT NULL,
  `volDiscType` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `VolSpecial` tinyint NOT NULL,
  `mixMatch` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `memType` tinyint NOT NULL,
  `staff` tinyint NOT NULL,
  `numflag` tinyint NOT NULL,
  `charflag` tinyint NOT NULL,
  `card_no` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `subtotals`
--

DROP TABLE IF EXISTS `subtotals`;
/*!50001 DROP VIEW IF EXISTS `subtotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `subtotals` (
  `LastID` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `runningTotal` tinyint NOT NULL,
  `discountableTotal` tinyint NOT NULL,
  `tenderTotal` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `paymentTotal` tinyint NOT NULL,
  `discountTTL` tinyint NOT NULL,
  `memSpecial` tinyint NOT NULL,
  `staffSpecial` tinyint NOT NULL,
  `fsEligible` tinyint NOT NULL,
  `fsTaxExempt` tinyint NOT NULL,
  `taxTotal` tinyint NOT NULL,
  `transDiscount` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `localTotal` tinyint NOT NULL,
  `voidTotal` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `suspended`
--

DROP TABLE IF EXISTS `suspended`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suspended` (
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `trans_type` varchar(1) DEFAULT NULL,
  `trans_subtype` varchar(2) DEFAULT NULL,
  `trans_status` varchar(1) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `regPrice` decimal(10,2) DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `memDiscount` decimal(10,2) DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` decimal(10,2) DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suspended`
--

LOCK TABLES `suspended` WRITE;
/*!40000 ALTER TABLE `suspended` DISABLE KEYS */;
/*!40000 ALTER TABLE `suspended` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `suspendedtoday`
--

DROP TABLE IF EXISTS `suspendedtoday`;
/*!50001 DROP VIEW IF EXISTS `suspendedtoday`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `suspendedtoday` (
  `datetime` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `trans_subtype` tinyint NOT NULL,
  `trans_status` tinyint NOT NULL,
  `department` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `scale` tinyint NOT NULL,
  `cost` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `regPrice` tinyint NOT NULL,
  `tax` tinyint NOT NULL,
  `foodstamp` tinyint NOT NULL,
  `discount` tinyint NOT NULL,
  `memDiscount` tinyint NOT NULL,
  `discountable` tinyint NOT NULL,
  `discounttype` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `percentDiscount` tinyint NOT NULL,
  `ItemQtty` tinyint NOT NULL,
  `volDiscType` tinyint NOT NULL,
  `volume` tinyint NOT NULL,
  `VolSpecial` tinyint NOT NULL,
  `mixMatch` tinyint NOT NULL,
  `matched` tinyint NOT NULL,
  `memType` tinyint NOT NULL,
  `staff` tinyint NOT NULL,
  `numflag` tinyint NOT NULL,
  `charflag` tinyint NOT NULL,
  `card_no` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `taxView`
--

DROP TABLE IF EXISTS `taxView`;
/*!50001 DROP VIEW IF EXISTS `taxView`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `taxView` (
  `id` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `taxTotal` tinyint NOT NULL,
  `fsTaxable` tinyint NOT NULL,
  `fsTaxTotal` tinyint NOT NULL,
  `foodstampTender` tinyint NOT NULL,
  `taxrate` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `taxrates`
--

DROP TABLE IF EXISTS `taxrates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxrates` (
  `id` int(11) NOT NULL DEFAULT '0',
  `rate` float DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taxrates`
--

LOCK TABLES `taxrates` WRITE;
/*!40000 ALTER TABLE `taxrates` DISABLE KEYS */;
/*!40000 ALTER TABLE `taxrates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `valutecRequest`
--

DROP TABLE IF EXISTS `valutecRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valutecRequest` (
  `date` int(11) DEFAULT NULL,
  `cashierNo` int(11) DEFAULT NULL,
  `laneNo` int(11) DEFAULT NULL,
  `transNo` int(11) DEFAULT NULL,
  `transID` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `identifier` varchar(10) DEFAULT NULL,
  `terminalID` varchar(20) DEFAULT NULL,
  `live` tinyint(4) DEFAULT NULL,
  `mode` varchar(32) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `PAN` varchar(19) DEFAULT NULL,
  `manual` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `valutecRequest`
--

LOCK TABLES `valutecRequest` WRITE;
/*!40000 ALTER TABLE `valutecRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `valutecRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `valutecRequestMod`
--

DROP TABLE IF EXISTS `valutecRequestMod`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valutecRequestMod` (
  `date` int(11) DEFAULT NULL,
  `cashierNo` int(11) DEFAULT NULL,
  `laneNo` int(11) DEFAULT NULL,
  `transNo` int(11) DEFAULT NULL,
  `transID` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `origAuthCode` varchar(9) DEFAULT NULL,
  `mode` varchar(32) DEFAULT NULL,
  `seconds` float DEFAULT NULL,
  `commErr` int(11) DEFAULT NULL,
  `httpCode` int(11) DEFAULT NULL,
  `validResponse` smallint(6) DEFAULT NULL,
  `xAuthorized` varchar(5) DEFAULT NULL,
  `xAuthorizationCode` varchar(9) DEFAULT NULL,
  `xBalance` varchar(8) DEFAULT NULL,
  `xErrorMsg` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `valutecRequestMod`
--

LOCK TABLES `valutecRequestMod` WRITE;
/*!40000 ALTER TABLE `valutecRequestMod` DISABLE KEYS */;
/*!40000 ALTER TABLE `valutecRequestMod` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `valutecResponse`
--

DROP TABLE IF EXISTS `valutecResponse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valutecResponse` (
  `date` int(11) DEFAULT NULL,
  `cashierNo` int(11) DEFAULT NULL,
  `laneNo` int(11) DEFAULT NULL,
  `transNo` int(11) DEFAULT NULL,
  `transID` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `identifier` varchar(10) DEFAULT NULL,
  `seconds` float DEFAULT NULL,
  `commErr` int(11) DEFAULT NULL,
  `httpCode` int(11) DEFAULT NULL,
  `validResponse` smallint(6) DEFAULT NULL,
  `xAuthorized` varchar(5) DEFAULT NULL,
  `xAuthorizationCode` varchar(9) DEFAULT NULL,
  `xBalance` varchar(8) DEFAULT NULL,
  `xErrorMsg` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `valutecResponse`
--

LOCK TABLES `valutecResponse` WRITE;
/*!40000 ALTER TABLE `valutecResponse` DISABLE KEYS */;
/*!40000 ALTER TABLE `valutecResponse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `ccReceiptView`
--

/*!50001 DROP TABLE IF EXISTS `ccReceiptView`*/;
/*!50001 DROP VIEW IF EXISTS `ccReceiptView`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ccReceiptView` AS select (case `r`.`mode` when 'tender' then 'Credit Card Purchase' when 'retail_sale' then 'Credit Card Purchase' when 'Credit_Sale' then 'Credit Card Purchase' when 'retail_alone_credit' then 'Credit Card Refund' when 'Credit_Return' then 'Credit Card Refund' when 'refund' then 'Credit Card Refund' else '' end) AS `tranType`,(case `r`.`mode` when 'refund' then (-(1) * `r`.`amount`) else `r`.`amount` end) AS `amount`,`r`.`PAN` AS `PAN`,(case `r`.`manual` when 1 then 'Manual' else 'Swiped' end) AS `entryMethod`,`r`.`issuer` AS `issuer`,`r`.`name` AS `name`,`s`.`xResultMessage` AS `xResultMessage`,`s`.`xApprovalNumber` AS `xApprovalNumber`,`s`.`xTransactionID` AS `xTransactionID`,`r`.`date` AS `date`,`r`.`cashierNo` AS `cashierNo`,`r`.`laneNo` AS `laneNo`,`r`.`transNo` AS `transNo`,`r`.`transID` AS `transID`,`r`.`datetime` AS `datetime`,0 AS `sortorder` from (`efsnetRequest` `r` join `efsnetResponse` `s` on(((`s`.`date` = `r`.`date`) and (`s`.`cashierNo` = `r`.`cashierNo`) and (`s`.`laneNo` = `r`.`laneNo`) and (`s`.`transNo` = `r`.`transNo`) and (`s`.`transID` = `r`.`transID`)))) where ((`s`.`validResponse` = 1) and ((`s`.`xResultMessage` like '%APPROVE%') or (`s`.`xResultMessage` like '%PENDING%'))) union all select (case `r`.`mode` when 'tender' then 'Credit Card Purchase CANCELED' when 'retail_sale' then 'Credit Card Purchase CANCELLED' when 'Credit_Sale' then 'Credit Card Purchase CANCELLED' when 'retail_alone_credit' then 'Credit Card Refund CANCELLED' when 'Credit_Return' then 'Credit Card Refund CANCELLED' when 'refund' then 'Credit Card Refund CANCELED' else '' end) AS `tranType`,(case `r`.`mode` when 'refund' then `r`.`amount` else (-(1) * `r`.`amount`) end) AS `amount`,`r`.`PAN` AS `PAN`,(case `r`.`manual` when 1 then 'Manual' else 'Swiped' end) AS `entryMethod`,`r`.`issuer` AS `issuer`,`r`.`name` AS `name`,`s`.`xResultMessage` AS `xResultMessage`,`s`.`xApprovalNumber` AS `xApprovalNumber`,`s`.`xTransactionID` AS `xTransactionID`,`r`.`date` AS `date`,`r`.`cashierNo` AS `cashierNo`,`r`.`laneNo` AS `laneNo`,`r`.`transNo` AS `transNo`,`r`.`transID` AS `transID`,`r`.`datetime` AS `datetime`,1 AS `sortorder` from ((`efsnetRequestMod` `m` join `efsnetRequest` `r` on(((`r`.`date` = `m`.`date`) and (`r`.`cashierNo` = `m`.`cashierNo`) and (`r`.`laneNo` = `m`.`laneNo`) and (`r`.`transNo` = `m`.`transNo`) and (`r`.`transID` = `m`.`transID`)))) join `efsnetResponse` `s` on(((`s`.`date` = `r`.`date`) and (`s`.`cashierNo` = `r`.`cashierNo`) and (`s`.`laneNo` = `r`.`laneNo`) and (`s`.`transNo` = `r`.`transNo`) and (`s`.`transID` = `r`.`transID`)))) where ((`s`.`validResponse` = 1) and (`s`.`xResultMessage` like '%APPROVE%') and (`m`.`validResponse` = 1) and ((`m`.`xResponseCode` = 0) or (`m`.`xResultMessage` like '%APPROVE%')) and (`m`.`mode` = 'void')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `gcReceiptView`
--

/*!50001 DROP TABLE IF EXISTS `gcReceiptView`*/;
/*!50001 DROP VIEW IF EXISTS `gcReceiptView`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `gcReceiptView` AS select (case `r`.`mode` when 'tender' then 'Gift Card Purchase' when 'refund' then 'Gift Card Refund' when 'addvalue' then 'Gift Card Add Value' when 'activate' then 'Gift Card Activation' else 'Gift Card Transaction' end) AS `tranType`,(case `r`.`mode` when 'refund' then (-(1) * `r`.`amount`) else `r`.`amount` end) AS `amount`,`r`.`terminalID` AS `terminalID`,`r`.`PAN` AS `PAN`,(case `r`.`manual` when 1 then 'Manual' else 'Swiped' end) AS `entryMethod`,`s`.`xAuthorizationCode` AS `xAuthorizationCode`,`s`.`xBalance` AS `xBalance`,'' AS `xVoidCode`,`r`.`date` AS `date`,`r`.`cashierNo` AS `cashierNo`,`r`.`laneNo` AS `laneNo`,`r`.`transNo` AS `transNo`,`r`.`transID` AS `transID`,`r`.`datetime` AS `datetime`,0 AS `sortorder` from (`valutecRequest` `r` join `valutecResponse` `s` on(((`s`.`date` = `r`.`date`) and (`s`.`cashierNo` = `r`.`cashierNo`) and (`s`.`laneNo` = `r`.`laneNo`) and (`s`.`transNo` = `r`.`transNo`) and (`s`.`transID` = `r`.`transID`)))) where ((`s`.`validResponse` = 1) and ((`s`.`xAuthorized` = 'true') or (`s`.`xAuthorized` = 'Appro'))) union all select (case `r`.`mode` when 'tender' then 'Gift Card Purchase CANCELED' when 'refund' then 'Gift Card Refund CANCELED' when 'addvalue' then 'Gift Card Add Value CANCELED' when 'activate' then 'Gift Card Activation CANCELED' else 'Gift Card Transaction CANCELED' end) AS `tranType`,(case `r`.`mode` when 'refund' then `r`.`amount` else (-(1) * `r`.`amount`) end) AS `amount`,`r`.`terminalID` AS `terminalID`,`r`.`PAN` AS `PAN`,(case `r`.`manual` when 1 then 'Manual' else 'Swiped' end) AS `entryMethod`,`m`.`origAuthCode` AS `xAuthorizationCode`,`m`.`xBalance` AS `xBalance`,`m`.`xAuthorizationCode` AS `xVoidCode`,`r`.`date` AS `date`,`r`.`cashierNo` AS `cashierNo`,`r`.`laneNo` AS `laneNo`,`r`.`transNo` AS `transNo`,`r`.`transID` AS `transID`,`m`.`datetime` AS `datetime`,1 AS `sortorder` from (`valutecRequestMod` `m` join `valutecRequest` `r` on(((`r`.`date` = `m`.`date`) and (`r`.`cashierNo` = `m`.`cashierNo`) and (`r`.`laneNo` = `m`.`laneNo`) and (`r`.`transNo` = `m`.`transNo`) and (`r`.`transID` = `m`.`transID`)))) where ((`m`.`validResponse` = 1) and ((`m`.`xAuthorized` = 'true') or (`m`.`xAuthorized` = 'Appro')) and (`m`.`mode` = 'void')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ltt_grouped`
--

/*!50001 DROP TABLE IF EXISTS `ltt_grouped`*/;
/*!50001 DROP VIEW IF EXISTS `ltt_grouped`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ltt_grouped` AS select `localtemptrans`.`upc` AS `upc`,`localtemptrans`.`description` AS `description`,`localtemptrans`.`trans_type` AS `trans_type`,`localtemptrans`.`trans_subtype` AS `trans_subtype`,sum(`localtemptrans`.`ItemQtty`) AS `itemqtty`,`localtemptrans`.`discounttype` AS `discounttype`,`localtemptrans`.`volume` AS `volume`,`localtemptrans`.`trans_status` AS `trans_status`,(case when (`localtemptrans`.`voided` = 1) then 0 else `localtemptrans`.`voided` end) AS `voided`,`localtemptrans`.`department` AS `department`,sum(`localtemptrans`.`quantity`) AS `quantity`,`localtemptrans`.`matched` AS `matched`,min(`localtemptrans`.`trans_id`) AS `trans_id`,`localtemptrans`.`scale` AS `scale`,sum(`localtemptrans`.`unitPrice`) AS `unitprice`,cast(sum(`localtemptrans`.`total`) as decimal(10,2)) AS `total`,sum(`localtemptrans`.`regPrice`) AS `regPrice`,`localtemptrans`.`tax` AS `tax`,`localtemptrans`.`foodstamp` AS `foodstamp`,`localtemptrans`.`charflag` AS `charflag`,(case when ((`localtemptrans`.`trans_status` = 'd') or (`localtemptrans`.`scale` = 1) or (`localtemptrans`.`trans_type` = 'T')) then `localtemptrans`.`trans_id` else `localtemptrans`.`scale` end) AS `grouper` from `localtemptrans` where ((not((`localtemptrans`.`description` like '** YOU SAVED %'))) and (`localtemptrans`.`trans_status` = 'M')) group by `localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`trans_type`,`localtemptrans`.`trans_subtype`,`localtemptrans`.`discounttype`,`localtemptrans`.`volume`,`localtemptrans`.`trans_status`,`localtemptrans`.`department`,`localtemptrans`.`scale`,(case when (`localtemptrans`.`voided` = 1) then 0 else `localtemptrans`.`voided` end),`localtemptrans`.`matched`,`localtemptrans`.`tax`,`localtemptrans`.`foodstamp`,`localtemptrans`.`charflag`,(case when ((`localtemptrans`.`trans_status` = 'd') or (`localtemptrans`.`scale` = 1) or (`localtemptrans`.`trans_type` = 'T')) then `localtemptrans`.`trans_id` else `localtemptrans`.`scale` end) union all select `localtemptrans`.`upc` AS `upc`,(case when (`localtemptrans`.`numflag` = 1) then concat(`localtemptrans`.`description`,'*') else `localtemptrans`.`description` end) AS `description`,`localtemptrans`.`trans_type` AS `trans_type`,`localtemptrans`.`trans_subtype` AS `trans_subtype`,sum(`localtemptrans`.`ItemQtty`) AS `itemqtty`,`localtemptrans`.`discounttype` AS `discounttype`,`localtemptrans`.`volume` AS `volume`,`localtemptrans`.`trans_status` AS `trans_status`,(case when (`localtemptrans`.`voided` = 1) then 0 else `localtemptrans`.`voided` end) AS `voided`,`localtemptrans`.`department` AS `department`,sum(`localtemptrans`.`quantity`) AS `quantity`,`localtemptrans`.`matched` AS `matched`,min(`localtemptrans`.`trans_id`) AS `trans_id`,`localtemptrans`.`scale` AS `scale`,`localtemptrans`.`unitPrice` AS `unitprice`,cast(sum(`localtemptrans`.`total`) as decimal(10,2)) AS `total`,`localtemptrans`.`regPrice` AS `regPrice`,`localtemptrans`.`tax` AS `tax`,`localtemptrans`.`foodstamp` AS `foodstamp`,`localtemptrans`.`charflag` AS `charflag`,(case when ((`localtemptrans`.`trans_status` = 'd') or (`localtemptrans`.`scale` = 1) or (`localtemptrans`.`trans_type` = 'T')) then `localtemptrans`.`trans_id` else `localtemptrans`.`scale` end) AS `grouper` from `localtemptrans` where ((not((`localtemptrans`.`description` like '** YOU SAVED %'))) and (`localtemptrans`.`trans_status` <> 'M') and (`localtemptrans`.`trans_type` <> 'L')) group by `localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`trans_type`,`localtemptrans`.`trans_subtype`,`localtemptrans`.`discounttype`,`localtemptrans`.`volume`,`localtemptrans`.`trans_status`,`localtemptrans`.`department`,`localtemptrans`.`scale`,(case when (`localtemptrans`.`voided` = 1) then 0 else `localtemptrans`.`voided` end),`localtemptrans`.`unitPrice`,`localtemptrans`.`regPrice`,`localtemptrans`.`matched`,`localtemptrans`.`tax`,`localtemptrans`.`foodstamp`,`localtemptrans`.`charflag`,(case when ((`localtemptrans`.`trans_status` = 'd') or (`localtemptrans`.`scale` = 1) or (`localtemptrans`.`trans_type` = 'T')) then `localtemptrans`.`trans_id` else `localtemptrans`.`scale` end) union all select `localtemptrans`.`upc` AS `upc`,(case when (`localtemptrans`.`discounttype` = 1) then concat(' > you saved $',cast(cast(sum(((`localtemptrans`.`quantity` * `localtemptrans`.`regPrice`) - (`localtemptrans`.`quantity` * `localtemptrans`.`unitPrice`))) as decimal(10,2)) as char(20) charset latin1),'  <') when (`localtemptrans`.`discounttype` = 2) then concat(' > you saved $',cast(cast(sum(((`localtemptrans`.`quantity` * `localtemptrans`.`regPrice`) - (`localtemptrans`.`quantity` * `localtemptrans`.`unitPrice`))) as decimal(10,2)) as char(20) charset latin1),'  Member Special <') end) AS `description`,`localtemptrans`.`trans_type` AS `trans_type`,'0' AS `trans_subtype`,0 AS `itemQtty`,`localtemptrans`.`discounttype` AS `discounttype`,`localtemptrans`.`volume` AS `volume`,'D' AS `trans_status`,2 AS `voided`,`localtemptrans`.`department` AS `department`,0 AS `quantity`,`localtemptrans`.`matched` AS `matched`,(min(`localtemptrans`.`trans_id`) + 1) AS `trans_id`,`localtemptrans`.`scale` AS `scale`,0 AS `unitprice`,0 AS `total`,0 AS `regPrice`,0 AS `tax`,0 AS `foodstamp`,`localtemptrans`.`charflag` AS `charflag`,(case when ((`localtemptrans`.`trans_status` = 'd') or (`localtemptrans`.`scale` = 1)) then `localtemptrans`.`trans_id` else `localtemptrans`.`scale` end) AS `grouper` from `localtemptrans` where ((not((`localtemptrans`.`description` like '** YOU SAVED %'))) and ((`localtemptrans`.`discounttype` = 1) or (`localtemptrans`.`discounttype` = 2)) and (`localtemptrans`.`trans_type` <> 'L')) group by `localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`trans_type`,`localtemptrans`.`trans_subtype`,`localtemptrans`.`discounttype`,`localtemptrans`.`volume`,`localtemptrans`.`department`,`localtemptrans`.`scale`,`localtemptrans`.`matched`,(case when ((`localtemptrans`.`trans_status` = 'd') or (`localtemptrans`.`scale` = 1)) then `localtemptrans`.`trans_id` else `localtemptrans`.`scale` end) having (cast(sum(((`localtemptrans`.`quantity` * `localtemptrans`.`regPrice`) - (`localtemptrans`.`quantity` * `localtemptrans`.`unitPrice`))) as decimal(10,2)) <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ltt_receipt`
--

/*!50001 DROP TABLE IF EXISTS `ltt_receipt`*/;
/*!50001 DROP VIEW IF EXISTS `ltt_receipt`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ltt_receipt` AS select `l`.`description` AS `description`,(case when (`l`.`voided` = 5) then 'Discount' when (`l`.`trans_status` = 'M') then 'Mbr special' when (`l`.`trans_status` = 'S') then 'Staff special' when (`l`.`unitPrice` = 0.01) then '' when ((`l`.`scale` <> 0) and (`l`.`quantity` <> 0)) then concat(`l`.`quantity`,' @ ',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (abs(`l`.`ItemQtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` = 1)) then concat(`l`.`volume`,' / ',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (abs(`l`.`ItemQtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` <> 1)) then concat(`l`.`quantity`,' @ ',`l`.`volume`,' /',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (`l`.`discounttype` = 3)) then concat(`l`.`ItemQtty`,' / ',`l`.`unitPrice`) when (abs(`l`.`ItemQtty`) > 1) then concat(`l`.`quantity`,' @ ',`l`.`unitPrice`) when (`l`.`matched` > 0) then '1 w/ vol adj' else '' end) AS `comment`,`l`.`total` AS `total`,(case when (`l`.`trans_status` = 'V') then 'VD' when (`l`.`trans_status` = 'R') then 'RF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` <> 0)) then 'TF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` = 0)) then 'T' when ((`l`.`tax` = 0) and (`l`.`foodstamp` <> 0)) then 'F' when ((`l`.`tax` > 1) and (`l`.`foodstamp` <> 0)) then concat(substr(`t`.`description`,1,1),'F') when ((`l`.`tax` > 1) and (`l`.`foodstamp` = 0)) then substr(`t`.`description`,1,1) when ((`l`.`tax` = 0) and (`l`.`foodstamp` = 0)) then '' end) AS `Status`,`l`.`trans_type` AS `trans_type`,`l`.`unitPrice` AS `unitPrice`,`l`.`voided` AS `voided`,(case when (`l`.`upc` = 'DISCOUNT') then ((select max(`localtemptrans`.`trans_id`) from `localtemptrans` where (`localtemptrans`.`voided` = 3)) - 1) when (`l`.`trans_type` = 'T') then (`l`.`trans_id` + 99999) else `l`.`trans_id` end) AS `trans_id` from (`localtemptrans` `l` left join `taxrates` `t` on((`l`.`tax` = `t`.`id`))) where ((`l`.`voided` <> 5) and (`l`.`upc` <> 'TAX') and (`l`.`trans_type` <> 'L')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ltt_receipt_reorder_g`
--

/*!50001 DROP TABLE IF EXISTS `ltt_receipt_reorder_g`*/;
/*!50001 DROP VIEW IF EXISTS `ltt_receipt_reorder_g`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ltt_receipt_reorder_g` AS select `l`.`description` AS `description`,(case when (`l`.`voided` = 5) then 'Discount' when (`l`.`trans_status` = 'M') then 'Mbr special' when (`l`.`trans_status` = 'S') then 'Staff special' when (`l`.`unitprice` = 0.01) then '' when (`l`.`charflag` = 'SO') then '' when ((`l`.`scale` <> 0) and (`l`.`quantity` <> 0)) then concat(cast(`l`.`quantity` as char charset latin1),' @ ',cast(`l`.`unitprice` as char charset latin1)) when ((abs(`l`.`itemqtty`) > 1) and (abs(`l`.`itemqtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` = 1)) then concat(cast(`l`.`volume` as char charset latin1),' / ',cast(`l`.`unitprice` as char charset latin1)) when ((abs(`l`.`itemqtty`) > 1) and (abs(`l`.`itemqtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` <> 1)) then concat(cast(`l`.`quantity` as char charset latin1),' @ ',cast(`l`.`volume` as char charset latin1),' /',cast(`l`.`unitprice` as char charset latin1)) when ((abs(`l`.`itemqtty`) > 1) and (`l`.`discounttype` = 3)) then concat(cast(`l`.`itemqtty` as char charset latin1),' / ',cast(`l`.`unitprice` as char charset latin1)) when (abs(`l`.`itemqtty`) > 1) then concat(cast(`l`.`quantity` as char charset latin1),' @ ',cast(`l`.`unitprice` as char charset latin1)) when (`l`.`matched` > 0) then '1 w/ vol adj' else '' end) AS `comment`,`l`.`total` AS `total`,(case when (`l`.`trans_status` = 'V') then 'VD' when (`l`.`trans_status` = 'R') then 'RF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` <> 0)) then 'TF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` = 0)) then 'T' when ((`l`.`tax` > 1) and (`l`.`foodstamp` <> 0)) then concat(substr(`t`.`description`,1,1),'F') when ((`l`.`tax` > 1) and (`l`.`foodstamp` = 0)) then substr(`t`.`description`,1,1) when ((`l`.`tax` = 0) and (`l`.`foodstamp` <> 0)) then 'F' when ((`l`.`tax` = 0) and (`l`.`foodstamp` = 0)) then '' end) AS `status`,(case when ((`l`.`trans_subtype` = 'CM') or (`l`.`voided` in (10,17))) then 'CM' else `l`.`trans_type` end) AS `trans_type`,`l`.`unitprice` AS `unitPrice`,`l`.`voided` AS `voided`,(`l`.`trans_id` + 1000) AS `sequence`,`l`.`department` AS `department`,`l`.`upc` AS `upc`,`l`.`trans_subtype` AS `trans_subtype` from (`ltt_grouped` `l` left join `taxrates` `t` on((`l`.`tax` = `t`.`id`))) where ((`l`.`voided` <> 5) and (`l`.`upc` <> 'TAX') and (`l`.`upc` <> 'DISCOUNT') and (`l`.`trans_type` <> 'L') and ((`l`.`trans_status` <> 'M') or (`l`.`total` <> cast('0.00' as decimal(10,2))))) union select '  ' AS `description`,' ' AS `comment`,0 AS `total`,' ' AS `Status`,' ' AS `trans_type`,0 AS `unitPrice`,0 AS `voided`,999 AS `sequence`,'' AS `department`,'' AS `upc`,'' AS `trans_subtype` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `lttsubtotals`
--

/*!50001 DROP TABLE IF EXISTS `lttsubtotals`*/;
/*!50001 DROP VIEW IF EXISTS `lttsubtotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `lttsubtotals` AS select `lttsummary`.`tdate` AS `tdate`,0 AS `taxTotal`,`lttsummary`.`fsTendered` AS `fsTendered`,cast(((`lttsummary`.`fsTendered` + `lttsummary`.`fsNoDiscTTL`) + (`lttsummary`.`fsDiscTTL` * ((100 - `lttsummary`.`percentDiscount`) / 100))) as decimal(10,2)) AS `fsEligible`,0 AS `fsTax`,cast(((`lttsummary`.`discountableTTL` * `lttsummary`.`percentDiscount`) / 100) as decimal(10,2)) AS `transDiscount` from `lttsummary` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `lttsummary`
--

/*!50001 DROP TABLE IF EXISTS `lttsummary`*/;
/*!50001 DROP VIEW IF EXISTS `lttsummary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `lttsummary` AS select (case when isnull(min(`localtemptrans`.`datetime`)) then now() else min(`localtemptrans`.`datetime`) end) AS `tdate`,max(`localtemptrans`.`card_no`) AS `card_no`,cast(sum(`localtemptrans`.`total`) as decimal(10,2)) AS `runningTotal`,cast(sum((case when (`localtemptrans`.`discounttype` = 1) then `localtemptrans`.`discount` else 0 end)) as decimal(10,2)) AS `discountTTL`,cast(sum((case when ((`localtemptrans`.`discountable` <> 0) and (`localtemptrans`.`tax` <> 0)) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `discTaxable`,cast(sum((case when (`localtemptrans`.`discounttype` in (2,3)) then `localtemptrans`.`memDiscount` else 0 end)) as decimal(10,2)) AS `memSpecial`,cast(sum((case when (`localtemptrans`.`discounttype` = 4) then `localtemptrans`.`memDiscount` else 0 end)) as decimal(10,2)) AS `staffSpecial`,cast(sum((case when (`localtemptrans`.`discountable` = 0) then 0 else `localtemptrans`.`total` end)) as decimal(10,2)) AS `discountableTTL`,cast(sum((case when ((`localtemptrans`.`trans_subtype` = 'MI') or (`localtemptrans`.`trans_subtype` = 'CX')) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `chargeTotal`,cast(sum((case when (`localtemptrans`.`department` = 990) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `paymentTotal`,cast(sum((case when ((`localtemptrans`.`trans_type` = 'T') and (`localtemptrans`.`department` = 0)) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `tenderTotal`,cast(sum((case when ((`localtemptrans`.`trans_subtype` = 'FS') or (`localtemptrans`.`trans_subtype` = 'EF')) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `fsTendered`,cast(sum((case when ((`localtemptrans`.`foodstamp` = 1) and (`localtemptrans`.`discountable` = 0)) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `fsNoDiscTTL`,cast(sum((case when ((`localtemptrans`.`foodstamp` = 1) and (`localtemptrans`.`discountable` <> 0)) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `fsDiscTTL`,(case when (isnull(max(`localtemptrans`.`percentDiscount`)) or (max(`localtemptrans`.`percentDiscount`) < 0)) then 0.00 else max(cast(`localtemptrans`.`percentDiscount` as decimal(10,0))) end) AS `percentDiscount`,cast(sum((case when (`localtemptrans`.`numflag` = 1) then `localtemptrans`.`total` else 0 end)) as decimal(10,2)) AS `localTotal`,cast(sum((case when (`localtemptrans`.`trans_status` = 'V') then -(`localtemptrans`.`total`) else 0 end)) as decimal(10,2)) AS `voidTotal`,max(`localtemptrans`.`trans_id`) AS `LastID` from `localtemptrans` where (`localtemptrans`.`trans_type` <> 'L') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `memdiscountadd`
--

/*!50001 DROP TABLE IF EXISTS `memdiscountadd`*/;
/*!50001 DROP VIEW IF EXISTS `memdiscountadd`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `memdiscountadd` AS select max(`localtemptrans`.`datetime`) AS `datetime`,`localtemptrans`.`register_no` AS `register_no`,`localtemptrans`.`emp_no` AS `emp_no`,`localtemptrans`.`trans_no` AS `trans_no`,`localtemptrans`.`upc` AS `upc`,(case when (`localtemptrans`.`volDiscType` in (3,4)) then 'Set Discount' else `localtemptrans`.`description` end) AS `description`,'I' AS `trans_type`,'' AS `trans_subtype`,'M' AS `trans_status`,max(`localtemptrans`.`department`) AS `department`,1 AS `quantity`,0 AS `scale`,0 AS `cost`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `unitPrice`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `total`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `regPrice`,max(`localtemptrans`.`tax`) AS `tax`,max(`localtemptrans`.`foodstamp`) AS `foodstamp`,0 AS `discount`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `memDiscount`,max(`localtemptrans`.`discountable`) AS `discountable`,20 AS `discounttype`,8 AS `voided`,max(`localtemptrans`.`percentDiscount`) AS `percentDiscount`,0 AS `ItemQtty`,0 AS `volDiscType`,0 AS `volume`,0 AS `VolSpecial`,0 AS `mixMatch`,0 AS `matched`,max(`localtemptrans`.`memType`) AS `memType`,max(`localtemptrans`.`staff`) AS `staff`,0 AS `numflag`,'' AS `charflag`,`localtemptrans`.`card_no` AS `card_no` from `localtemptrans` where (((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` = `localtemptrans`.`regPrice`)) or (`localtemptrans`.`trans_status` = 'M')) group by `localtemptrans`.`register_no`,`localtemptrans`.`emp_no`,`localtemptrans`.`trans_no`,`localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`card_no` having (sum(`localtemptrans`.`memDiscount`) <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `memdiscountremove`
--

/*!50001 DROP TABLE IF EXISTS `memdiscountremove`*/;
/*!50001 DROP VIEW IF EXISTS `memdiscountremove`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `memdiscountremove` AS select max(`localtemptrans`.`datetime`) AS `datetime`,`localtemptrans`.`register_no` AS `register_no`,`localtemptrans`.`emp_no` AS `emp_no`,`localtemptrans`.`trans_no` AS `trans_no`,`localtemptrans`.`upc` AS `upc`,(case when (`localtemptrans`.`volDiscType` in (3,4)) then 'Set Discount' else `localtemptrans`.`description` end) AS `description`,'I' AS `trans_type`,'' AS `trans_subtype`,'M' AS `trans_status`,max(`localtemptrans`.`department`) AS `department`,1 AS `quantity`,0 AS `scale`,0 AS `cost`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `unitPrice`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `total`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `regPrice`,max(`localtemptrans`.`tax`) AS `tax`,max(`localtemptrans`.`foodstamp`) AS `foodstamp`,0 AS `discount`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `memDiscount`,max(`localtemptrans`.`discountable`) AS `discountable`,20 AS `discounttype`,8 AS `voided`,max(`localtemptrans`.`percentDiscount`) AS `percentDiscount`,0 AS `ItemQtty`,0 AS `volDiscType`,0 AS `volume`,0 AS `VolSpecial`,0 AS `mixMatch`,0 AS `matched`,max(`localtemptrans`.`memType`) AS `memType`,max(`localtemptrans`.`staff`) AS `staff`,0 AS `numflag`,'' AS `charflag`,`localtemptrans`.`card_no` AS `card_no` from `localtemptrans` where (((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) or (`localtemptrans`.`trans_status` = 'M')) group by `localtemptrans`.`register_no`,`localtemptrans`.`emp_no`,`localtemptrans`.`trans_no`,`localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`card_no` having (sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end)) <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `receipt`
--

/*!50001 DROP TABLE IF EXISTS `receipt`*/;
/*!50001 DROP VIEW IF EXISTS `receipt`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `receipt` AS select (case when (`ltt_receipt`.`trans_type` = 'T') then concat(substr(concat(ucase(trim(`ltt_receipt`.`description`)),repeat(' ',44)),1,44),right(concat(repeat(' ',8),format((-(1) * `ltt_receipt`.`total`),2)),8),right(concat(repeat(' ',4),`ltt_receipt`.`Status`),4)) when (`ltt_receipt`.`voided` = 3) then concat(substr(concat(`ltt_receipt`.`description`,repeat(' ',30)),1,30),repeat(' ',9),'TOTAL',right(concat(repeat(' ',8),format(`ltt_receipt`.`unitPrice`,2)),8)) when (`ltt_receipt`.`voided` = 2) then `ltt_receipt`.`description` when (`ltt_receipt`.`voided` = 4) then `ltt_receipt`.`description` when (`ltt_receipt`.`voided` = 6) then `ltt_receipt`.`description` when ((`ltt_receipt`.`voided` = 7) or (`ltt_receipt`.`voided` = 17)) then concat(substr(concat(`ltt_receipt`.`description`,repeat(' ',30)),1,30),repeat(' ',14),right(concat(repeat(' ',8),format(`ltt_receipt`.`unitPrice`,2)),8),right(concat(repeat(' ',4),`ltt_receipt`.`Status`),4)) else concat(substr(concat(`ltt_receipt`.`description`,repeat(' ',30)),1,30),' ',substr(concat(`ltt_receipt`.`comment`,repeat(' ',13)),1,13),right(concat(repeat(' ',8),format(`ltt_receipt`.`total`,2)),8),right(concat(repeat(' ',4),`ltt_receipt`.`Status`),4)) end) AS `linetoprint` from `ltt_receipt` order by `ltt_receipt`.`trans_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `receipt_reorder_g`
--

/*!50001 DROP TABLE IF EXISTS `receipt_reorder_g`*/;
/*!50001 DROP VIEW IF EXISTS `receipt_reorder_g`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `receipt_reorder_g` AS select (case when (`r`.`trans_type` = 'T') then (case when ((`r`.`trans_subtype` = 'CP') and (`r`.`upc` <> '0')) then concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),' ',substr(concat(`r`.`comment`,repeat(' ',12)),1,12),right(concat(repeat(' ',8),cast(`r`.`total` as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) else concat(right(concat(repeat(' ',44),ucase(`r`.`description`)),44),right(concat(repeat(' ',8),cast((-(1) * `r`.`total`) as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) end) when (`r`.`voided` = 3) then concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),repeat(' ',9),'TOTAL',right(concat(repeat(' ',8),cast(`r`.`unitPrice` as char charset latin1)),8)) when (`r`.`voided` = 2) then `r`.`description` when (`r`.`voided` = 4) then `r`.`description` when (`r`.`voided` = 6) then `r`.`description` when ((`r`.`voided` = 7) or (`r`.`voided` = 17)) then concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),repeat(' ',14),right(concat(repeat(' ',8),cast(`r`.`unitPrice` as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) when (`r`.`sequence` < 1000) then `r`.`description` else concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),' ',substr(concat(`r`.`comment`,repeat(' ',12)),1,12),right(concat(repeat(' ',8),cast(`r`.`total` as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) end) AS `linetoprint`,`r`.`sequence` AS `sequence`,`r`.`department` AS `department`,`d`.`subdept_name` AS `dept_name`,`r`.`trans_type` AS `trans_type`,`r`.`upc` AS `upc` from (`rut_translog`.`ltt_receipt_reorder_g` `r` left join `rut_opdata`.`subdepts` `d` on((`r`.`department` = `d`.`dept_ID`))) where ((`r`.`total` <> 0) or (`r`.`unitPrice` = 0)) order by `r`.`sequence` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `receipt_reorder_unions_g`
--

/*!50001 DROP TABLE IF EXISTS `receipt_reorder_unions_g`*/;
/*!50001 DROP VIEW IF EXISTS `receipt_reorder_unions_g`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `receipt_reorder_unions_g` AS select `receipt_reorder_g`.`linetoprint` AS `linetoprint`,`receipt_reorder_g`.`sequence` AS `sequence`,`receipt_reorder_g`.`dept_name` AS `dept_name`,1 AS `ordered`,`receipt_reorder_g`.`upc` AS `upc` from `rut_translog`.`receipt_reorder_g` where (((`receipt_reorder_g`.`department` <> 0) or (`receipt_reorder_g`.`trans_type` in ('CM','I'))) and (not((`receipt_reorder_g`.`linetoprint` like 'member discount%')))) union all select replace(replace(replace(`r1`.`linetoprint`,'** T',' = t'),' **',' = '),'W','w') AS `linetoprint`,`r1`.`sequence` AS `sequence`,`r2`.`dept_name` AS `dept_name`,1 AS `ordered`,`r2`.`upc` AS `upc` from (`rut_translog`.`receipt_reorder_g` `r1` join `rut_translog`.`receipt_reorder_g` `r2` on(((`r1`.`sequence` + 1) = `r2`.`sequence`))) where ((`r1`.`linetoprint` like '** T%') and (`r2`.`dept_name` is not null) and (`r1`.`linetoprint` <> '** Tare Weight 0 **')) union all select concat(substr(concat('** ',trim(cast(`subtotals`.`percentDiscount` as char charset latin1)),'% Discount Applied **',repeat(' ',30)),1,30),' ',repeat(' ',13),right(concat(repeat(' ',8),cast((-(1) * `subtotals`.`transDiscount`) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,0 AS `sequence`,NULL AS `dept_name`,2 AS `ordered`,'' AS `upc` from `rut_translog`.`subtotals` where (`subtotals`.`percentDiscount` <> 0) union all select `receipt_reorder_g`.`linetoprint` AS `linetoprint`,`receipt_reorder_g`.`sequence` AS `sequence`,NULL AS `dept_name`,2 AS `ordered`,`receipt_reorder_g`.`upc` AS `upc` from `rut_translog`.`receipt_reorder_g` where (`receipt_reorder_g`.`linetoprint` like 'member discount%') union all select concat(right(concat(repeat(' ',44),'SUBTOTAL'),44),right(concat(repeat(' ',8),cast(round(((`l`.`runningTotal` - `s`.`taxTotal`) - `l`.`tenderTotal`),2) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,1 AS `sequence`,NULL AS `dept_name`,3 AS `ordered`,'' AS `upc` from (`rut_translog`.`lttsummary` `l` join `rut_translog`.`subtotals` `s`) union all select concat(right(concat(repeat(' ',44),'TAX'),44),right(concat(repeat(' ',8),cast(round(`subtotals`.`taxTotal`,2) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,2 AS `sequence`,NULL AS `dept_name`,3 AS `ordered`,'' AS `upc` from `rut_translog`.`subtotals` union all select concat(right(concat(repeat(' ',44),'TOTAL'),44),right(concat(repeat(' ',8),cast((`lttsummary`.`runningTotal` - `lttsummary`.`tenderTotal`) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,3 AS `sequence`,NULL AS `dept_name`,3 AS `ordered`,'' AS `upc` from `rut_translog`.`lttsummary` union all select `receipt_reorder_g`.`linetoprint` AS `linetoprint`,`receipt_reorder_g`.`sequence` AS `sequence`,`receipt_reorder_g`.`dept_name` AS `dept_name`,4 AS `ordered`,`receipt_reorder_g`.`upc` AS `upc` from `rut_translog`.`receipt_reorder_g` where (((`receipt_reorder_g`.`trans_type` = 'T') and (`receipt_reorder_g`.`department` = 0)) or ((`receipt_reorder_g`.`department` = 0) and (`receipt_reorder_g`.`trans_type` not in ('CM','I')) and (not((`receipt_reorder_g`.`linetoprint` like '** %'))) and (not((`receipt_reorder_g`.`linetoprint` like 'Subtotal%'))))) union all select concat(right(concat(repeat(' ',44),'CURRENT AMOUNT DUE'),44),right(concat(repeat(' ',8),cast((`subtotals`.`runningTotal` - `subtotals`.`transDiscount`) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,5 AS `sequence`,NULL AS `dept_name`,5 AS `ordered`,'' AS `upc` from `rut_translog`.`subtotals` where (`subtotals`.`runningTotal` <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_ltt_grouped`
--

/*!50001 DROP TABLE IF EXISTS `rp_ltt_grouped`*/;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_grouped`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_ltt_grouped` AS select `localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,`localtranstoday`.`card_no` AS `card_no`,`localtranstoday`.`upc` AS `upc`,`localtranstoday`.`description` AS `description`,`localtranstoday`.`trans_type` AS `trans_type`,`localtranstoday`.`trans_subtype` AS `trans_subtype`,sum(`localtranstoday`.`ItemQtty`) AS `itemqtty`,`localtranstoday`.`discounttype` AS `discounttype`,`localtranstoday`.`volume` AS `volume`,`localtranstoday`.`trans_status` AS `trans_status`,(case when (`localtranstoday`.`voided` = 1) then 0 else `localtranstoday`.`voided` end) AS `voided`,`localtranstoday`.`department` AS `department`,sum(`localtranstoday`.`quantity`) AS `quantity`,`localtranstoday`.`matched` AS `matched`,min(`localtranstoday`.`trans_id`) AS `trans_id`,`localtranstoday`.`scale` AS `scale`,sum(`localtranstoday`.`unitPrice`) AS `unitprice`,cast(sum(`localtranstoday`.`total`) as decimal(10,2)) AS `total`,sum(`localtranstoday`.`regPrice`) AS `regPrice`,`localtranstoday`.`tax` AS `tax`,`localtranstoday`.`foodstamp` AS `foodstamp`,(case when ((`localtranstoday`.`trans_status` = 'd') or (`localtranstoday`.`scale` = 1) or (`localtranstoday`.`trans_type` = 'T')) then `localtranstoday`.`trans_id` else `localtranstoday`.`scale` end) AS `grouper` from `localtranstoday` where ((not((`localtranstoday`.`description` like '** YOU SAVED %'))) and (`localtranstoday`.`trans_status` = 'M') and (`localtranstoday`.`datetime` >= curdate())) group by `localtranstoday`.`register_no`,`localtranstoday`.`emp_no`,`localtranstoday`.`trans_no`,`localtranstoday`.`card_no`,`localtranstoday`.`upc`,`localtranstoday`.`description`,`localtranstoday`.`trans_type`,`localtranstoday`.`trans_subtype`,`localtranstoday`.`discounttype`,`localtranstoday`.`volume`,`localtranstoday`.`trans_status`,`localtranstoday`.`department`,`localtranstoday`.`scale`,(case when (`localtranstoday`.`voided` = 1) then 0 else `localtranstoday`.`voided` end),`localtranstoday`.`matched`,`localtranstoday`.`tax`,`localtranstoday`.`foodstamp`,(case when ((`localtranstoday`.`trans_status` = 'd') or (`localtranstoday`.`scale` = 1) or (`localtranstoday`.`trans_type` = 'T')) then `localtranstoday`.`trans_id` else `localtranstoday`.`scale` end) union all select `localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,`localtranstoday`.`card_no` AS `card_no`,`localtranstoday`.`upc` AS `upc`,(case when (`localtranstoday`.`numflag` = 1) then concat(`localtranstoday`.`description`,'*') else `localtranstoday`.`description` end) AS `description`,`localtranstoday`.`trans_type` AS `trans_type`,`localtranstoday`.`trans_subtype` AS `trans_subtype`,sum(`localtranstoday`.`ItemQtty`) AS `itemqtty`,`localtranstoday`.`discounttype` AS `discounttype`,`localtranstoday`.`volume` AS `volume`,`localtranstoday`.`trans_status` AS `trans_status`,(case when (`localtranstoday`.`voided` = 1) then 0 else `localtranstoday`.`voided` end) AS `voided`,`localtranstoday`.`department` AS `department`,sum(`localtranstoday`.`quantity`) AS `quantity`,`localtranstoday`.`matched` AS `matched`,min(`localtranstoday`.`trans_id`) AS `trans_id`,`localtranstoday`.`scale` AS `scale`,`localtranstoday`.`unitPrice` AS `unitprice`,cast(sum(`localtranstoday`.`total`) as decimal(10,2)) AS `total`,`localtranstoday`.`regPrice` AS `regPrice`,`localtranstoday`.`tax` AS `tax`,`localtranstoday`.`foodstamp` AS `foodstamp`,(case when ((`localtranstoday`.`trans_status` = 'd') or (`localtranstoday`.`scale` = 1) or (`localtranstoday`.`trans_type` = 'T')) then `localtranstoday`.`trans_id` else `localtranstoday`.`scale` end) AS `grouper` from `localtranstoday` where ((not((`localtranstoday`.`description` like '** YOU SAVED %'))) and (`localtranstoday`.`trans_status` <> 'M') and (`localtranstoday`.`datetime` >= curdate()) and (`localtranstoday`.`trans_type` <> 'L')) group by `localtranstoday`.`register_no`,`localtranstoday`.`emp_no`,`localtranstoday`.`trans_no`,`localtranstoday`.`card_no`,`localtranstoday`.`upc`,`localtranstoday`.`description`,`localtranstoday`.`trans_type`,`localtranstoday`.`trans_subtype`,`localtranstoday`.`discounttype`,`localtranstoday`.`volume`,`localtranstoday`.`trans_status`,`localtranstoday`.`department`,`localtranstoday`.`scale`,(case when (`localtranstoday`.`voided` = 1) then 0 else `localtranstoday`.`voided` end),`localtranstoday`.`unitPrice`,`localtranstoday`.`regPrice`,`localtranstoday`.`matched`,`localtranstoday`.`tax`,`localtranstoday`.`foodstamp`,(case when ((`localtranstoday`.`trans_status` = 'd') or (`localtranstoday`.`scale` = 1) or (`localtranstoday`.`trans_type` = 'T')) then `localtranstoday`.`trans_id` else `localtranstoday`.`scale` end) union all select `localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,`localtranstoday`.`card_no` AS `card_no`,`localtranstoday`.`upc` AS `upc`,(case when (`localtranstoday`.`discounttype` = 1) then concat(' > you saved $',cast(cast(sum(((`localtranstoday`.`quantity` * `localtranstoday`.`regPrice`) - (`localtranstoday`.`quantity` * `localtranstoday`.`unitPrice`))) as decimal(10,2)) as char(20) charset latin1),'  <') when (`localtranstoday`.`discounttype` = 2) then concat(' > you saved $',cast(cast(sum(((`localtranstoday`.`quantity` * `localtranstoday`.`regPrice`) - (`localtranstoday`.`quantity` * `localtranstoday`.`unitPrice`))) as decimal(10,2)) as char(20) charset latin1),'  Member Special <') end) AS `description`,`localtranstoday`.`trans_type` AS `trans_type`,'0' AS `trans_subtype`,0 AS `itemQtty`,`localtranstoday`.`discounttype` AS `discounttype`,`localtranstoday`.`volume` AS `volume`,'D' AS `trans_status`,2 AS `voided`,`localtranstoday`.`department` AS `department`,0 AS `quantity`,`localtranstoday`.`matched` AS `matched`,(min(`localtranstoday`.`trans_id`) + 1) AS `trans_id`,`localtranstoday`.`scale` AS `scale`,0 AS `unitprice`,0 AS `total`,0 AS `regPrice`,0 AS `tax`,0 AS `foodstamp`,(case when ((`localtranstoday`.`trans_status` = 'd') or (`localtranstoday`.`scale` = 1)) then `localtranstoday`.`trans_id` else `localtranstoday`.`scale` end) AS `grouper` from `localtranstoday` where ((not((`localtranstoday`.`description` like '** YOU SAVED %'))) and ((`localtranstoday`.`discounttype` = 1) or (`localtranstoday`.`discounttype` = 2)) and (`localtranstoday`.`datetime` >= curdate()) and (`localtranstoday`.`trans_type` <> 'L')) group by `localtranstoday`.`register_no`,`localtranstoday`.`emp_no`,`localtranstoday`.`trans_no`,`localtranstoday`.`card_no`,`localtranstoday`.`upc`,`localtranstoday`.`description`,`localtranstoday`.`trans_type`,`localtranstoday`.`trans_subtype`,`localtranstoday`.`discounttype`,`localtranstoday`.`volume`,`localtranstoday`.`department`,`localtranstoday`.`scale`,`localtranstoday`.`matched`,(case when ((`localtranstoday`.`trans_status` = 'd') or (`localtranstoday`.`scale` = 1)) then `localtranstoday`.`trans_id` else `localtranstoday`.`scale` end) having (cast(sum(((`localtranstoday`.`quantity` * `localtranstoday`.`regPrice`) - (`localtranstoday`.`quantity` * `localtranstoday`.`unitPrice`))) as decimal(10,2)) <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_ltt_receipt`
--

/*!50001 DROP TABLE IF EXISTS `rp_ltt_receipt`*/;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_receipt`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_ltt_receipt` AS select `l`.`register_no` AS `register_no`,`l`.`emp_no` AS `emp_no`,`l`.`trans_no` AS `trans_no`,`l`.`description` AS `description`,(case when (`l`.`voided` = 5) then 'Discount' when (`l`.`trans_status` = 'M') then 'Mbr special' when (`l`.`trans_status` = 'S') then 'Staff special' when (`l`.`unitPrice` = 0.01) then '' when ((`l`.`scale` <> 0) and (`l`.`quantity` <> 0)) then concat(`l`.`quantity`,' @ ',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (abs(`l`.`ItemQtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` = 1)) then concat(`l`.`volume`,' / ',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (abs(`l`.`ItemQtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` <> 1)) then concat(`l`.`quantity`,' @ ',`l`.`volume`,' /',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (`l`.`discounttype` = 3)) then concat(`l`.`ItemQtty`,' / ',`l`.`unitPrice`) when (abs(`l`.`ItemQtty`) > 1) then concat(`l`.`quantity`,' @ ',`l`.`unitPrice`) when (`l`.`matched` > 0) then '1 w/ vol adj' else '' end) AS `comment`,`l`.`total` AS `total`,(case when (`l`.`trans_status` = 'V') then 'VD' when (`l`.`trans_status` = 'R') then 'RF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` <> 0)) then 'TF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` = 0)) then 'T' when ((`l`.`tax` > 1) and (`l`.`foodstamp` <> 0)) then concat(substr(`t`.`description`,1,1),'F') when ((`l`.`tax` > 1) and (`l`.`foodstamp` = 0)) then substr(`t`.`description`,1,1) when ((`l`.`tax` = 0) and (`l`.`foodstamp` <> 0)) then 'F' when ((`l`.`tax` = 0) and (`l`.`foodstamp` = 0)) then '' end) AS `Status`,`l`.`trans_type` AS `trans_type`,`l`.`unitPrice` AS `unitPrice`,`l`.`voided` AS `voided`,`l`.`trans_id` AS `trans_id` from (`localtranstoday` `l` left join `taxrates` `t` on((`l`.`tax` = `t`.`id`))) where ((`l`.`voided` <> 5) and (`l`.`upc` <> 'TAX') and (`l`.`upc` <> 'DISCOUNT') and (`l`.`trans_type` <> 'L') and (`l`.`datetime` >= curdate())) order by `l`.`emp_no`,`l`.`trans_no`,`l`.`trans_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_ltt_receipt_reorder_g`
--

/*!50001 DROP TABLE IF EXISTS `rp_ltt_receipt_reorder_g`*/;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_receipt_reorder_g`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_ltt_receipt_reorder_g` AS select `l`.`register_no` AS `register_no`,`l`.`emp_no` AS `emp_no`,`l`.`trans_no` AS `trans_no`,`l`.`card_no` AS `card_no`,`l`.`description` AS `description`,(case when (`l`.`voided` = 5) then 'Discount' when (`l`.`trans_status` = 'M') then 'Mbr special' when (`l`.`trans_status` = 'S') then 'Staff special' when (`l`.`unitprice` = 0.01) then '' when ((`l`.`scale` <> 0) and (`l`.`quantity` <> 0)) then concat(cast(`l`.`quantity` as char charset latin1),' @ ',cast(`l`.`unitprice` as char charset latin1)) when ((abs(`l`.`itemqtty`) > 1) and (abs(`l`.`itemqtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` = 1)) then concat(cast(`l`.`volume` as char charset latin1),' / ',cast(`l`.`unitprice` as char charset latin1)) when ((abs(`l`.`itemqtty`) > 1) and (abs(`l`.`itemqtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` <> 1)) then concat(cast(`l`.`quantity` as char charset latin1),' @ ',cast(`l`.`volume` as char charset latin1),' /',cast(`l`.`unitprice` as char charset latin1)) when ((abs(`l`.`itemqtty`) > 1) and (`l`.`discounttype` = 3)) then concat(cast(`l`.`itemqtty` as char charset latin1),' / ',cast(`l`.`unitprice` as char charset latin1)) when (abs(`l`.`itemqtty`) > 1) then concat(cast(`l`.`quantity` as char charset latin1),' @ ',cast(`l`.`unitprice` as char charset latin1)) when (`l`.`matched` > 0) then '1 w/ vol adj' else '' end) AS `comment`,`l`.`total` AS `total`,(case when (`l`.`trans_status` = 'V') then 'VD' when (`l`.`trans_status` = 'R') then 'RF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` <> 0)) then 'TF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` = 0)) then 'T' when ((`l`.`tax` > 1) and (`l`.`foodstamp` <> 0)) then concat(substr(`t`.`description`,1,1),'F') when ((`l`.`tax` > 1) and (`l`.`foodstamp` = 0)) then substr(`t`.`description`,1,1) when ((`l`.`tax` = 0) and (`l`.`foodstamp` <> 0)) then 'F' when ((`l`.`tax` = 0) and (`l`.`foodstamp` = 0)) then '' end) AS `status`,`l`.`trans_type` AS `trans_type`,`l`.`unitprice` AS `unitPrice`,`l`.`voided` AS `voided`,(`l`.`trans_id` + 1000) AS `sequence`,`l`.`department` AS `department`,`l`.`upc` AS `upc`,`l`.`trans_subtype` AS `trans_subtype` from (`rp_ltt_grouped` `l` left join `taxrates` `t` on((`l`.`tax` = `t`.`id`))) where ((`l`.`voided` <> 5) and (`l`.`upc` <> 'TAX') and (`l`.`upc` <> 'DISCOUNT') and (`l`.`trans_type` <> 'L') and ((`l`.`trans_status` <> 'M') or (`l`.`total` <> cast('0.00' as decimal(10,0))))) union select 0 AS `register_no`,0 AS `emp_no`,0 AS `trans_no`,0 AS `card_no`,'  ' AS `description`,' ' AS `comment`,0 AS `total`,' ' AS `Status`,' ' AS `trans_type`,0 AS `unitPrice`,0 AS `voided`,999 AS `sequence`,'' AS `department`,'' AS `upc`,'' AS `trans_subtype` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_lttsubtotals`
--

/*!50001 DROP TABLE IF EXISTS `rp_lttsubtotals`*/;
/*!50001 DROP VIEW IF EXISTS `rp_lttsubtotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_lttsubtotals` AS select `rp_lttsummary`.`emp_no` AS `emp_no`,`rp_lttsummary`.`register_no` AS `register_no`,`rp_lttsummary`.`trans_no` AS `trans_no`,`rp_lttsummary`.`tdate` AS `tdate`,0 AS `taxTotal`,`rp_lttsummary`.`fsTendered` AS `fsTendered`,cast(((`rp_lttsummary`.`fsTendered` + `rp_lttsummary`.`fsNoDiscTTL`) + (`rp_lttsummary`.`fsDiscTTL` * ((100 - `rp_lttsummary`.`percentDiscount`) / 100))) as decimal(10,2)) AS `fsEligible`,0 AS `fsTax`,cast(((`rp_lttsummary`.`discountableTTL` * `rp_lttsummary`.`percentDiscount`) / 100) as decimal(10,2)) AS `transDiscount` from `rp_lttsummary` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_lttsummary`
--

/*!50001 DROP TABLE IF EXISTS `rp_lttsummary`*/;
/*!50001 DROP VIEW IF EXISTS `rp_lttsummary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_lttsummary` AS select `localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`trans_no` AS `trans_no`,(case when isnull(min(`localtranstoday`.`datetime`)) then now() else min(`localtranstoday`.`datetime`) end) AS `tdate`,max(`localtranstoday`.`card_no`) AS `card_no`,cast(sum(`localtranstoday`.`total`) as decimal(10,2)) AS `runningTotal`,cast(sum((case when (`localtranstoday`.`discounttype` = 1) then `localtranstoday`.`discount` else 0 end)) as decimal(10,2)) AS `discountTTL`,cast(sum((case when ((`localtranstoday`.`discountable` <> 0) and (`localtranstoday`.`tax` <> 0)) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `discTaxable`,cast(sum((case when (`localtranstoday`.`discounttype` in (2,3)) then `localtranstoday`.`memDiscount` else 0 end)) as decimal(10,2)) AS `memSpecial`,cast(sum((case when (`localtranstoday`.`discounttype` = 4) then `localtranstoday`.`memDiscount` else 0 end)) as decimal(10,2)) AS `staffSpecial`,cast(sum((case when (`localtranstoday`.`discountable` = 0) then 0 else `localtranstoday`.`total` end)) as decimal(10,2)) AS `discountableTTL`,cast(sum((case when ((`localtranstoday`.`trans_subtype` = 'MI') or (`localtranstoday`.`trans_subtype` = 'CX')) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `chargeTotal`,cast(sum((case when (`localtranstoday`.`department` = 990) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `paymentTotal`,cast(sum((case when ((`localtranstoday`.`trans_type` = 'T') and (`localtranstoday`.`department` = 0)) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `tenderTotal`,cast(sum((case when ((`localtranstoday`.`trans_subtype` = 'FS') or (`localtranstoday`.`trans_subtype` = 'EF')) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `fsTendered`,cast(sum((case when ((`localtranstoday`.`foodstamp` = 1) and (`localtranstoday`.`discountable` = 0)) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `fsNoDiscTTL`,cast(sum((case when ((`localtranstoday`.`foodstamp` = 1) and (`localtranstoday`.`discountable` <> 0)) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `fsDiscTTL`,(case when (isnull(max(`localtranstoday`.`percentDiscount`)) or (max(`localtranstoday`.`percentDiscount`) < 0)) then 0.00 else max(cast(`localtranstoday`.`percentDiscount` as decimal(10,0))) end) AS `percentDiscount`,cast(sum((case when (`localtranstoday`.`numflag` = 1) then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `localTotal`,cast(sum((case when (`localtranstoday`.`trans_status` = 'V') then -(`localtranstoday`.`total`) else 0 end)) as decimal(10,2)) AS `voidTotal`,max(`localtranstoday`.`trans_id`) AS `LastID` from `localtranstoday` where ((`localtranstoday`.`trans_type` <> 'L') and (`localtranstoday`.`datetime` >= curdate())) group by `localtranstoday`.`emp_no`,`localtranstoday`.`register_no`,`localtranstoday`.`trans_no` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_receipt`
--

/*!50001 DROP TABLE IF EXISTS `rp_receipt`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt` AS select `rp_ltt_receipt`.`register_no` AS `register_no`,`rp_ltt_receipt`.`emp_no` AS `emp_no`,`rp_ltt_receipt`.`trans_no` AS `trans_no`,(case when (`rp_ltt_receipt`.`trans_type` = 'T') then concat(substr(concat(ucase(trim(`rp_ltt_receipt`.`description`)),repeat(' ',44)),1,44),right(concat(repeat(' ',8),format((-(1) * `rp_ltt_receipt`.`total`),2)),8),right(concat(repeat(' ',4),`rp_ltt_receipt`.`Status`),4)) when (`rp_ltt_receipt`.`voided` = 3) then concat(substr(concat(`rp_ltt_receipt`.`description`,repeat(' ',30)),1,30),repeat(' ',9),'TOTAL',right(concat(repeat(' ',8),format(`rp_ltt_receipt`.`unitPrice`,2)),8)) when (`rp_ltt_receipt`.`voided` = 2) then `rp_ltt_receipt`.`description` when (`rp_ltt_receipt`.`voided` = 4) then `rp_ltt_receipt`.`description` when (`rp_ltt_receipt`.`voided` = 6) then `rp_ltt_receipt`.`description` when ((`rp_ltt_receipt`.`voided` = 7) or (`rp_ltt_receipt`.`voided` = 17)) then concat(substr(concat(`rp_ltt_receipt`.`description`,repeat(' ',30)),1,30),repeat(' ',14),right(concat(repeat(' ',8),format(`rp_ltt_receipt`.`unitPrice`,2)),8),right(concat(repeat(' ',4),`rp_ltt_receipt`.`Status`),4)) else concat(substr(concat(`rp_ltt_receipt`.`description`,repeat(' ',30)),1,30),' ',substr(concat(`rp_ltt_receipt`.`comment`,repeat(' ',13)),1,13),right(concat(repeat(' ',8),format(`rp_ltt_receipt`.`total`,2)),8),right(concat(repeat(' ',4),`rp_ltt_receipt`.`Status`),4)) end) AS `linetoprint`,`rp_ltt_receipt`.`trans_id` AS `trans_id` from `rp_ltt_receipt` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_receipt_header`
--

/*!50001 DROP TABLE IF EXISTS `rp_receipt_header`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_header`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt_header` AS select min(`localtranstoday`.`datetime`) AS `dateTimeStamp`,`localtranstoday`.`card_no` AS `memberID`,`localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,cast(sum((case when (`localtranstoday`.`discounttype` = 1) then `localtranstoday`.`discount` else 0 end)) as decimal(10,2)) AS `discountTTL`,cast(sum((case when (`localtranstoday`.`discounttype` = 2) then `localtranstoday`.`memDiscount` else 0 end)) as decimal(10,2)) AS `memSpecial`,(case when isnull(min(`localtranstoday`.`datetime`)) then 0 else sum((case when (`localtranstoday`.`discounttype` = 4) then `localtranstoday`.`memDiscount` else 0 end)) end) AS `staffSpecial`,cast(sum((case when (`localtranstoday`.`upc` = '0000000008005') then `localtranstoday`.`total` else 0 end)) as decimal(10,2)) AS `couponTotal`,cast(sum((case when (`localtranstoday`.`upc` = 'MEMCOUPON') then `localtranstoday`.`unitPrice` else 0 end)) as decimal(10,2)) AS `memCoupon`,abs(sum((case when ((`localtranstoday`.`trans_subtype` = 'MI') or (`localtranstoday`.`trans_subtype` = 'CX')) then `localtranstoday`.`total` else 0 end))) AS `chargeTotal`,sum((case when (`localtranstoday`.`upc` = 'Discount') then `localtranstoday`.`total` else 0 end)) AS `transDiscount`,sum((case when (`localtranstoday`.`trans_type` = 'T') then (-(1) * `localtranstoday`.`total`) else 0 end)) AS `tenderTotal` from `localtranstoday` where (`localtranstoday`.`trans_type` <> 'L') group by `localtranstoday`.`register_no`,`localtranstoday`.`emp_no`,`localtranstoday`.`trans_no`,`localtranstoday`.`card_no` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_receipt_reorder_g`
--

/*!50001 DROP TABLE IF EXISTS `rp_receipt_reorder_g`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_reorder_g`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt_reorder_g` AS select `r`.`register_no` AS `register_no`,`r`.`emp_no` AS `emp_no`,`r`.`trans_no` AS `trans_no`,`r`.`card_no` AS `card_no`,(case when (`r`.`trans_type` = 'T') then (case when ((`r`.`trans_subtype` = 'CP') and (`r`.`upc` <> '0')) then concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),' ',substr(concat(`r`.`comment`,repeat(' ',12)),1,12),right(concat(repeat(' ',8),cast(`r`.`total` as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) else concat(right(concat(repeat(' ',44),ucase(`r`.`description`)),44),right(concat(repeat(' ',8),cast((-(1) * `r`.`total`) as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) end) when (`r`.`voided` = 3) then concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),repeat(' ',9),'TOTAL',right(concat(repeat(' ',8),cast(`r`.`unitPrice` as char charset latin1)),8)) when (`r`.`voided` = 2) then `r`.`description` when (`r`.`voided` = 4) then `r`.`description` when (`r`.`voided` = 6) then `r`.`description` when ((`r`.`voided` = 7) or (`r`.`voided` = 17)) then concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),repeat(' ',14),right(concat(repeat(' ',8),cast(`r`.`unitPrice` as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) when (`r`.`sequence` < 1000) then `r`.`description` else concat(substr(concat(`r`.`description`,repeat(' ',30)),1,30),' ',substr(concat(`r`.`comment`,repeat(' ',12)),1,12),right(concat(repeat(' ',8),cast(`r`.`total` as char charset latin1)),8),right(concat(repeat(' ',4),`r`.`status`),4)) end) AS `linetoprint`,`r`.`sequence` AS `sequence`,`r`.`department` AS `department`,`d`.`subdept_name` AS `dept_name`,(case when ((`r`.`trans_subtype` = 'CM') or (`r`.`voided` in (10,17))) then 'CM' else `r`.`trans_type` end) AS `trans_type`,`r`.`upc` AS `upc` from (`rut_translog`.`rp_ltt_receipt_reorder_g` `r` left join `rut_opdata`.`subdepts` `d` on((`r`.`department` = `d`.`dept_ID`))) where ((`r`.`total` <> 0) or (`r`.`unitPrice` = 0)) order by `r`.`register_no`,`r`.`emp_no`,`r`.`trans_no`,`r`.`card_no`,`r`.`sequence` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_receipt_reorder_unions_g`
--

/*!50001 DROP TABLE IF EXISTS `rp_receipt_reorder_unions_g`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_reorder_unions_g`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt_reorder_unions_g` AS select `rp_receipt_reorder_g`.`linetoprint` AS `linetoprint`,`rp_receipt_reorder_g`.`emp_no` AS `emp_no`,`rp_receipt_reorder_g`.`register_no` AS `register_no`,`rp_receipt_reorder_g`.`trans_no` AS `trans_no`,`rp_receipt_reorder_g`.`sequence` AS `sequence`,`rp_receipt_reorder_g`.`dept_name` AS `dept_name`,1 AS `ordered`,`rp_receipt_reorder_g`.`upc` AS `upc` from `rut_translog`.`rp_receipt_reorder_g` where (((`rp_receipt_reorder_g`.`department` <> 0) or (`rp_receipt_reorder_g`.`trans_type` = 'CM')) and (not((`rp_receipt_reorder_g`.`linetoprint` like 'member discount%')))) union all select replace(replace(`r1`.`linetoprint`,'** T',' = T'),' **',' = ') AS `linetoprint`,`r1`.`emp_no` AS `emp_no`,`r1`.`register_no` AS `register_no`,`r1`.`trans_no` AS `trans_no`,`r1`.`sequence` AS `sequence`,`r2`.`dept_name` AS `dept_name`,1 AS `ordered`,`r2`.`upc` AS `upc` from (`rut_translog`.`rp_receipt_reorder_g` `r1` join `rut_translog`.`rp_receipt_reorder_g` `r2` on((((`r1`.`sequence` + 1) = `r2`.`sequence`) and (`r1`.`register_no` = `r2`.`register_no`) and (`r1`.`emp_no` = `r2`.`emp_no`) and (`r1`.`trans_no` = `r2`.`trans_no`)))) where ((`r1`.`linetoprint` like '** T%') and (`r2`.`dept_name` is not null) and (`r1`.`linetoprint` <> '** Tare Weight 0 **')) union all select concat(substr(concat('** ',trim(cast(`rp_subtotals`.`percentDiscount` as char charset latin1)),'% Discount Applied **',repeat(' ',30)),1,30),' ',repeat(' ',13),right(concat(repeat(' ',8),cast((-(1) * `rp_subtotals`.`transDiscount`) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,`rp_subtotals`.`emp_no` AS `emp_no`,`rp_subtotals`.`register_no` AS `register_no`,`rp_subtotals`.`trans_no` AS `trans_no`,0 AS `sequence`,NULL AS `dept_name`,2 AS `ordered`,'' AS `upc` from `rut_translog`.`rp_subtotals` where (`rp_subtotals`.`percentDiscount` <> 0) union all select `rp_receipt_reorder_g`.`linetoprint` AS `linetoprint`,`rp_receipt_reorder_g`.`emp_no` AS `emp_no`,`rp_receipt_reorder_g`.`register_no` AS `register_no`,`rp_receipt_reorder_g`.`trans_no` AS `trans_no`,`rp_receipt_reorder_g`.`sequence` AS `sequence`,NULL AS `dept_name`,2 AS `ordered`,`rp_receipt_reorder_g`.`upc` AS `upc` from `rut_translog`.`rp_receipt_reorder_g` where (`rp_receipt_reorder_g`.`linetoprint` like 'member discount%') union all select concat(right(concat(repeat(' ',44),'SUBTOTAL'),44),right(concat(repeat(' ',8),cast(round(((`l`.`runningTotal` - `s`.`taxTotal`) - `l`.`tenderTotal`),2) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,`l`.`emp_no` AS `emp_no`,`l`.`register_no` AS `register_no`,`l`.`trans_no` AS `trans_no`,1 AS `sequence`,NULL AS `dept_name`,3 AS `ordered`,'' AS `upc` from (`rut_translog`.`rp_lttsummary` `l` join `rut_translog`.`rp_subtotals` `s`) where ((`l`.`emp_no` = `s`.`emp_no`) and (`l`.`register_no` = `s`.`register_no`) and (`l`.`trans_no` = `s`.`trans_no`)) union all select concat(right(concat(repeat(' ',44),'TAX'),44),right(concat(repeat(' ',8),cast(round(`rp_subtotals`.`taxTotal`,2) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,`rp_subtotals`.`emp_no` AS `emp_no`,`rp_subtotals`.`register_no` AS `register_no`,`rp_subtotals`.`trans_no` AS `trans_no`,2 AS `sequence`,NULL AS `dept_name`,3 AS `ordered`,'' AS `upc` from `rut_translog`.`rp_subtotals` union all select concat(right(concat(repeat(' ',44),'TOTAL'),44),right(concat(repeat(' ',8),cast((`rp_lttsummary`.`runningTotal` - `rp_lttsummary`.`tenderTotal`) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,`rp_lttsummary`.`emp_no` AS `emp_no`,`rp_lttsummary`.`register_no` AS `register_no`,`rp_lttsummary`.`trans_no` AS `trans_no`,3 AS `sequence`,NULL AS `dept_name`,3 AS `ordered`,'' AS `upc` from `rut_translog`.`rp_lttsummary` union all select `rp_receipt_reorder_g`.`linetoprint` AS `linetoprint`,`rp_receipt_reorder_g`.`emp_no` AS `emp_no`,`rp_receipt_reorder_g`.`register_no` AS `register_no`,`rp_receipt_reorder_g`.`trans_no` AS `trans_no`,`rp_receipt_reorder_g`.`sequence` AS `sequence`,`rp_receipt_reorder_g`.`dept_name` AS `dept_name`,4 AS `ordered`,`rp_receipt_reorder_g`.`upc` AS `upc` from `rut_translog`.`rp_receipt_reorder_g` where (((`rp_receipt_reorder_g`.`trans_type` = 'T') and (`rp_receipt_reorder_g`.`department` = 0)) or ((`rp_receipt_reorder_g`.`department` = 0) and (`rp_receipt_reorder_g`.`linetoprint` like '%Coupon%'))) union all select concat(right(concat(repeat(' ',44),'CURRENT AMOUNT DUE'),44),right(concat(repeat(' ',8),cast((`rp_subtotals`.`runningTotal` - `rp_subtotals`.`transDiscount`) as char charset latin1)),8),repeat(' ',4)) AS `linetoprint`,`rp_subtotals`.`emp_no` AS `emp_no`,`rp_subtotals`.`register_no` AS `register_no`,`rp_subtotals`.`trans_no` AS `trans_no`,5 AS `sequence`,NULL AS `dept_name`,5 AS `ordered`,'' AS `upc` from `rut_translog`.`rp_subtotals` where (`rp_subtotals`.`runningTotal` <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_subtotals`
--

/*!50001 DROP TABLE IF EXISTS `rp_subtotals`*/;
/*!50001 DROP VIEW IF EXISTS `rp_subtotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_subtotals` AS select `l`.`emp_no` AS `emp_no`,`l`.`register_no` AS `register_no`,`l`.`trans_no` AS `trans_no`,(case when isnull(`l`.`LastID`) then 0 else `l`.`LastID` end) AS `LastID`,`l`.`card_no` AS `card_no`,`l`.`runningTotal` AS `runningTotal`,`l`.`discountableTTL` AS `discountableTotal`,`l`.`tenderTotal` AS `tenderTotal`,`l`.`chargeTotal` AS `chargeTotal`,`l`.`paymentTotal` AS `paymentTotal`,`l`.`discountTTL` AS `discountTTL`,`l`.`memSpecial` AS `memSpecial`,`l`.`staffSpecial` AS `staffSpecial`,`s`.`fsEligible` AS `fsEligible`,0 AS `fsTaxExempt`,0 AS `taxTotal`,`s`.`transDiscount` AS `transDiscount`,`l`.`percentDiscount` AS `percentDiscount`,`l`.`localTotal` AS `localTotal`,`l`.`voidTotal` AS `voidTotal` from (`rp_lttsummary` `l` join `rp_lttsubtotals` `s`) where ((`l`.`tdate` = `s`.`tdate`) and (`l`.`emp_no` = `s`.`emp_no`) and (`l`.`register_no` = `s`.`register_no`) and (`l`.`trans_no` = `s`.`trans_no`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `screendisplay`
--

/*!50001 DROP TABLE IF EXISTS `screendisplay`*/;
/*!50001 DROP VIEW IF EXISTS `screendisplay`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `screendisplay` AS select (case when ((`l`.`voided` = 5) or (`l`.`voided` = 11) or (`l`.`voided` = 17) or (`l`.`trans_type` = 'T')) then '' else `l`.`description` end) AS `description`,(case when ((`l`.`discounttype` = 3) and (`l`.`trans_status` = 'V')) then concat(`l`.`ItemQtty`,' /',`l`.`unitPrice`) when (`l`.`voided` = 5) then 'Discount' when (`l`.`trans_status` = 'M') then 'Mbr special' when (`l`.`trans_status` = 'S') then 'Staff special' when ((`l`.`scale` <> 0) and (`l`.`quantity` <> 0) and (`l`.`unitPrice` <> 0.01)) then concat(`l`.`quantity`,' @ ',`l`.`unitPrice`) when (substr(`l`.`upc`,1,3) = '002') then concat(`l`.`ItemQtty`,' @ ',`l`.`regPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (abs(`l`.`ItemQtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` = 1)) then concat(`l`.`volume`,' for ',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (abs(`l`.`ItemQtty`) > abs(`l`.`quantity`)) and (`l`.`discounttype` <> 3) and (`l`.`quantity` <> 1)) then concat(`l`.`quantity`,' @ ',`l`.`volume`,' for ',`l`.`unitPrice`) when ((abs(`l`.`ItemQtty`) > 1) and (`l`.`discounttype` = 3)) then concat(`l`.`ItemQtty`,' / ',`l`.`unitPrice`) when (abs(`l`.`ItemQtty`) > 1) then concat(`l`.`quantity`,' @ ',`l`.`unitPrice`) when (`l`.`voided` = 3) then 'Total ' when (`l`.`voided` = 5) then 'Discount ' when (`l`.`voided` = 7) then '' when ((`l`.`voided` = 11) or (`l`.`voided` = 17)) then `l`.`upc` when (`l`.`matched` > 0) then '1 w/ vol adj' when (`l`.`trans_type` = 'T') then `l`.`description` else '' end) AS `comment`,(case when ((`l`.`voided` = 3) or (`l`.`voided` = 5) or (`l`.`voided` = 7) or (`l`.`voided` = 11) or (`l`.`voided` = 17)) then `l`.`unitPrice` when (`l`.`trans_status` = 'D') then '' else `l`.`total` end) AS `total`,(case when (`l`.`trans_status` = 'V') then 'VD' when (`l`.`trans_status` = 'R') then 'RF' when (`l`.`trans_status` = 'C') then 'MC' when ((`l`.`tax` = 1) and (`l`.`foodstamp` <> 0)) then 'TF' when ((`l`.`tax` = 1) and (`l`.`foodstamp` = 0)) then 'T' when ((`l`.`tax` > 1) and (`l`.`foodstamp` <> 0)) then concat(substr(`t`.`description`,0,1),'F') when ((`l`.`tax` > 1) and (`l`.`foodstamp` = 0)) then substr(`t`.`description`,0,1) when ((`l`.`tax` = 0) and (`l`.`foodstamp` <> 0)) then 'F' when ((`l`.`tax` = 0) and (`l`.`foodstamp` = 0)) then '' else '' end) AS `status`,(case when ((`l`.`trans_status` = 'V') or (`l`.`trans_type` = 'T') or (`l`.`trans_status` = 'R') or (`l`.`trans_status` = 'C') or (`l`.`trans_status` = 'M') or (`l`.`voided` = 17) or (`l`.`trans_status` = 'J')) then '800000' when (((`l`.`discounttype` <> 0) and ((`l`.`matched` > 0) or (`l`.`volDiscType` = 0))) or (`l`.`voided` = 2) or (`l`.`voided` = 6) or (`l`.`voided` = 4) or (`l`.`voided` = 5) or (`l`.`voided` = 10) or (`l`.`voided` = 22)) then '408080' when ((`l`.`voided` = 3) or (`l`.`voided` = 11)) then '000000' when (`l`.`voided` = 7) then '800080' else '004080' end) AS `lineColor`,`l`.`discounttype` AS `discounttype`,`l`.`trans_type` AS `trans_type`,`l`.`trans_status` AS `trans_status`,`l`.`voided` AS `voided`,`l`.`trans_id` AS `trans_id` from (`localtemptrans` `l` left join `taxrates` `t` on((`l`.`tax` = `t`.`id`))) where (`l`.`trans_type` <> 'L') order by `l`.`trans_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `staffdiscountadd`
--

/*!50001 DROP TABLE IF EXISTS `staffdiscountadd`*/;
/*!50001 DROP VIEW IF EXISTS `staffdiscountadd`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `staffdiscountadd` AS select max(`localtemptrans`.`datetime`) AS `datetime`,`localtemptrans`.`register_no` AS `register_no`,`localtemptrans`.`emp_no` AS `emp_no`,`localtemptrans`.`trans_no` AS `trans_no`,`localtemptrans`.`upc` AS `upc`,`localtemptrans`.`description` AS `description`,'I' AS `trans_type`,'' AS `trans_subtype`,'S' AS `trans_status`,max(`localtemptrans`.`department`) AS `department`,1 AS `quantity`,0 AS `scale`,0 AS `cost`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `unitPrice`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `total`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `regPrice`,max(`localtemptrans`.`tax`) AS `tax`,max(`localtemptrans`.`foodstamp`) AS `foodstamp`,0 AS `discount`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `memDiscount`,3 AS `discountable`,40 AS `discounttype`,8 AS `voided`,max(`localtemptrans`.`percentDiscount`) AS `percentDiscount`,0 AS `ItemQtty`,0 AS `volDiscType`,0 AS `volume`,0 AS `VolSpecial`,0 AS `mixMatch`,0 AS `matched`,max(`localtemptrans`.`memType`) AS `memType`,max(`localtemptrans`.`staff`) AS `staff`,0 AS `numflag`,'' AS `charflag`,`localtemptrans`.`card_no` AS `card_no` from `localtemptrans` where (((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` = `localtemptrans`.`regPrice`)) or (`localtemptrans`.`trans_status` = 'S')) group by `localtemptrans`.`register_no`,`localtemptrans`.`emp_no`,`localtemptrans`.`trans_no`,`localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`card_no` having (sum(`localtemptrans`.`memDiscount`) <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `staffdiscountremove`
--

/*!50001 DROP TABLE IF EXISTS `staffdiscountremove`*/;
/*!50001 DROP VIEW IF EXISTS `staffdiscountremove`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `staffdiscountremove` AS select max(`localtemptrans`.`datetime`) AS `datetime`,`localtemptrans`.`register_no` AS `register_no`,`localtemptrans`.`emp_no` AS `emp_no`,`localtemptrans`.`trans_no` AS `trans_no`,`localtemptrans`.`upc` AS `upc`,`localtemptrans`.`description` AS `description`,'I' AS `trans_type`,'' AS `trans_subtype`,'S' AS `trans_status`,max(`localtemptrans`.`department`) AS `department`,1 AS `quantity`,0 AS `scale`,0 AS `cost`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `unitPrice`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `total`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `regPrice`,max(`localtemptrans`.`tax`) AS `tax`,max(`localtemptrans`.`foodstamp`) AS `foodstamp`,0 AS `discount`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `memDiscount`,3 AS `discountable`,40 AS `discounttype`,8 AS `voided`,max(`localtemptrans`.`percentDiscount`) AS `percentDiscount`,0 AS `ItemQtty`,0 AS `volDiscType`,0 AS `volume`,0 AS `VolSpecial`,0 AS `mixMatch`,0 AS `matched`,max(`localtemptrans`.`memType`) AS `memType`,max(`localtemptrans`.`staff`) AS `staff`,0 AS `numflag`,'' AS `charflag`,`localtemptrans`.`card_no` AS `card_no` from `localtemptrans` where (((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) or (`localtemptrans`.`trans_status` = 'S')) group by `localtemptrans`.`register_no`,`localtemptrans`.`emp_no`,`localtemptrans`.`trans_no`,`localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`card_no` having (sum((case when ((`localtemptrans`.`discounttype` = 4) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end)) <> 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `subtotals`
--

/*!50001 DROP TABLE IF EXISTS `subtotals`*/;
/*!50001 DROP VIEW IF EXISTS `subtotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `subtotals` AS select (case when isnull(`l`.`LastID`) then 0 else `l`.`LastID` end) AS `LastID`,`l`.`card_no` AS `card_no`,`l`.`runningTotal` AS `runningTotal`,`l`.`discountableTTL` AS `discountableTotal`,`l`.`tenderTotal` AS `tenderTotal`,`l`.`chargeTotal` AS `chargeTotal`,`l`.`paymentTotal` AS `paymentTotal`,`l`.`discountTTL` AS `discountTTL`,`l`.`memSpecial` AS `memSpecial`,`l`.`staffSpecial` AS `staffSpecial`,`s`.`fsEligible` AS `fsEligible`,0 AS `fsTaxExempt`,0 AS `taxTotal`,`s`.`transDiscount` AS `transDiscount`,`l`.`percentDiscount` AS `percentDiscount`,`l`.`localTotal` AS `localTotal`,`l`.`voidTotal` AS `voidTotal` from (`lttsummary` `l` join `lttsubtotals` `s`) where (`l`.`tdate` = `s`.`tdate`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `suspendedtoday`
--

/*!50001 DROP TABLE IF EXISTS `suspendedtoday`*/;
/*!50001 DROP VIEW IF EXISTS `suspendedtoday`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `suspendedtoday` AS select `suspended`.`datetime` AS `datetime`,`suspended`.`register_no` AS `register_no`,`suspended`.`emp_no` AS `emp_no`,`suspended`.`trans_no` AS `trans_no`,`suspended`.`upc` AS `upc`,`suspended`.`description` AS `description`,`suspended`.`trans_type` AS `trans_type`,`suspended`.`trans_subtype` AS `trans_subtype`,`suspended`.`trans_status` AS `trans_status`,`suspended`.`department` AS `department`,`suspended`.`quantity` AS `quantity`,`suspended`.`scale` AS `scale`,`suspended`.`cost` AS `cost`,`suspended`.`unitPrice` AS `unitPrice`,`suspended`.`total` AS `total`,`suspended`.`regPrice` AS `regPrice`,`suspended`.`tax` AS `tax`,`suspended`.`foodstamp` AS `foodstamp`,`suspended`.`discount` AS `discount`,`suspended`.`memDiscount` AS `memDiscount`,`suspended`.`discountable` AS `discountable`,`suspended`.`discounttype` AS `discounttype`,`suspended`.`voided` AS `voided`,`suspended`.`percentDiscount` AS `percentDiscount`,`suspended`.`ItemQtty` AS `ItemQtty`,`suspended`.`volDiscType` AS `volDiscType`,`suspended`.`volume` AS `volume`,`suspended`.`VolSpecial` AS `VolSpecial`,`suspended`.`mixMatch` AS `mixMatch`,`suspended`.`matched` AS `matched`,`suspended`.`memType` AS `memType`,`suspended`.`staff` AS `staff`,`suspended`.`numflag` AS `numflag`,`suspended`.`charflag` AS `charflag`,`suspended`.`card_no` AS `card_no`,`suspended`.`trans_id` AS `trans_id` from `suspended` where ((to_days(now()) - to_days(`suspended`.`datetime`)) = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `taxView`
--

/*!50001 DROP TABLE IF EXISTS `taxView`*/;
/*!50001 DROP VIEW IF EXISTS `taxView`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`corelane`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `taxView` AS select `r`.`id` AS `id`,`r`.`description` AS `description`,cast((sum((case when ((`l`.`trans_type` in ('I','D')) and (`l`.`discountable` = 0)) then `l`.`total` when ((`l`.`trans_type` in ('I','D')) and (`l`.`discountable` <> 0)) then (`l`.`total` * ((100 - `s`.`percentDiscount`) / 100)) else 0 end)) * `r`.`rate`) as decimal(10,2)) AS `taxTotal`,cast(sum((case when ((`l`.`trans_type` in ('I','D')) and (`l`.`discountable` = 0) and (`l`.`foodstamp` = 1)) then `l`.`total` when ((`l`.`trans_type` in ('I','D')) and (`l`.`discountable` <> 0) and (`l`.`foodstamp` = 1)) then (`l`.`total` * ((100 - `s`.`percentDiscount`) / 100)) else 0 end)) as decimal(10,2)) AS `fsTaxable`,cast((sum((case when ((`l`.`trans_type` in ('I','D')) and (`l`.`discountable` = 0) and (`l`.`foodstamp` = 1)) then `l`.`total` when ((`l`.`trans_type` in ('I','D')) and (`l`.`discountable` <> 0) and (`l`.`foodstamp` = 1)) then (`l`.`total` * ((100 - `s`.`percentDiscount`) / 100)) else 0 end)) * `r`.`rate`) as decimal(10,2)) AS `fsTaxTotal`,(-(1) * max(`s`.`fsTendered`)) AS `foodstampTender`,max(`r`.`rate`) AS `taxrate` from ((`taxrates` `r` left join `localtemptrans` `l` on((`r`.`id` = `l`.`tax`))) join `lttsummary` `s`) where (`l`.`trans_type` <> 'L') group by `r`.`id`,`r`.`description` */;
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

-- Dump completed on 2013-12-25 19:39:24
