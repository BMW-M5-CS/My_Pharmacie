<?php

require_once '../../int_Public/Dos-php/config.php';

header("Content-Type: application/json");

// verification de la session utilisateur
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => 'Vous devez être connecté.']);
    exit();
}

$id_user = $_SESSION['user_id'];

// lecture des données du formulaire
$donnees = json_decode(file_get_contents("php://input"), true);
$action  = trim($donnees['action'] ?? '');

//action de modification des informations personnelles
if ($action === 'modifier_infos') {

    $prenom               = trim($donnees['prenom']             ?? '');
    $nom                  = trim($donnees['nom']                ?? '');
    $phone_email          = trim($donnees['phone_email']        ?? '');
    $contact_recuperation = trim($donnees['contact_recuperation'] ?? '');
    $mdp_actuel           = trim($donnees['mdp_actuel']         ?? '');

    // validation des champs
   if(empty($prenom) || empty($nom) || empty($phone_email) || empty($mdp_actuel)) {
        echo json_encode(["success" => false, "message" => 'Tous les champs sont obligatoires.']);
        exit();
    }

    if (!empty($contact_recuperation)) {
        $est_email      = filter_var($contact_recuperation, FILTER_VALIDATE_EMAIL);
        $est_telephone  = preg_match('/^[0-9+\s]{8,20}$/', $contact_recuperation);

        if (!$est_email && !$est_telephone) {
            echo json_encode(["success" => false, "message" => 'Le contact de récupération doit être un email ou un numéro de téléphone valide.']);
            exit();
        }
    }

    // verification du mot de passe actuel avant mise a jour 
    $sql_verif  = "SELECT \"password\" FROM users WHERE id_user = ?";
    $stmt_verif = $pdo->prepare($sql_verif);
    $stmt_verif->execute([$id_user]);
    $user       = $stmt_verif->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($mdp_actuel, $user['password'])) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => 'Mot de passe actuel incorrect.']);
        exit();
    }

    //verification de l'email pour eviter les doublons
    $sql_verif = "SELECT id_user FROM users WHERE phone_email = ? AND id_user != ?";
    $stmt_verif = $pdo->prepare($sql_verif);
    $stmt_verif->execute([$phone_email, $id_user]);
    
    if ($stmt_verif->fetch()){
        echo json_encode(["success" => false, "message" => 'Cet email  ou ce numero est déjà utilisé par un autre compte.']);
        exit();
    }

    //mise ajour de la base de données avec les nouvelles informations
    $sql_update = "UPDATE users 
                   SET prenom           = ?, 
                   nom                  = ?, 
                   phone_email          = ?,
                   contact_recuperation = ?
                WHERE id_user = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$prenom, $nom, $phone_email, $contact_recuperation, $id_user]);


    // metre a jour la session avec les nouvelles informations
    $_SESSION['prenom']               = $prenom;
    $_SESSION['nom']                  = $nom;
    $_SESSION['phone_email']          = $phone_email;  
    $_SESSION['contact_recuperation'] = $contact_recuperation;

    echo json_encode([
        "success"              => true, 
        "message"              => 'Informations personnelles mises à jour avec succès.',
        "prenom"               => $prenom,
        "nom"                  => $nom,
        "phone_email"          => $phone_email,
        "contact_recuperation" => $contact_recuperation
    ]);

    exit();
}



// Action de modification du mot de passe
if ($action === 'modifier_mdp') {

    $mdp_actuel   = trim($donnees['mdp_actuel']   ?? '');
    $nouveau_mdp  = trim($donnees['nouveau_mdp']  ?? '');
    $confirmation = trim($donnees['confirmation'] ?? '');

    // validation des champs
    if (empty($mdp_actuel) || empty($nouveau_mdp) || empty($confirmation)) {
        echo json_encode(["success" => false, "message" => 'Tous les champs sont obligatoires.']);
        exit();
    }

    if ($nouveau_mdp !== $confirmation) {
        echo json_encode(["success" => false, "message" => 'Le nouveau mot de passe et la confirmation ne correspondent pas.']);
        exit();
    }

   if (strlen($nouveau_mdp) < 8) {
        echo json_encode(["success" => false, "message" => 'Le nouveau mot de passe doit contenir au moins 6 caractères.']);
        exit();
    }

    // verification du mot de passe actuel
    $sql_verif  = "SELECT \"password\" FROM users WHERE id_user = ?";
    $stmt = $pdo->prepare($sql_verif);
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($mdp_actuel, $user['password'])) {
        echo json_encode(["success" => false, "message" => 'Mot de passe actuel incorrect.']);
        exit();
    }

    //verification que le nouveau mot de passe est différent de l'ancien
    if (password_verify($nouveau_mdp, $user['password'])) {
        echo json_encode(["success" => false, "message" => 'Le nouveau mot de passe doit être différent de l\'ancien.']);
        exit();
    }

    // hachage du nouveau mot de passe et mise à jour dans la base de données
    $nouveau_mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

    $sql_update       = "UPDATE users SET \"password\" = ? WHERE id_user = ?";
    $stmt_update      = $pdo->prepare($sql_update);
    $stmt_update->execute([$nouveau_mdp_hash, $id_user]);

    echo json_encode([
        'success' => true, 
        'message' => 'Mot de passe mis à jour avec succès.'
    ]);

    exit();
}

// Action non reconnue
http_response_code(400);
echo json_encode([
    "success" => false, 
    "message" => 'Action non reconnue.
']);

?>