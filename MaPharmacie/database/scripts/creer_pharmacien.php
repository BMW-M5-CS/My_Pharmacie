<?php

// ============================================================================
// Création d'un compte administrateur — SCRIPT EN LIGNE DE COMMANDE UNIQUEMENT
// ----------------------------------------------------------------------------
// Usage :
//   php creer_pharmacien.php
//   (puis répondre aux questions posées)
//
// Crée soit un compte PHARMACIEN (rattaché à une pharmacie précise), soit un
// compte ADMINISTRATION PLATEFORME (voit et gère toutes les pharmacies —
// c'est le rôle qui a remplacé l'idée initiale de "Ministère").
//
// Volontairement PAS un formulaire web : ces comptes donnent accès à des
// données sensibles (réservations, coordonnées clients), donc leur création
// doit rester entre les mains de l'équipe MaPharmacie, jamais exposée sur
// une URL publique.
// ============================================================================

// Garde-fou : refuse absolument de s'exécuter si jamais appelé via un serveur
// web (PHP_SAPI vaut 'cli' uniquement en ligne de commande).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("Ce script ne peut être exécuté qu'en ligne de commande.\n");
}

require_once __DIR__ . '/../../int_Public/Dos-php/config.php';


function demander(string $question, bool $optionnel = false): string {
    do {
        echo $question;
        $reponse = trim(fgets(STDIN));
        if ($reponse === '' && !$optionnel) {
            echo "  → Ce champ est obligatoire.\n";
        }
    } while ($reponse === '' && !$optionnel);
    return $reponse;
}

echo "=== Création d'un compte administrateur ===\n\n";
echo "Quel type de compte ?\n";
echo "  [1] Pharmacien (rattaché à une pharmacie précise)\n";
echo "  [2] Administration plateforme (voit et gère toutes les pharmacies)\n";

$type_choisi = demander("\nVotre choix (1 ou 2) : ");

if (!in_array($type_choisi, ['1', '2'], true)) {
    die("Choix invalide.\n");
}

$pharmacie_valide = null;

if ($type_choisi === '1') {

    // ----- Choix de la pharmacie, uniquement pour un compte pharmacien -----

    $sql_pharmacies = "SELECT id_pharmacie, nom_pharmacie, ville FROM pharmacies ORDER BY nom_pharmacie";
    $pharmacies     = $pdo->query($sql_pharmacies)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pharmacies)) {
        die("Aucune pharmacie en base. Créez d'abord la pharmacie avant son compte.\n");
    }

    echo "\nPharmacies disponibles :\n";
    foreach ($pharmacies as $p) {
        echo "  [{$p['id_pharmacie']}] {$p['nom_pharmacie']} ({$p['ville']})\n";
    }

    $id_pharmacie_saisi = demander("\nID de la pharmacie : ");

    foreach ($pharmacies as $p) {
        if ((string) $p['id_pharmacie'] === trim($id_pharmacie_saisi)) {
            $pharmacie_valide = $p;
            break;
        }
    }

    if ($pharmacie_valide === null) {
        die("ID de pharmacie invalide.\n");
    }
}


// ----- Informations du compte -----

$nom    = demander("\nNom : ");
$prenom = demander("Prénom : ");

$email = demander("Email (sert d'identifiant de connexion) : ");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email invalide.\n");
}


// ----- Vérifier que l'email n'est pas déjà utilisé -----

$stmt_verif = $pdo->prepare("SELECT id_administrateur FROM administrateurs WHERE email = ?");
$stmt_verif->execute([$email]);

if ($stmt_verif->fetch()) {
    die("Un compte administrateur existe déjà avec cet email.\n");
}


// ----- Mot de passe -----
// Saisie visible en clair : c'est un script d'administration système, exécuté
// une seule fois par Wilfried lui-même en local — pas un flux utilisateur.

$mot_de_passe = demander("Mot de passe temporaire (à changer ensuite) : ");

if (strlen($mot_de_passe) < 8) {
    die("Le mot de passe doit contenir au moins 8 caractères.\n");
}


// ----- Récupérer l'id du rôle correspondant -----

$nom_role = ($type_choisi === '1') ? 'pharmacien' : 'administration_plateforme';

$stmt_role = $pdo->prepare("SELECT id_role FROM roles WHERE nom_role = ?");
$stmt_role->execute([$nom_role]);
$role = $stmt_role->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    die("Rôle '$nom_role' introuvable — avez-vous bien appliqué les migrations 006 et 008 ?\n");
}


// ----- Insertion -----

$mdp_hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);

$sql_insert = "INSERT INTO administrateurs (id_pharmacie, id_role, nom, prenom, email, mot_de_passe_hash)
               VALUES (?, ?, ?, ?, ?, ?)";
$pdo->prepare($sql_insert)->execute([
    $pharmacie_valide['id_pharmacie'] ?? null, // NULL pour un compte administration plateforme
    $role['id_role'],
    $nom,
    $prenom,
    $email,
    $mdp_hash
]);

if ($pharmacie_valide !== null) {
    echo "\n✓ Compte pharmacien créé pour {$pharmacie_valide['nom_pharmacie']}.\n";
} else {
    echo "\n✓ Compte administration plateforme créé.\n";
}
echo "  Identifiant de connexion : {$email}\n";
