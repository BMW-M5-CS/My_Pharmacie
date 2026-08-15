<?php

require_once 'config.php';
require_once 'fonctions_horaires.php';

$id_produit = $_GET['id_produit'] ?? null;

if (!$id_produit || !is_numeric($id_produit)){
    http_response_code(400);
    echo json_encode(['erreur' => 'ID invalide']);
    exit();
}

$sql_produit = "SELECT
                id_produit,
                nom_medicament,
                nom_generique,
                forme_pharmaceutique,
                dosage,
                conditionnement,
                description,
                prix_unitaire_fcfa
            FROM produits WHERE id_produit = ?";

$stmt = $pdo->prepare($sql_produit);
$stmt ->execute([$id_produit]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$produit){
    http_response_code(404);
    echo json_encode(['erreur' => 'Produit introuvable']);
    exit();
}

$sql_pharmacies = "SELECT
                     p.id_pharmacie,
                     p.nom_pharmacie, 
                     s.id_stock,
                     p.adresse,
                     p.ville,
                     p.commune,
                     p.quartier,
                     p.telephone_pharmacie,
                     p.heure_ouverture,
                     p.heure_fermeture,
                     p.statut_garde,
                     p.latitude,
                     p.longitude
                FROM pharmacies p JOIN stocks s ON s.id_pharmacie = p.id_pharmacie 
                WHERE s.id_produit = ? AND s.quantite_disponible > 0
                ORDER BY p.nom_pharmacie ASC";

$stmt2 = $pdo->prepare($sql_pharmacies);
$stmt2->execute([$id_produit]);
$id_pharmacies = $stmt2->fetchAll(PDO::FETCH_ASSOC);


// ---------------------------- Récupération des assurances acceptées par ces pharmacies ----------------------------
// Requête séparée plutôt qu'une jointure directe : évite de dupliquer une ligne pharmacie
// par assurance acceptée, et reste simple à rattacher ensuite en PHP

$id_pharmacies_liste = array_column($id_pharmacies, 'id_pharmacie');

$assurances_par_pharmacie = [];

if (!empty($id_pharmacies_liste)) {

    $marqueurs = implode(',', array_fill(0, count($id_pharmacies_liste), '?'));

    $sql_assurances = "SELECT
                            pa.id_pharmacie,
                            a.nom_assurance,
                            a.logo_assurance
                        FROM pharmacie_assurances pa
                        JOIN assurances a ON a.id_assurance = pa.id_assurance
                        WHERE pa.id_pharmacie IN ($marqueurs)";

    $stmt3 = $pdo->prepare($sql_assurances);
    $stmt3->execute($id_pharmacies_liste);

    foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $assurances_par_pharmacie[$ligne['id_pharmacie']][] = [
            'nom_assurance'  => $ligne['nom_assurance'],
            'logo_assurance' => $ligne['logo_assurance'],
        ];
    }
}

foreach ($id_pharmacies as &$pharmacie) {
    $pharmacie['assurances'] = $assurances_par_pharmacie[$pharmacie['id_pharmacie']] ?? [];
    $pharmacie['statut_calcule'] = calculerStatutPharmacie(
        (bool) $pharmacie['statut_garde'],
        $pharmacie['heure_ouverture'],
        $pharmacie['heure_fermeture']
    );
}
unset($pharmacie);


$produit['pharmacies'] = $id_pharmacies;

header('Content-Type: application/json');
echo json_encode($produit, JSON_UNESCAPED_UNICODE);

?>