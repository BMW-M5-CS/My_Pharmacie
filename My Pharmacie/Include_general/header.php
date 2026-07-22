<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$est_connecte = isset($_SESSION['user_id']);

$base_img = $est_connecte ? '../../int_Client/Dos-img/' : '../Dos-img/';

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


                            <!-- baniere de rappel d'ajoute du contact de recuperatiotn -->

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

    <script>
        document.getElementById('alerte-recuperation-fermer').addEventListener('click', function() {
            document.getElementById('alerte-recuperation').classList.add('masquee');

            const bannièreReinit = document.getElementById('banniere-reinitialisation');
            if (bannièreReinit) {
                bannièreReinit.classList.remove('alerte-reinitialisation');
                bannièreReinit.classList.add('alerte-recuperation-position');
            }
        });
    </script>
    
<?php endif; ?>


                          <!-- baniere de rappel de section en attente de reinitialisation du mode de passe avec compte a rebours  -->

<?php if (!empty($_SESSION['reinitialisation_en_attente'])): ?>

    <div class="alerte-recuperation alerte-reinitialisation" id="banniere-reinitialisation">

        <div class="alerte-recuperation-icone">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="alerte-recuperation-texte">
            <strong>Réinitialisation du mot de passe en attente</strong>
            <span>Temps restant : <span id="compte-a-rebours"></span></span>
        </div>

        <a href="/my_pharmacie/My Pharmacie/int_Public/Dos-page/reinitialisation_mdp.php?token=<?= urlencode($_SESSION['reinitialisation_token']) ?>" class="alerte-recuperation-btn">Terminer maintenant</a>

    </div>

    <script>
    const expiration = new Date("<?= date('c', strtotime($_SESSION['reinitialisation_expiration'])) ?>").getTime();
    const banniere = document.getElementById('banniere-reinitialisation');
    const affichage = document.getElementById('compte-a-rebours');

    const interval = setInterval(() => {
        const restant = expiration - Date.now();
        if (restant <= 0) {
            clearInterval(interval);
            location.reload();
            return;
        }
        const minutes = Math.floor(restant / 60000);
        const secondes = Math.floor((restant % 60000) / 1000);
        affichage.textContent = minutes + " min " + secondes + " s";

        if (minutes < 20) {
            banniere.classList.add('clignote-rouge');
        }
    }, 1000);
    </script>
<?php endif; ?>