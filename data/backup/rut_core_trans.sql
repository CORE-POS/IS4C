-- MySQL dump 10.13  Distrib 5.5.34, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: rut_core_trans
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
-- Table structure for table `AR_EOM_Summary`
--

DROP TABLE IF EXISTS `AR_EOM_Summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AR_EOM_Summary` (
  `cardno` int(11) NOT NULL DEFAULT '0',
  `memName` varchar(100) DEFAULT NULL,
  `priorBalance` decimal(10,2) DEFAULT NULL,
  `threeMonthCharges` decimal(10,2) DEFAULT NULL,
  `threeMonthPayments` decimal(10,2) DEFAULT NULL,
  `threeMonthBalance` decimal(10,2) DEFAULT NULL,
  `twoMonthCharges` decimal(10,2) DEFAULT NULL,
  `twoMonthPayments` decimal(10,2) DEFAULT NULL,
  `twoMonthBalance` decimal(10,2) DEFAULT NULL,
  `lastMonthCharges` decimal(10,2) DEFAULT NULL,
  `lastMonthPayments` decimal(10,2) DEFAULT NULL,
  `lastMonthBalance` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`cardno`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AR_EOM_Summary`
--

LOCK TABLES `AR_EOM_Summary` WRITE;
/*!40000 ALTER TABLE `AR_EOM_Summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `AR_EOM_Summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `AR_statementHistory`
--

DROP TABLE IF EXISTS `AR_statementHistory`;
/*!50001 DROP VIEW IF EXISTS `AR_statementHistory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `AR_statementHistory` (
  `card_no` tinyint NOT NULL,
  `charges` tinyint NOT NULL,
  `payments` tinyint NOT NULL,
  `date` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `dept_name` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `CashPerformDay`
--

DROP TABLE IF EXISTS `CashPerformDay`;
/*!50001 DROP VIEW IF EXISTS `CashPerformDay`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `CashPerformDay` (
  `proc_date` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `Trans_Num` tinyint NOT NULL,
  `startTime` tinyint NOT NULL,
  `endTime` tinyint NOT NULL,
  `transInterval` tinyint NOT NULL,
  `items` tinyint NOT NULL,
  `rings` tinyint NOT NULL,
  `Cancels` tinyint NOT NULL,
  `card_no` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `CashPerformDay_cache`
--

DROP TABLE IF EXISTS `CashPerformDay_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CashPerformDay_cache` (
  `proc_date` datetime DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_num` varchar(25) DEFAULT NULL,
  `startTime` datetime DEFAULT NULL,
  `endTime` datetime DEFAULT NULL,
  `transInterval` int(11) DEFAULT NULL,
  `items` float DEFAULT NULL,
  `rings` int(11) DEFAULT NULL,
  `Cancels` int(11) DEFAULT NULL,
  `card_no` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CashPerformDay_cache`
--

LOCK TABLES `CashPerformDay_cache` WRITE;
/*!40000 ALTER TABLE `CashPerformDay_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `CashPerformDay_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompleteSpecialOrder`
--

DROP TABLE IF EXISTS `CompleteSpecialOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CompleteSpecialOrder` (
  `order_id` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `trans_type` varchar(255) DEFAULT NULL,
  `trans_subtype` varchar(255) DEFAULT NULL,
  `trans_status` varchar(255) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` double DEFAULT '0',
  `unitPrice` double DEFAULT NULL,
  `total` double DEFAULT NULL,
  `regPrice` double DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `memDiscount` double DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` double DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` smallint(6) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompleteSpecialOrder`
--

LOCK TABLES `CompleteSpecialOrder` WRITE;
/*!40000 ALTER TABLE `CompleteSpecialOrder` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompleteSpecialOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `InvAdjustTotals`
--

DROP TABLE IF EXISTS `InvAdjustTotals`;
/*!50001 DROP VIEW IF EXISTS `InvAdjustTotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvAdjustTotals` (
  `upc` tinyint NOT NULL,
  `diff` tinyint NOT NULL,
  `inv_date` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `InvAdjustments`
--

DROP TABLE IF EXISTS `InvAdjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InvAdjustments` (
  `inv_date` datetime DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `diff` double DEFAULT NULL,
  KEY `upc` (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvAdjustments`
--

LOCK TABLES `InvAdjustments` WRITE;
/*!40000 ALTER TABLE `InvAdjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvAdjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InvCache`
--

DROP TABLE IF EXISTS `InvCache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InvCache` (
  `upc` varchar(13) DEFAULT NULL,
  `OrderedQty` int(11) DEFAULT NULL,
  `SoldQty` int(11) DEFAULT NULL,
  `Adjustments` int(11) DEFAULT NULL,
  `LastAdjustDate` datetime DEFAULT NULL,
  `CurrentStock` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvCache`
--

LOCK TABLES `InvCache` WRITE;
/*!40000 ALTER TABLE `InvCache` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvCache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InvDelivery`
--

DROP TABLE IF EXISTS `InvDelivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InvDelivery` (
  `inv_date` datetime DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `price` float DEFAULT NULL,
  KEY `upc` (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvDelivery`
--

LOCK TABLES `InvDelivery` WRITE;
/*!40000 ALTER TABLE `InvDelivery` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvDelivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InvDeliveryArchive`
--

DROP TABLE IF EXISTS `InvDeliveryArchive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InvDeliveryArchive` (
  `inv_date` datetime DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `price` float DEFAULT NULL,
  KEY `upc` (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvDeliveryArchive`
--

LOCK TABLES `InvDeliveryArchive` WRITE;
/*!40000 ALTER TABLE `InvDeliveryArchive` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvDeliveryArchive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InvDeliveryLM`
--

DROP TABLE IF EXISTS `InvDeliveryLM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InvDeliveryLM` (
  `inv_date` datetime DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `price` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvDeliveryLM`
--

LOCK TABLES `InvDeliveryLM` WRITE;
/*!40000 ALTER TABLE `InvDeliveryLM` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvDeliveryLM` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `InvDeliveryTotals`
--

DROP TABLE IF EXISTS `InvDeliveryTotals`;
/*!50001 DROP VIEW IF EXISTS `InvDeliveryTotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvDeliveryTotals` (
  `upc` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL,
  `inv_date` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `InvDeliveryUnion`
--

DROP TABLE IF EXISTS `InvDeliveryUnion`;
/*!50001 DROP VIEW IF EXISTS `InvDeliveryUnion`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvDeliveryUnion` (
  `upc` tinyint NOT NULL,
  `vendor_id` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL,
  `inv_date` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `InvRecentOrders`
--

DROP TABLE IF EXISTS `InvRecentOrders`;
/*!50001 DROP VIEW IF EXISTS `InvRecentOrders`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvRecentOrders` (
  `inv_date` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `InvRecentSales`
--

DROP TABLE IF EXISTS `InvRecentSales`;
/*!50001 DROP VIEW IF EXISTS `InvRecentSales`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvRecentSales` (
  `upc` tinyint NOT NULL,
  `mostRecentOrder` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `InvSales`
--

DROP TABLE IF EXISTS `InvSales`;
/*!50001 DROP VIEW IF EXISTS `InvSales`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvSales` (
  `inv_date` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `InvSalesArchive`
--

DROP TABLE IF EXISTS `InvSalesArchive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InvSalesArchive` (
  `inv_date` datetime DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `price` float DEFAULT NULL,
  KEY `upc` (`upc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvSalesArchive`
--

LOCK TABLES `InvSalesArchive` WRITE;
/*!40000 ALTER TABLE `InvSalesArchive` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvSalesArchive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `InvSalesTotals`
--

DROP TABLE IF EXISTS `InvSalesTotals`;
/*!50001 DROP VIEW IF EXISTS `InvSalesTotals`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvSalesTotals` (
  `upc` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `InvSalesUnion`
--

DROP TABLE IF EXISTS `InvSalesUnion`;
/*!50001 DROP VIEW IF EXISTS `InvSalesUnion`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `InvSalesUnion` (
  `upc` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `price` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `Inventory`
--

DROP TABLE IF EXISTS `Inventory`;
/*!50001 DROP VIEW IF EXISTS `Inventory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `Inventory` (
  `upc` tinyint NOT NULL,
  `OrderedQty` tinyint NOT NULL,
  `SoldQty` tinyint NOT NULL,
  `Adjustments` tinyint NOT NULL,
  `LastAdjustDate` tinyint NOT NULL,
  `CurrentStock` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `PendingSpecialOrder`
--

DROP TABLE IF EXISTS `PendingSpecialOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PendingSpecialOrder` (
  `order_id` int(11) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `register_no` smallint(6) DEFAULT NULL,
  `emp_no` smallint(6) DEFAULT NULL,
  `trans_no` int(11) DEFAULT NULL,
  `upc` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `trans_type` varchar(255) DEFAULT NULL,
  `trans_subtype` varchar(255) DEFAULT NULL,
  `trans_status` varchar(255) DEFAULT NULL,
  `department` smallint(6) DEFAULT NULL,
  `quantity` double DEFAULT NULL,
  `scale` tinyint(4) DEFAULT NULL,
  `cost` double DEFAULT '0',
  `unitPrice` double DEFAULT NULL,
  `total` double DEFAULT NULL,
  `regPrice` double DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `memDiscount` double DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` double DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` smallint(6) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` smallint(6) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` int(11) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PendingSpecialOrder`
--

LOCK TABLES `PendingSpecialOrder` WRITE;
/*!40000 ALTER TABLE `PendingSpecialOrder` DISABLE KEYS */;
/*!40000 ALTER TABLE `PendingSpecialOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpecialOrderContact`
--

DROP TABLE IF EXISTS `SpecialOrderContact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SpecialOrderContact` (
  `card_no` int(11) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `first_name` varchar(30) DEFAULT NULL,
  `othlast_name` varchar(30) DEFAULT NULL,
  `othfirst_name` varchar(30) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email_1` varchar(50) DEFAULT NULL,
  `email_2` varchar(50) DEFAULT NULL,
  `ads_OK` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpecialOrderContact`
--

LOCK TABLES `SpecialOrderContact` WRITE;
/*!40000 ALTER TABLE `SpecialOrderContact` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpecialOrderContact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpecialOrderDeptMap`
--

DROP TABLE IF EXISTS `SpecialOrderDeptMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SpecialOrderDeptMap` (
  `dept_ID` int(11) NOT NULL DEFAULT '0',
  `map_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`dept_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpecialOrderDeptMap`
--

LOCK TABLES `SpecialOrderDeptMap` WRITE;
/*!40000 ALTER TABLE `SpecialOrderDeptMap` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpecialOrderDeptMap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpecialOrderHistory`
--

DROP TABLE IF EXISTS `SpecialOrderHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SpecialOrderHistory` (
  `order_id` int(11) DEFAULT NULL,
  `entry_type` varchar(20) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `entry_value` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpecialOrderHistory`
--

LOCK TABLES `SpecialOrderHistory` WRITE;
/*!40000 ALTER TABLE `SpecialOrderHistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpecialOrderHistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpecialOrderID`
--

DROP TABLE IF EXISTS `SpecialOrderID`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SpecialOrderID` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpecialOrderID`
--

LOCK TABLES `SpecialOrderID` WRITE;
/*!40000 ALTER TABLE `SpecialOrderID` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpecialOrderID` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpecialOrderNotes`
--

DROP TABLE IF EXISTS `SpecialOrderNotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SpecialOrderNotes` (
  `order_id` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `superID` int(11) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpecialOrderNotes`
--

LOCK TABLES `SpecialOrderNotes` WRITE;
/*!40000 ALTER TABLE `SpecialOrderNotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpecialOrderNotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpecialOrderStatus`
--

DROP TABLE IF EXISTS `SpecialOrderStatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SpecialOrderStatus` (
  `order_id` int(11) NOT NULL DEFAULT '0',
  `status_flag` int(11) DEFAULT NULL,
  `sub_status` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpecialOrderStatus`
--

LOCK TABLES `SpecialOrderStatus` WRITE;
/*!40000 ALTER TABLE `SpecialOrderStatus` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpecialOrderStatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `TenderTapeGeneric`
--

DROP TABLE IF EXISTS `TenderTapeGeneric`;
/*!50001 DROP VIEW IF EXISTS `TenderTapeGeneric`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `TenderTapeGeneric` (
  `tdate` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `tender_code` tinyint NOT NULL,
  `-1 * sum(total)` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `alog`
--

DROP TABLE IF EXISTS `alog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alog` (
  `datetime` datetime DEFAULT NULL,
  `LaneNo` smallint(6) DEFAULT NULL,
  `CashierNo` smallint(6) DEFAULT NULL,
  `TransNo` int(11) DEFAULT NULL,
  `Activity` tinyint(4) DEFAULT NULL,
  `Interval` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alog`
--

LOCK TABLES `alog` WRITE;
/*!40000 ALTER TABLE `alog` DISABLE KEYS */;
/*!40000 ALTER TABLE `alog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ar_history`
--

DROP TABLE IF EXISTS `ar_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_history` (
  `card_no` int(11) DEFAULT NULL,
  `Charges` decimal(10,2) DEFAULT NULL,
  `Payments` decimal(10,2) DEFAULT NULL,
  `tdate` datetime DEFAULT NULL,
  `trans_num` varchar(90) DEFAULT NULL,
  KEY `card_no` (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ar_history`
--

LOCK TABLES `ar_history` WRITE;
/*!40000 ALTER TABLE `ar_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `ar_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ar_history_backup`
--

DROP TABLE IF EXISTS `ar_history_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_history_backup` (
  `card_no` int(11) DEFAULT NULL,
  `Charges` decimal(10,2) DEFAULT NULL,
  `Payments` decimal(10,2) DEFAULT NULL,
  `tdate` datetime DEFAULT NULL,
  `trans_num` varchar(90) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ar_history_backup`
--

LOCK TABLES `ar_history_backup` WRITE;
/*!40000 ALTER TABLE `ar_history_backup` DISABLE KEYS */;
/*!40000 ALTER TABLE `ar_history_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ar_history_sum`
--

DROP TABLE IF EXISTS `ar_history_sum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_history_sum` (
  `card_no` int(11) NOT NULL DEFAULT '0',
  `charges` decimal(10,2) DEFAULT NULL,
  `payments` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ar_history_sum`
--

LOCK TABLES `ar_history_sum` WRITE;
/*!40000 ALTER TABLE `ar_history_sum` DISABLE KEYS */;
/*!40000 ALTER TABLE `ar_history_sum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `ar_history_today`
--

DROP TABLE IF EXISTS `ar_history_today`;
/*!50001 DROP VIEW IF EXISTS `ar_history_today`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ar_history_today` (
  `card_no` tinyint NOT NULL,
  `charges` tinyint NOT NULL,
  `payments` tinyint NOT NULL,
  `tdate` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `ar_history_today_sum`
--

DROP TABLE IF EXISTS `ar_history_today_sum`;
/*!50001 DROP VIEW IF EXISTS `ar_history_today_sum`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ar_history_today_sum` (
  `card_no` tinyint NOT NULL,
  `charges` tinyint NOT NULL,
  `payments` tinyint NOT NULL,
  `balance` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `ar_live_balance`
--

DROP TABLE IF EXISTS `ar_live_balance`;
/*!50001 DROP VIEW IF EXISTS `ar_live_balance`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `ar_live_balance` (
  `card_no` tinyint NOT NULL,
  `totcharges` tinyint NOT NULL,
  `totpayments` tinyint NOT NULL,
  `balance` tinyint NOT NULL,
  `mark` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

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
-- Temporary table structure for view `dddItems`
--

DROP TABLE IF EXISTS `dddItems`;
/*!50001 DROP VIEW IF EXISTS `dddItems`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `dddItems` (
  `year` tinyint NOT NULL,
  `month` tinyint NOT NULL,
  `day` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `dept_no` tinyint NOT NULL,
  `dept_name` tinyint NOT NULL,
  `quantity` tinyint NOT NULL,
  `total` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `dheader`
--

DROP TABLE IF EXISTS `dheader`;
/*!50001 DROP VIEW IF EXISTS `dheader`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `dheader` (
  `proc_date` tinyint NOT NULL,
  `datetime` tinyint NOT NULL,
  `starttime` tinyint NOT NULL,
  `endtime` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `till_no` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `receipt_type` tinyint NOT NULL,
  `cust_id` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `pretax` tinyint NOT NULL,
  `tot_gross` tinyint NOT NULL,
  `tot_ref` tinyint NOT NULL,
  `tot_void` tinyint NOT NULL,
  `tot_taxA` tinyint NOT NULL,
  `discount` tinyint NOT NULL,
  `arPayments` tinyint NOT NULL,
  `stockPayments` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `memCoupons` tinyint NOT NULL,
  `tot_taxB` tinyint NOT NULL,
  `tot_taxC` tinyint NOT NULL,
  `tot_taxD` tinyint NOT NULL,
  `tot_rings` tinyint NOT NULL,
  `time` tinyint NOT NULL,
  `rings_per_min` tinyint NOT NULL,
  `rings_per_total` tinyint NOT NULL,
  `timeon` tinyint NOT NULL,
  `points_earned` tinyint NOT NULL,
  `uploaded` tinyint NOT NULL,
  `points_used` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `dlog`
--

DROP TABLE IF EXISTS `dlog`;
/*!50001 DROP VIEW IF EXISTS `dlog`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `dlog` (
  `tdate` tinyint NOT NULL,
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
  `trans_id` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_15`
--

DROP TABLE IF EXISTS `dlog_15`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dlog_15` (
  `tdate` datetime DEFAULT NULL,
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
  `cost` double DEFAULT NULL,
  `unitPrice` double DEFAULT NULL,
  `total` double DEFAULT NULL,
  `regPrice` double DEFAULT NULL,
  `tax` smallint(6) DEFAULT NULL,
  `foodstamp` tinyint(4) DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `memDiscount` double DEFAULT NULL,
  `discountable` tinyint(4) DEFAULT NULL,
  `discounttype` tinyint(4) DEFAULT NULL,
  `voided` tinyint(4) DEFAULT NULL,
  `percentDiscount` tinyint(4) DEFAULT NULL,
  `ItemQtty` double DEFAULT NULL,
  `volDiscType` tinyint(4) DEFAULT NULL,
  `volume` tinyint(4) DEFAULT NULL,
  `VolSpecial` double DEFAULT NULL,
  `mixMatch` varchar(13) DEFAULT NULL,
  `matched` tinyint(4) DEFAULT NULL,
  `memType` tinyint(2) DEFAULT NULL,
  `staff` tinyint(4) DEFAULT NULL,
  `numflag` int(11) DEFAULT '0',
  `charflag` varchar(2) DEFAULT '',
  `card_no` varchar(255) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `trans_num` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dlog_15`
--

LOCK TABLES `dlog_15` WRITE;
/*!40000 ALTER TABLE `dlog_15` DISABLE KEYS */;
/*!40000 ALTER TABLE `dlog_15` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `dlog_90_view`
--

DROP TABLE IF EXISTS `dlog_90_view`;
/*!50001 DROP VIEW IF EXISTS `dlog_90_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `dlog_90_view` (
  `tdate` tinyint NOT NULL,
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
  `trans_id` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

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
  `trans_id` int(11) DEFAULT NULL,
  KEY `datetime` (`datetime`),
  KEY `upc` (`upc`),
  KEY `department` (`department`),
  KEY `card_no` (`card_no`),
  KEY `trans_type` (`trans_type`)
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
-- Table structure for table `equity_history_sum`
--

DROP TABLE IF EXISTS `equity_history_sum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equity_history_sum` (
  `card_no` int(11) NOT NULL DEFAULT '0',
  `payments` decimal(10,2) DEFAULT NULL,
  `startdate` datetime DEFAULT NULL,
  PRIMARY KEY (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equity_history_sum`
--

LOCK TABLES `equity_history_sum` WRITE;
/*!40000 ALTER TABLE `equity_history_sum` DISABLE KEYS */;
/*!40000 ALTER TABLE `equity_history_sum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `equity_live_balance`
--

DROP TABLE IF EXISTS `equity_live_balance`;
/*!50001 DROP VIEW IF EXISTS `equity_live_balance`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `equity_live_balance` (
  `memnum` tinyint NOT NULL,
  `payments` tinyint NOT NULL,
  `startdate` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `houseCouponThisMonth`
--

DROP TABLE IF EXISTS `houseCouponThisMonth`;
/*!50001 DROP VIEW IF EXISTS `houseCouponThisMonth`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `houseCouponThisMonth` (
  `card_no` tinyint NOT NULL,
  `upc` tinyint NOT NULL,
  `quantity` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

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
/*!40000 ALTER TABLE `lane_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `memChargeBalance`
--

DROP TABLE IF EXISTS `memChargeBalance`;
/*!50001 DROP VIEW IF EXISTS `memChargeBalance`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `memChargeBalance` (
  `CardNo` tinyint NOT NULL,
  `availBal` tinyint NOT NULL,
  `balance` tinyint NOT NULL,
  `mark` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `newBalanceStockToday_test`
--

DROP TABLE IF EXISTS `newBalanceStockToday_test`;
/*!50001 DROP VIEW IF EXISTS `newBalanceStockToday_test`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `newBalanceStockToday_test` (
  `memnum` tinyint NOT NULL,
  `payments` tinyint NOT NULL,
  `startdate` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_dt_receipt_90`
--

DROP TABLE IF EXISTS `rp_dt_receipt_90`;
/*!50001 DROP VIEW IF EXISTS `rp_dt_receipt_90`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_dt_receipt_90` (
  `datetime` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `comment` tinyint NOT NULL,
  `total` tinyint NOT NULL,
  `Status` tinyint NOT NULL,
  `trans_type` tinyint NOT NULL,
  `memberID` tinyint NOT NULL,
  `unitPrice` tinyint NOT NULL,
  `voided` tinyint NOT NULL,
  `trans_id` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `rp_receipt_header_90`
--

DROP TABLE IF EXISTS `rp_receipt_header_90`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_header_90`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `rp_receipt_header_90` (
  `dateTimeStamp` tinyint NOT NULL,
  `memberID` tinyint NOT NULL,
  `trans_num` tinyint NOT NULL,
  `register_no` tinyint NOT NULL,
  `emp_no` tinyint NOT NULL,
  `trans_no` tinyint NOT NULL,
  `discountTTL` tinyint NOT NULL,
  `memSpecial` tinyint NOT NULL,
  `couponTotal` tinyint NOT NULL,
  `memCoupon` tinyint NOT NULL,
  `chargeTotal` tinyint NOT NULL,
  `transDiscount` tinyint NOT NULL,
  `tenderTotal` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `stockSumToday`
--

DROP TABLE IF EXISTS `stockSumToday`;
/*!50001 DROP VIEW IF EXISTS `stockSumToday`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `stockSumToday` (
  `card_no` tinyint NOT NULL,
  `totPayments` tinyint NOT NULL,
  `startdate` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `stockSum_purch`
--

DROP TABLE IF EXISTS `stockSum_purch`;
/*!50001 DROP VIEW IF EXISTS `stockSum_purch`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `stockSum_purch` (
  `card_no` tinyint NOT NULL,
  `totPayments` tinyint NOT NULL,
  `startdate` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `stockpurchases`
--

DROP TABLE IF EXISTS `stockpurchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stockpurchases` (
  `card_no` int(11) DEFAULT NULL,
  `stockPurchase` decimal(10,2) DEFAULT NULL,
  `tdate` datetime DEFAULT NULL,
  `trans_num` varchar(90) DEFAULT NULL,
  `dept` int(11) DEFAULT NULL,
  KEY `card_no` (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stockpurchases`
--

LOCK TABLES `stockpurchases` WRITE;
/*!40000 ALTER TABLE `stockpurchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `stockpurchases` ENABLE KEYS */;
UNLOCK TABLES;

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
  `trans_id` int(11) DEFAULT NULL,
  KEY `datetime` (`datetime`),
  KEY `upc` (`upc`),
  KEY `department` (`department`),
  KEY `card_no` (`card_no`),
  KEY `trans_type` (`trans_type`)
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
-- Table structure for table `transarchive`
--

DROP TABLE IF EXISTS `transarchive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transarchive` (
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
  KEY `datetime` (`datetime`),
  KEY `upc` (`upc`),
  KEY `department` (`department`),
  KEY `card_no` (`card_no`),
  KEY `trans_type` (`trans_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transarchive`
--

LOCK TABLES `transarchive` WRITE;
/*!40000 ALTER TABLE `transarchive` DISABLE KEYS */;
/*!40000 ALTER TABLE `transarchive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `unpaid_ar_balances`
--

DROP TABLE IF EXISTS `unpaid_ar_balances`;
/*!50001 DROP VIEW IF EXISTS `unpaid_ar_balances`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `unpaid_ar_balances` (
  `card_no` tinyint NOT NULL,
  `old_balance` tinyint NOT NULL,
  `recent_payments` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `unpaid_ar_today`
--

DROP TABLE IF EXISTS `unpaid_ar_today`;
/*!50001 DROP VIEW IF EXISTS `unpaid_ar_today`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `unpaid_ar_today` (
  `card_no` tinyint NOT NULL,
  `old_balance` tinyint NOT NULL,
  `recent_payments` tinyint NOT NULL,
  `mark` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

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
-- Table structure for table `voidTransHistory`
--

DROP TABLE IF EXISTS `voidTransHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voidTransHistory` (
  `tdate` datetime DEFAULT NULL,
  `description` varchar(40) DEFAULT NULL,
  `trans_num` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  KEY `tdate` (`tdate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voidTransHistory`
--

LOCK TABLES `voidTransHistory` WRITE;
/*!40000 ALTER TABLE `voidTransHistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `voidTransHistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `AR_statementHistory`
--

/*!50001 DROP TABLE IF EXISTS `AR_statementHistory`*/;
/*!50001 DROP VIEW IF EXISTS `AR_statementHistory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `AR_statementHistory` AS select `a`.`card_no` AS `card_no`,`a`.`Charges` AS `charges`,`a`.`Payments` AS `payments`,`a`.`tdate` AS `date`,`a`.`trans_num` AS `trans_num`,'' AS `upc`,'Payment - Thank You' AS `description`,'' AS `dept_name` from `ar_history` `a` where (((to_days(`a`.`tdate`) - to_days(now())) > -(91)) and (period_diff(date_format(`a`.`tdate`,'%Y%m'),date_format(now(),'%Y%m')) <= 0) and (`a`.`Payments` > 0)) union all select `a`.`card_no` AS `card_no`,`a`.`Charges` AS `charges`,`a`.`Payments` AS `payments`,cast(`a`.`tdate` as char charset latin1) AS `date`,`a`.`trans_num` AS `trans_num`,`d`.`upc` AS `upc`,(case when ((`d`.`trans_type` = 'T') and (`d`.`register_no` = 20)) then 'Gazette Advertisement' else `d`.`description` end) AS `description`,`d`.`description` AS `dept_name` from (`ar_history` `a` left join `transarchive` `d` on((((to_days(`a`.`tdate`) - to_days(`d`.`datetime`)) = 0) and (`a`.`trans_num` = concat(cast(`d`.`emp_no` as char charset latin1),'-',cast(`d`.`register_no` as char charset latin1),'-',cast(`d`.`trans_no` as char charset latin1)))))) where (((to_days(`a`.`tdate`) - to_days(now())) > -(91)) and (period_diff(date_format(`a`.`tdate`,'%Y%m'),date_format(now(),'%Y%m')) <= 0) and (`d`.`trans_status` <> 'X') and (`a`.`Payments` <= 0) and (((`d`.`trans_type` in ('I','D')) and (`d`.`trans_subtype` not in ('0','CP'))) or ((`d`.`trans_type` = 'T') and (`d`.`register_no` = 20) and (period_diff(date_format(`a`.`tdate`,'%Y%m'),date_format(((2009 - 5) - 1),'%Y%m')) = 0)))) union all select `d`.`card_no` AS `card_no`,(case when (`d`.`trans_subtype` = 'MI') then -(`d`.`total`) else 0 end) AS `charges`,(case when (`d`.`department` = -(999)) then `d`.`total` else 0 end) AS `payments`,cast(`d`.`datetime` as char charset latin1) AS `date`,concat(cast(`d`.`emp_no` as char charset latin1),'-',cast(`d`.`register_no` as char charset latin1),'-',cast(`d`.`trans_no` as char charset latin1)) AS `trans_num`,`d`.`upc` AS `upc`,(case when (`d`.`department` = -(999)) then 'Payment - Thank You' else `a`.`description` end) AS `description`,`d`.`description` AS `dept_name` from (`dtransactions` `d` left join `dtransactions` `a` on(((`d`.`register_no` = `a`.`register_no`) and (`d`.`emp_no` = `a`.`emp_no`) and (`d`.`trans_no` = `a`.`trans_no`)))) where (((`d`.`department` = -(999)) or (`d`.`trans_subtype` = 'MI')) and (`d`.`trans_status` <> 'X') and (`a`.`trans_type` in ('I','D')) and (`a`.`trans_subtype` not in ('0','CP'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `CashPerformDay`
--

/*!50001 DROP TABLE IF EXISTS `CashPerformDay`*/;
/*!50001 DROP VIEW IF EXISTS `CashPerformDay`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `CashPerformDay` AS select min(`dlog_90_view`.`tdate`) AS `proc_date`,max(`dlog_90_view`.`emp_no`) AS `emp_no`,max(`dlog_90_view`.`trans_num`) AS `Trans_Num`,min(`dlog_90_view`.`tdate`) AS `startTime`,max(`dlog_90_view`.`tdate`) AS `endTime`,(case when (timestampdiff(SECOND,min(`dlog_90_view`.`tdate`),max(`dlog_90_view`.`tdate`)) = 0) then 1 else timestampdiff(SECOND,min(`dlog_90_view`.`tdate`),max(`dlog_90_view`.`tdate`)) end) AS `transInterval`,sum((case when (abs(`dlog_90_view`.`quantity`) > 30) then 1 else abs(`dlog_90_view`.`quantity`) end)) AS `items`,count(`dlog_90_view`.`upc`) AS `rings`,sum((case when (`dlog_90_view`.`trans_status` = 'V') then 1 else 0 end)) AS `Cancels`,max(`dlog_90_view`.`card_no`) AS `card_no` from `dlog_90_view` where (`dlog_90_view`.`trans_type` in ('I','D','0','C')) group by year(`dlog_90_view`.`tdate`),month(`dlog_90_view`.`tdate`),dayofmonth(`dlog_90_view`.`tdate`),`dlog_90_view`.`trans_num` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvAdjustTotals`
--

/*!50001 DROP TABLE IF EXISTS `InvAdjustTotals`*/;
/*!50001 DROP VIEW IF EXISTS `InvAdjustTotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvAdjustTotals` AS select `InvAdjustments`.`upc` AS `upc`,sum(`InvAdjustments`.`diff`) AS `diff`,max(`InvAdjustments`.`inv_date`) AS `inv_date` from `InvAdjustments` group by `InvAdjustments`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvDeliveryTotals`
--

/*!50001 DROP TABLE IF EXISTS `InvDeliveryTotals`*/;
/*!50001 DROP VIEW IF EXISTS `InvDeliveryTotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvDeliveryTotals` AS select `InvDeliveryUnion`.`upc` AS `upc`,sum(`InvDeliveryUnion`.`quantity`) AS `quantity`,sum(`InvDeliveryUnion`.`price`) AS `price`,max(`InvDeliveryUnion`.`inv_date`) AS `inv_date` from `InvDeliveryUnion` group by `InvDeliveryUnion`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvDeliveryUnion`
--

/*!50001 DROP TABLE IF EXISTS `InvDeliveryUnion`*/;
/*!50001 DROP VIEW IF EXISTS `InvDeliveryUnion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvDeliveryUnion` AS select `InvDelivery`.`upc` AS `upc`,`InvDelivery`.`vendor_id` AS `vendor_id`,sum(`InvDelivery`.`quantity`) AS `quantity`,sum(`InvDelivery`.`price`) AS `price`,max(`InvDelivery`.`inv_date`) AS `inv_date` from `InvDelivery` group by `InvDelivery`.`upc`,`InvDelivery`.`vendor_id` union all select `InvDeliveryLM`.`upc` AS `upc`,`InvDeliveryLM`.`vendor_id` AS `vendor_id`,sum(`InvDeliveryLM`.`quantity`) AS `quantity`,sum(`InvDeliveryLM`.`price`) AS `price`,max(`InvDeliveryLM`.`inv_date`) AS `inv_date` from `InvDeliveryLM` group by `InvDeliveryLM`.`upc`,`InvDeliveryLM`.`vendor_id` union all select `InvDeliveryArchive`.`upc` AS `upc`,`InvDeliveryArchive`.`vendor_id` AS `vendor_id`,sum(`InvDeliveryArchive`.`quantity`) AS `quantity`,sum(`InvDeliveryArchive`.`price`) AS `price`,max(`InvDeliveryArchive`.`inv_date`) AS `inv_date` from `InvDeliveryArchive` group by `InvDeliveryArchive`.`upc`,`InvDeliveryArchive`.`vendor_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvRecentOrders`
--

/*!50001 DROP TABLE IF EXISTS `InvRecentOrders`*/;
/*!50001 DROP VIEW IF EXISTS `InvRecentOrders`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvRecentOrders` AS select `InvDelivery`.`inv_date` AS `inv_date`,`InvDelivery`.`upc` AS `upc`,sum(`InvDelivery`.`quantity`) AS `quantity`,sum(`InvDelivery`.`price`) AS `price` from `InvDelivery` group by `InvDelivery`.`inv_date`,`InvDelivery`.`upc` union all select `InvDeliveryLM`.`inv_date` AS `inv_date`,`InvDeliveryLM`.`upc` AS `upc`,sum(`InvDeliveryLM`.`quantity`) AS `quantity`,sum(`InvDeliveryLM`.`price`) AS `price` from `InvDeliveryLM` group by `InvDeliveryLM`.`inv_date`,`InvDeliveryLM`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvRecentSales`
--

/*!50001 DROP TABLE IF EXISTS `InvRecentSales`*/;
/*!50001 DROP VIEW IF EXISTS `InvRecentSales`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvRecentSales` AS select `t`.`upc` AS `upc`,max(`t`.`inv_date`) AS `mostRecentOrder`,sum((case when isnull(`s`.`quantity`) then 0 else `s`.`quantity` end)) AS `quantity`,sum((case when isnull(`s`.`price`) then 0 else `s`.`price` end)) AS `price` from (`InvDeliveryTotals` `t` left join `InvSales` `s` on(((`t`.`upc` = `s`.`upc`) and ((to_days(`s`.`inv_date`) - to_days(`t`.`inv_date`)) >= 0)))) group by `t`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvSales`
--

/*!50001 DROP TABLE IF EXISTS `InvSales`*/;
/*!50001 DROP VIEW IF EXISTS `InvSales`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvSales` AS select `transarchive`.`datetime` AS `inv_date`,`transarchive`.`upc` AS `upc`,`transarchive`.`quantity` AS `quantity`,`transarchive`.`total` AS `price` from `transarchive` where ((period_diff(date_format(now(),'%Y%m'),date_format(`transarchive`.`datetime`,'%Y%m')) <= 1) and (`transarchive`.`scale` = 0) and (`transarchive`.`trans_status` not in ('X','R')) and (`transarchive`.`trans_type` = 'I') and (`transarchive`.`trans_subtype` <> '0') and (`transarchive`.`register_no` <> 99) and (`transarchive`.`emp_no` <> 9999)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvSalesTotals`
--

/*!50001 DROP TABLE IF EXISTS `InvSalesTotals`*/;
/*!50001 DROP VIEW IF EXISTS `InvSalesTotals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvSalesTotals` AS select `InvSalesUnion`.`upc` AS `upc`,sum(`InvSalesUnion`.`quantity`) AS `quantity`,sum(`InvSalesUnion`.`price`) AS `price` from `InvSalesUnion` group by `InvSalesUnion`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `InvSalesUnion`
--

/*!50001 DROP TABLE IF EXISTS `InvSalesUnion`*/;
/*!50001 DROP VIEW IF EXISTS `InvSalesUnion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `InvSalesUnion` AS select `InvSales`.`upc` AS `upc`,sum(`InvSales`.`quantity`) AS `quantity`,sum(`InvSales`.`price`) AS `price` from `InvSales` where (period_diff(date_format(now(),'%Y%m'),date_format(`InvSales`.`inv_date`,'%Y%m')) = 0) group by `InvSales`.`upc` union all select `InvSalesArchive`.`upc` AS `upc`,sum(`InvSalesArchive`.`quantity`) AS `quantity`,sum(`InvSalesArchive`.`price`) AS `price` from `InvSalesArchive` group by `InvSalesArchive`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `Inventory`
--

/*!50001 DROP TABLE IF EXISTS `Inventory`*/;
/*!50001 DROP VIEW IF EXISTS `Inventory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `Inventory` AS select `d`.`upc` AS `upc`,`d`.`quantity` AS `OrderedQty`,(case when isnull(`s`.`quantity`) then 0 else `s`.`quantity` end) AS `SoldQty`,(case when isnull(`a`.`diff`) then 0 else `a`.`diff` end) AS `Adjustments`,(case when isnull(`a`.`inv_date`) then '1900-01-01' else `a`.`inv_date` end) AS `LastAdjustDate`,((`d`.`quantity` - (case when isnull(`s`.`quantity`) then 0 else `s`.`quantity` end)) + (case when isnull(`a`.`diff`) then 0 else `a`.`diff` end)) AS `CurrentStock` from (((`rut_core_trans`.`InvDeliveryTotals` `d` join `rut_core_op`.`vendorItems` `v` on((`d`.`upc` = `v`.`upc`))) left join `rut_core_trans`.`InvSalesTotals` `s` on((`d`.`upc` = `s`.`upc`))) left join `rut_core_trans`.`InvAdjustTotals` `a` on((`d`.`upc` = `a`.`upc`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `TenderTapeGeneric`
--

/*!50001 DROP TABLE IF EXISTS `TenderTapeGeneric`*/;
/*!50001 DROP VIEW IF EXISTS `TenderTapeGeneric`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `TenderTapeGeneric` AS select max(`dlog`.`tdate`) AS `tdate`,`dlog`.`emp_no` AS `emp_no`,`dlog`.`register_no` AS `register_no`,`dlog`.`trans_no` AS `trans_no`,(case when ((`dlog`.`trans_subtype` = 'CP') and (`dlog`.`upc` like '%MAD%')) then '' when (`dlog`.`trans_subtype` in ('EF','EC','TA')) then 'EF' else `dlog`.`trans_subtype` end) AS `tender_code`,(-(1) * sum(`dlog`.`total`)) AS `-1 * sum(total)` from `dlog` where (((to_days(now()) - to_days(`dlog`.`tdate`)) = 0) and (`dlog`.`trans_subtype` not in ('0',''))) group by `dlog`.`emp_no`,`dlog`.`register_no`,`dlog`.`trans_no`,(case when ((`dlog`.`trans_subtype` = 'CP') and (`dlog`.`upc` like '%MAD%')) then '' when (`dlog`.`trans_subtype` in ('EF','EC','TA')) then 'EF' else `dlog`.`trans_subtype` end) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ar_history_today`
--

/*!50001 DROP TABLE IF EXISTS `ar_history_today`*/;
/*!50001 DROP VIEW IF EXISTS `ar_history_today`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ar_history_today` AS select `dlog`.`card_no` AS `card_no`,sum((case when (`dlog`.`trans_subtype` = 'MI') then -(`dlog`.`total`) else 0 end)) AS `charges`,sum((case when (`dlog`.`department` = -(999)) then `dlog`.`total` else 0 end)) AS `payments`,max(`dlog`.`tdate`) AS `tdate`,`dlog`.`trans_num` AS `trans_num` from `dlog` where ((`dlog`.`trans_subtype` = 'MI') or ((`dlog`.`department` = -(999)) and ((to_days(now()) - to_days(`dlog`.`tdate`)) = 0))) group by `dlog`.`card_no`,`dlog`.`trans_num` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ar_history_today_sum`
--

/*!50001 DROP TABLE IF EXISTS `ar_history_today_sum`*/;
/*!50001 DROP VIEW IF EXISTS `ar_history_today_sum`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ar_history_today_sum` AS select `dlog`.`card_no` AS `card_no`,sum((case when (`dlog`.`trans_subtype` = 'MI') then -(`dlog`.`total`) else 0 end)) AS `charges`,sum((case when (`dlog`.`department` = -(999)) then `dlog`.`total` else 0 end)) AS `payments`,(sum((case when (`dlog`.`trans_subtype` = 'MI') then -(`dlog`.`total`) else 0 end)) - sum((case when (`dlog`.`department` = -(999)) then `dlog`.`total` else 0 end))) AS `balance` from `dlog` where ((`dlog`.`trans_subtype` = 'MI') or ((`dlog`.`department` = -(999)) and ((to_days(now()) - to_days(`dlog`.`tdate`)) = 0))) group by `dlog`.`card_no` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ar_live_balance`
--

/*!50001 DROP TABLE IF EXISTS `ar_live_balance`*/;
/*!50001 DROP VIEW IF EXISTS `ar_live_balance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ar_live_balance` AS select `c`.`CardNo` AS `card_no`,((case when isnull(`a`.`charges`) then 0 else `a`.`charges` end) + (case when isnull(`t`.`charges`) then 0 else `t`.`charges` end)) AS `totcharges`,((case when isnull(`a`.`payments`) then 0 else `a`.`payments` end) + (case when isnull(`t`.`payments`) then 0 else `t`.`payments` end)) AS `totpayments`,((case when isnull(`a`.`balance`) then 0 else `a`.`balance` end) + (case when isnull(`t`.`balance`) then 0 else `t`.`balance` end)) AS `balance`,(case when isnull(`t`.`card_no`) then 0 else 1 end) AS `mark` from ((`rut_core_op`.`custdata` `c` left join `rut_core_trans`.`ar_history_sum` `a` on(((`c`.`CardNo` = `a`.`card_no`) and (`c`.`personNum` = 1)))) left join `rut_core_trans`.`ar_history_today_sum` `t` on(((`c`.`CardNo` = `t`.`card_no`) and (`c`.`personNum` = 1)))) where (`c`.`personNum` = 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

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
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `ccReceiptView` AS select (case when (`r`.`mode` = 'tender') then 'Credit Card Purchase' when (`r`.`mode` = 'retail_sale') then 'Credit Card Purchase' when (`r`.`mode` = 'Credit_Sale') then 'Credit Card Purchase' when (`r`.`mode` = 'retail_credit_alone') then 'Credit Card Refund' when (`r`.`mode` = 'Credit_Return') then 'Credit Card Refund' when (`r`.`mode` = 'refund') then 'Credit Card Refund' else '' end) AS `tranType`,(case when ((`r`.`mode` = 'refund') or (`r`.`mode` = 'retail_credit_alone')) then (-(1) * `r`.`amount`) else `r`.`amount` end) AS `amount`,`r`.`PAN` AS `PAN`,(case when (`r`.`manual` = 1) then 'Manual' else 'Swiped' end) AS `entryMethod`,`r`.`issuer` AS `issuer`,`r`.`name` AS `name`,`s`.`xResultMessage` AS `xResultMessage`,`s`.`xApprovalNumber` AS `xApprovalNumber`,`s`.`xTransactionID` AS `xTransactionID`,`r`.`date` AS `date`,`r`.`cashierNo` AS `cashierNo`,`r`.`laneNo` AS `laneNo`,`r`.`transNo` AS `transNo`,`r`.`transID` AS `transID`,`r`.`datetime` AS `datetime`,0 AS `sortorder` from (`efsnetRequest` `r` left join `efsnetResponse` `s` on(((`s`.`date` = `r`.`date`) and (`s`.`cashierNo` = `r`.`cashierNo`) and (`s`.`laneNo` = `r`.`laneNo`) and (`s`.`transNo` = `r`.`transNo`) and (`s`.`transID` = `r`.`transID`)))) where ((`s`.`validResponse` = 1) and ((`s`.`xResultMessage` like '%APPROVE%') or (`s`.`xResultMessage` like '%PENDING%')) and (`r`.`date` = date_format(curdate(),'%Y%m%d'))) union all select (case when (`r`.`mode` = 'tender') then 'Credit Card Purchase CANCELED' when (`r`.`mode` = 'retail_sale') then 'Credit Card Purchase CANCELLED' when (`r`.`mode` = 'Credit_Sale') then 'Credit Card Purchase CANCELLED' when (`r`.`mode` = 'retail_credit_alone') then 'Credit Card Refund CANCELLED' when (`r`.`mode` = 'Credit_Return') then 'Credit Card Refund CANCELLED' when (`r`.`mode` = 'refund') then 'Credit Card Refund CANCELED' else '' end) AS `tranType`,(case when ((`r`.`mode` = 'refund') or (`r`.`mode` = 'retail_credit_alone')) then `r`.`amount` else (-(1) * `r`.`amount`) end) AS `amount`,`r`.`PAN` AS `PAN`,(case when (`r`.`manual` = 1) then 'Manual' else 'Swiped' end) AS `entryMethod`,`r`.`issuer` AS `issuer`,`r`.`name` AS `name`,`s`.`xResultMessage` AS `xResultMessage`,`s`.`xApprovalNumber` AS `xApprovalNumber`,`s`.`xTransactionID` AS `xTransactionID`,`r`.`date` AS `date`,`r`.`cashierNo` AS `cashierNo`,`r`.`laneNo` AS `laneNo`,`r`.`transNo` AS `transNo`,`r`.`transID` AS `transID`,`r`.`datetime` AS `datetime`,1 AS `sortorder` from ((`efsnetRequestMod` `m` left join `efsnetRequest` `r` on(((`r`.`date` = `m`.`date`) and (`r`.`cashierNo` = `m`.`cashierNo`) and (`r`.`laneNo` = `m`.`laneNo`) and (`r`.`transNo` = `m`.`transNo`) and (`r`.`transID` = `m`.`transID`)))) left join `efsnetResponse` `s` on(((`s`.`date` = `r`.`date`) and (`s`.`cashierNo` = `r`.`cashierNo`) and (`s`.`laneNo` = `r`.`laneNo`) and (`s`.`transNo` = `r`.`transNo`) and (`s`.`transID` = `r`.`transID`)))) where ((`s`.`validResponse` = 1) and (`s`.`xResultMessage` like '%APPROVE%') and (`m`.`validResponse` = 1) and ((`m`.`xResponseCode` = 0) or (`m`.`xResultMessage` like '%APPROVE%')) and (`m`.`mode` = 'void') and (`m`.`date` = date_format(curdate(),'%Y%m%d'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `dddItems`
--

/*!50001 DROP TABLE IF EXISTS `dddItems`*/;
/*!50001 DROP VIEW IF EXISTS `dddItems`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `dddItems` AS select year(`d`.`datetime`) AS `year`,month(`d`.`datetime`) AS `month`,dayofmonth(`d`.`datetime`) AS `day`,`d`.`upc` AS `upc`,`d`.`description` AS `description`,`e`.`dept_no` AS `dept_no`,`e`.`dept_name` AS `dept_name`,sum(`d`.`quantity`) AS `quantity`,sum(`d`.`total`) AS `total` from (`rut_core_trans`.`dtransactions` `d` left join `rut_core_op`.`departments` `e` on((`d`.`department` = `e`.`dept_no`))) where ((`d`.`trans_status` = 'Z') and (`d`.`trans_type` in ('D','I')) and (`d`.`trans_subtype` = '') and (`d`.`emp_no` <> 9999) and (`d`.`register_no` <> 99) and ((to_days(now()) - to_days(`d`.`datetime`)) = 0)) group by year(`d`.`datetime`),month(`d`.`datetime`),dayofmonth(`d`.`datetime`),`d`.`upc`,`d`.`description`,`e`.`dept_no`,`e`.`dept_name` union all select year(`d`.`datetime`) AS `year`,month(`d`.`datetime`) AS `month`,dayofmonth(`d`.`datetime`) AS `day`,`d`.`upc` AS `upc`,`d`.`description` AS `description`,`e`.`dept_no` AS `dept_no`,`e`.`dept_name` AS `dept_name`,sum(`d`.`quantity`) AS `quantity`,sum(`d`.`total`) AS `total` from (`rut_core_trans`.`transarchive` `d` left join `rut_core_op`.`departments` `e` on((`d`.`department` = `e`.`dept_no`))) where ((`d`.`trans_status` = 'Z') and (`d`.`trans_type` in ('D','I')) and (`d`.`trans_subtype` = '') and (`d`.`emp_no` <> 9999) and (`d`.`register_no` <> 99)) group by year(`d`.`datetime`),month(`d`.`datetime`),dayofmonth(`d`.`datetime`),`d`.`upc`,`d`.`description`,`e`.`dept_no`,`e`.`dept_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `dheader`
--

/*!50001 DROP TABLE IF EXISTS `dheader`*/;
/*!50001 DROP VIEW IF EXISTS `dheader`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `dheader` AS select min(`dlog_90_view`.`tdate`) AS `proc_date`,min(`dlog_90_view`.`tdate`) AS `datetime`,min(`dlog_90_view`.`tdate`) AS `starttime`,max(`dlog_90_view`.`tdate`) AS `endtime`,`dlog_90_view`.`emp_no` AS `emp_no`,`dlog_90_view`.`register_no` AS `till_no`,`dlog_90_view`.`register_no` AS `register_no`,`dlog_90_view`.`trans_no` AS `trans_no`,'N' AS `trans_type`,'' AS `receipt_type`,`dlog_90_view`.`card_no` AS `cust_id`,sum((case when (`dlog_90_view`.`trans_type` = 'T') then (-(1) * `dlog_90_view`.`total`) else 0 end)) AS `total`,sum(((case when (`dlog_90_view`.`trans_type` = 'T') then (-(1) * `dlog_90_view`.`total`) else 0 end) + (case when (`dlog_90_view`.`trans_type` = 'S') then (-(1) * `dlog_90_view`.`total`) else 0 end))) AS `pretax`,sum((((case when (`dlog_90_view`.`trans_type` = 'T') then (-(1) * `dlog_90_view`.`total`) else 0 end) + (case when (`dlog_90_view`.`trans_type` = 'S') then (-(1) * `dlog_90_view`.`total`) else 0 end)) + (case when (`dlog_90_view`.`upc` like 'TAX%') then `dlog_90_view`.`total` else 0 end))) AS `tot_gross`,sum((case when (`dlog_90_view`.`trans_status` = 'R') then (-(1) * `dlog_90_view`.`total`) else 0 end)) AS `tot_ref`,sum((case when (`dlog_90_view`.`trans_status` = 'V') then (-(1) * `dlog_90_view`.`total`) else 0 end)) AS `tot_void`,sum((case when (`dlog_90_view`.`upc` like 'TAX%') then `dlog_90_view`.`total` else 0 end)) AS `tot_taxA`,sum((case when (`dlog_90_view`.`trans_type` = 'S') then (-(1) * `dlog_90_view`.`total`) else 0 end)) AS `discount`,sum((case when (`dlog_90_view`.`department` = -(999)) then `dlog_90_view`.`total` else 0 end)) AS `arPayments`,sum((case when (`dlog_90_view`.`department` = 46) then `dlog_90_view`.`total` else 0 end)) AS `stockPayments`,sum((case when (`dlog_90_view`.`trans_subtype` = 'MI') then (-(1) * `dlog_90_view`.`total`) else 0 end)) AS `chargeTotal`,sum((case when (`dlog_90_view`.`upc` like '%MAD%') then `dlog_90_view`.`total` else 0 end)) AS `memCoupons`,0 AS `tot_taxB`,0 AS `tot_taxC`,0 AS `tot_taxD`,(case when (`dlog_90_view`.`trans_no` = 1) then 0 else sum((case when ((`dlog_90_view`.`trans_type` = 'I') or (`dlog_90_view`.`trans_type` = 'D')) then 1 else 0 end)) end) AS `tot_rings`,timestampdiff(SECOND,min(`dlog_90_view`.`tdate`),max(`dlog_90_view`.`tdate`)) AS `time`,0 AS `rings_per_min`,0 AS `rings_per_total`,0 AS `timeon`,0 AS `points_earned`,1 AS `uploaded`,0 AS `points_used`,`dlog_90_view`.`trans_num` AS `trans_num` from `dlog_90_view` group by `dlog_90_view`.`trans_num`,`dlog_90_view`.`emp_no`,`dlog_90_view`.`register_no`,`dlog_90_view`.`trans_no`,`dlog_90_view`.`card_no` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `dlog`
--

/*!50001 DROP TABLE IF EXISTS `dlog`*/;
/*!50001 DROP VIEW IF EXISTS `dlog`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `dlog` AS select `dtransactions`.`datetime` AS `tdate`,`dtransactions`.`register_no` AS `register_no`,`dtransactions`.`emp_no` AS `emp_no`,`dtransactions`.`trans_no` AS `trans_no`,`dtransactions`.`upc` AS `upc`,`dtransactions`.`description` AS `description`,(case when ((`dtransactions`.`trans_subtype` in ('CP','IC')) or (`dtransactions`.`upc` like '%000000052')) then 'T' when (`dtransactions`.`upc` = 'DISCOUNT') then 'S' else `dtransactions`.`trans_type` end) AS `trans_type`,(case when (`dtransactions`.`upc` = 'MAD Coupon') then 'MA' when (`dtransactions`.`upc` like '%00000000052') then 'RR' else `dtransactions`.`trans_subtype` end) AS `trans_subtype`,`dtransactions`.`trans_status` AS `trans_status`,`dtransactions`.`department` AS `department`,`dtransactions`.`quantity` AS `quantity`,`dtransactions`.`scale` AS `scale`,`dtransactions`.`cost` AS `cost`,`dtransactions`.`unitPrice` AS `unitPrice`,`dtransactions`.`total` AS `total`,`dtransactions`.`regPrice` AS `regPrice`,`dtransactions`.`tax` AS `tax`,`dtransactions`.`foodstamp` AS `foodstamp`,`dtransactions`.`discount` AS `discount`,`dtransactions`.`memDiscount` AS `memDiscount`,`dtransactions`.`discountable` AS `discountable`,`dtransactions`.`discounttype` AS `discounttype`,`dtransactions`.`voided` AS `voided`,`dtransactions`.`percentDiscount` AS `percentDiscount`,`dtransactions`.`ItemQtty` AS `ItemQtty`,`dtransactions`.`volDiscType` AS `volDiscType`,`dtransactions`.`volume` AS `volume`,`dtransactions`.`VolSpecial` AS `VolSpecial`,`dtransactions`.`mixMatch` AS `mixMatch`,`dtransactions`.`matched` AS `matched`,`dtransactions`.`memType` AS `memType`,`dtransactions`.`staff` AS `staff`,`dtransactions`.`numflag` AS `numflag`,`dtransactions`.`charflag` AS `charflag`,`dtransactions`.`card_no` AS `card_no`,`dtransactions`.`trans_id` AS `trans_id`,concat(cast(`dtransactions`.`emp_no` as char charset latin1),'-',cast(`dtransactions`.`register_no` as char charset latin1),'-',cast(`dtransactions`.`trans_no` as char charset latin1)) AS `trans_num` from `dtransactions` where ((`dtransactions`.`trans_status` not in ('D','X','Z')) and (`dtransactions`.`emp_no` <> 9999) and (`dtransactions`.`register_no` <> 99)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `dlog_90_view`
--

/*!50001 DROP TABLE IF EXISTS `dlog_90_view`*/;
/*!50001 DROP VIEW IF EXISTS `dlog_90_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `dlog_90_view` AS select `transarchive`.`datetime` AS `tdate`,`transarchive`.`register_no` AS `register_no`,`transarchive`.`emp_no` AS `emp_no`,`transarchive`.`trans_no` AS `trans_no`,`transarchive`.`upc` AS `upc`,`transarchive`.`description` AS `description`,(case when ((`transarchive`.`trans_subtype` in ('CP','IC')) or (`transarchive`.`upc` like '%000000052')) then 'T' when (`transarchive`.`upc` = 'DISCOUNT') then 'S' else `transarchive`.`trans_type` end) AS `trans_type`,(case when (`transarchive`.`upc` = 'MAD Coupon') then 'MA' when (`transarchive`.`upc` like '%00000000052') then 'RR' else `transarchive`.`trans_subtype` end) AS `trans_subtype`,`transarchive`.`trans_status` AS `trans_status`,`transarchive`.`department` AS `department`,`transarchive`.`quantity` AS `quantity`,`transarchive`.`scale` AS `scale`,`transarchive`.`cost` AS `cost`,`transarchive`.`unitPrice` AS `unitPrice`,`transarchive`.`total` AS `total`,`transarchive`.`regPrice` AS `regPrice`,`transarchive`.`tax` AS `tax`,`transarchive`.`foodstamp` AS `foodstamp`,`transarchive`.`discount` AS `discount`,`transarchive`.`memDiscount` AS `memDiscount`,`transarchive`.`discountable` AS `discountable`,`transarchive`.`discounttype` AS `discounttype`,`transarchive`.`voided` AS `voided`,`transarchive`.`percentDiscount` AS `percentDiscount`,`transarchive`.`ItemQtty` AS `ItemQtty`,`transarchive`.`volDiscType` AS `volDiscType`,`transarchive`.`volume` AS `volume`,`transarchive`.`VolSpecial` AS `VolSpecial`,`transarchive`.`mixMatch` AS `mixMatch`,`transarchive`.`matched` AS `matched`,`transarchive`.`memType` AS `memType`,`transarchive`.`staff` AS `staff`,`transarchive`.`numflag` AS `numflag`,`transarchive`.`charflag` AS `charflag`,`transarchive`.`card_no` AS `card_no`,`transarchive`.`trans_id` AS `trans_id`,concat(cast(`transarchive`.`emp_no` as char charset latin1),'-',cast(`transarchive`.`register_no` as char charset latin1),'-',cast(`transarchive`.`trans_no` as char charset latin1)) AS `trans_num` from `transarchive` where ((`transarchive`.`trans_status` not in ('D','X','Z')) and (`transarchive`.`emp_no` <> 9999) and (`transarchive`.`register_no` <> 99)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `equity_live_balance`
--

/*!50001 DROP TABLE IF EXISTS `equity_live_balance`*/;
/*!50001 DROP VIEW IF EXISTS `equity_live_balance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `equity_live_balance` AS select `m`.`card_no` AS `memnum`,(case when ((`a`.`card_no` is not null) and (`b`.`card_no` is not null)) then (`a`.`payments` + `b`.`totPayments`) when (`a`.`card_no` is not null) then `a`.`payments` when (`b`.`card_no` is not null) then `b`.`totPayments` end) AS `payments`,(case when isnull(`a`.`startdate`) then `b`.`startdate` else `a`.`startdate` end) AS `startdate` from ((`rut_core_op`.`meminfo` `m` left join `rut_core_trans`.`equity_history_sum` `a` on((`a`.`card_no` = `m`.`card_no`))) left join `rut_core_trans`.`stockSumToday` `b` on((`m`.`card_no` = `b`.`card_no`))) where ((`a`.`card_no` is not null) or (`b`.`card_no` is not null)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `houseCouponThisMonth`
--

/*!50001 DROP TABLE IF EXISTS `houseCouponThisMonth`*/;
/*!50001 DROP VIEW IF EXISTS `houseCouponThisMonth`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `houseCouponThisMonth` AS select `dlog_90_view`.`card_no` AS `card_no`,`dlog_90_view`.`upc` AS `upc`,sum(`dlog_90_view`.`quantity`) AS `quantity` from `dlog_90_view` where ((`dlog_90_view`.`upc` like '00499999%') and (period_diff(date_format(now(),'%Y%m'),date_format(`dlog_90_view`.`tdate`,'%Y%m')) = 0)) group by `dlog_90_view`.`card_no`,`dlog_90_view`.`upc` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `memChargeBalance`
--

/*!50001 DROP TABLE IF EXISTS `memChargeBalance`*/;
/*!50001 DROP VIEW IF EXISTS `memChargeBalance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `memChargeBalance` AS select `c`.`CardNo` AS `CardNo`,(case when isnull(`a`.`balance`) then `c`.`ChargeLimit` else (`c`.`ChargeLimit` - `a`.`balance`) end) AS `availBal`,(case when isnull(`a`.`balance`) then 0 else `a`.`balance` end) AS `balance`,(case when isnull(`a`.`mark`) then 0 else `a`.`mark` end) AS `mark` from (`rut_core_op`.`custdata` `c` left join `rut_core_trans`.`ar_live_balance` `a` on((`c`.`CardNo` = `a`.`card_no`))) where (`c`.`personNum` = 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `newBalanceStockToday_test`
--

/*!50001 DROP TABLE IF EXISTS `newBalanceStockToday_test`*/;
/*!50001 DROP VIEW IF EXISTS `newBalanceStockToday_test`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `newBalanceStockToday_test` AS select `m`.`card_no` AS `memnum`,(case when ((`a`.`card_no` is not null) and (`b`.`card_no` is not null)) then (`a`.`totPayments` + `b`.`totPayments`) when (`a`.`card_no` is not null) then `a`.`totPayments` when (`b`.`card_no` is not null) then `b`.`totPayments` end) AS `payments`,(case when isnull(`a`.`startdate`) then `b`.`startdate` else `a`.`startdate` end) AS `startdate` from ((`rut_core_op`.`meminfo` `m` left join `rut_core_trans`.`stockSum_purch` `a` on((`a`.`card_no` = `m`.`card_no`))) left join `rut_core_trans`.`stockSumToday` `b` on((`m`.`card_no` = `b`.`card_no`))) where ((`a`.`card_no` is not null) or (`b`.`card_no` is not null)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_dt_receipt_90`
--

/*!50001 DROP TABLE IF EXISTS `rp_dt_receipt_90`*/;
/*!50001 DROP VIEW IF EXISTS `rp_dt_receipt_90`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_dt_receipt_90` AS select `transarchive`.`datetime` AS `datetime`,`transarchive`.`register_no` AS `register_no`,`transarchive`.`emp_no` AS `emp_no`,`transarchive`.`trans_no` AS `trans_no`,`transarchive`.`description` AS `description`,(case when (`transarchive`.`voided` = 5) then 'Discount' when (`transarchive`.`trans_status` = 'M') then 'Mbr special' when ((`transarchive`.`scale` <> 0) and (`transarchive`.`quantity` <> 0)) then concat(cast(`transarchive`.`quantity` as char charset latin1),' @ ',cast(`transarchive`.`unitPrice` as char charset latin1)) when ((abs(`transarchive`.`ItemQtty`) > 1) and (abs(`transarchive`.`ItemQtty`) > abs(`transarchive`.`quantity`)) and (`transarchive`.`discounttype` <> 3) and (`transarchive`.`quantity` = 1)) then concat(cast(`transarchive`.`volume` as char charset latin1),' /',cast(`transarchive`.`unitPrice` as char charset latin1)) when ((abs(`transarchive`.`ItemQtty`) > 1) and (abs(`transarchive`.`ItemQtty`) > abs(`transarchive`.`quantity`)) and (`transarchive`.`discounttype` <> 3) and (`transarchive`.`quantity` <> 1)) then concat(cast(`transarchive`.`quantity` as char charset latin1),' @ ',cast(`transarchive`.`volume` as char charset latin1),' /',cast(`transarchive`.`unitPrice` as char charset latin1)) when ((abs(`transarchive`.`ItemQtty`) > 1) and (`transarchive`.`discounttype` = 3)) then concat(cast(`transarchive`.`ItemQtty` as char charset latin1),' /',cast(`transarchive`.`unitPrice` as char charset latin1)) when (abs(`transarchive`.`ItemQtty`) > 1) then concat(cast(`transarchive`.`quantity` as char charset latin1),' @ ',cast(`transarchive`.`unitPrice` as char charset latin1)) when (`transarchive`.`matched` > 0) then '1 w/ vol adj' else '' end) AS `comment`,`transarchive`.`total` AS `total`,(case when (`transarchive`.`trans_status` = 'V') then 'VD' when (`transarchive`.`trans_status` = 'R') then 'RF' when ((`transarchive`.`tax` <> 0) and (`transarchive`.`foodstamp` <> 0)) then 'TF' when ((`transarchive`.`tax` <> 0) and (`transarchive`.`foodstamp` = 0)) then 'T' when ((`transarchive`.`tax` = 0) and (`transarchive`.`foodstamp` <> 0)) then 'F' when ((`transarchive`.`tax` = 0) and (`transarchive`.`foodstamp` = 0)) then '' end) AS `Status`,`transarchive`.`trans_type` AS `trans_type`,`transarchive`.`card_no` AS `memberID`,`transarchive`.`unitPrice` AS `unitPrice`,`transarchive`.`voided` AS `voided`,`transarchive`.`trans_id` AS `trans_id`,concat(cast(`transarchive`.`emp_no` as char charset latin1),'-',cast(`transarchive`.`register_no` as char charset latin1),'-',cast(`transarchive`.`trans_no` as char charset latin1)) AS `trans_num` from `transarchive` where ((`transarchive`.`voided` <> 5) and (`transarchive`.`upc` <> 'TAX') and (`transarchive`.`upc` <> 'DISCOUNT')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `rp_receipt_header_90`
--

/*!50001 DROP TABLE IF EXISTS `rp_receipt_header_90`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_header_90`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt_header_90` AS select `transarchive`.`datetime` AS `dateTimeStamp`,`transarchive`.`card_no` AS `memberID`,concat(cast(`transarchive`.`emp_no` as char charset latin1),'-',cast(`transarchive`.`register_no` as char charset latin1),'-',cast(`transarchive`.`trans_no` as char charset latin1)) AS `trans_num`,`transarchive`.`register_no` AS `register_no`,`transarchive`.`emp_no` AS `emp_no`,`transarchive`.`trans_no` AS `trans_no`,cast(sum((case when (`transarchive`.`discounttype` = 1) then `transarchive`.`discount` else 0 end)) as decimal(10,2)) AS `discountTTL`,cast(sum((case when (`transarchive`.`discounttype` = 2) then `transarchive`.`memDiscount` else 0 end)) as decimal(10,2)) AS `memSpecial`,cast(sum((case when (`transarchive`.`upc` = '0000000008005') then `transarchive`.`total` else 0 end)) as decimal(10,2)) AS `couponTotal`,cast(sum((case when (`transarchive`.`upc` = 'MEMCOUPON') then `transarchive`.`unitPrice` else 0 end)) as decimal(10,2)) AS `memCoupon`,abs(sum((case when ((`transarchive`.`trans_subtype` = 'MI') or (`transarchive`.`trans_subtype` = 'CX')) then `transarchive`.`total` else 0 end))) AS `chargeTotal`,sum((case when (`transarchive`.`upc` = 'Discount') then `transarchive`.`total` else 0 end)) AS `transDiscount`,sum((case when (`transarchive`.`trans_type` = 'T') then (-(1) * `transarchive`.`total`) else 0 end)) AS `tenderTotal` from `transarchive` group by `transarchive`.`register_no`,`transarchive`.`emp_no`,`transarchive`.`trans_no`,`transarchive`.`card_no`,`transarchive`.`datetime` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `stockSumToday`
--

/*!50001 DROP TABLE IF EXISTS `stockSumToday`*/;
/*!50001 DROP VIEW IF EXISTS `stockSumToday`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `stockSumToday` AS select `dlog`.`card_no` AS `card_no`,sum((case when (`dlog`.`department` = 46) then `dlog`.`total` else 0 end)) AS `totPayments`,min(`dlog`.`tdate`) AS `startdate` from `dlog` where (((to_days(now()) - to_days(`dlog`.`tdate`)) = 0) and (`dlog`.`department` = 46)) group by `dlog`.`card_no` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `stockSum_purch`
--

/*!50001 DROP TABLE IF EXISTS `stockSum_purch`*/;
/*!50001 DROP VIEW IF EXISTS `stockSum_purch`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `stockSum_purch` AS select `stockpurchases`.`card_no` AS `card_no`,sum(`stockpurchases`.`stockPurchase`) AS `totPayments`,min(`stockpurchases`.`tdate`) AS `startdate` from `stockpurchases` group by `stockpurchases`.`card_no` */;
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
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `suspendedtoday` AS select `suspended`.`datetime` AS `datetime`,`suspended`.`register_no` AS `register_no`,`suspended`.`emp_no` AS `emp_no`,`suspended`.`trans_no` AS `trans_no`,`suspended`.`upc` AS `upc`,`suspended`.`description` AS `description`,`suspended`.`trans_type` AS `trans_type`,`suspended`.`trans_subtype` AS `trans_subtype`,`suspended`.`trans_status` AS `trans_status`,`suspended`.`department` AS `department`,`suspended`.`quantity` AS `quantity`,`suspended`.`scale` AS `scale`,`suspended`.`cost` AS `cost`,`suspended`.`unitPrice` AS `unitPrice`,`suspended`.`total` AS `total`,`suspended`.`regPrice` AS `regPrice`,`suspended`.`tax` AS `tax`,`suspended`.`foodstamp` AS `foodstamp`,`suspended`.`discount` AS `discount`,`suspended`.`memDiscount` AS `memDiscount`,`suspended`.`discountable` AS `discountable`,`suspended`.`discounttype` AS `discounttype`,`suspended`.`voided` AS `voided`,`suspended`.`percentDiscount` AS `percentDiscount`,`suspended`.`ItemQtty` AS `ItemQtty`,`suspended`.`volDiscType` AS `volDiscType`,`suspended`.`volume` AS `volume`,`suspended`.`VolSpecial` AS `VolSpecial`,`suspended`.`mixMatch` AS `mixMatch`,`suspended`.`matched` AS `matched`,`suspended`.`memType` AS `memType`,`suspended`.`staff` AS `staff`,`suspended`.`numflag` AS `numflag`,`suspended`.`charflag` AS `charflag`,`suspended`.`card_no` AS `card_no`,`suspended`.`trans_id` AS `trans_id` from `suspended` where ((to_days(now()) - to_days(`suspended`.`datetime`)) = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `unpaid_ar_balances`
--

/*!50001 DROP TABLE IF EXISTS `unpaid_ar_balances`*/;
/*!50001 DROP VIEW IF EXISTS `unpaid_ar_balances`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `unpaid_ar_balances` AS select `ar_history`.`card_no` AS `card_no`,sum((case when (((to_days(`ar_history`.`tdate`) - to_days(now())) < -(20)) and (`ar_history`.`card_no` not between 5000 and 6099)) then (`ar_history`.`Charges` - `ar_history`.`Payments`) else 0 end)) AS `old_balance`,sum((case when ((to_days(`ar_history`.`tdate`) - to_days(now())) >= -(20)) then `ar_history`.`Payments` else 0 end)) AS `recent_payments` from `ar_history` where (`ar_history`.`card_no` <> 11) group by `ar_history`.`card_no` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `unpaid_ar_today`
--

/*!50001 DROP TABLE IF EXISTS `unpaid_ar_today`*/;
/*!50001 DROP VIEW IF EXISTS `unpaid_ar_today`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`coreserver`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `unpaid_ar_today` AS select `u`.`card_no` AS `card_no`,`u`.`old_balance` AS `old_balance`,(case when isnull(`m`.`card_no`) then `u`.`recent_payments` else (`m`.`payments` + `u`.`recent_payments`) end) AS `recent_payments`,(case when isnull(`m`.`card_no`) then 0 else 1 end) AS `mark` from (`unpaid_ar_balances` `u` left join `ar_history_today_sum` `m` on((`u`.`card_no` = `m`.`card_no`))) */;
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

-- Dump completed on 2013-12-25 18:47:31
