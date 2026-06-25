<?php
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide.");
}

require_once '../Dos-php/config.php';

try {
    $nom          = trim($_POST['nom'] ?? '');
    $phone_email  = trim($_POST['phone_email'] ?? '');
    $sujet        = trim($_POST['sujet'] ?? '');
    $message      = trim($_POST['message'] ?? '');
    $id_user      = $_SESSION['user_id'] ?? NULL;
    $plateforme   = 'web';

    if (empty($nom) || empty($phone_email) || empty($sujet) || empty($message)) {
        die("Erreur : Tous les champs sont obligatoires.");
    }

    $sql = "INSERT INTO contacts (id_user, nom, phone_email, sujet, message, plateforme)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo ->prepare($sql);
    $stmt ->execute([$id_user, $nom, $phone_email, $sujet, $message, $plateforme]);

    header("Location: ../Dos-page/contact.php?succes=1");
    exit();

}catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>