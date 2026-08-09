<?php
require_once '../../int_Public/Dos-php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../int_Public/Dos-page/conex.php');
    exit();
}

$sql = "SELECT p.id_produit, p.nom_medicament, p.forme_pharmaceutique, p.prix_unitaire_fcfa, h.date_consultee
        FROM historique_recherche h
        JOIN produits p ON p.id_produit = h.id_produit
        WHERE h.id_user = ?
        ORDER BY h.date_consultee DESC
        LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$historique = $stmt->fetchAll();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function imagePlaceholderProduit($id) {
    return 'https://picsum.photos/seed/produit' . $id . '/400/400';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon historique</title>
    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../Dos-css/historique.css">
    <link rel="stylesheet" href="../Dos-css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <?php include '../../Include_general/header.php'; ?>
    <script src="../Dos-js/header.js" defer></script>


    <div class="historique-hero">

        <a href="acceuil.php" class="historique-retour"><i class="fa-solid fa-arrow-left"></i> Retour à mon espace</a>

        <div class="historique-hero-haut">

            <div>
                <span class="historique-eyebrow">Consultations récentes</span>
                <h1><i class="fa-solid fa-clock-rotate-left"></i> Mon historique</h1>
                <p>Retrouve les produits que tu as récemment consultés, pour les réserver à nouveau en un clic.</p>
            </div>

            <?php if (!empty($historique)) : ?>
                <form method="POST" action="../Dos-php/vider_historique.php" onsubmit="return confirm('Vider tout ton historique de consultation ?');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn-vider"><i class="fa-solid fa-trash"></i> Vider l'historique</button>
                </form>
            <?php endif; ?>

        </div>

    </div>


    <section class="historique-section">

        <?php if (isset($_GET['vide'])) : ?>

            <div class="message-succes">
                <i class="fa-solid fa-circle-check"></i>
                <span>Ton historique a bien été vidé.</span>
            </div>

        <?php endif; ?>

        <?php if (empty($historique)) : ?>

            <div class="aucun-historique">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <p>Aucun produit consulté pour le moment.</p>
                <a href="../../int_Public/Dos-page/produit.php" class="btn-parcourir">Parcourir les produits</a>
            </div>

        <?php else : ?>

            <div class="historique-scroll">

                <div class="grille-historique">

                    <?php foreach ($historique as $item) : ?>

                        <article class="carte-historique" data-id="<?php echo $item['id_produit']; ?>">

                            <div class="carte-historique-image">
                                <img src="<?php echo imagePlaceholderProduit($item['id_produit']); ?>" alt="<?php echo htmlspecialchars($item['nom_medicament']); ?>" loading="lazy">
                            </div>

                            <div class="carte-historique-corps">

                                <h3><?php echo htmlspecialchars($item['nom_medicament']); ?></h3>
                                <span class="carte-historique-forme"><?php echo htmlspecialchars($item['forme_pharmaceutique']); ?></span>

                                <div class="carte-historique-bas">
                                    <span class="carte-historique-prix"><?php echo htmlspecialchars($item['prix_unitaire_fcfa']); ?> FCFA</span>
                                    <span class="carte-historique-date">Consulté le <?php echo date('d/m/Y', strtotime($item['date_consultee'])); ?></span>
                                </div>

                                <button class="btn-voir-produit-historique" data-id="<?php echo $item['id_produit']; ?>">
                                    <i class="fa-solid fa-eye"></i> Revoir la fiche
                                </button>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>
                
            </div>

        <?php endif; ?>

    </section>


    <?php include '../../Include_general/footer.php'; ?>

    <script src="../Dos-js/historique.js"></script>

</body>
</html>