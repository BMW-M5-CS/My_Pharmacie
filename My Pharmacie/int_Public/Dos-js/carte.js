// ── Variables globales ──
let map;
let marqueurs           = [];
let pharmaciesData      = [];
let marqueurUtilisateur = null;

// Icônes des marqueurs, créées une seule fois (indépendantes de la carte elle-même)
const iconeVerte = L.divIcon({
    className: '',
    html: '<div style="background-color:#00b000;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></div>',
    iconSize:    [16, 16],
    iconAnchor:  [8, 8],
    popupAnchor: [0, -10]
});

const iconeRouge = L.divIcon({
    className: '',
    html: '<div style="background-color:#7c3aed;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></div>',
    iconSize:    [16, 16],
    iconAnchor:  [8, 8],
    popupAnchor: [0, -10]
});

// Marqueur gris : pharmacie fermée en ce moment (ni de garde, ni dans son
// créneau horaire normal). Calculé côté serveur avec l'heure réelle
// (voir fonctions_horaires.php), plus la seule valeur statut_garde.
const iconeGrise = L.divIcon({
    className: '',
    html: '<div style="background-color:#9e9e9e;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></div>',
    iconSize:    [16, 16],
    iconAnchor:  [8, 8],
    popupAnchor: [0, -10]
});

const iconeBleu = L.divIcon({
    className: '',
    html: '<div style="background-color:#2980b9;width:18px;height:18px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.5);"></div>',
    iconSize:    [18, 18],
    iconAnchor:  [9, 9],
    popupAnchor: [0, -12]
});

// Marqueur doré : mis en avant sur la pharmacie la plus proche disposant du
// produit recherché (recherche croisée carte + stock)
const iconeOr = L.divIcon({
    className: '',
    html: '<div style="background-color:#f5a623;width:22px;height:22px;border-radius:50%;border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.5);"></div>',
    iconSize:    [22, 22],
    iconAnchor:  [11, 11],
    popupAnchor: [0, -13]
});

// Choisit l'icône du marqueur à partir du statut calculé côté serveur
// (voir fonctions_horaires.php : 'garde', 'ouverte', ou 'fermee').
function iconePourStatut(statutCalcule) {
    if (statutCalcule === 'garde') return iconeRouge;
    if (statutCalcule === 'ouverte') return iconeVerte;
    return iconeGrise;
}

function couleurPourStatut(statutCalcule) {
    if (statutCalcule === 'garde') return '#7c3aed';
    if (statutCalcule === 'ouverte') return '#00b000';
    return '#9e9e9e';
}

function libellePourStatut(statutCalcule) {
    if (statutCalcule === 'garde') return 'Pharmacie de garde';
    if (statutCalcule === 'ouverte') return 'Ouverte';
    return 'Fermée';
}


// Mode recherche produit actif ou non (filtre la liste/les marqueurs affichés)
let modeRechercheProduitActif = false;


// ===================================================================
// FLUX ASSURANCE — posé avant chaque recherche produit sur la carte
// ===================================================================

// Référentiel des 3 assurances gérées, utilisé pour les chips de choix et les badges
const ASSURANCES_DISPONIBLES = [
    { nom: 'INAM',            logo: '../Dos-img/INAM.png' },
    { nom: 'AMU',              logo: '../Dos-img/AMU.png' },
    { nom: 'SUNU Assurance',   logo: '../Dos-img/SUNU.png' },
];


function construireModalAssurance() {

    if (document.getElementById('modal-question-assurance')) return;

    const overlay = document.createElement('div');
    overlay.className = 'fenetre-assurance';
    overlay.id         = 'modal-question-assurance';

    overlay.innerHTML = `
        <div class="modal-contenu-assurance">
            <button type="button" class="modal-assurance-fermer" id="modal-assurance-fermer">&times;</button>
            <div id="modal-assurance-corps"></div>
        </div>
    `;

    document.body.appendChild(overlay);

    document.getElementById('modal-assurance-fermer').addEventListener('click', fermerModalAssurance);

    // Fermeture au clic en dehors du contenu (sur le fond assombri)
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) fermerModalAssurance();
    });
}

construireModalAssurance();


function ouvrirEtapeModalAssurance(html, brancherEcouteurs) {

    document.getElementById('modal-assurance-corps').innerHTML = html;
    document.getElementById('modal-question-assurance').classList.add('actif');

    brancherEcouteurs();
}

