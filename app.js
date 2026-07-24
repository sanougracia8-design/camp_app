document.addEventListener('DOMContentLoaded', function() {
    // Auto-fermeture des alertes après 5s
    document.querySelectorAll('.alert').forEach(function(a) {
        setTimeout(function() {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(function() { a.remove(); }, 500);
        }, 5000);
    });
});
