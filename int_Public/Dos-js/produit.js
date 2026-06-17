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