function fermerModalAssurance() {
    document.getElementById('modal-question-assurance').classList.remove('actif');
}


// ── Constructeurs HTML des 3 étapes possibles ──

function htmlEtapeOuiNon() {
    return `
        <p class="modal-assurance-question">Souhaitez-vous utiliser une assurance ?</p>
        <div class="modal-assurance-boutons">
            <button type="button" class="btn-assurance-principal" id="ma-btn-oui">Oui</button>
            <button type="button" class="btn-assurance-secondaire" id="ma-btn-non">Non</button>
        </div>
    `;
}

function htmlEtapeConnecte(assuranceDefaut) {

    const infosLogo = ASSURANCES_DISPONIBLES.find(function (a) { return a.nom === assuranceDefaut; });
    const logoHtml  = infosLogo ? '<img src="' + infosLogo.logo + '" alt="">' : '';

    return `
        <p class="modal-assurance-question">Quelle assurance souhaitez-vous utiliser ?</p>
        <div class="modal-assurance-boutons modal-assurance-boutons-colonne">
            <button type="button" class="btn-assurance-principal btn-mon-assurance" id="ma-btn-mon-assurance">
                ${logoHtml} Utiliser mon assurance (${echapperHtml(assuranceDefaut)})
            </button>
            <button type="button" class="btn-assurance-secondaire" id="ma-btn-autre-assurance">Choisir une autre assurance</button>
            <button type="button" class="btn-assurance-lien" id="ma-btn-laisser-tomber">Laisser tomber l'assurance</button>
        </div>
    `;
}

function htmlEtapeChoixListe() {

    const chips = ASSURANCES_DISPONIBLES.map(function (a) {
        return `
            <button type="button" class="btn-choix-assurance-item" data-nom-assurance="${echapperHtml(a.nom)}">
                <img src="${a.logo}" alt="">
                <span>${echapperHtml(a.nom)}</span>
            </button>
        `;
    }).join('');

    return `
        <p class="modal-assurance-question">Choisissez votre assurance</p>
        <div class="modal-assurance-boutons modal-assurance-boutons-colonne">
            ${chips}
            <button type="button" class="btn-assurance-lien" id="ma-btn-laisser-tomber-liste">Laisser tomber l'assurance</button>
        </div>
    `;
}


// ── Orchestration des étapes ──

// Point d'entrée principal : à appeler avant toute recherche produit sur la carte.
// callback(assuranceChoisie) est appelé une fois la réponse obtenue (null = pas de filtre assurance)
function demanderAssurance(callback) {

    ouvrirEtapeModalAssurance(htmlEtapeOuiNon(), function () {

        document.getElementById('ma-btn-oui').addEventListener('click', function () {
            afficherEtapeChoixAssurance(callback);
        });

        document.getElementById('ma-btn-non').addEventListener('click', function () {
            fermerModalAssurance();
            callback(null);
        });
    });
}

function afficherEtapeChoixAssurance(callback) {

    const connecte        = document.body.dataset.connecte === '1';
    const assuranceDefaut = document.body.dataset.assuranceDefaut || '';

    // Client connecté avec une assurance déjà enregistrée en profil : 3 choix.
    // Sinon (visiteur, ou client sans assurance en profil) : directement les 4 choix (voir plus bas)
    if (connecte && assuranceDefaut !== '') {

        ouvrirEtapeModalAssurance(htmlEtapeConnecte(assuranceDefaut), function () {

            document.getElementById('ma-btn-mon-assurance').addEventListener('click', function () {
                fermerModalAssurance();
                callback(assuranceDefaut);
            });

            document.getElementById('ma-btn-autre-assurance').addEventListener('click', function () {
                afficherEtapeChoixListe(callback);
            });

            document.getElementById('ma-btn-laisser-tomber').addEventListener('click', function () {
                fermerModalAssurance();
                callback(null);
            });
        });

    } else {

        afficherEtapeChoixListe(callback);
    }
}

function afficherEtapeChoixListe(callback) {

    ouvrirEtapeModalAssurance(htmlEtapeChoixListe(), function () {

        document.querySelectorAll('.btn-choix-assurance-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                fermerModalAssurance();
                callback(this.dataset.nomAssurance);
            });
        });

        document.getElementById('ma-btn-laisser-tomber-liste').addEventListener('click', function () {
            fermerModalAssurance();
            callback(null);
        });
    });
}

