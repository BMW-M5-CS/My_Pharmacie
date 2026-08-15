// ===================================================================
// RÉSERVATION — gestion des produits, du panier et de la confirmation
// ===================================================================

// Échappe le texte avant de l'insérer dans du HTML, pour éviter qu'un nom de
// produit contenant du code HTML ne s'exécute dans le navigateur (XSS).
// Absente jusqu'ici de ce fichier : reservation.php ne charge aucun autre
// script qui la définirait globalement (contrairement à produit.php/carte.php).
function echapperHtml(texte) {

    const div = document.createElement('div');
    div.textContent = texte ?? '';
    return div.innerHTML;
}


const idPharmacie = document.body.dataset.idPharmacie;
const produitInit  = document.body.dataset.produitInit; // id_stock pré-sélectionné, peut être vide

const grilleProduits = document.getElementById('resa-grille-produits');
const nbProduitsEl   = document.getElementById('resa-nb-produits');
const rechercheInput = document.getElementById('resa-recherche');

const ticketLignes    = document.getElementById('resa-ticket-lignes');
const panierVideMsg   = document.getElementById('resa-panier-vide');
const totalArticlesEl = document.getElementById('resa-total-articles');
const btnConfirmer    = document.getElementById('resa-btn-confirmer');

const confirmOverlay = document.getElementById('resa-confirm-overlay');
const confirmCode    = document.getElementById('resa-confirm-code');
const confirmExpire  = document.getElementById('resa-confirm-expire');

// panier = { id_stock: { nom, forme, prix, qte, max } }
let panier = {};
let produitsDisponibles = [];


// ===================================================================
// 1. CHARGEMENT DES PRODUITS DE LA PHARMACIE
// ===================================================================

function chargerProduits() {

    fetch('../Dos-php/get_produit-pharmacie.php?id_pharmacie=' + idPharmacie)
        .then(function(response) { return response.json(); })
        .then(function(data) {

            produitsDisponibles = data.produits || [];
            nbProduitsEl.textContent = '(' + produitsDisponibles.length + ')';
            afficherProduits(produitsDisponibles);

            // Pré-sélection depuis modal pharmacie (ancien comportement)
            if (produitInit) {
                ajouterAuPanier(produitInit);
            }

            // Pré-remplissage depuis mes_reservations (nouveau)
            preRemplirPanierDepuisURL();
        })
        .catch(function() {
            grilleProduits.innerHTML = '<p class="resa-aucun-produit">Erreur lors du chargement des produits.</p>';
        });
}


function afficherProduits(liste) {

    grilleProduits.innerHTML = '';

    if (liste.length === 0) {
        grilleProduits.innerHTML = '<p class="resa-aucun-produit">Aucun produit ne correspond à votre recherche.</p>';
        return;
    }

    liste.forEach(function(produit) {

        const carte = document.createElement('div');
        carte.classList.add('resa-carte-produit');
        carte.dataset.idStock = produit.id_stock;

        const qteActuelle = panier[produit.id_stock] ? panier[produit.id_stock].qte : 0;
        const dejaMax     = qteActuelle >= produit.max_reservable;

        carte.innerHTML = `
            <div class="resa-produit-vignette"></div>
            <span class="resa-produit-nom">${echapperHtml(produit.nom_medicament)}</span>
            <span class="resa-produit-forme">${echapperHtml(produit.forme_pharmaceutique)}</span>
            <span class="resa-produit-prix">${Number(produit.prix_unitaire_fcfa).toLocaleString('fr-FR')} FCFA</span>
            <div class="resa-stepper">
                <button class="resa-moins" ${qteActuelle === 0 ? 'disabled' : ''}>−</button>
                <span class="resa-qte">${qteActuelle}</span>
                <button class="resa-plus" ${dejaMax ? 'disabled' : ''}>+</button>
            </div>
        `;

        if (produit.max_reservable === 0) {

            carte.classList.add('epuise');
            carte.querySelector('.resa-stepper').innerHTML = '<span class="resa-produit-rupture">Indisponible pour le moment</span>';

        } else {

            carte.querySelector('.resa-plus').addEventListener('click', function() {
                ajouterAuPanier(produit.id_stock);
            });

            carte.querySelector('.resa-moins').addEventListener('click', function() {
                retirerDuPanier(produit.id_stock);
            });
        }

        grilleProduits.appendChild(carte);
    });
}


// ===================================================================
// 2. RECHERCHE LOCALE PARMI LES PRODUITS CHARGÉS
// ===================================================================

rechercheInput.addEventListener('input', function() {

    const terme = this.value.toLowerCase();

    const filtres = produitsDisponibles.filter(function(p) {
        return p.nom_medicament.toLowerCase().includes(terme);
    });

    afficherProduits(filtres);
});


// ===================================================================
// 3. GESTION DU PANIER
// ===================================================================

function trouverProduit(idStock) {

    return produitsDisponibles.find(function(p) {
        return String(p.id_stock) === String(idStock);
    });
}


function ajouterAuPanier(idStock) {

    const produit = trouverProduit(idStock);
    if (!produit) return;

    if (!panier[idStock]) {
        panier[idStock] = {
            nom:  produit.nom_medicament,
            prix: produit.prix_unitaire_fcfa,
            qte:  0,
            max:  produit.max_reservable
        };
    }

    if (panier[idStock].qte >= panier[idStock].max) return;

    panier[idStock].qte += 1;
    rafraichirAffichage();
}


