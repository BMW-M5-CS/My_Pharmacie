<?php

require_once '../../Include_general/session_init.php';

// On ne vide que les clés admin_* : si jamais une session mixte existait
// (cas rare), on ne touche pas à une éventuelle session client en parallèle.
unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_nom'],
    $_SESSION['admin_prenom'],
    $_SESSION['admin_pharmacie_id'],
    $_SESSION['admin_role_id'],
    $_SESSION['admin_modif_mdp']
);

session_regenerate_id(true);

header("Location: ../Dos-page/conex_admin.php");
exit();
