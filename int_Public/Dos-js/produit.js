document.querySelector('.sreach_input').addEventListener('input', function() {
    const recherche = this.value.toLowerCase();
    const cartes    = document.querySelectorAll('.produit');

    cartes.forEach(function(carte) {
        const nom = carte.querySelector('h3').textContent.toLowerCase();
        if (nom.includes(recherche)) {
            carte.style.display = 'flex';
        } else {
            carte.style.display = 'none';
        }
    });
});