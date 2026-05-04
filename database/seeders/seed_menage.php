<?php
/**
 * Seed Ménage : lots, résidents, produits, inventaire, fournisseurs ménage, shifts planning
 * Usage : php database/seed_menage.php
 */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/core/Database.php';

$db = Database::getInstance()->getConnection();

// =============================================
// 1. CRÉER DES LOTS LOGEMENT à La Badiane (61)
// =============================================
$stmtLot = $db->prepare("INSERT INTO lots (copropriete_id, numero_lot, type, etage, surface, nombre_pieces) VALUES (?,?,?,?,?,?)");

$lots = [
    // Studios (10)
    [61, '101', 'studio', 1, 25, 1], [61, '102', 'studio', 1, 28, 1],
    [61, '103', 'studio', 1, 25, 1], [61, '104', 'studio', 1, 27, 1],
    [61, '105', 'studio', 1, 26, 1], [61, '201', 'studio', 2, 25, 1],
    [61, '202', 'studio', 2, 28, 1], [61, '203', 'studio', 2, 25, 1],
    [61, '204', 'studio', 2, 27, 1], [61, '205', 'studio', 2, 26, 1],
    // T2 (6)
    [61, '106', 't2', 1, 45, 2], [61, '107', 't2', 1, 48, 2],
    [61, '206', 't2', 2, 45, 2], [61, '207', 't2', 2, 48, 2],
    [61, '301', 't2', 3, 45, 2], [61, '302', 't2', 3, 48, 2],
    // T3 (2)
    [61, '303', 't3', 3, 70, 3], [61, '304', 't3', 3, 75, 3],
];

$lotIds = [];
foreach ($lots as $l) {
    $stmtLot->execute($l);
    $lotIds[] = ['id' => (int)$db->lastInsertId(), 'numero' => $l[1], 'type' => $l[2]];
}
echo count($lots) . " lots créés à La Badiane\n";

// =============================================
// 2. CRÉER DES RÉSIDENTS SENIORS + OCCUPATIONS
// =============================================
$hash = password_hash('Resi1234', PASSWORD_DEFAULT);

$residents = [
    ['Mme', 'Marguerite', 'Dupont',    '1938-03-15', 'premium'],
    ['M',  'Henri',      'Martin',     '1940-07-22', 'premium'],
    ['Mme', 'Jeanne',     'Bernard',    '1935-11-08', 'confort'],
    ['M',  'Marcel',     'Thomas',     '1942-01-30', 'confort'],
    ['Mme', 'Simone',     'Robert',     '1937-06-12', 'confort'],
    ['M',  'Pierre',     'Richard',    '1939-09-25', 'essentiel'],
    ['Mme', 'Yvette',     'Moreau',     '1941-04-18', 'essentiel'],
    ['M',  'Jacques',    'Laurent',    '1936-12-03', 'premium'],
    ['Mme', 'Suzanne',    'Simon',      '1943-08-07', 'confort'],
    ['M',  'André',      'Michel',     '1938-02-20', 'essentiel'],
    ['Mme', 'Paulette',   'Garcia',     '1940-10-14', 'premium'],
    ['M',  'Georges',    'David',      '1937-05-28', 'confort'],
];

$stmtUser = $db->prepare("INSERT INTO users (username, email, password_hash, password_plain, prenom, nom, role, actif, created_at) VALUES (?,?,?,?,?,?,?,1,NOW())");
$stmtRes = $db->prepare("INSERT INTO residents_seniors (user_id, civilite, nom, prenom, date_naissance, niveau_autonomie, regime_alimentaire, actif, created_at) VALUES (?,?,?,?,?,'autonome','normal',1,NOW())");
$stmtOcc = $db->prepare("INSERT INTO occupations_residents (resident_id, lot_id, exploitant_id, date_entree, statut, forfait_type, loyer_mensuel_resident, created_at) VALUES (?,?,1,?,?,?,?,NOW())");

