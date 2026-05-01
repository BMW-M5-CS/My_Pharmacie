// ── Variables globales ──
let map;
let marqueurs           = [];
let pharmaciesData      = [];
let marqueurUtilisateur = null;
let iconeBleu;

// ── Fonctions globales (accessibles depuis onclick dans le HTML) ──

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
}

function surlignerItem(index) {
    document.querySelectorAll('.pharmacie-item').forEach(function(el) {
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
        function(position) {
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

            setTimeout(function() {
                document.getElementById('msg-geoloc').style.display = 'none';
            }, 2000);
        },
        function() {
            afficherMessage("Impossible de vous localiser. Vérifiez les autorisations.");
            setTimeout(function() {
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

// ── Fetch : récupérer les pharmacies et initialiser la carte ──

fetch('../Dos-php/get_carte.php')
    .then(function(response) { return response.json(); })
    .then(function(pharmacies) {

        // Stocker dans la variable globale
        pharmaciesData = pharmacies;

        // Mettre à jour le compteur dans la liste
        const compteur = document.getElementById('compteur');
        if (compteur) compteur.textContent = '(' + pharmacies.length + ')';

        // ── Initialisation de la carte centrée sur Lomé ──
        map = L.map('map').setView([6.1375, 1.2123], 13);

        // ── Fond de carte OpenStreetMap ──
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);

        // ── Icônes personnalisées ──
        const iconeVerte = L.divIcon({
            className: '',
            html: '<div style="background-color:#03ad03;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></div>',
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

        // iconeBleu est global car utilisé dans localiserUtilisateur()
        iconeBleu = L.divIcon({
            className: '',
            html: '<div style="background-color:#2980b9;width:18px;height:18px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.5);"></div>',
            iconSize:    [18, 18],
            iconAnchor:  [9, 9],
            popupAnchor: [0, -12]
        });

        // ── Placement des marqueurs ──
        pharmacies.forEach(function(p, index) {
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            const icone = p.statut_garde ? iconeRouge : iconeVerte;

            const contenuPopup = `
                <div style="min-width:200px;font-family:Arial,sans-serif;">
                    <div style="font-weight:700;font-size:15px;color:green;margin-bottom:6px;">
                        ${p.nom_pharmacie}
                    </div>
                    <div style="font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-location-dot" style="color:green;"></i>
                        ${p.adresse}, ${p.ville}
                    </div>
                    <div style="font-size:13px;margin-bottom:4px;">
                        <i class="fas fa-clock" style="color:green;"></i>
                        ${p.heure_ouverture} – ${p.heure_fermeture}
                    </div>
                    <div style="font-size:13px;margin-bottom:6px;">
                        <i class="fas fa-phone" style="color:green;"></i>
                        ${p.telephone_pharmacie}
                    </div>
                    <div style="font-size:12px;font-weight:600;color:${p.statut_garde ? '#e74c3c' : '#03ad03'};">
                        <i class="fas fa-circle"></i>
                        ${p.statut_garde ? 'Pharmacie de garde' : 'Ouverte'}
                    </div>
                </div>
            `;

            const marqueur = L.marker([lat, lng], { icon: icone })
                .addTo(map)
                .bindPopup(contenuPopup, { maxWidth: 260 });

            marqueur.on('click', function() {
                surlignerItem(index);
            });

            marqueurs.push(marqueur);
        });

    })
    .catch(function(erreur) {
        console.error('Erreur chargement pharmacies :', erreur);
    });
