<?php
require_once '../../int_Public/Dos-php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../int_Public/Dos-page/conex.php');
    exit();
}

$id_user = $_SESSION['user_id'];
$prenom  = $_SESSION['prenom'] ?? '';

// ===== Récupération de toutes les réservations =====
$sql = "SELECT
            r.code_reservation,
            r.statut,
            r.date_reservation,
            r.expire_at,
            r.quantite_reservee,
            ph.nom_pharmacie,
            ph.ville,
            ph.quartier,
            p.nom_medicament,
            p.forme_pharmaceutique
        FROM reservations r
        JOIN stocks s      ON s.id_stock      = r.id_stock
        JOIN produits p    ON p.id_produit    = s.id_produit
        JOIN pharmacies ph ON ph.id_pharmacie = s.id_pharmacie
        WHERE r.id_user = ?
        ORDER BY r.date_reservation DESC, r.code_reservation";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_user]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Regroupement PHP par code_reservation =====
$groupes = [];
foreach ($lignes as $ligne) {
    $code = $ligne['code_reservation'];
    if (!isset($groupes[$code])) {
        $groupes[$code] = [
            'code_reservation' => $code,
            'statut'           => $ligne['statut'],
            'date_reservation' => $ligne['date_reservation'],
            'expire_at'        => $ligne['expire_at'],
            'nom_pharmacie'    => $ligne['nom_pharmacie'],
            'ville'            => $ligne['ville'],
            'quartier'         => $ligne['quartier'],
            'produits'         => []
        ];
    }
    $groupes[$code]['produits'][] = [
        'nom'      => $ligne['nom_medicament'],
        'forme'    => $ligne['forme_pharmaceutique'],
        'quantite' => $ligne['quantite_reservee']
    ];
}

// ===== Compteurs par statut pour les boutons de filtre =====
$compteurs = ['en_attente' => 0, 'confirmee' => 0, 'annulee' => 0, 'expiree' => 0, 'renouvele' => 0];

foreach ($groupes as $g) {
    if (isset($compteurs[$g['statut']])) {
        $compteurs[$g['statut']]++;
    }
}
$total = count($groupes);

// ===== Mapping statut => label + classe CSS =====
$badges = [
    'en_attente' => ['label' => 'En attente', 'classe' => 'badge-attente'],
    'confirmee'  => ['label' => 'Confirmée',  'classe' => 'badge-confirme'],
    'annulee'    => ['label' => 'Annulée',    'classe' => 'badge-annule'],
    'expiree'    => ['label' => 'Expirée',    'classe' => 'badge-expire'],
    'renouvele'  => ['label' => 'Renouvelée', 'classe' => 'badge-renouvele'],
];

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations — My Pharmacy</title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/mes_reservations.css">
    <script src="../Dos-js/header.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <?php include '../../Include_general/header.php'; ?>

    <!-- ===== Hero ===== -->
    <section class="mresa-hero">
        <a href="acceuil.php" class="mresa-retour">
            <i class="fa-solid fa-arrow-left"></i> Retour à mon espace
        </a>
        <span class="mresa-eyebrow">Historique</span>
        <h1>Mes réservations</h1>
        <p>Retrouvez toutes vos réservations et annulez celles encore en attente.</p>
    </section>

    <!-- ===== Filtres ===== -->
    <div class="mresa-filtres">

        <button class="filtre-btn actif" data-filtre="tous">
            Toutes <span class="filtre-compteur"><?php echo $total; ?></span>
        </button>

        <button class="filtre-btn" data-filtre="en_attente">
            En attente <span class="filtre-compteur"><?php echo $compteurs['en_attente']; ?></span>
        </button>

        <button class="filtre-btn" data-filtre="confirmee">
            Confirmées <span class="filtre-compteur"><?php echo $compteurs['confirmee']; ?></span>
        </button>

        <button class="filtre-btn" data-filtre="annulee">
            Annulées <span class="filtre-compteur"><?php echo $compteurs['annulee']; ?></span>
        </button>

        <button class="filtre-btn" data-filtre="renouvele">
            Renouvelées <span class="filtre-compteur"><?php echo $compteurs['renouvele']; ?></span>
        </button>

        <button class="filtre-btn" data-filtre="expiree">
            Expirées <span class="filtre-compteur"><?php echo $compteurs['expiree']; ?></span>
        </button>

    </div>

    <!-- ===== Liste des réservations ===== -->
    <section class="mresa-contenu" id="mresa-liste">

        <?php if (empty($groupes)) : ?>
            <div class="mresa-vide">
                <i class="fa-solid fa-basket-shopping"></i>
                <p>Vous n'avez encore effectué aucune réservation.</p>
                <a href="../../int_Public/Dos-page/pharmacie.php" class="mresa-btn-chercher">
                    Trouver une pharmacie
                </a>
            </div>

        <?php else : ?>
            <?php foreach ($groupes as $groupe) :
                $badge         = $badges[$groupe['statut']] ?? ['label' => $groupe['statut'], 'classe' => 'badge-expire'];
                $date_formatee = date('d/m/Y à H:i', strtotime($groupe['date_reservation']));
                $expire_fmt    = date('d/m/Y à H:i', strtotime($groupe['expire_at']));
            ?>
                <div class="mresa-carte" data-statut="<?php echo htmlspecialchars($groupe['statut']); ?>">

                    <!-- En-tête -->
                    <div class="mresa-carte-head">
                        <div class="mresa-head-left">
                            <span class="mresa-code">
                                <i class="fa-solid fa-tag"></i>
                                <?php echo htmlspecialchars($groupe['code_reservation']); ?>
                            </span>
                            <span class="mresa-pharmacie">
                                <i class="fa-solid fa-location-dot"></i>
                                <?php echo htmlspecialchars($groupe['nom_pharmacie']); ?> —
                                <?php echo htmlspecialchars($groupe['ville']); ?>,
                                <?php echo htmlspecialchars($groupe['quartier']); ?>
                            </span>
                        </div>
                        <span class="badge <?php echo $badge['classe']; ?>">
                            <?php echo $badge['label']; ?>
                        </span>
                    </div>

                   <!-- Produits en grille -->
                    <div class="mresa-produits-grille">

                        <?php foreach ($groupe['produits'] as $produit) : ?>

                            <div class="mresa-produit-tuile">
                                <i class="fa-solid fa-pills"></i>
                                <span class="mresa-produit-nom">
                                    <?php echo htmlspecialchars($produit['nom']); ?>
                                </span>
                                <span class="mresa-produit-forme">
                                    <?php echo htmlspecialchars($produit['forme']); ?>
                                </span>
                                <span class="mresa-produit-qte">
                                    Qté : <?php echo (int)$produit['quantite']; ?>
                                </span>
                            </div>

                        <?php endforeach; ?>
                    </div>

                    <!-- Pied de carte -->
                    <div class="mresa-carte-foot">
                        <div class="mresa-dates">
                            <span class="mresa-date-ligne">
                                <i class="fa-regular fa-calendar"></i>
                                Réservé le <?php echo $date_formatee; ?>
                            </span>
                            <?php if ($groupe['statut'] === 'en_attente') : ?>
                                <span class="mresa-date-ligne mresa-expire-info">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                    Expire le <?php echo $expire_fmt; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($groupe['statut'] === 'en_attente') : ?>
                                <button
                                    class="mresa-btn-annuler"
                                    data-code="<?php echo htmlspecialchars($groupe['code_reservation']); ?>">
                                    <i class="fa-solid fa-xmark"></i> Annuler
                                </button>

                            <?php elseif ($groupe['statut'] === 'expiree') : ?>
                                
                                <?php
                                    $expire_dt      = new DateTime($groupe['expire_at']);
                                    $maintenant_dt  = new DateTime();
                                    $diff_heures    = ($maintenant_dt->getTimestamp() - $expire_dt->getTimestamp()) / 3600;
                                ?>
                                <?php if ($diff_heures <= 10) : ?>

                                    <button
                                        class="mresa-btn-renouveler"
                                        data-code="<?php echo htmlspecialchars($groupe['code_reservation']); ?>">
                                        <i class="fa-solid fa-rotate-right"></i> Renouveler
                                   </button>

                                <?php else : ?>
                                    <span class="mresa-delai-depasse">
                                        <i class="fa-solid fa-clock"></i> Délai de renouvellement dépassé
                                    </span>
                                <?php endif; ?>

                            <?php endif; ?>

                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </section>

    <?php include '../../Include_general/footer.php'; ?>

    <!-- ===== Modale de confirmation d'annulation ===== -->
    <div class="mresa-overlay" id="mresa-overlay">
        <div class="mresa-modale">
            <i class="fa-solid fa-triangle-exclamation icone-alerte"></i>
            <h3>Confirmer l'annulation</h3>
            <p>
                Voulez-vous vraiment annuler la réservation
                <strong id="mresa-modale-code"></strong> ?
                <br>Cette action est irréversible.
            </p>
            <div class="mresa-modale-btns">
                <button class="mresa-modale-non" id="mresa-modale-non">Non, garder</button>
                <button class="mresa-modale-oui" id="mresa-modale-oui">Oui, annuler</button>
            </div>
        </div>
    </div>

    <!-- ===== Modale succès renouvellement ===== -->
