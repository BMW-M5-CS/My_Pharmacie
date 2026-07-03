
// section des variable globales

const btnModifierInfos      = document.getElementById("btn-modifier-infos");
const btnAnnulerInfos       = document.getElementById("btn-annuler-infos");
const btnEnregistrerInfo    = document.getElementById("btn-enregistrer-infos");
const infosLecture          = document.getElementById("infos-lecture");
const infosEdition          = document.getElementById("infos-edition");
const messageInfos          = document.getElementById("message-infos");

const btnEnregistrerMdp     = document.getElementById("btn-enregistrer-mdp");
const messageMdp            = document.getElementById("message-mdp");

const overlay               = document.getElementById("profil-overlay");
const modaleAnnuler         = document.getElementById("modale-annuler");
const modaleConfirmer       = document.getElementById("modale-confirmer");
const mdpConfirmationModale = document.getElementById("mdp-confirmation-modale");
const messageModale         = document.getElementById("message-modale");





// section d'edition des infos personnelles
btnModifierInfos.addEventListener("click", function() {

    infosLecture.style.display = "none";
    infosEdition.style.display = "block";

    btnModifierInfos.style.display = "none";

    messageInfos.textContent = "";
    messageInfos.className = "profil-message";

});


// Annulation de l'edition des infos personnelles

btnAnnulerInfos.addEventListener("click", function() {

    infosEdition.style.display = "none";
    infosLecture.style.display = "block";

    btnModifierInfos.style.display = "flex";

    document.getElementById('edit-nom').value          = document.getElementById('lecture-nom').textContent.trim();
    document.getElementById('edit-prenom').value       = document.getElementById('lecture-prenom').textContent.trim();
    document.getElementById('edit-phone-email').value  = document.getElementById('lecture-phone-email').textContent.trim();

    messageInfos.textContent = "";
    messageInfos.className = "profil-message";

});


//Click sur Enregistrer avec l'ouverture du modal de confirmation

btnEnregistrerInfo.addEventListener("click", function() {
    const nom        = document.getElementById('edit-nom').value.trim();
    const prenom     = document.getElementById('edit-prenom').value.trim();
    const phoneEmail = document.getElementById('edit-phone-email').value.trim();
    
    //validation rapide avant l'ouverture du modal

    if (!nom || !prenom || !phoneEmail) {
       afficherMessage(messageInfos, "Tous les champs sont obligatoires.", "erreur");
       return;
    }

    // Ouverture du modal de confirmation
    mdpConfirmationModale.value = "";
    messageModale.textContent   = "";
    messageModale.className       = "profil-message";
    overlay.classList.add("actif");
});


// section modale de confirmation 

modaleAnnuler.addEventListener('click', function() {
    overlay.classList.remove('actif');
});

overlay.addEventListener('click', function(e) {
    if (e.target === overlay){
        overlay.classList.remove('actif');
    }
});

