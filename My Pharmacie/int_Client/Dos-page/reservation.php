<?php
require_once '../../int_Public/Dos-php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../int_Public/Dos-page/conex.php');
    exit();
}

$id_pharmacie = $_GET['id_pharmacie'] ?? null;
if (!$id_pharmacie || !is_numeric($id_pharmacie)) {
    header('Location: ../../int_Public/Dos-page/pharmacie.php');
    exit();
}

$produit_init = $_GET['produit'] ?? null;

$sql = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, commune, quartier,
               heure_ouverture, heure_fermeture, telephone_pharmacie, statut_garde
        FROM pharmacies WHERE id_pharmacie = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_pharmacie]);
$pharmacie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pharmacie) {
    header('Location: ../../int_Public/Dos-page/pharmacie.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation — <?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?></title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../../int_Public/Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/reservation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body data-id-pharmacie="<?php echo (int)$pharmacie['id_pharmacie']; ?>"
      data-produit-init="<?php echo htmlspecialchars($produit_init ?? ''); ?>">

    <?php include '../../Include_general/header.php'; ?>

    <!-- ===== En-tête pharmacie ===== -->
    <section class="resa-hero">
        <div class="resa-hero-inner">
            <a href="../../int_Public/Dos-page/pharmacie.php" class="resa-retour">
                <i class="fa-solid fa-arrow-left"></i> Retour aux pharmacies
            </a>

            <div class="resa-hero-row">
                <div class="resa-hero-info">
                    <span class="resa-eyebrow">Réservation</span>
                    <h1><?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?></h1>
                    <div class="resa-meta">
                        <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pharmacie['adresse']); ?>, <?php echo htmlspecialchars($pharmacie['ville']); ?></span>
                        <span><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($pharmacie['heure_ouverture']); ?> – <?php echo htmlspecialchars($pharmacie['heure_fermeture']); ?></span>
                        <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($pharmacie['telephone_pharmacie']); ?></span>
                    </div>
                </div>
                <?php if ($pharmacie['statut_garde']) : ?>
                    <span class="resa-badge-garde"><i class="fa-solid fa-moon"></i> Pharmacie de garde</span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== Corps : produits + panier ===== -->
    <section class="resa-corps">

        <!-- Colonne gauche : produits -->
        <div class="resa-produits-col">

            <div class="resa-produits-head">
                <h2>Produits disponibles <span id="resa-nb-produits"></span></h2>
                <input type="text" id="resa-recherche" placeholder="Rechercher un médicament...">
            </div>

            <div class="resa-grille-produits" id="resa-grille-produits">
                <!-- cartes produits injectées en JS -->
            </div>

        </div>

        <!-- Colonne droite : panier (ticket) -->
        <aside class="resa-panier">
            <div class="resa-panier-sticky">

                <div class="resa-ticket">
                    <div class="resa-ticket-head">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Mon panier</span>
                    </div>

                    <div class="resa-ticket-pharmacie">
                        <?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?>
                    </div>

                    <div class="resa-ticket-perforation"></div>

                    <div class="resa-ticket-lignes" id="resa-ticket-lignes">
                        <p class="resa-panier-vide" id="resa-panier-vide">
                            <i class="fa-solid fa-basket-shopping"></i>
                            Votre panier est vide.<br>Ajoutez un produit à gauche.
                        </p>
                    </div>

                    <div class="resa-ticket-perforation"></div>

                    <div class="resa-ticket-footer">
                        <div class="resa-ticket-total">
                            <span>Articles sélectionnés</span>
                            <span id="resa-total-articles">0</span>
                        </div>
                        <button class="resa-btn-confirmer" id="resa-btn-confirmer" disabled>
                            <i class="fa-solid fa-calendar-check"></i> Confirmer la réservation
                        </button>
                        <p class="resa-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Réservation gratuite, valable 2 heures à compter de la confirmation.
                        </p>
                    </div>
                </div>

            </div>
        </aside>

    </section>

    <?php include '../../int_Public/Dos-include/footer.php'; ?>

    <!-- ===== Modale de confirmation ===== -->
    <div class="resa-confirm-overlay" id="resa-confirm-overlay">
        <div class="resa-confirm-box">
            <i class="fa-solid fa-circle-check"></i>
            <h3>Réservation confirmée</h3>
            <p>Présentez ce code à la pharmacie pour récupérer vos produits.</p>
            <div class="resa-confirm-code" id="resa-confirm-code">—</div>
            <p class="resa-confirm-expire" id="resa-confirm-expire"></p>
            <a href="../Dos-page/acceuil.php" class="resa-confirm-btn">Retour à mon espace</a>
        </div>
    </div>

    <script src="../Dos-js/reservation.js"></script>
</body>
</html>