function retirerDuPanier(idStock) {

    if (!panier[idStock]) return;

    panier[idStock].qte -= 1;

    if (panier[idStock].qte <= 0) {
        delete panier[idStock];
    }

    rafraichirAffichage();
}


function rafraichirAffichage() {

    // ----- Met à jour les steppers sur les cartes produits visibles -----

    document.querySelectorAll('.resa-carte-produit').forEach(function(carte) {

        const idStock  = carte.dataset.idStock;
        const qteSpan  = carte.querySelector('.resa-qte');
        const btnMoins = carte.querySelector('.resa-moins');
        const btnPlus  = carte.querySelector('.resa-plus');

        if (!qteSpan) return;

        const qte = panier[idStock] ? panier[idStock].qte : 0;
        const max = panier[idStock] ? panier[idStock].max : (trouverProduit(idStock) || {}).max_reservable;

        qteSpan.textContent = qte;
        btnMoins.disabled   = qte === 0;
        btnPlus.disabled    = qte >= max;
    });


    // ----- Reconstruit le ticket -----

    const idsStock = Object.keys(panier);

    if (idsStock.length === 0) {

        ticketLignes.innerHTML = '';
        ticketLignes.appendChild(panierVideMsg);
        panierVideMsg.style.display = 'block';

    } else {

        ticketLignes.innerHTML = '';

        idsStock.forEach(function(idStock) {

            const item  = panier[idStock];
            const ligne = document.createElement('div');
            ligne.classList.add('resa-ligne-panier');

            ligne.innerHTML = `
                <div class="resa-ligne-info">
                    <span class="resa-ligne-nom">${echapperHtml(item.nom)}</span>
                    <span class="resa-ligne-prix">${Number(item.prix).toLocaleString('fr-FR')} FCFA</span>
                </div>
                <div class="resa-ligne-stepper">
                    <button class="resa-ligne-moins">−</button>
                    <span>${item.qte}</span>
                    <button class="resa-ligne-plus" ${item.qte >= item.max ? 'disabled' : ''}>+</button>
                </div>
            `;

            ligne.querySelector('.resa-ligne-plus').addEventListener('click', function() {
                ajouterAuPanier(idStock);
            });

            ligne.querySelector('.resa-ligne-moins').addEventListener('click', function() {
                retirerDuPanier(idStock);
            });

            ticketLignes.appendChild(ligne);
        });
    }


    // ----- Total d'articles + état du bouton confirmer -----

    const totalArticles = Object.values(panier).reduce(function(somme, item) {
        return somme + item.qte;
    }, 0);

    totalArticlesEl.textContent = totalArticles;
    btnConfirmer.disabled       = totalArticles === 0;
}


// ===================================================================
// 4. CONFIRMATION DE LA RÉSERVATION
// ===================================================================

btnConfirmer.addEventListener('click', function() {

    const idsStock = Object.keys(panier);
    if (idsStock.length === 0) return;

    const panierEnvoye = idsStock.map(function(idStock) {
        return { id_stock: idStock, quantite: panier[idStock].qte };
    });

    btnConfirmer.disabled  = true;
    btnConfirmer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Confirmation...';

    fetch('../Dos-php/traite_reservation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token:   document.getElementById('csrf-token').value,
            id_pharmacie: idPharmacie,
            produits:     panierEnvoye
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {

        if (data.succes) {

            confirmCode.textContent   = data.code_reservation;
            confirmExpire.textContent = 'Valable jusqu\'à ' + data.expire_at;
            confirmOverlay.classList.add('actif');
            panier = {};

        } else {

            alert(data.message || 'Certains produits ne sont plus disponibles en quantité suffisante. Le panier va être actualisé.');
            chargerProduits();
            panier = {};
            rafraichirAffichage();
        }
    })
    .catch(function() {
        alert('Erreur lors de la confirmation de la réservation. Veuillez réessayer.');
    })
    .finally(function() {
        btnConfirmer.disabled  = false;
        btnConfirmer.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Confirmer la réservation';
    });
});


// ===================================================================
// PRÉ-REMPLISSAGE DU PANIER DEPUIS L'URL (venant de mes_reservations)
// ===================================================================

function preRemplirPanierDepuisURL() {

    const params     = new URLSearchParams(window.location.search);
    const preselects = params.getAll('preselect[]');
    const qtes       = params.getAll('qte[]');

    if (preselects.length === 0) return;

    // Pour chaque id_stock reçu dans l'URL, on force la quantité dans le panier
    preselects.forEach(function(idStock, index) {

        const qte     = parseInt(qtes[index]) || 1;
        const produit = trouverProduit(idStock);

        if (!produit) return;

        // Ne pas dépasser le max_reservable réel
        const qtefinale = Math.min(qte, produit.max_reservable);
        if (qtefinale <= 0) return;

        panier[idStock] = {
            nom:  produit.nom_medicament,
            prix: produit.prix_unitaire_fcfa,
            qte:  qtefinale,
            max:  produit.max_reservable
        };
    });

    rafraichirAffichage();
}


chargerProduits();