$residentIds = [];
foreach ($residents as $i => $r) {
    if ($i >= count($lotIds)) break; // Pas plus de résidents que de lots
    $lot = $lotIds[$i];
    $username = 'resident_' . strtolower($r[2]) . '_' . ($i + 1);
    $email = strtolower($r[2]) . ($i+1) . '@resident.domitys.fr';

    $stmtUser->execute([$username, $email, $hash, 'Resi1234', $r[1], $r[2], 'locataire_permanent']);
    $userId = (int)$db->lastInsertId();

    $stmtRes->execute([$userId, $r[0], $r[2], $r[1], $r[3]]);
    $residentId = (int)$db->lastInsertId();

    $loyer = match($lot['type']) { 'studio' => 1200, 't2' => 1600, 't3' => 2100, default => 1200 };
    $stmtOcc->execute([$residentId, $lot['id'], '2025-01-15', 'actif', $r[4], $loyer]);

    $residentIds[] = $residentId;
    echo "  Résident: {$r[1]} {$r[2]} -> Lot {$lot['numero']} ({$lot['type']}, forfait {$r[4]})\n";
}
echo count($residentIds) . " résidents créés avec occupations\n\n";

// =============================================
// 3. HÔTES TEMPORAIRES à La Badiane
// =============================================
// Utiliser des lots restants pour 2 hôtes
$stmtHote = $db->prepare("INSERT INTO hotes_temporaires (civilite, nom, prenom, lot_id, residence_id, date_arrivee, date_depart_prevue, statut, regime_repas, ne_pas_deranger, nb_personnes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");

$lotHote1 = $lotIds[count($residentIds)]['id'] ?? null;
$lotHote2 = $lotIds[count($residentIds)+1]['id'] ?? null;

if ($lotHote1) {
    $stmtHote->execute(['M', 'Visiteur', 'Jean', $lotHote1, 61, date('Y-m-d'), date('Y-m-d', strtotime('+5 days')), 'en_cours', 'petit_dejeuner', 0, 1]);
    echo "Hôte 1: Jean Visiteur (lot {$lotIds[count($residentIds)]['numero']})\n";
}
if ($lotHote2) {
    $stmtHote->execute(['Mme', 'Touriste', 'Marie', $lotHote2, 61, date('Y-m-d'), date('Y-m-d', strtotime('+3 days')), 'en_cours', 'pension_complete', 1, 2]);
    echo "Hôte 2: Marie Touriste (lot {$lotIds[count($residentIds)+1]['numero']}, NE PAS DÉRANGER)\n";
}
echo "\n";

// =============================================
// 4. FOURNISSEURS MÉNAGE (nouveaux)
// =============================================
$stmtFourn = $db->prepare("INSERT INTO fournisseurs (nom, siret, adresse, code_postal, ville, telephone, email, type_service, actif) VALUES (?,?,?,?,?,?,?,?,1)");

$fournisseursMenage = [
    ['Initial (Hygiène Pro)', '33445566778899', '15 Rue de l\'Industrie', '69007', 'Lyon', '0478000010', 'commandes@initial.fr', 'hygiene'],
    ['Procter & Gamble Pro', '11223344556677', '3 Avenue des Entreprises', '92100', 'Boulogne', '0140000020', 'pro@pg.com', 'nettoyant'],
];

foreach ($fournisseursMenage as $f) {
    $stmtFourn->execute($f);
}
$initialId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Initial%' LIMIT 1")->fetchColumn();
$pgId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Procter%' LIMIT 1")->fetchColumn();
echo "2 fournisseurs ménage créés (Initial, P&G Pro)\n";

// Lier à La Badiane via rest_fournisseur_residence
$db->prepare("INSERT IGNORE INTO rest_fournisseur_residence (fournisseur_id, residence_id, statut, jour_livraison) VALUES (?,61,'actif','lundi,jeudi')")->execute([$initialId]);
$db->prepare("INSERT IGNORE INTO rest_fournisseur_residence (fournisseur_id, residence_id, statut, jour_livraison) VALUES (?,61,'actif','mardi')")->execute([$pgId]);
// Aussi lier Metro (déjà existant) pour le ménage
$metroId = $db->query("SELECT id FROM fournisseurs WHERE nom LIKE 'Metro%' LIMIT 1")->fetchColumn();
echo "Fournisseurs liés à La Badiane\n\n";

