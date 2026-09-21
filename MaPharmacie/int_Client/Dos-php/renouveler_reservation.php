<?php

require_once '../../int_Public/Dos-php/config.php';
require_once '../../int_Public/Dos-php/mailer_config.php';
require_once '../../Include_general/config_fonctionnalites.php';

header('Content-Type: application/json');


// ===== Sécurité : utilisateur connecté =====

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);
    echo json_encode(['succes' => false, 'cas' => 'impossible', 'message' => 'Vous devez être connecté.']);
    exit();
}

$id_user = $_SESSION['user_id'];


// ===== Lecture des données envoyées =====

$donnees = json_decode(file_get_contents('php://input'), true);


// ===== Vérification du jeton CSRF =====

if (!isset($donnees['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $donnees['csrf_token'])) {

    http_response_code(403);
    echo json_encode(['succes' => false, 'cas' => 'impossible', 'message' => 'Requête invalide, veuillez rafraîchir la page.']);
    exit();
}

// L'ancienne réservation peut ne pas avoir de code_reservation si elle a expiré
// AVANT d'avoir été confirmée par le pharmacien (jamais accepté) — dans ce cas
// on la retrouve par son groupe_demande à la place.
$identifiant_ancien = trim($donnees['identifiant'] ?? '');
$produits_choisis    = $donnees['produits_choisis'] ?? null;

if (empty($identifiant_ancien)) {

    http_response_code(400);
    echo json_encode(['succes' => false, 'cas' => 'impossible', 'message' => 'Réservation à renouveler introuvable.']);
    exit();
}


try {

    // ===================================================================
    // Bloc 1 — Récupérer la réservation originale
    // ===================================================================

    $sql_ancien = "SELECT
                        r.id_stock,
                        r.quantite_reservee,
                        r.statut,
                        r.expire_at,
                        r.expire_demande_at,
                        r.renouvelable,
                        p.nom_medicament,
                        p.forme_pharmaceutique,
                        s.id_pharmacie
                   FROM reservations r
                   JOIN stocks s   ON s.id_stock   = r.id_stock
                   JOIN produits p ON p.id_produit = s.id_produit
                   WHERE (r.code_reservation = ? OR r.groupe_demande = ?)
                     AND r.id_user = ?";

    $stmt_ancien = $pdo->prepare($sql_ancien);
    $stmt_ancien->execute([$identifiant_ancien, $identifiant_ancien, $id_user]);
    $lignes_anciennes = $stmt_ancien->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lignes_anciennes)) {

        echo json_encode(['succes' => false, 'cas' => 'impossible', 'message' => 'Réservation introuvable.']);
        exit();
    }

    $premiere_ligne = $lignes_anciennes[0];


    // ===================================================================
    // Bloc 2 — Vérifications de sécurité
    // ===================================================================

    if ($premiere_ligne['statut'] !== 'expiree') {

        echo json_encode(['succes' => false, 'cas' => 'impossible', 'message' => 'Cette réservation ne peut pas être renouvelée.']);
        exit();
    }

    // Une réservation ne peut être renouvelée qu'une seule fois : si celle-ci
    // est déjà elle-même issue d'un renouvellement, on s'arrête ici.
    if (!$premiere_ligne['renouvelable']) {

        echo json_encode([
            'succes'  => false,
            'cas'     => 'impossible',
            'message' => 'Cette réservation a déjà été renouvelée une fois — le renouvellement n\'est possible qu\'une seule fois. Merci de refaire une nouvelle recherche.'
        ]);
        exit();
    }

    // La date de référence dépend de la cause de l'expiration : soit le
    // pharmacien n'a jamais répondu (expire_demande_at), soit le client n'est
    // pas venu chercher après confirmation (expire_at).
    $reference_expiration = $premiere_ligne['expire_at'] ?? $premiere_ligne['expire_demande_at'];

    $expire_ref  = new DateTime($reference_expiration);
    $maintenant  = new DateTime();
    $diff_heures = ($maintenant->getTimestamp() - $expire_ref->getTimestamp()) / 3600;

    if ($diff_heures > 5) {

        echo json_encode([
            'succes'  => false,
            'cas'     => 'impossible',
            'message' => 'Le délai de renouvellement de 5 heures est dépassé. Veuillez refaire une recherche.'
        ]);
        exit();
    }

    $id_pharmacie = $premiere_ligne['id_pharmacie'];


    // ===================================================================
    // Bloc 3 — Aperçu du stock pour chaque produit (INDICATIF)
    // ===================================================================
    // Comme pour une demande de réservation normale, le stock n'est pas
    // bloqué ici : seules les réservations déjà CONFIRMÉES par un pharmacien
    // comptent comme réellement engagées. Ce chiffre sert seulement à
    // prévenir le client si une quantité est visiblement devenue impossible,
    // pas à garantir une réservation ferme — la décision finale reste celle
    // du pharmacien au moment où il traite la nouvelle demande.

    $sql_stock = "SELECT
                    s.quantite_disponible - COALESCE((
                        SELECT SUM(r2.quantite_reservee)
                        FROM reservations r2
                        WHERE r2.id_stock = s.id_stock
                          AND r2.statut = 'confirmee'
                          AND r2.expire_at > NOW()
                    ), 0) AS max_reservable
                  FROM stocks s
                  WHERE s.id_stock = ?";

    $stmt_stock = $pdo->prepare($sql_stock);

    $produits_ok        = [];
    $produits_problemes = [];

    foreach ($lignes_anciennes as $ligne) {

        $stmt_stock->execute([$ligne['id_stock']]);
        $stock      = $stmt_stock->fetch(PDO::FETCH_ASSOC);
        $stmt_stock->closeCursor();
        $disponible = max(0, (int) ($stock['max_reservable'] ?? 0));
        $demande    = (int) $ligne['quantite_reservee'];

        if ($disponible >= $demande) {

            $produits_ok[] = [
                'id_stock' => $ligne['id_stock'],
                'nom'      => $ligne['nom_medicament'],
                'forme'    => $ligne['forme_pharmaceutique'],
                'quantite' => $demande
            ];

        } else {

            $produits_problemes[] = [
                'id_stock'   => $ligne['id_stock'],
                'nom'        => $ligne['nom_medicament'],
                'forme'      => $ligne['forme_pharmaceutique'],
                'demande'    => $demande,
                'disponible' => $disponible
            ];
        }
    }


    // ===================================================================
    // Bloc 4 — Décision
    // ===================================================================

    if (!empty($produits_problemes) && $produits_choisis === null) {

        echo json_encode([
            'succes'             => true,
            'cas'                => 'partiel',
            'id_pharmacie'       => $id_pharmacie,
            'produits_ok'        => $produits_ok,
            'produits_problemes' => $produits_problemes
        ]);
        exit();
    }

    // Sécurité : jamais confiance aveugle à "produits_choisis" envoyé par le client.
    // On ne garde que les entrées dont l'id_stock fait bien partie de la réservation
    // d'origine, avec la quantité d'origine (jamais celle envoyée par le client).
    if ($produits_choisis !== null) {

        $quantites_par_stock = [];
        foreach ($produits_ok as $p) {
            $quantites_par_stock[$p['id_stock']] = $p['quantite'];
        }

        $liste_finale = [];
        foreach ((array) $produits_choisis as $choix) {

            $id_stock_choisi = $choix['id_stock'] ?? null;

            if ($id_stock_choisi !== null && array_key_exists($id_stock_choisi, $quantites_par_stock)) {
                $liste_finale[] = [
                    'id_stock' => $id_stock_choisi,
                    'quantite' => $quantites_par_stock[$id_stock_choisi],
                ];
            }
        }

    } else {
        $liste_finale = $produits_ok;
    }

    if (empty($liste_finale)) {

        echo json_encode(['succes' => false, 'cas' => 'impossible', 'message' => 'Aucun produit disponible pour le renouvellement.']);
        exit();
    }


    // ===================================================================
    // Bloc 5 — Insertion de la nouvelle DEMANDE (pas encore de code : elle
    // devra être acceptée par le pharmacien, comme n'importe quelle demande)
    // ===================================================================

    $pdo->beginTransaction();

    $nouveau_groupe     = bin2hex(random_bytes(8));
    $expire_demande_sql = "NOW() + INTERVAL '12 hours'";

    $sql_insert = "INSERT INTO reservations
                        (id_user, id_stock, quantite_reservee, groupe_demande, date_reservation, expire_demande_at, statut, renouvelable)
                    VALUES (?, ?, ?, ?, NOW(), $expire_demande_sql, 'demandee', FALSE)";

    $stmt_insert = $pdo->prepare($sql_insert);

    foreach ($liste_finale as $produit) {
        $stmt_insert->execute([
            $id_user,
            $produit['id_stock'],
            (int) $produit['quantite'],
            $nouveau_groupe
        ]);
    }


    // ===================================================================
    // Bloc 5bis — Marquer l'ancienne réservation comme renouvelée, avec un
    // lien vers le groupe de la nouvelle demande (pas encore de code)
    // ===================================================================

    $sql_marquer_ancienne = "UPDATE reservations
                              SET statut = 'renouvele', reserve_code_renouvele = ?
                              WHERE (code_reservation = ? OR groupe_demande = ?) AND id_user = ?";
    $stmt_marquer         = $pdo->prepare($sql_marquer_ancienne);
    $stmt_marquer->execute([$nouveau_groupe, $identifiant_ancien, $identifiant_ancien, $id_user]);

    $pdo->commit();

    // ===== Notification immédiate au(x) pharmacien(s) — même logique que pour une demande normale =====
    $sql_admins = "SELECT a.email, a.prenom, p.nom_pharmacie
                   FROM administrateurs a
                   JOIN pharmacies p ON p.id_pharmacie = a.id_pharmacie
                   WHERE a.id_pharmacie = ? AND a.statut = 'actif'";
    $stmt_admins = $pdo->prepare($sql_admins);
    $stmt_admins->execute([$id_pharmacie]);
    $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

    foreach ($admins as $admin) {

        $sujet = "Nouvelle demande de réservation (renouvellement) — " . $admin['nom_pharmacie'];
        $corps = "
            <p>Bonjour " . htmlspecialchars($admin['prenom']) . ",</p>
            <p>Une demande de réservation renouvelée vient d'arriver pour " . htmlspecialchars($admin['nom_pharmacie']) . ".</p>
            <p>Vous avez 12h pour l'accepter ou la rejeter.</p>
            <p><a href=\"" . SITE_URL . "/int_Admin/Dos-page/demandes_reservation.php\">Voir la demande dans mon espace pharmacien</a></p>
        ";

        envoyerEmail($admin['email'], $sujet, $corps);
    }

    echo json_encode([
        'succes'  => true,
        'cas'     => 'ok',
        'message' => 'Votre demande de renouvellement a été envoyée à la pharmacie. '
                   . 'Vous recevrez un email dès qu\'elle sera acceptée, avec votre code de retrait.'
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['succes' => false, 'cas' => 'erreur', 'message' => 'Erreur serveur. Veuillez réessayer.']);
}
