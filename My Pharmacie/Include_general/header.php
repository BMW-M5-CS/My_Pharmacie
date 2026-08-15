<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$est_connecte = isset($_SESSION['user_id']);


$afficher_alerte_recup = false;

if ($est_connecte) {
    $contact_recup_vide  = empty($_SESSION['contact_recuperation']);
    $alerte_deja_ignoree = !empty($_SESSION['alerte_recup_ignoree']);

    if ($contact_recup_vide && !$alerte_deja_ignoree) {
        $afficher_alerte_recup = true;
    }
}


// Détection de la page actuellement consultée, pour mettre en valeur
// le lien correspondant dans le menu du header.
$page_courante = basename($_SERVER['PHP_SELF']);

function lien_actif($nom_fichier, $page_courante) {
    return $nom_fichier === $page_courante ? ' actif' : '';
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
                    <li><a href="../../int_Client/Dos-page/acceuil.php" class="btn<?php echo lien_actif('acceuil.php', $page_courante); ?>"<?php echo lien_actif('acceuil.php', $page_courante) ? ' aria-current="page"' : ''; ?>><i class="fa-solid fa-house"></i> Accueil</a></li>
                <?php else : ?>
                    <li><a href="../../int_Public/Dos-page/acceuil.php" class="btn<?php echo lien_actif('acceuil.php', $page_courante); ?>"<?php echo lien_actif('acceuil.php', $page_courante) ? ' aria-current="page"' : ''; ?>><i class="fa-solid fa-house"></i> Accueil</a></li>
                <?php endif; ?>
                <li><a href="../../int_Public/Dos-page/produit.php" class="btn<?php echo lien_actif('produit.php', $page_courante); ?>"<?php echo lien_actif('produit.php', $page_courante) ? ' aria-current="page"' : ''; ?>><i class="fa-solid fa-pills"></i> Produit</a></li>
                <li><a href="../../int_Public/Dos-page/pharmacie.php" class="btn<?php echo lien_actif('pharmacie.php', $page_courante); ?>"<?php echo lien_actif('pharmacie.php', $page_courante) ? ' aria-current="page"' : ''; ?>><i class="fa-solid fa-location-dot"></i> Pharmacies</a></li>
                <li><a href="../../int_Public/Dos-page/carte.php" class="btn<?php echo lien_actif('carte.php', $page_courante); ?>"<?php echo lien_actif('carte.php', $page_courante) ? ' aria-current="page"' : ''; ?>><i class="fa-solid fa-map"></i> Carte</a></li>
                <li><a href="../../int_Public/Dos-page/contact.php" class="btn<?php echo lien_actif('contact.php', $page_courante); ?>"<?php echo lien_actif('contact.php', $page_courante) ? ' aria-current="page"' : ''; ?>><i class="fa-solid fa-envelope"></i> Contact</a></li>
            </ul>
        </div>


        <?php if ($est_connecte) : ?>

            <div class="profil">
                <a href="../../int_Client/Dos-page/profil.php" class="btn-profil<?php echo lien_actif('profil.php', $page_courante); ?>"><i class="fa-solid fa-circle-user"></i> Mon Profil</a>
                <a href="../../int_Client/Dos-php/deconex.php" class="btn-deconex"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </div>

        <?php else : ?>

            <div class="conex">
                <a href="../../int_Public/Dos-page/conex.php" class="btn-conex<?php echo lien_actif('conex.php', $page_courante); ?>"><i class="fa-solid fa-user-lock"></i> Connexion</a>
                <a href="../../int_Public/Dos-page/inscription.php" class="btn-inscrit<?php echo lien_actif('inscription.php', $page_courante); ?>">S'inscrire</a>
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

        <button type="button" class="alerte-recuperation-fermer" id="alerte-recuperation-fermer" data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" aria-label="Fermer">
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