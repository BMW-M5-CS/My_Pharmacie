<?php
/**
 * Résolution de la photo d'une pharmacie
 * =======================================
 *
 * Remplace l'ancien système qui affichait une photo aléatoire non liée à la
 * pharmacie (picsum.photos, basé sur l'id) par un visuel local et contrôlé.
 *
 * La colonne pharmacies.image_url existe déjà en base depuis le début, mais
 * n'a jamais été alimentée ni utilisée par aucune page : aujourd'hui aucune
 * pharmacie n'a de vraie photo. En attendant les vraies photos (Wilfried les
 * prendra une fois les pharmacies réelles enregistrées), on fait comme pour
 * les images produits : un petit jeu de visuels génériques, choisis pour
 * varier visuellement d'une pharmacie à l'autre sans avoir une vraie photo
 * par établissement.
 *
 * Différence importante avec images_produits.php : là-bas, le choix du
 * fichier dépend d'une donnée métier (la forme pharmaceutique). Ici, il n'y a
 * pas d'équivalent (une pharmacie n'a pas de "catégorie" visuelle) : la
 * variante est donc choisie de façon déterministe à partir de id_pharmacie
 * (modulo), juste pour éviter que toutes les cartes soient identiques.
 *
 * QUAND LES VRAIES PHOTOS SERONT DISPONIBLES : il suffira d'écrire le nom du
 * fichier réel dans pharmacies.image_url pour cette pharmacie. Cette fonction
 * privilégie déjà la colonne si elle est renseignée — aucun changement de
 * code ne sera nécessaire à ce moment-là.
 */

const PHOTOS_PHARMACIES_DOSSIER = '../Dos-img/pharmacies/'; // chemin relatif depuis Dos-page/*.php

const PHOTOS_PHARMACIES_GENERIQUES = [
    'pharmacie_1.png',
    'pharmacie_2.png',
    'pharmacie_3.png',
    'pharmacie_4.png',
    'pharmacie_5.png',
    'pharmacie_6.png',
    'pharmacie_7.png',
    'pharmacie_8.png',
    'pharmacie_9.png',
    'pharmacie_10.png',
];

/**
 * Chemin complet (relatif) prêt à mettre dans un attribut src="".
 *
 * @param string|null $image_url_bdd Valeur de pharmacies.image_url (vraie photo si présente)
 * @param int         $id_pharmacie  Utilisé uniquement si $image_url_bdd est vide, pour choisir
 *                                   une variante générique de façon stable (toujours la même
 *                                   pour une pharmacie donnée, plutôt qu'aléatoire à chaque chargement)
 * @param string      $prefixe_relatif Chemin relatif jusqu'au dossier int_Public depuis la page
 *   appelante. Par défaut '../Dos-img/pharmacies/' (pages situées dans int_Public/Dos-page/).
 *   Depuis int_Client/Dos-page/, passer '../../int_Public/Dos-img/pharmacies/'.
 */
function chemin_photo_pharmacie(?string $image_url_bdd, int $id_pharmacie, string $prefixe_relatif = PHOTOS_PHARMACIES_DOSSIER): string {

    // Vraie photo déjà renseignée en base : priorité absolue
    if ($image_url_bdd !== null && trim($image_url_bdd) !== '') {
        return $prefixe_relatif . $image_url_bdd;
    }

    // Pas de vraie photo : variante générique stable selon l'id (jamais aléatoire,
    // pour que la même pharmacie affiche toujours la même image d'un chargement à l'autre)
    $index = $id_pharmacie % count(PHOTOS_PHARMACIES_GENERIQUES);

    return $prefixe_relatif . PHOTOS_PHARMACIES_GENERIQUES[$index];
}