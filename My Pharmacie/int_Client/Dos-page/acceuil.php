<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../int_Public/Dos-page/conex.php");
    exit();
}

require_once '../../int_Public/Dos-php/config.php';

$id_user = $_SESSION['user_id'];

// ===== Statistiques des réservations par statut =====
$sql_stats = "SELECT statut, COUNT(*) as total
              FROM reservations
              WHERE id_user = ?
              GROUP BY statut";
$stmt = $pdo->prepare($sql_stats);
$stmt->execute([$id_user]);
$stats_brutes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$nb_attente   = $stats_brutes['en_attente'] ?? 0;
$nb_confirmee = $stats_brutes['confirmee']  ?? 0;
$nb_annulee   = $stats_brutes['annulee']    ?? 0;
$nb_expiree   = $stats_brutes['expiree']    ?? 0;
$nb_total     = $nb_attente + $nb_confirmee + $nb_annulee + $nb_expiree;

// ===== Les 4 dernières réservations avec détails produit + pharmacie =====
$sql_resa = "SELECT r.id_res, r.quantite_reservee, r.code_reservation, r.date_reservation, r.statut,
                    p.nom_medicament, p.forme_pharmaceutique,
                    ph.nom_pharmacie, ph.ville, ph.quartier
             FROM reservations r
             JOIN stocks s    ON s.id_stock = r.id_stock
             JOIN produits p  ON p.id_produit = s.id_produit
             JOIN pharmacies ph ON ph.id_pharmacie = s.id_pharmacie
             WHERE r.id_user = ?
             ORDER BY r.date_reservation DESC
             LIMIT 4";
$stmt2 = $pdo->prepare($sql_resa);
$stmt2->execute([$id_user]);
$dernieres_reservations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ===== Initiales pour l'avatar =====
$prenom    = $_SESSION['prenom'] ?? '';
$nom       = $_SESSION['nom'] ?? '';
$initiales = strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceuil client</title>
    <link rel="stylesheet" href="../Dos-css/acceuil.css">
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <script src="../Dos-js/header.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include '../../Include_general/header.php'; ?>

    <section class="hero-client">
        <div class="hero-top">
            <div class="hero-left">
                <h1>Bon retour parmi nous, <span><?php echo htmlspecialchars($prenom); ?></span> <span class="wave">👋</span></h1>
                <p>Votre espace personnel — retrouvez vos réservations, gérez votre profil<br>
                   et accédez rapidement aux pharmacies près de vous.</p>
                <div class="date-line">
                    <i class="fa-solid fa-calendar-days"></i>
                    <?php echo date('l d F Y'); ?> — Lomé, Togo
                </div>
            </div>
            <div class="avatar"><?php echo htmlspecialchars($initiales); ?></div>
        </div>
    </section>

    <section class="stats-row">
        <div class="stat-card stat-green">
            <div class="stat-ico"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-text">
                <span class="stat-label">En attente</span>
                <span class="stat-value"><?php echo $nb_attente; ?></span>
            </div>
        </div>

        <div class="stat-card stat-orange">
            <div class="stat-ico"><i class="fa-solid fa-check"></i></div>
            <div class="stat-text">
                <span class="stat-label">Confirmées</span>
                <span class="stat-value"><?php echo $nb_confirmee; ?></span>
            </div>
        </div>

        <div class="stat-card stat-blue">
            <div class="stat-ico"><i class="fa-solid fa-list-check"></i></div>
            <div class="stat-text">
                <span class="stat-label">Total réservations</span>
                <span class="stat-value"><?php echo $nb_total; ?></span>
            </div>
        </div>

        <div class="stat-card stat-red">
            <div class="stat-ico"><i class="fa-solid fa-xmark"></i></div>
            <div class="stat-text">
                <span class="stat-label">Annulées / Expirées</span>
                <span class="stat-value"><?php echo $nb_annulee + $nb_expiree; ?></span>
            </div>
        </div>
        
    </section>

    <section class="main-grid">

        <div class="col-left">
            <div class="card">
                <div class="card-title">
                    <span><i class="fa-solid fa-calendar-check"></i> Dernières réservations</span>
                    <a href="../Dos-page/mes_reservations.php" class="voir-tout">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <?php if (empty($dernieres_reservations)) : ?>
                    <p class="aucune-reservation">
                        Vous n'avez encore fait aucune réservation. 
                        <a href="../../int_Public/Dos-page/produit.php">Parcourez nos produits</a> pour commencer.
                    </p>
                <?php else : ?>
                    <?php foreach ($dernieres_reservations as $resa) : ?>
                        <div class="resa-item">
                            <div class="resa-left">
                                <div class="resa-ico"><i class="fa-solid fa-pills"></i></div>
                                <div>
                                    <div class="resa-nom">
                                        <?php echo htmlspecialchars($resa['nom_medicament']); ?> —
                                        <?php echo htmlspecialchars($resa['forme_pharmaceutique']); ?>
                                    </div>
                                    <div class="resa-pharmacie">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?php echo htmlspecialchars($resa['nom_pharmacie']); ?> —
                                        <?php echo htmlspecialchars($resa['ville']); ?>, <?php echo htmlspecialchars($resa['quartier']); ?>
                                    </div>
                                    <div class="resa-date">
                                        <i class="fa-regular fa-clock"></i>
                                        Réservé le <?php echo date('d M Y à H:i', strtotime($resa['date_reservation'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                                $badge_classes = [
                                    'en_attente' => ['attente', 'En attente'],
                                    'confirmee'  => ['confirme', 'Confirmée'],
                                    'annulee'    => ['annule', 'Annulée'],
                                    'expiree'    => ['expire', 'Expirée'],
                                ];
                                $b = $badge_classes[$resa['statut']] ?? ['attente', $resa['statut']];
                            ?>
                            <span class="badge badge-<?php echo $b[0]; ?>"><?php echo $b[1]; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-right">
            <div class="profil-card">
                <div class="profil-avatar"><?php echo htmlspecialchars($initiales); ?></div>
                <div class="profil-nom"><?php echo htmlspecialchars($prenom . ' ' . $nom); ?></div>
                <div class="profil-contact"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($_SESSION['phone_email'] ?? ''); ?></div>
                <a href="profil.php" class="btn-edit-profil"><i class="fa-solid fa-pen"></i> Modifier mon profil</a>
            </div>

            <div class="card actions-card">
                <div class="card-title"><span><i class="fa-solid fa-bolt"></i> Actions rapides</span></div>
                <a href="../../int_Public/Dos-page/produit.php" class="action-btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Chercher un médicament</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <a href="../../int_Public/Dos-page/pharmacie.php" class="action-btn">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Trouver une pharmacie</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <a href="../../int_Public/Dos-page/carte.php" class="action-btn">
                    <i class="fa-solid fa-map"></i>
                    <span>Voir la carte</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <a href="../Dos-page/mes_reservations.php" class="action-btn">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Toutes mes réservations</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
                <a href="../../int_Public/Dos-page/contact.php" class="action-btn">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Nous contacter</span>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
            </div>
        </div>

    </section>

    <?php include'../../Include_general/footer.php'; ?>

    

</body>
</html>