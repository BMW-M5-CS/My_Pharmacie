
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

         <?php include'../Dos-include/header.php'; ?>

    <section class="hero">

        <h1>Bienvenue sur Votre Pharmacie</h1>
        <p>
            Avec nous trouver un médicaments et la pharmacie ou il est disponible n'est plus une <br>
            dificulté localisez instantanement la pharmacie la plus proche disposant <br>
            du médicaments recherché en seulement quelques cliques
        </p>

        <div class="search-box">
            <input type="text" class="recherche" class placeholder="Rechercher... ">
            <button class="btn-search"><i class="fas fa-search"></i> search</button>
        </div>

    </section>

        <?php include'../Dos-include/footer.php'; ?>

    
</body>
</html>