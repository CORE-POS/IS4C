-- MySQL dump 10.11
--
-- Host: localhost    Database: is4c_op
-- ------------------------------------------------------
-- Server version	5.0.67

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
-- Current Database: `is4c_op`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `is4c_op` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `is4c_op`;

DROP TABLE IF EXISTS `prodExtra`;
CREATE TABLE `prodExtra` (
	`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
	`distributor` varchar(100) default NULL,
	`manufacturer` varchar(100) default NULL,
	`cost` numeric(10,2) default NULL,
	`margin` numeric(10,2) default NULL,
	`variable_pricing` tinyint default NULL,
	`local` tinyint default NULL,
	`case_quantity` varchar(15) default NULL,
	`case_cost` numeric(10,2) default NULL,
	`case_info` varchar(100) default NULL,
	PRIMARY KEY (`upc`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `taxrates`;
CREATE TABLE `taxrates` (
	`id` int NOT NULL,
	`rate` float default NULL,
	`description` varchar(30) default NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `likeCodes`;
CREATE TABLE `likeCodes` (
	`likeCode` int NOT NULL,
	`likeCodeDesc` varchar(50) default NULL,
	PRIMARY KEY (`likeCode`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `upcLike`;
CREATE TABLE `upcLike` (
	`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
	`likeCode` int default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Table structure for table `UNFI`
--
DROP TABLE IF EXISTS `UNFI`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `UNFI` (
  `brand` varchar(30) default NULL,
  `sku` int(6) default NULL,
  `size` varchar(25) default NULL,
  `upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
  `units` int(3) default NULL,
  `cost` decimal(9,2) default NULL,
  `description` varchar(35) default NULL,
  `depart` varchar(15) default NULL,
  PRIMARY KEY  (`upc`),
  KEY `newindex` (`upc`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `batchList`
--

DROP TABLE IF EXISTS `batchList`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `batchList` (
  `listID` int(6) NOT NULL auto_increment,
  `upc` varchar(13) default NULL,
  `batchID` int(5) default NULL,
  `salePrice` decimal(10,2) default NULL,
  `active` tinyint(1) default '0',
  PRIMARY KEY  (`listID`)
) ENGINE=MyISAM AUTO_INCREMENT=10526 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `batches`
--

DROP TABLE IF EXISTS `batches`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `batches` (
  `batchID` int(5) NOT NULL auto_increment,
  `startDate` date NOT NULL,
  `endDate` date default NULL,
  `batchName` varchar(80) default NULL,
  `batchType` int(3) default NULL,
  `discountType` int(2) default NULL,
  `active` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`batchID`)
) ENGINE=MyISAM AUTO_INCREMENT=330 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `batchType`;
CREATE TABLE `batchType` (
	`batchTypeID` int NOT NULL,
	`typeDesc` varchar(50) default NULL,
	`discType` int default NULL,
	PRIMARY KEY (`batchTypeID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `batchOwner`;
CREATE TABLE `batchOwner` (
	`batchID` int NOT NULL,
	`owner` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `batchBarcodes`;
CREATE TABLE `batchBarcodes` (
	`upc` varchar(13) default NULL,
	`description` varchar(30) default NULL,
	`normal_price` decimal(10,2) default NULL,
	`brand` varchar(50) default NULL,
	`sku` varchar(10) default NULL,
	`size` varchar(30) default NULL,
	`units` varchar(15) default NULL,
	`vendor` varchar(50) default NULL,
	`batchID` int default NULL,
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `chargecode`
--

DROP TABLE IF EXISTS `chargecode`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `chargecode` (
  `staffID` varchar(4) default NULL,
  `chargecode` varchar(6) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `couponcodes`
--

DROP TABLE IF EXISTS `couponcodes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `couponcodes` (
  `Code` varchar(4) default NULL,
  `Qty` int(11) default NULL,
  `Value` double default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cust_pr_2008`
--

DROP TABLE IF EXISTS `cust_pr_2008`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `cust_pr_2008` (
  `card_no` smallint(5) default NULL,
  `points` double default NULL,
  `alloc` double default NULL,
  `paid` double default NULL,
  `ret` double default NULL,
  KEY `card_no` (`card_no`),
  KEY `paid` (`paid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cust_pr_2009`
--

DROP TABLE IF EXISTS `cust_pr_2009`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `cust_pr_2009` (
  `card_no` smallint(5) default NULL,
  `points` double default NULL,
  `alloc` double default NULL,
  `paid` double default NULL,
  `ret` double default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `custdata`
--

DROP TABLE IF EXISTS `custdata`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `custdata` (
  `CardNo` int(8) NOT NULL,
  `personNum` tinyint(4) NOT NULL default '1',
  `LastName` varchar(30) default NULL,
  `FirstName` varchar(30) default NULL,
  `CashBack` double NOT NULL default '60',
  `Balance` double NOT NULL default '0',
  `Discount` smallint(6) default NULL,
  `MemDiscountLimit` double NOT NULL default '0',
  `ChargeOk` tinyint(4) NOT NULL default '1',
  `WriteChecks` tinyint(4) NOT NULL default '1',
  `StoreCoupons` tinyint(4) NOT NULL default '1',
  `Type` varchar(10) NOT NULL default 'pc',
  `memType` tinyint(4) default NULL,
  `staff` tinyint(4) NOT NULL default '0',
  `SSI` tinyint(4) NOT NULL default '0',
  `Purchases` double NOT NULL default '0',
  `NumberOfChecks` smallint(6) NOT NULL default '0',
  `memCoupons` int(11) NOT NULL default '1',
  `blueLine` varchar(50) default NULL,
  `Shown` tinyint(4) NOT NULL default '1',
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`id`),
  KEY `CardNo` (`CardNo`),
  KEY `staff` (`staff`)
) ENGINE=MyISAM AUTO_INCREMENT=7126 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `custdataBAK`
--

DROP TABLE IF EXISTS `custdataBAK`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `custdataBAK` (
  `CardNo` varchar(25) default NULL,
  `personNum` tinyint(4) NOT NULL default '1',
  `LastName` varchar(30) default NULL,
  `FirstName` varchar(30) default NULL,
  `CashBack` double NOT NULL default '60',
  `Balance` double NOT NULL default '0',
  `Discount` smallint(6) default NULL,
  `MemDiscountLimit` double NOT NULL default '0',
  `ChargeOk` tinyint(4) NOT NULL default '1',
  `WriteChecks` tinyint(4) NOT NULL default '1',
  `StoreCoupons` tinyint(4) NOT NULL default '1',
  `Type` varchar(10) NOT NULL default 'pc',
  `memType` tinyint(4) default NULL,
  `staff` tinyint(4) NOT NULL default '0',
  `SSI` tinyint(4) NOT NULL default '0',
  `Purchases` double NOT NULL default '0',
  `NumberOfChecks` smallint(6) NOT NULL default '0',
  `memCoupons` int(11) NOT NULL default '1',
  `blueLine` varchar(50) default NULL,
  `Shown` tinyint(4) NOT NULL default '1',
  `id` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `departments` (
  `dept_no` smallint(6) default NULL,
  `dept_name` varchar(30) default NULL,
  `dept_tax` tinyint(4) default NULL,
  `dept_fs` tinyint(4) default NULL,
  `dept_limit` double default NULL,
  `dept_minimum` double default NULL,
  `dept_discount` tinyint(4) default NULL,
  `modified` datetime default NULL,
  `modifiedby` int(11) default NULL,
  KEY `dept_no` (`dept_no`),
  KEY `dept_name` (`dept_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `employees` (
  `emp_no` smallint(6) default NULL,
  `CashierPassword` int(11) default NULL,
  `AdminPassword` int(11) default NULL,
  `FirstName` varchar(255) default NULL,
  `LastName` varchar(255) default NULL,
  `JobTitle` varchar(255) default NULL,
  `EmpActive` tinyint(4) default NULL,
  `frontendsecurity` smallint(6) default NULL,
  `backendsecurity` smallint(6) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `globalvalues`
--

DROP TABLE IF EXISTS `globalvalues`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `globalvalues` (
  `CashierNo` int(11) default NULL,
  `Cashier` varchar(30) default NULL,
  `LoggedIn` tinyint(4) default NULL,
  `TransNo` int(11) default NULL,
  `TTLFlag` tinyint(4) default NULL,
  `FntlFlag` tinyint(4) default NULL,
  `TaxExempt` tinyint(4) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `inx`
--

DROP TABLE IF EXISTS `inx`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `inx` (
  `userID` int(4) default NULL,
  `command` int(2) default NULL,
  `proce` varchar(50) default NULL,
  `TID` int(6) default NULL,
  `CC` varchar(50) default NULL,
  `expdate` int(4) default NULL,
  `manaul` int(2) default NULL,
  `tracktwo` varchar(150) default NULL,
  `transno` int(8) default NULL,
  `present` int(2) default NULL,
  `amount` decimal(10,2) default NULL,
  `name` varchar(80) default NULL,
  `transdate` varchar(50) default NULL,
  `trans_id` int(3) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `legacy_products`
--

DROP TABLE IF EXISTS `legacy_products`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `legacy_products` (
  `upc` bigint(13) unsigned zerofill default NULL,
  `description` varchar(30) default NULL,
  `normal_price` double default NULL,
  `pricemethod` smallint(6) default NULL,
  `groupprice` double default NULL,
  `quantity` smallint(6) default NULL,
  `special_price` double default NULL,
  `specialpricemethod` smallint(6) default NULL,
  `specialgroupprice` double default NULL,
  `specialquantity` smallint(6) default NULL,
  `start_date` datetime default NULL,
  `end_date` datetime default NULL,
  `department` smallint(6) default NULL,
  `size` varchar(9) default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `scale` tinyint(4) default NULL,
  `mixmatchcode` varchar(13) default NULL,
  `modified` datetime default NULL,
  `advertised` tinyint(4) default NULL,
  `tareweight` double default NULL,
  `discount` smallint(6) default NULL,
  `discounttype` tinyint(4) default NULL,
  `unitofmeasure` varchar(15) default NULL,
  `wicable` smallint(6) default NULL,
  `deposit` double default '0',
  `qttyEnforced` tinyint(4) default NULL,
  `inUse` tinyint(4) default NULL,
  `subdept` smallint(4) default NULL,
  `id` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `likecodes`
--

DROP TABLE IF EXISTS `likecodes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `likecodes` (
  `likeCode` int(4) default NULL,
  `likeCodeDesc` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `members`
--

DROP TABLE IF EXISTS `members`;
/*!50001 DROP VIEW IF EXISTS `members`*/;
/*!50001 CREATE TABLE `members` (
  `id` int(11),
  `card_no` int(8),
  `first_name` varchar(30),
  `last_name` varchar(30),
  `cash_back` double,
  `balance` double,
  `discount` smallint(6),
  `mem_discount_limit` double,
  `charge_ok` tinyint(4),
  `write_checks` tinyint(4),
  `store_coupons` tinyint(4),
  `mem_type` varchar(10),
  `mem_status` tinyint(4),
  `staff` tinyint(4),
  `purchases` double,
  `number_of_checks` smallint(6),
  `blue_line` varchar(50),
  `shown` tinyint(4)
) */;

--
-- Table structure for table `meminfo`
--

DROP TABLE IF EXISTS `meminfo`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `meminfo` (
  `card_no` smallint(5) default NULL,
  `last_name` varchar(30) default NULL,
  `first_name` varchar(30) default NULL,
  `othlast_name` varchar(30) default NULL,
  `othfirst_name` varchar(30) default NULL,
  `street` varchar(30) default NULL,
  `city` varchar(20) default NULL,
  `state` varchar(2) default NULL,
  `zip` varchar(10) default NULL,
  `phone` varchar(30) default NULL,
  `email_1` varchar(30) default NULL,
  `email_2` varchar(30) default NULL,
  `ads_OK` tinyint(1) default '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `memtype`
--

DROP TABLE IF EXISTS `memtype`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `memtype` (
  `memtype` tinyint(2) default NULL,
  `memDesc` varchar(20) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `messages` (
  `id` varchar(20) default NULL,
  `message` varchar(60) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `newMembers`
--

DROP TABLE IF EXISTS `newMembers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `newMembers` (
  `CardNo` varchar(25) default NULL,
  `personNum` tinyint(4) NOT NULL default '1',
  `LastName` varchar(30) default NULL,
  `FirstName` varchar(30) default NULL,
  `CashBack` double NOT NULL default '60',
  `Balance` double NOT NULL default '0',
  `Discount` smallint(6) default NULL,
  `MemDiscountLimit` double NOT NULL default '0',
  `ChargeOk` tinyint(4) NOT NULL default '1',
  `WriteChecks` tinyint(4) NOT NULL default '1',
  `StoreCoupons` tinyint(4) NOT NULL default '1',
  `Type` varchar(10) NOT NULL default 'pc',
  `memType` tinyint(4) default NULL,
  `staff` tinyint(4) NOT NULL default '0',
  `SSI` tinyint(4) NOT NULL default '0',
  `Purchases` double NOT NULL default '0',
  `NumberOfChecks` smallint(6) NOT NULL default '0',
  `memCoupons` int(11) NOT NULL default '1',
  `blueLine` varchar(50) default NULL,
  `Shown` tinyint(4) NOT NULL default '1',
  `id` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `prodUpdate`
--

DROP TABLE IF EXISTS `prodUpdate`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `prodUpdate` (
  `upc` varchar(13) default NULL,
  `description` varchar(50) default NULL,
  `price` decimal(10,2) default NULL,
  `dept` int(6) default NULL,
  `tax` bit(1) default NULL,
  `fs` bit(1) default NULL,
  `scale` bit(1) default NULL,
  `likeCode` int(6) default NULL,
  `modified` date default NULL,
  `user` int(8) default NULL,
  `forceQty` bit(1) default NULL,
  `noDisc` bit(1) default NULL,
  `inUse` bit(1) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `prod_subdepts`
--

DROP TABLE IF EXISTS `prod_subdepts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `prod_subdepts` (
  `upc` bigint(13) unsigned zerofill default NULL,
  `description` varchar(30) default NULL,
  `department` tinyint(2) default NULL,
  `subdept` smallint(4) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `product_details`
--

DROP TABLE IF EXISTS `product_details`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `product_details` (
  `brand` varchar(30) default NULL,
  `order_no` int(6) default NULL,
  `pack_size` varchar(25) default NULL,
  `upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
  `units` int(3) default NULL,
  `cost` decimal(9,2) default NULL,
  `description` varchar(35) default NULL,
  `depart` varchar(15) default NULL,
  `distributor` varchar(30) NOT NULL,
  `product` varchar(255) default NULL,
  KEY `upc` (`upc`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `products` (
  `upc` bigint(13) unsigned zerofill default NULL,
  `description` varchar(30) default NULL,
  `normal_price` double default NULL,
  `pricemethod` smallint(6) default NULL,
  `groupprice` double default NULL,
  `quantity` smallint(6) default NULL,
  `special_price` double default NULL,
  `specialpricemethod` smallint(6) default NULL,
  `specialgroupprice` double default NULL,
  `specialquantity` smallint(6) default NULL,
  `start_date` datetime default NULL,
  `end_date` datetime default NULL,
  `department` smallint(6) default NULL,
  `size` varchar(9) default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `scale` tinyint(4) default NULL,
  `mixmatchcode` varchar(13) default NULL,
  `modified` datetime default NULL,
  `advertised` tinyint(4) default NULL,
  `tareweight` double default NULL,
  `discount` smallint(6) default NULL,
  `discounttype` tinyint(4) default NULL,
  `unitofmeasure` varchar(15) default NULL,
  `wicable` smallint(6) default NULL,
  `deposit` double default '0',
  `qttyEnforced` tinyint(4) default NULL,
  `inUse` tinyint(4) default NULL,
  `subdept` smallint(4) default NULL,
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`id`),
  KEY `upc` (`upc`),
  KEY `description` (`description`),
  KEY `normal_price` (`normal_price`),
  KEY `subdept` (`subdept`),
  KEY `department` (`department`)
) ENGINE=MyISAM AUTO_INCREMENT=8986 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `promomsgs`
--

DROP TABLE IF EXISTS `promomsgs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `promomsgs` (
  `startDate` datetime default NULL,
  `endDate` datetime default NULL,
  `promoMsg` varchar(50) default NULL,
  `sequence` tinyint(4) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `staff` (
  `staff_no` tinyint(2) default NULL,
  `staff_desc` varchar(20) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `subdeptTotals`
--

DROP TABLE IF EXISTS `subdeptTotals`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `subdeptTotals` (
  `date` date NOT NULL,
  `dept_no` tinyint(2) NOT NULL,
  `dept_name` varchar(30) NOT NULL,
  `subdept_no` smallint(4) NOT NULL,
  `subdept_name` varchar(30) NOT NULL,
  `item_count` int(10) NOT NULL,
  `item_total` int(10) NOT NULL,
  `id` int(10) NOT NULL auto_increment,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `subdeptindex`
--

DROP TABLE IF EXISTS `subdeptindex`;
/*!50001 DROP VIEW IF EXISTS `subdeptindex`*/;
/*!50001 CREATE TABLE `subdeptindex` (
  `upc` bigint(13) unsigned zerofill,
  `department` smallint(6),
  `dept_name` varchar(30),
  `subdept` smallint(4),
  `subdept_name` varchar(30)
) */;

--
-- Table structure for table `subdepts`
--

DROP TABLE IF EXISTS `subdepts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `subdepts` (
  `subdept_no` smallint(4) NOT NULL,
  `subdept_name` varchar(30) default NULL,
  `dept_ID` tinyint(2) default NULL,
  KEY `subdept_no` (`subdept_no`),
  KEY `subdept_name` (`subdept_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `tenders`
--

DROP TABLE IF EXISTS `tenders`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tenders` (
  `TenderID` smallint(6) default NULL,
  `TenderCode` varchar(255) default NULL,
  `TenderName` varchar(255) default NULL,
  `TenderType` varchar(255) default NULL,
  `ChangeMessage` varchar(255) default NULL,
  `MinAmount` double default NULL,
  `MaxAmount` double default NULL,
  `MaxRefund` double default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `upclike`
--

DROP TABLE IF EXISTS `upclike`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `upclike` (
  `upc` varchar(13) default NULL,
  `likeCode` int(4) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `volunteerdiscounts`
--

DROP TABLE IF EXISTS `volunteerdiscounts`;
/*!50001 DROP VIEW IF EXISTS `volunteerdiscounts`*/;
/*!50001 CREATE TABLE `volunteerdiscounts` (
  `CardNo` int(8),
  `hours` tinyint(4),
  `total` int(6),
  `id` int(11)
) */;

--
-- Current Database: `is4c_log`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `is4c_log` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `is4c_log`;

--
-- Table structure for table `SPINS_2008`
--

DROP TABLE IF EXISTS `SPINS_2008`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `SPINS_2008` (
  `period` tinyint(2) default NULL,
  `week_tag` tinyint(2) default NULL,
  `start_date` date default NULL,
  `end_date` date default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `SPINS_2009`
--

DROP TABLE IF EXISTS `SPINS_2009`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `SPINS_2009` (
  `period` tinyint(2) default NULL,
  `week_tag` tinyint(2) default NULL,
  `start_date` date default NULL,
  `end_date` date default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `activitylog`
--

DROP TABLE IF EXISTS `activitylog`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `activitylog` (
  `datetime` datetime default NULL,
  `LaneNo` tinyint(4) default NULL,
  `CashierNo` tinyint(4) default NULL,
  `TransNo` int(11) default NULL,
  `Activity` tinyint(4) default NULL,
  `Interval` double default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `alog`
--

DROP TABLE IF EXISTS `alog`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `alog` (
  `datetime` datetime default NULL,
  `LaneNo` tinyint(4) default NULL,
  `CashierNo` tinyint(4) default NULL,
  `TransNo` int(11) default NULL,
  `Activity` tinyint(4) default NULL,
  `Interval` double default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_2006`
--

DROP TABLE IF EXISTS `dlog_2006`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dlog_2006` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `card_no` varchar(255) default NULL,
  `memType` tinyint(2) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_2007`
--

DROP TABLE IF EXISTS `dlog_2007`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dlog_2007` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL,
  KEY `trans_type` (`trans_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_2008`
--

DROP TABLE IF EXISTS `dlog_2008`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dlog_2008` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL,
  KEY `datetime` (`datetime`),
  KEY `upc` (`upc`),
  KEY `department` (`department`),
  KEY `total` (`total`),
  KEY `staff` (`staff`),
  KEY `trans_type` (`trans_type`),
  KEY `trans_subtype` (`trans_subtype`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_2008_pr`
--

DROP TABLE IF EXISTS `dlog_2008_pr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dlog_2008_pr` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_2009`
--

DROP TABLE IF EXISTS `dlog_2009`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dlog_2009` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL,
  KEY `datetime` (`datetime`),
  KEY `total` (`total`),
  KEY `unitPrice` (`unitPrice`),
  KEY `quantity` (`quantity`),
  KEY `department` (`department`),
  KEY `upc` (`upc`),
  KEY `trans_status` (`trans_status`),
  KEY `emp_no` (`emp_no`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `dlog_tmp`
--

DROP TABLE IF EXISTS `dlog_tmp`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dlog_tmp` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `dtrans_today`
--

DROP TABLE IF EXISTS `dtrans_today`;
/*!50001 DROP VIEW IF EXISTS `dtrans_today`*/;
/*!50001 CREATE TABLE `dtrans_today` (
  `datetime` datetime,
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `upc` varchar(255),
  `description` varchar(255),
  `trans_type` varchar(255),
  `trans_subtype` varchar(255),
  `trans_status` varchar(255),
  `department` smallint(6),
  `quantity` double,
  `Scale` tinyint(4),
  `unitPrice` double,
  `total` double,
  `regPrice` double,
  `tax` smallint(6),
  `foodstamp` tinyint(4),
  `discount` double,
  `memDiscount` double,
  `discountable` tinyint(4),
  `discounttype` tinyint(4),
  `voided` tinyint(4),
  `percentDiscount` tinyint(4),
  `ItemQtty` double,
  `volDiscType` tinyint(4),
  `volume` tinyint(4),
  `VolSpecial` double,
  `mixMatch` smallint(6),
  `matched` smallint(6),
  `memType` tinyint(2),
  `staff` tinyint(4),
  `card_no` varchar(255),
  `trans_id` int(11)
) */;

--
-- Table structure for table `dtransactions`
--

DROP TABLE IF EXISTS `dtransactions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dtransactions` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `endofshift`
--

DROP TABLE IF EXISTS `endofshift`;
/*!50001 DROP VIEW IF EXISTS `endofshift`*/;
/*!50001 CREATE TABLE `endofshift` (
  `datetime` datetime,
  `emp_no` smallint(6),
  `register_no` smallint(6),
  `trans_no` int(11)
) */;

--
-- Table structure for table `localtemptrans`
--

DROP TABLE IF EXISTS `localtemptrans`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `localtemptrans` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`trans_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `localtrans`
--

DROP TABLE IF EXISTS `localtrans`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `localtrans` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `localtranstoday`
--

DROP TABLE IF EXISTS `localtranstoday`;
/*!50001 DROP VIEW IF EXISTS `localtranstoday`*/;
/*!50001 CREATE TABLE `localtranstoday` (
  `datetime` datetime,
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `upc` varchar(255),
  `description` varchar(255),
  `trans_type` varchar(255),
  `trans_subtype` varchar(255),
  `trans_status` varchar(255),
  `department` smallint(6),
  `quantity` double,
  `Scale` tinyint(4),
  `unitPrice` double,
  `total` double,
  `regPrice` double,
  `tax` smallint(6),
  `foodstamp` tinyint(4),
  `discount` double,
  `memDiscount` double,
  `discountable` tinyint(4),
  `discounttype` tinyint(4),
  `voided` tinyint(4),
  `percentDiscount` tinyint(4),
  `ItemQtty` double,
  `volDiscType` tinyint(4),
  `volume` tinyint(4),
  `VolSpecial` double,
  `mixMatch` smallint(6),
  `matched` smallint(6),
  `card_no` varchar(255),
  `trans_id` int(11)
) */;

--
-- Temporary table structure for view `memchargebalance`
--

DROP TABLE IF EXISTS `memchargebalance`;
/*!50001 DROP VIEW IF EXISTS `memchargebalance`*/;
/*!50001 CREATE TABLE `memchargebalance` (
  `cardNo` int(8),
  `availBal` double,
  `balance` double
) */;

--
-- Temporary table structure for view `memchargetotals`
--

DROP TABLE IF EXISTS `memchargetotals`;
/*!50001 DROP VIEW IF EXISTS `memchargetotals`*/;
/*!50001 CREATE TABLE `memchargetotals` (
  `card_no` varchar(255),
  `chargeTotal` double
) */;

--
-- Temporary table structure for view `memdiscountadd`
--

DROP TABLE IF EXISTS `memdiscountadd`;
/*!50001 DROP VIEW IF EXISTS `memdiscountadd`*/;
/*!50001 CREATE TABLE `memdiscountadd` (
  `datetime` datetime,
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `upc` varchar(255),
  `description` varchar(255),
  `trans_type` varchar(1),
  `trans_subtype` char(0),
  `trans_status` varchar(1),
  `department` smallint(6),
  `quantity` int(1),
  `scale` int(1),
  `unitPrice` double,
  `total` double,
  `regPrice` double,
  `tax` smallint(6),
  `foodstamp` tinyint(4),
  `discount` int(1),
  `memDiscount` double,
  `discountable` int(1),
  `discounttype` int(2),
  `voided` int(1),
  `percentDiscount` int(1),
  `ItemQtty` int(1),
  `volDiscType` int(1),
  `volume` int(1),
  `VolSpecial` int(1),
  `mixMatch` int(1),
  `matched` int(1),
  `card_no` varchar(255)
) */;

--
-- Temporary table structure for view `memdiscountremove`
--

DROP TABLE IF EXISTS `memdiscountremove`;
/*!50001 DROP VIEW IF EXISTS `memdiscountremove`*/;
/*!50001 CREATE TABLE `memdiscountremove` (
  `datetime` datetime,
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `upc` varchar(255),
  `description` varchar(255),
  `trans_type` varchar(1),
  `trans_subtype` char(0),
  `trans_status` varchar(1),
  `department` smallint(6),
  `quantity` int(1),
  `scale` int(1),
  `unitPrice` double,
  `total` double,
  `regPrice` double,
  `tax` smallint(6),
  `foodstamp` tinyint(4),
  `discount` int(1),
  `memDiscount` double,
  `discountable` int(1),
  `discounttype` int(2),
  `voided` int(1),
  `percentDiscount` int(1),
  `ItemQtty` int(1),
  `volDiscType` int(1),
  `volume` int(1),
  `VolSpecial` int(1),
  `mixMatch` int(1),
  `matched` int(1),
  `card_no` varchar(255)
) */;

--
-- Temporary table structure for view `pr_redeemed`
--

DROP TABLE IF EXISTS `pr_redeemed`;
/*!50001 DROP VIEW IF EXISTS `pr_redeemed`*/;
/*!50001 CREATE TABLE `pr_redeemed` (
  `datetime` datetime,
  `emp_no` smallint(6),
  `register_no` smallint(6),
  `trans_no` int(11),
  `card_no` varchar(255),
  `total` double
) */;

--
-- Temporary table structure for view `rp_list`
--

DROP TABLE IF EXISTS `rp_list`;
/*!50001 DROP VIEW IF EXISTS `rp_list`*/;
/*!50001 CREATE TABLE `rp_list` (
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `total` double
) */;

--
-- Temporary table structure for view `rp_ltt_receipt`
--

DROP TABLE IF EXISTS `rp_ltt_receipt`;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_receipt`*/;
/*!50001 CREATE TABLE `rp_ltt_receipt` (
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `description` varchar(255),
  `comment` varbinary(53),
  `total` double,
  `Status` varchar(2),
  `trans_type` varchar(255),
  `unitPrice` double,
  `voided` tinyint(4),
  `trans_id` int(11)
) */;

--
-- Temporary table structure for view `rp_receipt`
--

DROP TABLE IF EXISTS `rp_receipt`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt`*/;
/*!50001 CREATE TABLE `rp_receipt` (
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `linetoprint` varbinary(255),
  `trans_id` int(11)
) */;

--
-- Temporary table structure for view `rp_receipt_header`
--

DROP TABLE IF EXISTS `rp_receipt_header`;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_header`*/;
/*!50001 CREATE TABLE `rp_receipt_header` (
  `dateTimeStamp` datetime,
  `memberID` varchar(255),
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `discountTTL` double,
  `memSpecial` double,
  `couponTotal` double,
  `memCoupon` double,
  `chargeTotal` double,
  `transDiscount` double,
  `tenderTotal` double
) */;

--
-- Temporary table structure for view `screendisplay`
--

DROP TABLE IF EXISTS `screendisplay`;
/*!50001 DROP VIEW IF EXISTS `screendisplay`*/;
/*!50001 CREATE TABLE `screendisplay` (
  `description` varchar(255),
  `comment` varbinary(255),
  `total` varbinary(22),
  `status` varchar(2),
  `lineColor` varchar(6),
  `discounttype` tinyint(4),
  `trans_type` varchar(255),
  `trans_status` varchar(255),
  `voided` tinyint(4),
  `trans_id` int(11)
) */;

--
-- Table structure for table `suspended`
--

DROP TABLE IF EXISTS `suspended`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `suspended` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `suspendedlist`
--

DROP TABLE IF EXISTS `suspendedlist`;
/*!50001 DROP VIEW IF EXISTS `suspendedlist`*/;
/*!50001 CREATE TABLE `suspendedlist` (
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `total` double
) */;

--
-- Temporary table structure for view `suspendedtoday`
--

DROP TABLE IF EXISTS `suspendedtoday`;
/*!50001 DROP VIEW IF EXISTS `suspendedtoday`*/;
/*!50001 CREATE TABLE `suspendedtoday` (
  `datetime` datetime,
  `register_no` smallint(6),
  `emp_no` smallint(6),
  `trans_no` int(11),
  `upc` varchar(255),
  `description` varchar(255),
  `trans_type` varchar(255),
  `trans_subtype` varchar(255),
  `trans_status` varchar(255),
  `department` smallint(6),
  `quantity` double,
  `scale` tinyint(4),
  `unitPrice` double,
  `total` double,
  `regPrice` double,
  `tax` smallint(6),
  `foodstamp` tinyint(4),
  `discount` double,
  `memDiscount` double,
  `discountable` tinyint(4),
  `discounttype` tinyint(4),
  `voided` tinyint(4),
  `percentDiscount` tinyint(4),
  `ItemQtty` double,
  `volDiscType` tinyint(4),
  `volume` tinyint(4),
  `VolSpecial` double,
  `mixMatch` smallint(6),
  `matched` smallint(6),
  `card_no` varchar(255),
  `trans_id` int(11)
) */;

--
-- Table structure for table `testdata`
--

DROP TABLE IF EXISTS `testdata`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `testdata` (
  `datetime` datetime default NULL,
  `register_no` smallint(6) default NULL,
  `emp_no` smallint(6) default NULL,
  `trans_no` int(11) default NULL,
  `upc` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `trans_type` varchar(255) default NULL,
  `trans_subtype` varchar(255) default NULL,
  `trans_status` varchar(255) default NULL,
  `department` smallint(6) default NULL,
  `quantity` double default NULL,
  `Scale` tinyint(4) default NULL,
  `unitPrice` double default NULL,
  `total` double default NULL,
  `regPrice` double default NULL,
  `tax` smallint(6) default NULL,
  `foodstamp` tinyint(4) default NULL,
  `discount` double default NULL,
  `memDiscount` double default NULL,
  `discountable` tinyint(4) default NULL,
  `discounttype` tinyint(4) default NULL,
  `voided` tinyint(4) default NULL,
  `percentDiscount` tinyint(4) default NULL,
  `ItemQtty` double default NULL,
  `volDiscType` tinyint(4) default NULL,
  `volume` tinyint(4) default NULL,
  `VolSpecial` double default NULL,
  `mixMatch` smallint(6) default NULL,
  `matched` smallint(6) default NULL,
  `memType` tinyint(2) default NULL,
  `staff` tinyint(4) default NULL,
  `card_no` varchar(255) default NULL,
  `trans_id` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Current Database: `is4c_op`
--

USE `is4c_op`;

--
-- Final view structure for view `members`
--

/*!50001 DROP TABLE `members`*/;
/*!50001 DROP VIEW IF EXISTS `members`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `members` AS select `custdata`.`id` AS `id`,`custdata`.`CardNo` AS `card_no`,`custdata`.`FirstName` AS `first_name`,`custdata`.`LastName` AS `last_name`,`custdata`.`CashBack` AS `cash_back`,`custdata`.`Balance` AS `balance`,`custdata`.`Discount` AS `discount`,`custdata`.`MemDiscountLimit` AS `mem_discount_limit`,`custdata`.`ChargeOk` AS `charge_ok`,`custdata`.`WriteChecks` AS `write_checks`,`custdata`.`StoreCoupons` AS `store_coupons`,`custdata`.`Type` AS `mem_type`,`custdata`.`memType` AS `mem_status`,`custdata`.`staff` AS `staff`,`custdata`.`Purchases` AS `purchases`,`custdata`.`NumberOfChecks` AS `number_of_checks`,`custdata`.`blueLine` AS `blue_line`,`custdata`.`Shown` AS `shown` from `custdata` */;

--
-- Final view structure for view `subdeptindex`
--

/*!50001 DROP TABLE `subdeptindex`*/;
/*!50001 DROP VIEW IF EXISTS `subdeptindex`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `subdeptindex` AS select `p`.`upc` AS `upc`,`p`.`department` AS `department`,`d`.`dept_name` AS `dept_name`,`p`.`subdept` AS `subdept`,`s`.`subdept_name` AS `subdept_name` from ((`products` `p` join `departments` `d`) join `subdepts` `s`) where ((`p`.`department` = `d`.`dept_no`) and (`p`.`subdept` = `s`.`subdept_no`)) group by `p`.`upc` */;

--
-- Final view structure for view `volunteerdiscounts`
--

/*!50001 DROP TABLE `volunteerdiscounts`*/;
/*!50001 DROP VIEW IF EXISTS `volunteerdiscounts`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `volunteerdiscounts` AS select `custdata`.`CardNo` AS `CardNo`,`custdata`.`SSI` AS `hours`,(`custdata`.`SSI` * 20) AS `total`,`custdata`.`id` AS `id` from `custdata` where (`custdata`.`staff` = 3) */;

--
-- Current Database: `is4c_log`
--

USE `is4c_log`;

--
-- Final view structure for view `dtrans_today`
--

/*!50001 DROP TABLE `dtrans_today`*/;
/*!50001 DROP VIEW IF EXISTS `dtrans_today`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `dtrans_today` AS select sql_no_cache `dtransactions`.`datetime` AS `datetime`,`dtransactions`.`register_no` AS `register_no`,`dtransactions`.`emp_no` AS `emp_no`,`dtransactions`.`trans_no` AS `trans_no`,`dtransactions`.`upc` AS `upc`,`dtransactions`.`description` AS `description`,`dtransactions`.`trans_type` AS `trans_type`,`dtransactions`.`trans_subtype` AS `trans_subtype`,`dtransactions`.`trans_status` AS `trans_status`,`dtransactions`.`department` AS `department`,`dtransactions`.`quantity` AS `quantity`,`dtransactions`.`Scale` AS `Scale`,`dtransactions`.`unitPrice` AS `unitPrice`,`dtransactions`.`total` AS `total`,`dtransactions`.`regPrice` AS `regPrice`,`dtransactions`.`tax` AS `tax`,`dtransactions`.`foodstamp` AS `foodstamp`,`dtransactions`.`discount` AS `discount`,`dtransactions`.`memDiscount` AS `memDiscount`,`dtransactions`.`discountable` AS `discountable`,`dtransactions`.`discounttype` AS `discounttype`,`dtransactions`.`voided` AS `voided`,`dtransactions`.`percentDiscount` AS `percentDiscount`,`dtransactions`.`ItemQtty` AS `ItemQtty`,`dtransactions`.`volDiscType` AS `volDiscType`,`dtransactions`.`volume` AS `volume`,`dtransactions`.`VolSpecial` AS `VolSpecial`,`dtransactions`.`mixMatch` AS `mixMatch`,`dtransactions`.`matched` AS `matched`,`dtransactions`.`memType` AS `memType`,`dtransactions`.`staff` AS `staff`,`dtransactions`.`card_no` AS `card_no`,`dtransactions`.`trans_id` AS `trans_id` from `dtransactions` where ((cast(`dtransactions`.`datetime` as date) = curdate()) and (`dtransactions`.`trans_status` <> _latin1'X') and (`dtransactions`.`emp_no` <> 9999)) */;

--
-- Final view structure for view `endofshift`
--

/*!50001 DROP TABLE `endofshift`*/;
/*!50001 DROP VIEW IF EXISTS `endofshift`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `endofshift` AS select `dlog_2008`.`datetime` AS `datetime`,`dlog_2008`.`emp_no` AS `emp_no`,`dlog_2008`.`register_no` AS `register_no`,`dlog_2008`.`trans_no` AS `trans_no` from `dlog_2008` where ((`dlog_2008`.`trans_type` = _latin1'S') and (`dlog_2008`.`emp_no` <> 9999)) */;

--
-- Final view structure for view `localtranstoday`
--

/*!50001 DROP TABLE `localtranstoday`*/;
/*!50001 DROP VIEW IF EXISTS `localtranstoday`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `localtranstoday` AS select sql_no_cache `localtrans`.`datetime` AS `datetime`,`localtrans`.`register_no` AS `register_no`,`localtrans`.`emp_no` AS `emp_no`,`localtrans`.`trans_no` AS `trans_no`,`localtrans`.`upc` AS `upc`,`localtrans`.`description` AS `description`,`localtrans`.`trans_type` AS `trans_type`,`localtrans`.`trans_subtype` AS `trans_subtype`,`localtrans`.`trans_status` AS `trans_status`,`localtrans`.`department` AS `department`,`localtrans`.`quantity` AS `quantity`,`localtrans`.`Scale` AS `Scale`,`localtrans`.`unitPrice` AS `unitPrice`,`localtrans`.`total` AS `total`,`localtrans`.`regPrice` AS `regPrice`,`localtrans`.`tax` AS `tax`,`localtrans`.`foodstamp` AS `foodstamp`,`localtrans`.`discount` AS `discount`,`localtrans`.`memDiscount` AS `memDiscount`,`localtrans`.`discountable` AS `discountable`,`localtrans`.`discounttype` AS `discounttype`,`localtrans`.`voided` AS `voided`,`localtrans`.`percentDiscount` AS `percentDiscount`,`localtrans`.`ItemQtty` AS `ItemQtty`,`localtrans`.`volDiscType` AS `volDiscType`,`localtrans`.`volume` AS `volume`,`localtrans`.`VolSpecial` AS `VolSpecial`,`localtrans`.`mixMatch` AS `mixMatch`,`localtrans`.`matched` AS `matched`,`localtrans`.`card_no` AS `card_no`,`localtrans`.`trans_id` AS `trans_id` from `localtrans` where ((to_days(`localtrans`.`datetime`) - to_days(now())) = 0) */;

--
-- Final view structure for view `memchargebalance`
--

/*!50001 DROP TABLE `memchargebalance`*/;
/*!50001 DROP VIEW IF EXISTS `memchargebalance`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `is4c_log`.`memchargebalance` AS select `c`.`CardNo` AS `cardNo`,(case when isnull(`m`.`card_no`) then (`c`.`MemDiscountLimit` - `c`.`Balance`) else (`c`.`MemDiscountLimit` - (`c`.`Balance` - `m`.`chargeTotal`)) end) AS `availBal`,(case when isnull(`m`.`card_no`) then `c`.`Balance` else (`c`.`Balance` - `m`.`chargeTotal`) end) AS `balance` from (`is4c_op`.`custdata` `c` left join `is4c_log`.`memchargetotals` `m` on((`c`.`CardNo` = `m`.`card_no`))) */;

--
-- Final view structure for view `memchargetotals`
--

/*!50001 DROP TABLE `memchargetotals`*/;
/*!50001 DROP VIEW IF EXISTS `memchargetotals`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `memchargetotals` AS select `dtransactions`.`card_no` AS `card_no`,sum(`dtransactions`.`total`) AS `chargeTotal` from `dtransactions` where (`dtransactions`.`trans_subtype` = _latin1'MI') group by `dtransactions`.`card_no` */;

--
-- Final view structure for view `memdiscountadd`
--

/*!50001 DROP TABLE `memdiscountadd`*/;
/*!50001 DROP VIEW IF EXISTS `memdiscountadd`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `memdiscountadd` AS select max(`localtemptrans`.`datetime`) AS `datetime`,`localtemptrans`.`register_no` AS `register_no`,`localtemptrans`.`emp_no` AS `emp_no`,`localtemptrans`.`trans_no` AS `trans_no`,`localtemptrans`.`upc` AS `upc`,`localtemptrans`.`description` AS `description`,_latin1'I' AS `trans_type`,_latin1'' AS `trans_subtype`,_latin1'M' AS `trans_status`,max(`localtemptrans`.`department`) AS `department`,1 AS `quantity`,0 AS `scale`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `unitPrice`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `total`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `regPrice`,max(`localtemptrans`.`tax`) AS `tax`,max(`localtemptrans`.`foodstamp`) AS `foodstamp`,0 AS `discount`,(-(1) * sum(`localtemptrans`.`memDiscount`)) AS `memDiscount`,3 AS `discountable`,20 AS `discounttype`,8 AS `voided`,0 AS `percentDiscount`,0 AS `ItemQtty`,0 AS `volDiscType`,0 AS `volume`,0 AS `VolSpecial`,0 AS `mixMatch`,0 AS `matched`,`localtemptrans`.`card_no` AS `card_no` from `localtemptrans` where (((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` = `localtemptrans`.`regPrice`)) or (`localtemptrans`.`trans_status` = _latin1'M')) group by `localtemptrans`.`register_no`,`localtemptrans`.`emp_no`,`localtemptrans`.`trans_no`,`localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`card_no` having (sum(`localtemptrans`.`memDiscount`) <> 0) */;

--
-- Final view structure for view `memdiscountremove`
--

/*!50001 DROP TABLE `memdiscountremove`*/;
/*!50001 DROP VIEW IF EXISTS `memdiscountremove`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `memdiscountremove` AS select max(`localtemptrans`.`datetime`) AS `datetime`,`localtemptrans`.`register_no` AS `register_no`,`localtemptrans`.`emp_no` AS `emp_no`,`localtemptrans`.`trans_no` AS `trans_no`,`localtemptrans`.`upc` AS `upc`,`localtemptrans`.`description` AS `description`,_latin1'I' AS `trans_type`,_latin1'' AS `trans_subtype`,_latin1'M' AS `trans_status`,max(`localtemptrans`.`department`) AS `department`,1 AS `quantity`,0 AS `scale`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `unitPrice`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `total`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `regPrice`,max(`localtemptrans`.`tax`) AS `tax`,max(`localtemptrans`.`foodstamp`) AS `foodstamp`,0 AS `discount`,(-(1) * sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end))) AS `memDiscount`,3 AS `discountable`,20 AS `discounttype`,8 AS `voided`,0 AS `percentDiscount`,0 AS `ItemQtty`,0 AS `volDiscType`,0 AS `volume`,0 AS `VolSpecial`,0 AS `mixMatch`,0 AS `matched`,`localtemptrans`.`card_no` AS `card_no` from `localtemptrans` where (((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) or (`localtemptrans`.`trans_status` = _latin1'M')) group by `localtemptrans`.`register_no`,`localtemptrans`.`emp_no`,`localtemptrans`.`trans_no`,`localtemptrans`.`upc`,`localtemptrans`.`description`,`localtemptrans`.`card_no` having (sum((case when ((`localtemptrans`.`discounttype` = 2) and (`localtemptrans`.`unitPrice` <> `localtemptrans`.`regPrice`)) then (-(1) * `localtemptrans`.`memDiscount`) else `localtemptrans`.`memDiscount` end)) <> 0) */;

--
-- Final view structure for view `pr_redeemed`
--

/*!50001 DROP TABLE `pr_redeemed`*/;
/*!50001 DROP VIEW IF EXISTS `pr_redeemed`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `pr_redeemed` AS select `dlog_2008_pr`.`datetime` AS `datetime`,`dlog_2008_pr`.`emp_no` AS `emp_no`,`dlog_2008_pr`.`register_no` AS `register_no`,`dlog_2008_pr`.`trans_no` AS `trans_no`,`dlog_2008_pr`.`card_no` AS `card_no`,`dlog_2008_pr`.`total` AS `total` from `dlog_2008_pr` where ((`dlog_2008_pr`.`trans_subtype` = _latin1'PT') and (`dlog_2008_pr`.`emp_no` <> 9999) and (`dlog_2008_pr`.`trans_status` <> _latin1'x')) */;

--
-- Final view structure for view `rp_list`
--

/*!50001 DROP TABLE `rp_list`*/;
/*!50001 DROP VIEW IF EXISTS `rp_list`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_list` AS select sql_no_cache `localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,sum((case when (`localtranstoday`.`trans_type` = _latin1'T') then (-(1) * `localtranstoday`.`total`) else `localtranstoday`.`total` end)) AS `total` from `localtranstoday` group by `localtranstoday`.`register_no`,`localtranstoday`.`emp_no`,`localtranstoday`.`trans_no` */;

--
-- Final view structure for view `rp_ltt_receipt`
--

/*!50001 DROP TABLE `rp_ltt_receipt`*/;
/*!50001 DROP VIEW IF EXISTS `rp_ltt_receipt`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_ltt_receipt` AS select sql_no_cache `localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,`localtranstoday`.`description` AS `description`,(case when (`localtranstoday`.`voided` = 5) then _latin1'Discount' when (`localtranstoday`.`trans_status` = _latin1'M') then _latin1'Mbr special' when (`localtranstoday`.`trans_status` = _latin1'S') then _latin1'Staff special' when ((`localtranstoday`.`Scale` <> 0) and (`localtranstoday`.`quantity` <> 0)) then concat(`localtranstoday`.`quantity`,_latin1' @ ',`localtranstoday`.`unitPrice`) when ((abs(`localtranstoday`.`ItemQtty`) > 1) and (abs(`localtranstoday`.`ItemQtty`) > abs(`localtranstoday`.`quantity`)) and (`localtranstoday`.`discounttype` <> 3) and (`localtranstoday`.`quantity` = 1)) then concat(`localtranstoday`.`volume`,_latin1' /',`localtranstoday`.`unitPrice`) when ((abs(`localtranstoday`.`ItemQtty`) > 1) and (abs(`localtranstoday`.`ItemQtty`) > abs(`localtranstoday`.`quantity`)) and (`localtranstoday`.`discounttype` <> 3) and (`localtranstoday`.`quantity` <> 1)) then concat(`localtranstoday`.`quantity`,_latin1' @ ',`localtranstoday`.`volume`,_latin1' /',`localtranstoday`.`unitPrice`) when ((abs(`localtranstoday`.`ItemQtty`) > 1) and (`localtranstoday`.`discounttype` = 3)) then concat(`localtranstoday`.`ItemQtty`,_latin1' /',`localtranstoday`.`unitPrice`) when (abs(`localtranstoday`.`ItemQtty`) > 1) then concat(`localtranstoday`.`quantity`,_latin1' @ ',`localtranstoday`.`unitPrice`) when (`localtranstoday`.`matched` > 0) then _latin1'1 w/ vol adj' else _latin1'' end) AS `comment`,`localtranstoday`.`total` AS `total`,(case when (`localtranstoday`.`trans_status` = _latin1'V') then _latin1'VD' when (`localtranstoday`.`trans_status` = _latin1'R') then _latin1'RF' when ((`localtranstoday`.`tax` <> 0) and (`localtranstoday`.`foodstamp` <> 0)) then _latin1'TF' when ((`localtranstoday`.`tax` <> 0) and (`localtranstoday`.`foodstamp` = 0)) then _latin1'T' when ((`localtranstoday`.`tax` = 0) and (`localtranstoday`.`foodstamp` <> 0)) then _latin1'F' when ((`localtranstoday`.`tax` = 0) and (`localtranstoday`.`foodstamp` = 0)) then _latin1'' end) AS `Status`,`localtranstoday`.`trans_type` AS `trans_type`,`localtranstoday`.`unitPrice` AS `unitPrice`,`localtranstoday`.`voided` AS `voided`,`localtranstoday`.`trans_id` AS `trans_id` from `localtranstoday` where ((`localtranstoday`.`voided` <> 5) and (`localtranstoday`.`upc` <> _latin1'TAX') and (`localtranstoday`.`upc` <> _latin1'DISCOUNT')) order by `localtranstoday`.`emp_no`,`localtranstoday`.`trans_no`,`localtranstoday`.`trans_id` */;

--
-- Final view structure for view `rp_receipt`
--

/*!50001 DROP TABLE `rp_receipt`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt` AS select sql_no_cache `rp_ltt_receipt`.`register_no` AS `register_no`,`rp_ltt_receipt`.`emp_no` AS `emp_no`,`rp_ltt_receipt`.`trans_no` AS `trans_no`,(case when (`rp_ltt_receipt`.`trans_type` = _latin1'T') then concat(right(concat(repeat(_latin1' ',44),ucase(rtrim(`rp_ltt_receipt`.`description`))),44),right(concat(repeat(_latin1' ',8),(-(1) * `rp_ltt_receipt`.`total`)),8),right(concat(repeat(_latin1' ',4),`rp_ltt_receipt`.`Status`),4)) when (`rp_ltt_receipt`.`voided` = 3) then concat(left(concat(`rp_ltt_receipt`.`description`,repeat(_latin1' ',30)),30),repeat(_latin1' ',9),_latin1'TOTAL',right(concat(repeat(_latin1' ',8),`rp_ltt_receipt`.`unitPrice`),8)) when (`rp_ltt_receipt`.`voided` = 2) then `rp_ltt_receipt`.`description` when (`rp_ltt_receipt`.`voided` = 4) then `rp_ltt_receipt`.`description` when (`rp_ltt_receipt`.`voided` = 6) then `rp_ltt_receipt`.`description` when ((`rp_ltt_receipt`.`voided` = 7) or (`rp_ltt_receipt`.`voided` = 17)) then concat(left(concat(`rp_ltt_receipt`.`description`,repeat(_latin1' ',30)),30),repeat(_latin1' ',14),right(concat(repeat(_latin1' ',8),`rp_ltt_receipt`.`unitPrice`),8),right(concat(repeat(_latin1' ',4),`rp_ltt_receipt`.`Status`),4)) else concat(left(concat(`rp_ltt_receipt`.`description`,repeat(_latin1' ',30)),30),_latin1' ',left(concat(`rp_ltt_receipt`.`comment`,repeat(_latin1' ',13)),13),right(concat(repeat(_latin1' ',8),`rp_ltt_receipt`.`total`),8),right(concat(repeat(_latin1' ',4),`rp_ltt_receipt`.`Status`),4)) end) AS `linetoprint`,`rp_ltt_receipt`.`trans_id` AS `trans_id` from `rp_ltt_receipt` */;

--
-- Final view structure for view `rp_receipt_header`
--

/*!50001 DROP TABLE `rp_receipt_header`*/;
/*!50001 DROP VIEW IF EXISTS `rp_receipt_header`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `rp_receipt_header` AS select sql_no_cache min(`localtranstoday`.`datetime`) AS `dateTimeStamp`,`localtranstoday`.`card_no` AS `memberID`,`localtranstoday`.`register_no` AS `register_no`,`localtranstoday`.`emp_no` AS `emp_no`,`localtranstoday`.`trans_no` AS `trans_no`,sum((case when (`localtranstoday`.`discounttype` = 1) then `localtranstoday`.`discount` else 0 end)) AS `discountTTL`,sum((case when (`localtranstoday`.`discounttype` = 2) then `localtranstoday`.`memDiscount` else 0 end)) AS `memSpecial`,sum((case when (`localtranstoday`.`upc` = _latin1'0000000008005') then `localtranstoday`.`total` else 0 end)) AS `couponTotal`,sum((case when (`localtranstoday`.`upc` = _latin1'MEMCOUPON') then `localtranstoday`.`unitPrice` else 0 end)) AS `memCoupon`,abs(sum((case when ((`localtranstoday`.`trans_subtype` = _latin1'MI') or (`localtranstoday`.`trans_subtype` = _latin1'CX')) then `localtranstoday`.`total` else 0 end))) AS `chargeTotal`,sum((case when (`localtranstoday`.`upc` = _latin1'Discount') then `localtranstoday`.`total` else 0 end)) AS `transDiscount`,sum((case when (`localtranstoday`.`trans_type` = _latin1'T') then (-(1) * `localtranstoday`.`total`) else 0 end)) AS `tenderTotal` from `localtranstoday` group by `localtranstoday`.`register_no`,`localtranstoday`.`emp_no`,`localtranstoday`.`trans_no`,`localtranstoday`.`card_no` */;

--
-- Final view structure for view `screendisplay`
--

/*!50001 DROP TABLE `screendisplay`*/;
/*!50001 DROP VIEW IF EXISTS `screendisplay`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `screendisplay` AS select (case when ((`localtemptrans`.`voided` = 5) or (`localtemptrans`.`voided` = 11) or (`localtemptrans`.`voided` = 17) or (`localtemptrans`.`trans_type` = _latin1'T')) then _latin1'' else `localtemptrans`.`description` end) AS `description`,(case when ((`localtemptrans`.`discounttype` = 3) and (`localtemptrans`.`trans_status` = _latin1'V')) then concat(`localtemptrans`.`ItemQtty`,_latin1' /',`localtemptrans`.`unitPrice`) when (`localtemptrans`.`voided` = 5) then _latin1'Discount' when (`localtemptrans`.`trans_status` = _latin1'M') then _latin1'Mbr special' when (`localtemptrans`.`trans_status` = _latin1'S') then _latin1'Staff special' when ((`localtemptrans`.`scale` <> 0) and (`localtemptrans`.`quantity` <> 0)) then concat(`localtemptrans`.`quantity`,_latin1' @ ',`localtemptrans`.`unitPrice`) when (substr(`localtemptrans`.`upc`,1,3) = _latin1'002') then concat(`localtemptrans`.`ItemQtty`,_latin1' @ ',`localtemptrans`.`regPrice`) when ((abs(`localtemptrans`.`ItemQtty`) > 1) and (abs(`localtemptrans`.`ItemQtty`) > abs(`localtemptrans`.`quantity`)) and (`localtemptrans`.`discounttype` <> 3) and (`localtemptrans`.`quantity` = 1)) then concat(`localtemptrans`.`volume`,_latin1' for ',`localtemptrans`.`unitPrice`) when ((abs(`localtemptrans`.`ItemQtty`) > 1) and (abs(`localtemptrans`.`ItemQtty`) > abs(`localtemptrans`.`quantity`)) and (`localtemptrans`.`discounttype` <> 3) and (`localtemptrans`.`quantity` <> 1)) then concat(`localtemptrans`.`quantity`,_latin1' @ ',`localtemptrans`.`volume`,_latin1' for ',`localtemptrans`.`unitPrice`) when ((abs(`localtemptrans`.`ItemQtty`) > 1) and (`localtemptrans`.`discounttype` = 3)) then concat(`localtemptrans`.`ItemQtty`,_latin1' /',`localtemptrans`.`unitPrice`) when (abs(`localtemptrans`.`ItemQtty`) > 1) then concat(`localtemptrans`.`quantity`,_latin1' @ ',`localtemptrans`.`unitPrice`) when (`localtemptrans`.`voided` = 3) then _latin1'Total ' when (`localtemptrans`.`voided` = 5) then _latin1'Discount ' when (`localtemptrans`.`voided` = 7) then _latin1'' when ((`localtemptrans`.`voided` = 11) or (`localtemptrans`.`voided` = 17)) then `localtemptrans`.`upc` when (`localtemptrans`.`matched` > 0) then _latin1'1 w/ vol adj' when (`localtemptrans`.`trans_type` = _latin1'T') then `localtemptrans`.`description` else _latin1'' end) AS `comment`,(case when ((`localtemptrans`.`voided` = 3) or (`localtemptrans`.`voided` = 5) or (`localtemptrans`.`voided` = 7) or (`localtemptrans`.`voided` = 11) or (`localtemptrans`.`voided` = 17)) then `localtemptrans`.`unitPrice` when (`localtemptrans`.`trans_status` = _latin1'D') then _latin1'' else `localtemptrans`.`total` end) AS `total`,(case when (`localtemptrans`.`trans_status` = _latin1'V') then _latin1'VD' when (`localtemptrans`.`trans_status` = _latin1'R') then _latin1'RF' when (`localtemptrans`.`trans_status` = _latin1'C') then _latin1'MC' when ((`localtemptrans`.`tax` <> 0) and (`localtemptrans`.`foodstamp` <> 0)) then _latin1'TF' when ((`localtemptrans`.`tax` <> 0) and (`localtemptrans`.`foodstamp` = 0)) then _latin1'T' when ((`localtemptrans`.`tax` = 0) and (`localtemptrans`.`foodstamp` <> 0)) then _latin1'F' when ((`localtemptrans`.`tax` = 0) and (`localtemptrans`.`foodstamp` = 0)) then _latin1'' else _latin1'' end) AS `status`,(case when ((`localtemptrans`.`trans_status` = _latin1'V') or (`localtemptrans`.`trans_type` = _latin1'T') or (`localtemptrans`.`trans_status` = _latin1'R') or (`localtemptrans`.`trans_status` = _latin1'C') or (`localtemptrans`.`trans_status` = _latin1'M') or (`localtemptrans`.`voided` = 17) or (`localtemptrans`.`trans_status` = _latin1'J')) then _latin1'800000' when ((`localtemptrans`.`discounttype` <> 0) or (`localtemptrans`.`voided` = 2) or (`localtemptrans`.`voided` = 6) or (`localtemptrans`.`voided` = 4) or (`localtemptrans`.`voided` = 5) or (`localtemptrans`.`voided` = 10) or (`localtemptrans`.`voided` = 22)) then _latin1'408080' when ((`localtemptrans`.`voided` = 3) or (`localtemptrans`.`voided` = 11)) then _latin1'000000' when (`localtemptrans`.`voided` = 7) then _latin1'800080' else _latin1'004080' end) AS `lineColor`,`localtemptrans`.`discounttype` AS `discounttype`,`localtemptrans`.`trans_type` AS `trans_type`,`localtemptrans`.`trans_status` AS `trans_status`,`localtemptrans`.`voided` AS `voided`,`localtemptrans`.`trans_id` AS `trans_id` from `localtemptrans` order by `localtemptrans`.`trans_id` */;

--
-- Final view structure for view `suspendedlist`
--

/*!50001 DROP TABLE `suspendedlist`*/;
/*!50001 DROP VIEW IF EXISTS `suspendedlist`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `suspendedlist` AS select sql_no_cache `suspended`.`register_no` AS `register_no`,`suspended`.`emp_no` AS `emp_no`,`suspended`.`trans_no` AS `trans_no`,sum(`suspended`.`total`) AS `total` from `suspended` where ((to_days(`suspended`.`datetime`) - to_days(now())) = 0) group by `suspended`.`register_no`,`suspended`.`emp_no`,`suspended`.`trans_no` */;

--
-- Final view structure for view `suspendedtoday`
--

/*!50001 DROP TABLE `suspendedtoday`*/;
/*!50001 DROP VIEW IF EXISTS `suspendedtoday`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `suspendedtoday` AS select sql_no_cache `suspended`.`datetime` AS `datetime`,`suspended`.`register_no` AS `register_no`,`suspended`.`emp_no` AS `emp_no`,`suspended`.`trans_no` AS `trans_no`,`suspended`.`upc` AS `upc`,`suspended`.`description` AS `description`,`suspended`.`trans_type` AS `trans_type`,`suspended`.`trans_subtype` AS `trans_subtype`,`suspended`.`trans_status` AS `trans_status`,`suspended`.`department` AS `department`,`suspended`.`quantity` AS `quantity`,`suspended`.`scale` AS `scale`,`suspended`.`unitPrice` AS `unitPrice`,`suspended`.`total` AS `total`,`suspended`.`regPrice` AS `regPrice`,`suspended`.`tax` AS `tax`,`suspended`.`foodstamp` AS `foodstamp`,`suspended`.`discount` AS `discount`,`suspended`.`memDiscount` AS `memDiscount`,`suspended`.`discountable` AS `discountable`,`suspended`.`discounttype` AS `discounttype`,`suspended`.`voided` AS `voided`,`suspended`.`percentDiscount` AS `percentDiscount`,`suspended`.`ItemQtty` AS `ItemQtty`,`suspended`.`volDiscType` AS `volDiscType`,`suspended`.`volume` AS `volume`,`suspended`.`VolSpecial` AS `VolSpecial`,`suspended`.`mixMatch` AS `mixMatch`,`suspended`.`matched` AS `matched`,`suspended`.`card_no` AS `card_no`,`suspended`.`trans_id` AS `trans_id` from `suspended` where ((to_days(`suspended`.`datetime`) - to_days(now())) = 0) */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-05-10 23:12:48
