<?php
require_once 'config.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide");
}

$token  = $_POST['token'] ?? '';
$action = $_POST['action'] ?? '';
$adresse_ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';

if (empty($token) || empty($action)) {
    header("Location: ../Dos-page/mdp_oublie.php");
    exit();
}

$token_hash = hash('sha256', $token);

// Revérifier le token à chaque soumission (ne jamais faire confiance à ce qui a été affiché avant)
$sql = "SELECT rt.*, u.id_user, u.info_modif_mdp
        FROM reset_tokens rt
        JOIN users u ON u.id_user = rt.id_user
        WHERE rt.token = ? AND rt.utilise = FALSE AND rt.date_expiration > NOW()";
$stmt = $pdo->prepare($sql);
$stmt->execute([$token_hash]);
$ligne = $stmt->fetch();

if (!$ligne) {
    header("Location: ../Dos-page/reinitialisation_mdp.php?token=" . urlencode($token));
    exit();
}

$id_user = $ligne['id_user'];

if ($action === 'reinitialiser') {

    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirm_mdp = $_POST['confirm_mdp'] ?? '';

    if (strlen($nouveau_mdp) < 8 || $nouveau_mdp !== $confirm_mdp) {
        header("Location: ../Dos-page/reinitialisation_mdp.php?token=" . urlencode($token));
        exit();
    }

    $mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

    $pdo->prepare("UPDATE users SET \"password\" = ?, info_modif_mdp = NOW() WHERE id_user = ?")
        ->execute([$mdp_hash, $id_user]);

    $pdo->prepare("UPDATE reset_tokens SET utilise = TRUE WHERE id_token = ?")
        ->execute([$ligne['id_token']]);

    $pdo->prepare("INSERT INTO journal_securite_compte (id_user, type_evenement, id_token, adresse_ip)
                    VALUES (?, 'mot_de_passe_change', ?, ?)")
        ->execute([$id_user, $ligne['id_token'], $adresse_ip]);

    // Fin de la session temporaire éventuelle, si elle existait
    unset($_SESSION['reinitialisation_en_attente']);
    unset($_SESSION['reinitialisation_expiration']);
    unset($_SESSION['reinitialisation_token']);

    // On force une reconnexion propre avec le nouveau mot de passe
    session_regenerate_id(true);
    session_destroy();

    header("Location: ../Dos-page/conex.php?mdp_reinitialise=1");
    exit();

} elseif ($action === 'ignorer') {

    // Vérifier à nouveau la limite des 2 "ignorer" (ne jamais se fier à l'affichage précédent)
    $sql_compte = "SELECT COUNT(*) AS nb
                   FROM journal_securite_compte
                   WHERE id_user = ?
                     AND type_evenement = 'reinitialisation_ignoree'
                     AND date_evenement > COALESCE(?::timestamp, '1970-01-01')";
    $stmt_compte = $pdo->prepare($sql_compte);
    $stmt_compte->execute([$id_user, $ligne['info_modif_mdp']]);
    $compte = $stmt_compte->fetch();

    if ((int) $compte['nb'] >= 2) {
        header("Location: ../Dos-page/reinitialisation_mdp.php?token=" . urlencode($token));
        exit();
    }

    $pdo->prepare("INSERT INTO journal_securite_compte (id_user, type_evenement, id_token, adresse_ip)
                    VALUES (?, 'reinitialisation_ignoree', ?, ?)")
        ->execute([$id_user, $ligne['id_token'], $adresse_ip]);

    // Créer une vraie session, mais marquée "en attente"
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id_user;
    $_SESSION['reinitialisation_en_attente'] = true;
    $_SESSION['reinitialisation_expiration'] = $ligne['date_expiration'];
    $_SESSION['reinitialisation_token'] = $token;

    header("Location: ../../int_Client/Dos-page/acceuil.php");
    exit();

} else {
    header("Location: ../Dos-page/mdp_oublie.php");
    exit();
}