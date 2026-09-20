<?php

require_once '../../Include_general/session_init.php';
require_once '../../int_Public/Dos-php/config.php';
require_once '../Dos-php/verifier_session_admin.php';

// Réservé au compte administration plateforme — un pharmacien normal n'a
// aucune raison de voir la liste de toutes les pharmacies.
if ($_SESSION['admin_role_nom'] !== 'administration_plateforme') {
    header("Location: demandes_reservation.php");
    exit();
}

$erreur = $_GET['erreur'] ?? '';

$sql = "SELECT id_pharmacie, nom_pharmacie, ville, quartier, statut
        FROM pharmacies
        ORDER BY nom_pharmacie ASC";
$pharmacies = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes les pharmacies — MaPharmacie</title>
    <link rel="stylesheet" href="../../Include_general/variables.css">
    <link rel="stylesheet" href="../Dos-css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <input type="hidden" id="csrf-token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <div class="admin-page">

        <div class="admin-topbar">
            <h1><i class="fa-solid fa-shop"></i> Toutes les pharmacies</h1>
            <a href="creer_pharmacie.php" class="admin-btn-accepter admin-btn-inline">
                <i class="fa-solid fa-plus"></i> Créer une pharmacie
            </a>
            <a href="../Dos-php/deconnexion_admin.php" class="admin-lien-deconnexion" title="Se déconnecter">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>

        <?php if ($erreur === 'pharmacie_introuvable') : ?>
            <p class="admin-message-erreur">Cette pharmacie n'existe pas ou plus.</p>
        <?php endif; ?>

        <?php if (empty($pharmacies)) : ?>

            <p class="admin-vide">Aucune pharmacie enregistrée pour le moment.</p>

        <?php else : ?>

            <div class="admin-liste-pharmacies">

                <?php foreach ($pharmacies as $p) : ?>

                    <div class="admin-carte-pharmacie">

                        <a href="demandes_reservation.php?pharmacie=<?php echo (int) $p['id_pharmacie']; ?>" class="admin-pharmacie-lien">
                            <span class="admin-pharmacie-nom">
                                <?php echo htmlspecialchars($p['nom_pharmacie']); ?>
                                <?php if ($p['statut'] === 'desactivee') : ?>
                                    <span class="admin-badge-desactivee">Désactivée</span>
                                <?php endif; ?>
                            </span>
                            <span class="admin-pharmacie-lieu">
                                <?php echo htmlspecialchars($p['ville']); ?><?php echo $p['quartier'] ? ' — ' . htmlspecialchars($p['quartier']) : ''; ?>
                            </span>
                        </a>

                        <button
                            class="admin-btn-toggle-statut"
                            data-id="<?php echo (int) $p['id_pharmacie']; ?>"
                            data-statut-actuel="<?php echo htmlspecialchars($p['statut']); ?>">
                            <?php echo $p['statut'] === 'active' ? 'Désactiver' : 'Réactiver'; ?>
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

    <script src="../Dos-js/liste_pharmacies.js"></script>

</body>
</html>
