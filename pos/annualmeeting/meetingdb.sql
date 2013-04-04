-- MySQL dump 10.11
--
-- Host: localhost    Database: annualmeeting
-- ------------------------------------------------------
-- Server version	5.0.95

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
-- Temporary table structure for view `arrivals`
--

DROP TABLE IF EXISTS `arrivals`;
/*!50001 DROP VIEW IF EXISTS `arrivals`*/;
/*!50001 CREATE TABLE `arrivals` (
  `typeDesc` varchar(50),
  `pending` decimal(23,0),
  `arrived` decimal(23,0)
) ENGINE=MyISAM */;

--
-- Table structure for table `custdata`
--

DROP TABLE IF EXISTS `custdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custdata` (
  `CardNo` int(8) default NULL,
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
  KEY `LastName` (`LastName`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custdata`
--

LOCK TABLES `custdata` WRITE;
/*!40000 ALTER TABLE `custdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `custdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mealttl`
--

DROP TABLE IF EXISTS `mealttl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mealttl` (
  `name` varchar(50) default NULL,
  `ttl` int(11) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mealttl`
--

LOCK TABLES `mealttl` WRITE;
/*!40000 ALTER TABLE `mealttl` DISABLE KEYS */;
INSERT INTO `mealttl` VALUES ('Chicken',100),('Child Spaghetti',100),('Ratatouille',100);
/*!40000 ALTER TABLE `mealttl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mealtype`
--

DROP TABLE IF EXISTS `mealtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mealtype` (
  `id` int(11) default NULL,
  `typeDesc` varchar(50) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mealtype`
--

LOCK TABLES `mealtype` WRITE;
/*!40000 ALTER TABLE `mealtype` DISABLE KEYS */;
INSERT INTO `mealtype` VALUES (0,'Child Spaghetti'),(1,'Chicken'),(2,'Curry');
/*!40000 ALTER TABLE `mealtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membercards`
--

DROP TABLE IF EXISTS `membercards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membercards` (
  `card_no` int(11) NOT NULL default '0',
  `upc` varchar(13) default NULL,
  PRIMARY KEY  (`card_no`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membercards`
--

LOCK TABLES `membercards` WRITE;
/*!40000 ALTER TABLE `membercards` DISABLE KEYS */;
/*!40000 ALTER TABLE `membercards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `tdate` datetime default NULL,
  `card_no` int(11) default NULL,
  `name` varchar(150) default NULL,
  `email` varchar(150) default NULL,
  `phone` varchar(30) default NULL,
  `guest_count` int(11) default NULL,
  `child_count` int(11) default NULL,
  `paid` int(11) default NULL,
  `checked_in` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regmeals`
--

DROP TABLE IF EXISTS `regmeals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regmeals` (
  `card_no` int(11) default NULL,
  `type` varchar(5) default NULL,
  `subtype` smallint(6) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regmeals`
--

LOCK TABLES `regmeals` WRITE;
/*!40000 ALTER TABLE `regmeals` DISABLE KEYS */;
/*!40000 ALTER TABLE `regmeals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `arrivals`
--

/*!50001 DROP TABLE `arrivals`*/;
/*!50001 DROP VIEW IF EXISTS `arrivals`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `arrivals` AS select `t`.`typeDesc` AS `typeDesc`,sum((case when (`r`.`checked_in` = 0) then 1 else 0 end)) AS `pending`,sum((case when (`r`.`checked_in` = 1) then 1 else 0 end)) AS `arrived` from ((`regmeals` `m` left join `registrations` `r` on((`m`.`card_no` = `r`.`card_no`))) left join `mealtype` `t` on((`t`.`id` = `m`.`subtype`))) group by `t`.`typeDesc` */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-05  8:37:29
