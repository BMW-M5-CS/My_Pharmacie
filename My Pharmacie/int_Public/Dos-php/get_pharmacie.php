<?php

require_once 'config.php';
require_once 'fonctions_notation.php';
require_once 'fonctions_horaires.php';

$id_pharmacie = $_GET['id_pharmacie'] ?? null;

if(!$id_pharmacie || !is_numeric($id_pharmacie)){
    http_response_code(400);
    echo json_encode(['erreur' => 'ID Invalide']);
    exit();
}

$sql_pharmacie = "SELECT 
                    id_pharmacie, 
                    nom_pharmacie, 
                    adresse, 
                    ville, 
                    commune, 
                    quartier, 
                    telephone_pharmacie, 
                    heure_ouverture, 
                    heure_fermeture,
                    statut_garde, 
                    latitude, 
                    longitude
                  FROM pharmacies WHERE id_pharmacie = ?";

$stmt = $pdo->prepare($sql_pharmacie);
$stmt->execute([$id_pharmacie]);
$pharmacie = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pharmacie){
    http_response_code(404);
    echo json_encode(['erreur' => 'Pharmacie introuvable']);
    exit();
}

$sql_produits = "SELECT 
                    p.id_produit, 
                    s.id_stock,
                    p.nom_medicament, 
                    p.forme_pharmaceutique,
                    p.prix_unitaire_fcfa
                FROM produits p 
                JOIN stocks s ON s.id_produit = p.id_produit 
                WHERE s.id_pharmacie = ? AND s.quantite_disponible > 0 
                ORDER BY p.nom_medicament ASC";

$stmt2 = $pdo->prepare($sql_produits);
$stmt2->execute([$id_pharmacie]);
$produits = $stmt2->fetchAll(PDO::FETCH_ASSOC);


// ---------------------------- Assurances acceptées par cette pharmacie ----------------------------

$sql_assurances = "SELECT a.nom_assurance, a.logo_assurance
                    FROM pharmacie_assurances pa
                    JOIN assurances a ON a.id_assurance = pa.id_assurance
                    WHERE pa.id_pharmacie = ?
                    ORDER BY a.nom_assurance ASC";

$stmt_assurances = $pdo->prepare($sql_assurances);
$stmt_assurances->execute([$id_pharmacie]);
$assurances = $stmt_assurances->fetchAll(PDO::FETCH_ASSOC);


// ---------------------------- Moyenne et nombre d'avis ----------------------------

$sql_moyenne = "SELECT COALESCE(AVG(note), 0) AS note_moyenne, COUNT(*) AS nombre_avis
                 FROM avis WHERE id_pharmacie = ?";
$stmt_moyenne = $pdo->prepare($sql_moyenne);
$stmt_moyenne->execute([$id_pharmacie]);
$moyenne = $stmt_moyenne->fetch(PDO::FETCH_ASSOC);


// ---------------------------- Taux de disponibilité ----------------------------

$top_produits = recupererTopProduitsReserves($pdo);

if (!empty($top_produits)) {

    $placeholders = implode(',', array_fill(0, count($top_produits), '?'));

    $sql_taux = "SELECT COUNT(*) * 100.0 / ? AS taux
                 FROM stocks
                 WHERE id_pharmacie = ? AND quantite_disponible > 0 AND id_produit IN ($placeholders)";

    $stmt_taux = $pdo->prepare($sql_taux);
    $stmt_taux->execute(array_merge([count($top_produits)], [$id_pharmacie], $top_produits));

} else {

    // Pas encore assez de réservations en base : repli sur le pourcentage global du catalogue
    $sql_taux = "SELECT COUNT(*) FILTER (WHERE quantite_disponible > 0) * 100.0 / NULLIF(COUNT(*), 0) AS taux
                 FROM stocks WHERE id_pharmacie = ?";

    $stmt_taux = $pdo->prepare($sql_taux);
    $stmt_taux->execute([$id_pharmacie]);
}

$taux_disponibilite = (float) ($stmt_taux->fetch(PDO::FETCH_ASSOC)['taux'] ?? 0);


$pharmacie['produits']            = $produits;
$pharmacie['assurances']          = $assurances;
$pharmacie['note_moyenne']        = round((float) $moyenne['note_moyenne'], 1);
$pharmacie['nombre_avis']         = (int) $moyenne['nombre_avis'];
$pharmacie['taux_disponibilite']  = round($taux_disponibilite);
$pharmacie['recommandee']         = estPharmacieRecommandee((float) $moyenne['note_moyenne'], $taux_disponibilite);
$pharmacie['etoiles_html']        = afficherEtoiles((float) $moyenne['note_moyenne']);
$pharmacie['statut_calcule']      = calculerStatutPharmacie(
    (bool) $pharmacie['statut_garde'],
    $pharmacie['heure_ouverture'],
    $pharmacie['heure_fermeture']
);

header('Content-Type: application/json');
echo json_encode($pharmacie, JSON_UNESCAPED_UNICODE);