// ===================================================================
// MES RÉSERVATIONS — filtres, annulation, renouvellement
// ===================================================================

// ===================================================================
// SECTION 1 — VARIABLES GLOBALES
// ===================================================================

const overlayAnnulation    = document.getElementById('mresa-overlay');
const modaleCodeAnnulation = document.getElementById('mresa-modale-code');
const btnNon               = document.getElementById('mresa-modale-non');
const btnOui               = document.getElementById('mresa-modale-oui');
let identifiantAnnulationEnCours = null;
let carteAnnulationEnCours       = null;

const overlaySucces    = document.getElementById('mresa-overlay-succes');
const btnFermerSucces  = document.getElementById('mresa-succes-fermer');

const overlayPartiel   = document.getElementById('mresa-overlay-partiel');
const btnPrendreReste  = document.getElementById('mresa-partiel-prendre');
const btnAbandonner    = document.getElementById('mresa-partiel-abandonner');
const btnModifier      = document.getElementById('mresa-partiel-modifier');
const btnRefaire       = document.getElementById('mresa-partiel-refaire');

let donneesPartielEnCours = null;

// Un groupe de réservations est identifié soit par son code (une fois
// confirmé par le pharmacien), soit par son groupe_demande (tant que la
// demande n'a pas encore de code) — cette fonction choisit le bon.
function identifiantDuBouton(btn) {
    return btn.dataset.code || btn.dataset.groupe;
}

// ===================================================================
// SECTION 2 — FILTRES
// ===================================================================

document.querySelectorAll('.filtre-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filtre-btn').forEach(function(b) {
            b.classList.remove('actif');
        });
        this.classList.add('actif');
        const filtre = this.dataset.filtre;
        document.querySelectorAll('.mresa-carte').forEach(function(carte) {
            if (filtre === 'tous' || carte.dataset.statut === filtre) {
                carte.style.display = 'block';
            } else {
                carte.style.display = 'none';
            }
        });
    });
});

// ===================================================================
// SECTION 3 — ANNULATION
// ===================================================================

document.querySelectorAll('.mresa-btn-annuler').forEach(function(btn) {
    btn.addEventListener('click', function() {
        identifiantAnnulationEnCours = identifiantDuBouton(this);
        carteAnnulationEnCours       = this.closest('.mresa-carte');
        modaleCodeAnnulation.textContent = this.dataset.code || 'cette demande';
        overlayAnnulation.classList.add('actif');
    });
});

btnNon.addEventListener('click', function() {
    fermerModaleAnnulation();
});

overlayAnnulation.addEventListener('click', function(e) {
    if (e.target === overlayAnnulation) fermerModaleAnnulation();
});

function fermerModaleAnnulation() {
    overlayAnnulation.classList.remove('actif');
    identifiantAnnulationEnCours = null;
    carteAnnulationEnCours       = null;
}

btnOui.addEventListener('click', function() {
    if (!identifiantAnnulationEnCours) return;

    btnOui.disabled    = true;
    btnOui.textContent = 'Annulation...';

    fetch('../Dos-php/annuler_reservation.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            csrf_token: document.getElementById('csrf-token').value,
            identifiant: identifiantAnnulationEnCours
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.succes) {
            mettreAJourCarteAnnulee(identifiantAnnulationEnCours);
            mettreAJourCompteurs();
        } else {
            alert(data.message || 'Une erreur est survenue.');
        }
    })
    .catch(function() {
        alert('Erreur réseau. Veuillez réessayer.');
    })
    .finally(function() {
        btnOui.disabled    = false;
        btnOui.textContent = 'Oui, annuler';
        fermerModaleAnnulation();
    });
});

function mettreAJourCarteAnnulee(identifiant) {
    const btn = document.querySelector(
        '.mresa-btn-annuler[data-code="' + identifiant + '"], .mresa-btn-annuler[data-groupe="' + identifiant + '"]'
    );
    if (!btn) return;
    const carte = btn.closest('.mresa-carte');
    if (!carte) return;

    const badge = carte.querySelector('.badge');
    if (badge) {
        badge.className   = 'badge badge-annule';
        badge.textContent = 'Annulée';
    }
    const expireInfo = carte.querySelector('.mresa-expire-info');
    if (expireInfo) expireInfo.remove();
    btn.remove();
    carte.dataset.statut = 'annulee';
}

