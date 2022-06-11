INSERT INTO `places` VALUES (1, NULL, 'France', 'FR', 'country', NOW(), NOW());
INSERT INTO `places` VALUES (2, 1, 'Île-de-France', 'FR', 'area', NOW(), NOW());
INSERT INTO `places` VALUES (3, 2, 'Paris', 'FR', 'city', NOW(), NOW());

INSERT INTO `dioceses` (`diocese_id`, `country_id`, `name`, `website`, `created_at`, `updated_at`, `gcatholic_id`) VALUES
(1242250, 1, 'Archidiocèse de Paris', '', NOW(), NOW(), '');

INSERT INTO `wikidata_churches` (`wikidata_church_id`, `place_id`, `name`, `latitude`, `longitude`, `address`, `created_at`, `updated_at`, `parish_id`, `diocese_id`, `messesinfo_id`) VALUES
(2981, 3, 'cathédrale Notre-Dame-de-Paris', 48.853, 2.3498, '', NOW(), NOW(), NULL, 1242250, '75/paris-04/cathedrale-notre-dame-de-paris');

INSERT INTO `churches` VALUES (1, 2981, NULL, 'http://www.notredamedeparis.fr/visites-2/informations-pratiques/horaires/');
