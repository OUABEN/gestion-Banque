<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Ma Banque</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #8B4B6B 0%, #C9A4B5 50%, #E8D4DC 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(139, 75, 107, 0.9);
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .logo {
            color: white;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .logo::before {
            content: "🏠";
            margin-right: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome-text {
            color: white;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #8B4B6B;
        }

        /* Messages Container */
        .messages-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .new-message-btn {
            background: #8B4B6B;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .new-message-btn:hover {
            background: #6B1B3D;
            transform: translateY(-1px);
        }

        /* Message List */
        .message-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message-item {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .message-item.unread {
            background: #F9F0F5;
            border-left: 4px solid #8B4B6B;
        }

        .message-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .message-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #E8D4DC;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #8B4B6B;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .message-sender {
            font-weight: 600;
            color: #333;
        }

        .message-date {
            font-size: 12px;
            color: #888;
        }

        .message-subject {
            font-weight: 500;
            margin-bottom: 5px;
            color: #444;
        }

        .message-preview {
            font-size: 13px;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .no-messages {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 16px;
        }

        /* Message Detail Modal */
        .message-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .message-detail {
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }

        .message-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .message-detail-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #888;
        }

        .message-detail-sender {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .message-detail-date {
            font-size: 14px;
            color: #888;
            margin-bottom: 15px;
        }

        .message-detail-content {
            line-height: 1.6;
            color: #444;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .message-action-btn {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .reply-btn {
            background: #8B4B6B;
            color: white;
        }

        .delete-btn {
            background: #f5f5f5;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
            }

            body {
                padding: 10px;
            }

            .message-detail {
                width: 95%;
                padding: 15px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .message-item {
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
        <div class="user-info">
            <div class="welcome-text">Bienvenue chez Banque!</div>
            <div class="user-avatar">U</div>
        </div>
    </header>

    <div class="messages-container">
        <div class="section-header">
            <h2 class="section-title">Messages</h2>
            <button class="new-message-btn" onclick="showNewMessageForm()">Nouveau Message</button>
        </div>
        
        <div class="message-list">
            <!-- Example messages - in a real app these would come from your PHP backend -->
            <div class="message-item unread" onclick="showMessageDetail('urgent')">
                <div class="message-icon">📢</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">Service Client</span>
                        <span class="message-date">Aujourd'hui, 10:30</span>
                    </div>
                    <div class="message-subject">Mise à jour importante de sécurité</div>
                    <div class="message-preview">Nous vous informons d'une importante mise à jour de sécurité concernant votre compte. Veuillez lire attentivement ces informations...</div>
                </div>
            </div>

            <div class="message-item" onclick="showMessageDetail('transaction')">
                <div class="message-icon">💳</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">Système de Notification</span>
                        <span class="message-date">Hier, 14:45</span>
                    </div>
                    <div class="message-subject">Transaction effectuée</div>
                    <div class="message-preview">Une transaction de 315.50 DH a été effectuée sur votre compte le 14/03/2025 chez SUPERMARCHE MARJANE...</div>
                </div>
            </div>

            <div class="message-item" onclick="showMessageDetail('newsletter')">
                <div class="message-icon">📰</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">Équipe Ma Banque</span>
                        <span class="message-date">12/03/2025</span>
                    </div>
                    <div class="message-subject">Votre newsletter mensuelle</div>
                    <div class="message-preview">Découvrez les nouvelles fonctionnalités de notre application mobile et nos conseils pour bien gérer votre épargne ce mois-ci...</div>
                </div>
            </div>

            <div class="message-item" onclick="showMessageDetail('promo')">
                <div class="message-icon">🎁</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">Promotions</span>
                        <span class="message-date">05/03/2025</span>
                    </div>
                    <div class="message-subject">Offre exclusive pour vous</div>
                    <div class="message-preview">Profitez d'une offre exceptionnelle sur notre nouveau produit d'épargne avec un taux avantageux de 5% pendant 3 mois...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Detail Modal -->
    <div class="message-modal" id="messageModal">
        <div class="message-detail">
            <div class="message-detail-header">
                <h3 class="message-detail-title" id="messageDetailTitle">Mise à jour importante de sécurité</h3>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <div class="message-detail-sender" id="messageDetailSender">Service Client</div>
            <div class="message-detail-date" id="messageDetailDate">Aujourd'hui, 10:30</div>
            <div class="message-detail-content" id="messageDetailContent">
                <p>Cher client,</p>
                <p>Nous vous informons d'une importante mise à jour de sécurité concernant votre compte. À partir du 1er avril 2025, nous renforçons nos protocoles d'authentification pour mieux protéger vos données.</p>
                <p>Vous devrez peut-être mettre à jour votre application mobile et confirmer votre identité lors de votre prochaine connexion. Ces mesures supplémentaires nous aideront à prévenir les activités frauduleuses.</p>
                <p>Si vous avez des questions, n'hésitez pas à contacter notre service client au 0800 123 456.</p>
                <p>Cordialement,<br>L'équipe Ma Banque</p>
            </div>
            <div class="message-actions">
                <button class="message-action-btn reply-btn" onclick="replyToMessage()">Répondre</button>
                <button class="message-action-btn delete-btn" onclick="deleteMessage()">Supprimer</button>
            </div>
        </div>
    </div>

    <script>
        // Show message detail
        function showMessageDetail(type) {
            const modal = document.getElementById('messageModal');
            const messages = {
                'urgent': {
                    title: 'Mise à jour importante de sécurité',
                    sender: 'Service Client',
                    date: 'Aujourd\'hui, 10:30',
                    content: `<p>Cher client,</p>
                              <p>Nous vous informons d'une importante mise à jour de sécurité concernant votre compte. À partir du 1er avril 2025, nous renforçons nos protocoles d'authentification pour mieux protéger vos données.</p>
                              <p>Vous devrez peut-être mettre à jour votre application mobile et confirmer votre identité lors de votre prochaine connexion. Ces mesures supplémentaires nous aideront à prévenir les activités frauduleuses.</p>
                              <p>Si vous avez des questions, n'hésitez pas à contacter notre service client au 0800 123 456.</p>
                              <p>Cordialement,<br>L'équipe Ma Banque</p>`
                },
                'transaction': {
                    title: 'Transaction effectuée',
                    sender: 'Système de Notification',
                    date: 'Hier, 14:45',
                    content: `<p>Transaction effectuée sur votre compte:</p>
                              <ul>
                                <li><strong>Montant:</strong> 315.50 DH</li>
                                <li><strong>Commerçant:</strong> SUPERMARCHE MARJANE</li>
                                <li><strong>Date:</strong> 14/03/2025 à 14:32</li>
                                <li><strong>Solde actuel:</strong> 24,684.50 DH</li>
                              </ul>
                              <p>Si vous ne reconnaissez pas cette transaction, veuillez immédiatement contacter notre service sécurité au 0800 123 789.</p>`
                },
                'newsletter': {
                    title: 'Votre newsletter mensuelle',
                    sender: 'Équipe Ma Banque',
                    date: '12/03/2025',
                    content: `<p>Bonjour,</p>
                              <p>Voici les principales actualités de ce mois:</p>
                              <h4>Nouvelle fonctionnalité: Virements instantanés</h4>
                              <p>Désormais, effectuez des virements qui arrivent en quelques secondes chez la plupart des banques marocaines.</p>
                              <h4>Conseil du mois: Optimisez votre épargne</h4>
                              <p>Avec les taux actuels, pensez à diversifier vos placements entre comptes sur livret et fonds d'investissement.</p>
                              <h4>Sécurité renforcée</h4>
                              <p>Nous avons ajouté la vérification en deux étapes pour toutes les opérations sensibles.</p>
                              <p>Bonne lecture!</p>`
                },
                'promo': {
                    title: 'Offre exclusive pour vous',
                    sender: 'Promotions',
                    date: '05/03/2025',
                    content: `<p>Cher client,</p>
                              <p>En tant que client privilégié, nous vous proposons une offre exceptionnelle sur notre nouveau produit d'épargne "Épargne Plus":</p>
                              <ul>
                                <li>Taux avantageux de 5% pendant 3 mois</li>
                                <li>Pas de frais de gestion</li>
                                <li>Retraits disponibles à tout moment</li>
                              </ul>
                              <p>Cette offre est valable jusqu'au 31/03/2025. Pour en bénéficier, rendez-vous dans votre espace client et cliquez sur "Épargne".</p>
                              <p>Cordialement,<br>Votre conseiller financier</p>`
                }
            };

            document.getElementById('messageDetailTitle').textContent = messages[type].title;
            document.getElementById('messageDetailSender').textContent = messages[type].sender;
            document.getElementById('messageDetailDate').textContent = messages[type].date;
            document.getElementById('messageDetailContent').innerHTML = messages[type].content;

            modal.style.display = 'flex';
            
            // Mark as read
            const messageItem = event.currentTarget;
            messageItem.classList.remove('unread');
        }

        // Close modal
        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        // Reply to message
        function replyToMessage() {
            alert('Fonctionnalité de réponse sera implémentée ici');
            closeModal();
        }

        // Delete message
        function deleteMessage() {
            if (confirm('Voulez-vous vraiment supprimer ce message?')) {
                alert('Message supprimé');
                closeModal();
            }
        }

        // New message form
        function showNewMessageForm() {
            alert('Formulaire de nouveau message sera affiché ici');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>