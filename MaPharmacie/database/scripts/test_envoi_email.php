<?php

// ============================================================================
// Outil de diagnostic — teste l'envoi d'email et affiche l'erreur exacte
// ----------------------------------------------------------------------------
// Usage : php test_envoi_email.php
// Puis tape l'adresse email où tu veux recevoir le test.
//
// Contrairement au vrai site (qui cache volontairement les erreurs aux
// utilisateurs, pour la sécurité), cet outil affiche tout, en clair, pour
// nous permettre de trouver la vraie cause.
// ============================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("Ce script ne peut être exécuté qu'en ligne de commande.\n");
}

require_once __DIR__ . '/../../int_Public/Dos-php/mailer_config.php'; // charge aussi mailer_secrets.php (définit $smtp_user / $smtp_pass)

echo "=== Test d'envoi d'email ===\n\n";
echo "Adresse email où envoyer le test : ";
$destinataire = trim(fgets(STDIN));

if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
    die("Adresse email invalide.\n");
}

echo "\nEnvoi en cours...\n\n";

// On force PHPMailer à nous dire ce qu'il fait, étape par étape, au lieu
// de rester silencieux comme sur le vrai site.
try {

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    $mail->SMTPDebug   = 2; // affiche toute la conversation avec le serveur mail
    $mail->Debugoutput = function ($str, $level) {
        echo "[SMTP] $str\n";
    };

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user; // vient de mailer_secrets.php, chargé plus haut
    $mail->Password   = $smtp_pass; // vient de mailer_secrets.php, chargé plus haut
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($smtp_user, 'MaPharmacie (test)');
    $mail->addAddress($destinataire);

    $mail->isHTML(true);
    $mail->Subject = 'Test d\'envoi — MaPharmacie';
    $mail->Body    = '<p>Si tu reçois cet email, l\'envoi fonctionne correctement.</p>';

    $mail->send();

    echo "\n✓ SUCCÈS — l'email a bien été envoyé à $destinataire. Vérifie ta boîte (et le dossier Spam).\n";

} catch (Exception $e) {

    echo "\n✗ ÉCHEC — voici l'erreur exacte :\n";
    echo $mail->ErrorInfo . "\n";
}
