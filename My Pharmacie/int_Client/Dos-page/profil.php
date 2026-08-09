<?php

require_once '../../int_Public/Dos-php/config.php';


// ===== Redirection si une réinitialisation de mot de passe est en attente =====

if (!empty($_SESSION['reinitialisation_en_attente'])) {

    header("Location: ../../int_Public/Dos-page/reinitialisation_mdp.php?token=" . urlencode($_SESSION['reinitialisation_token']));
    exit();
}


// ===== Vérification de la session =====

if (!isset($_SESSION['user_id'])) {

    header('Location: ../../int_Public/Dos-page/conex.php');
    exit();
}

$id_user = $_SESSION['user_id'];


// ===== Génération du jeton CSRF, réutilisé s'il existe déjà =====

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// ===== Récupération des infos actuelles de l'utilisateur =====

$sql  = "SELECT nom, prenom, phone_email, contact_recuperation FROM users WHERE id_user = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$nom                  = $user['nom']                  ?? '';
$prenom               = $user['prenom']               ?? '';
$phone_email          = $user['phone_email']          ?? '';
$contact_recuperation = $user['contact_recuperation'] ?? '';

$initiales = strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

?>
<!DOCTYPE html>
<html lang="fr">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil — My Pharmacy</title>

    <link rel="stylesheet" href="../Dos-css/header.css">
    <link rel="stylesheet" href="../../int_Public/Dos-css/footer.css">
    <link rel="stylesheet" href="../Dos-css/profil.css">
    <script src="../Dos-js/header.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    

