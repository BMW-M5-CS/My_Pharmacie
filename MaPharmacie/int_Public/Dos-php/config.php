<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fuseau horaire du Togo, fixé explicitement — sans ça, PHP utilise le fuseau
// par défaut du serveur (souvent celui de la machine de dev, pas forcément Lomé),
// ce qui fausse tous les calculs "pharmacie ouverte/fermée en ce moment".
date_default_timezone_set('Africa/Lome');

if (getenv('DB_HOST') !== false) {
    // Environnement Docker : les identifiants viennent de l'environnement
    // (.env / docker-compose.yml), jamais de db_secrets.php — ce fichier
    // reste réservé à WAMP, avec qui il partage le même dossier sur le
    // disque. Le générer depuis Docker écraserait les identifiants locaux.
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_name = getenv('POSTGRES_DB') ?: 'my_pharmacie';
    $db_user = getenv('POSTGRES_USER') ?: 'postgres';
    $db_pass = getenv('POSTGRES_PASSWORD');
} else {
    require_once __DIR__ . '/db_secrets.php';
}
require_once __DIR__ . '/../../Include_general/config_fonctionnalites.php';

$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name}";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // On ne montre jamais le détail technique de l'erreur au visiteur,
    // on la garde seulement dans les logs serveur.
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur technique est survenue. Veuillez réessayer plus tard.");
}

// ===== Expiration de la session de réinitialisation "ignorer" =====
if (!empty($_SESSION['reinitialisation_en_attente']) && !empty($_SESSION['reinitialisation_expiration'])) {
    if (strtotime($_SESSION['reinitialisation_expiration']) < time()) {
        session_destroy();
        header("Location: " . SITE_URL . "/int_Public/Dos-page/conex.php?session_expiree=1");
        exit();
    }
}

// ===== Déconnexion forcée si le mot de passe a été changé depuis un autre appareil =====
// On compare la date de dernière modif connue par CETTE session avec celle actuellement en base.
// Si la base est plus récente, ça veut dire que le mot de passe a été changé ailleurs
// (profil ou réinitialisation) : on invalide cette session-ci par sécurité.

if (isset($_SESSION['user_id'])) {

    $stmt_verif_mdp = $pdo->prepare("SELECT info_modif_mdp FROM users WHERE id_user = ?");
    $stmt_verif_mdp->execute([$_SESSION['user_id']]);
    $ligne_mdp = $stmt_verif_mdp->fetch();

    if ($ligne_mdp) {

        $modif_bd      = $ligne_mdp['info_modif_mdp'];
        $modif_session = $_SESSION['info_modif_mdp'] ?? null;

        $horodatage_bd      = $modif_bd      !== null ? strtotime($modif_bd)      : 0;
        $horodatage_session = $modif_session !== null ? strtotime($modif_session) : 0;

        if ($horodatage_bd > $horodatage_session) {
            session_destroy();
            header("Location: " . SITE_URL . "/int_Public/Dos-page/conex.php?session_expiree=1");
            exit();
        }

    } else {
        // L'utilisateur en session n'existe plus en base (compte supprimé) : on ferme la session.
        session_destroy();
        header("Location: " . SITE_URL . "/int_Public/Dos-page/conex.php?session_expiree=1");
        exit();
    }
}

// ===== Expiration automatique des réservations dépassées =====
// Exécutée seulement 1 fois sur 20 (~5% des requêtes), pas à chaque page vue :
// à l'échelle (millions de lignes), lancer cet UPDATE sur 100% des requêtes serait
// inutilement coûteux. Ce mécanisme de probabilité est le même principe que celui
// utilisé par PHP pour son propre garbage collector de sessions.
// IMPORTANT (côté base de données, à faire dans pgAdmin) : pour que cette requête
// reste rapide même avec des millions de réservations, un index partiel est nécessaire :
//   CREATE INDEX idx_reservations_expiration
//   ON reservations (expire_at)
//   WHERE statut = 'en_attente';
function expirer_reservations_obsoletes($pdo) {
    $sql = "UPDATE reservations 
            SET statut = 'expiree' 
            WHERE statut = 'en_attente' 
            AND expire_at < NOW()";
    $pdo->exec($sql);
}

if (mt_rand(1, 20) === 1) {
    expirer_reservations_obsoletes($pdo);
}
?>