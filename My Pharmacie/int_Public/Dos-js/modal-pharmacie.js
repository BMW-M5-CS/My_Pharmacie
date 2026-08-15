// ======================= Modal Pharmacie ======================

// Échappe le texte avant de l'insérer dans du HTML, pour éviter qu'un nom de
// produit/pharmacie contenant du code HTML ne s'exécute dans le navigateur (XSS)
function echapperHtml(texte) {

    const div = document.createElement('div');
    div.textContent = texte ?? '';
    return div.innerHTML;
}


const modalOverlay = document.getElementById('modal-pharmacie');
const modalFermer  = document.getElementById('modal-fermer-pharmacie');

// Produits de la pharmacie actuellement affichée dans le modal (chargés une
// seule fois à l'ouverture), utilisés pour le filtrage local de la recherche
let produitsPharmacieActuelle = [];
let idPharmacieModalActuelle  = null;


// ===================================================================
// OUVERTURE DE LA FENÊTRE DE VUE DE LA PHARMACIE
// ===================================================================

document.querySelectorAll('.voir').forEach(function(btn) {

    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        ouvrirModalPharmacie(id);
    });
});


function ouvrirModalPharmacie(id) {

    fetch('../Dos-php/get_pharmacie.php?id_pharmacie=' + id)
        .then(function(response) { return response.json(); })
        .then(function(data) {

            // ----- Ajout des infos -----

            document.getElementById('modal-nom-pharmacie').textContent = data.nom_pharmacie;
            document.getElementById('modal-adresse').textContent       = data.adresse;
            document.getElementById('modal-ville').textContent         = data.ville;
            document.getElementById('modal-commune').textContent       = data.commune;
            document.getElementById('modal-quartier').textContent      = data.quartier;
            document.getElementById('modal-horaire').textContent       = data.heure_ouverture + '-' + data.heure_fermeture;
            document.getElementById('modal-telephone').textContent     = data.telephone_pharmacie;

            const nomStickyEl = document.getElementById('modal-nom-pharmacie-sticky');
            if (nomStickyEl) nomStickyEl.textContent = data.nom_pharmacie;

            const gardeEl = document.getElementById('modal-garde');

            if (data.statut_calcule === 'garde') {
                gardeEl.innerHTML = '<span class="modal-garde-badge garde">Pharmacie de garde</span>';
            } else if (data.statut_calcule === 'ouverte') {
                gardeEl.innerHTML = '<span class="modal-garde-badge">Ouverte</span>';
            } else {
                gardeEl.innerHTML = '<span class="modal-garde-badge fermee">Fermée</span>';
            }

            document.getElementById('modal-nb-produits').textContent = '(' + data.produits.length + ')';


            // ----- Assurances acceptées -----

            const assurancesEl = document.getElementById('modal-assurances-pharmacie');

            if (assurancesEl) {

                if (data.assurances && data.assurances.length > 0) {

                    assurancesEl.innerHTML = '<span class="modal-assurances-titre"><i class="fa-solid fa-notes-medical"></i> Assurances acceptées :</span>'
                        + data.assurances.map(function (a) {
                            return '<span class="modal-assurance-item">'
                                + '<img src="../Dos-img/' + encodeURIComponent(a.logo_assurance) + '" alt="' + echapperHtml(a.nom_assurance) + '">'
                                + echapperHtml(a.nom_assurance)
                                + '</span>';
                        }).join('');

                } else {

                    assurancesEl.innerHTML = '<span class="modal-assurances-titre"><i class="fa-solid fa-notes-medical"></i></span>'
                        + '<span class="modal-assurance-aucune">Aucune assurance acceptée</span>';
                }
            }


            // ----- Résumé notation -----

            const notationEl = document.getElementById('modal-notation');

            if (data.nombre_avis > 0) {
                notationEl.innerHTML = `
                    <span class="etoiles">${data.etoiles_html}</span>
                    <span class="note-chiffre">${data.note_moyenne.toFixed(1)}</span>
                    <span class="nb-avis">(${data.nombre_avis} avis)</span>
                    ${data.recommandee ? '<span class="badge-recommandee-mini"><i class="fa-solid fa-award"></i> Recommandée</span>' : ''}
                `;
            } else {
                notationEl.innerHTML = '<span class="pas-avis">Pas encore d\'avis</span>';
            }


            // ----- Déclaration des données de la carte et affichage -----

            const lat = parseFloat(data.latitude);
            const lng = parseFloat(data.longitude);

            if (window._modalMap) {
                window._modalMap.remove();
                window._modalMap = null;
            }

            setTimeout(function() {

                window._modalMap = L.map('modal-carte').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(window._modalMap);

                L.marker([lat, lng])
                    .addTo(window._modalMap)
                    .bindPopup(echapperHtml(data.nom_pharmacie))
                    .openPopup();

            }, 100);


            // ----- Recherche produit : réinitialisation pour la nouvelle pharmacie -----

            produitsPharmacieActuelle = data.produits;
            idPharmacieModalActuelle  = data.id_pharmacie;

            const champRechercheModal = document.getElementById('modal-recherche-produit-input');
            if (champRechercheModal) champRechercheModal.value = '';

            const suggestionsModal = document.getElementById('modal-suggestions-produit');
            if (suggestionsModal) {
                suggestionsModal.classList.remove('actif');
                suggestionsModal.innerHTML = '';
            }

            const viderModal = document.getElementById('modal-recherche-produit-vider');
            if (viderModal) viderModal.classList.remove('actif');


            // ----- Remplissage de la liste des produits -----

            afficherProduitsModal(produitsPharmacieActuelle, idPharmacieModalActuelle);


            modalOverlay.classList.add('actif');
            document.body.classList.add('fenetre-pharmacie-ouvert');
        })
        .catch(function() {
            alert('Erreur lors du chargement des données');
        });
}


