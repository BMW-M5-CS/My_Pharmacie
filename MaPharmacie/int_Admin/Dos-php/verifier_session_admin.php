<?php

// ============================================================================
// Garde de session — à inclure en tout premier sur CHAQUE page int_Admin
// protégée (après session_init.php et config.php).
// ----------------------------------------------------------------------------
// Un seul endroit qui décide "ce pharmacien a-t-il le droit d'être ici ?" —
// pour ne jamais avoir à recopier cette vérification page par page (risque
// d'oubli = accès non protégé, cf. charte de rigueur sur le cloisonnement).
// ============================================================================

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Dos-page/conex_admin.php");
    exit();
}

// Si le mot de passe a été changé ailleurs (autre session, ou action interne),
// cette session est automatiquement invalidée — même principe que pour les
// comptes clients dans config.php. On rafraîchit aussi le rôle et la
// pharmacie à chaque page : si jamais ça change côté base (ex. réaffectation),
// la session ne doit jamais s'appuyer sur une valeur devenue périmée.
$stmt_verif_mdp = $pdo->prepare(
    "SELECT date_modif_mdp, statut, id_pharmacie, id_role
     FROM administrateurs WHERE id_administrateur = ?"
);
$stmt_verif_mdp->execute([$_SESSION['admin_id']]);
$admin_actuel = $stmt_verif_mdp->fetch(PDO::FETCH_ASSOC);

if (!$admin_actuel
    || $admin_actuel['statut'] !== 'actif'
    || $admin_actuel['date_modif_mdp'] != $_SESSION['admin_modif_mdp']) {

    session_unset();
    session_destroy();
    header("Location: ../Dos-page/conex_admin.php");
    exit();
}

$_SESSION['admin_pharmacie_id'] = $admin_actuel['id_pharmacie'];

$stmt_role = $pdo->prepare("SELECT nom_role FROM roles WHERE id_role = ?");
$stmt_role->execute([$admin_actuel['id_role']]);
$_SESSION['admin_role_nom'] = $stmt_role->fetchColumn();
