<?php

session_start();

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    die("Requête invalide");
}

require_once 'config.php';
require_once 'mailer_config.php';

$identifiant = trim($_POST['phone_email'] ?? '');
$adresse_ip  = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';

function terminerAvecMessageGenerique() {
    header("Location: ../Dos-page/mdp_oublie.php?envoye=1");
    exit();
}

if (empty($identifiant)) {
    terminerAvecMessageGenerique();
}

try {

    // ===== 1. Vérifier si cet identifiant est actuellement sous blocage progressif =====
    $sql_flags = "SELECT COUNT(*) AS nombre, MAX(date_detection) AS derniere
                  FROM tentatives_malveillantes
                  WHERE identifiant = ? AND raison = 'dispersion_ip'";
    $stmt_flags = $pdo->prepare($sql_flags);
    $stmt_flags->execute([$identifiant]);
    $flags = $stmt_flags->fetch();

    if ($flags && $flags['nombre'] > 0) {
        $occurrence = (int) $flags['nombre'];

        if ($occurrence === 1) {
            $duree_blocage = '+24 hours';
        } elseif ($occurrence === 2) {
            $duree_blocage = '+72 hours';
        } else {
            $duree_blocage = '+7 days';
        }

        $fin_blocage = strtotime($flags['derniere'] . ' ' . $duree_blocage);

        if (time() < $fin_blocage) {
            // Encore sous blocage : on enregistre la tentative comme bloquée, sans envoyer d'email
            $sql_log = "INSERT INTO tentatives_reset_mdp (identifiant, adresse_ip, bloquee) VALUES (?, ?, TRUE)";
            $pdo->prepare($sql_log)->execute([$identifiant, $adresse_ip]);
            terminerAvecMessageGenerique();
        }
    }

    // ===== 2. Vérifier la limite de volume : 3 demandes max / 24h pour cet identifiant =====
    $sql_volume = "SELECT COUNT(*) AS nombre
                   FROM tentatives_reset_mdp
                   WHERE identifiant = ? AND date_tentative > NOW() - INTERVAL '24 hours'";
    $stmt_volume = $pdo->prepare($sql_volume);
    $stmt_volume->execute([$identifiant]);
    $volume = $stmt_volume->fetch();

    if ($volume && $volume['nombre'] >= 3) {
        $sql_log = "INSERT INTO tentatives_reset_mdp (identifiant, adresse_ip, bloquee) VALUES (?, ?, TRUE)";
        $pdo->prepare($sql_log)->execute([$identifiant, $adresse_ip]);
        terminerAvecMessageGenerique();
    }

    // ===== 3. Enregistrer cette tentative (elle est autorisée à ce stade) =====
    $sql_log = "INSERT INTO tentatives_reset_mdp (identifiant, adresse_ip, bloquee) VALUES (?, ?, FALSE)";
    $pdo->prepare($sql_log)->execute([$identifiant, $adresse_ip]);

    // ===== 4. Détection de dispersion d'IP pour ce même identifiant (sur 24h) =====
    $sql_ips = "SELECT COUNT(DISTINCT adresse_ip) AS nombre_ips
                FROM tentatives_reset_mdp
                WHERE identifiant = ? AND date_tentative > NOW() - INTERVAL '24 hours'";
    $stmt_ips = $pdo->prepare($sql_ips);
    $stmt_ips->execute([$identifiant]);
    $ips = $stmt_ips->fetch();

    if ($ips && $ips['nombre_ips'] >= 3) {
        $sql_flag = "INSERT INTO tentatives_malveillantes (identifiant, adresse_ip, raison) VALUES (?, ?, 'dispersion_ip')";
        $pdo->prepare($sql_flag)->execute([$identifiant, $adresse_ip]);
    }

    // ===== 5. Détection de scan horizontal depuis cette IP (plusieurs identifiants en 1h) =====
    $sql_scan = "SELECT COUNT(DISTINCT identifiant) AS nombre_identifiants
                 FROM tentatives_reset_mdp
                 WHERE adresse_ip = ? AND date_tentative > NOW() - INTERVAL '1 hour'";
    $stmt_scan = $pdo->prepare($sql_scan);
    $stmt_scan->execute([$adresse_ip]);
    $scan = $stmt_scan->fetch();

    if ($scan && $scan['nombre_identifiants'] >= 5) {
        $sql_flag = "INSERT INTO tentatives_malveillantes (identifiant, adresse_ip, raison) VALUES (?, ?, 'scan_horizontal')";
        $pdo->prepare($sql_flag)->execute([$identifiant, $adresse_ip]);
    }

    // ===== 6. Recherche du compte et envoi de l'email si tout est en ordre =====
    $sql  = "SELECT * FROM users WHERE phone_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identifiant]);
    $user = $stmt->fetch();

    if ($user) {

        $destinataire = null;

        if (!empty($user['contact_recuperation']) && filter_var($user['contact_recuperation'], FILTER_VALIDATE_EMAIL)) {
            $destinataire = $user['contact_recuperation'];
        } elseif (filter_var($user['phone_email'], FILTER_VALIDATE_EMAIL)) {
            $destinataire = $user['phone_email'];
        }

        if ($destinataire !== null) {

            // Invalider tous les anciens tokens non utilisés de ce compte
            $sql_invalider = "UPDATE reset_tokens SET utilise = TRUE WHERE id_user = ? AND utilise = FALSE";
            $pdo->prepare($sql_invalider)->execute([$user['id_user']]);

            // Générer un nouveau token, mais stocker uniquement son hash en base
            $token           = bin2hex(random_bytes(32));
            $token_hash      = hash('sha256', $token);
            $date_expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $sql_insert  = "INSERT INTO reset_tokens (id_user, token, date_expiration) VALUES (?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$user['id_user'], $token_hash, $date_expiration]);

            // Le token EN CLAIR ne part que dans l'email, jamais en base
            $lien = "http://" . $_SERVER['HTTP_HOST']
                  . dirname(dirname($_SERVER['SCRIPT_NAME']))
                  . "/Dos-page/reinitialisation_mdp.php?token=" . $token;

            $sujet = "Réinitialisation de votre mot de passe — My Pharmacie";
            $corps = "
                <p>Bonjour " . htmlspecialchars($user['prenom']) . ",</p>
                <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
                <p><a href=\"" . $lien . "\">Cliquez ici pour choisir un nouveau mot de passe</a></p>
                <p>Ce lien est valable 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p>
            ";

            envoyerEmail($destinataire, $sujet, $corps);
        }
    }

} catch (PDOException $e) {
    error_log("Erreur mdp oublié : " . $e->getMessage());
}

terminerAvecMessageGenerique();

?>