// ===================================================================
// DEMANDES DE RÉSERVATION — accepter / rejeter
// ===================================================================

const overlayRejet     = document.getElementById('admin-overlay-rejet');
const champMotifRejet  = document.getElementById('admin-motif-rejet');
const btnRejetAnnuler  = document.getElementById('admin-rejet-annuler');
const btnRejetConfirm  = document.getElementById('admin-rejet-confirmer');

let groupeEnCoursDeRejet = null;

function csrfToken() {
    return document.getElementById('csrf-token').value;
}

function envoyerDecision(groupe, action, motif, boutonOrigine) {

    if (boutonOrigine) {
        boutonOrigine.disabled = true;
    }

    fetch('../Dos-php/traiter_decision_reservation.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            csrf_token:      csrfToken(),
            groupe_demande:  groupe,
            action:          action,
            motif:           motif || ''
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.succes) {
            retirerCarte(groupe);
        } else {
            alert(data.message || 'Une erreur est survenue.');
            if (boutonOrigine) {
                boutonOrigine.disabled = false;
            }
        }
    })
    .catch(function() {
        alert('Erreur réseau. Veuillez réessayer.');
        if (boutonOrigine) {
            boutonOrigine.disabled = false;
        }
    });
}

function retirerCarte(groupe) {
    const carte = document.querySelector('.admin-carte-demande[data-groupe="' + groupe + '"]');
    if (carte) {
        carte.remove();
    }
    const compteur = document.querySelector('.admin-compteur-total');
    if (compteur) {
        const liste = document.querySelectorAll('.admin-carte-demande');
        compteur.textContent = liste.length + ' en attente';
    }
}

// ----- Accepter -----

document.querySelectorAll('.admin-btn-accepter').forEach(function(btn) {
    btn.addEventListener('click', function() {
        envoyerDecision(this.dataset.groupe, 'accepter', null, this);
    });
});

// ----- Rejeter : ouvre la modale de motif -----

document.querySelectorAll('.admin-btn-rejeter').forEach(function(btn) {
    btn.addEventListener('click', function() {
        groupeEnCoursDeRejet = this.dataset.groupe;
        champMotifRejet.value = '';
        overlayRejet.classList.add('actif');
    });
});

btnRejetAnnuler.addEventListener('click', function() {
    overlayRejet.classList.remove('actif');
    groupeEnCoursDeRejet = null;
});

overlayRejet.addEventListener('click', function(e) {
    if (e.target === overlayRejet) {
        overlayRejet.classList.remove('actif');
        groupeEnCoursDeRejet = null;
    }
});

btnRejetConfirm.addEventListener('click', function() {
    if (!groupeEnCoursDeRejet) return;

    const motif = champMotifRejet.value.trim();
    envoyerDecision(groupeEnCoursDeRejet, 'rejeter', motif, null);

    overlayRejet.classList.remove('actif');
    groupeEnCoursDeRejet = null;
});