// ===================================================================
// SECTION 4 — RENOUVELLEMENT
// ===================================================================

document.querySelectorAll('.mresa-btn-renouveler').forEach(function(btn) {
    btn.addEventListener('click', function() {
        lancerRenouvellement(identifiantDuBouton(this), null);
    });
});

function lancerRenouvellement(identifiant, produitsChoisis) {
    const corps = { identifiant: identifiant, csrf_token: document.getElementById('csrf-token').value };
    if (produitsChoisis !== null) {
        corps.produits_choisis = produitsChoisis;
    }

    fetch('../Dos-php/renouveler_reservation.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(corps)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        traiterReponseRenouvellement(data, identifiant);
    })
    .catch(function() {
        alert('Erreur réseau. Veuillez réessayer.');
    });
}

function traiterReponseRenouvellement(data, identifiant) {
    if (!data.succes && data.cas === 'impossible') {
        alert(data.message);
        return;
    }
    if (data.succes && data.cas === 'ok') {
        afficherModaleSucces(identifiant);
        return;
    }
    if (data.succes && data.cas === 'partiel') {
        donneesPartielEnCours = {
            identifiant_ancien: identifiant,
            id_pharmacie:       data.id_pharmacie,
            produits_ok:        data.produits_ok,
            produits_problemes: data.produits_problemes
        };
        afficherModalePartielle(data.produits_ok, data.produits_problemes);
        return;
    }
}

// ===================================================================
// SECTION 5 — MODALE SUCCÈS
// ===================================================================
// Le renouvellement crée une nouvelle DEMANDE, pas une réservation confirmée
// tout de suite : pas de code à afficher ici, juste une confirmation d'envoi.

function afficherModaleSucces(identifiantAncien) {
    overlaySucces.classList.add('actif');
    mettreAJourCarteRenouvele(identifiantAncien);
    mettreAJourCompteurs();
}

btnFermerSucces.addEventListener('click', function() {
    overlaySucces.classList.remove('actif');
});

overlaySucces.addEventListener('click', function(e) {
    if (e.target === overlaySucces) overlaySucces.classList.remove('actif');
});

function mettreAJourCarteRenouvele(identifiantAncien) {

    const btn = document.querySelector(
        '.mresa-btn-renouveler[data-code="' + identifiantAncien + '"], .mresa-btn-renouveler[data-groupe="' + identifiantAncien + '"]'
    );
    if (!btn) return;

    const carte = btn.closest('.mresa-carte');
    if (!carte) return;


    // ----- Mise à jour du badge de statut -----

    const badge = carte.querySelector('.badge');
    if (badge) {
        badge.className   = 'badge badge-renouvele';
        badge.textContent = 'Renouvelée';
    }


    // ----- Ajout de la ligne d'info -----

    const dates = carte.querySelector('.mresa-dates');
    if (dates) {
        const ligne = document.createElement('span');
        ligne.className = 'mresa-date-ligne mresa-renouvele-info';
        ligne.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Nouvelle demande envoyée, en attente de validation par la pharmacie';
        dates.appendChild(ligne);
    }

    btn.remove();
    carte.dataset.statut = 'renouvele';
}

// ===================================================================
// SECTION 6 — MODALE PARTIELLE (4 CHOIX)
// ===================================================================

function afficherModalePartielle(produitsOk, produitsProblemes) {
    const liste = document.getElementById('mresa-partiel-liste');
    liste.innerHTML = '';

    produitsProblemes.forEach(function(produit) {
        const ligne = document.createElement('div');
        ligne.className = 'mresa-partiel-produit';

        let message = '';
        if (produit.disponible === 0) {
            message = 'Plus disponible en stock';
        } else {
            message = 'Vous aviez demandé ' + produit.demande
                    + ', il n\'en reste que ' + produit.disponible;
        }

        ligne.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>'
            + '<span class="mresa-partiel-nom">'
            + produit.nom + ' <em>' + produit.forme + '</em>'
            + '</span>'
            + '<span class="mresa-partiel-msg">' + message + '</span>';

        liste.appendChild(ligne);
    });

    // Désactiver "Prendre le reste" si tout est épuisé
    const auMoinsUnDispo = produitsOk.length > 0
        || produitsProblemes.some(function(p) { return p.disponible > 0; });
    btnPrendreReste.disabled = !auMoinsUnDispo;

    overlayPartiel.classList.add('actif');
}

