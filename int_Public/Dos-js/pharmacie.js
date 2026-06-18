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

