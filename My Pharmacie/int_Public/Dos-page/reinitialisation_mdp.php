<?php

require_once '../Dos-php/config.php';

// Empêche le navigateur de garder cette page en cache (y compris via le bouton
// "précédent" / bfcache). Sans ça, la page peut réafficher une version périmée
// du bouton "Ignorer" après la limite de 2 essais atteinte. Ce n'est pas une
// faille de sécurité en soi (traite_reinitialise_mdp.php revérifie déjà la
// limite côté serveur avant d'agir), mais c'est trompeur pour la personne :
// le bouton a l'air de fonctionner alors qu'il ne fait que la renvoyer ici.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$token = $_GET['token'] ?? '';
$token_valide = false;
$peut_ignorer = false;
$user = null;

if (!empty($token)) {
    $token_hash = hash('sha256', $token);

    $sql = "SELECT rt.*, u.id_user, u.prenom, u.info_modif_mdp
            FROM reset_tokens rt
            JOIN users u ON u.id_user = rt.id_user
            WHERE rt.token = ? AND rt.utilise = FALSE AND rt.date_expiration > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token_hash]);
    $ligne = $stmt->fetch();

    if ($ligne) {
        $token_valide = true;
        $user = $ligne;

        // Compter les "ignorer" depuis le dernier changement de mot de passe
        $sql_compte = "SELECT COUNT(*) AS nb
                       FROM journal_securite_compte
                       WHERE id_user = ?
                         AND type_evenement = 'reinitialisation_ignoree'
                         AND date_evenement > COALESCE(?::timestamp, '1970-01-01')";
        $stmt_compte = $pdo->prepare($sql_compte);
        $stmt_compte->execute([$user['id_user'], $user['info_modif_mdp']]);
        $compte = $stmt_compte->fetch();

        $peut_ignorer = ((int) $compte['nb']) < 2;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe — My Pharmacie</title>
    <link rel="stylesheet" href="../Dos-css/reinitialisation_mdp.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="boite-centrale">

        <?php if (!$token_valide): ?>

            <h1><i class="fa-solid fa-triangle-exclamation"></i> Lien invalide</h1>
            <p>Ce lien de réinitialisation n'est plus valide. Il a peut-être expiré ou déjà été utilisé.</p>
            <p class="rappel">Rappel : les demandes sont limitées à 3 par 24h. Si vous avez atteint cette limite, réessayez dans 24h.</p>
            <a href="mdp_oublie.php" class="bouton-principal">Faire une nouvelle demande</a>

        <?php else: ?>

            <h1><i class="fa-solid fa-key"></i> Choisir un nouveau mot de passe</h1>
            <p>Bonjour <?= htmlspecialchars($user['prenom']) ?>, choisissez votre nouveau mot de passe.</p>

            <form method="POST" action="../Dos-php/traite_reinitialise_mdp.php" id="form-reinitialisation">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="action" value="reinitialiser">

                <label for="nouveau-mdp">Nouveau mot de passe</label>
                <input type="password" id="nouveau-mdp" name="nouveau_mdp" autocomplete="new-password" required minlength="8">

                <label for="confirm-mdp">Confirmer le mot de passe</label>
                <input type="password" id="confirm-mdp" name="confirm_mdp" autocomplete="new-password" required minlength="8">

                <p class="erreur" id="msg-erreur" style="display:none;"></p>

                <button type="submit" class="bouton-principal">Réinitialiser le mot de passe</button>
            </form>

            <?php if ($peut_ignorer): ?>
                <form method="POST" action="../Dos-php/traite_reinitialise_mdp.php" id="form-ignorer">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="ignorer">
                    <button type="submit" class="bouton-secondaire">Ignorer pour l'instant (urgence)</button>
                </form>
            <?php else: ?>
                <p class="rappel">Vous avez déjà utilisé l'option "Ignorer" 2 fois. Vous devez maintenant réinitialiser votre mot de passe pour continuer.</p>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <script>
    // Si cette page est restaurée depuis le cache de navigation arrière du
    // navigateur (bouton "précédent"), certains navigateurs ignorent l'en-tête
    // Cache-Control pour cette restauration précise. On force donc un rechargement
    // complet dans ce cas, pour être sûr que l'état "Ignorer" affiché est à jour.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    document.getElementById('form-reinitialisation')?.addEventListener('submit', function(e) {
        const mdp = document.getElementById('nouveau-mdp').value;
        const confirm = document.getElementById('confirm-mdp').value;
        const erreur = document.getElementById('msg-erreur');

        if (mdp !== confirm) {
            e.preventDefault();
            erreur.textContent = "Les mots de passe ne correspondent pas.";
            erreur.style.display = 'block';
        }
    });
    </script>
</body>
</html>