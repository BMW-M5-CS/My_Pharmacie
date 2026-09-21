<?php

require_once '../../int_Public/Dos-php/config.php';
require_once '../../int_Public/Dos-php/mailer_config.php';
require_once '../../Include_general/config_fonctionnalites.php';

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

$sql_verif = "SELECT r.statut, s.id_pharmacie
              FROM reservations r
              JOIN stocks s ON s.id_stock = r.id_stock
              WHERE (r.code_reservation = ? OR r.groupe_demande = ?)
                AND r.id_user = ?
                AND r.statut IN ('demandee', 'confirmee')";

$stmt_verif = $pdo->prepare($sql_verif);
$stmt_verif->execute([$identifiant, $identifiant, $id_user]);
$lignes_a_annuler = $stmt_verif->fetchAll(PDO::FETCH_ASSOC);

if (empty($lignes_a_annuler)) {

    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Réservation introuvable ou déjà traitée.']);
    exit();
}

$statut_avant = $lignes_a_annuler[0]['statut'];
$id_pharmacie = $lignes_a_annuler[0]['id_pharmacie'];


// ===== Annulation de toutes les lignes du groupe =====

$sql_annuler = "UPDATE reservations
                SET statut = 'annulee'
                WHERE (code_reservation = ? OR groupe_demande = ?)
                  AND id_user = ?
                  AND statut IN ('demandee', 'confirmee')";

$stmt_annuler = $pdo->prepare($sql_annuler);
$stmt_annuler->execute([$identifiant, $identifiant, $id_user]);

// ===== Notification au(x) pharmacien(s) — "best effort", n'empêche jamais l'annulation elle-même =====
$sql_admins = "SELECT a.email, a.prenom, p.nom_pharmacie
               FROM administrateurs a
               JOIN pharmacies p ON p.id_pharmacie = a.id_pharmacie
               WHERE a.id_pharmacie = ? AND a.statut = 'actif'";
$stmt_admins = $pdo->prepare($sql_admins);
$stmt_admins->execute([$id_pharmacie]);
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

foreach ($admins as $admin) {

    $sujet = "Réservation annulée par le client — " . $admin['nom_pharmacie'];
    $corps = "
        <p>Bonjour " . htmlspecialchars($admin['prenom']) . ",</p>
        <p>Un client vient d'annuler " . ($statut_avant === 'confirmee' ? "une réservation déjà confirmée" : "une demande de réservation en attente") . " pour " . htmlspecialchars($admin['nom_pharmacie']) . ".</p>
        " . ($statut_avant === 'confirmee' ? "<p>Le produit mis de côté peut être remis en vente.</p>" : "") . "
        <p><a href=\"" . SITE_URL . "/int_Admin/Dos-page/demandes_reservation.php\">Voir mon espace pharmacien</a></p>
    ";

    envoyerEmail($admin['email'], $sujet, $corps);
}

echo json_encode(['succes' => true, 'message' => 'Réservation annulée avec succès.']);
