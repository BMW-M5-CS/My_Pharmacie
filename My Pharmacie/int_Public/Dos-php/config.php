<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dsn     = "pgsql:host=localhost;port=5432;dbname=my_pharmacie";
$db_user = "postgres";
$db_pass = "1234";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ===== Expiration automatique des réservations dépassées =====
function expirer_reservations_obsoletes($pdo) {
    $sql = "UPDATE reservations 
            SET statut = 'expiree' 
            WHERE statut = 'en_attente' 
            AND expire_at < NOW()";
    $pdo->exec($sql);
}

expirer_reservations_obsoletes($pdo);
?>
