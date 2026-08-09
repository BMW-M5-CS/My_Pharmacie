<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$est_connecte = isset($_SESSION['user_id']);


$afficher_alerte_recup = false;

if ($est_connecte) {
    $phone_email_est_email = filter_var($_SESSION['phone_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $contact_recup_vide    = empty($_SESSION['contact_recuperation']);

    if (!$phone_email_est_email && $contact_recup_vide) {
        $afficher_alerte_recup = true;
    }
}
?>


<header>

    <a href="<?php echo $est_connecte ? '../../int_Client/Dos-page/acceuil.php' : '../../int_Public/Dos-page/acceuil.php'; ?>" class="logo">
        <i class="fa-solid fa-mortar-pestle"></i>
        <span>Ma<strong>Pharmacie</strong></span>
    </a>


    <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>


    <div class="menu-mobile" id="menu-mobile">

        <div class="menu">
            <ul>
                <?php if ($est_connecte) : ?>
                    <li><a href="../../int_Client/Dos-page/acceuil.php" class="btn"><i class="fa-solid fa-house"></i> Accueil</a></li>
                <?php else : ?>
                    <li><a href="../../int_Public/Dos-page/acceuil.php" class="btn"><i class="fa-solid fa-house"></i> Accueil</a></li>
                <?php endif; ?>
                <li><a href="../../int_Public/Dos-page/produit.php" class="btn"><i class="fa-solid fa-pills"></i> Produit</a></li>
                <li><a href="../../int_Public/Dos-page/pharmacie.php" class="btn"><i class="fa-solid fa-location-dot"></i> Pharmacies</a></li>
                <li><a href="../../int_Public/Dos-page/carte.php" class="btn"><i class="fa-solid fa-map"></i> Carte</a></li>
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
                <a href="../../int_Public/Dos-page/conex.php" class="btn-conex"><i class="fa-solid fa-user-lock"></i> Connexion</a>
                <a href="../../int_Public/Dos-page/inscription.php" class="btn-inscrit">S'inscrire</a>
            </div>

        <?php endif; ?>

    </div>

</header>


<?php if ($afficher_alerte_recup) : ?>

    <div class="alerte-recuperation" id="alerte-recuperation">

        <div class="alerte-recuperation-icone">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="alerte-recuperation-texte">
            <strong>Sécurisez votre compte</strong>
            <span>Ajoutez un contact de récupération pour ne jamais perdre l'accès à votre compte.</span>
        </div>

        <a href="../../int_Client/Dos-page/profil.php" class="alerte-recuperation-btn">Ajouter maintenant</a>

        <button type="button" class="alerte-recuperation-fermer" id="alerte-recuperation-fermer" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

<?php endif; ?>


<?php if (!empty($_SESSION['reinitialisation_en_attente'])): ?>

    <div class="alerte-recuperation alerte-reinitialisation" id="banniere-reinitialisation" data-expiration="<?= date('c', strtotime($_SESSION['reinitialisation_expiration'])) ?>">

        <div class="alerte-recuperation-icone">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="alerte-recuperation-texte">
            <strong>Réinitialisation du mot de passe en attente</strong>
            <span>Temps restant : <span id="compte-a-rebours"></span></span>
        </div>

        <a href="/my_pharmacie/My Pharmacie/int_Public/Dos-page/reinitialisation_mdp.php?token=<?= urlencode($_SESSION['reinitialisation_token']) ?>" class="alerte-recuperation-btn">Terminer maintenant</a>

    </div>

<?php endif; ?>