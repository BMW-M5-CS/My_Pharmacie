<?php

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

    // Si "Se souvenir de moi" est coché — cookie persistant de 30 jours
        if (isset($_POST['remenber'])) {
            $duree = 30 * 24 * 60 * 60;
            session_set_cookie_params($duree);
            setcookie(session_name(), session_id(), time() + $duree, '/');
        }

    $_SESSION['user_id']              = $user['id_user'];        
    $_SESSION['nom']                  = $user['nom'];
    $_SESSION['prenom']               = $user['prenom'];
    $_SESSION['role']                 = $user['role'];
    $_SESSION['phone_email']          = $user['phone_email'];
    $_SESSION['contact_recuperation'] = $user['contact_recuperation'];

       $redirect = $_POST['redirect'] ?? '';
       
        if (!empty($redirect)) {
            header("Location: " . $redirect);
        } else {
            header("Location: ../../int_Client/Dos-page/acceuil.php");
        } 
        exit();

    } else {
        echo "Identifiant ou mot de passe incorrect pour " . htmlspecialchars($identifian);
    }

} catch (PDOException $e) {
    echo "Erreur de connexion : ". $e->getMessage();
}
?>