// =============================================
// 5. PRODUITS MÉNAGE (50)
// =============================================
$stmtProd = $db->prepare("INSERT INTO menage_produits (nom, categorie, section, unite, prix_reference, fournisseur_id, marque, conditionnement, actif) VALUES (?,?,?,?,?,?,?,?,1)");

$produits = [
    // Nettoyants (10)
    ['Nettoyant multi-surfaces', 'nettoyant', 'commun', 'litre', 3.50, $pgId, 'Mr. Propre', 'Bidon 5L'],
    ['Nettoyant vitres', 'nettoyant', 'commun', 'litre', 4.20, $pgId, 'Ajax', 'Spray 750ml'],
    ['Nettoyant sol', 'nettoyant', 'interieur', 'litre', 2.80, $pgId, 'St Marc', 'Bidon 5L'],
    ['Nettoyant salle de bain', 'nettoyant', 'interieur', 'litre', 5.50, $pgId, 'Cillit Bang', 'Spray 750ml'],
    ['Nettoyant cuisine', 'nettoyant', 'interieur', 'litre', 4.00, $pgId, 'Cif', 'Spray 750ml'],
    ['Nettoyant WC', 'nettoyant', 'interieur', 'unite', 2.50, $pgId, 'Harpic', 'Flacon 750ml'],
    ['Nettoyant inox', 'nettoyant', 'interieur', 'litre', 6.00, $initialId, null, 'Spray 500ml'],
    ['Dégraissant professionnel', 'nettoyant', 'commun', 'litre', 8.50, $initialId, null, 'Bidon 5L'],
    ['Nettoyant moquette', 'nettoyant', 'interieur', 'litre', 7.00, $initialId, null, 'Bidon 2L'],
    ['Nettoyant extérieur haute pression', 'nettoyant', 'exterieur', 'litre', 12.00, $initialId, 'Kärcher', 'Bidon 5L'],
    // Désinfectants (6)
    ['Désinfectant surfaces', 'desinfectant', 'commun', 'litre', 6.50, $initialId, 'Anios', 'Bidon 5L'],
    ['Gel hydroalcoolique', 'desinfectant', 'commun', 'litre', 8.00, $initialId, null, 'Flacon pompe 1L'],
    ['Désinfectant WC', 'desinfectant', 'interieur', 'unite', 3.50, $pgId, 'Domestos', 'Flacon 1L'],
    ['Désodorisant', 'desinfectant', 'interieur', 'unite', 4.00, $pgId, 'Febreze', 'Spray 400ml'],
    ['Pastilles désinfectantes', 'desinfectant', 'commun', 'boite', 15.00, $initialId, 'Anios', 'Boîte 150'],
    ['Désinfectant poubelles', 'desinfectant', 'exterieur', 'litre', 5.00, $initialId, null, 'Bidon 5L'],
    // Lessives (6)
    ['Lessive liquide', 'lessive', 'laverie', 'litre', 5.50, $pgId, 'Ariel Pro', 'Bidon 10L'],
    ['Adoucissant', 'lessive', 'laverie', 'litre', 3.80, $pgId, 'Lenor', 'Bidon 5L'],
    ['Détachant', 'lessive', 'laverie', 'litre', 7.00, $pgId, 'Vanish', 'Flacon 1L'],
    ['Lessive draps blancs', 'lessive', 'laverie', 'litre', 6.00, $initialId, null, 'Bidon 10L'],
    ['Javellisant', 'lessive', 'laverie', 'litre', 2.50, $pgId, 'Javel La Croix', 'Bidon 5L'],
    ['Assouplissant professionnel', 'lessive', 'laverie', 'litre', 4.50, $initialId, null, 'Bidon 10L'],
    // Matériel (10)
    ['Balai microfibre', 'materiel', 'commun', 'unite', 12.00, $metroId, 'Vileda', null],
    ['Serpillière microfibre', 'materiel', 'interieur', 'unite', 4.50, $metroId, 'Vileda', 'Lot de 3'],
    ['Éponges', 'materiel', 'commun', 'carton', 8.00, $metroId, 'Spontex', 'Pack 10'],
    ['Chiffons microfibre', 'materiel', 'commun', 'carton', 15.00, $metroId, null, 'Pack 20'],
    ['Raclette vitres', 'materiel', 'interieur', 'unite', 6.00, $metroId, null, null],
    ['Brosse WC', 'materiel', 'interieur', 'unite', 3.50, $metroId, null, null],
    ['Seau essoreur', 'materiel', 'commun', 'unite', 25.00, $metroId, 'Vileda', null],
    ['Pelle + balayette', 'materiel', 'commun', 'unite', 5.00, $metroId, null, null],
    ['Gants ménage', 'materiel', 'commun', 'boite', 8.00, $metroId, 'Mapa', 'Boîte 100 paires'],
    ['Chariot de ménage', 'materiel', 'commun', 'unite', 180.00, $initialId, null, null],
    // Sacs poubelle (4)
    ['Sacs poubelle 30L', 'sac_poubelle', 'interieur', 'rouleau', 2.50, $metroId, null, 'Rouleau 50'],
    ['Sacs poubelle 100L', 'sac_poubelle', 'exterieur', 'rouleau', 4.50, $metroId, null, 'Rouleau 25'],
    ['Sacs poubelle 240L', 'sac_poubelle', 'exterieur', 'rouleau', 8.00, $metroId, null, 'Rouleau 10'],
    ['Sacs tri sélectif (jaune)', 'sac_poubelle', 'exterieur', 'rouleau', 3.00, $metroId, null, 'Rouleau 25'],
    // Papier (4)
    ['Papier toilette', 'papier', 'interieur', 'carton', 25.00, $metroId, 'Lotus', 'Carton 96 rouleaux'],
    ['Essuie-tout', 'papier', 'commun', 'carton', 18.00, $metroId, 'Okay', 'Carton 24 rouleaux'],
    ['Mouchoirs', 'papier', 'interieur', 'carton', 12.00, $metroId, 'Lotus', 'Carton 48 boîtes'],
    ['Sacs aspirateur', 'papier', 'interieur', 'boite', 15.00, $metroId, null, 'Boîte 10'],
    // Autre (10)
    ['Produit anti-calcaire', 'autre', 'interieur', 'litre', 5.50, $pgId, 'Viakal', 'Spray 500ml'],
    ['Cire parquet', 'autre', 'interieur', 'litre', 12.00, $initialId, null, 'Bidon 2L'],
    ['Détartrant machine à laver', 'autre', 'laverie', 'unite', 4.00, $pgId, 'Calgon', 'Boîte 45 pastilles'],
    ['Absorbeur d\'humidité', 'autre', 'interieur', 'unite', 6.50, null, 'Rubson', null],
    ['Insecticide', 'autre', 'exterieur', 'unite', 8.00, null, 'Raid', 'Spray 400ml'],
    ['Sel adoucisseur', 'autre', 'laverie', 'kg', 1.50, $metroId, null, 'Sac 25kg'],
    ['Liquide rinçage lave-vaisselle', 'autre', 'interieur', 'litre', 3.00, $pgId, 'Sun', 'Flacon 500ml'],
    ['Détergent lave-linge pro', 'autre', 'laverie', 'litre', 9.00, $initialId, null, 'Bidon 20L'],
    ['Cirage boiserie', 'autre', 'commun', 'litre', 14.00, $initialId, null, 'Bidon 1L'],
    ['Produit dépoussiérant', 'autre', 'interieur', 'unite', 5.00, $pgId, 'Pliz', 'Spray 500ml'],
];

