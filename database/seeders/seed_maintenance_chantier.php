<?php
/**
 * Seeder : 7 fournisseurs chantier/maintenance technique + 30 produits + liens
 *
 * Idempotent : si un fournisseur ou produit avec le même nom existe déjà,
 * il est réutilisé (pas de doublon).
 *
 * Usage :  php database/seeders/seed_maintenance_chantier.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=synd_gest;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ─── 7 FOURNISSEURS CHANTIER ────────────────────────────────────────
$fournisseurs = [
    ['nom' => 'Rexel France',         'siret' => '40903930400123', 'adresse' => '15 rue de l\'Artisanat',     'cp' => '75019', 'ville' => 'Paris',     'tel' => '01 42 03 50 00', 'email' => 'pro@rexel.fr',          'contact' => 'Marc Dubois',     'type_service' => 'travaux_elec'],
    ['nom' => 'Cedeo Plomberie',      'siret' => '32258301700456', 'adresse' => '8 zone industrielle Sud',     'cp' => '69100', 'ville' => 'Villeurbanne','tel' => '04 78 84 12 30','email' => 'commande@cedeo.fr',     'contact' => 'Sophie Martin',  'type_service' => 'travaux_plomberie'],
    ['nom' => 'Point P Matériaux',    'siret' => '54080535400789', 'adresse' => '42 boulevard de l\'Industrie','cp' => '13013', 'ville' => 'Marseille', 'tel' => '04 91 88 22 00', 'email' => 'pro13@pointp.fr',       'contact' => 'Karim Benali',   'type_service' => 'travaux_elec,travaux_plomberie,autre'],
    ['nom' => 'Tollens Peintures',    'siret' => '78901234500321', 'adresse' => '3 rue des Couleurs',          'cp' => '92110', 'ville' => 'Clichy',    'tel' => '01 47 56 78 90', 'email' => 'pro@tollens.com',       'contact' => 'Émilie Renaud',  'type_service' => 'autre'],
    ['nom' => 'Aqua Diffusion',       'siret' => '41782456700234', 'adresse' => '7 avenue de la Piscine',      'cp' => '06200', 'ville' => 'Nice',      'tel' => '04 93 71 45 88', 'email' => 'commande@aqua-diff.fr','contact' => 'Patrice Olivieri','type_service' => 'piscine'],
    ['nom' => 'Otis Maintenance',     'siret' => '54200002000567', 'adresse' => '1 parc Otis Innovation',      'cp' => '92500', 'ville' => 'Rueil-Malmaison','tel' => '01 47 32 50 00','email' => 'maintenance@otis.fr','contact' => 'Service technique','type_service' => 'autre'],
    ['nom' => 'Würth France',         'siret' => '78911230400891', 'adresse' => '12 rue de la Quincaillerie',  'cp' => '67100', 'ville' => 'Strasbourg','tel' => '03 88 64 70 00', 'email' => 'pro@wurth.fr',          'contact' => 'Thomas Kessler', 'type_service' => 'travaux_elec,travaux_plomberie,autre'],
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

// Lier les 7 fournisseurs à TOUTES les résidences actives via fournisseur_residence
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

// ─── SPÉCIALITÉS REQUISES ──────────────────────────────────────────
$specs = $pdo->query("SELECT slug, id FROM specialites")->fetchAll(PDO::FETCH_KEY_PAIR);

// ─── 30 PRODUITS MAINTENANCE ───────────────────────────────────────
// Format : [nom, specialite_slug, categorie, type, unite, prix_unitaire, fournisseur_prefere_nom]
$produits = [
    // ─── ÉLECTRICITÉ (5)
    ['Disjoncteur 16A modulaire',            'electricite', 'piece_detachee',     'produit', 'pièce', 12.50,  'Rexel France'],
    ['Disjoncteur différentiel 30mA 25A',    'electricite', 'piece_detachee',     'produit', 'pièce', 38.90,  'Rexel France'],
    ['Câble électrique H07VK 2.5mm² (rouleau 100m)', 'electricite', 'consommable', 'produit', 'rouleau', 89.00, 'Rexel France'],
    ['Prise de courant 16A NF type E',       'electricite', 'piece_detachee',     'produit', 'pièce', 4.20,   'Rexel France'],
    ['Tableau électrique 3 rangées 13 modules','electricite', 'piece_detachee',   'produit', 'pièce', 75.00,  'Rexel France'],

    // ─── PLOMBERIE (5)
    ['Tube cuivre Ø16mm (barre 5m)',         'plomberie',   'consommable',        'produit', 'barre', 32.50,  'Cedeo Plomberie'],
    ['Raccord laiton 3/4" mâle-femelle',     'plomberie',   'piece_detachee',     'produit', 'pièce', 5.80,   'Cedeo Plomberie'],
    ['Robinet thermostatique radiateur',     'plomberie',   'piece_detachee',     'produit', 'pièce', 28.00,  'Cedeo Plomberie'],
    ['Mitigeur lavabo chromé',               'plomberie',   'piece_detachee',     'produit', 'pièce', 65.00,  'Cedeo Plomberie'],
    ['Joint torique caoutchouc (sachet 50)', 'plomberie',   'consommable',        'produit', 'sachet', 8.90,  'Cedeo Plomberie'],

    // ─── TRAVAUX (gros œuvre / second œuvre) (5)
    ['Sac ciment Portland 35 kg',            'travaux',     'consommable',        'produit', 'sac',   9.50,   'Point P Matériaux'],
    ['Sac plâtre allégé 25 kg',              'travaux',     'consommable',        'produit', 'sac',   8.00,   'Point P Matériaux'],
    ['Plaque de plâtre BA13 (250×120cm)',    'travaux',     'consommable',        'produit', 'plaque', 6.80,  'Point P Matériaux'],
    ['Vis à bois inox 4×40mm (boîte 200)',   'travaux',     'consommable',        'produit', 'boîte', 12.40,  'Würth France'],
    ['Cheville à frapper 8×60mm (sachet 100)','travaux',    'consommable',        'produit', 'sachet', 9.20,  'Würth France'],

    // ─── PEINTURE (5)
    ['Peinture acrylique mate blanche 10L',  'peinture',    'consommable',        'produit', 'pot',   78.00,  'Tollens Peintures'],
    ['Peinture acrylique satinée 5L',        'peinture',    'consommable',        'produit', 'pot',   52.00,  'Tollens Peintures'],
    ['Sous-couche universelle 10L',          'peinture',    'consommable',        'produit', 'pot',   62.00,  'Tollens Peintures'],
    ['Rouleau peinture 18cm + manche',       'peinture',    'outillage_main',     'outil',   'pièce', 7.50,   'Tollens Peintures'],
    ['Bâche de protection 4×5m',             'peinture',    'consommable',        'produit', 'pièce', 11.00,  'Tollens Peintures'],

    // ─── PISCINE (4)
    ['Chlore choc poudre 5 kg',              'piscine',     'produit_chimique',   'produit', 'seau',  45.00,  'Aqua Diffusion'],
    ['Chlore lent galets 5 kg',              'piscine',     'produit_chimique',   'produit', 'seau',  39.00,  'Aqua Diffusion'],
    ['Correcteur pH+ poudre 5 kg',           'piscine',     'produit_chimique',   'produit', 'seau',  21.00,  'Aqua Diffusion'],
    ['Floculant clarifiant 5L',              'piscine',     'produit_chimique',   'produit', 'bidon', 28.00,  'Aqua Diffusion'],

    // ─── ASCENSEUR (2)
    ['Câble traction acier 8mm (10m)',       'ascenseur',   'piece_detachee',     'produit', 'rouleau', 195.00,'Otis Maintenance'],
    ['Galet de roulement standard',          'ascenseur',   'piece_detachee',     'produit', 'pièce',  82.00, 'Otis Maintenance'],

    // ─── OUTILLAGE GÉNÉRIQUE (2)
    ['Perceuse-visseuse 18V Li-Ion',         null,          'outillage_motorise', 'outil',   'pièce', 145.00, 'Würth France'],
    ['Multimètre numérique électricien',     'electricite', 'outillage_main',     'outil',   'pièce', 68.00,  'Rexel France'],

    // ─── EPI (2)
    ['Casque de chantier blanc',             null,          'epi',                'produit', 'pièce', 12.50,  'Würth France'],
    ['Gants de protection cuir taille L',    null,          'epi',                'produit', 'paire', 8.90,   'Würth France'],
];

$nbCrees = 0; $nbLiens = 0;
foreach ($produits as $p) {
    [$nom, $specSlug, $cat, $type, $unite, $prix, $founNom] = $p;

    // Anti-doublon
    $stmt = $pdo->prepare("SELECT id FROM maintenance_produits WHERE nom = ?");
    $stmt->execute([$nom]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $prodId = (int)$existing;
        echo "  = Produit déjà présent : $nom\n";
    } else {
        $specId = $specSlug ? ($specs[$specSlug] ?? null) : null;
        $sql = "INSERT INTO maintenance_produits (nom, specialite_id, categorie, type, unite, prix_unitaire, actif) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $pdo->prepare($sql)->execute([$nom, $specId, $cat, $type, $unite, $prix]);
        $prodId = (int)$pdo->lastInsertId();
        $nbCrees++;
        echo "  + $nom\n";
    }

    // Lien produit ↔ fournisseur préféré
    $founId = $idsFournisseurs[$founNom] ?? null;
    if ($founId) {
        $stmt = $pdo->prepare("SELECT id FROM produit_fournisseurs WHERE produit_module = 'maintenance' AND produit_id = ? AND fournisseur_id = ?");
        $stmt->execute([$prodId, $founId]);
        if (!$stmt->fetchColumn()) {
            $pdo->prepare("INSERT INTO produit_fournisseurs (produit_module, produit_id, fournisseur_id, prix_unitaire_specifique, fournisseur_prefere) VALUES ('maintenance', ?, ?, ?, 1)")
                ->execute([$prodId, $founId, $prix]);
            $nbLiens++;
        }
    }
}

echo "\n✓ Produits créés : $nbCrees / " . count($produits) . "\n";
echo "✓ Liens produit-fournisseur créés : $nbLiens\n";

echo "\n=== ÉTAT FINAL ===\n";
echo 'Fournisseurs total : ' . $pdo->query('SELECT COUNT(*) FROM fournisseurs')->fetchColumn() . "\n";
echo 'Liens fournisseur_residence : ' . $pdo->query('SELECT COUNT(*) FROM fournisseur_residence')->fetchColumn() . "\n";
echo 'Produits maintenance : ' . $pdo->query('SELECT COUNT(*) FROM maintenance_produits')->fetchColumn() . "\n";
echo 'Liens produit_fournisseurs maintenance : ' . $pdo->query("SELECT COUNT(*) FROM produit_fournisseurs WHERE produit_module='maintenance'")->fetchColumn() . "\n";