// Fermer la modale partielle (Abandonner)
btnAbandonner.addEventListener('click', function() {
    overlayPartiel.classList.remove('actif');
    donneesPartielEnCours = null;
});

overlayPartiel.addEventListener('click', function(e) {
    if (e.target === overlayPartiel) {
        overlayPartiel.classList.remove('actif');
        donneesPartielEnCours = null;
    }
});

// Choix 1 — "Prendre le reste"
btnPrendreReste.addEventListener('click', function() {
    if (!donneesPartielEnCours) return;

    const listeFinale = [];

    donneesPartielEnCours.produits_ok.forEach(function(p) {
        listeFinale.push({ id_stock: p.id_stock, quantite: p.quantite });
    });

    donneesPartielEnCours.produits_problemes.forEach(function(p) {
        if (p.disponible > 0) {
            listeFinale.push({ id_stock: p.id_stock, quantite: p.disponible });
        }
    });

    overlayPartiel.classList.remove('actif');
    lancerRenouvellement(donneesPartielEnCours.identifiant_ancien, listeFinale);
    donneesPartielEnCours = null;
});

// Choix 2 — "Modifier la réservation"
btnModifier.addEventListener('click', function() {
    if (!donneesPartielEnCours) return;

    const params = new URLSearchParams();
    params.append('id_pharmacie', donneesPartielEnCours.id_pharmacie);

    donneesPartielEnCours.produits_ok.forEach(function(p) {
        params.append('preselect[]', p.id_stock);
        params.append('qte[]', p.quantite);
    });

    donneesPartielEnCours.produits_problemes.forEach(function(p) {
        if (p.disponible > 0) {
            params.append('preselect[]', p.id_stock);
            params.append('qte[]', p.disponible);
        }
    });

    overlayPartiel.classList.remove('actif');
    donneesPartielEnCours = null;

    window.location.href = 'reservation.php?' + params.toString();
});

// Choix 3 — "Refaire la composition complète"
btnRefaire.addEventListener('click', function() {
    if (!donneesPartielEnCours) return;

    const params = new URLSearchParams();
    params.append('id_pharmacie', donneesPartielEnCours.id_pharmacie);

    donneesPartielEnCours.produits_ok.forEach(function(p) {
        params.append('preselect[]', p.id_stock);
        params.append('qte[]', p.quantite);
    });

    donneesPartielEnCours.produits_problemes.forEach(function(p) {
        if (p.disponible > 0) {
            params.append('preselect[]', p.id_stock);
            params.append('qte[]', p.disponible);
        }
    });

    overlayPartiel.classList.remove('actif');
    donneesPartielEnCours = null;

    window.location.href = 'reservation.php?' + params.toString();
});

// ===================================================================
// SECTION 7 — MISE À JOUR DES COMPTEURS
// ===================================================================

function mettreAJourCompteurs() {
    const compteurs = { tous: 0, demandee: 0, confirmee: 0, rejetee: 0, annulee: 0, renouvele: 0, expiree: 0 };

    document.querySelectorAll('.mresa-carte').forEach(function(carte) {
        const statut = carte.dataset.statut;
        compteurs.tous++;
        if (compteurs[statut] !== undefined) {
            compteurs[statut]++;
        }
    });

    document.querySelectorAll('.filtre-btn').forEach(function(btn) {
        const filtre = btn.dataset.filtre;
        const span   = btn.querySelector('.filtre-compteur');
        if (span && compteurs[filtre] !== undefined) {
            span.textContent = compteurs[filtre];
        }
    });
}
