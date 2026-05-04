<?php
/**
 * Seed : 50 plats + 50 produits + 5 fournisseurs + tarifs
 * Usage : php database/seed_restauration.php
 */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/core/Database.php';

$db = Database::getInstance()->getConnection();

// =============================================
// FOURNISSEURS (5)
// =============================================
$fournisseurs = [
    ['Metro Cash & Carry', '78435296100015', '1 Rue du Commerce', '13001', 'Marseille', '0491000001', 'commandes@metro.fr', 'alimentaire'],
    ['Sysco France', '44523178900028', '25 Avenue de la Gare', '69003', 'Lyon', '0472000002', 'commandes@sysco.fr', 'alimentaire'],
    ['Pomona', '32145698700034', '10 Rue des Halles', '75001', 'Paris', '0142000003', 'commandes@pomona.fr', 'fruits_legumes'],
    ['Davigel (Surgelés)', '55432198600045', '5 Zone Industrielle Nord', '31000', 'Toulouse', '0561000004', 'commandes@davigel.fr', 'surgeles'],
    ['Cave de la Résidence', '99887766554433', '8 Rue du Vignoble', '33000', 'Bordeaux', '0556000005', 'contact@caveresidence.fr', 'boissons'],
];

$stmtF = $db->prepare("INSERT INTO fournisseurs (nom, siret, adresse, code_postal, ville, telephone, email, type_service, actif) VALUES (?,?,?,?,?,?,?,?,1)");
foreach ($fournisseurs as $f) {
    $stmtF->execute($f);
}
$metroId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Metro%' LIMIT 1")->fetchColumn();
$syscoId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Sysco%' LIMIT 1")->fetchColumn();
$pomonaId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Pomona%' LIMIT 1")->fetchColumn();
$davigelId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Davigel%' LIMIT 1")->fetchColumn();
$caveId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Cave%' LIMIT 1")->fetchColumn();
echo "5 fournisseurs créés\n";

// =============================================
// 50 PLATS
// =============================================
$stmtP = $db->prepare("INSERT INTO rest_plats (nom, description, categorie, type_service, prix_unitaire, allergenes, regime, actif, ordre_affichage) VALUES (?,?,?,?,?,?,?,1,?)");

$entrees = [
    ['Salade César', 'Salade romaine, poulet grillé, parmesan, croûtons', 'tous', 6.50, 'gluten,lactose', 'normal'],
    ['Velouté de potimarron', 'Velouté onctueux au potimarron et crème fraîche', 'tous', 5.50, 'lactose', 'normal'],
    ['Terrine de campagne', 'Terrine maison aux herbes de Provence', 'tous', 6.00, null, 'normal'],
    ['Salade de chèvre chaud', 'Mesclun, toast de chèvre, miel, noix', 'tous', 7.00, 'lactose,fruits_a_coque', 'vegetarien'],
    ['Soupe à l\'oignon', 'Soupe gratinée traditionnelle', 'diner', 5.00, 'gluten,lactose', 'vegetarien'],
    ['Carpaccio de saumon', 'Saumon frais mariné, aneth, câpres', 'tous', 8.50, 'poisson', 'normal'],
    ['Œufs mimosa', 'Œufs durs, mayonnaise maison', 'tous', 4.50, 'oeuf', 'vegetarien'],
    ['Tarte aux poireaux', 'Tarte fine aux poireaux et crème', 'tous', 5.50, 'gluten,lactose,oeuf', 'vegetarien'],
    ['Gaspacho andalou', 'Soupe froide de tomates et poivrons', 'dejeuner', 5.00, null, 'vegan'],
    ['Salade niçoise', 'Thon, olives, œuf, tomates, haricots verts', 'dejeuner', 7.50, 'poisson,oeuf', 'normal'],
    ['Taboulé libanais', 'Boulgour, persil, menthe, tomates, citron', 'tous', 5.00, 'gluten', 'vegan'],
    ['Mousse de canard', 'Mousse de canard au porto', 'diner', 7.00, null, 'normal'],
    ['Rillettes de thon', 'Thon émietté, fromage frais, ciboulette', 'tous', 6.00, 'poisson,lactose', 'normal'],
    ['Salade de lentilles', 'Lentilles vertes, échalotes, vinaigrette', 'tous', 5.50, null, 'vegan'],
    ['Assiette de crudités', 'Carottes râpées, céleri, betteraves', 'tous', 4.50, null, 'vegan'],
    ['Quiche lorraine', 'Quiche aux lardons et gruyère', 'tous', 6.00, 'gluten,lactose,oeuf', 'normal'],
    ['Potage Crécy', 'Velouté de carottes au cumin', 'diner', 4.50, null, 'vegan'],
    ['Avocats aux crevettes', 'Demi-avocat garni de crevettes roses', 'tous', 7.50, 'crustaces', 'normal'],
    ['Feuilleté au fromage', 'Feuilleté croustillant au comté', 'tous', 5.50, 'gluten,lactose', 'vegetarien'],
    ['Salade de pâtes', 'Pennes, tomates séchées, mozzarella, basilic', 'dejeuner', 6.00, 'gluten,lactose', 'vegetarien'],
];
$i = 1;
foreach ($entrees as $e) { $stmtP->execute([$e[0], $e[1], 'entree', $e[2], $e[3], $e[4], $e[5], $i++]); }
echo "20 entrées créées\n";

