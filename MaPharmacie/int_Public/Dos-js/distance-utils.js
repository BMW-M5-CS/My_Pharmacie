// ===================================================================
// Utilitaires de distance — partagés entre carte.js et produit.js
// ===================================================================


// Distance à vol d'oiseau entre deux points GPS (formule de Haversine), en km
function calculerDistanceKm(lat1, lng1, lat2, lng2) {

    const R = 6371; // rayon moyen de la Terre en km

    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}


function formaterDistance(km) {

    if (km < 1) {
        return Math.round(km * 1000) + ' m';
    }

    return km.toFixed(1) + ' km';
}


// Position de l'utilisateur mise en cache pour la durée de la page, pour éviter
// de redemander la permission de géolocalisation à chaque recherche/ouverture
let _positionUtilisateurCache  = null;
let _positionUtilisateurEchec  = false;

function obtenirPositionUtilisateur(callback) {

    if (_positionUtilisateurCache) {
        callback(_positionUtilisateurCache);
        return;
    }

    if (_positionUtilisateurEchec || !navigator.geolocation) {
        callback(null);
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (position) {
            _positionUtilisateurCache = { lat: position.coords.latitude, lng: position.coords.longitude };
            callback(_positionUtilisateurCache);
        },
        function () {
            _positionUtilisateurEchec = true;
            callback(null);
        }
    );
}


// Permet à carte.js de réutiliser la position déjà obtenue via le bouton
// "Me localiser" (évite de redemander la permission une deuxième fois)
function definirPositionUtilisateurCache(lat, lng) {
    _positionUtilisateurCache = { lat: lat, lng: lng };
}
