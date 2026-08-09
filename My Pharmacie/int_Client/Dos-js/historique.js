
document.querySelectorAll('.btn-voir-produit-historique').forEach(function (btn) {

    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        window.location.href = '../../int_Public/Dos-page/produit.php?ouvrir=' + id;
    });
    
});