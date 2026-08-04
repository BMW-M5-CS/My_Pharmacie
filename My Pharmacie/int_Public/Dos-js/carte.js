// ── Variables globales ──
let map;
let marqueurs           = [];
let pharmaciesData      = [];
let marqueurUtilisateur = null;
let iconeBleu;


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

        const iconeVerte = L.divIcon({
            className: '',
            html: '<div style="background-color:#00b000;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></div>',
            iconSize:    [16, 16],
            iconAnchor:  [8, 8],
            popupAnchor: [0, -10]
        });

        const iconeRouge = L.divIcon({
            className: '',
            html: '<div style="background-color:#e74c3c;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></div>',
            iconSize:    [16, 16],
            iconAnchor:  [8, 8],
            popupAnchor: [0, -10]
        });

        iconeBleu = L.divIcon({
            className: '',
            html: '<div style="background-color:#2980b9;width:18px;height:18px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.5);"></div>',
            iconSize:    [18, 18],
            iconAnchor:  [9, 9],
            popupAnchor: [0, -12]
        });

        pharmacies.forEach(function (p, index) {
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            const icone = p.statut_garde ? iconeRouge : iconeVerte;

            const contenuPopup = `
                <div style="min-width:200px;font-family:Arial,sans-serif;">
                    <div style="font-weight:700;font-size:15px;color:#00b000;margin-bottom:6px;">
                        ${p.nom_pharmacie}
                    </div>
                    <div style="font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-location-dot" style="color:#00b000;"></i>
                        ${p.adresse}, ${p.ville}
                    </div>
                    <div style="font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-clock" style="color:#00b000;"></i>
                        ${p.heure_ouverture} – ${p.heure_fermeture}
                    </div>
                    <div style="font-size:13px;margin-bottom:6px;">
                        <i class="fas fa-phone" style="color:#00b000;"></i>
                        ${p.telephone_pharmacie}
                    </div>
                    <div style="font-size:12px;font-weight:600;color:${p.statut_garde ? '#e74c3c' : '#00b000'};">
                        <i class="fas fa-circle"></i>
                        ${p.statut_garde ? 'Pharmacie de garde' : 'Ouverte'}
                    </div>
                </div>
            `;

            const marqueur = L.marker([lat, lng], { icon: icone })
                .addTo(map)
                .bindPopup(contenuPopup, { maxWidth: 260 });

            marqueur.on('click', function () {
                surlignerItem(index);
            });

            marqueurs.push(marqueur);
        });

    })
    .catch(function (erreur) {
        console.error('Erreur chargement pharmacies :', erreur);
    });