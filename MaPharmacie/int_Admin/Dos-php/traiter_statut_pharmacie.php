<?php

require_once '../../Include_general/session_init.php';
require_once '../../int_Public/Dos-php/config.php';
require_once '../Dos-php/verifier_session_admin.php';

header('Content-Type: application/json');

// Réservé au compte administration plateforme.
if ($_SESSION['admin_role_nom'] !== 'administration_plateforme') {
    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Action non autorisée.']);
    exit();
}

$donnees = json_decode(file_get_contents('php://input'), true);

if (!isset($donnees['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $donnees['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Requête invalide, veuillez rafraîchir la page.']);
    exit();
}

$id_pharmacie = filter_var($donnees['id_pharmacie'] ?? null, FILTER_VALIDATE_INT);

if (!$id_pharmacie) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Pharmacie invalide.']);
    exit();
}

// ===== Bascule active/désactivée =====
// Jamais de suppression physique (cf. spec §3.2) : uniquement un changement
// de statut. Une pharmacie désactivée disparaît des recherches publiques,
// mais son compte pharmacien garde l'accès pour clôturer les réservations
// en cours — on ne touche donc à rien d'autre ici.

$sql = "UPDATE pharmacies
        SET statut = CASE WHEN statut = 'active' THEN 'desactivee' ELSE 'active' END
        WHERE id_pharmacie = ?
        RETURNING statut";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_pharmacie]);
$resultat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resultat) {
    http_response_code(404);
    echo json_encode(['succes' => false, 'message' => 'Pharmacie introuvable.']);
    exit();
}

echo json_encode(['succes' => true, 'nouveau_statut' => $resultat['statut']]);
