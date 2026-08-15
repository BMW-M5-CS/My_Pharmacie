// ===================================================================
// VARIABLES GLOBALES
// ===================================================================

const btnModifierInfos   = document.getElementById("btn-modifier-infos");
const btnAnnulerInfos    = document.getElementById("btn-annuler-infos");
const btnEnregistrerInfo = document.getElementById("btn-enregistrer-infos");
const infosLecture       = document.getElementById("infos-lecture");
const infosEdition       = document.getElementById("infos-edition");
const messageInfos       = document.getElementById("message-infos");

const btnEnregistrerMdp = document.getElementById("btn-enregistrer-mdp");
const messageMdp        = document.getElementById("message-mdp");

const overlay               = document.getElementById("profil-overlay");
const modaleAnnuler         = document.getElementById("modale-annuler");
const modaleConfirmer       = document.getElementById("modale-confirmer");
const mdpConfirmationModale = document.getElementById("mdp-confirmation-modale");
const messageModale         = document.getElementById("message-modale");


// ===================================================================
// SECTION 1 — ÉDITION DES INFOS PERSONNELLES
// ===================================================================

btnModifierInfos.addEventListener("click", function() {

    infosLecture.style.display = "none";
    infosEdition.style.display = "block";

    btnModifierInfos.style.display = "none";

    messageInfos.textContent = "";
    messageInfos.className   = "profil-message";
});


// ----- Annulation de l'édition des infos personnelles -----

btnAnnulerInfos.addEventListener("click", function() {

    infosEdition.style.display = "none";
    infosLecture.style.display = "block";

    btnModifierInfos.style.display = "flex";

    document.getElementById('edit-nom').value                  = document.getElementById('lecture-nom').textContent.trim();
    document.getElementById('edit-prenom').value               = document.getElementById('lecture-prenom').textContent.trim();
    document.getElementById('edit-phone-email').value          = document.getElementById('lecture-phone-email').textContent.trim();
    document.getElementById('edit-contact-recuperation').value = (document.getElementById('lecture-contact-recuperation').textContent.trim() === 'Non renseigné') ? '' : document.getElementById('lecture-contact-recuperation').textContent.trim();

    messageInfos.textContent = "";
    messageInfos.className   = "profil-message";
});


// ----- Clic sur "Enregistrer" : ouverture du modal de confirmation -----

btnEnregistrerInfo.addEventListener("click", function() {

    const nom                 = document.getElementById('edit-nom').value.trim();
    const prenom               = document.getElementById('edit-prenom').value.trim();
    const phoneEmail          = document.getElementById('edit-phone-email').value.trim();
    const contactRecuperation = document.getElementById('edit-contact-recuperation').value.trim();

    // Validation rapide avant l'ouverture du modal
    if (!nom || !prenom || !phoneEmail) {
        afficherMessage(messageInfos, "Tous les champs sont obligatoires.", "erreur");
        return;
    }

    // Ouverture du modal de confirmation
    mdpConfirmationModale.value = "";
    messageModale.textContent   = "";
    messageModale.className     = "profil-message";
    overlay.classList.add("actif");
});


// ===================================================================
// SECTION 1BIS — ÉDITION DE L'ASSURANCE PAR DÉFAUT
// ===================================================================

const btnModifierAssurance   = document.getElementById("btn-modifier-assurance");
const btnAnnulerAssurance    = document.getElementById("btn-annuler-assurance");
const btnEnregistrerAssurance = document.getElementById("btn-enregistrer-assurance");
const assuranceLecture       = document.getElementById("assurance-lecture");
const assuranceEdition       = document.getElementById("assurance-edition");
const messageAssurance       = document.getElementById("message-assurance");
const champAssuranceDefaut   = document.getElementById("edit-assurance-defaut");

btnModifierAssurance.addEventListener("click", function() {

    assuranceLecture.style.display = "none";
    assuranceEdition.style.display = "block";

    btnModifierAssurance.style.display = "none";

    messageAssurance.textContent = "";
    messageAssurance.className   = "profil-message";
});


// ----- Sélection d'une chip d'assurance -----

document.querySelectorAll('.profil-chip-assurance').forEach(function (chip) {

    if (chip.dataset.nomAssurance === champAssuranceDefaut.value) {
        chip.classList.add('active');
    }

    chip.addEventListener('click', function () {

        document.querySelectorAll('.profil-chip-assurance').forEach(function (autre) {
            autre.classList.remove('active');
        });

        this.classList.add('active');
        champAssuranceDefaut.value = this.dataset.nomAssurance;
    });
});


// ----- Annulation de l'édition de l'assurance -----

btnAnnulerAssurance.addEventListener("click", function() {

    assuranceEdition.style.display = "none";
    assuranceLecture.style.display = "block";

    btnModifierAssurance.style.display = "flex";

    const valeurActuelle = document.getElementById('lecture-assurance-defaut').textContent.trim();
    const valeurReelle    = (valeurActuelle === 'Aucune') ? '' : valeurActuelle;

    champAssuranceDefaut.value = valeurReelle;

    document.querySelectorAll('.profil-chip-assurance').forEach(function (chip) {
        chip.classList.toggle('active', chip.dataset.nomAssurance === valeurReelle);
    });

    messageAssurance.textContent = "";
    messageAssurance.className   = "profil-message";
});


// ----- Enregistrement de l'assurance par défaut (pas de mot de passe requis : préférence à faible enjeu) -----

