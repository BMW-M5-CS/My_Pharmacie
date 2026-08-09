<?php
require_once '../Dos-php/config.php';

$sql = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, latitude, longitude, 
               statut_garde, heure_ouverture, heure_fermeture, telephone_pharmacie
        FROM pharmacies
        ORDER BY nom_pharmacie ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pharmacies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte des Pharmacies</title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/carte.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body data-connecte="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>">

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>


    <div class="carte-entete">

        <div class="entete-fond"></div>

        <div class="entete-contenu">

            <div class="title">
                <i class="fas fa-map-marked-alt"></i>
                <h1>Carte des Pharmacies</h1>
            </div>

            <p class="text">Retrouvez toutes les pharmacies partenaires localisées sur la carte</p>

            <div class="entete_input">

                <div class="champ-recherche-wrapper">

                    <div class="champ-recherche">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="carte-recherche-produit-input" class="sreach_input" placeholder="Chercher un médicament (ex : Paracétamol)" autocomplete="off">
                        <button type="button" id="carte-recherche-produit-effacer" class="carte-recherche-effacer" title="Effacer la recherche">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="carte-suggestions-produit" id="carte-suggestions-produit"></div>

                </div>

                <button type="button" id="carte-recherche-produit-btn" class="sreach_btn">
                    <i class="fas fa-search"></i> Rechercher
                </button>

                <button class="sreach_btn" id="btn-geoloc">
                    <i class="fas fa-location-crosshairs"></i> Me localiser
                </button>

            </div>

            <div class="carte-recherche-statut" id="carte-recherche-statut"></div>

        </div>

    </div>


    <div class="legende">
        <div class="legende-item">
            <span class="legende-dot dot-vert"></span> Pharmacie ouverte
        </div>
        <div class="legende-item">
            <span class="legende-dot dot-rouge"></span> Pharmacie de garde
        </div>
        <div class="legende-item">
            <span class="legende-dot dot-bleu"></span> Votre position
        </div>
        <div class="legende-item">
            <span class="legende-dot dot-or"></span> La plus proche avec ce produit
        </div>
    </div>


    <button class="btn-basculer-vue" id="btn-basculer-vue">
        <i class="fas fa-list"></i> <span id="texte-basculer">Voir la liste</span>
    </button>

    <div class="carte-wrapper mode-carte" id="carte-wrapper">

        <div class="liste-pharmacies" id="liste-pharmacies">

            <div class="liste-titre">
                Pharmacies <span id="compteur">(<?php echo count($pharmacies); ?>)</span>
            </div>

            <div class="liste-scroll">

                <?php foreach ($pharmacies as $index => $p) : ?>

                    <div class="pharmacie-item" id="item-<?php echo $index; ?>" data-index="<?php echo $index; ?>">

                        <div class="pharm-nom">
                            <?php echo htmlspecialchars($p['nom_pharmacie']); ?>
                        </div>

                        <div class="pharm-adresse">
                            <i class="fas fa-location-dot"></i>
                            <?php echo htmlspecialchars($p['adresse'] . ', ' . $p['ville']); ?>
                        </div>

                        <div class="pharm-statut">
                            <?php if ($p['statut_garde']) : ?>
                                <span class="statut-garde"><i class="fas fa-circle"></i> Pharmacie de garde</span>
                            <?php else : ?>
                                <span class="statut-ouvert"><i class="fas fa-circle"></i> Ouverte</span>
                            <?php endif; ?>
                        </div>

                        <button class="pharm-btn-voir" data-id="<?php echo $p['id_pharmacie']; ?>">
                            <i class="fa-solid fa-store"></i> Voir la pharmacie
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <div id="map"></div>

    </div>

    <div id="msg-geoloc"></div>


    <div class="fenetre-pharmacie" id="modal-pharmacie">
        <div class="modal-contenu-pharmacie">

            <button class="modal-fermer-pharmacie" id="modal-fermer-pharmacie">&times;</button>

            <div class="modal-haut-pharmacie">

                <div class="modal-image-pharmacie">
                    <img id="modal-image-pharmacie-img" src="" alt="">
                </div>

                <div class="modal-infos-pharmacie">

                    <h2 id="modal-nom-pharmacie"></h2>

                    <div class="modal-notation" id="modal-notation"></div>

                    <div class="modal-infos-liste">
                        <p><i class="fa-solid fa-location-dot"></i> <span id="modal-adresse"></span></p>
                        <p><i class="fa-solid fa-city"></i> <span id="modal-ville"></span></p>
                        <p><i class="fa-solid fa-map"></i> <span id="modal-commune"></span></p>
                        <p><i class="fa-solid fa-map-pin"></i> <span id="modal-quartier"></span></p>
                        <p><i class="fa-solid fa-clock"></i> <span id="modal-horaire"></span></p>
                        <p><i class="fa-solid fa-phone"></i> <span id="modal-telephone"></span></p>
                        <p><i class="fa-solid fa-circle"></i> <span id="modal-garde"></span></p>
                    </div>

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


    <?php include '../../Include_general/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../Dos-js/distance-utils.js"></script>
    <script src="../Dos-js/modal-pharmacie.js"></script>
    <script src="../Dos-js/carte.js"></script>

</body>
</html>