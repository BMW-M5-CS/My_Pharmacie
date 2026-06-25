<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$est_connecte = isset($_SESSION['user_id']);
?>

<footer>
    <div class="footer-container">

        <!-- Colonne 1 : Branding -->
        <div class="footer-col footer-brand">
            <div class="footer-logo">
                <i class="fas fa-mortar-pestle"></i>
                <span>My<strong>Pharmacie</strong></span>
            </div>
            <p class="footer-tagline">Votre santé, notre priorité.<br>Trouvez le médicament qu'il vous faut, où que vous soyez.</p>
        </div>

        <!-- Colonne 2 : Navigation -->
        <div class="footer-col">
            <h4 class="footer-title">Navigation</h4>
            <ul class="footer-links">
                <?php if ($est_connecte) : ?>
                    <li><a href="../../int_Client/Dos-page/acceuil.php"><i class="fas fa-house"></i> Accueil</a></li>
                <?php else : ?>
                    <li><a href="../../int_Public/Dos-page/acceuil.php"><i class="fas fa-house"></i> Accueil</a></li>
                <?php endif; ?>
                <li><a href="../../int_Public/Dos-page/produit.php"><i class="fas fa-pills"></i> Produits</a></li>
                <li><a href="../../int_Public/Dos-page/pharmacie.php"><i class="fas fa-location-dot"></i> Pharmacies</a></li>
                <li><a href="../../int_Public/Dos-page/contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
            </ul>
        </div>

        <!-- Colonne 3 : Contact -->
        <div class="footer-col">
            <h4 class="footer-title">Contact</h4>
            <ul class="footer-links">
                <li><i class="fas fa-phone"></i> +228 90 00 00 00</li>
                <li><i class="fas fa-envelope"></i> contact@mypharmacy.tg</li>
                <li><i class="fas fa-map-marker-alt"></i> Bd du 13 Janvier, Lomé</li>
            </ul>
        </div>

        <!-- Colonne 4 : Urgence -->
        <div class="footer-col">
            <h4 class="footer-title">Urgences</h4>
            <ul class="footer-links">
                <li><i class="fas fa-truck-medical"></i> SAMU : 15</li>
                <li><i class="fas fa-shield-halved"></i> Police : 17</li>
                <li><i class="fas fa-fire-extinguisher"></i> Pompiers : 18</li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2025 MyPharmacie — Tous droits réservés</p>
    </div>
</footer>