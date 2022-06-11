INSERT INTO `places` VALUES (1, NULL, 'France', 'FR', 'country', NOW(), NOW());
INSERT INTO `places` VALUES (2, 1, 'Île-de-France', 'FR', 'area', NOW(), NOW());
INSERT INTO `places` VALUES (3, 2, 'Paris', 'FR', 'city', NOW(), NOW());

INSERT INTO `dioceses` (`diocese_id`, `country_id`, `name`, `website`, `created_at`, `updated_at`, `gcatholic_id`) VALUES
(1242250, 1, 'Archidiocèse de Paris', '', NOW(), NOW(), '');

INSERT INTO `parishes` (`parish_id`, `diocese_id`, `country_id`, `name`, `messesinfo_id`, `website`, `zip_code`, `created_at`, `updated_at`) VALUES
(97293132, 1242250, 1, 'Paroisse Saint-Sulpice', 'pa/75/saint-sulpice', '', '75006', NOW(), NOW());

INSERT INTO `wikidata_churches` (`wikidata_church_id`, `place_id`, `name`, `latitude`, `longitude`, `address`, `created_at`, `updated_at`, `parish_id`, `diocese_id`, `messesinfo_id`) VALUES
(295844, 3, 'Église Saint-Sulpice de Paris', 48.8511, 2.3347, '', NOW(), NOW(), 97293132, 1242250, '75/paris-06/saint-sulpice'),
(2981, 3, 'cathédrale Notre-Dame-de-Paris', 48.853, 2.3498, '', NOW(), NOW(), NULL, 1242250, '75/paris-04/cathedrale-notre-dame-de-paris');

INSERT INTO `churches` (`church_id`, `wikidata_church_id`, `theodia_church_id`, `masses_url`) VALUES
(1, 295844, NULL, 'https://www.paroissesaintsulpice.paris/les-horaires/'),
(2, 2981, NULL, 'http://www.notredamedeparis.fr/visites-2/informations-pratiques/horaires/');
