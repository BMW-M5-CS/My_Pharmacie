<?php
    session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="../Dos-css/conex.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <form action="../Dos-php/traite_conex.php" method ="POST">
        <section class="contener-conex">
                <div class="titre">
                    <h1>Connexion</h1>
                </div>

                <div class="input_conex">
                    
                    <div class="email">
                        <i class="fa-solid fa-envelope"></i> <span>Email ou Téléphone :</span>
                        <input type="text" name="phone_email" placeholder="Adresse mail ou Numero de Telephone" class="mail-conex">
                    </div>
                    
                    <div class="password">
                        <i class="fa-solid fa-lock"></i> Mot de passe :
                        <input type="password" name="mot_de_passe" placeholder="Entrer votre mot de passe" class="mot_de_passe">
                    </div>

                    <label>
                        <input type="checkbox" name="remenber" class="coche_input"> <span class="text">Se souvenir de moi</span> 
                    </label>

                </div>
                
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">      

                <button class="btn-conex" type="submit">Se Connecter</button>
                <a href="../Dos-page/inscription.php" class="btn">Créé un compte</a>

                <a class="retour" href="../Dos-page/acceuil.php">Revenir à la page d'acceuil</a>

        </section>
    </form>
</body>
</html>