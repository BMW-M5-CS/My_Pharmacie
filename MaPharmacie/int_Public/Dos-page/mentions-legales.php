<?php
require_once '../../Include_general/session_init.php';
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions légales — MaPharmacie</title>
    <meta name="description" content="Mentions légales de MaPharmacie : éditeur, hébergeur et propriété intellectuelle.">
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

        <h1>Mentions légales</h1>
        <p class="legal-maj">Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>

        <h2>1. Éditeur du site</h2>
        <p>
            Le site MaPharmacie est édité par : <strong>[Raison sociale / nom de l'éditeur à compléter]</strong>,
            [forme juridique à préciser — entreprise individuelle, société, association...], immatriculé(e) sous le
            numéro [numéro d'immatriculation à compléter, ex. RCCM], dont le siège est situé à
            [adresse complète à compléter], Lomé, République Togolaise.
        </p>
        <p>
            Directeur de la publication : [Nom à compléter].<br>
            Contact : <span id="legal-contact-email">[email de contact à compléter]</span> —
            <span id="legal-contact-tel">[téléphone à compléter]</span>.
        </p>

        <h2>2. Hébergement</h2>
        <p>
            Le site est hébergé par : <strong>[Nom de l'hébergeur à compléter]</strong>,
            [adresse de l'hébergeur à compléter].
        </p>

        <h2>3. Propriété intellectuelle</h2>
        <p>
            L'ensemble des éléments du site MaPharmacie (textes, mise en page, graphismes, logo, base de données)
            est protégé par le droit de la propriété intellectuelle. Toute reproduction, représentation ou
            exploitation, totale ou partielle, sans autorisation préalable écrite de l'éditeur, est interdite.
        </p>

        <h2>4. Nature des informations diffusées</h2>
        <p>
            MaPharmacie référence des informations transmises par les pharmacies partenaires (disponibilité des
            produits, horaires d'ouverture, statut de garde). Ces informations sont fournies à titre indicatif et
            peuvent évoluer entre deux mises à jour. MaPharmacie ne se substitue en aucun cas à un avis médical
            ou pharmaceutique professionnel.
        </p>

        <h2>5. Protection des données personnelles</h2>
        <p>
            Le traitement des données personnelles collectées sur ce site est décrit dans notre
            <a href="confidentialite.php">politique de confidentialité</a>, conformément à la loi togolaise
            n°2019-014 du 29 octobre 2019 relative à la protection des données à caractère personnel.
        </p>

        <h2>6. Droit applicable</h2>
        <p>
            Les présentes mentions légales sont soumises au droit togolais. Tout litige relève de la compétence
            exclusive des juridictions togolaises.
        </p>

        <div class="legal-note-redaction">
            <strong>Note de rédaction :</strong> ce document est un modèle de structure juridique standard.
            Les champs entre crochets doivent être complétés avec les informations réelles de l'entité qui
            exploitera MaPharmacie, et l'ensemble devrait être relu par un professionnel du droit togolais
            avant tout dépôt officiel ou mise en ligne publique définitive. Je ne suis pas juriste — ce contenu
            n'a pas valeur d'avis juridique.
        </div>

    </main>

    <?php include '../../Include_general/footer.php'; ?>

</body>
</html>
