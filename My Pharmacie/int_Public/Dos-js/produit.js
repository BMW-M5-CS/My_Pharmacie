const CARTE_PAR_PAGE = 12;
let carteVisibles = CARTE_PAR_PAGE;

const cartes      = document.querySelectorAll('.carte-produit');
const btnVoirPlus = document.querySelector('.btn-see_more');


// Échappe le texte avant de l'insérer dans du HTML, pour éviter qu'un nom de
// pharmacie contenant du code HTML ne s'exécute dans le navigateur (XSS)
function echapperHtml(texte) {

    const div = document.createElement('div');
    div.textContent = texte ?? '';
    return div.innerHTML;
}


function imagePlaceholderProduit(id) {
    return 'https://picsum.photos/seed/produit' + id + '/400/400';
}


// ===================================================================
// PAGINATION "VOIR PLUS"
// ===================================================================

function afficherCartes() {

    cartes.forEach(function (carte, index) {
        carte.style.display = index < carteVisibles ? 'flex' : 'none';
    });

    if (btnVoirPlus) {
        btnVoirPlus.style.display = carteVisibles >= cartes.length ? 'none' : 'block';
    }
}

afficherCartes();

if (btnVoirPlus) {
    btnVoirPlus.addEventListener('click', function () {
        carteVisibles += CARTE_PAR_PAGE;
        afficherCartes();
    });
}


// ===================================================================
// RECHERCHE LOCALE PARMI LES CARTES AFFICHÉES
// ===================================================================

const champRecherche = document.querySelector('.sreach_input');

if (champRecherche) {

    champRecherche.addEventListener('input', function () {

        const recherche = this.value.toLowerCase();

        if (recherche === '') {

            carteVisibles = CARTE_PAR_PAGE;
            afficherCartes();

        } else {

            if (btnVoirPlus) {
                btnVoirPlus.style.display = 'none';
            }

            cartes.forEach(function (carte) {
                const nom = carte.querySelector('.carte-produit-nom').textContent.toLowerCase();
                carte.style.display = nom.includes(recherche) ? 'flex' : 'none';
            });
        }
    });
}


// ===================================================================
// FENÊTRE DE VUE DU PRODUIT
// ===================================================================

const modalOuvrirProduit = document.getElementById('modal-produit');
const modalFermerProduit = document.getElementById('modal-fermer-produit');

// Liste (éventuellement triée par distance) des pharmacies qui ont le produit
// actuellement affiché dans le modal, utilisée pour le filtrage local de la recherche
let pharmaciesProduitActuelles = [];

document.querySelectorAll('.Voir-produit').forEach(function (btn) {

    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        ouvrirModalProduit(id);
    });
});


// Affiche (ou ré-affiche, triée) la liste des pharmacies qui ont le produit.
// Si les objets pharmacie ont un champ distanceKm (calculé une fois la position
// de l'utilisateur connue), la première de la liste reçoit le badge "La plus proche"
function rendrePharmaciesProduit(liste, conteneur) {

    conteneur.innerHTML = '';

    if (liste.length === 0) {

        conteneur.innerHTML = '<p class="modal-aucun">Aucune pharmacie, pour le moment, ne dispose de ce produit.</p>';
        return;
    }

    liste.forEach(function (pharmacie, index) {

        const aUneDistance = pharmacie.distanceKm !== undefined;

        const badgeProche = (index === 0 && aUneDistance)
            ? '<span class="badge-plus-proche"><i class="fa-solid fa-location-arrow"></i> La plus proche</span>'
            : '';

        const distanceHtml = aUneDistance
            ? '<span class="modal-pharmacie-distance"><i class="fa-solid fa-route"></i> ' + formaterDistance(pharmacie.distanceKm) + '</span>'
            : '';

        const item = document.createElement('div');
        item.classList.add('modal-pharmacie-item');
        item.innerHTML = `
            ${badgeProche}
            <span class="modal-pharmacie-nom">${echapperHtml(pharmacie.nom_pharmacie)}</span>
            ${distanceHtml}
            <button class="btn-voir-pharmacie" data-id="${pharmacie.id_pharmacie}">
                <i class="fa-solid fa-store"></i> Voir la pharmacie
            </button>
        `;
        conteneur.appendChild(item);
    });

    conteneur.querySelectorAll('.btn-voir-pharmacie').forEach(function (btn) {

        btn.addEventListener('click', function () {
            const idPharmacie = this.dataset.id;
            fermerModalProduit();
            ouvrirModalPharmacie(idPharmacie);
        });
    });
}


