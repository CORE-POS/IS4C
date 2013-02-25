-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb1ubuntu0.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 20, 2013 at 06:31 PM
-- Server version: 5.0.67
-- PHP Version: 5.2.6-2ubuntu4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `is4c_log`
--

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE IF NOT EXISTS `shifts` (
  `ShiftName` varchar(25) default NULL,
  `NiceName` varchar(255) NOT NULL,
  `ShiftID` int(2) NOT NULL,
  `visible` tinyint(1) default NULL,
  `ShiftOrder` int(2) default NULL,
  PRIMARY KEY  (`ShiftID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`ShiftName`, `NiceName`, `ShiftID`, `visible`, `ShiftOrder`) VALUES
('Technology', '', 1, 1, 17),
('CashS', 'Cash Store', 2, 1, 1),
('CashP', 'Cash Paper', 3, 1, 2),
('Breaks', '', 4, 1, 3),
('ProdS', 'Produce Store', 5, 1, 5),
('ProdP', 'Produce Paper', 26, 1, 6),
('GrocS', 'Grocery Store', 25, 1, 7),
('GrocP', 'Grocery Paper', 24, 1, 8),
('BulkS', 'Bulk Store', 23, 1, 9),
('BulkP', 'Bulk Paper', 22, 1, 10),
('NF', 'Non Foods', 21, 1, 11),
('Maint', 'Maintenance', 20, 1, 12),
('OM', '', 19, 1, 13),
('Farm', 'Farmers Market', 17, 1, 14),
('HOO', '', 16, 1, 15),
('FC/Book', 'Finance Coord.', 15, 1, 16),
('FM', 'Finance Mgr.', 30, 1, 18),
('DM', 'Development Mgr.', 13, 1, 19),
('DORC', '', 12, 1, 20),
('PM/Hire', 'Personnel & Hiring', 11, 1, 21),
('MM', 'Marketing & Membership', 10, 1, 22),
('BoD', 'Board of Directors', 9, 1, 23),
('Train', 'Training', 8, 1, 24),
('TeamC', 'Team Coordinators', 7, 1, 25),
('CM', '', 6, 1, 26),
('FET', '', 27, 1, 4),
('PTO', 'PTO requested', 31, 1, 31),
('lunch', '', 0, 0, 32);