$plats = [
    ['Poulet rôti aux herbes', 'Poulet fermier rôti, thym, romarin, pommes grenaille', 'tous', 12.00, null, 'normal'],
    ['Filet de saumon grillé', 'Saumon grillé, sauce citronnée, riz basmati', 'tous', 14.00, 'poisson', 'normal'],
    ['Blanquette de veau', 'Veau mijoté, sauce crémée, carottes, champignons', 'tous', 13.50, 'lactose', 'normal'],
    ['Steak haché grillé', 'Steak 150g, frites maison, salade', 'dejeuner', 11.00, null, 'normal'],
    ['Filet de cabillaud', 'Cabillaud en croûte d\'herbes, purée de brocolis', 'tous', 13.00, 'poisson,gluten', 'normal'],
    ['Bœuf bourguignon', 'Bœuf mijoté au vin rouge, lardons, champignons', 'diner', 14.50, null, 'normal'],
    ['Escalope de dinde', 'Escalope panée, pâtes fraîches, sauce tomate', 'tous', 10.50, 'gluten,oeuf', 'normal'],
    ['Gratin dauphinois', 'Pommes de terre gratinées à la crème', 'tous', 8.50, 'lactose', 'vegetarien'],
    ['Risotto aux champignons', 'Riz arborio crémeux, champignons de Paris et shiitake', 'tous', 11.00, 'lactose', 'vegetarien'],
    ['Couscous royal', 'Semoule, merguez, poulet, légumes, bouillon épicé', 'dejeuner', 14.00, 'gluten', 'normal'],
    ['Lasagnes bolognaise', 'Lasagnes maison, sauce bolognaise, béchamel', 'tous', 11.50, 'gluten,lactose,oeuf', 'normal'],
    ['Pavé de thon mi-cuit', 'Thon mi-cuit, sésame, wok de légumes', 'tous', 15.00, 'poisson,sesame', 'normal'],
    ['Ratatouille provençale', 'Légumes du soleil mijotés, riz complet', 'tous', 9.50, null, 'vegan'],
    ['Hachis parmentier', 'Bœuf haché, purée gratinée au four', 'tous', 10.00, 'lactose', 'normal'],
    ['Curry de légumes', 'Légumes de saison au curry doux, riz thaï', 'tous', 10.00, null, 'vegan'],
    ['Filet mignon de porc', 'Filet mignon en croûte, sauce moutarde', 'diner', 13.50, 'gluten,lactose', 'normal'],
    ['Omelette aux fines herbes', 'Omelette baveuse, salade verte', 'tous', 8.00, 'oeuf', 'vegetarien'],
    ['Sauté de dinde', 'Dinde sautée à la crème et aux champignons', 'tous', 11.00, 'lactose', 'normal'],
    ['Dos de lieu noir', 'Lieu noir poêlé, beurre blanc, pommes vapeur', 'tous', 12.50, 'poisson,lactose', 'normal'],
    ['Tajine d\'agneau', 'Agneau aux pruneaux et amandes, semoule', 'diner', 15.00, 'fruits_a_coque,gluten', 'normal'],
];
$i = 1;
foreach ($plats as $p) { $stmtP->execute([$p[0], $p[1], 'plat', $p[2], $p[3], $p[4], $p[5], $i++]); }
echo "20 plats créés\n";

