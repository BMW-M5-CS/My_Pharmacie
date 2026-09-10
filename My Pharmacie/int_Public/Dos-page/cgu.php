<?php
require_once '../../Include_general/session_init.php';
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions générales d'utilisation — MaPharmacie</title>
    <meta name="description" content="Conditions générales d'utilisation de MaPharmacie : accès au service, réservations, responsabilités.">
    <link rel="stylesheet" href="../../Include_general/variables.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/legal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body style="background-color: var(--fond-page);">

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>

    <main class="legal-conteneur">

        <h1>Conditions générales d'utilisation</h1>
        <p class="legal-maj">Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>

        <h2>1. Objet</h2>
        <p>
            Les présentes conditions générales d'utilisation (CGU) régissent l'accès et l'utilisation du site et
            des services MaPharmacie, qui permettent de rechercher des médicaments disponibles en pharmacie,
            de localiser des pharmacies et de réserver un produit en vue d'un retrait sur place.
        </p>

        <h2>2. Accès au service</h2>
        <p>
            La consultation des pharmacies, des produits et de la carte est accessible librement, sans compte.
            La création d'un compte client est nécessaire pour effectuer une réservation, consulter son historique
            ou gérer son profil.
        </p>

        <h2>3. Inscription et compte utilisateur</h2>
        <ul>
            <li>L'utilisateur s'engage à fournir des informations exactes lors de son inscription.</li>
            <li>L'utilisateur est responsable de la confidentialité de ses identifiants de connexion.</li>
            <li>MaPharmacie se réserve le droit de suspendre un compte en cas d'usage frauduleux ou abusif du service.</li>
        </ul>

        <h2>4. Réservations</h2>
        <ul>
            <li>Une réservation effectuée via MaPharmacie constitue une mise de côté du produit par la pharmacie, non un achat en ligne : le paiement et le retrait s'effectuent sur place, en pharmacie.</li>
            <li>Les informations de disponibilité et de prix affichées proviennent des pharmacies partenaires et peuvent, malgré nos efforts de mise à jour, différer ponctuellement de la réalité du stock en officine au moment du retrait.</li>
            <li>MaPharmacie ne garantit pas la disponibilité effective du produit au moment du retrait et ne peut être tenu responsable d'une rupture de stock survenue entre la réservation et le passage en pharmacie.</li>
        </ul>

        <h2>5. Obligations de l'utilisateur</h2>
        <p>
            L'utilisateur s'engage à ne pas détourner le service à des fins frauduleuses, à ne pas multiplier les
            réservations sans intention de retrait, et à respecter les présentes conditions ainsi que la
            réglementation togolaise en vigueur.
        </p>

        <h2>6. Responsabilité</h2>
        <p>
            MaPharmacie agit en tant qu'intermédiaire d'information entre les pharmacies partenaires et les
            utilisateurs. MaPharmacie ne dispense aucun conseil médical ou pharmaceutique et ne saurait se
            substituer à l'avis d'un pharmacien ou d'un médecin. En cas d'urgence médicale, contactez immédiatement
            les services d'urgence (SAMU : 15).
        </p>

        <h2>7. Données personnelles</h2>
        <p>
            L'utilisation du service implique la collecte de certaines données personnelles, décrite en détail
            dans notre <a href="confidentialite.php">politique de confidentialité</a>.
        </p>

        <h2>8. Modification des CGU</h2>
        <p>
            MaPharmacie se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront
            informés de toute modification substantielle lors de leur prochaine connexion.
        </p>

        <h2>9. Droit applicable et juridiction</h2>
        <p>
            Les présentes CGU sont soumises au droit togolais. Tout litige relatif à leur interprétation ou leur
            exécution relève de la compétence exclusive des juridictions togolaises.
        </p>

        <div class="legal-note-redaction">
            <strong>Note de rédaction :</strong> ce document est un modèle de structure standard couvrant les
            points essentiels d'un service de ce type. Il doit être relu et validé par un professionnel du droit
            togolais avant tout dépôt officiel ou mise en ligne publique définitive, en particulier les clauses de
            responsabilité liées à l'exactitude des stocks. Je ne suis pas juriste — ce contenu n'a pas valeur
            d'avis juridique.
        </div>

    </main>

    <?php include '../../Include_general/footer.php'; ?>

</body>
</html>
