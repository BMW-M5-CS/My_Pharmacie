<?php

require_once '../../Include_general/session_init.php';

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    die("Requête invalide");
}

require_once '../../int_Public/Dos-php/config.php';

try {
    $email      = trim($_POST['email']         ?? '');
    $mdp_tape   = $_POST['mot_de_passe']        ?? '';
    $adresse_ip = $_SERVER['REMOTE_ADDR']       ?? 'inconnue';

    // ===== 1. Protection anti-brute-force : max 5 échecs / 15 min pour cet email =====
    // Même principe que pour les clients (tentatives_connexion), mais dans sa
    // propre table pour ne jamais mélanger les deux univers de comptes.
    $sql_echecs = "SELECT COUNT(*) AS nombre
                   FROM tentatives_connexion_admin
                   WHERE identifiant = ? AND reussie = FALSE
                     AND date_tentative > NOW() - INTERVAL '15 minutes'";
    $stmt_echecs = $pdo->prepare($sql_echecs);
    $stmt_echecs->execute([$email]);
    $echecs = $stmt_echecs->fetch();

    // ===== 2. Protection anti-brute-force distribué : max 15 échecs / 15 min depuis cette IP =====
    $sql_echecs_ip = "SELECT COUNT(*) AS nombre
                       FROM tentatives_connexion_admin
                       WHERE adresse_ip = ? AND reussie = FALSE
                         AND date_tentative > NOW() - INTERVAL '15 minutes'";
    $stmt_echecs_ip = $pdo->prepare($sql_echecs_ip);
    $stmt_echecs_ip->execute([$adresse_ip]);
    $echecs_ip = $stmt_echecs_ip->fetch();

    if (($echecs && (int) $echecs['nombre'] >= 5) || ($echecs_ip && (int) $echecs_ip['nombre'] >= 15)) {
        header("Location: ../Dos-page/conex_admin.php?erreur=trop_de_tentatives");
        exit();
    }

    $sql  = "SELECT * FROM administrateurs WHERE email = ? AND statut = 'actif'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($mdp_tape, $admin['mot_de_passe_hash'])) {

        // Connexion réussie : on journalise quand même (utile pour un futur audit)
        $pdo->prepare("INSERT INTO tentatives_connexion_admin (identifiant, adresse_ip, reussie) VALUES (?, ?, TRUE)")
            ->execute([$email, $adresse_ip]);

        // Régénérer l'identifiant de session à chaque connexion : empêche la
        // fixation de session (un attaquant qui aurait fixé un id de session
        // avant la connexion ne peut pas en hériter les droits admin).
        session_regenerate_id(true);

        $_SESSION['admin_id']           = $admin['id_administrateur'];
        $_SESSION['admin_nom']          = $admin['nom'];
        $_SESSION['admin_prenom']       = $admin['prenom'];
        $_SESSION['admin_pharmacie_id'] = $admin['id_pharmacie']; // NULL pour l'administration plateforme
        $_SESSION['admin_role_id']      = $admin['id_role'];
        $_SESSION['admin_modif_mdp']    = $admin['date_modif_mdp'];

        // Le rôle décide de l'écran d'arrivée : l'administration plateforme
        // (pas rattachée à une pharmacie précise) commence par la liste de
        // toutes les pharmacies ; un pharmacien va directement à ses propres
        // demandes.
        $stmt_role = $pdo->prepare("SELECT nom_role FROM roles WHERE id_role = ?");
        $stmt_role->execute([$admin['id_role']]);
        $nom_role = $stmt_role->fetchColumn();

        $_SESSION['admin_role_nom'] = $nom_role;

        if ($nom_role === 'administration_plateforme') {
            header("Location: ../Dos-page/liste_pharmacies.php");
        } else {
            header("Location: ../Dos-page/demandes_reservation.php");
        }
        exit();

    } else {

        // Connexion échouée : on journalise pour la protection anti-brute-force
        $pdo->prepare("INSERT INTO tentatives_connexion_admin (identifiant, adresse_ip, reussie) VALUES (?, ?, FALSE)")
            ->execute([$email, $adresse_ip]);

        header("Location: ../Dos-page/conex_admin.php?erreur=identifiants_incorrects");
        exit();
    }

} catch (PDOException $e) {
    error_log("Erreur connexion admin : " . $e->getMessage());
    header("Location: ../Dos-page/conex_admin.php?erreur=technique");
    exit();
}