$desserts = [
    ['Crème brûlée', 'Crème vanillée caramélisée au chalumeau', 'tous', 5.50, 'lactose,oeuf', 'vegetarien'],
    ['Tarte aux pommes', 'Tarte maison, compote et pommes caramélisées', 'tous', 5.00, 'gluten,lactose,oeuf', 'vegetarien'],
    ['Mousse au chocolat', 'Mousse au chocolat noir 70%', 'tous', 5.00, 'lactose,oeuf', 'vegetarien'],
    ['Île flottante', 'Blancs en neige sur crème anglaise, caramel', 'tous', 5.50, 'lactose,oeuf', 'vegetarien'],
    ['Salade de fruits frais', 'Fruits de saison, menthe fraîche', 'tous', 4.50, null, 'vegan'],
    ['Panna cotta', 'Panna cotta vanille, coulis de fruits rouges', 'tous', 5.50, 'lactose', 'vegetarien'],
    ['Fondant au chocolat', 'Cœur coulant au chocolat, crème anglaise', 'diner', 6.50, 'gluten,lactose,oeuf', 'vegetarien'],
    ['Fromage blanc au miel', 'Fromage blanc fermier, miel de lavande', 'tous', 4.00, 'lactose', 'vegetarien'],
    ['Clafoutis aux cerises', 'Clafoutis aux cerises noires', 'tous', 5.00, 'gluten,lactose,oeuf', 'vegetarien'],
    ['Compote de pommes', 'Compote maison, cannelle', 'tous', 3.50, null, 'vegan'],
];
$i = 1;
foreach ($desserts as $d) { $stmtP->execute([$d[0], $d[1], 'dessert', $d[2], $d[3], $d[4], $d[5], $i++]); }
echo "10 desserts créés\n";

// =============================================
// 50 PRODUITS
// =============================================
$stmtProd = $db->prepare("INSERT INTO rest_produits (nom, categorie, unite, prix_reference, fournisseur_id, marque, conditionnement, actif) VALUES (?,?,?,?,?,?,?,1)");

