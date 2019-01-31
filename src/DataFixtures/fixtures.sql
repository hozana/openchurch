SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `churches`;

CREATE TABLE `churches` (
  `church_id` int(11) NOT NULL AUTO_INCREMENT,
  `wikidata_church_id` int(11) DEFAULT NULL,
  `theodia_church_id` int(11) DEFAULT NULL,
  `masses_url` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`church_id`),
  KEY `IDX_E533287F959FC021` (`wikidata_church_id`),
  KEY `IDX_E533287FBA5F2368` (`theodia_church_id`),
  CONSTRAINT `FK_E533287F959FC021` FOREIGN KEY (`wikidata_church_id`) REFERENCES `wikidata_churches` (`wikidata_church_id`),
  CONSTRAINT `FK_E533287FBA5F2368` FOREIGN KEY (`theodia_church_id`) REFERENCES `theodia_churches` (`theodia_church_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `churches` WRITE;

INSERT INTO `churches` VALUES (1,2981,NULL,'http://www.notredamedeparis.fr/visites-2/informations-pratiques/horaires/');

UNLOCK TABLES;


DROP TABLE IF EXISTS `places`;

CREATE TABLE `places` (
  `place_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci,
  `country_code` longtext COLLATE utf8mb4_unicode_ci,
  `type` enum('city','country','state','area','unknown') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:PlaceType)',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`place_id`),
  KEY `IDX_FEAF6C55727ACA70` (`parent_id`),
  CONSTRAINT `FK_FEAF6C55727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `places` (`place_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `places` WRITE;

INSERT INTO `places` VALUES
(1,NULL,'France','FR','country',NOW(),NOW()),
(2,1,'Île-de-France','FR','area',NOW(),NOW()),
(3,2,'Paris','FR','city',NOW(),NOW());

UNLOCK TABLES;


DROP TABLE IF EXISTS `wikidata_churches`;

CREATE TABLE `wikidata_churches` (
  `wikidata_church_id` int(11) NOT NULL AUTO_INCREMENT,
  `place_id` int(11) NOT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`wikidata_church_id`),
  KEY `IDX_F72BF489DA6A219` (`place_id`),
  CONSTRAINT `FK_F72BF489DA6A219` FOREIGN KEY (`place_id`) REFERENCES `places` (`place_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2982 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `wikidata_churches` WRITE;

INSERT INTO `wikidata_churches` VALUES
(2981,3,'cathédrale Notre-Dame-de-Paris',48.853,2.3498,'6 Parvis Notre-Dame - place Jean-Paul-II',NOW(),NOW());

UNLOCK TABLES;

SET FOREIGN_KEY_CHECKS = 1;
