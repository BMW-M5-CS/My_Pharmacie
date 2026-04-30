<?php
require_once '../Dos-php/config.php';

$forme     = $_GET['Forme'] ?? '';
$recherche = $_GET['recherche'] ?? '';

if($forme !== ''&& $recherche !=='') {
    $sql = "SELECT nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE forme_pharmaceutique = ? AND nom_medicament ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forme, '%' . $recherche .'%']);

}elseif ($forme !== '') {
    $sql = "SELECT nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE forme_pharmaceutique = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forme]);

}elseif ($recherche !== '') {
    $sql = "SELECT nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE nom_medicament ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $recherche . '%']);

}else {
    $sql = "SELECT nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$produits = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos produits</title>
    <link rel="stylesheet" href="../Dos-css/header.css"> 
    <link rel="stylesheet" href="../Dos-css/produit.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

      <?php include'../Dos-include/header.php'; ?>

    <form class="tout_entete" method="GET" action="produit.php">
        <div class="title">
            <h1>Nos Médicaments</h1>
        </div>
        <div class="text">
            Trouvez rapidement un medicament le plus proche de vous
        </div>      

        <div class="entete_input">

            <input type="text" name="recherche" class="sreach_input" placeholder="Entrez le nom du produit rechercher">

            <select name="Forme" id="Forme">
                <option value=""> Toutes les formes </option>

                <!-- Formes solides -->
                <option value="Comprimé">Comprimé</option>
                <option value="Gélule">Gélule</option>
                <option value="Capsule">Capsule</option>
                <option value="Comprimé effervescent">Comprimé effervescent</option>
                <option value="Poudre">Poudre</option>
                <option value="Granulés">Granulés</option>
                <option value="Lyophilisat">Lyophilisat</option>

                <!-- Formes liquides -->
                <option value="Sirop">Sirop</option>
                <option value="Solution buvable">Solution buvable</option>
                <option value="Suspension buvable">Suspension buvable</option>
                <option value="Gouttes">Gouttes</option>
                <option value="Ampoule buvable">Ampoule buvable</option>

                <!-- Formes Injectables -->
                <option value="Solution injectable">Solution injectable</option>
                <option value="Poudre pour injection">Poudre pour injection</option>
                <option value="Suspension injectable">Suspension injectable</option>

                <!-- Formes topiques (usage externe) -->
                <option value="Crème">Crème</option>
                <option value="Pommade">Pommade</option>
                <option value="Gel">Gel</option>
                <option value="Lotion">Lotion</option>
                <option value="Spray">Spray</option>

                <!-- Formes rectales/vaginales -->
                <option value="Suppositoire">Suppositoire</option>
                <option value="Ovule">Ovule</option>

                <!-- Formes respiratoires -->
                <option value="Aérosol / Inhalateur">Aérosol / Inhalateur</option>
                <option value="Spray nasal">Spray nasal</option>
            </select>

            <button class="sreach_btn"><i class="fas fa-search"></i> Rechercher</button>
        </div>
    </form>

    <section class="hero">
        <div class="sous-titre">
            <h2>Nos Produits</h2>
        </div>

        <div class="grille">
                               <?php foreach($produits as $produit) : ?>

            <div class="produit">
                <div class="image">
                    <p>image</p>
                </div>
                <div class="prod-name">
                   <h3> <?php echo htmlspecialchars($produit['nom_medicament']); ?> </h3>
                </div>
                <div class="prix-prod">
                   <p> <?php echo htmlspecialchars($produit['prix_unitaire_fcfa']); ?> FCFA </p>
                </div>
                <button class="Voir-produit">Voir le produit</button>
            </div>
                            <?php endforeach; ?>
                         
        </div>

    </section>
    
    <?php include'../Dos-include/footer.php'; ?>
    
    <script src="../Dos-js/produit.js"></script>
</body>
</html>