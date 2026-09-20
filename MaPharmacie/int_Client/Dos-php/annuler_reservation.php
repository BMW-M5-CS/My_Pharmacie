<?php

require_once '../../int_Public/Dos-php/config.php';

header('Content-Type: application/json');


// ===== Vérification de la session utilisateur =====

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);
    echo json_encode(['succes' => false, 'message' => 'Non connecté.']);
    exit();
}

$id_user = $_SESSION['user_id'];


// ===== Lecture des données envoyées =====

$donnees = json_decode(file_get_contents('php://input'), true);


// ===== Vérification du jeton CSRF =====

if (!isset($donnees['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $donnees['csrf_token'])) {

    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Requête invalide, veuillez rafraîchir la page.']);
    exit();
}

// Une demande pas encore confirmée n'a pas de code_reservation : on accepte
// aussi de la retrouver par son groupe_demande.
$identifiant = trim($donnees['identifiant'] ?? '');

if (empty($identifiant)) {

    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Réservation à annuler introuvable.']);
    exit();
}


// ===== Vérification : la réservation appartient bien à cet utilisateur ET peut encore être annulée =====
// Une demande en attente de validation ("demandee") ou déjà confirmée ("confirmee")
// peut toujours être annulée par le client — pas les autres statuts.

$sql_verif = "SELECT COUNT(*) FROM reservations
              WHERE (code_reservation = ? OR groupe_demande = ?)
                AND id_user = ?
                AND statut IN ('demandee', 'confirmee')";

$stmt_verif = $pdo->prepare($sql_verif);
$stmt_verif->execute([$identifiant, $identifiant, $id_user]);
$nb = (int) $stmt_verif->fetchColumn();

if ($nb === 0) {

    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Réservation introuvable ou déjà traitée.']);
    exit();
}


// ===== Annulation de toutes les lignes du groupe =====

$sql_annuler = "UPDATE reservations
                SET statut = 'annulee'
                WHERE (code_reservation = ? OR groupe_demande = ?)
                  AND id_user = ?
                  AND statut IN ('demandee', 'confirmee')";

$stmt_annuler = $pdo->prepare($sql_annuler);
$stmt_annuler->execute([$identifiant, $identifiant, $id_user]);

echo json_encode(['succes' => true, 'message' => 'Réservation annulée avec succès.']);
