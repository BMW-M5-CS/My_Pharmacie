<?php

// Récupère les N produits les plus réservés, toutes pharmacies confondues.
// C'est la base du calcul du taux de disponibilité "intelligent" qu'on avait décidé :
// mieux vaut mesurer si une pharmacie a les produits vraiment demandés,
// plutôt que son pourcentage de stock sur l'ensemble du catalogue.
function recupererTopProduitsReserves(PDO $pdo, int $limite = 15): array {

    $sql = "SELECT s.id_produit, COUNT(*) AS nb
            FROM reservations r
            JOIN stocks s ON s.id_stock = r.id_stock
            GROUP BY s.id_produit
            ORDER BY nb DESC
            LIMIT ?";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


// Détermine si une pharmacie mérite le badge "Recommandée"
function estPharmacieRecommandee(float $note_moyenne, float $taux_disponibilite): bool {

    $SEUIL_NOTE          = 4.0;
    $SEUIL_DISPONIBILITE = 75.0;

    return $note_moyenne >= $SEUIL_NOTE && $taux_disponibilite >= $SEUIL_DISPONIBILITE;
}


// Transforme une note (ex: 4.3) en HTML d'étoiles (pleines / demi / vides)
function afficherEtoiles(float $note): string {

    $pleines = floor($note);
    $demi    = ($note - $pleines) >= 0.5 ? 1 : 0;
    $vides   = 5 - $pleines - $demi;

    $html = str_repeat('<i class="fa-solid fa-star"></i>', (int) $pleines);

    if ($demi) {
        $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }

    $html .= str_repeat('<i class="fa-regular fa-star"></i>', (int) $vides);

    return $html;
}