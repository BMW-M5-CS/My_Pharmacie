// ===================================================================
// Statut pharmacie (ouverte / de garde / fermée) — utilitaires partagés
// Couleurs alignées sur Include_general/variables.css (direction "identité
// togolaise"). Toute page qui affiche un statut de pharmacie doit charger
// ce fichier plutôt que redéfinir ses propres constantes de couleur.
// ===================================================================

function couleurPourStatut(statutCalcule) {
    if (statutCalcule === 'garde')   return '#B5432D'; // terre cuite — urgence
    if (statutCalcule === 'ouverte') return '#1D5C3B'; // vert principal
    return '#6B6558'; // fermée — gris-brun
}

function libellePourStatut(statutCalcule) {
    if (statutCalcule === 'garde')   return 'Pharmacie de garde';
    if (statutCalcule === 'ouverte') return 'Ouverte';
    return 'Fermée';
}

// Icône Font Awesome (classe) associée à chaque statut — utilisée là où un
// badge affiche une icône plutôt qu'un simple point de couleur.
function iconeClassePourStatut(statutCalcule) {
    if (statutCalcule === 'garde')   return 'fa-solid fa-truck-medical';
    if (statutCalcule === 'ouverte') return 'fa-solid fa-circle-check';
    return 'fa-solid fa-clock';
}

// Construit le petit texte "(réouverture à 08:00)" à partir de l'heure
// d'ouverture brute renvoyée par le serveur (format HH:MM:SS). Renvoie une
// chaîne vide si l'heure n'est pas connue.
function formaterHeureReouverture(pharmacie) {
    return pharmacie.heure_ouverture ? ' (réouverture à ' + pharmacie.heure_ouverture.slice(0, 5) + ')' : '';
}

// Badge HTML complet, prêt à insérer dans une carte/liste de pharmacie.
// Ajoute l'heure de réouverture uniquement quand la pharmacie est fermée.
function construireBadgeStatut(pharmacie) {
    const statut  = pharmacie.statut_calcule;
    const suffixe = statut === 'fermee' ? formaterHeureReouverture(pharmacie) : '';
    return '<span class="badge-statut-pharmacie" style="color:' + couleurPourStatut(statut) + '">'
        + '<i class="' + iconeClassePourStatut(statut) + '"></i> '
        + libellePourStatut(statut) + suffixe
        + '</span>';
}