function ouvrirModalProduit(id) {

    fetch('../Dos-php/get_produit.php?id_produit=' + id)
        .then(function (response) { return response.json(); })
        .then(function (data) {

            document.getElementById('modal-nom-produit').textContent          = data.nom_medicament;
            document.getElementById('modal-nom_generique').textContent        = data.nom_generique ? 'Générique : ' + data.nom_generique : '';
            document.getElementById('modal-forme_pharmaceutique').textContent = 'Forme : ' + data.forme_pharmaceutique;
            document.getElementById('modal-dosage').textContent               = data.dosage ? 'Dosage : ' + data.dosage : '';
            document.getElementById('modal-conditionnement').textContent      = data.conditionnement ? 'Conditionnement : ' + data.conditionnement : '';
            document.getElementById('modal-description').textContent          = data.description;
            document.getElementById('modal-prix_unitaire_fcfa').textContent   = 'Prix : ' + data.prix_unitaire_fcfa + ' FCFA';

            const nomStickyProduitEl = document.getElementById('modal-nom-produit-sticky');
            if (nomStickyProduitEl) nomStickyProduitEl.textContent = data.nom_medicament;

            const imageModal = document.getElementById('modal-image-produit-img');

            if (imageModal) {
                imageModal.src = imagePlaceholderProduit(id);
                imageModal.alt = data.nom_medicament;
            }

            document.getElementById('modal-nb-pharmacies').textContent = '(' + data.pharmacies.length + ')';

            enregistrerConsultationHistorique(id);


            // ----- Recherche pharmacie : réinitialisation pour le nouveau produit -----

            const champRechercheProduitModal = document.getElementById('modal-recherche-pharmacie-input');
            if (champRechercheProduitModal) champRechercheProduitModal.value = '';

            const suggestionsProduitModal = document.getElementById('modal-suggestions-pharmacie');
            if (suggestionsProduitModal) {
                suggestionsProduitModal.classList.remove('actif');
                suggestionsProduitModal.innerHTML = '';
            }

            const viderProduitModal = document.getElementById('modal-recherche-pharmacie-vider');
            if (viderProduitModal) viderProduitModal.classList.remove('actif');


            // ----- Remplissage de la liste des pharmacies (triée par distance si possible) -----

            const listePharmacies = document.getElementById('modal-liste-pharmacie');

            pharmaciesProduitActuelles = data.pharmacies;
            rendrePharmaciesProduit(pharmaciesProduitActuelles, listePharmacies);

            // La géolocalisation est asynchrone : on affiche d'abord la liste telle
            // quelle (ordre alphabétique venant du serveur), puis on la re-trie et
            // on affiche la distance dès que la position de l'utilisateur est connue
            obtenirPositionUtilisateur(function (position) {

                if (!position) return;

                const pharmaciesAvecDistance = data.pharmacies.map(function (p) {
                    return Object.assign({}, p, {
                        distanceKm: calculerDistanceKm(position.lat, position.lng, parseFloat(p.latitude), parseFloat(p.longitude))
                    });
                }).sort(function (a, b) { return a.distanceKm - b.distanceKm; });

                pharmaciesProduitActuelles = pharmaciesAvecDistance;
                rendrePharmaciesProduit(pharmaciesProduitActuelles, listePharmacies);
            });

            modalOuvrirProduit.classList.add('actif');
            document.body.classList.add('modal-ouvert');
        })
        .catch(function () {
            alert('Erreur lors du chargement des données.');
        });
}