// Utilisée par le bouton "Utiliser une autre assurance" du panneau de repli (cas "aucun résultat") :
// l'utilisateur a déjà répondu "Oui" à la question initiale, donc on saute directement au choix
function demanderAssuranceChoixSeul(callback) {
    afficherEtapeChoixListe(callback);
}


function centrerSurPharmacie(index) {
    const p   = pharmaciesData[index];
    const lat = parseFloat(p.latitude);
    const lng = parseFloat(p.longitude);

    if (isNaN(lat) || isNaN(lng)) return;

    map.setView([lat, lng], 16);
    marqueurs[index].openPopup();
    surlignerItem(index);

    const item = document.getElementById('item-' + index);
    if (item) item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (window.innerWidth <= 900) {
        basculerVue('carte');
    }
}

function surlignerItem(index) {
    document.querySelectorAll('.pharmacie-item').forEach(function (el) {
        el.classList.remove('active');
    });
    const item = document.getElementById('item-' + index);
    if (item) item.classList.add('active');
}

function localiserUtilisateur() {
    if (!navigator.geolocation) {
        afficherMessage("Votre navigateur ne supporte pas la géolocalisation.");
        return;
    }

    afficherMessage("Localisation en cours...");

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // Réutilisée par la recherche produit pour calculer les distances,
            // sans redemander la permission de géolocalisation une deuxième fois
            definirPositionUtilisateurCache(lat, lng);

            if (marqueurUtilisateur) {
                map.removeLayer(marqueurUtilisateur);
            }

            marqueurUtilisateur = L.marker([lat, lng], { icon: iconeBleu })
                .addTo(map)
                .bindPopup('<strong>📍 Vous êtes ici</strong>')
                .openPopup();

            map.setView([lat, lng], 15);
            afficherMessage("Position trouvée !");

            if (window.innerWidth <= 900) {
                basculerVue('carte');
            }

            setTimeout(function () {
                document.getElementById('msg-geoloc').style.display = 'none';
            }, 2000);
        },
        function () {
            afficherMessage("Impossible de vous localiser. Vérifiez les autorisations.");
            setTimeout(function () {
                document.getElementById('msg-geoloc').style.display = 'none';
            }, 3000);
        }
    );
}

function afficherMessage(texte) {
    const msg         = document.getElementById('msg-geoloc');
    msg.textContent   = texte;
    msg.style.display = 'block';
}


// ── Bascule liste / carte (mobile uniquement) ──

const carteWrapper   = document.getElementById('carte-wrapper');
const btnBasculerVue = document.getElementById('btn-basculer-vue');
const texteBasculer  = document.getElementById('texte-basculer');

function basculerVue(vue) {
    if (vue === 'carte') {

        carteWrapper.classList.remove('mode-liste');
        carteWrapper.classList.add('mode-carte');
        texteBasculer.textContent = 'Voir la liste';
        btnBasculerVue.querySelector('i').className = 'fas fa-list';

        // Leaflet doit recalculer sa taille une fois son conteneur redevenu visible,
        // sinon la carte reste grise ou mal dimensionnée après un display:none
        setTimeout(function () {
            if (map) map.invalidateSize();
        }, 200);

    } else {

        carteWrapper.classList.remove('mode-carte');
        carteWrapper.classList.add('mode-liste');
        texteBasculer.textContent = 'Voir la carte';
        btnBasculerVue.querySelector('i').className = 'fas fa-map';

    }
}

if (btnBasculerVue) {
    btnBasculerVue.addEventListener('click', function () {
        const enModeCarte = carteWrapper.classList.contains('mode-carte');
        basculerVue(enModeCarte ? 'liste' : 'carte');
    });
}


// ── Bouton géolocalisation ──

const btnGeoloc = document.getElementById('btn-geoloc');
if (btnGeoloc) {
    btnGeoloc.addEventListener('click', localiserUtilisateur);
}


// ── Clic sur un élément de la liste ──

document.querySelectorAll('.pharmacie-item').forEach(function (item) {
    item.addEventListener('click', function () {
        centrerSurPharmacie(parseInt(this.dataset.index, 10));
    });
});


