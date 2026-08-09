<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacie</title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/acceuil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

        <?php  include '../../Include_general/header.php'; ?>
        <script src="../Dos-js/header.js" defer></script>

    <section class="hero">

        <h1>Bienvenue sur Votre Pharmacie</h1>
        
        <p>
            Avec nous, trouver un médicament et la pharmacie où il est disponible n'est plus une difficulté.
            Localisez instantanément la pharmacie la plus proche disposant du médicament recherché en quelques clics.
        </p>

        <form method="GET" action="../Dos-page/produit.php" class="">
            <div class="search-box-wrapper">

                <div class="search-box">
                    <input type="text" name="recherche" class="recherche sreach_input" id="champ-recherche-input" placeholder="Rechercher... ">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> search
                    </button>
                </div>

                <div class="suggestions-liste" id="suggestions-liste"></div>

            </div>
        </form>

    </section>

        <?php include'../../Include_general/footer.php'; ?>

    <script src="../Dos-js/autocomplete-produit.js"></script>

</body>
</html>