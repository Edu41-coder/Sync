<?php
/**
 * Seeder : 4 fournisseurs jardinage + 30 produits/outils + liens
 *
 * Idempotent.
 *
 * Usage :  php database/seeders/seed_jardinage.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=synd_gest;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ─── 4 FOURNISSEURS JARDINAGE ──────────────────────────────────────
$fournisseurs = [
    ['nom' => 'Truffaut Pro',           'siret' => '57215024500123', 'adresse' => '12 route des Pépinières',  'cp' => '78400', 'ville' => 'Chatou',         'tel' => '01 30 71 50 00', 'email' => 'pro@truffaut.com',       'contact' => 'Caroline Vigneron', 'type_service' => 'jardinage'],
    ['nom' => 'Botanic Pépinières',     'siret' => '40195830200456', 'adresse' => '8 zone industrielle Verte', 'cp' => '38330', 'ville' => 'Saint-Ismier',  'tel' => '04 76 52 80 00', 'email' => 'commande@botanic.com',  'contact' => 'Julien Roussel',    'type_service' => 'jardinage'],
    ['nom' => 'Jardiland Distribution', 'siret' => '32815602100789', 'adresse' => '15 avenue des Floralies',   'cp' => '94300', 'ville' => 'Vincennes',      'tel' => '01 41 74 30 00', 'email' => 'pro@jardiland.com',     'contact' => 'Sandra Mercier',    'type_service' => 'jardinage'],
    ['nom' => 'Stihl France',           'siret' => '78924560300321', 'adresse' => '3 parc d\'activités Forestier','cp' => '67800','ville' => 'Bischheim',      'tel' => '03 88 18 60 00', 'email' => 'pro@stihl.fr',          'contact' => 'Marc Wagner',       'type_service' => 'jardinage'],
];

$idsFournisseurs = [];
foreach ($fournisseurs as $f) {
    $stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE nom = ?");
    $stmt->execute([$f['nom']]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $idsFournisseurs[$f['nom']] = (int)$existing;
        echo "  = Fournisseur déjà présent : {$f['nom']} (id=$existing)\n";
        continue;
    }
    $sql = "INSERT INTO fournisseurs (nom, siret, adresse, code_postal, ville, telephone, email, contact_nom, type_service, actif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $pdo->prepare($sql)->execute([$f['nom'], $f['siret'], $f['adresse'], $f['cp'], $f['ville'], $f['tel'], $f['email'], $f['contact'], $f['type_service']]);
    $newId = (int)$pdo->lastInsertId();
    $idsFournisseurs[$f['nom']] = $newId;
    echo "  + Fournisseur créé : {$f['nom']} (id=$newId)\n";
}

// Lier les 4 fournisseurs à TOUTES les résidences actives
$residencesIds = $pdo->query("SELECT id FROM coproprietees WHERE actif = 1")->fetchAll(PDO::FETCH_COLUMN);
$nbLiens = 0;
foreach ($idsFournisseurs as $nom => $founId) {
    foreach ($residencesIds as $resId) {
        $stmt = $pdo->prepare("SELECT id FROM fournisseur_residence WHERE fournisseur_id = ? AND residence_id = ?");
        $stmt->execute([$founId, $resId]);
        if ($stmt->fetchColumn()) continue;
        $pdo->prepare("INSERT INTO fournisseur_residence (fournisseur_id, residence_id, statut) VALUES (?, ?, 'actif')")->execute([$founId, $resId]);
        $nbLiens++;
    }
}
echo "\n✓ Liens fournisseur × résidence créés : $nbLiens\n";

// ─── 30 PRODUITS / OUTILS JARDINAGE ────────────────────────────────
// Format : [nom, categorie, type, unite, prix, marque, bio, fournisseur_prefere]
$produits = [
    // ─── ENGRAIS / TERREAU (5)
    ['Engrais universel granulés 5 kg',         'engrais',       'produit', 'sac',     12.50, 'Or Brun',           1, 'Botanic Pépinières'],
    ['Engrais gazon longue durée 10 kg',        'engrais',       'produit', 'sac',     22.00, 'Compo',             0, 'Truffaut Pro'],
    ['Terreau universel 70L',                   'terreau',       'produit', 'sac',     14.00, 'Or Brun',           1, 'Botanic Pépinières'],
    ['Terreau plantation arbustes 60L',         'terreau',       'produit', 'sac',     16.50, 'Vilmorin',          1, 'Truffaut Pro'],
    ['Compost végétal 40L',                     'terreau',       'produit', 'sac',     9.80,  'Or Brun',           1, 'Botanic Pépinières'],

    // ─── SEMENCES / PLANTS (5)
    ['Gazon rustique sportif 5 kg',             'semence',       'produit', 'sac',     35.00, 'Vilmorin',          0, 'Truffaut Pro'],
    ['Gazon ombre 1 kg',                        'semence',       'produit', 'sac',     14.50, 'Vilmorin',          0, 'Truffaut Pro'],
    ['Mélange fleurs vivaces 100g',             'semence',       'produit', 'piece',   8.90,  'Botanic',           1, 'Botanic Pépinières'],
    ['Lavande angustifolia (godet 9cm)',        'plant',         'produit', 'piece',   3.50,  'Pépinière locale',  1, 'Botanic Pépinières'],
    ['Rosier buisson rouge (conteneur 3L)',     'plant',         'produit', 'piece',   12.00, 'Meilland',          0, 'Jardiland Distribution'],

    // ─── PHYTOSANITAIRE (4)
    ['Anti-mousse gazon concentré 1L',          'phytosanitaire','produit', 'litre',   18.00, 'Solabiol',          1, 'Botanic Pépinières'],
    ['Désherbant total naturel 5L',             'phytosanitaire','produit', 'bidon',   24.00, 'Solabiol',          1, 'Botanic Pépinières'],
    ['Insecticide bio pucerons spray 750ml',    'phytosanitaire','produit', 'ml',      9.50,  'Naturen',           1, 'Truffaut Pro'],
    ['Bouillie bordelaise 800g',                'phytosanitaire','produit', 'sac',     11.00, 'Compo',             0, 'Jardiland Distribution'],

    // ─── ARROSAGE (4)
    ['Tuyau d\'arrosage 25m + raccords',        'arrosage',      'produit', 'rouleau', 38.00, 'Gardena',           0, 'Jardiland Distribution'],
    ['Programmateur d\'arrosage 2 voies',       'arrosage',      'produit', 'piece',   89.00, 'Gardena',           0, 'Jardiland Distribution'],
    ['Lance d\'arrosage multi-jets',            'arrosage',      'produit', 'piece',   18.50, 'Gardena',           0, 'Jardiland Distribution'],
    ['Goutteurs micro-arrosage (sachet 100)',   'arrosage',      'produit', 'piece',   14.00, 'Rain Bird',         0, 'Jardiland Distribution'],

    // ─── OUTILLAGE MAIN (5)
    ['Sécateur professionnel à enclume',        'outillage_main','outil',   'piece',   42.00, 'Felco',             0, 'Stihl France'],
    ['Bêche manche bois fibres',                'outillage_main','outil',   'piece',   28.00, 'Spear & Jackson',   0, 'Truffaut Pro'],
    ['Râteau acier 14 dents',                   'outillage_main','outil',   'piece',   22.00, 'Bahco',             0, 'Truffaut Pro'],
    ['Pelle pointue manche bois',               'outillage_main','outil',   'piece',   24.50, 'Spear & Jackson',   0, 'Truffaut Pro'],
    ['Cisaille à haie manuelle',                'outillage_main','outil',   'piece',   38.00, 'Bahco',             0, 'Stihl France'],

    // ─── OUTILLAGE MOTORISÉ (3)
    ['Tondeuse thermique tractée 53cm',         'outillage_motorise','outil','piece',  549.00,'Stihl',             0, 'Stihl France'],
    ['Taille-haie thermique 60cm',              'outillage_motorise','outil','piece',  389.00,'Stihl HS-46',       0, 'Stihl France'],
    ['Souffleur thermique à dos',               'outillage_motorise','outil','piece',  445.00,'Stihl BR 200',      0, 'Stihl France'],

    // ─── PROTECTION (2)
    ['Voile d\'hivernage 1.5×10m',              'protection',    'produit', 'rouleau', 18.00, 'Nortène',           0, 'Botanic Pépinières'],
    ['Filet brise-vue vert 1.5×10m',            'protection',    'produit', 'rouleau', 32.00, 'Nortène',           0, 'Botanic Pépinières'],

    // ─── CONSOMMABLE (2)
    ['Sacs de jardin 100L (lot de 10)',         'consommable',   'produit', 'piece',   12.00, 'Tubi',              0, 'Jardiland Distribution'],
    ['Bidon huile 2 temps moteur 1L',           'consommable',   'produit', 'litre',   14.50, 'Stihl',             0, 'Stihl France'],
];

$nbCrees = 0; $nbLinks = 0;
foreach ($produits as $p) {
    [$nom, $cat, $type, $unite, $prix, $marque, $bio, $founNom] = $p;

    $stmt = $pdo->prepare("SELECT id FROM jardin_produits WHERE nom = ?");
    $stmt->execute([$nom]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $prodId = (int)$existing;
        echo "  = Produit déjà présent : $nom\n";
    } else {
        $sql = "INSERT INTO jardin_produits (nom, categorie, type, unite, prix_unitaire, marque, bio, actif) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $pdo->prepare($sql)->execute([$nom, $cat, $type, $unite, $prix, $marque, $bio]);
        $prodId = (int)$pdo->lastInsertId();
        $nbCrees++;
        echo "  + $nom\n";
    }

    // Lien produit ↔ fournisseur préféré
    $founId = $idsFournisseurs[$founNom] ?? null;
    if ($founId) {
        $stmt = $pdo->prepare("SELECT id FROM produit_fournisseurs WHERE produit_module = 'jardinage' AND produit_id = ? AND fournisseur_id = ?");
        $stmt->execute([$prodId, $founId]);
        if (!$stmt->fetchColumn()) {
            $pdo->prepare("INSERT INTO produit_fournisseurs (produit_module, produit_id, fournisseur_id, prix_unitaire_specifique, fournisseur_prefere) VALUES ('jardinage', ?, ?, ?, 1)")
                ->execute([$prodId, $founId, $prix]);
            $nbLinks++;
        }
    }
}

echo "\n✓ Produits créés : $nbCrees / " . count($produits) . "\n";
echo "✓ Liens produit-fournisseur créés : $nbLinks\n";

echo "\n=== ÉTAT FINAL ===\n";
echo 'Fournisseurs total : ' . $pdo->query('SELECT COUNT(*) FROM fournisseurs')->fetchColumn() . "\n";
echo 'Produits jardinage : ' . $pdo->query('SELECT COUNT(*) FROM jardin_produits')->fetchColumn() . "\n";
echo 'Liens produit_fournisseurs jardinage : ' . $pdo->query("SELECT COUNT(*) FROM produit_fournisseurs WHERE produit_module='jardinage'")->fetchColumn() . "\n";