// ── Clic sur le bouton "Voir la pharmacie" d'un élément de la liste ──
// (stopPropagation pour ne pas déclencher aussi le centrage sur la carte)

document.querySelectorAll('.pharm-btn-voir').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        ouvrirModalPharmacie(this.dataset.id);
    });
});


// ===================================================================
// RECHERCHE PRODUIT SUR LA CARTE
// Croise le nom du produit cherché avec le stock de chaque pharmacie, filtre
// la carte + la liste sur les pharmacies qui l'ont, et met en avant la plus
// proche (si la position de l'utilisateur est disponible).
// ===================================================================

const champRechercheProduitCarte = document.getElementById('carte-recherche-produit-input');
const suggestionsProduitCarte    = document.getElementById('carte-suggestions-produit');
const btnRechercheProduitCarte   = document.getElementById('carte-recherche-produit-btn');
const btnEffacerRechercheCarte   = document.getElementById('carte-recherche-produit-effacer');
const statutRechercheCarte       = document.getElementById('carte-recherche-statut');

let timeoutSuggestionsCarte = null;


// ── Autocomplétion (réutilise l'endpoint déjà utilisé sur produit.php/pharmacie.php) ──

if (champRechercheProduitCarte && suggestionsProduitCarte) {

    champRechercheProduitCarte.addEventListener('input', function () {

        const q = this.value.trim();

        if (q === '' && modeRechercheProduitActif) {
            reinitialiserRechercheProduitCarte();
        }

        clearTimeout(timeoutSuggestionsCarte);

        if (q.length < 2) {
            suggestionsProduitCarte.innerHTML = '';
            suggestionsProduitCarte.classList.remove('actif');
            return;
        }

        timeoutSuggestionsCarte = setTimeout(function () {

            fetch('../Dos-php/get_suggestions_produit.php?q=' + encodeURIComponent(q))
                .then(function (response) { return response.json(); })
                .then(function (resultats) {

                    suggestionsProduitCarte.innerHTML = '';

                    if (resultats.length === 0) {
                        suggestionsProduitCarte.classList.remove('actif');
                        return;
                    }

                    resultats.forEach(function (nom) {

                        const item = document.createElement('div');
                        item.classList.add('carte-suggestion-item');
                        item.textContent = nom;

                        item.addEventListener('click', function () {
                            champRechercheProduitCarte.value = nom;
                            suggestionsProduitCarte.innerHTML = '';
                            suggestionsProduitCarte.classList.remove('actif');
                            lancerRechercheAvecAssurance(nom);
                        });

                        suggestionsProduitCarte.appendChild(item);
                    });

                    suggestionsProduitCarte.classList.add('actif');
                })
                .catch(function () {
                    suggestionsProduitCarte.innerHTML = '';
                    suggestionsProduitCarte.classList.remove('actif');
                });

        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (!champRechercheProduitCarte.contains(e.target) && !suggestionsProduitCarte.contains(e.target)) {
            suggestionsProduitCarte.innerHTML = '';
            suggestionsProduitCarte.classList.remove('actif');
        }
    });

    champRechercheProduitCarte.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            suggestionsProduitCarte.innerHTML = '';
            suggestionsProduitCarte.classList.remove('actif');
            lancerRechercheAvecAssurance(this.value);
        }
    });
}

if (btnRechercheProduitCarte) {
    btnRechercheProduitCarte.addEventListener('click', function () {
        lancerRechercheAvecAssurance(champRechercheProduitCarte.value);
    });
}

if (btnEffacerRechercheCarte) {
    btnEffacerRechercheCarte.addEventListener('click', function () {
        champRechercheProduitCarte.value = '';
        masquerRepliAssurance();
        reinitialiserRechercheProduitCarte();
    });
}


// ── Point d'entrée : pose la question assurance puis lance la recherche avec la réponse obtenue ──

function lancerRechercheAvecAssurance(nomProduit) {

    const q = nomProduit.trim();

    if (q === '') {
        reinitialiserRechercheProduitCarte();
        return;
    }

    demanderAssurance(function (assuranceChoisie) {
        rechercherProduitSurCarte(q, assuranceChoisie);
    });
}


// ── Lancement de la recherche : interroge le serveur, regroupe par pharmacie ──

