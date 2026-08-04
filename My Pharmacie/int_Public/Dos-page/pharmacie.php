<?php

require_once '../Dos-php/config.php';
require_once '../Dos-php/fonctions_notation.php';

$ville     = $_GET['ville'] ?? '';
$recherche = $_GET['recherche'] ?? '';


// ---------------------------- Préparation du calcul de fiabilité (partagé par les 4 requêtes ci-dessous) ----------------------------

$top_produits = recupererTopProduitsReserves($pdo);

if (!empty($top_produits)) {

    $placeholders_top = implode(',', array_fill(0, count($top_produits), '?'));

    $sql_taux = "(SELECT COUNT(*) * 100.0 / " . count($top_produits) . "
                  FROM stocks s2
                  WHERE s2.id_pharmacie = p.id_pharmacie AND s2.quantite_disponible > 0 AND s2.id_produit IN ($placeholders_top))";

    $params_taux = $top_produits;

} else {

    $sql_taux = "(SELECT COUNT(*) FILTER (WHERE quantite_disponible > 0) * 100.0 / NULLIF(COUNT(*), 0)
                  FROM stocks s2 WHERE s2.id_pharmacie = p.id_pharmacie)";

    $params_taux = [];
}

$sql_champs_notation = "COALESCE((SELECT AVG(a.note) FROM avis a WHERE a.id_pharmacie = p.id_pharmacie), 0) AS note_moyenne,
                         (SELECT COUNT(*) FROM avis a WHERE a.id_pharmacie = p.id_pharmacie) AS nombre_avis,
                         COALESCE($sql_taux, 0) AS taux_disponibilite";


// ---------------------------- Les 4 variantes de recherche, notation incluse ----------------------------

if ($ville !== '' && $recherche !== '') {
    $sql = "SELECT p.id_pharmacie, p.nom_pharmacie, p.adresse, p.ville, p.heure_ouverture, p.heure_fermeture, p.telephone_pharmacie, p.statut_garde,
                   $sql_champs_notation
            FROM pharmacies p WHERE p.ville = ? AND p.nom_pharmacie ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params_taux, [$ville, '%' . $recherche . '%']));

} elseif ($ville !== '') {
    $sql = "SELECT p.id_pharmacie, p.nom_pharmacie, p.adresse, p.ville, p.heure_ouverture, p.heure_fermeture, p.telephone_pharmacie, p.statut_garde,
                   $sql_champs_notation
            FROM pharmacies p WHERE p.ville = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params_taux, [$ville]));

} elseif ($recherche !== '') {
    $sql = "SELECT p.id_pharmacie, p.nom_pharmacie, p.adresse, p.ville, p.heure_ouverture, p.heure_fermeture, p.telephone_pharmacie, p.statut_garde,
                   $sql_champs_notation
            FROM pharmacies p WHERE p.nom_pharmacie ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params_taux, ['%' . $recherche . '%']));

} else {
    $sql = "SELECT p.id_pharmacie, p.nom_pharmacie, p.adresse, p.ville, p.heure_ouverture, p.heure_fermeture, p.telephone_pharmacie, p.statut_garde,
                   $sql_champs_notation
            FROM pharmacies p";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_taux);
}

$pharmacies = $stmt->fetchAll();


function imagePlaceholderPharmacie($id) {
    return 'https://picsum.photos/seed/pharmacie' . $id . '/400/400';
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos pharmacies</title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/pharmacie.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body data-connecte="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>">

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>


    <form class="tout_entete" method="GET" action="pharmacie.php">

        <div class="entete-fond"></div>

        <div class="entete-contenu">

            <div class="title">
                <i class="fa-solid fa-location-dot"></i>
                <h1>Nos Pharmacies Partenaires</h1>
            </div>

            <p class="text">Trouvez rapidement une pharmacie proche de vous</p>

            <div class="entete_input">

                <div class="champ-recherche-wrapper">

                    <div class="champ-recherche">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="recherche" id="champ-recherche-input" class="sreach_input" placeholder="Entrez le nom d'une pharmacie" value="<?php echo htmlspecialchars($recherche); ?>" autocomplete="off">
                    </div>

                    <div class="suggestions-liste" id="suggestions-liste"></div>

                </div>

                <select name="ville" id="ville">

                    <option value="">Toutes les villes</option>

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

                <!-- Emplacement réservé pour le futur filtre "Assurance" : un <select> de plus ici, aucune autre modif nécessaire -->

                <button type="submit" class="sreach_btn"><i class="fas fa-search"></i> Rechercher</button>

            </div>

        </div>

    </form>


    <section class="hero">

        <h2 class="sous-titre">Nos Pharmacies</h2>

        <?php if (empty($pharmacies)) : ?>

            <div class="aucun-resultat">
                <i class="fa-solid fa-magnifying-glass"></i>
                <p>Aucune pharmacie ne correspond à ta recherche.</p>
            </div>

        <?php else : ?>

            <div class="produits-conteneur" id="produits-conteneur">

                <div class="grille">

                    <?php foreach ($pharmacies as $pharmacie) : ?>

                        <article class="carte-pharmacie">

                            <div class="carte-pharmacie-image">
                                <img src="<?php echo imagePlaceholderPharmacie($pharmacie['id_pharmacie']); ?>" alt="<?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?>" loading="lazy">

                                <?php if ($pharmacie['statut_garde']) : ?>
                                    <span class="badge-garde">Garde</span>
                                <?php endif; ?>
                            </div>

                            <div class="carte-pharmacie-corps">

                                <h3 class="carte-pharmacie-nom"><?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?></h3>

                                <div class="carte-pharmacie-notation">

                                    <?php if ($pharmacie['nombre_avis'] > 0) : ?>

                                        <span class="etoiles"><?php echo afficherEtoiles((float) $pharmacie['note_moyenne']); ?></span>
                                        <span class="note-chiffre"><?php echo number_format((float) $pharmacie['note_moyenne'], 1); ?></span>
                                        <span class="nb-avis">(<?php echo (int) $pharmacie['nombre_avis']; ?> avis)</span>

                                    <?php else : ?>

                                        <span class="pas-avis">Pas encore d'avis</span>

                                    <?php endif; ?>

                                </div>

                                <?php if (estPharmacieRecommandee((float) $pharmacie['note_moyenne'], (float) $pharmacie['taux_disponibilite'])) : ?>

                                    <div class="badge-recommandee">
                                        <i class="fa-solid fa-award"></i> Pharmacie recommandée
                                    </div>

                                <?php endif; ?>

                                <div class="carte-pharmacie-infos">
                                    <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pharmacie['ville']); ?></p>
                                    <p><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($pharmacie['heure_ouverture']); ?> – <?php echo htmlspecialchars($pharmacie['heure_fermeture']); ?></p>
                                    <p><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($pharmacie['telephone_pharmacie']); ?></p>
                                </div>

                                <button class="voir" data-id="<?php echo $pharmacie['id_pharmacie']; ?>">
                                    <i class="fa-solid fa-store"></i> Voir la pharmacie
                                </button>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <button class="btn-remonter" id="btn-remonter" aria-label="Revenir en haut de la liste">
                    <i class="fa-solid fa-arrow-up"></i>
                </button>

            </div>

            <div class="plus">
                <button class="voir_plus">Voir plus <i class="fa-solid fa-arrow-right"></i></button>
            </div>

        <?php endif; ?>

    </section>


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

    <script src="../Dos-js/modal-pharmacie.js"></script>
    <script src="../Dos-js/pharmacie.js"></script>

</body>
</html>