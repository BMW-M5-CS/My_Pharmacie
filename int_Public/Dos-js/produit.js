const CARTE_PAR_PAGE = 12;
let carteVisibles = CARTE_PAR_PAGE;

const carte       = document.querySelectorAll('.produit');
const btnVoirPlus = document.querySelector('.btn-see_more');

function afficherCartes() {
    carte.forEach(function(map, index) {
        if (index < carteVisibles) {
            map.style.display = 'flex';
        } else {
            map.style.display = 'none';
        }
    });

    if (carteVisibles >= carte.length) {
        btnVoirPlus.style.display = 'none';
    } else {
        btnVoirPlus.style.display = 'block';
    }
}

afficherCartes();

btnVoirPlus.addEventListener('click', function() {
    carteVisibles += CARTE_PAR_PAGE;
    afficherCartes();
});

document.querySelector('.sreach_input').addEventListener('input', function() {
    const recherche = this.value.toLowerCase();

    if (recherche === '') {

        carteVisibles = CARTE_PAR_PAGE;
        afficherCartes();
    } else {

        btnVoirPlus.style.display = 'none';
        carte.forEach(function(c) {
            const nom = c.querySelector('h3').textContent.toLowerCase();
            c.style.display = nom.includes(recherche) ? 'flex' : 'none';
        });
    }
});

// ----------------------------------- fenetre de vue du produit -------------------------------

const modalOuvrirProduit = document.getElementById('modal-produit');
const modalFermerProduit = document.getElementById('modal-fermer-produit');

// ---------------------------- Ouverture de la fenetre et vue du produit ------------------------
document.querySelectorAll('.Voir-produit').forEach(function(btn){
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        ouvrirModalProduit(id);
    });
});

// ---------------------------------- Recuperation des donne en php ---------------------------------
function ouvrirModalProduit(id){
    fetch('../Dos-php/get_produit.php?id_produit=' + id)
         .then(function(response){ return response.json(); })
         .then(function(data){
            document.getElementById('modal-nom-produit').textContent                  = data.nom_medicament;
            document.getElementById('modal-nom_generique').textContent        = data.nom_generique ? 'Générique : ' + data.nom_generique : '';
            document.getElementById('modal-forme_pharmaceutique').textContent = 'Forme : ' + data.forme_pharmaceutique;
            document.getElementById('modal-dosage').textContent               = data.dosage ? 'Dosage : ' + data.dosage : '';
            document.getElementById('modal-conditionnement').textContent      = data.conditionnement ? 'Conditionnemnt : ' + data.conditionnement : '';
            document.getElementById('modal-description').textContent          = data.description;
            document.getElementById('modal-prix_unitaire_fcfa').textContent   = 'Prix : ' + data.prix_unitaire_fcfa + ' FCFA' ;

            // ----------------------------------- nombre de pharmacie ----------------------------------
            document.getElementById('modal-nb-pharmacies').textContent = '(' + data.pharmacies.length + ')';

            // --------------------------------- liste des pharmacies -----------------------------------
            const listePharmacies = document.getElementById('modal-liste-pharmacie');
            listePharmacies.innerHTML = '';

            if(data.pharmacies.length === 0){
                listePharmacies.innerHTML = '<p class ="modal-aucun"> Aucune pharmacie, pour le moment ne dispose de ce produit. </p> ';
            } else {
                data.pharmacies.forEach(function(pharmacie){
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

            // -------------------------------- bonton voir la pharmacie ----------------------------------
            document.querySelectorAll('.btn-voir-pharmacie').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const idPharmacie = this.dataset.id;
                    fermerModalProduit();
                    ouvrirModalPharmacie(idPharmacie);
                });
            });

            modalOuvrirProduit.classList.add('actif');
            document.body.classList.add('modal-ouvert');
         })
         .catch(function(){
            alert('Erreur lors du chargement des données.');
         });

}

modalFermerProduit.addEventListener('click', fermerModalProduit);
modalOuvrirProduit.addEventListener('click', function(e){
    if (e.target === modalOuvrirProduit){
        fermerModalProduit();
    }
});

function fermerModalProduit(){
    modalOuvrirProduit.classList.remove('actif');
    document.body.classList.remove('modal-ouvert');
}