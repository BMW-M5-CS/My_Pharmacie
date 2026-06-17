<?php
require_once '../Dos-php/config.php';

$ville     = $_GET['ville'] ?? '';
$recherche = $_GET['recherche'] ?? '';

if ($ville !== '' && $recherche !=='') {
    $sql = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, heure_ouverture, heure_fermeture, telephone_pharmacie, statut_garde
            FROM pharmacies WHERE ville = ? AND nom_pharmacie ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ville, '%' . $recherche . '%']);

} elseif ($ville !== '') {
    $sql = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, heure_ouverture, heure_fermeture, telephone_pharmacie, statut_garde
            FROM pharmacies WHERE ville = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ville]);

} elseif ($recherche !=='') {
    $sql = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, heure_ouverture, heure_fermeture, telephone_pharmacie, statut_garde
            FROM pharmacies WHERE nom_pharmacie ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt ->execute(['%' . $recherche . '%']);

}else {
    $sql = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, heure_ouverture, heure_fermeture, telephone_pharmacie, statut_garde
            FROM pharmacies";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$pharmacies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos pharmacie</title>
    <link rel="stylesheet" href="../Dos-css/header.css"> 
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/pharmacie.css">   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body data-connecte="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>">
    
    <?php include'../Dos-include/header.php'; ?>

    <form method="GET" action="pharmacie.php" class="tout_entete">
        <div class="title">
            <h1>Nos pharmacies Partenaires</h1>
        </div>
        <div class="text">
            Trouvez rapidement une pharmacie proche de vous
        </div>      

        <div class="entete_input">

            <input type="text" name="recherche" class="sreach_input" placeholder="Entrez le nom d'une pharmacie rechercher">

            <select name="ville" id="ville">

                <option value="">Toutes les Villes</option>

                <!-- Région Maritime -->
                <option value="Lomé">Lomé</option>
                <option value="Aneho">Aného</option>
                <option value="Tabligbo">Tabligbo</option>
                <option value="Vogan">Vogan</option>
                <option value="Tsevie">Tsévié</option>

                <!-- Région Plateaux -->
                <option value="Kpalime">Kpalimé</option>
                <option value="Atakpame">Atakpamé</option>
                <option value="Badou">Badou</option>
                <option value="Notsè">Notsè</option>

                <!-- Région Centrale -->
                <option value="Sokode">Sokodé</option>
                <option value="Tchamba">Tchamba</option>
                <option value="Blitta">Blitta</option>

                <!-- Région Kara -->
                <option value="Kara">Kara</option>
                <option value="Bafilo">Bafilo</option>
                <option value="Niamtougou">Niamtougou</option>
                <option value="Bassari">Bassari</option>

                <!-- Région Savanes -->
                <option value="Dapaong">Dapaong</option>
                <option value="Mango">Mango</option>
                <option value="Tandjouare">Tandjouaré</option>
            </select>

            <button class="sreach_btn"><i class="fas fa-search"></i> Rechercher</button>
        </div>
    </form>

    <section class="hero">
        <div class="sous-titre">
            <h2>Nos Pharmacie de la place</h2>
        </div>

        <div class="grille">                       
                                     <?php foreach($pharmacies as $pharmacie) : ?>

            <div class="pharmacie">
                <div class="name">
                    <h2 class="name"> <?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?> </h2>
                </div>
                <div class="contenu">
                    <div class="info">
                        <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pharmacie['ville']); ?> </p>
                        <br>
                        <p><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($pharmacie['heure_ouverture']);?> - <?php echo htmlspecialchars($pharmacie['heure_fermeture']);?> </p>
                        <br>
                        <p><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($pharmacie['telephone_pharmacie']); ?> </p>
                        <br>
                        <p><i class="fa-solid fa-circle"></i> <?php echo $pharmacie['statut_garde'] ? 'Pharmacie de garde' : 'Ouverte'; ?> </p>
                    </div>
                    <div class="imag">
                        
                    </div>
                </div>
                <button class="voir" data-id="<?php echo $pharmacie['id_pharmacie']; ?>">Voir la pharmacie</button>
            </div>
                                       <?php endforeach; ?>

           

        </div>

        <div class="plus">
            <button class="voir_plus">Voir plus <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </section>

    <div class="modal-overlay" id="modal-pharmacie">
        <div class="modal-contenu">

            <button class="modal-fermer" id="modal-fermer">&times;</button>

            <div class="modal-haut">

                    <div class="modal-image">
                         <!-- image de la pharmacie -->
                    </div>

                    <div class="modal-infos">
                        <h2 id="modal-nom"></h2>
                        <p><i class="fa-solid fa-location-dot"></i> <span id="modal-adresse"></span></p>
                        <p><i class="fa-solid fa-city"></i> <span id="modal-ville"></span></p>
                        <p><i class="fa-solid fa-map"></i> <span id="modal-commune"></span></p>
                        <p><i class="fa-solid fa-map-pin"></i> <span id="modal-quartier"></span></p>
                        <p><i class="fa-solid fa-clock"></i> <span id="modal-horaire"></span></p>
                        <p><i class="fa-solid fa-phone"></i> <span id="modal-telephone"></span></p>
                        <p><i class="fa-solid fa-circle"></i> <span id="modal-garde"></span></p>
                    </div>

                    <div class="modal-carte" id="modal-carte">
                        <!-- le cade de la carte -->
                    </div>
            </div>

            <div class="modal-separateur"></div>

            <div class="modal-bas">
                <h3>Produits disponibles <span id="modal-nb-produits"></span> </h3>
                <div class="modal-produits-grille" id="modal-liste-produits"></div>
            </div>

        </div>
    </div>

     
    <?php include'../Dos-include/footer.php'; ?>

    <script src="../Dos-js/pharmacie.js"></script>
</body>
</html>