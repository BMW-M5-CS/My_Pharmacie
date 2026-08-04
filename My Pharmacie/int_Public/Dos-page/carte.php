<?php
require_once '../Dos-php/config.php';

$sql = "SELECT nom_pharmacie, adresse, ville, latitude, longitude, 
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
<body>

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

            <button class="btn-geoloc" id="btn-geoloc">
                <i class="fas fa-location-crosshairs"></i> Me localiser
            </button>

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

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <div id="map"></div>

    </div>

    <div id="msg-geoloc"></div>

    <?php include '../../Include_general/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../Dos-js/carte.js"></script>

</body>
</html>