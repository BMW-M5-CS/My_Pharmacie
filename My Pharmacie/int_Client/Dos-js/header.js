document.addEventListener('DOMContentLoaded', function () {


    const menuToggle = document.getElementById('menu-toggle');
    const menuMobile  = document.getElementById('menu-mobile');

    if (menuToggle && menuMobile) {
        menuToggle.addEventListener('click', function () {
            const ouvert = menuMobile.classList.toggle('ouvert');
            this.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
    }


    const alerteFermer   = document.getElementById('alerte-recuperation-fermer');
    const alerteRecup    = document.getElementById('alerte-recuperation');
    const banniereReinit = document.getElementById('banniere-reinitialisation');

    if (alerteFermer && alerteRecup) {
        alerteFermer.addEventListener('click', function () {
            alerteRecup.classList.add('masquee');

            if (banniereReinit) {
                banniereReinit.classList.remove('alerte-reinitialisation');
                banniereReinit.classList.add('alerte-recuperation-position');
            }

            // Persiste l'ignorance côté serveur pour le reste de cette session de connexion,
            // pour que la bannière ne réapparaisse pas à chaque changement de page.
            fetch('../../../My Pharmacie/int_Public/Dos-php/ignore_alert_recuperat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: alerteFermer.dataset.csrf })
            }).catch(function () {
                // Échec silencieux : la bannière reste au moins masquée visuellement
                // pour la page en cours, même si la persistance serveur a échoué.
            });
        });
    }


    if (banniereReinit) {

        const expiration = new Date(banniereReinit.dataset.expiration).getTime();
        const affichage  = document.getElementById('compte-a-rebours');

        const interval = setInterval(function () {
            const restant = expiration - Date.now();

            if (restant <= 0) {
                clearInterval(interval);
                location.reload();
                return;
            }

            const minutes  = Math.floor(restant / 60000);
            const secondes = Math.floor((restant % 60000) / 1000);
            affichage.textContent = minutes + ' min ' + secondes + ' s';

            if (minutes < 20) {
                banniereReinit.classList.add('clignote-rouge');
            }

        }, 1000);
    }

});