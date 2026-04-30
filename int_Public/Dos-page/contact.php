<?php
session_start();
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   $nom_auto        = $_SESSION['nom'] ?? '';
   $phone_email_auto = $_SESSION['phone_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nous contacter</title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/contact.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>
<body>
    <?php include'../Dos-include/header.php'; ?> 

    <?php if (isset($_GET['succes'])): ?>
       <p style="text-align: center; color: white; margin-top: 20px;">
          ✅ Votre message a bien été envoyé !
       </p>

    <?php endif; ?>

    <div class="contact-container">

    
        <div class="entrer-section">

            <form method="POST" action="../Dos-php/traite_contact.php">

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="name" for="nom">Nom Complet</label>
                    <input type="text" id="nom" name="nom" placeholder="Entrez votre nom complet">
                </div>

                <div class="form-group">
                    <label class="mail" for="email">Email ou numero de téléphone</label>
                    <input type="text" id="email" name="phone_email" placeholder="Entrer votre adresse email">
                </div>

                <div class="form-group">
                    <label class="subject" for="sujet">Sujet</label>
                    <input type="text" id="sujet" name="sujet" placeholder="Entrez le sujet de votre message">
                </div>

                <div class="form-group">
                    <label class="message" for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Entrez votre message..."></textarea>
                </div>

                    <button type="submit" class="btn-envoyer">Envoyer le Message</button>

            </form>
        </div>


        <div class="info-section">

            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span>+228 90 00 00 00</span>
            </div>

            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span>contact@mypharmacy.tg</span>
            </div>

            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>Boulevard du 13 Janvier,<br>Lomé, Togo</span>
            </div>

            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span>
                    Heures d'ouverture :<br>
                    Lun-Ven 8h00–20h00<br>
                    Sam 9h00–18h00
                </span>
            </div>

            <!-- CARTE GOOGLE MAPS centrée sur Lomé -->
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31786.64!2d1.2213!3d6.1375!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1023e1c113185419%3A0x3223145054854e4c!2sLom%C3%A9%2C%20Togo!5e0!3m2!1sfr!2sfr"
                    allowfullscreen=""
                    loading="lazy">
                </iframe>
            </div>
        </div>

        <div class="btn_more">
            <button class="btn-see_more">Voir plus</button>
        </div>

    </div>
    
      <?php include'../Dos-include/footer.php'; ?> 
</body>
</html>
