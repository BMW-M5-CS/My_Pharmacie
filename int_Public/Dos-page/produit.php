<?php
require_once '../Dos-php/config.php';

$forme     = $_GET['Forme'] ?? '';
$recherche = $_GET['recherche'] ?? '';

if($forme !== ''&& $recherche !=='') {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE forme_pharmaceutique = ? AND nom_medicament ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forme, '%' . $recherche .'%']);

}elseif ($forme !== '') {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE forme_pharmaceutique = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forme]);

}elseif ($recherche !== '') {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE nom_medicament ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $recherche . '%']);

}else {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body data-connecte="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>">

    <?php  include '../Dos-include/header.php'; ?>

    <form class="tout_entete" method="GET" action="produit.php">
        <div class="title">
            <h1><i class="fa-solid fa-pills"></i>  Nos Médicaments</h1>
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
                <button class="Voir-produit" data-id="<?php echo $produit['id_produit']; ?>" >Voir le produit</button>
            </div>
                            <?php endforeach; ?>
                         
        </div>

        <div class="voir_plus">
            <button class="btn-see_more"> Voir plus  <i class="fa-solid fa-arrow-right"></i> </button>
        </div>
    </section>

    <div class="fenetre-produit" id="modal-produit">
        <div class="modal-contenu-produit">

            <button class="modal-fermer-produit" id="modal-fermer-produit">&times;</button>

            <div class="modal-haut-produit">

                <div class="modal-image-produit">
                    <!-- image de produit -->
                </div>

                <div class="modal-infos-produit">
                    <h2 id="modal-nom-produit"></h2>
                    <p><i class="fas fa-dna"></i><span id="modal-nom_generique"></span></p>
                    <p><i class="fas fa-capsules"></i><span id="modal-forme_pharmaceutique"></span></p>
                    <p><i class="fas fa-weight-hanging"></i><span id="modal-dosage"></span></p>
                    <p><i class="fas fa-box"></i><span id="modal-conditionnement"></span></p>
                    <p><i class="fas fa-align-left"></i><span id="modal-description"></span></p>
                    <p><i class="fas fa-tags"></i><span id="modal-prix_unitaire_fcfa"></span></p>
                </div>
            </div>

            <div class="modal-separateur-produit"></div>

            <div class="modal-bas-produit">
                <h3>Pharmacie Disposant de ce produit <span id="modal-nb-pharmacies"></span> </h3>
                <div class="modal-pharmacie-grille" id="modal-liste-pharmacie"></div>
            </div>
        </div>
    </div>

    <!-- ================ MODAL PHARMACIE (partagé) ================ -->
    <div class="fenetre-pharmacie" id="modal-pharmacie">
        <div class="modal-contenu-pharmacie">

            <button class="modal-fermer-pharmacie" id="modal-fermer-pharmacie">&times;</button>

            <div class="modal-haut-pharmacie">
                <div class="modal-image-pharmacie"></div>

                <div class="modal-infos-pharmacie">
                    <h2 id="modal-nom-pharmacie"></h2>
                    <p><i class="fa-solid fa-location-dot"></i> <span id="modal-adresse"></span></p>
                    <p><i class="fa-solid fa-city"></i> <span id="modal-ville"></span></p>
                    <p><i class="fa-solid fa-map"></i> <span id="modal-commune"></span></p>
                    <p><i class="fa-solid fa-map-pin"></i> <span id="modal-quartier"></span></p>
                    <p><i class="fa-solid fa-clock"></i> <span id="modal-horaire"></span></p>
                    <p><i class="fa-solid fa-phone"></i> <span id="modal-telephone"></span></p>
                    <p><i class="fa-solid fa-circle"></i> <span id="modal-garde"></span></p>
                </div>

                <div class="modal-carte" id="modal-carte"></div>
            </div>

            <div class="modal-separateur-pharmacie"></div>

            <div class="modal-bas-pharmacie">
                <h3>Produits disponibles <span id="modal-nb-produits"></span></h3>
                <div class="modal-produits-grille" id="modal-liste-produits"></div>
            </div>

        </div>
    </div>
    
    <?php include'../Dos-include/footer.php'; ?>
    
    <script src="../Dos-js/modal-pharmacie.js"></script>
    <script src="../Dos-js/produit.js"></script>


</body>
</html>