function rechercherProduitSurCarte(recherche, assuranceChoisie) {

    const q = recherche.trim();

    if (q === '') {
        reinitialiserRechercheProduitCarte();
        return;
    }

    masquerRepliAssurance();
    afficherStatutRecherche('Recherche en cours...');

    fetch('../Dos-php/get_carte_produit.php?recherche=' + encodeURIComponent(q))
        .then(function (response) { return response.json(); })
        .then(function (resultats) {

            const pharmaciesTrouvees = grouperResultatsParPharmacie(resultats);

            if (pharmaciesTrouvees.length === 0) {
                appliquerFiltreProduitCarte([]);
                afficherStatutRecherche("Aucune pharmacie n'a ce produit en stock actuellement.", true);
                return;
            }

            obtenirPositionUtilisateur(function (position) {

                if (position) {
                    pharmaciesTrouvees.forEach(function (p) {
                        p.distanceKm = calculerDistanceKm(position.lat, position.lng, parseFloat(p.latitude), parseFloat(p.longitude));
                    });
                    pharmaciesTrouvees.sort(function (a, b) { return a.distanceKm - b.distanceKm; });
                }

                // ----- Pas de filtre assurance : comportement classique à 2 contraintes -----

                if (!assuranceChoisie) {

                    appliquerFiltreProduitCarte(pharmaciesTrouvees);

                    const plusProche      = pharmaciesTrouvees[0];
                    const suffixeDistance = plusProche.distanceKm !== undefined
                        ? ' — la plus proche est à ' + formaterDistance(plusProche.distanceKm)
                        : '';

                    afficherStatutRecherche(pharmaciesTrouvees.length + ' pharmacie(s) ont ce produit' + suffixeDistance);
                    return;
                }

                // ----- Filtre assurance actif : 3ᵉ contrainte -----

                const pharmaciesAvecAssurance = pharmaciesTrouvees.filter(function (p) {
                    return (p.assurances || []).some(function (a) { return a.nom_assurance === assuranceChoisie; });
                });

                if (pharmaciesAvecAssurance.length > 0) {

                    appliquerFiltreProduitCarte(pharmaciesAvecAssurance);

                    const plusProche      = pharmaciesAvecAssurance[0];
                    const suffixeDistance = plusProche.distanceKm !== undefined
                        ? ' — la plus proche est à ' + formaterDistance(plusProche.distanceKm)
                        : '';

                    afficherStatutRecherche(pharmaciesAvecAssurance.length + ' pharmacie(s) ont ce produit et acceptent ' + assuranceChoisie + suffixeDistance);
                    return;
                }

                // ----- Aucune pharmacie avec ce produit n'accepte cette assurance -----
                // On affiche quand même la carte/liste normale (2 contraintes), et on propose en plus
                // la pharmacie la plus proche sans la contrainte assurance, avec les options de repli

                appliquerFiltreProduitCarte(pharmaciesTrouvees);
                afficherStatutRecherche("Aucune pharmacie avec ce produit n'accepte " + assuranceChoisie + ".", true);
                afficherRepliAssurance(pharmaciesTrouvees[0], assuranceChoisie, q);
            });
        })
        .catch(function () {
            afficherStatutRecherche('Erreur lors de la recherche.', true);
        });
}


function grouperResultatsParPharmacie(resultats) {

    const groupes = {};

    resultats.forEach(function (r) {

        if (!groupes[r.id_pharmacie]) {
            groupes[r.id_pharmacie] = Object.assign({}, r, { produitsCorrespondants: [], assurances: r.assurances || [] });
        }

        groupes[r.id_pharmacie].produitsCorrespondants.push({
            nom_medicament:      r.nom_medicament,
            prix_unitaire_fcfa:  r.prix_unitaire_fcfa
        });
    });

    return Object.values(groupes);
}


function afficherStatutRecherche(texte, aucunResultat) {
    if (statutRechercheCarte) {
        statutRechercheCarte.textContent = texte;
        statutRechercheCarte.classList.add('actif');
        statutRechercheCarte.classList.toggle('aucun-resultat', !!aucunResultat);
    }
}


// ── Panneau de repli affiché quand aucune pharmacie disposant du produit n'accepte l'assurance choisie ──

function obtenirConteneurRepliAssurance() {

    let conteneur = document.getElementById('carte-repli-assurance');

    if (!conteneur && statutRechercheCarte) {
        conteneur = document.createElement('div');
        conteneur.id        = 'carte-repli-assurance';
        conteneur.className = 'carte-repli-assurance';
        statutRechercheCarte.parentNode.insertBefore(conteneur, statutRechercheCarte.nextSibling);
    }

    return conteneur;
}

