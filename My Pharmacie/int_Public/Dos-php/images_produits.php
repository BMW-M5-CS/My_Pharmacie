<?php
/**
 * Association forme pharmaceutique -> image générique
 * ======================================================
 *
 * Remplace l'ancien système qui affichait une photo aléatoire non liée au
 * produit (picsum.photos, basé sur l'id) par une vraie image représentative
 * de la forme du médicament (comprimé, gélule, sirop...).
 *
 * Une seule image par CATÉGORIE visuelle, pas une par forme exacte : 24
 * formes différentes existent dans la base, mais elles se regroupent en 8
 * familles qui se ressemblent visuellement. Ça évite d'avoir à gérer 24
 * fichiers (et bien sûr, une image par médicament exact serait ingérable à
 * l'échelle de centaines de produits).
 *
 * INSTALLATION REQUISE (à faire une fois) : place les 8 fichiers images
 * (issus du script uniformiser_images.py) dans ce dossier, avec exactement
 * ces noms :
 *
 *   int_Public/Dos-img/produits/comprime.png
 *   int_Public/Dos-img/produits/gelule.png
 *   int_Public/Dos-img/produits/poudre.png
 *   int_Public/Dos-img/produits/sirop.png
 *   int_Public/Dos-img/produits/injectable.png
 *   int_Public/Dos-img/produits/topique.png
 *   int_Public/Dos-img/produits/spray.png
 *   int_Public/Dos-img/produits/suppositoire.png
 *
 * Tant que ces fichiers ne sont pas présents, chemin_image_pour_forme()
 * renvoie quand même un chemin valide (pas d'erreur), mais l'image sera
 * cassée dans le navigateur jusqu'à ce que tu places les fichiers.
 */

const IMAGES_PRODUITS_DOSSIER = '../Dos-img/produits/'; // chemin relatif depuis Dos-page/*.php

/**
 * Fait correspondre une forme_pharmaceutique exacte (telle qu'enregistrée en
 * base) à l'un des 8 fichiers image génériques.
 */
function nom_fichier_image_pour_forme(?string $forme): string {

    $correspondances = [
        // Comprimés
        'Comprimé'              => 'comprime.png',
        'Comprimé effervescent' => 'comprime.png',
        'Capsule'                => 'comprime.png',

        // Gélules
        'Gélule'      => 'gelule.png',
        'Lyophilisat' => 'gelule.png',

        // Poudres
        'Poudre'                => 'poudre.png',
        'Granulés'              => 'poudre.png',
        'Poudre pour injection' => 'poudre.png',

        // Liquides buvables
        'Sirop'              => 'sirop.png',
        'Solution buvable'   => 'sirop.png',
        'Suspension buvable' => 'sirop.png',
        'Gouttes'            => 'sirop.png',
        'Ampoule buvable'    => 'sirop.png',

        // Injectables
        'Solution injectable'   => 'injectable.png',
        'Suspension injectable' => 'injectable.png',

        // Topiques
        'Crème'   => 'topique.png',
        'Pommade' => 'topique.png',
        'Gel'     => 'topique.png',
        'Lotion'  => 'topique.png',

        // Sprays / inhalateurs
        'Spray'                 => 'spray.png',
        'Aérosol / Inhalateur'  => 'spray.png',
        'Spray nasal'           => 'spray.png',

        // Suppositoires / ovules
        'Suppositoire' => 'suppositoire.png',
        'Ovule'         => 'suppositoire.png',
    ];

    // Forme inconnue ou absente -> repli sur l'image "comprimé" plutôt que de
    // casser l'affichage. Signalé en log pour qu'une forme oubliée dans cette
    // liste soit remarquée plutôt que de silencieusement afficher la mauvaise image.
    if ($forme === null || !isset($correspondances[$forme])) {
        error_log("[nom_fichier_image_pour_forme] Forme pharmaceutique non reconnue : " . var_export($forme, true));
        return 'comprime.png';
    }

    return $correspondances[$forme];
}

/**
 * Chemin complet (relatif) prêt à mettre dans un attribut src="".
 *
 * @param string|null $forme Forme pharmaceutique du produit
 * @param string $prefixe_relatif Chemin relatif jusqu'au dossier int_Public
 *   depuis la page appelante. Par défaut '../Dos-img/produits/' (pages situées
 *   dans int_Public/Dos-page/). Depuis int_Client/Dos-page/, passer
 *   '../../int_Public/Dos-img/produits/'.
 */
function chemin_image_pour_forme(?string $forme, string $prefixe_relatif = IMAGES_PRODUITS_DOSSIER): string {
    return $prefixe_relatif . nom_fichier_image_pour_forme($forme);
}