foreach ($produits as $p) { $stmtProd->execute($p); }
echo count($produits) . " produits ménage créés\n\n";

// =============================================
// 6. INVENTAIRE (remplir stock pour La Badiane)
// =============================================
$allProduits = $db->query("SELECT id, nom FROM menage_produits WHERE actif=1")->fetchAll(PDO::FETCH_ASSOC);
$stmtInv = $db->prepare("INSERT IGNORE INTO menage_inventaire (produit_id, residence_id, quantite_stock, seuil_alerte, emplacement) VALUES (?,61,?,?,?)");

$emplacements = ['Local technique RDC', 'Réserve étage 1', 'Réserve étage 2', 'Local laverie', 'Local poubelles'];
$count = 0;
foreach ($allProduits as $p) {
    $stock = rand(3, 30);
    $seuil = max(2, intval($stock * 0.3));
    $empl = $emplacements[array_rand($emplacements)];
    $stmtInv->execute([$p['id'], $stock, $seuil, $empl]);
    $count++;
}
echo "$count produits ajoutés à l'inventaire de La Badiane\n\n";

// =============================================
// 7. SHIFTS PLANNING (pour que la distribution auto fonctionne)
// =============================================
$today = date('Y-m-d');
$stmtShift = $db->prepare("INSERT INTO planning_shifts (user_id, residence_id, titre, date_debut, date_fin, type_shift, statut, created_at) VALUES (?,?,?,?,?,?,?,NOW())");