<div class="mresa-overlay" id="mresa-overlay-succes">
    <div class="mresa-modale">
        <i class="fa-solid fa-circle-check" style="font-size:44px; color:#2ecc40; display:block; margin-bottom:14px;"></i>
        <h3>Réservation renouvelée !</h3>
        <p>Votre nouveau code de réservation est :</p>
        <div class="mresa-code-affiche" id="mresa-succes-code"></div>
        <p class="mresa-succes-expire">
            Valable jusqu'au <strong id="mresa-succes-expire"></strong>.<br>
            Présentez-vous à la pharmacie avant cette heure.
        </p>
        <div class="mresa-modale-btns">
            <button class="mresa-modale-oui" id="mresa-succes-fermer">Fermer</button>
        </div>
    </div>
</div>

<!-- ===== Modale renouvellement partiel (3 choix) ===== -->
<div class="mresa-overlay" id="mresa-overlay-partiel">
    <div class="mresa-modale mresa-modale-large">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:40px; color:#e67e22; display:block; margin-bottom:14px;"></i>
        <h3>Stock modifié</h3>
        <p>Certains produits de votre réservation ne sont plus disponibles en quantité suffisante :</p>
        <div class="mresa-partiel-liste" id="mresa-partiel-liste"></div>
        <p class="mresa-partiel-question">Que souhaitez-vous faire ?</p>

        <div class="mresa-partiel-btns">
            <button class="mresa-partiel-btn-prendre" id="mresa-partiel-prendre">
                <i class="fa-solid fa-check"></i> Prendre le reste
            </button>
            <button class="mresa-partiel-btn-modifier" id="mresa-partiel-modifier">
                <i class="fa-solid fa-pen-to-square"></i> Modifier la réservation
            </button>
            <button class="mresa-partiel-btn-refaire" id="mresa-partiel-refaire">
                <i class="fa-solid fa-rotate-left"></i> Refaire la composition complète
            </button>
            <button class="mresa-partiel-btn-abandonner" id="mresa-partiel-abandonner">
                <i class="fa-solid fa-xmark"></i> Abandonner
            </button>
        </div>

    </div>
</div>

    <script src="../Dos-js/mes_reservations.js"></script>
</body>
</html>