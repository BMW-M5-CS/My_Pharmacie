<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$nom_auto               = $_SESSION['nom'] ?? '';
$phone_email_auto       = $_SESSION['phone_email'] ?? '';

$messages_erreur = [
    'champs_obligatoires' => 'Tous les champs sont obligatoires.',
    'technique'           => 'Une erreur technique est survenue, réessayez.',
];
$erreur = $messages_erreur[$_GET['erreur'] ?? ''] ?? null;
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

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>


    <div class="contact-entete">

        <div class="entete-fond"></div>

        <div class="entete-contenu">

            <div class="title">
                <i class="fa-solid fa-envelope"></i>
                <h1>Contactez-nous</h1>
            </div>

            <p class="text">Une question, une suggestion ? Écris-nous, on te répond rapidement.</p>

        </div>

    </div>


    <section class="contact-section">

        <div class="contact-container">

            <div class="entrer-section">

                <?php if (isset($_GET['succes'])) : ?>

                    <div class="message-succes">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Votre message a bien été envoyé ! Nous te répondrons rapidement.</span>
                    </div>

                <?php endif; ?>

                <?php if ($erreur) : ?>

                    <div class="msg-erreur">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?php echo htmlspecialchars($erreur); ?></span>
                    </div>

                <?php endif; ?>

                <form method="POST" action="../Dos-php/traite_contact.php">

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" placeholder="Entrez votre nom complet" value="<?php echo htmlspecialchars($nom_auto); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email ou numéro de téléphone</label>
                        <input type="text" id="email" name="phone_email" placeholder="Entrez votre email ou téléphone" value="<?php echo htmlspecialchars($phone_email_auto); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <input type="text" id="sujet" name="sujet" placeholder="Entrez le sujet de votre message" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Entrez votre message..." required></textarea>
                    </div>

                    <button type="submit" class="btn-envoyer"><i class="fa-solid fa-paper-plane"></i> Envoyer le message</button>

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

                <div class="map-container">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31786.64!2d1.2213!3d6.1375!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1023e1c113185419%3A0x3223145054854e4c!2sLom%C3%A9%2C%20Togo!5e0!3m2!1sfr!2sfr"
                        allowfullscreen=""
                        loading="lazy">
                    </iframe>
                </div>

                <a href="carte.php" class="btn-voir-carte">
                    <i class="fa-solid fa-map-location-dot"></i> Voir la carte des pharmacies
                </a>

            </div>

        </div>

    </section>


    <?php include '../../Include_general/footer.php'; ?>

</body>
</html>