<?php

require_once __DIR__ .'/../../vendor/autoload.php';

if (getenv('SMTP_HOST') !== false) {
    // Environnement Docker (Mailpit) : ces valeurs ne sont même pas utilisées
    // quand SMTP_AUTH=false (voir plus bas) — mailer_secrets.php reste
    // réservé à WAMP, pour la même raison que db_secrets.php ci-dessus.
    $smtp_user = getenv('SMTP_USER') ?: '';
    $smtp_pass = getenv('SMTP_PASS') ?: '';
} else {
    require_once __DIR__ .'/mailer_secrets.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function envoyerEmail($destinataire, $sujet, $corpsHtml) {
    
    global $smtp_user, $smtp_pass;
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP — Gmail par défaut (WAMP en local),
        // remplaçable via l'environnement (voir .env / docker-compose.yml) :
        // sous Docker, ces variables pointent vers Mailpit (aucune
        // authentification requise, tous les emails sont interceptés
        // localement sans jamais partir sur un vrai réseau).
        $smtp_host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtp_port       = getenv('SMTP_PORT') ?: 587;
        $smtp_auth       = getenv('SMTP_AUTH') !== false ? filter_var(getenv('SMTP_AUTH'), FILTER_VALIDATE_BOOLEAN) : true;
        $smtp_encryption = getenv('SMTP_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
        $smtp_from_email = getenv('SMTP_FROM_EMAIL') ?: 'edranwilfried2005@gmail.com';

        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = $smtp_auth;

        if ($smtp_auth) {
            $mail->Username = $smtp_user;      // adresse mail utilisée pour le test
            $mail->Password = $smtp_pass;              // Remplacez par votre mot de passe d'application Gmail
        }

        if ($smtp_encryption !== '') {
            $mail->SMTPSecure = $smtp_encryption;   // Utilisation de TLS (vide = aucun chiffrement, ex. Mailpit)
        }
        $mail->Port = (int) $smtp_port;
        $mail->CharSet = 'UTF-8';                             // Définir l'encodage des caractères

        // Configuration de l'expéditeur et du destinataire
        $mail->setFrom($smtp_from_email, 'MaPharmacie');
        $mail->addAddress($destinataire);

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body = $corpsHtml;

        $mail->send();
        return true;                                           // Email envoyé avec succès

    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo);
        return false; 
    }  
}

?>