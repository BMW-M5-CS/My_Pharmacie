<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../int_Public/Dos-page/conex.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceuil client</title>
    <link rel="stylesheet" href="../Dos-css/acceuil.css">
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
        <?php
           include'../Dos-include/header.php'; 
        ?>

        <div class="mot-bienvenue">
            <h2>Bienvenue sur notre site</h2>
            <h2>et message de bienvenue</h2>
        </div>

       <div class="section">

            <div class="reservation">
                <div class="reservation-en-cours">
                    <h3><i class="fas fa-calendar-clock"></i>reservation en cours</h3>
                </div>

                <div class="reservation-confirmer">
                    <h3><i class="fas fa-check-circle"></i>reservation confirmer</h3>
                </div>

                <div class="reservation-total">
                    <h3><i class="fas fa-list"></i>reservation Total</h3>
                </div>

                <div class="reservation-annuler">
                    <h3><i class="fas fa-times-circle"></i>reservation annuler</h3>
                </div>

            </div>



            <div class="hero">

                <div class="reservation-suplement">

                </div>

            </div>


       </div>
 
       <div class="footer">
            <h2>MyPharmacie</h2>
            <p>© 2026 My Pharmacie — Votre santé, notre priorité</p>
            <ul>
                <li><a href="../../int_Client/Dos-page/acceuil.php">Acceuil</a></li>
                <li><a href="../../int_Public/Dos-page/produit.php">Produits</a></li>
                <li><a href="../../int_Public/Dos-page/contact.php">Contact</a></li>
            </ul>
       </div>

    
</body>
</html>