btnEnregistrerAssurance.addEventListener("click", function() {

    btnEnregistrerAssurance.disabled    = true;
    btnEnregistrerAssurance.textContent = 'Enregistrement...';

    fetch('../Dos-php/traite_profil.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action:            'modifier_assurance',
            csrf_token:         document.getElementById('csrf-token').value,
            assurance_defaut:   champAssuranceDefaut.value
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {

        if (data.success) {

            document.getElementById('lecture-assurance-defaut').textContent = data.assurance_defaut || 'Aucune';

            assuranceEdition.style.display     = 'none';
            assuranceLecture.style.display     = 'block';
            btnModifierAssurance.style.display = 'flex';

            afficherMessage(messageAssurance, 'Assurance mise à jour avec succès.', 'succes');

        } else {
            afficherMessage(messageAssurance, data.message, 'erreur');
        }
    })
    .catch(function() {
        afficherMessage(messageAssurance, 'Erreur réseau. Veuillez réessayer.', 'erreur');
    })
    .finally(function() {
        btnEnregistrerAssurance.disabled  = false;
        btnEnregistrerAssurance.innerHTML = '<i class="fa-solid fa-check"></i> Enregistrer';
    });
});


// ===================================================================
// SECTION 2 — MODALE DE CONFIRMATION
// ===================================================================

modaleAnnuler.addEventListener('click', function() {
    overlay.classList.remove('actif');
});

overlay.addEventListener('click', function(e) {

    if (e.target === overlay) {
        overlay.classList.remove('actif');
    }
});


// ----- Confirmation : envoi des modifications au backend -----

modaleConfirmer.addEventListener('click', function() {

    const mdpActuel = mdpConfirmationModale.value.trim();

    if (!mdpActuel) {
        afficherMessage(messageModale, 'Veuillez entrer votre mode passe.', 'erreur ');
        return;
    }

    const nom                 = document.getElementById('edit-nom').value.trim();
    const prenom              = document.getElementById('edit-prenom').value.trim();
    const phoneEmail          = document.getElementById('edit-phone-email').value.trim();
    const contactRecuperation = document.getElementById('edit-contact-recuperation').value.trim();

    modaleConfirmer.disabled    = true;
    modaleConfirmer.textContent = 'Enregistrement...';

    fetch('../Dos-php/traite_profil.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action:               'modifier_infos',
            csrf_token:            document.getElementById('csrf-token').value,
            nom:                   nom,
            prenom:                prenom,
            phone_email:           phoneEmail,
            contact_recuperation:  contactRecuperation,
            mdp_actuel:            mdpActuel
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {

        if (data.success) {

            // Fermer la modale
            overlay.classList.remove('actif');

            // Mettre à jour l'affichage en lecture seule
            document.getElementById('lecture-nom').textContent                  = data.nom;
            document.getElementById('lecture-prenom').textContent               = data.prenom;
            document.getElementById('lecture-phone-email').textContent          = data.phone_email;
            document.getElementById('lecture-contact-recuperation').textContent = data.contact_recuperation || 'Non renseigné';

            // Mettre à jour la carte avatar
            document.getElementById('profil-nom-complet').textContent        = data.prenom + ' ' + data.nom;
            document.getElementById('profil-phone-email-resume').textContent = data.phone_email;

            // Mettre à jour les initiales de l'avatar
            const initiales = (data.prenom.charAt(0) + data.nom.charAt(0)).toUpperCase();
            document.querySelector('.profil-avatar').textContent = initiales;

            // Repasser en mode lecture
            infosEdition.style.display     = 'none';
            infosLecture.style.display     = 'block';
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
        modaleConfirmer.disabled  = false;
        modaleConfirmer.innerHTML = '<i class="fa-solid fa-check"></i> Confirmer';
    });
});


// ===================================================================
// SECTION 3 — CHANGEMENT DU MOT DE PASSE
// ===================================================================

btnEnregistrerMdp.addEventListener('click', function() {

    const mdpActuel  = document.getElementById('mdp-actuel').value.trim();
    const nouveauMdp = document.getElementById('nouveau-mdp').value.trim();
    const confirmMdp = document.getElementById('confirm-mdp').value.trim();

    // Validation côté client
    if (!mdpActuel || !nouveauMdp || !confirmMdp) {
        afficherMessage(messageMdp, 'Tous les champs sont obligatoires.', 'erreur');
        return;
    }

    if (nouveauMdp !== confirmMdp) {
        afficherMessage(messageMdp, 'Le nouveau mot de passe et la confirmation ne correspondent pas.', 'erreur');
        return;
    }

    if (nouveauMdp.length < 8) {
        afficherMessage(messageMdp, 'Le mot de passe doit contenir au moins 8 caractères.', 'erreur');
        return;
    }

    btnEnregistrerMdp.disabled    = true;
    btnEnregistrerMdp.textContent = 'Modification...';

    fetch('../Dos-php/traite_profil.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action:       'modifier_mdp',
            csrf_token:   document.getElementById('csrf-token').value,
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
        btnEnregistrerMdp.disabled  = false;
        btnEnregistrerMdp.innerHTML = '<i class="fa-solid fa-lock"></i> Changer le mot de passe';
    });
});


// ===================================================================
// SECTION 4 — AFFICHAGE / MASQUAGE DU MOT DE PASSE
// ===================================================================

document.querySelectorAll('.profil-toggle-mdp').forEach(function(icone) {

    icone.addEventListener('click', function() {

        const cible = document.getElementById(this.dataset.cible);

        if (cible.type === 'password') {
            cible.type     = 'text';
            this.className = this.className.replace('fa-eye', 'fa-eye-slash');
        } else {
            cible.type     = 'password';
            this.className = this.className.replace('fa-eye-slash', 'fa-eye');
        }
    });
});


// ===================================================================
// SECTION 5 — AFFICHAGE DES MESSAGES
// ===================================================================

function afficherMessage(element, texte, type) {

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