modalFermerProduit.addEventListener('click', fermerModalProduit);

modalOuvrirProduit.addEventListener('click', function (e) {
    if (e.target === modalOuvrirProduit) {
        fermerModalProduit();
    }
});


function fermerModalProduit() {

    modalOuvrirProduit.classList.remove('actif');
    document.body.classList.remove('modal-ouvert');
}


// ===================================================================
// BARRE STICKY (nom du produit + fermeture) EN HAUT DU MODAL
// ===================================================================

function construireBarreStickyProduit() {

    if (document.getElementById('modal-barre-sticky-produit')) return;

    const contenu = modalOuvrirProduit.querySelector('.modal-contenu-produit');
    if (!contenu) return;

    const barre = document.createElement('div');
    barre.id        = 'modal-barre-sticky-produit';
    barre.className = 'modal-barre-sticky-produit';

    const nom = document.createElement('span');
    nom.id        = 'modal-nom-produit-sticky';
    nom.className = 'modal-nom-produit-sticky';

    barre.appendChild(nom);
    barre.appendChild(modalFermerProduit); // déplace le bouton de fermeture existant (même id, même écouteur)

    contenu.insertBefore(barre, contenu.firstChild);
}


// ===================================================================
// RECHERCHE / AUTOCOMPLÉTION DES PHARMACIES À L'INTÉRIEUR DU MODAL PRODUIT
// (filtrage 100% local sur les pharmacies déjà chargées, sans nouvel appel serveur)
// ===================================================================

function construireBarreRechercheModalPharmacie() {

    if (document.getElementById('modal-recherche-pharmacie-wrapper')) return;

    const listePharmacies = document.getElementById('modal-liste-pharmacie');
    if (!listePharmacies) return;


    const wrapper = document.createElement('div');
    wrapper.id = 'modal-recherche-pharmacie-wrapper';
    wrapper.className = 'modal-recherche-pharmacie-wrapper';
    wrapper.innerHTML = `
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="modal-recherche-pharmacie-input" class="modal-recherche-pharmacie-input" placeholder="Chercher ta pharmacie (ex : ta pharmacie de quartier)" autocomplete="off">
        <div class="modal-suggestions-pharmacie" id="modal-suggestions-pharmacie"></div>
    `;

    const viderBtn = document.createElement('button');
    viderBtn.type      = 'button';
    viderBtn.id        = 'modal-recherche-pharmacie-vider';
    viderBtn.className = 'modal-recherche-pharmacie-vider';
    viderBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Réinitialiser la recherche';


    listePharmacies.parentNode.insertBefore(wrapper, listePharmacies);
    listePharmacies.parentNode.insertBefore(viderBtn, listePharmacies);


    document.getElementById('modal-recherche-pharmacie-input').addEventListener('input', function () {
        afficherSuggestionsPharmacieModal(this.value);
        filtrerPharmaciesModalProduit(this.value);
    });

    viderBtn.addEventListener('click', function () {
        document.getElementById('modal-recherche-pharmacie-input').value = '';
        afficherSuggestionsPharmacieModal('');
        filtrerPharmaciesModalProduit('');
    });
}


function filtrerPharmaciesModalProduit(recherche) {

    const q          = recherche.trim().toLowerCase();
    const listeConteneur = document.getElementById('modal-liste-pharmacie');
    const viderModal  = document.getElementById('modal-recherche-pharmacie-vider');

    if (q === '') {

        rendrePharmaciesProduit(pharmaciesProduitActuelles, listeConteneur);
        if (viderModal) viderModal.classList.remove('actif');

    } else {

        const resultats = pharmaciesProduitActuelles.filter(function (p) {
            return p.nom_pharmacie.toLowerCase().includes(q);
        });

        rendrePharmaciesProduit(resultats, listeConteneur);
        if (viderModal) viderModal.classList.add('actif');
    }
}


