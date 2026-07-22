<?php

require_once __DIR__ .'/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function envoyerEmail($destinataire, $sujet, $corpsHtml) {
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'edranwilfried2005@gmail.com';      // adresse mail utilisée pour le test
        $mail->Password = 'gatl abjs evzw psrz';              // Remplacez par votre mot de passe d'application Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Utilisation de TLS
        $mail->Port = 587; 
        $mail->CharSet = 'UTF-8';                             // Définir l'encodage des caractères

        // Configuration de l'expéditeur et du destinataire
        $mail->setFrom('edranwilfried2005@gmail.com','My Pharmacie'); 
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