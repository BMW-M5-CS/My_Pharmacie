
<?php

require_once '../Dos-php/config.php';
require_once '../Dos-php/fonctions_notation.php';

$id_pharmacie = $_GET['id_pharmacie'] ?? null;

if (!$id_pharmacie || !is_numeric($id_pharmacie)) {
    header('Location: pharmacie.php');
    exit();
}

$sql_pharmacie = "SELECT id_pharmacie, nom_pharmacie, ville FROM pharmacies WHERE id_pharmacie = ?";
$stmt = $pdo->prepare($sql_pharmacie);
$stmt->execute([$id_pharmacie]);
$pharmacie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pharmacie) {
    header('Location: pharmacie.php');
    exit();
}

$sql_moyenne = "SELECT COALESCE(AVG(note), 0) AS note_moyenne, COUNT(*) AS nombre_avis FROM avis WHERE id_pharmacie = ?";
$stmt_moyenne = $pdo->prepare($sql_moyenne);
$stmt_moyenne->execute([$id_pharmacie]);
$moyenne = $stmt_moyenne->fetch(PDO::FETCH_ASSOC);

$note_moyenne = round((float) $moyenne['note_moyenne'], 1);
$nombre_avis  = (int) $moyenne['nombre_avis'];

$repartition = repartitionAvis($pdo, $id_pharmacie);

$sql_avis = "SELECT a.note, a.commentaire, a.date_avis, COALESCE(u.prenom, 'Utilisateur supprimé') AS prenom
             FROM avis a
             LEFT JOIN users u ON u.id_user = a.id_user
             WHERE a.id_pharmacie = ?
             ORDER BY a.date_avis DESC";
$stmt_avis = $pdo->prepare($sql_avis);
$stmt_avis->execute([$id_pharmacie]);
$liste_avis = $stmt_avis->fetchAll(PDO::FETCH_ASSOC);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$peut_noter    = false;
$avis_existant = null;

if (isset($_SESSION['user_id'])) {

    $peut_noter = verifierEligibiliteAvis($pdo, $_SESSION['user_id'], $id_pharmacie);

    $sql_mon_avis = "SELECT note, commentaire FROM avis WHERE id_user = ? AND id_pharmacie = ?";
    $stmt_mon_avis = $pdo->prepare($sql_mon_avis);
    $stmt_mon_avis->execute([$_SESSION['user_id'], $id_pharmacie]);
    $avis_existant = $stmt_mon_avis->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis — <?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?></title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/avis-pharmacie.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>


    <div class="avis-entete">

        <div class="entete-fond"></div>

        <div class="entete-contenu">

            <a href="pharmacie.php" class="lien-retour"><i class="fa-solid fa-arrow-left"></i> Retour aux pharmacies</a>

            <div class="title">
                <i class="fa-solid fa-star"></i>
                <h1>Avis — <?php echo htmlspecialchars($pharmacie['nom_pharmacie']); ?></h1>
            </div>

            <p class="text"><?php echo htmlspecialchars($pharmacie['ville']); ?></p>

        </div>

    </div>


    <section class="avis-section">

        <div class="avis-container">

            <?php if (isset($_GET['succes'])) : ?>

                <div class="message-succes">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Merci, ton avis a bien été enregistré !</span>
                </div>

            <?php endif; ?>


            <div class="resume-notation">

                <div class="resume-moyenne">
                    <div class="resume-chiffre"><?php echo $nombre_avis > 0 ? number_format($note_moyenne, 1) : '—'; ?></div>
                    <div class="resume-etoiles"><?php echo afficherEtoiles($note_moyenne); ?></div>
                    <div class="resume-total"><?php echo $nombre_avis; ?> avis</div>
                </div>

                <div class="resume-barres">

                    <?php foreach ($repartition as $etoile => $total) : ?>

                        <div class="barre-ligne">
                            <span class="barre-label"><?php echo $etoile; ?> <i class="fa-solid fa-star"></i></span>
                            <div class="barre-fond">
                                <div class="barre-remplie" style="width: <?php echo $nombre_avis > 0 ? round($total / $nombre_avis * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="barre-total"><?php echo $total; ?></span>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <div class="ecrire-avis">

                <?php if (!isset($_SESSION['user_id'])) : ?>

                    <div class="avis-non-eligible">
                        <i class="fa-solid fa-lock"></i>
                        <p>Connecte-toi pour laisser un avis sur cette pharmacie.</p>
                        <a href="conex.php" class="btn-lien-conex">Se connecter</a>
                    </div>

                <?php elseif (!$peut_noter) : ?>

                    <div class="avis-non-eligible">
                        <i class="fa-solid fa-circle-info"></i>
                        <p>Tu dois avoir une réservation confirmée dans cette pharmacie pour pouvoir la noter.</p>
                    </div>

                <?php else : ?>

                    <h3><?php echo $avis_existant ? 'Modifier mon avis' : 'Laisser un avis'; ?></h3>

                    <form method="POST" action="../Dos-php/traite_avis.php">

                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id_pharmacie" value="<?php echo $id_pharmacie; ?>">

                        <div class="etoiles-input">

                            <?php for ($i = 5; $i >= 1; $i--) : ?>
                                <input type="radio" name="note" id="n<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo (isset($avis_existant['note']) && $avis_existant['note'] == $i) ? 'checked' : ''; ?> required>
                                <label for="n<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                            <?php endfor; ?>

                        </div>

                        <textarea name="commentaire" placeholder="Ton commentaire (facultatif)"><?php echo htmlspecialchars($avis_existant['commentaire'] ?? ''); ?></textarea>

                        <button type="submit" class="btn-envoyer-avis">
                            <i class="fa-solid fa-paper-plane"></i> <?php echo $avis_existant ? 'Mettre à jour' : 'Envoyer mon avis'; ?>
                        </button>

                    </form>

                <?php endif; ?>

            </div>


            <div class="liste-avis">

                <h3>Tous les avis <span>(<?php echo $nombre_avis; ?>)</span></h3>

                <?php if (empty($liste_avis)) : ?>

                    <p class="aucun-avis">Aucun avis pour le moment.</p>

                <?php else : ?>

                    <?php foreach ($liste_avis as $avis) : ?>

                        <div class="avis-item">

                            <div class="avis-item-haut">
                                <span class="avis-auteur"><?php echo htmlspecialchars($avis['prenom']); ?></span>
                                <span class="avis-date"><?php echo date('d M Y', strtotime($avis['date_avis'])); ?></span>
                            </div>

                            <div class="avis-etoiles"><?php echo afficherEtoiles((float) $avis['note']); ?></div>

                            <?php if (!empty($avis['commentaire'])) : ?>
                                <p class="avis-commentaire"><?php echo nl2br(htmlspecialchars($avis['commentaire'])); ?></p>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <?php include '../../Include_general/footer.php'; ?>

</body>
</html>