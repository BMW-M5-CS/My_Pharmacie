<?php

session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide.");
}

require_once 'config.php';

try {
    $nom          = trim($_POST['nom'] ?? '');
    $prenom       = trim($_POST['prenom'] ?? '');
    $phone_email  = trim($_POST['phone_email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm      = $_POST['confirm'] ?? '';

    if (empty($nom) || empty($prenom) || empty($phone_email) || empty($mot_de_passe)) {
        die("Erreur : Tous les champs sont obligatoires.");
    }

    if (strlen($mot_de_passe) < 6) {
        die("Erreur : Le mot de passe doit contenir au moins 6 caractères.");
    }

    if ($mot_de_passe !== $confirm) {
        die("Erreur : Les deux mots de passe ne sont pas identique !");
    }

    $check = $pdo->prepare("SELECT id_user FROM users WHERE phone_email = ?");
    $check->execute([$phone_email]);
    if ($check->fetch()) {
        die("Erreur : Cet email ou téléphone est déjà utilisé.");
    }

$mdp_securise = password_hash($mot_de_passe, PASSWORD_BCRYPT);
$sql = "INSERT INTO users(nom, prenom, phone_email, \"password\", \"role\") 
        VALUES (?, ?, ?, ?, 'client')";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nom, $prenom, $phone_email, $mdp_securise]);

header("Location: ../../int_Public/Dos-page/conex.php?inscrit=1");
exit();

 } catch (PDOException $e) {
        echo "Erreur de base de données : ". $e->getMessage();
 }
?>
