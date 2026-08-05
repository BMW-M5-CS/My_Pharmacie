<?php

session_start();

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide");
}

require_once 'config.php';

// ===== Sécurité : n'accepter comme redirection après connexion qu'un chemin interne au site =====
// Empêche un lien du type conex.php?redirect=https://site-pirate.com de rediriger
// l'utilisateur vers un site externe après une connexion réussie.

function estRedirectionSure($redirect) {
    if ($redirect === '') {
        return false;
    }
    // Refuse toute URL absolue ou protocol-relative (http://, https://, //...)
    if (preg_match('#^(https?:)?//#i', $redirect)) {
        return false;
    }
    // Refuse toute tentative d'injection d'en-tête via retour à la ligne
    if (strpbrk($redirect, "\r\n") !== false) {
        return false;
    }
    return true;
}

try {
    $identifian = $_POST["phone_email"]  ?? '';
    $mdp_tape   = $_POST["mot_de_passe"] ?? '';
    $adresse_ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';

    // ===== 1. Protection anti-brute-force : max 5 échecs / 15 min pour cet identifiant =====
    $sql_echecs = "SELECT COUNT(*) AS nombre
                   FROM tentatives_connexion
                   WHERE identifiant = ? AND reussie = FALSE
                     AND date_tentative > NOW() - INTERVAL '15 minutes'";
    $stmt_echecs = $pdo->prepare($sql_echecs);
    $stmt_echecs->execute([$identifian]);
    $echecs = $stmt_echecs->fetch();

    // ===== 2. Protection anti-brute-force distribué : max 15 échecs / 15 min depuis cette IP =====
    $sql_echecs_ip = "SELECT COUNT(*) AS nombre
                       FROM tentatives_connexion
                       WHERE adresse_ip = ? AND reussie = FALSE
                         AND date_tentative > NOW() - INTERVAL '15 minutes'";
    $stmt_echecs_ip = $pdo->prepare($sql_echecs_ip);
    $stmt_echecs_ip->execute([$adresse_ip]);
    $echecs_ip = $stmt_echecs_ip->fetch();

    if (($echecs && (int) $echecs['nombre'] >= 5) || ($echecs_ip && (int) $echecs_ip['nombre'] >= 15)) {
        header("Location: ../Dos-page/conex.php?erreur=trop_de_tentatives");
        exit();
    }

    $sql  = "SELECT * FROM users WHERE phone_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identifian]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp_tape, $user['password'])) {

        // Connexion réussie : on journalise quand même (utile pour un futur audit de sécurité)
        $pdo->prepare("INSERT INTO tentatives_connexion (identifiant, adresse_ip, reussie) VALUES (?, ?, TRUE)")
            ->execute([$identifian, $adresse_ip]);

        // Si "Se souvenir de moi" est coché — cookie persistant de 30 jours
        if (isset($_POST['remenber'])) {
            $duree = 30 * 24 * 60 * 60;
            setcookie(session_name(), session_id(), [
                'expires'  => time() + $duree,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        $_SESSION['user_id']              = $user['id_user'];
        $_SESSION['nom']                  = $user['nom'];
        $_SESSION['prenom']               = $user['prenom'];
        $_SESSION['role']                 = $user['role'];
        $_SESSION['phone_email']          = $user['phone_email'];
        $_SESSION['contact_recuperation'] = $user['contact_recuperation'];

        // Sert de référence pour la vérification "session invalidée si mdp changé ailleurs" dans config.php
        $_SESSION['info_modif_mdp'] = $user['info_modif_mdp'];

        $redirect = $_POST['redirect'] ?? '';

        if (!empty($redirect) && estRedirectionSure($redirect)) {
            header("Location: " . $redirect);
        } else {
            header("Location: ../../int_Client/Dos-page/acceuil.php");
        }
        exit();

    } else {

        // Connexion échouée : on journalise pour la protection anti-brute-force
        $pdo->prepare("INSERT INTO tentatives_connexion (identifiant, adresse_ip, reussie) VALUES (?, ?, FALSE)")
            ->execute([$identifian, $adresse_ip]);

        header("Location: ../Dos-page/conex.php?erreur=identifiants_incorrects");
        exit();
    }

} catch (PDOException $e) {
    error_log("Erreur connexion : " . $e->getMessage());
    header("Location: ../Dos-page/conex.php?erreur=technique");
    exit();
}

?>