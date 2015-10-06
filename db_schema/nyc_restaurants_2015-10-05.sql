# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.6.26)
# Database: nyc_restaurants
# Generation Time: 2015-10-05 23:56:01 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table address
# ------------------------------------------------------------

DROP TABLE IF EXISTS `address`;

CREATE TABLE `address` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `boro` varchar(200) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `street` varchar(500) DEFAULT NULL,
  `zip` varchar(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table cuisine_type
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cuisine_type`;

CREATE TABLE `cuisine_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table inspection
# ------------------------------------------------------------

DROP TABLE IF EXISTS `inspection`;

CREATE TABLE `inspection` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_date` date DEFAULT NULL,
  `violation_id` int(11) DEFAULT NULL,
  `type` varchar(500) DEFAULT NULL,
  `score` int(3) DEFAULT NULL,
  `grade` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table restaurant
# ------------------------------------------------------------

DROP TABLE IF EXISTS `restaurant`;

CREATE TABLE `restaurant` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `camis` varchar(11) DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `cuisine_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table restaurant_to_inspection
# ------------------------------------------------------------

DROP TABLE IF EXISTS `restaurant_to_inspection`;

CREATE TABLE `restaurant_to_inspection` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) DEFAULT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table violation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `violation`;

CREATE TABLE `violation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(5) DEFAULT NULL,
  `description` text,
  `flag` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
