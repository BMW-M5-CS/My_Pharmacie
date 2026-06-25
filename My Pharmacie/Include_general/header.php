<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$est_connecte = isset($_SESSION['user_id']);

$base_img = $est_connecte ? '../../int_Client/Dos-img/' : '../Dos-img/';
?>

<header>

    <div class="logo">
        <img class="entete" src="<?php echo $base_img; ?>log.jpg" alt="log">
    </div>

    <div class="menu">
        <ul>
            <?php if ($est_connecte) : ?>
                <li><a href="../../int_Client/Dos-page/acceuil.php" class="btn"><i class="fa-solid fa-house"></i> Accueil</a></li>
            <?php else : ?>
                <li><a href="../../int_Public/Dos-page/acceuil.php" class="btn"><i class="fa-solid fa-house"></i> Accueil</a></li>
            <?php endif; ?>
                <li><a href="../../int_Public/Dos-page/produit.php" class="btn"><i class="fa-solid fa-pills"></i> Produit</a></li>
                <li><a href="../../int_Public/Dos-page/pharmacie.php" class="btn"><i class="fa-solid fa-location-dot"></i> Pharmacies</a></li>
                <li><a href="../../int_Public/Dos-page/carte.php" class="btn"> <i class="fas fa-map"></i> Carte</a></li>
                <li><a href="../../int_Public/Dos-page/contact.php" class="btn"><i class="fa-solid fa-envelope"></i> Contact</a></li>
        </ul>
    </div>

     <?php if ($est_connecte) : ?>

        <div class="profil">
            <a href="../../int_Client/Dos-page/profil.php" class="btn-profil"><i class="fa-solid fa-circle-user"></i> Mon Profil</a>
            <a href="../../int_Client/Dos-php/deconex.php" class="btn-deconex"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
        </div>

    <?php else : ?>

        <div class="conex">
            <a href="../../int_Public/Dos-page/conex.php" class="btn-conex"><i class="fa-solid fa-user-lock"></i>Connexion</a>
            <a href="../../int_Public/Dos-page/inscription.php" class="btn-inscrit">S'inscrire</a>
        </div>

    <?php endif; ?>

</header>