<?php

session_start();

header('Content-Type: application/json');


// ===== Sécurité : utilisateur connecté uniquement =====

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'erreur' => 'Non connecté']);
    exit();
}


// ===== Sécurité : vérification CSRF =====

$donnees     = json_decode(file_get_contents('php://input'), true);
$csrf_recu   = $donnees['csrf_token'] ?? '';

if (empty($csrf_recu) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_recu)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'erreur' => 'Requête invalide']);
    exit();
}


// ===== Marque la bannière comme ignorée pour le reste de cette session de connexion =====
// Elle réapparaîtra à la prochaine connexion tant qu'aucun contact de récupération
// n'aura été renseigné (voir Include_general/header.php et traite_conex.php).

$_SESSION['alerte_recup_ignoree'] = true;

echo json_encode(['success' => true]);