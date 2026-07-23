<?php

session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide.");
}

require_once 'config.php';

try {
    $nom                  = trim($_POST['nom'] ?? '');
    $prenom               = trim($_POST['prenom'] ?? '');
    $phone_email          = trim($_POST['phone_email'] ?? '');
    $contact_recuperation = trim($_POST['contact_recuperation'] ?? '');

    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm      = $_POST['confirm'] ?? '';

    if (empty($nom) || empty($prenom) || empty($phone_email) || empty($mot_de_passe)) {
        header("Location: ../Dos-page/inscription.php?erreur=champs_obligatoires");
        exit();
    }

    if (strlen($mot_de_passe) < 8) {
        header("Location: ../Dos-page/inscription.php?erreur=mdp_court");
        exit();
    }

    if ($mot_de_passe !== $confirm) {
        header("Location: ../Dos-page/inscription.php?erreur=mdp_differents");
        exit();
    }

    if (!empty($contact_recuperation)) {
        $est_email     = filter_var($contact_recuperation, FILTER_VALIDATE_EMAIL);
        $est_telephone = preg_match('/^[0-9+\s]{8,20}$/', $contact_recuperation);

        if (!$est_email && !$est_telephone) {
            header("Location: ../Dos-page/inscription.php?erreur=contact_invalide");
            exit();
        }
    }

    $check = $pdo->prepare("SELECT id_user FROM users WHERE phone_email = ?");
    $check->execute([$phone_email]);
    if ($check->fetch()) {
        header("Location: ../Dos-page/inscription.php?erreur=deja_utilise");
        exit();
    }

    $mdp_securise = password_hash($mot_de_passe, PASSWORD_BCRYPT);
    $sql = "INSERT INTO users(nom, prenom, phone_email, contact_recuperation, \"password\", \"role\") 
            VALUES (?, ?, ?, ?, ?, 'client')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $prenom, $phone_email, $contact_recuperation ?: null, $mdp_securise]);

    header("Location: ../../int_Public/Dos-page/conex.php?inscrit=1");
    exit();

} catch (PDOException $e) {
    error_log("Erreur inscription : " . $e->getMessage());
    header("Location: ../Dos-page/inscription.php?erreur=technique");
    exit();
}
?>
