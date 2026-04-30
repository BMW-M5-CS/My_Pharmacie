<?php
   session_start();
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="../Dos-css/inscription.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <section class="inscription">
        <div class="inscrit">
            <h1>Inscription</h1>
        </div>

        <form action="../Dos-php/traite_inscrit.php" method="POST" class="input-inscrit">

            <label>
                <span>Nom :</span>
                <input type="text" name="nom" placeholder="Entrer votre nom" class="input-name" required>
                <i class="fa fa-user"></i>
            </label>

            <label>
                <span>Prenom :</span>
                <input type="text" name="prenom" placeholder="Entrer votre Prenoms" class="input-first_name" required>
                <i class="fa fa-user"></i>
            </label>
            
            <label>
                <span>Email ou telephone :</span>
                <input type="text" name="phone_email" placeholder="Entrer votre adresse email" class="input-mail" required>
                <i class="fa fa-envelope"></i>
            </label>

            <label>
                <span>Mots de passe :</span>
                <input type="password" name="mot_de_passe" placeholder="Créé un mot de passe " class="input-password" required>
                <i class="fa fa-lock"></i>
            </label>

            <label>
                <span>confirmation :</span>
                <input type="password" name="confirm" placeholder="Confirmez votre le mot de passe créé " class="input-confirm_password" required>
                <i class="fa fa-lock"></i>
            </label>

            <label class="souvenir_and_case">
                    <a href="../Dos-page/conex.php" class="revenir">J'ai déja un compte</a>
            </label>

                     <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
           
            <label for="" class="inscrit-btn">
                <button class="btn-inscrit" type="submit"> S'inscrire </button>
                <button class="btn-inscrit" type="button"> Continuer avec Google</button>
            </label>
            
        </form>
           <a href="../Dos-page/acceuil.php" class="retour">Revenir a la page d'acceuil</a>
    </section>


</body>
</html>