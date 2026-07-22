<?php

    session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $envoye = isset($_GET['envoye']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="../Dos-css/mdp_oublie.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <?php if ($envoye) : ?>

        <section class="contener-conex">
            <div class="titre">
                <h1><i class="fa-solid fa-paper-plane"></i> Demande envoyée</h1>
            </div>

            <p class="message-info">
                Si un compte existe avec cet identifiant et dispose d'un moyen de récupération,
                un lien de réinitialisation vient de lui être envoyé.
            </p>

            <a href="../Dos-page/conex.php" class="btn">Retour à la connexion</a>
        </section>

    <?php else : ?>

        <form action="../Dos-php/traite_mdp_oublie.php" method="POST">

            <section class="contener-conex">

                <div class="titre">
                    <h1>Mot de passe oublié</h1>
                </div>

                <p class="sous-titre">
                    Entrez votre email ou numéro de téléphone, nous vous enverrons
                    un lien pour réinitialiser votre mot de passe.
                </p>

                <div class="input_conex">
                    <div class="email">
                        <i class="fa-solid fa-envelope"></i> <span>Email ou Téléphone :</span>
                        <input type="text" name="phone_email" placeholder="Adresse mail ou Numéro de téléphone" class="mail-conex" required>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button class="btn-conex" type="submit">Envoyer le lien</button>

                <a class="retour" href="../Dos-page/conex.php">Revenir à la connexion</a>

            </section>
        </form>

    <?php endif; ?>

</body>
</html>