function afficherSuggestionsPharmacieModal(recherche) {

    const conteneur = document.getElementById('modal-suggestions-pharmacie');
    if (!conteneur) return;

    const q = recherche.trim().toLowerCase();

    if (q === '') {
        conteneur.classList.remove('actif');
        conteneur.innerHTML = '';
        return;
    }

    const correspondances = pharmaciesProduitActuelles
        .filter(function (p) { return p.nom_pharmacie.toLowerCase().includes(q); })
        .slice(0, 6);

    if (correspondances.length === 0) {
        conteneur.classList.remove('actif');
        conteneur.innerHTML = '';
        return;
    }

    conteneur.innerHTML = correspondances.map(function (p) {
        return '<div class="modal-suggestion-pharmacie-item" data-id-pharmacie="' + p.id_pharmacie + '">' + echapperHtml(p.nom_pharmacie) + '</div>';
    }).join('');

    conteneur.classList.add('actif');

    conteneur.querySelectorAll('.modal-suggestion-pharmacie-item').forEach(function (item) {

        item.addEventListener('click', function () {

            const idPharmacie = this.dataset.idPharmacie;
            const pharmacie    = pharmaciesProduitActuelles.find(function (p) {
                return String(p.id_pharmacie) === String(idPharmacie);
            });

            if (pharmacie) {
                document.getElementById('modal-recherche-pharmacie-input').value = pharmacie.nom_pharmacie;
                rendrePharmaciesProduit([pharmacie], document.getElementById('modal-liste-pharmacie'));

                const viderModal = document.getElementById('modal-recherche-pharmacie-vider');
                if (viderModal) viderModal.classList.add('actif');
            }

            conteneur.classList.remove('actif');
            conteneur.innerHTML = '';
        });
    });
}


// Ferme la liste de suggestions au clic en dehors du champ de recherche
document.addEventListener('click', function (e) {

    const wrapper = document.getElementById('modal-recherche-pharmacie-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {

        const suggestions = document.getElementById('modal-suggestions-pharmacie');
        if (suggestions) suggestions.classList.remove('actif');
    }
});


// Mise en place, une seule fois, de la barre sticky et de la barre de recherche
construireBarreStickyProduit();
construireBarreRechercheModalPharmacie();


// ===================================================================
// HISTORIQUE DE CONSULTATION
// ===================================================================

// Signale au serveur qu'un produit vient d'être consulté (pour l'historique).
// Silencieux si le visiteur n'est pas connecté : c'est le serveur qui décide
// quoi faire de la requête, on ne bloque jamais l'affichage du produit pour ça.
function enregistrerConsultationHistorique(idProduit) {

    fetch('../Dos-php/enregistrer_historique.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_produit: idProduit })
    }).catch(function () {
        // Échec silencieux : l'historique n'est jamais bloquant pour la navigation
    });
}


// ===================================================================
// OUVERTURE DIRECTE D'UN PRODUIT VIA ?ouvrir=ID (venant de la page historique)
// ===================================================================

(function () {

    const parametres     = new URLSearchParams(window.location.search);
    const idProduitOuvrir = parametres.get('ouvrir');

    if (idProduitOuvrir) {
        ouvrirModalProduit(idProduitOuvrir);
    }

})();


// ===================================================================
// BOUTON "REMONTER" DANS LA ZONE PRODUITS
// ===================================================================

const produitsConteneur = document.getElementById('produits-conteneur');
const btnRemonter       = document.getElementById('btn-remonter');

if (produitsConteneur && btnRemonter) {

    produitsConteneur.addEventListener('scroll', function () {

        if (produitsConteneur.scrollTop > 400) {
            btnRemonter.classList.add('visible');
        } else {
            btnRemonter.classList.remove('visible');
        }
    });

    btnRemonter.addEventListener('click', function () {
        produitsConteneur.scrollTo({ top: 0, behavior: 'smooth' });
    });
}


// L'auto-complétion de la recherche produit est gérée par autocomplete-produit.js,
// inclus juste avant ce fichier dans produit.php