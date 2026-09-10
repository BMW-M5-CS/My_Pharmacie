<?php
/**
 * Résolution de zone géographique — module partagé
 *
 * Centralise la logique de filtrage géographique utilisée par get_carte.php,
 * get_produit.php et get_carte_produit.php, pour éviter que chaque endpoint
 * ne réinvente sa propre variante (et dérive au fil du temps).
 *
 * Deux modes de zone, au choix de l'appelant (voir conversation produit) :
 *   - bbox   : bornes lat/long du viewport de la carte (lat_min, lat_max, lng_min, lng_max)
 *   - ville  : nom de ville/commune déclaré ou déduit du profil client
 *
 * IMPORTANT : tant que le frontend n'envoie pas encore ces paramètres (le
 * sélecteur de ville et le suivi de viewport sont un chantier séparé, pas
 * encore construit), aucun des deux n'est présent dans la requête. Dans ce
 * cas, AUCUN filtre géographique n'est appliqué — mais le plafond dur
 * (LIMITE_RESULTATS_MAX) protège quand même contre le chargement de la table
 * entière. Cette limite n'est PAS une solution définitive : sans zone, les
 * résultats renvoyés sont juste "les 500 premiers par ordre alphabétique",
 * pas les plus pertinents. Le sélecteur de ville / viewport doit être
 * construit ensuite pour que ce plafond redevienne un vrai filet de sécurité
 * plutôt que le mécanisme de limitation principal.
 */

const LIMITE_RESULTATS_MAX = 500;

function resoudreZoneGeographique(): array {

    $ville = trim($_GET['ville'] ?? '');

    $bbox = null;
    if (isset($_GET['lat_min'], $_GET['lat_max'], $_GET['lng_min'], $_GET['lng_max'])
        && is_numeric($_GET['lat_min']) && is_numeric($_GET['lat_max'])
        && is_numeric($_GET['lng_min']) && is_numeric($_GET['lng_max'])
    ) {
        $bbox = [
            'lat_min' => (float) $_GET['lat_min'],
            'lat_max' => (float) $_GET['lat_max'],
            'lng_min' => (float) $_GET['lng_min'],
            'lng_max' => (float) $_GET['lng_max'],
        ];
    }

    return [
        'ville' => $ville !== '' ? $ville : null,
        'bbox'  => $bbox,
    ];
}

/**
 * Construit la clause SQL (avec placeholders positionnels ?) correspondant à
 * la zone résolue, à concaténer après un WHERE déjà ouvert par l'appelant.
 * Priorité au bbox (plus précis) si les deux sont présents.
 *
 * @return array{sql: string, valeurs: array}
 */
function construireClauseZone(array $zone, string $alias = 'p'): array {

    if ($zone['bbox']) {
        return [
            'sql' => " AND $alias.latitude BETWEEN ? AND ? AND $alias.longitude BETWEEN ? AND ?",
            'valeurs' => [
                $zone['bbox']['lat_min'], $zone['bbox']['lat_max'],
                $zone['bbox']['lng_min'], $zone['bbox']['lng_max'],
            ],
        ];
    }

    if ($zone['ville']) {
        return [
            'sql' => " AND $alias.ville = ?",
            'valeurs' => [$zone['ville']],
        ];
    }

    return ['sql' => '', 'valeurs' => []];
}