function masquerRepliAssurance() {
    const conteneur = document.getElementById('carte-repli-assurance');
    if (conteneur) {
        conteneur.innerHTML = '';
        conteneur.classList.remove('actif');
    }
}

function afficherRepliAssurance(pharmacieProche, assuranceChoisie, recherche) {

    const conteneur = obtenirConteneurRepliAssurance();
    if (!conteneur) return;

    const distanceTexte = pharmacieProche.distanceKm !== undefined
        ? ' (à ' + formaterDistance(pharmacieProche.distanceKm) + ')'
        : '';

    conteneur.innerHTML = `
        <p class="repli-assurance-texte">
            <strong>${echapperHtml(pharmacieProche.nom_pharmacie)}</strong> a ce produit${distanceTexte},
            mais ne prend pas ${echapperHtml(assuranceChoisie)}.
        </p>
        <div class="repli-assurance-boutons">
            <button type="button" class="btn-repli-autre-assurance" id="btn-repli-autre-assurance">
                <i class="fa-solid fa-rotate"></i> Utiliser une autre assurance
            </button>
            <button type="button" class="btn-repli-sans-assurance" id="btn-repli-sans-assurance">
                <i class="fa-solid fa-xmark"></i> Refaire sans assurance
            </button>
        </div>
    `;

    conteneur.classList.add('actif');

    document.getElementById('btn-repli-autre-assurance').addEventListener('click', function () {
        demanderAssuranceChoixSeul(function (nouvelleAssurance) {
            rechercherProduitSurCarte(recherche, nouvelleAssurance);
        });
    });

    document.getElementById('btn-repli-sans-assurance').addEventListener('click', function () {
        rechercherProduitSurCarte(recherche, null);
    });
}


// ── Applique le filtre : cache les pharmacies qui n'ont pas le produit, sur la
//    carte comme dans la liste, et met en évidence la plus proche ──

function appliquerFiltreProduitCarte(pharmaciesTrouvees) {

    modeRechercheProduitActif = true;

    const idsTrouves = new Set(pharmaciesTrouvees.map(function (p) { return String(p.id_pharmacie); }));

    let indexPlusProche = -1;

    pharmaciesData.forEach(function (p, index) {

        const correspond = idsTrouves.has(String(p.id_pharmacie));
        const item        = document.getElementById('item-' + index);

        if (item) item.style.display = correspond ? '' : 'none';

        if (marqueurs[index]) {

            if (correspond) {

                if (!map.hasLayer(marqueurs[index])) marqueurs[index].addTo(map);
                marqueurs[index].setIcon(iconePourStatut(p.statut_calcule));

            } else if (map.hasLayer(marqueurs[index])) {

                map.removeLayer(marqueurs[index]);
            }
        }
    });

    // La première pharmacie trouvée (déjà triée par distance si la géoloc est connue)
    // reçoit le marqueur doré, et la carte se centre dessus
    if (pharmaciesTrouvees.length > 0) {

        indexPlusProche = pharmaciesData.findIndex(function (p) {
            return String(p.id_pharmacie) === String(pharmaciesTrouvees[0].id_pharmacie);
        });

        if (indexPlusProche !== -1 && marqueurs[indexPlusProche]) {

            marqueurs[indexPlusProche].setIcon(iconeOr);

            const latProche = parseFloat(pharmaciesData[indexPlusProche].latitude);
            const lngProche = parseFloat(pharmaciesData[indexPlusProche].longitude);

            if (!isNaN(latProche) && !isNaN(lngProche)) {
                map.setView([latProche, lngProche], 15);
                marqueurs[indexPlusProche].openPopup();
            }

            surlignerItem(indexPlusProche);
        }
    }

    const compteur = document.getElementById('compteur');
    if (compteur) compteur.textContent = '(' + pharmaciesTrouvees.length + ')';
}


// ── Retour à l'affichage normal (toutes les pharmacies, icônes d'origine) ──

