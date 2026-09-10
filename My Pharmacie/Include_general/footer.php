<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$est_connecte = isset($_SESSION['user_id']);

// TODO(Wilfried) : remplacer par les vraies coordonnées avant toute mise en ligne
// publique ou tout dépôt de dossier administratif — ces valeurs sont des
// placeholders volontairement non réalistes pour ne pas être prises pour de
// vraies coordonnées par erreur.
$contact_telephone = '+228 XX XX XX XX';
$contact_email      = 'contact@[votre-domaine].tg';
$contact_adresse    = '[Adresse à compléter], Lomé';
?>

<footer>
    <div class="footer-container">

        <!-- Colonne 1 : Branding -->
        <div class="footer-col footer-brand">
            <div class="footer-logo">
                <i class="fas fa-mortar-pestle"></i>
                <span>Ma<strong>Pharmacie</strong></span>
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
                <li><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact_telephone); ?></li>
                <li><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact_email); ?></li>
                <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($contact_adresse); ?></li>
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

    <nav class="footer-legal" aria-label="Informations légales">
        <a href="../../int_Public/Dos-page/mentions-legales.php">Mentions légales</a>
        <a href="../../int_Public/Dos-page/cgu.php">Conditions générales d'utilisation</a>
        <a href="../../int_Public/Dos-page/confidentialite.php">Politique de confidentialité</a>
    </nav>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> MaPharmacie — Tous droits réservés</p>
    </div>
</footer>