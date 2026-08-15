<?php

require_once 'config.php';
require_once 'fonctions_horaires.php';

header('Content-Type: application/json');

$recherche = trim($_GET['recherche'] ?? '');

if ($recherche === '') {
    echo json_encode([]);
    exit();
}


// Une pharmacie peut apparaître plusieurs fois ici si plusieurs de ses produits
// correspondent à la recherche (ex: "Paracétamol" et "Paracétamol Effervescent") ;
// le regroupement par pharmacie se fait côté client. On ne renvoie jamais la
// quantité en stock, seulement le fait qu'il y en ait (> 0).

$sql = "SELECT p.id_pharmacie, p.nom_pharmacie, p.adresse, p.ville, p.commune, p.quartier,
               p.latitude, p.longitude, p.statut_garde,
               p.heure_ouverture, p.heure_fermeture, p.telephone_pharmacie,
               pr.id_produit, pr.nom_medicament, pr.forme_pharmaceutique, pr.prix_unitaire_fcfa, s.id_stock
        FROM pharmacies p
        JOIN stocks s   ON s.id_pharmacie = p.id_pharmacie
        JOIN produits pr ON pr.id_produit = s.id_produit
        WHERE pr.nom_medicament ILIKE ? AND s.quantite_disponible > 0
        ORDER BY p.nom_pharmacie ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['%' . $recherche . '%']);
$resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ---------------------------- Assurances acceptées par les pharmacies trouvées ----------------------------
// Nécessaire pour permettre au client de filtrer/mettre en avant selon l'assurance choisie,
// sans avoir à refaire un appel serveur à chaque changement de choix d'assurance

$id_pharmacies_liste = array_unique(array_column($resultats, 'id_pharmacie'));

$assurances_par_pharmacie = [];

if (!empty($id_pharmacies_liste)) {

    $marqueurs = implode(',', array_fill(0, count($id_pharmacies_liste), '?'));

    $sql_assurances = "SELECT pa.id_pharmacie, a.nom_assurance, a.logo_assurance
                        FROM pharmacie_assurances pa
                        JOIN assurances a ON a.id_assurance = pa.id_assurance
                        WHERE pa.id_pharmacie IN ($marqueurs)";

    $stmt_assurances = $pdo->prepare($sql_assurances);
    $stmt_assurances->execute(array_values($id_pharmacies_liste));

    foreach ($stmt_assurances->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $assurances_par_pharmacie[$ligne['id_pharmacie']][] = [
            'nom_assurance'  => $ligne['nom_assurance'],
            'logo_assurance' => $ligne['logo_assurance'],
        ];
    }
}

foreach ($resultats as &$ligne_resultat) {
    $ligne_resultat['assurances'] = $assurances_par_pharmacie[$ligne_resultat['id_pharmacie']] ?? [];
    $ligne_resultat['statut_calcule'] = calculerStatutPharmacie(
        (bool) $ligne_resultat['statut_garde'],
        $ligne_resultat['heure_ouverture'],
        $ligne_resultat['heure_fermeture']
    );
}
unset($ligne_resultat);


echo json_encode($resultats, JSON_UNESCAPED_UNICODE);