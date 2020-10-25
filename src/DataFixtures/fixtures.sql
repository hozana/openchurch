INSERT INTO `places` VALUES (1,NULL,'France','FR','country',NOW(),NOW());
INSERT INTO `places` VALUES (2,1,'Île-de-France','FR','area',NOW(),NOW());
INSERT INTO `places` VALUES (3,2,'Paris','FR','city',NOW(),NOW());

INSERT INTO `wikidata_churches` VALUES
(2981,3,'cathédrale Notre-Dame-de-Paris',48.853,2.3498,'6 Parvis Notre-Dame - place Jean-Paul-II',NOW(),NOW());

INSERT INTO `churches` VALUES (1,2981,NULL,'http://www.notredamedeparis.fr/visites-2/informations-pratiques/horaires/');
