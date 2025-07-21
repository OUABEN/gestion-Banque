// Fonction pour confirmer les actions importantes
function confirmAction(message) {
    return confirm(message);
}

// Gestion des formulaires
document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire de virement
    const virementForm = document.getElementById('virement-form');
    if (virementForm) {
        virementForm.addEventListener('submit', function(e) {
            const montant = parseFloat(document.getElementById('montant').value);
            const solde = parseFloat(document.getElementById('solde_disponible').value);
            
            if (montant > solde) {
                e.preventDefault();
                alert('Le montant du virement ne peut pas dépasser votre solde disponible.');
            }
        });
    }
    
    // Affichage des détails du compte
    const accountCards = document.querySelectorAll('.account-card');
    accountCards.forEach(card => {
        card.addEventListener('click', function() {
            const accountId = this.dataset.accountId;
            window.location.href = `compte_details.php?id=${accountId}`;
        });
    });
    
    // Marquer les messages comme lus
    const messageItems = document.querySelectorAll('.message-item');
    messageItems.forEach(item => {
        item.addEventListener('click', function() {
            const messageId = this.dataset.messageId;
            fetch(`mark_as_read.php?id=${messageId}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.remove('unread');
                        const badge = document.querySelector('.badge');
                        if (badge) {
                            const count = parseInt(badge.textContent) - 1;
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                            }
                        }
                    }
                });
        });
    });
});