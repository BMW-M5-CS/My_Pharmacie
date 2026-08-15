<?php

require_once 'config.php';
require_once 'fonctions_horaires.php';

$sql  = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, commune, quartier,
                latitude, longitude, statut_garde,
                heure_ouverture, heure_fermeture, telephone_pharmacie
         FROM pharmacies
         ORDER BY nom_pharmacie ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pharmacies as &$pharmacie) {
    $pharmacie['statut_calcule'] = calculerStatutPharmacie(
        (bool) $pharmacie['statut_garde'],
        $pharmacie['heure_ouverture'],
        $pharmacie['heure_fermeture']
    );
}
unset($pharmacie);


// ---------------------------- Assurances acceptées par chaque pharmacie ----------------------------

$id_pharmacies_liste = array_column($pharmacies, 'id_pharmacie');

$assurances_par_pharmacie = [];

if (!empty($id_pharmacies_liste)) {

    $marqueurs_sql = implode(',', array_fill(0, count($id_pharmacies_liste), '?'));

    $sql_assurances = "SELECT pa.id_pharmacie, a.nom_assurance, a.logo_assurance
                        FROM pharmacie_assurances pa
                        JOIN assurances a ON a.id_assurance = pa.id_assurance
                        WHERE pa.id_pharmacie IN ($marqueurs_sql)";

    $stmt_assurances = $pdo->prepare($sql_assurances);
    $stmt_assurances->execute($id_pharmacies_liste);

    foreach ($stmt_assurances->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $assurances_par_pharmacie[$ligne['id_pharmacie']][] = [
            'nom_assurance'  => $ligne['nom_assurance'],
            'logo_assurance' => $ligne['logo_assurance'],
        ];
    }
}

foreach ($pharmacies as &$pharmacie) {
    $pharmacie['assurances'] = $assurances_par_pharmacie[$pharmacie['id_pharmacie']] ?? [];
}
unset($pharmacie);


header('Content-Type: application/json');
echo json_encode($pharmacies, JSON_UNESCAPED_UNICODE);
?>