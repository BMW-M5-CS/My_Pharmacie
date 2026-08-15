const CARTE_PAR_PAGE = 12;
let carteVisibles = CARTE_PAR_PAGE;

const cartes      = document.querySelectorAll('.carte-pharmacie');
const btnVoirPlus = document.querySelector('.voir_plus');


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
                const nom = carte.querySelector('.carte-pharmacie-nom').textContent.toLowerCase();
                carte.style.display = nom.includes(recherche) ? 'flex' : 'none';
            });
        }
    });
}


// ----------------------------------- bouton "remonter" dans la zone pharmacies -------------------------------

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

// ----------------------------------- auto-complétion recherche pharmacie -------------------------------

const inputRecherche   = document.getElementById('champ-recherche-input');
const suggestionsListe = document.getElementById('suggestions-liste');
let timeoutSuggestions = null;

if (inputRecherche && suggestionsListe) {

    inputRecherche.addEventListener('input', function () {

        const q = this.value.trim();

        clearTimeout(timeoutSuggestions);

        if (q.length < 2) {
            suggestionsListe.innerHTML = '';
            suggestionsListe.classList.remove('active');
            return;
        }

        timeoutSuggestions = setTimeout(function () {

            fetch('../Dos-php/get_suggestions_pharmacie.php?q=' + encodeURIComponent(q))
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


// ----------------------------------- menu déroulant personnalisé (réutilisé pour ville et assurance) -------------------------------

function initialiserMenuDeroulant(idWrapper, idAffiche, idTexte, idValeur, idListe, texteParDefaut) {

    const wrapper       = document.getElementById(idWrapper);
    const affiche       = document.getElementById(idAffiche);
    const texteAffiche  = document.getElementById(idTexte);
    const champValeur   = document.getElementById(idValeur);
    const liste         = document.getElementById(idListe);

    if (!wrapper || !affiche || !liste) return;

    affiche.addEventListener('click', function (e) {
        e.stopPropagation();
        wrapper.classList.toggle('active');
    });

    liste.querySelectorAll('.ville-item').forEach(function (item) {

        if (item.dataset.valeur === champValeur.value) {
            item.classList.add('selectionnee');
        }

        item.addEventListener('click', function () {

            liste.querySelectorAll('.ville-item').forEach(function (autre) {
                autre.classList.remove('selectionnee');
            });

            item.classList.add('selectionnee');

            champValeur.value     = item.dataset.valeur;
            texteAffiche.textContent = item.dataset.valeur !== '' ? item.dataset.valeur : texteParDefaut;

            wrapper.classList.remove('active');
        });

    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('active');
        }
    });
}

initialiserMenuDeroulant('select-ville-wrapper', 'select-ville-affiche', 'select-ville-texte', 'ville-valeur', 'villes-liste', 'Toutes les villes');
initialiserMenuDeroulant('select-assurance-wrapper', 'select-assurance-affiche', 'select-assurance-texte', 'assurance-valeur', 'assurances-liste', 'Toutes les assurances');