//confirmation envoyer les modification au backend 
modaleConfirmer.addEventListener('click', function(){

    const mdpActuel = mdpConfirmationModale.value.trim();

    if(!mdpActuel){
        afficherMessage(messageModale, 'Veuillez entrer votre mode passe.', 'erreur ');
        return;
    }

    const nom        = document.getElementById('edit-nom').value.trim();
    const prenom     = document.getElementById('edit-prenom').value.trim();
    const phoneEmail = document.getElementById('edit-phone-email').value.trim();

    modaleConfirmer.disabled    = true;
    modaleConfirmer.textContent = 'Enregistrement...';

    fetch('../Dos-php/traite_profil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action:      'modifier_infos',
            nom:         nom,
            prenom:      prenom,
            phone_email: phoneEmail,
            mdp_actuel:  mdpActuel
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {

        if(data.success) {
            // Fermer la modale
            overlay.classList.remove('actif');

            // Mettre à jour l'affichage en lecture seule
            document.getElementById('lecture-nom').textContent         = data.nom;
            document.getElementById('lecture-prenom').textContent      = data.prenom;
            document.getElementById('lecture-phone-email').textContent = data.phone_email;

            // Mettre à jour la carte avatar
            document.getElementById('profil-nom-complet').textContent       = data.prenom + ' ' + data.nom;
            document.getElementById('profil-phone-email-resume').textContent = data.phone_email;

            // Mettre à jour les initiales de l'avatar
            const initiales = (data.prenom.charAt(0) + data.nom.charAt(0)).toUpperCase();
            document.querySelector('.profil-avatar').textContent = initiales;

            // Repasser en mode lecture
            infosEdition.style.display  = 'none';
            infosLecture.style.display  = 'block';
            btnModifierInfos.style.display = 'flex';

            afficherMessage(messageInfos, 'Informations mises à jour avec succès.', 'succes');

        } else {
            afficherMessage(messageModale, data.message, 'erreur');
        }

    })
    .catch(function() {
        afficherMessage(messageModale, 'Erreur réseau. Veuillez réessayer.', 'erreur');
    })

    .finally(function() {
        modaleConfirmer.disabled    = false;
        modaleConfirmer.innerHTML   = '<i class="fa-solid fa-check"></i> Confirmer';
    });
})




// section changement du mot de passe 
btnEnregistrerMdp.addEventListener('click', function() {

    const mdpActuel   = document.getElementById('mdp-actuel').value.trim();
    const nouveauMdp  = document.getElementById('nouveau-mdp').value.trim();
    const confirmMdp  = document.getElementById('confirm-mdp').value.trim();

    // Validation côté client
    if (!mdpActuel || !nouveauMdp || !confirmMdp) {
        afficherMessage(messageMdp, 'Tous les champs sont obligatoires.', 'erreur');
        return;
    }

    if (nouveauMdp !== confirmMdp) {
        afficherMessage(messageMdp, 'Le nouveau mot de passe et la confirmation ne correspondent pas.', 'erreur');
        return;
    }

    if (nouveauMdp.length < 6) {
        afficherMessage(messageMdp, 'Le mot de passe doit contenir au moins 6 caractères.', 'erreur');
        return;
    }

    btnEnregistrerMdp.disabled    = true;
    btnEnregistrerMdp.textContent = 'Modification...';

    fetch('../Dos-php/traite_profil.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action:       'modifier_mdp',
            mdp_actuel:   mdpActuel,
            nouveau_mdp:  nouveauMdp,
            confirmation: confirmMdp
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {

        if (data.success) {

            // Vider les champs après succès
            document.getElementById('mdp-actuel').value  = '';
            document.getElementById('nouveau-mdp').value = '';
            document.getElementById('confirm-mdp').value = '';

            afficherMessage(messageMdp, data.message, 'succes');

        } else {
            afficherMessage(messageMdp, data.message, 'erreur');
        }

    })
    .catch(function() {
        afficherMessage(messageMdp, 'Erreur réseau. Veuillez réessayer.', 'erreur');
    })
    .finally(function() {
        btnEnregistrerMdp.disabled   = false;
        btnEnregistrerMdp.innerHTML  = '<i class="fa-solid fa-lock"></i> Changer le mot de passe';
    });

});


// section fonction d'affichage et maquage du mot de passe 
document.querySelectorAll('.profil-toggle-mdp').forEach(function(icone) {

    icone.addEventListener('click', function() {

        const cible = document.getElementById(this.dataset.cible);

        if (cible.type === 'password') {
            cible.type      = 'text';
            this.className  = this.className.replace('fa-eye', 'fa-eye-slash');
        } else {
            cible.type      = 'password';
            this.className  = this.className.replace('fa-eye-slash', 'fa-eye');
        }

    });

});


// section fonction d'affichage des messages
function afficherMessage(element, texte, type ) {

    element.textContent = texte;
    element.className   = 'profil-message profil-message-' + type;

    // Faire disparaître le message de succès après 4 secondes
    if (type === 'succes') {
        setTimeout(function() {
            element.textContent = '';
            element.className   = 'profil-message';
        }, 4000);
    }

}