// Employés ménage intérieur
$menageIntUsers = $db->query("SELECT id, username FROM users WHERE role='menage_interieur' AND actif=1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($menageIntUsers as $u) {
    // Shift aujourd'hui 7h-15h
    $stmtShift->execute([$u['id'], 61, 'Ménage intérieur', "$today 07:00:00", "$today 15:00:00", 'travail', 'planifie']);
    echo "Shift: {$u['username']} -> Badiane {$today} 7h-15h\n";
}

// Employés ménage extérieur
$menageExtUsers = $db->query("SELECT id, username FROM users WHERE role='menage_exterieur' AND actif=1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($menageExtUsers as $u) {
    $stmtShift->execute([$u['id'], 61, 'Ménage extérieur', "$today 06:00:00", "$today 14:00:00", 'travail', 'planifie']);
    echo "Shift: {$u['username']} -> Badiane {$today} 6h-14h\n";
}

// Employé laverie
$laverieUsers = $db->query("SELECT id, username FROM users WHERE role='employe_laverie' AND actif=1")->fetchAll(PDO::FETCH_ASSOC);
foreach ($laverieUsers as $u) {
    $stmtShift->execute([$u['id'], 61, 'Laverie', "$today 08:00:00", "$today 16:00:00", 'travail', 'planifie']);
    echo "Shift: {$u['username']} -> Badiane {$today} 8h-16h\n";
}

// Manager
$db->prepare("INSERT INTO planning_shifts (user_id, residence_id, titre, date_debut, date_fin, type_shift, statut, created_at) VALUES (67,61,'Supervision ménage','$today 07:00:00','$today 17:00:00','travail','planifie',NOW())")->execute();
echo "Shift: chef_menage -> Badiane {$today} 7h-17h\n\n";

// =============================================
// 8. QUELQUES DEMANDES LAVERIE DE TEST
// =============================================
if (!empty($residentIds)) {
    $stmtDem = $db->prepare("INSERT INTO menage_laverie_demandes (residence_id, resident_id, date_demande, type_linge, quantite, service_inclus, prix_unitaire, montant_total, statut) VALUES (?,?,?,?,?,?,?,?,?)");
    // Premium (inclus)
    $stmtDem->execute([61, $residentIds[0], date('Y-m-d'), 'draps_2p', 1, 1, 7.00, 0, 'demandee']);
    // Confort (payant)
    $stmtDem->execute([61, $residentIds[2], date('Y-m-d', strtotime('-1 day')), 'serviettes', 2, 0, 3.00, 6.00, 'en_cours']);
    // Essentiel (payant)
    $stmtDem->execute([61, $residentIds[5], date('Y-m-d', strtotime('-2 days')), 'linge_personnel', 3, 0, 4.00, 12.00, 'prete']);
    echo "3 demandes laverie créées\n";
}

echo "\n=== SEED MÉNAGE COMPLET ===\n";
echo "\nPour tester :\n";
echo "1. Connectez-vous avec chef_menage / Menage1234\n";
echo "2. Ménage > Intérieur > Générer les tâches > Distribuer\n";
echo "3. Connectez-vous avec menage_int1 / Menage1234 pour voir les lots assignés\n";