// ===================================================================
// AFFICHAGE (ET RÉ-AFFICHAGE FILTRÉ) DE LA LISTE DE PRODUITS
// ===================================================================

function afficherProduitsModal(liste, idPharmacie) {

    const listeProduit = document.getElementById('modal-liste-produits');
    listeProduit.innerHTML = '';

    if (liste.length === 0) {

        listeProduit.innerHTML = '<p class="modal-aucun"> Aucun produit ne correspond à ta recherche.</p>';

    } else {

        liste.forEach(function(produit) {

            const item = document.createElement('div');
            item.classList.add('modal-produit-item');
            item.innerHTML = `
                <div class="modal-produit-image"></div>
                <span class="modal-produit-nom">${echapperHtml(produit.nom_medicament)}</span>
                <span class="modal-produit-forme">${echapperHtml(produit.forme_pharmaceutique)}</span>
                <span class="modal-produit-prix">${produit.prix_unitaire_fcfa} FCFA</span>
                <button class="btn-reserver-produit" data-id-stock="${produit.id_stock}" data-id-pharmacie="${idPharmacie ?? ''}">
                   <i class="fa-solid fa-calendar-check"></i> Réserver
                </button>
            `;
            listeProduit.appendChild(item);
        });
    }


    // ----- Bouton de réservation + contrôle de la connexion -----

    document.querySelectorAll('.btn-reserver-produit').forEach(function(btn) {

        btn.addEventListener('click', function() {

            const connecte    = document.body.dataset.connecte;
            const idStock     = this.dataset.idStock;
            const idPharmacie = this.dataset.idPharmacie;

            if (connecte === '1') {
                window.location.href = '../../int_Client/Dos-page/reservation.php?id_pharmacie=' + idPharmacie + '&produit=' + idStock;
            } else {
                const destination = '../../int_Client/Dos-page/reservation.php?id_pharmacie=' + idPharmacie + '&produit=' + idStock;
                window.location.href = 'conex.php?redirect=' + encodeURIComponent(destination);
            }
        });
    });
}


// ===================================================================
// RECHERCHE / AUTOCOMPLÉTION DES PRODUITS À L'INTÉRIEUR DU MODAL
// (filtrage 100% local sur les produits déjà chargés, sans nouvel appel serveur)
// ===================================================================

function construireBarreRechercheModal() {

    if (document.getElementById('modal-recherche-produit-wrapper')) return;

    const listeProduits = document.getElementById('modal-liste-produits');
    if (!listeProduits) return;


    const wrapper = document.createElement('div');
    wrapper.id = 'modal-recherche-produit-wrapper';
    wrapper.className = 'modal-recherche-produit-wrapper';
    wrapper.innerHTML = `
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="modal-recherche-produit-input" class="modal-recherche-produit-input" placeholder="Rechercher un produit de cette pharmacie" autocomplete="off">
        <div class="modal-suggestions-produit" id="modal-suggestions-produit"></div>
    `;

    const viderBtn = document.createElement('button');
    viderBtn.type      = 'button';
    viderBtn.id        = 'modal-recherche-produit-vider';
    viderBtn.className = 'modal-recherche-produit-vider';
    viderBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Réinitialiser la recherche';


    listeProduits.parentNode.insertBefore(wrapper, listeProduits);
    listeProduits.parentNode.insertBefore(viderBtn, listeProduits);


    document.getElementById('modal-recherche-produit-input').addEventListener('input', function() {
        afficherSuggestionsModal(this.value);
        filtrerProduitsModal(this.value);
    });

    viderBtn.addEventListener('click', function() {
        document.getElementById('modal-recherche-produit-input').value = '';
        afficherSuggestionsModal('');
        filtrerProduitsModal('');
    });
}