</head>
<body>

    <?php include '../../Include_general/header.php'; ?>


    <!-- ===== Hero ===== -->

    <section class="profil-hero">

        <a href="acceuil.php" class="profil-retour">
            <i class="fa-solid fa-arrow-left"></i> Retour à mon espace
        </a>

        <span class="profil-eyebrow">Mon compte</span>
        <h1>Mon profil</h1>
        <p>Gérez vos informations personnelles et la sécurité de votre compte.</p>

    </section>


    <!-- ===== Contenu principal ===== -->

    <div class="profil-contenu">


        <!-- ===== Carte avatar + résumé ===== -->

        <div class="profil-avatar-carte">

            <div class="profil-avatar">
                <?php echo htmlspecialchars($initiales); ?>
            </div>

            <div class="profil-avatar-info">
                <h2 id="profil-nom-complet">
                    <?php echo htmlspecialchars($prenom . ' ' . $nom); ?>
                </h2>
                <span id="profil-phone-email-resume">
                    <?php echo htmlspecialchars($phone_email); ?>
                </span>
            </div>

        </div>


        <!-- ===== Section 1 — Informations personnelles ===== -->

        <div class="profil-section">

            <div class="profil-section-head">
                <div class="profil-section-titre">
                    <i class="fa-solid fa-user"></i>
                    <h3>Informations personnelles</h3>
                </div>
                <button class="profil-btn-modifier" id="btn-modifier-infos">
                    <i class="fa-solid fa-pen"></i> Modifier
                </button>
            </div>


            <!-- Affichage lecture seule -->

            <div class="profil-champs-lecture" id="infos-lecture">

                <div class="profil-champ-ligne">
                    <span class="profil-champ-label">Prénom</span>
                    <span class="profil-champ-valeur" id="lecture-prenom">
                        <?php echo htmlspecialchars($prenom); ?>
                    </span>
                </div>

                <div class="profil-champ-ligne">
                    <span class="profil-champ-label">Nom</span>
                    <span class="profil-champ-valeur" id="lecture-nom">
                        <?php echo htmlspecialchars($nom); ?>
                    </span>
                </div>

                <div class="profil-champ-ligne">
                    <span class="profil-champ-label">Téléphone / Email</span>
                    <span class="profil-champ-valeur" id="lecture-phone-email">
                        <?php echo htmlspecialchars($phone_email); ?>
                    </span>
                </div>

                <div class="profil-champ-ligne">
                    <span class="profil-champ-label">Contact de récupération</span>
                    <span class="profil-champ-valeur" id="lecture-contact-recuperation">
                        <?php echo htmlspecialchars($contact_recuperation ?: 'Non renseigné'); ?>
                    </span>
                </div>

            </div>


            <!-- Formulaire d'édition (caché par défaut) -->

            <div class="profil-champs-edition" id="infos-edition" style="display: none;">

                <div class="profil-input-groupe">
                    <label>Prénom</label>
                    <input
                        type="text"
                        id="edit-prenom"
                        value="<?php echo htmlspecialchars($prenom); ?>"
                        placeholder="Votre prénom">
                </div>

                <div class="profil-input-groupe">
                    <label>Nom</label>
                    <input
                        type="text"
                        id="edit-nom"
                        value="<?php echo htmlspecialchars($nom); ?>"
                        placeholder="Votre nom">
                </div>

                <div class="profil-input-groupe">
                    <label>Téléphone ou Email</label>
                    <input
                        type="text"
                        id="edit-phone-email"
                        value="<?php echo htmlspecialchars($phone_email); ?>"
                        placeholder="Téléphone ou adresse email">
                </div>

                <div class="profil-input-groupe">
                    <label>Contact de récupération <span class="profil-optionnel">(optionnel)</span></label>
                    <input
                        type="text"
                        id="edit-contact-recuperation"
                        value="<?php echo htmlspecialchars($contact_recuperation); ?>"
                        placeholder="Un email ou un numero pour récupérer votre compte si besoin">
                </div>

                <div class="profil-edition-btns">
                    <button class="profil-btn-annuler-edition" id="btn-annuler-infos">
                        Annuler
                    </button>
                    <button class="profil-btn-enregistrer" id="btn-enregistrer-infos">
                        <i class="fa-solid fa-check"></i> Enregistrer
                    </button>
                </div>

                <div class="profil-message" id="message-infos"></div>

            </div>

        </div>


        <!-- ===== Section 2 — Sécurité / Mot de passe ===== -->

        <div class="profil-section">

            <div class="profil-section-head">
                <div class="profil-section-titre">
                    <i class="fa-solid fa-lock"></i>
                    <h3>Sécurité</h3>
                </div>
            </div>

            <div class="profil-champs-edition">

                <div class="profil-input-groupe">
                    <label>Mot de passe actuel</label>
                    <div class="profil-input-mdp">
                        <input
                            type="password"
                            id="mdp-actuel"
                            autocomplete="new-password"
                            placeholder="Votre mot de passe actuel">
                        <i class="fa-solid fa-eye profil-toggle-mdp" data-cible="mdp-actuel"></i>
                    </div>
                </div>

                <div class="profil-input-groupe">
                    <label>Nouveau mot de passe</label>
                    <div class="profil-input-mdp">
                        <input
                            type="password"
                            id="nouveau-mdp"
                            autocomplete="new-password"
                            placeholder="Au moins 6 caractères">
                        <i class="fa-solid fa-eye profil-toggle-mdp" data-cible="nouveau-mdp"></i>
                    </div>
                </div>

                <div class="profil-input-groupe">
                    <label>Confirmer le nouveau mot de passe</label>
                    <div class="profil-input-mdp">
                        <input
                            type="password"
                            id="confirm-mdp"
                            autocomplete="new-password"
                            placeholder="Répétez le nouveau mot de passe">
                        <i class="fa-solid fa-eye profil-toggle-mdp" data-cible="confirm-mdp"></i>
                    </div>
                </div>

                <div class="profil-edition-btns">
                    <button class="profil-btn-enregistrer" id="btn-enregistrer-mdp">
                        <i class="fa-solid fa-lock"></i> Changer le mot de passe
                    </button>
                </div>

                <div class="profil-message" id="message-mdp"></div>

            </div>

        </div>


    </div>

    <!-- ===== Modale confirmation mot de passe pour modification infos ===== -->

    <div class="profil-overlay" id="profil-overlay">

        <div class="profil-modale">

            <i class="fa-solid fa-shield-halved profil-modale-icone"></i>
            <h3>Confirmer votre identité</h3>
            <p>Entrez votre mot de passe actuel pour confirmer les modifications.</p>

            <div class="profil-input-mdp">
                <input
                    type="password"
                    id="mdp-confirmation-modale"
                    autocomplete="new-password"
                    placeholder="Votre mot de passe actuel">
                <i class="fa-solid fa-eye profil-toggle-mdp" id="icone-oeil" data-cible="mdp-confirmation-modale"></i>
            </div>

            <div class="profil-message" id="message-modale"></div>

            <div class="profil-modale-btns">
                <button class="profil-modale-annuler" id="modale-annuler">Annuler</button>
                <button class="profil-modale-confirmer" id="modale-confirmer">
                    <i class="fa-solid fa-check"></i> Confirmer
                </button>
            </div>

        </div>

    </div>

    <?php include '../../Include_general/footer.php'; ?>

    <input type="hidden" id="csrf-token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <script src="../Dos-js/profil.js"></script>

</body>
</html>