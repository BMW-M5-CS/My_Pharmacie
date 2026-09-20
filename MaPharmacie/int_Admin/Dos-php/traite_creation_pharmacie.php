<?php

require_once '../../Include_general/session_init.php';
require_once '../../int_Public/Dos-php/config.php';
require_once '../Dos-php/verifier_session_admin.php';

if ($_SESSION['admin_role_nom'] !== 'administration_plateforme') {
    header("Location: ../Dos-page/demandes_reservation.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    die("Requête invalide");
}

$nom_pharmacie       = trim($_POST['nom_pharmacie']       ?? '');
$adresse             = trim($_POST['adresse']             ?? '');
$ville               = trim($_POST['ville']               ?? '');
$quartier            = trim($_POST['quartier']            ?? '');
$telephone_pharmacie = trim($_POST['telephone_pharmacie'] ?? '');
$latitude            = $_POST['latitude']                 ?? '';
$longitude           = $_POST['longitude']                ?? '';
$heure_ouverture     = trim($_POST['heure_ouverture']      ?? '');
$heure_fermeture     = trim($_POST['heure_fermeture']      ?? '');

if (empty($nom_pharmacie) || empty($adresse) || empty($ville) || empty($telephone_pharmacie)
    || $latitude === '' || $longitude === '' || empty($heure_ouverture) || empty($heure_fermeture)) {

    header("Location: ../Dos-page/creer_pharmacie.php?erreur=champs_manquants");
    exit();
}

if (!is_numeric($latitude) || !is_numeric($longitude)) {
    header("Location: ../Dos-page/creer_pharmacie.php?erreur=champs_manquants");
    exit();
}

$sql = "INSERT INTO pharmacies
            (nom_pharmacie, adresse, ville, quartier, telephone_pharmacie, latitude, longitude, heure_ouverture, heure_fermeture, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        RETURNING id_pharmacie";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $nom_pharmacie,
    $adresse,
    $ville,
    $quartier ?: null,
    $telephone_pharmacie,
    (float) $latitude,
    (float) $longitude,
    $heure_ouverture,
    $heure_fermeture
]);

header("Location: ../Dos-page/liste_pharmacies.php");
exit();
