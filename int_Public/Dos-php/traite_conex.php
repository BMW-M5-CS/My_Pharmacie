<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide");
}

require_once 'config.php';

try{
    $identifian = $_POST["phone_email"] ??'';
    $mdp_tape   = $_POST["mot_de_passe"] ??'';

    $sql  = "SELECT * FROM users WHERE phone_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identifian]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp_tape, $user['password'])) {
        $_SESSION['user_id'] = $user['id_user'];        
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['role']    = $user['role'];

       header("Location: ../../int_Client/Dos-page/acceuil.php");
       exit();
    } else {
        echo "Identifiant ou mot de passe incorrect pour." . htmlspecialchars($identifian);
    }
} catch (PDOException $e) {
    echo "Erreur de connexion : ". $e->getMessage();
}
?>