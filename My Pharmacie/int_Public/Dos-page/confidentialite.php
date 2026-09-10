<?php
require_once '../../Include_general/session_init.php';
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de confidentialité — MaPharmacie</title>
    <meta name="description" content="Politique de confidentialité de MaPharmacie : données collectées, finalités, conservation et droits des utilisateurs.">
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

        <h1>Politique de confidentialité</h1>
        <p class="legal-maj">Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>

        <p>
            MaPharmacie attache une importance particulière à la protection de vos données personnelles.
            Cette politique explique quelles données nous collectons, pourquoi, combien de temps nous les
            conservons, et quels sont vos droits. Elle est établie conformément à la loi togolaise n°2019-014
            du 29 octobre 2019 relative à la protection des données à caractère personnel, sous le contrôle de
            l'Instance de Protection des Données à Caractère Personnel (IPDCP — <a href="https://ipdcp.tg" target="_blank" rel="noopener">ipdcp.tg</a>).
        </p>

        <h2>1. Données que nous collectons</h2>
        <p>Selon votre usage du service, nous collectons :</p>
        <ul>
            <li><strong>Données de compte :</strong> nom, numéro de téléphone et/ou adresse e-mail, mot de passe (stocké de façon chiffrée).</li>
            <li><strong>Données de profil :</strong> informations d'assurance santé que vous renseignez volontairement, pour affiner la recherche de pharmacies compatibles.</li>
            <li><strong>Données de localisation :</strong> votre position, si vous l'autorisez dans votre navigateur, pour calculer la distance jusqu'aux pharmacies et trier les résultats de recherche.</li>
            <li><strong>Données d'utilisation :</strong> historique de vos recherches de produits, réservations effectuées, annulées ou renouvelées.</li>
            <li><strong>Données techniques :</strong> adresse IP et informations de session, à des fins de sécurité du compte.</li>
        </ul>

        <h2>2. Pourquoi nous les utilisons</h2>
        <ul>
            <li>Vous permettre de rechercher un médicament et de localiser une pharmacie qui le propose ;</li>
            <li>Trier les résultats par proximité et par compatibilité avec votre assurance ;</li>
            <li>Créer, suivre et gérer vos réservations auprès des pharmacies partenaires ;</li>
            <li>Sécuriser votre compte (protection contre les accès non autorisés) ;</li>
            <li>Améliorer la pertinence du service (statistiques d'usage agrégées, non individualisées).</li>
        </ul>
        <p>Nous ne vendons jamais vos données personnelles à des tiers.</p>

        <h2>3. Qui a accès à vos données</h2>
        <ul>
            <li>La pharmacie concernée, uniquement pour les informations nécessaires au traitement d'une réservation que vous avez initiée.</li>
            <li>L'équipe d'administration de la plateforme MaPharmacie, dans le cadre strict de la maintenance, de la modération et de la sécurité du service.</li>
            <li>Aucun tiers commercial, publicitaire ou partenaire externe n'a accès à vos données personnelles.</li>
        </ul>

        <h2>4. Durée de conservation</h2>
        <p>
            Vos données de compte sont conservées tant que votre compte est actif. L'historique de réservations
            est conservé pendant une durée de [durée à définir, ex. 3 ans] à des fins de suivi de service, sauf
            demande de suppression de votre part. Vous pouvez vider votre historique à tout moment depuis votre
            profil.
        </p>

        <h2>5. Vos droits</h2>
        <p>Conformément à la loi n°2019-014, vous disposez d'un droit d'accès, de rectification, d'opposition et
            de suppression de vos données personnelles. Vous pouvez exercer ces droits :</p>
        <ul>
            <li>directement depuis votre espace « Profil » pour la rectification et la suppression de compte ;</li>
            <li>en nous contactant à <span id="dpo-email">[adresse e-mail du responsable de traitement à compléter]</span>.</li>
        </ul>
        <p>
            Vous disposez également du droit de saisir l'Instance de Protection des Données à Caractère
            Personnel (IPDCP) si vous estimez que vos droits ne sont pas respectés.
        </p>

        <h2>6. Sécurité</h2>
        <p>
            Les mots de passe sont stockés de façon chiffrée et ne sont jamais accessibles en clair. Les échanges
            entre votre navigateur et nos serveurs sont protégés. L'accès aux données par notre équipe est limité
            aux personnes dont la mission le requiert.
        </p>

        <h2>7. Cas particulier des données des pharmacies partenaires</h2>
        <p>
            Les informations relatives au personnel, à l'agrément et aux stocks des pharmacies partenaires font
            l'objet d'un traitement distinct, encadré par un accord spécifique conclu directement avec chaque
            pharmacie, et non par la présente politique qui concerne les utilisateurs du service.
        </p>

        <div class="legal-note-redaction">
            <strong>Note de rédaction :</strong> ce document reflète les données réellement traitées par la
            plateforme telles qu'observées dans son fonctionnement actuel. Les champs entre crochets doivent être
            complétés, et la durée de conservation doit être fixée avec précision avant tout dépôt officiel. Une
            relecture par un professionnel du droit togolais reste nécessaire avant mise en ligne publique
            définitive ou dépôt auprès de l'IPDCP. Je ne suis pas juriste — ce contenu n'a pas valeur d'avis
            juridique.
        </div>

    </main>

    <?php include '../../Include_general/footer.php'; ?>

</body>
</html>