function filtrerProduitsModal(recherche) {

    const q = recherche.trim().toLowerCase();
    const viderModal = document.getElementById('modal-recherche-produit-vider');

    if (q === '') {

        afficherProduitsModal(produitsPharmacieActuelle, idPharmacieModalActuelle);
        if (viderModal) viderModal.classList.remove('actif');

    } else {

        const resultats = produitsPharmacieActuelle.filter(function(p) {
            return p.nom_medicament.toLowerCase().includes(q);
        });

        afficherProduitsModal(resultats, idPharmacieModalActuelle);
        if (viderModal) viderModal.classList.add('actif');
    }
}


function afficherSuggestionsModal(recherche) {

    const conteneur = document.getElementById('modal-suggestions-produit');
    if (!conteneur) return;

    const q = recherche.trim().toLowerCase();

    if (q === '') {
        conteneur.classList.remove('actif');
        conteneur.innerHTML = '';
        return;
    }

    const correspondances = produitsPharmacieActuelle
        .filter(function(p) { return p.nom_medicament.toLowerCase().includes(q); })
        .slice(0, 6);

    if (correspondances.length === 0) {
        conteneur.classList.remove('actif');
        conteneur.innerHTML = '';
        return;
    }

    conteneur.innerHTML = correspondances.map(function(p) {
        return '<div class="modal-suggestion-produit-item" data-id-stock="' + p.id_stock + '">' + echapperHtml(p.nom_medicament) + '</div>';
    }).join('');

    conteneur.classList.add('actif');

    conteneur.querySelectorAll('.modal-suggestion-produit-item').forEach(function(item) {

        item.addEventListener('click', function() {

            const idStock = this.dataset.idStock;
            const produit = produitsPharmacieActuelle.find(function(p) {
                return String(p.id_stock) === String(idStock);
            });

            if (produit) {
                document.getElementById('modal-recherche-produit-input').value = produit.nom_medicament;
                afficherProduitsModal([produit], idPharmacieModalActuelle);

                const viderModal = document.getElementById('modal-recherche-produit-vider');
                if (viderModal) viderModal.classList.add('actif');
            }

            conteneur.classList.remove('actif');
            conteneur.innerHTML = '';
        });
    });
}


// Ferme la liste de suggestions au clic en dehors du champ de recherche
document.addEventListener('click', function(e) {

    const wrapper = document.getElementById('modal-recherche-produit-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {

        const suggestions = document.getElementById('modal-suggestions-produit');
        if (suggestions) suggestions.classList.remove('actif');
    }
});


// ===================================================================
// BARRE STICKY (nom de la pharmacie + fermeture) EN HAUT DU MODAL
// ===================================================================

function construireBarreStickyModal() {

    if (document.getElementById('modal-barre-sticky-pharmacie')) return;

    const contenu = modalOverlay.querySelector('.modal-contenu-pharmacie');
    if (!contenu) return;

    const barre = document.createElement('div');
    barre.id        = 'modal-barre-sticky-pharmacie';
    barre.className = 'modal-barre-sticky-pharmacie';

    const nom = document.createElement('span');
    nom.id        = 'modal-nom-pharmacie-sticky';
    nom.className = 'modal-nom-pharmacie-sticky';

    barre.appendChild(nom);
    barre.appendChild(modalFermer); // déplace le bouton de fermeture existant (même id, même écouteur)

    contenu.insertBefore(barre, contenu.firstChild);
}


// Mise en place, une seule fois, de la barre sticky et de la barre de recherche
construireBarreStickyModal();
construireBarreRechercheModal();


// ===================================================================
// FERMETURE DU MODAL
// ===================================================================

modalFermer.addEventListener('click', fermerModal);

modalOverlay.addEventListener('click', function(e) {
    if (e.target === modalOverlay) {
        fermerModal();
    }
});


function fermerModal() {

    modalOverlay.classList.remove('actif');
    document.body.classList.remove('fenetre-pharmacie-ouvert');

    if (window._modalMap) {
        window._modalMap.remove();
        window._modalMap = null;
    }
}