function reinitialiserRechercheProduitCarte() {

    modeRechercheProduitActif = false;

    masquerRepliAssurance();

    pharmaciesData.forEach(function (p, index) {

        const item = document.getElementById('item-' + index);
        if (item) item.style.display = '';

        if (marqueurs[index]) {

            if (!map.hasLayer(marqueurs[index])) marqueurs[index].addTo(map);
            marqueurs[index].setIcon(iconePourStatut(p.statut_calcule));
        }
    });

    document.querySelectorAll('.pharmacie-item').forEach(function (el) {
        el.classList.remove('active');
    });

    const compteur = document.getElementById('compteur');
    if (compteur) compteur.textContent = '(' + pharmaciesData.length + ')';

    if (statutRechercheCarte) {
        statutRechercheCarte.textContent = '';
        statutRechercheCarte.classList.remove('actif', 'aucun-resultat');
    }
}


// ── Fetch : récupérer les pharmacies et initialiser la carte ──

fetch('../Dos-php/get_carte.php')
    .then(function (response) { return response.json(); })
    .then(function (pharmacies) {

        pharmaciesData = pharmacies;

        const compteur = document.getElementById('compteur');
        if (compteur) compteur.textContent = '(' + pharmacies.length + ')';

        map = L.map('map').setView([6.1375, 1.2123], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);

        pharmacies.forEach(function (p, index) {
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            const icone = iconePourStatut(p.statut_calcule);

            const badgesAssurancesHtml = (p.assurances && p.assurances.length > 0)
                ? '<div style="display:flex;gap:4px;margin-bottom:6px;">' +
                    p.assurances.map(function (a) {
                        return '<img src="../Dos-img/' + encodeURIComponent(a.logo_assurance) + '" alt="' + echapperHtml(a.nom_assurance) + '" title="' + echapperHtml(a.nom_assurance) + '" style="width:20px;height:20px;object-fit:contain;border-radius:50%;border:1px solid #eee;background:white;">';
                    }).join('') +
                  '</div>'
                : '';

            // Les champs venant de la BDD sont échappés avant insertion dans le HTML
            // du popup, pour éviter qu'un nom/adresse/téléphone contenant du code
            // HTML ne s'exécute dans le navigateur (XSS)
            const contenuPopup = `
                <div style="min-width:200px;font-family:Arial,sans-serif;">
                    <div style="font-weight:700;font-size:15px;color:#00b000;margin-bottom:6px;">
                        ${echapperHtml(p.nom_pharmacie)}
                    </div>
                    <div style="font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-location-dot" style="color:#00b000;"></i>
                        ${echapperHtml(p.adresse)}, ${echapperHtml(p.ville)}
                    </div>
                    <div style="font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-clock" style="color:#00b000;"></i>
                        ${echapperHtml(p.heure_ouverture)} – ${echapperHtml(p.heure_fermeture)}
                    </div>
                    <div style="font-size:13px;margin-bottom:6px;">
                        <i class="fas fa-phone" style="color:#00b000;"></i>
                        ${echapperHtml(p.telephone_pharmacie)}
                    </div>
                    <div style="font-size:12px;font-weight:600;color:${couleurPourStatut(p.statut_calcule)};margin-bottom:8px;">
                        <i class="fas fa-circle"></i>
                        ${libellePourStatut(p.statut_calcule)}
                    </div>
                    ${badgesAssurancesHtml}
                    <button type="button" class="popup-btn-voir-pharmacie" data-id="${p.id_pharmacie}" style="width:100%;height:32px;background-color:#00b000;color:white;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fa-solid fa-store"></i> Voir la pharmacie
                    </button>
                </div>
            `;

            const marqueur = L.marker([lat, lng], { icon: icone })
                .addTo(map)
                .bindPopup(contenuPopup, { maxWidth: 260 });

            marqueur.on('click', function () {
                surlignerItem(index);
            });

            // Le bouton "Voir la pharmacie" n'existe dans le DOM qu'une fois le
            // popup ouvert, donc on écoute son ouverture pour brancher le clic
            marqueur.on('popupopen', function () {
                const btn = document.querySelector('.popup-btn-voir-pharmacie[data-id="' + p.id_pharmacie + '"]');
                if (btn) {
                    btn.addEventListener('click', function () {
                        ouvrirModalPharmacie(p.id_pharmacie);
                    });
                }
            });

            marqueurs.push(marqueur);
        });

    })
    .catch(function (erreur) {
        console.error('Erreur chargement pharmacies :', erreur);
    });