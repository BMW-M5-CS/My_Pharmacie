<?php

require_once '../../Include_general/session_init.php';
require_once '../../int_Public/Dos-php/config.php';
require_once '../Dos-php/verifier_session_admin.php';

if ($_SESSION['admin_role_nom'] !== 'administration_plateforme') {
    header("Location: demandes_reservation.php");
    exit();
}

$erreur = $_GET['erreur'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une pharmacie — MaPharmacie</title>
    <link rel="stylesheet" href="../../Include_general/variables.css">
    <link rel="stylesheet" href="../Dos-css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="admin-boite-centrale admin-boite-large">

        <div class="admin-entete">
            <i class="fa-solid fa-plus"></i>
            <h1>Créer une pharmacie</h1>
        </div>

        <?php if ($erreur === 'champs_manquants') : ?>
            <p class="admin-message-erreur">Merci de remplir tous les champs obligatoires.</p>
        <?php endif; ?>

        <form action="../Dos-php/traite_creation_pharmacie.php" method="POST">

            <div class="admin-groupe-champ">
                <label for="nom_pharmacie">Nom de la pharmacie *</label>
                <input type="text" id="nom_pharmacie" name="nom_pharmacie" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="adresse">Adresse *</label>
                <input type="text" id="adresse" name="adresse" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="ville">Ville *</label>
                <input type="text" id="ville" name="ville" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="quartier">Quartier</label>
                <input type="text" id="quartier" name="quartier">
            </div>

            <div class="admin-groupe-champ">
                <label for="telephone_pharmacie">Téléphone *</label>
                <input type="tel" id="telephone_pharmacie" name="telephone_pharmacie" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="latitude">Latitude *</label>
                <input type="number" step="any" id="latitude" name="latitude" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="longitude">Longitude *</label>
                <input type="number" step="any" id="longitude" name="longitude" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="heure_ouverture">Heure d'ouverture *</label>
                <input type="time" id="heure_ouverture" name="heure_ouverture" required>
            </div>

            <div class="admin-groupe-champ">
                <label for="heure_fermeture">Heure de fermeture *</label>
                <input type="time" id="heure_fermeture" name="heure_fermeture" required>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit" class="admin-bouton-principal">Créer la pharmacie</button>

            <a href="liste_pharmacies.php" class="admin-lien-retour">Annuler</a>

        </form>

    </div>

</body>
</html>
