const CARTE_PAR_PAGE = 12;
let carteVisibles = CARTE_PAR_PAGE;

const cartes      = document.querySelectorAll('.carte-produit');
const btnVoirPlus = document.querySelector('.btn-see_more');


function imagePlaceholderProduit(id) {
    return 'https://picsum.photos/seed/produit' + id + '/400/400';
}


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


// ----------------------------------- fenêtre de vue du produit -------------------------------

const modalOuvrirProduit = document.getElementById('modal-produit');
const modalFermerProduit = document.getElementById('modal-fermer-produit');


document.querySelectorAll('.Voir-produit').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        ouvrirModalProduit(id);
    });
});


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

            const imageModal = document.getElementById('modal-image-produit-img');
            if (imageModal) {
                imageModal.src = imagePlaceholderProduit(id);
                imageModal.alt = data.nom_medicament;
            }

            document.getElementById('modal-nb-pharmacies').textContent = '(' + data.pharmacies.length + ')';

            const listePharmacies = document.getElementById('modal-liste-pharmacie');
            listePharmacies.innerHTML = '';

            if (data.pharmacies.length === 0) {
                listePharmacies.innerHTML = '<p class="modal-aucun">Aucune pharmacie, pour le moment, ne dispose de ce produit.</p>';
            } else {
                data.pharmacies.forEach(function (pharmacie) {
                    const item = document.createElement('div');
                    item.classList.add('modal-pharmacie-item');
                    item.innerHTML = `
                        <span class="modal-pharmacie-nom">${pharmacie.nom_pharmacie}</span>
                        <button class="btn-voir-pharmacie" data-id="${pharmacie.id_pharmacie}">
                            <i class="fa-solid fa-store"></i> Voir la pharmacie
                        </button>
                    `;
                    listePharmacies.appendChild(item);
                });
            }

            document.querySelectorAll('.btn-voir-pharmacie').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const idPharmacie = this.dataset.id;
                    fermerModalProduit();
                    ouvrirModalPharmacie(idPharmacie);
                });
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

// ----------------------------------- bouton "remonter" dans la zone produits -------------------------------

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

// ----------------------------- Auto-complétion recherche pharmacie -----------------------------------

const inputRecherche    = document.getElementById('champ-recherche-input');
const suggestionsListe = document.getElementById('suggestions-liste');
let timeoutSuggestions = null;

if (inputRecherche && suggestionsListe){

    inputRecherche.addEventListener('input', function() {

        const q = this.value.trim();

        clearTimeout(timeoutSuggestions);

        if(q.length < 2){
            suggestionsListe.innerHTML = '';
            suggestionsListe.classList.remove('active');
            return;
        }

        timeoutSuggestions = setTimeout(function() {

            fetch('../Dos-php/get_suggestions_produit.php?q=' + encodeURIComponent(q))
                .then(function (response) { return response.json(); })
                .then(function (resultats) {

                    suggestionsListe.innerHTML = '';

                    if(resultats.length === 0){
                        suggestionsListe.classList.remove('active');
                        return;
                    }

                    resultats.forEach(function (nom) {
                        const item = document.createElement('div');
                        item.classList.add('suggestion-item');
                        item.textContent = nom;

                        item.addEventListener('click', function () {
                            inputRecherche.value = nom;
                            suggestionsListe.innerHTML = '';
                            suggestionsListe.classList.remove('active');
                            inputRecherche.closest('form').submit();
                        });

                        suggestionsListe.appendChild(item);
                    });

                    suggestionsListe.classList.add('active');
                }) 

                .catch(function () {
                    suggestionsListe.innerHTML = '';
                    suggestionsListe.classList.remove('active');
                });

        }, 250);

    });

    document.addEventListener('click', function (e) {
        if (!inputRecherche.contains(e.target) && !suggestionsListe.contains(e.target)) {
            suggestionsListe.innerHTML = '';
            suggestionsListe.classList.remove('active');
        }
    });

}
