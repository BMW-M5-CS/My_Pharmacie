// ===================================================================
// LISTE DES PHARMACIES — activer / désactiver
// ===================================================================

document.querySelectorAll('.admin-btn-toggle-statut').forEach(function(btn) {
    btn.addEventListener('click', function() {

        const id = this.dataset.id;
        this.disabled = true;

        fetch('../Dos-php/traiter_statut_pharmacie.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                csrf_token:    document.getElementById('csrf-token').value,
                id_pharmacie:  id
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {

            if (!data.succes) {
                alert(data.message || 'Une erreur est survenue.');
                btn.disabled = false;
                return;
            }

            const carte = btn.closest('.admin-carte-pharmacie');
            const nomSpan = carte.querySelector('.admin-pharmacie-nom');

            if (data.nouveau_statut === 'desactivee') {
                btn.textContent = 'Réactiver';
                if (!nomSpan.querySelector('.admin-badge-desactivee')) {
                    const badge = document.createElement('span');
                    badge.className = 'admin-badge-desactivee';
                    badge.textContent = 'Désactivée';
                    nomSpan.appendChild(badge);
                }
            } else {
                btn.textContent = 'Désactiver';
                const badge = nomSpan.querySelector('.admin-badge-desactivee');
                if (badge) badge.remove();
            }

            btn.disabled = false;
        })
        .catch(function() {
            alert('Erreur réseau. Veuillez réessayer.');
            btn.disabled = false;
        });
    });
});
