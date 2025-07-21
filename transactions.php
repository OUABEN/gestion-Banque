<?php
require_once 'config.php';
redirectIfNotLoggedIn();

// Récupérer les transactions
try {
    $stmt = $pdo->prepare("SELECT t.*, 
                          ce.numero_compte as compte_emetteur, 
                          cd.numero_compte as compte_destinataire 
                          FROM transactions t
                          LEFT JOIN comptes ce ON t.id_compte_emetteur = ce.id_compte
                          LEFT JOIN comptes cd ON t.id_compte_destinataire = cd.id_compte
                          WHERE t.id_compte_emetteur IN (SELECT id_compte FROM comptes WHERE id_client = ?)
                          OR t.id_compte_destinataire IN (SELECT id_compte FROM comptes WHERE id_client = ?)
                          ORDER BY t.date_transaction DESC");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des transactions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Ma Banque</title>
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

        /* Navigation */
        .nav-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 20px;
        }

        .page-title {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        /* Filter Card */
        .filter-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .filter-title {
            font-size: 16px;
            font-weight: 600;
            color: #8B4B6B;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-title::before {
            content: "🔍";
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .form-group select, 
        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .filter-btn {
            grid-column: span 2;
            background: #8B4B6B;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: #6B1B3D;
            transform: translateY(-2px);
        }

        /* Transactions List */
        .transactions-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
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

        .transaction-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .transaction-item:hover {
            background: #f9f9f9;
            margin: 0 -25px;
            padding: 15px 25px;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }

        .transaction-details {
            flex: 1;
        }

        .transaction-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .transaction-info {
            display: flex;
            gap: 15px;
        }

        .transaction-info-item {
            font-size: 12px;
            color: #666;
        }

        .transaction-info-item span {
            font-weight: 600;
            color: #444;
        }

        .transaction-amount {
            font-size: 14px;
            font-weight: 600;
        }

        .amount-positive {
            color: #4CAF50;
        }

        .amount-negative {
            color: #f44336;
        }

        .transaction-status {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 10px;
        }

        .status-complete {
            background: #E8F5E8;
            color: #4CAF50;
        }

        .status-pending {
            background: #FFF3E0;
            color: #FF9800;
        }

        .status-failed {
            background: #FFEBEE;
            color: #f44336;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .filter-btn {
                grid-column: span 1;
            }
            
            .header {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            body {
                padding: 10px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filter-card,
        .transactions-section {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
        <div class="user-info">
            <div class="welcome-text">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_id']); ?></div>
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_id'], 0, 1)); ?></div>
        </div>
    </header>

    <div class="nav-menu">
        <a href="home.php" class="nav-link">Accueil</a>
        <a href="comptes.php" class="nav-link">Comptes</a>
        <a href="transactions.php" class="nav-link active">Transactions</a>
        <a href="virements.php" class="nav-link">Virements</a>
        <a href="logout.php" class="nav-link">Déconnexion</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Historique des Transactions</h1>
        <p class="page-subtitle">Toutes vos opérations financières en un seul endroit</p>
    </div>

    <div class="filter-card">
        <h3 class="filter-title">Filtrer les transactions</h3>
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="type">Type de transaction</label>
                <select id="type" name="type">
                    <option value="">Tous les types</option>
                    <option value="depot">Dépôt</option>
                    <option value="retrait">Retrait</option>
                    <option value="virement">Virement</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date">Période</label>
                <select id="date" name="date">
                    <option value="">Toutes les dates</option>
                    <option value="today">Aujourd'hui</option>
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                </select>
            </div>
            <button type="submit" class="filter-btn">Appliquer les filtres</button>
        </form>
    </div>

    <div class="transactions-section">
        <div class="section-header">
            <h2 class="section-title">Vos Transactions</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <p>Aucune transaction trouvée</p>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $transaction): 
                $isCredit = ($transaction['type_transaction'] === 'depot' || 
                            ($transaction['type_transaction'] === 'virement' && 
                             $transaction['id_compte_emetteur'] != $transaction['id_compte_destinataire']));
                
                $icon = '';
                $iconBg = '';
                if ($transaction['type_transaction'] === 'depot') {
                    $icon = '💰';
                    $iconBg = '#E8F5E8';
                } elseif ($transaction['type_transaction'] === 'retrait') {
                    $icon = '🏧';
                    $iconBg = '#FFF3E0';
                } elseif ($transaction['type_transaction'] === 'virement') {
                    $icon = $isCredit ? '💸' : '📤';
                    $iconBg = $isCredit ? '#E3F2FD' : '#F3E5F5';
                }
                
                $statusClass = 'status-' . $transaction['statut'];
            ?>
            <div class="transaction-item" onclick="showTransactionDetail(<?php echo $transaction['id_transaction']; ?>)">
                <div class="transaction-icon" style="background: <?php echo $iconBg; ?>"><?php echo $icon; ?></div>
                <div class="transaction-details">
                    <div class="transaction-title">
                        <?php echo ucfirst($transaction['type_transaction']); ?>
                        <?php if ($transaction['type_transaction'] === 'virement'): ?>
                            - <?php echo $isCredit ? 'Reçu de ' . htmlspecialchars($transaction['compte_emetteur']) : 'Envoyé à ' . htmlspecialchars($transaction['compte_destinataire']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="transaction-info">
                        <div class="transaction-info-item">
                            <span>Date:</span> <?php echo date('d/m/Y H:i', strtotime($transaction['date_transaction'])); ?>
                        </div>
                        <?php if (!empty($transaction['description'])): ?>
                        <div class="transaction-info-item">
                            <span>Motif:</span> <?php echo htmlspecialchars($transaction['description']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="transaction-amount <?php echo $isCredit ? 'amount-positive' : 'amount-negative'; ?>">
                    <?php echo $isCredit ? '+' : '-'; ?>
                    <?php echo number_format($transaction['montant'], 2, ',', ' '); ?> DH
                    <span class="transaction-status <?php echo $statusClass; ?>">
                        <?php echo ucfirst($transaction['statut']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Notification system
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existing = document.querySelector('.notification');
            if (existing) {
                existing.remove();
            }

            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            
            // Style the notification
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
                max-width: 350px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                word-wrap: break-word;
            `;
            
            // Set background color based on type
            switch(type) {
                case 'success':
                    notification.style.background = 'linear-gradient(135deg, #4CAF50, #45a049)';
                    break;
                case 'info':
                    notification.style.background = 'linear-gradient(135deg, #2196F3, #0b7dda)';
                    break;
                case 'warning':
                    notification.style.background = 'linear-gradient(135deg, #FF9800, #F57C00)';
                    break;
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOut 0.3s ease-in';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 4000);
        }

        // Add CSS for notification animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        function showTransactionDetail(id) {
            // In a real app, this would fetch details from the server
            showNotification('Chargement des détails de la transaction #' + id + '...', 'info');
        }
    </script>
</body>
</html>