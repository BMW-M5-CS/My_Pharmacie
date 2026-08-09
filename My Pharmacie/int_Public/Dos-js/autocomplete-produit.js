// ===================================================================
// AUTO-COMPLÉTION RECHERCHE PRODUIT
// Partagé entre acceuil.php et produit.php
// ===================================================================

const inputRecherche   = document.getElementById('champ-recherche-input');
const suggestionsListe = document.getElementById('suggestions-liste');
let timeoutSuggestions = null;

if (inputRecherche && suggestionsListe) {

    inputRecherche.addEventListener('input', function() {

        const q = this.value.trim();

        clearTimeout(timeoutSuggestions);

        if (q.length < 2) {
            suggestionsListe.innerHTML = '';
            suggestionsListe.classList.remove('active');
            return;
        }

        timeoutSuggestions = setTimeout(function() {

            fetch('../Dos-php/get_suggestions_produit.php?q=' + encodeURIComponent(q))
                .then(function (response) { return response.json(); })
                .then(function (resultats) {

                    suggestionsListe.innerHTML = '';

                    if (resultats.length === 0) {
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