CREATE TABLE `address` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `boro` varchar(200) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `street` varchar(500) DEFAULT NULL,
  `zip` varchar(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24198 DEFAULT CHARSET=latin1;


CREATE TABLE `cuisine_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=latin1;


CREATE TABLE `inspection` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inspection_date` date DEFAULT NULL,
  `violation_id` int(11) DEFAULT NULL,
  `type` varchar(500) DEFAULT NULL,
  `score` int(3) DEFAULT NULL,
  `grade` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=242477 DEFAULT CHARSET=latin1;


CREATE TABLE `restaurant` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `camis` varchar(11) DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `cuisine_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24197 DEFAULT CHARSET=latin1;


CREATE TABLE `restaurant_to_inspection` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) DEFAULT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=242476 DEFAULT CHARSET=latin1;


CREATE TABLE `top_10_restaurants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant` varchar(250) DEFAULT NULL,
  `address` text,
  `phone` varchar(15) DEFAULT NULL,
  `score` smallint(3) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `insert_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=latin1;


CREATE TABLE `violation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(5) DEFAULT NULL,
  `description` text,
  `flag` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25025 DEFAULT CHARSET=latin1;