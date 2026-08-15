<?php
require_once '../Dos-php/config.php';

$forme     = $_GET['Forme'] ?? '';
$recherche = $_GET['recherche'] ?? '';

if ($forme !== '' && $recherche !== '') {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE forme_pharmaceutique = ? AND nom_medicament ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forme, '%' . $recherche . '%']);

} elseif ($forme !== '') {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE forme_pharmaceutique = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forme]);

} elseif ($recherche !== '') {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits WHERE nom_medicament ILIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $recherche . '%']);

} else {
    $sql = "SELECT id_produit, nom_medicament, forme_pharmaceutique, prix_unitaire_fcfa
            FROM produits";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$produits = $stmt->fetchAll();


function imagePlaceholderProduit($id) {
    return 'https://picsum.photos/seed/produit' . $id . '/400/400';
}
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
<body data-connecte="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>" data-assurance-defaut="<?php echo htmlspecialchars($_SESSION['assurance_defaut'] ?? ''); ?>">

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>


    <form class="tout_entete" method="GET" action="produit.php">

        <div class="entete-fond"></div>

        <div class="entete-contenu">

            <div class="title">
                <i class="fa-solid fa-pills"></i>
                <h1>Nos Médicaments</h1>
            </div>

            <p class="text">Trouvez rapidement un médicament le plus proche de vous</p>

            <div class="entete_input">

                <div class="champ-recherche-wrapper">

                    <div class="champ-recherche">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="recherche" class="sreach_input" id="champ-recherche-input" placeholder="Entrez le nom du produit recherché" value="<?php echo htmlspecialchars($recherche); ?>">
                    </div>

                    <div class="suggestions-liste" id="suggestions-liste"></div>

                </div>

                <div class="select-forme-wrapper" id="select-forme-wrapper">

                    <input type="hidden" name="Forme" id="forme-valeur" value="<?php echo htmlspecialchars($forme); ?>">

                    <button type="button" class="select-forme-affiche" id="select-forme-affiche">
                        <span id="select-forme-texte"><?php echo $forme !== '' ? htmlspecialchars($forme) : 'Toutes les formes'; ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div class="formes-liste" id="formes-liste">

                        <div class="forme-item" data-valeur="">Toutes les formes</div>
                        <div class="forme-item" data-valeur="Comprimé">Comprimé</div>
                        <div class="forme-item" data-valeur="Gélule">Gélule</div>
                        <div class="forme-item" data-valeur="Capsule">Capsule</div>
                        <div class="forme-item" data-valeur="Comprimé effervescent">Comprimé effervescent</div>
                        <div class="forme-item" data-valeur="Poudre">Poudre</div>
                        <div class="forme-item" data-valeur="Granulés">Granulés</div>
                        <div class="forme-item" data-valeur="Lyophilisat">Lyophilisat</div>
                        <div class="forme-item" data-valeur="Sirop">Sirop</div>
                        <div class="forme-item" data-valeur="Solution buvable">Solution buvable</div>
                        <div class="forme-item" data-valeur="Suspension buvable">Suspension buvable</div>
                        <div class="forme-item" data-valeur="Gouttes">Gouttes</div>
                        <div class="forme-item" data-valeur="Ampoule buvable">Ampoule buvable</div>
                        <div class="forme-item" data-valeur="Solution injectable">Solution injectable</div>
                        <div class="forme-item" data-valeur="Poudre pour injection">Poudre pour injection</div>
                        <div class="forme-item" data-valeur="Suspension injectable">Suspension injectable</div>
                        <div class="forme-item" data-valeur="Crème">Crème</div>
                        <div class="forme-item" data-valeur="Pommade">Pommade</div>
                        <div class="forme-item" data-valeur="Gel">Gel</div>
                        <div class="forme-item" data-valeur="Lotion">Lotion</div>
                        <div class="forme-item" data-valeur="Spray">Spray</div>
                        <div class="forme-item" data-valeur="Suppositoire">Suppositoire</div>
                        <div class="forme-item" data-valeur="Ovule">Ovule</div>
                        <div class="forme-item" data-valeur="Aérosol / Inhalateur">Aérosol / Inhalateur</div>
                        <div class="forme-item" data-valeur="Spray nasal">Spray nasal</div>

                    </div>

                </div>

                <button type="submit" class="sreach_btn"><i class="fas fa-search"></i> Rechercher</button>

            </div>

        </div>

    </form>


    <section class="hero">

        <h2 class="sous-titre">Nos Produits</h2>

        <?php if (empty($produits)) : ?>

            <div class="aucun-resultat">
                <i class="fa-solid fa-magnifying-glass"></i>
                <p>Aucun produit ne correspond à ta recherche.</p>
            </div>

        <?php else : ?>

            <div class="produits-conteneur" id="produits-conteneur">

                <div class="grille">

                    <?php foreach ($produits as $produit) : ?>

                        <article class="carte-produit">

                            <div class="carte-produit-image">
                                <img src="<?php echo imagePlaceholderProduit($produit['id_produit']); ?>" alt="<?php echo htmlspecialchars($produit['nom_medicament']); ?>" loading="lazy">
                            </div>

                            <div class="carte-produit-corps">

                                <h3 class="carte-produit-nom"><?php echo htmlspecialchars($produit['nom_medicament']); ?></h3>

                                <span class="carte-produit-forme"><?php echo htmlspecialchars($produit['forme_pharmaceutique']); ?></span>

                                <div class="carte-produit-bas">
                                    <span class="carte-produit-prix"><?php echo htmlspecialchars($produit['prix_unitaire_fcfa']); ?> FCFA</span>
                                    <button class="Voir-produit" data-id="<?php echo $produit['id_produit']; ?>">Voir</button>
                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <button class="btn-remonter" id="btn-remonter" aria-label="Revenir en haut de la liste">
                    <i class="fa-solid fa-arrow-up"></i>
                </button>

            </div>

            <div class="voir_plus">
                    <button class="btn-see_more">Voir plus <i class="fa-solid fa-arrow-right"></i></button>
            </div>


        <?php endif; ?>

    </section>

                                         <!-- fenetre du modale produit  -->

    <div class="fenetre-produit" id="modal-produit">
        <div class="modal-contenu-produit">

            <button class="modal-fermer-produit" id="modal-fermer-produit">&times;</button>

            <div class="modal-haut-produit">

                <div class="modal-image-produit">
                    <img id="modal-image-produit-img" src="" alt="">
                </div>

                <div class="modal-infos-produit">

                    <h2 id="modal-nom-produit"></h2>

                    <div class="modal-infos-liste">
                        <p><i class="fas fa-dna"></i><span id="modal-nom_generique"></span></p>
                        <p><i class="fas fa-capsules"></i><span id="modal-forme_pharmaceutique"></span></p>
                        <p><i class="fas fa-weight-hanging"></i><span id="modal-dosage"></span></p>
                        <p><i class="fas fa-box"></i><span id="modal-conditionnement"></span></p>
                        <p><i class="fas fa-align-left"></i><span id="modal-description"></span></p>
                        <p><i class="fas fa-tags"></i><span id="modal-prix_unitaire_fcfa"></span></p>
                    </div>

                </div>

            </div>

            <div class="modal-separateur-produit"></div>

            <div class="modal-bas-produit">
                <h3>Pharmacies disposant de ce produit <span id="modal-nb-pharmacies"></span></h3>
                <div class="modal-pharmacie-grille" id="modal-liste-pharmacie"></div>
            </div>

        </div>
    </div>



                <!-- fenetre du modale de la pharmacie partager avec le modale produit -->

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

                    <div class="modal-assurances-pharmacie" id="modal-assurances-pharmacie"></div>

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

    <script src="../Dos-js/distance-utils.js"></script>
    <script src="../Dos-js/modal-pharmacie.js"></script>
    <script src="../Dos-js/autocomplete-produit.js"></script>
    <script src="../Dos-js/produit.js"></script>

</body>
</html>