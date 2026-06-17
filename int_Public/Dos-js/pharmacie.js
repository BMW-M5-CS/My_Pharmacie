const CARDS_PAR_PAGE = 6;
let cardsVisibles = CARDS_PAR_PAGE;

const cartes      = document.querySelectorAll('.pharmacie');
const btnVoirPlus = document.querySelector('.voir_plus');

function afficherCartes() {
    cartes.forEach(function(carte, index) {
        if (index < cardsVisibles) {
            carte.style.display = 'flex';
        } else {
            carte.style.display = 'none';
        }
    });

    if (cardsVisibles >= cartes.length) {
        btnVoirPlus.style.display = 'none';
    } else {
        btnVoirPlus.style.display = 'block';
    }
}

// Initialisation au chargement
afficherCartes();

// Clic sur "Voir plus"
btnVoirPlus.addEventListener('click', function() {
    cardsVisibles += CARDS_PAR_PAGE;
    afficherCartes();
});


document.querySelector('.sreach_input').addEventListener('input', function() {
    const recherche = this.value.toLowerCase();

    if (recherche === '') {
        cardsVisibles = CARDS_PAR_PAGE;
        afficherCartes();
    } else {

        btnVoirPlus.style.display = 'none';
        cartes.forEach(function(carte) {
            const nom = carte.querySelector('h2').textContent.toLowerCase();
            carte.style.display = nom.includes(recherche) ? 'flex' : 'none';
        });
    }
});

// ======================= Modal Pharmacie ======================
 
const modalOverlay = document.getElementById('modal-pharmacie');
const modalFermer  = document.getElementById('modal-fermer');

// ------------------------------------ Ouvreture de la fenetre de vue de la pharmacie --------------------------
document.querySelectorAll('.voir').forEach(function(btn){
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        ouvrirModalPharmacie(id);
    });
});

function ouvrirModalPharmacie(id){
    fetch('../Dos-php/get_pharmacie.php?id_pharmacie=' + id)
         .then(function(response) { return response.json(); })
         .then(function(data) {

            // ---------------------------------- Ajout des infos ----------------------------------------
            document.getElementById('modal-nom').textContent       = data.nom_pharmacie;
            document.getElementById('modal-adresse').textContent   = data.adresse;
            document.getElementById('modal-ville').textContent     = data.ville;
            document.getElementById('modal-commune').textContent   = data.commune;
            document.getElementById('modal-quartier').textContent  = data.quartier;
            document.getElementById('modal-horaire').textContent   = data.heure_ouverture + '-' + data.heure_fermeture ;
            document.getElementById('modal-telephone').textContent = data.telephone_pharmacie;
            const gardeEl = document.getElementById('modal-garde');
            if (data.statut_garde) {
                gardeEl.innerHTML = '<span class="modal-garde-badge">Pharmacie de garde</span>';
            } else {
                gardeEl.innerHTML = '<span class="modal-garde-badge">Ouverte</span>';
            }
            document.getElementById('modal-nb-produits').textContent = '(' + data.produits.length + ')';

            //  ------------------- declaration des donnée de la carte et affichage ------------------
            const lat = parseFloat(data.latitude);
            const lng = parseFloat(data.longitude);
            if (window._modalMap){
                window._modalMap.remove();
                window._modalMap = null;
            }
            setTimeout(function(){
                window._modalMap = L.map('modal-carte').setView([lat, lng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(window._modalMap);

                L.marker([lat, lng])
                    .addTo(window._modalMap)
                    .bindPopup(data.nom_pharmacie)
                    .openPopup();
            }, 100);


            // ------------------------- Remplissage de la liste des produits ---------------------------
            const listeProduit = document.getElementById('modal-liste-produits');
            listeProduit.innerHTML = '';

            if (data.produits.length === 0){
                listeProduit.innerHTML = '<p class="modal-aucun"> Aucun produit disponible pour le moment.</p>';
            }else {
                data.produits.forEach(function(produit){
                    const item = document.createElement('div');
                    item.classList.add('modal-produit-item');
                    item.innerHTML = `
                        <div class="modal-produit-image"></div>
                        <span class="modal-produit-nom">${produit.nom_medicament}</span>
                        <span class="modal-produit-forme">${produit.forme_pharmaceutique}</span>
                        <span class="modal-produit-prix">${produit.prix_unitaire_fcfa} FCFA</span>
                        <button class="btn-reserver-produit" data-id-stock="${produit.id_stock}" data-id-pharmacie="${data.id_pharmacie}">
                           <i class="fa-solid fa-calendar-check"></i> Réserver
                        </button>
                    `;
                    listeProduit.appendChild(item);
                })
            }

            // -------------------------- bouton de reservation plus controle de la connexion --------------------------------
            document.querySelectorAll('.btn-reserver-produit').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const connecte    = document.body.dataset.connecte;
                    const idStock     = this.dataset.idStock;
                    const idPharmacie = this.dataset.idPharmacie;
                    if(connecte === '1'){
                        window.location.href = '../../int_Client/Dos-page/reservation.php?id_pharmacie=' + idPharmacie + '&produit=' + idStock;
                    } else{
                        window.location.href = 'conex.php';
                    }
                });
            });

            modalOverlay.classList.add('actif');
            document.body.classList.add('modal-ouvert');
         })

         .catch(function(){
            alert('Erreur lors du chargement des données');
         });
}

modalFermer.addEventListener('click', fermerModal);
modalOverlay.addEventListener('click', function(e){
    if(e.target === modalOverlay){
        fermerModal();
    }
});

function fermerModal(){
    modalOverlay.classList.remove('actif');
    document.body.classList.remove('modal-ouvert');
    if (window._modalMap) {
        window._modalMap.remove();
        window._modalMap = null;
    }
}