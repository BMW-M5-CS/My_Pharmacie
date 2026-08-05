<?php
    session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $redirect = $_GET['redirect'] ?? '';

    $messages_erreur = [
        'identifiants_incorrects' => 'Identifiant ou mot de passe incorrect.',
        'trop_de_tentatives'      => 'Trop de tentatives de connexion. Réessayez dans quelques minutes.',
        'technique'               => 'Une erreur technique est survenue, réessayez.',
    ];
    $erreur = $messages_erreur[$_GET['erreur'] ?? ''] ?? null;

    $messages_succes = [
        'inscrit'          => 'Compte créé avec succès. Vous pouvez vous connecter.',
        'mdp_reinitialise' => 'Mot de passe réinitialisé avec succès. Connectez-vous avec votre nouveau mot de passe.',
        'session_expiree'  => 'Votre session a expiré, veuillez vous reconnecter.',
    ];
    $succes = null;
    foreach ($messages_succes as $cle => $texte) {
        if (isset($_GET[$cle])) {
            $succes = $texte;
            break;
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — My Pharmacie</title>
    <link rel="stylesheet" href="../Dos-css/conex.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="boite-centrale">

        <div class="entete-conex">
            <i class="fa-solid fa-user-lock"></i>
            <h1>Connexion</h1>
            <p>Accédez à votre espace My Pharmacie</p>
        </div>

        <?php if ($erreur): ?>
            <p class="msg-erreur"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <?php if ($succes): ?>
            <p class="msg-succes"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($succes) ?></p>
        <?php endif; ?>

        <form action="../Dos-php/traite_conex.php" method="POST">

            <div class="groupe-champ">
                <label for="phone_email">Email ou téléphone</label>
                <div class="champ-icone">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="text" id="phone_email" name="phone_email" placeholder="Votre email ou numéro" required>
                </div>
            </div>

            <div class="groupe-champ">
                <label for="mot_de_passe">Mot de passe</label>
                <div class="champ-icone">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Votre mot de passe" autocomplete="current-password" required>
                </div>
            </div>

            <div class="ligne-options">
                <label class="case-souvenir">
                    <input type="checkbox" name="remenber">
                    <span>Se souvenir de moi</span>
                </label>
                <a href="../Dos-page/mdp_oublie.php" class="lien-oublie">Mot de passe oublié ?</a>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

            <button type="submit" class="bouton-principal">Se connecter</button>
            <button type="button" class="bouton-google"><i class="fa-brands fa-google"></i> Continuer avec Google</button>

            <p class="lien-connexion">Pas encore de compte ? <a href="../Dos-page/inscription.php">S'inscrire</a></p>
        </form>

        <a href="../Dos-page/acceuil.php" class="retour"><i class="fa-solid fa-arrow-left"></i> Revenir à l'accueil</a>
    </div>

</body>
</html>