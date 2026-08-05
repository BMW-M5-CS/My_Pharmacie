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

// Vérifie si un utilisateur a le droit de noter une pharmacie précise
// (centralisée ici pour ne plus la dupliquer entre get_pharmacie.php et traite_avis.php)
function verifierEligibiliteAvis(PDO $pdo, int $id_user, int $id_pharmacie): bool {

    $sql = "SELECT COUNT(*) AS total
            FROM reservations r
            JOIN stocks s ON s.id_stock = r.id_stock
            WHERE r.id_user = ? AND s.id_pharmacie = ? AND r.statut = 'confirmee'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_user, $id_pharmacie]);

    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
}


// Renvoie le nombre d'avis pour chaque note (5 à 1), pour afficher les barres de répartition
function repartitionAvis(PDO $pdo, int $id_pharmacie): array {

    $sql = "SELECT note, COUNT(*) AS total FROM avis WHERE id_pharmacie = ? GROUP BY note";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_pharmacie]);
    $resultats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $repartition = [];

    for ($i = 5; $i >= 1; $i--) {
        $repartition[$i] = (int) ($resultats[$i] ?? 0);
    }

    return $repartition;
}