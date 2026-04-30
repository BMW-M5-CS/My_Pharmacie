<?php
$dsn     = "pgsql:host=localhost;port=5432;dbname=my_pharmacie";
$db_user = "postgres";
$db_pass = "1234";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
