<?php

require_once '../../Include_general/session_init.php';
require_once '../../int_Public/Dos-php/config.php';
require_once '../Dos-php/verifier_session_admin.php';
require_once '../../int_Public/Dos-php/canaux_recuperation.php';

// ===== Détermination de la pharmacie à gérer =====
// Un pharmacien normal : toujours SA pharmacie, jamais un autre choix
// possible, même en modifiant l'URL à la main (id_pharmacie vient
// uniquement de sa session, jamais de ce que l'utilisateur pourrait envoyer).
//
// L'administration plateforme (pas rattachée à une pharmacie précise, cf.
// migration 008) : PEUT choisir la pharmacie via ?pharmacie=ID dans l'URL —
// mais on vérifie quand même que cette pharmacie existe réellement avant de
// l'utiliser, jamais confiance aveugle à une valeur venue de l'extérieur.

if ($_SESSION['admin_role_nom'] === 'administration_plateforme') {

    $id_pharmacie_demande = filter_input(INPUT_GET, 'pharmacie', FILTER_VALIDATE_INT);

    if (!$id_pharmacie_demande) {
        header("Location: liste_pharmacies.php");
        exit();
    }

    $stmt_verif_pharmacie = $pdo->prepare("SELECT id_pharmacie, nom_pharmacie FROM pharmacies WHERE id_pharmacie = ?");
    $stmt_verif_pharmacie->execute([$id_pharmacie_demande]);
    $pharmacie_ciblee = $stmt_verif_pharmacie->fetch(PDO::FETCH_ASSOC);

    if (!$pharmacie_ciblee) {
        header("Location: liste_pharmacies.php?erreur=pharmacie_introuvable");
        exit();
    }

    $id_pharmacie   = $pharmacie_ciblee['id_pharmacie'];
    $nom_pharmacie_geree = $pharmacie_ciblee['nom_pharmacie'];

} else {
    // Cas normal : un pharmacien ne gère toujours que sa propre pharmacie.
    $id_pharmacie        = $_SESSION['admin_pharmacie_id'];
    $nom_pharmacie_geree = null;
}


// ===== Demandes en attente de décision pour CETTE pharmacie uniquement =====
// Scoping systématique par pharmacie_id : jamais les demandes d'une autre
// pharmacie, quoi qu'il arrive (cf. charte de rigueur, risque IDOR).

$sql = "SELECT
            r.groupe_demande,
            r.quantite_reservee,
            r.date_reservation,
            r.expire_demande_at,
            u.nom,
            u.prenom,
            u.phone_email,
            u.email_recuperation,
            u.telephone_recuperation,
            p.nom_medicament,
            p.forme_pharmaceutique,
            s.quantite_disponible
        FROM reservations r
        JOIN stocks s     ON s.id_stock     = r.id_stock
        JOIN produits p   ON p.id_produit   = s.id_produit
        JOIN users u      ON u.id_user      = r.id_user
        WHERE s.id_pharmacie = ?
          AND r.statut = 'demandee'
        ORDER BY r.date_reservation ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_pharmacie]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ===== Regroupement par demande (plusieurs produits peuvent partager le même groupe_demande) =====

$demandes = [];
foreach ($lignes as $ligne) {
    $groupe = $ligne['groupe_demande'];
    if (!isset($demandes[$groupe])) {
        $demandes[$groupe] = [
            'groupe_demande'    => $groupe,
            'date_reservation'  => $ligne['date_reservation'],
            'expire_demande_at' => $ligne['expire_demande_at'],
            // Principe "need to know" (spec §2.3) : jamais l'email du client
            // affiché ici, seulement nom + téléphone.
            'client_nom'        => $ligne['prenom'] . ' ' . $ligne['nom'],
            'client_telephone'  => resoudreTelephoneContact($ligne) ?? 'Non renseigné',
            'produits'          => []
        ];
    }
    $demandes[$groupe]['produits'][] = [
        'nom'                  => $ligne['nom_medicament'],
        'forme'                => $ligne['forme_pharmaceutique'],
        'quantite_demandee'    => $ligne['quantite_reservee'],
        'quantite_disponible'  => $ligne['quantite_disponible'],
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes de réservation — MaPharmacie</title>
    <link rel="stylesheet" href="../../Include_general/variables.css">
    <link rel="stylesheet" href="../Dos-css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <input type="hidden" id="csrf-token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <div class="admin-page">

        <div class="admin-topbar">
            <h1><i class="fa-solid fa-bell"></i> Demandes de réservation</h1>
            <?php if ($nom_pharmacie_geree !== null) : ?>
                <span class="admin-pharmacie-geree">
                    <a href="liste_pharmacies.php" title="Retour à la liste des pharmacies"><i class="fa-solid fa-arrow-left"></i></a>
                    <?php echo htmlspecialchars($nom_pharmacie_geree); ?>
                </span>
            <?php endif; ?>
            <span class="admin-compteur-total"><?php echo count($demandes); ?> en attente</span>
            <a href="../Dos-php/deconnexion_admin.php" class="admin-lien-deconnexion" title="Se déconnecter">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>

        <?php if (empty($demandes)) : ?>

            <p class="admin-vide">Aucune demande en attente pour le moment.</p>

        <?php else : ?>

            <div class="admin-liste-demandes">

                <?php foreach ($demandes as $demande) : ?>

                    <div class="admin-carte-demande" data-groupe="<?php echo htmlspecialchars($demande['groupe_demande']); ?>">

                        <div class="admin-carte-entete">
                            <span class="admin-client-nom">
                                <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($demande['client_nom']); ?>
                            </span>
                            <span class="admin-client-tel">
                                <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($demande['client_telephone']); ?>
                            </span>
                            <span class="admin-delai">
                                À traiter avant le <?php echo date('d/m/Y à H:i', strtotime($demande['expire_demande_at'])); ?>
                            </span>
                        </div>

                        <ul class="admin-liste-produits">
                            <?php foreach ($demande['produits'] as $produit) : ?>
                                <li>
                                    <?php echo htmlspecialchars($produit['nom']) . ' ' . htmlspecialchars($produit['forme']); ?>
                                    — demandé : <?php echo (int) $produit['quantite_demandee']; ?>
                                    <span class="admin-stock-info">(stock déclaré : <?php echo (int) $produit['quantite_disponible']; ?>)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="admin-carte-actions">
                            <button class="admin-btn-accepter" data-groupe="<?php echo htmlspecialchars($demande['groupe_demande']); ?>">
                                <i class="fa-solid fa-check"></i> Accepter
                            </button>
                            <button class="admin-btn-rejeter" data-groupe="<?php echo htmlspecialchars($demande['groupe_demande']); ?>">
                                <i class="fa-solid fa-xmark"></i> Rejeter
                            </button>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

    <!-- ===== Modale de motif de rejet ===== -->
    <div class="admin-overlay" id="admin-overlay-rejet">
        <div class="admin-modale">
            <h3>Motif du rejet (optionnel)</h3>
            <textarea id="admin-motif-rejet" placeholder="Ex : rupture de stock, produit non disponible..."></textarea>
            <div class="admin-modale-btns">
                <button id="admin-rejet-annuler" class="admin-btn-secondaire">Annuler</button>
                <button id="admin-rejet-confirmer" class="admin-btn-danger">Confirmer le rejet</button>
            </div>
        </div>
    </div>

    <script src="../Dos-js/demandes_reservation.js"></script>

</body>
</html>
