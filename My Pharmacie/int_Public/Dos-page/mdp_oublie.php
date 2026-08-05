<?php

    session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $envoye = isset($_GET['envoye']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — My Pharmacie</title>
    <link rel="stylesheet" href="../Dos-css/mdp_oublie.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="boite-centrale">

        <?php if ($envoye) : ?>

            <div class="entete-mdp-oublie">
                <i class="fa-solid fa-paper-plane"></i>
                <h1>Demande envoyée</h1>
            </div>

            <p class="message-info">
                Si un compte existe avec cet identifiant et dispose d'un moyen de récupération,
                un lien de réinitialisation vient de lui être envoyé.
            </p>

            <a href="../Dos-page/conex.php" class="bouton-principal bouton-lien">Retour à la connexion</a>

        <?php else : ?>

            <div class="entete-mdp-oublie">
                <i class="fa-solid fa-key"></i>
                <h1>Mot de passe oublié</h1>
                <p>Entrez votre email ou numéro de téléphone, nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
            </div>

            <form action="../Dos-php/traite_mdp_oublie.php" method="POST">

                <div class="groupe-champ">
                    <label for="phone_email">Email ou téléphone</label>
                    <div class="champ-icone">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="text" id="phone_email" name="phone_email" placeholder="Votre email ou numéro" required>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button type="submit" class="bouton-principal">Envoyer le lien</button>

                <a href="../Dos-page/conex.php" class="retour"><i class="fa-solid fa-arrow-left"></i> Revenir à la connexion</a>

            </form>

        <?php endif; ?>

    </div>

</body>
</html>