$produits = [
    // Fruits & Légumes (10)
    ['Pommes de terre', 'fruits_legumes', 'kg', 1.20, $pomonaId, null, 'Sac 10kg'],
    ['Carottes', 'fruits_legumes', 'kg', 1.50, $pomonaId, null, 'Sac 5kg'],
    ['Oignons', 'fruits_legumes', 'kg', 1.80, $pomonaId, null, 'Filet 5kg'],
    ['Tomates', 'fruits_legumes', 'kg', 2.90, $pomonaId, null, 'Cagette 5kg'],
    ['Salade (laitue)', 'fruits_legumes', 'unite', 1.10, $pomonaId, null, 'Pièce'],
    ['Courgettes', 'fruits_legumes', 'kg', 2.20, $pomonaId, null, 'Cagette'],
    ['Pommes Golden', 'fruits_legumes', 'kg', 2.50, $pomonaId, null, 'Carton 10kg'],
    ['Bananes', 'fruits_legumes', 'kg', 1.70, $pomonaId, null, 'Carton'],
    ['Citrons', 'fruits_legumes', 'kg', 3.00, $pomonaId, null, 'Filet 1kg'],
    ['Champignons de Paris', 'fruits_legumes', 'kg', 4.50, $pomonaId, null, 'Barquette 500g'],
    // Viandes (6)
    ['Poulet entier', 'viandes', 'kg', 7.50, $syscoId, null, 'Sous vide'],
    ['Steak haché 15%', 'viandes', 'kg', 9.80, $syscoId, 'Charal', 'Carton 5kg'],
    ['Escalope de dinde', 'viandes', 'kg', 11.00, $syscoId, null, 'Sous vide 2kg'],
    ['Veau (épaule)', 'viandes', 'kg', 18.50, $syscoId, null, 'Sous vide'],
    ['Filet mignon porc', 'viandes', 'kg', 12.00, $syscoId, null, 'Sous vide'],
    ['Lardons fumés', 'viandes', 'kg', 8.50, $metroId, 'Herta', 'Sachet 1kg'],
    // Poissons (4)
    ['Filet de saumon', 'poissons', 'kg', 22.00, $syscoId, null, 'Sous vide 1kg'],
    ['Filet de cabillaud', 'poissons', 'kg', 16.00, $davigelId, null, 'Surgelé 1kg'],
    ['Crevettes roses', 'poissons', 'kg', 14.50, $davigelId, null, 'Surgelé 1kg'],
    ['Thon (pavé)', 'poissons', 'kg', 24.00, $syscoId, null, 'Sous vide'],
    // Laitier (6)
    ['Beurre doux', 'laitier', 'kg', 8.50, $metroId, 'Président', 'Plaquette 250g x4'],
    ['Crème fraîche épaisse', 'laitier', 'litre', 3.20, $metroId, 'Elle & Vire', 'Pot 1L'],
    ['Lait entier', 'laitier', 'litre', 1.10, $metroId, 'Candia', 'Brique 1L x6'],
    ['Gruyère râpé', 'laitier', 'kg', 9.00, $metroId, null, 'Sachet 1kg'],
    ['Fromage blanc', 'laitier', 'kg', 3.50, $metroId, null, 'Seau 5kg'],
    ['Œufs', 'laitier', 'unite', 0.25, $metroId, null, 'Plateau 30 œufs'],
    // Épicerie sèche (8)
    ['Farine T55', 'epicerie_seche', 'kg', 0.90, $metroId, 'Francine', 'Sac 5kg'],
    ['Huile d\'olive', 'epicerie_seche', 'litre', 6.50, $metroId, 'Puget', 'Bidon 5L'],
    ['Pâtes penne', 'epicerie_seche', 'kg', 1.40, $metroId, 'Barilla', 'Paquet 3kg'],
    ['Riz basmati', 'epicerie_seche', 'kg', 2.80, $metroId, 'Uncle Ben\'s', 'Sac 5kg'],
    ['Sucre en poudre', 'epicerie_seche', 'kg', 1.10, $metroId, 'Daddy', 'Sac 5kg'],
    ['Chocolat pâtissier', 'epicerie_seche', 'kg', 12.00, $metroId, 'Valrhona', 'Tablette 1kg'],
    ['Semoule fine', 'epicerie_seche', 'kg', 1.60, $metroId, 'Tipiak', 'Sachet 5kg'],
    ['Lentilles vertes', 'epicerie_seche', 'kg', 3.50, $metroId, null, 'Sachet 5kg'],
    // Boissons (5)
    ['Eau minérale', 'boissons', 'bouteille', 0.30, $caveId, 'Evian', 'Pack 6x1.5L'],
    ['Jus d\'orange', 'boissons', 'litre', 2.20, $caveId, 'Tropicana', 'Brique 1L'],
    ['Vin rouge (côtes du Rhône)', 'boissons', 'bouteille', 4.50, $caveId, null, 'Bouteille 75cl'],
    ['Café moulu', 'boissons', 'kg', 14.00, $metroId, 'Lavazza', 'Paquet 1kg'],
    ['Thé vert', 'boissons', 'boite', 3.80, $metroId, 'Lipton', 'Boîte 100 sachets'],
    // Condiments (4)
    ['Sel fin', 'condiments', 'kg', 0.60, $metroId, 'La Baleine', 'Boîte 1kg'],
    ['Poivre noir moulu', 'condiments', 'unite', 5.50, $metroId, 'Ducros', 'Pot 100g'],
    ['Moutarde de Dijon', 'condiments', 'unite', 2.80, $metroId, 'Maille', 'Pot 850g'],
    ['Vinaigre balsamique', 'condiments', 'bouteille', 4.50, $metroId, null, 'Bouteille 50cl'],
    // Surgelés (4)
    ['Frites surgelées', 'surgeles', 'kg', 2.20, $davigelId, 'McCain', 'Sac 2.5kg'],
    ['Petits pois surgelés', 'surgeles', 'kg', 2.80, $davigelId, 'Bonduelle', 'Sachet 1kg'],
    ['Glace vanille', 'surgeles', 'litre', 8.00, $davigelId, 'Häagen-Dazs', 'Bac 2.5L'],
    ['Pâte feuilletée', 'surgeles', 'unite', 1.50, $davigelId, null, 'Rouleau x10'],
    // Non alimentaire (3)
    ['Serviettes papier', 'non_alimentaire', 'carton', 15.00, $metroId, 'Lotus', 'Carton 2000'],
    ['Film alimentaire', 'non_alimentaire', 'unite', 6.00, $metroId, null, 'Rouleau 300m'],
    ['Gants jetables', 'non_alimentaire', 'boite', 8.50, $metroId, null, 'Boîte 100'],
];

foreach ($produits as $p) { $stmtProd->execute($p); }
echo "50 produits créés\n";

// =============================================
// TARIFS PAR DÉFAUT (La Badiane)
// =============================================
$stmtT = $db->prepare("INSERT IGNORE INTO rest_tarifs (residence_id, type_service, prix_menu, prix_entree, prix_plat, prix_dessert, prix_boisson) VALUES (?,?,?,?,?,?,?)");
$stmtT->execute([61, 'petit_dejeuner', 8.00, null, null, null, 2.00]);
$stmtT->execute([61, 'dejeuner', 15.00, 6.00, 12.00, 5.00, 3.00]);
$stmtT->execute([61, 'gouter', 5.00, null, null, 4.00, 2.00]);
$stmtT->execute([61, 'diner', 15.00, 6.00, 12.00, 5.00, 3.00]);
$stmtT->execute([61, 'snack_bar', 6.00, null, null, null, 3.00]);
echo "Tarifs La Badiane créés\n";

echo "\n=== SEED TERMINÉ ===\n";
