<?php
   session_start();
   if (empty($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   }

   $messages_erreur = [
        'champs_obligatoires' => 'Tous les champs sont obligatoires.',
        'mdp_court'           => 'Le mot de passe doit contenir au moins 8 caractères.',
        'mdp_differents'      => 'Les deux mots de passe ne sont pas identiques.',
        'contact_invalide'    => 'Le contact de récupération doit être un email ou un numéro valide.',
        'deja_utilise'        => 'Cet email ou téléphone est déjà utilisé.',
        'technique'           => 'Une erreur technique est survenue, réessayez.',
    ];
    $erreur = $messages_erreur[$_GET['erreur'] ?? ''] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — My Pharmacie</title>
    <link rel="stylesheet" href="../Dos-css/inscription.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="boite-centrale">

        <div class="entete-inscription">
            <i class="fa-solid fa-user-plus"></i>
            <h1>Créer un compte</h1>
            <p>Rejoignez My Pharmacie en quelques instants</p>
        </div>

        <?php if ($erreur): ?>
            <p class="msg-erreur"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form action="../Dos-php/traite_inscrit.php" method="POST">

            <div class="ligne-double">
                <div class="groupe-champ">
                    <label for="nom">Nom</label>
                    <div class="champ-icone">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                    </div>
                </div>

                <div class="groupe-champ">
                    <label for="prenom">Prénom</label>
                    <div class="champ-icone">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                    </div>
                </div>
            </div>

            <div class="groupe-champ">
                <label for="phone_email">Email ou téléphone</label>
                <div class="champ-icone">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="text" id="phone_email" name="phone_email" placeholder="Votre email ou numéro" required>
                </div>
            </div>

            <div class="groupe-champ">
                <label for="contact_recuperation">Contact de récupération <span class="optionnel">(optionnel)</span></label>
                <div class="champ-icone">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="text" id="contact_recuperation" name="contact_recuperation" placeholder="Email ou numéro de secours">
                </div>
            </div>

            <div class="groupe-champ">
                <label for="mot_de_passe">Mot de passe</label>
                <div class="champ-icone">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Créez un mot de passe" autocomplete="new-password" minlength="8" required>
                </div>
            </div>

            <div class="groupe-champ">
                <label for="confirm">Confirmation</label>
                <div class="champ-icone">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="confirm" name="confirm" placeholder="Confirmez le mot de passe" autocomplete="new-password" minlength="8" required>
                </div>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <button type="submit" class="bouton-principal">Créer mon compte</button>
            <!-- <button type="button" class="bouton-google"><i class="fa-brands fa-google"></i> Continuer avec Google</button> -->

            <p class="lien-connexion">Vous avez déjà un compte ? <a href="../Dos-page/conex.php">Se connecter</a></p>
        </form>

        <a href="../Dos-page/acceuil.php" class="retour"><i class="fa-solid fa-arrow-left"></i> Revenir à l'accueil</a>
    </div>

</body>
</html>