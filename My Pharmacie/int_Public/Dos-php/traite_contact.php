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
        header("Location: ../Dos-page/contact.php?erreur=champs_obligatoires");
        exit();
    }

    $sql = "INSERT INTO contacts (id_user, nom, phone_email, sujet, message, plateforme)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo ->prepare($sql);
    $stmt ->execute([$id_user, $nom, $phone_email, $sujet, $message, $plateforme]);

    header("Location: ../Dos-page/contact.php?succes=1");
    exit();

} catch (PDOException $e) {
    // On ne montre jamais le détail technique de l'erreur au visiteur,
    // on la garde seulement dans les logs serveur.
    error_log("Erreur envoi contact : " . $e->getMessage());
    header("Location: ../Dos-page/contact.php?erreur=technique");
    exit();
}