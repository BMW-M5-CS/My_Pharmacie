<?php

require_once '../../Include_general/session_init.php';

// Déjà connecté en tant que pharmacien ? On saute directement à l'espace admin.
if (isset($_SESSION['admin_id'])) {
    header("Location: demandes_reservation.php");
    exit();
}

$erreur = $_GET['erreur'] ?? '';

$messages_erreur = [
    'identifiants_incorrects' => 'Email ou mot de passe incorrect.',
    'trop_de_tentatives'      => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.',
    'technique'               => 'Une erreur est survenue. Veuillez réessayer.',
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace pharmacien — MaPharmacie</title>
    <link rel="stylesheet" href="../../Include_general/variables.css">
    <link rel="stylesheet" href="../Dos-css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="admin-boite-centrale">

        <div class="admin-entete">
            <i class="fa-solid fa-shop"></i>
            <h1>Espace pharmacien</h1>
        </div>

        <?php if ($erreur && isset($messages_erreur[$erreur])) : ?>
            <p class="admin-message-erreur"><?php echo htmlspecialchars($messages_erreur[$erreur]); ?></p>
        <?php endif; ?>

        <form action="../Dos-php/traite_conex_admin.php" method="POST">

            <div class="admin-groupe-champ">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="admin-groupe-champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit" class="admin-bouton-principal">Se connecter</button>

        </form>

    </div>

</body>
</html>
