<?php
require_once 'config.php';
redirectIfNotLoggedIn();

// Récupérer les informations du client
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();
    
    // Récupérer les comptes du client
    $stmt = $pdo->prepare("SELECT c.*, t.libelle as type_compte FROM comptes c JOIN types_compte t ON c.id_type = t.id_type WHERE c.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $comptes = $stmt->fetchAll();
    
    // Calculer le solde total
    $solde_total = 0;
    foreach ($comptes as $compte) {
        $solde_total += $compte['solde'];
    }
    
    // Récupérer les dernières transactions
    $stmt = $pdo->prepare("SELECT t.*, 
                          ce.numero_compte as compte_emetteur, 
                          cd.numero_compte as compte_destinataire 
                          FROM transactions t
                          LEFT JOIN comptes ce ON t.id_compte_emetteur = ce.id_compte
                          LEFT JOIN comptes cd ON t.id_compte_destinataire = cd.id_compte
                          WHERE t.id_compte_emetteur IN (SELECT id_compte FROM comptes WHERE id_client = ?)
                          OR t.id_compte_destinataire IN (SELECT id_compte FROM comptes WHERE id_client = ?)
                          ORDER BY t.date_transaction DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $transactions = $stmt->fetchAll();
    
    // Récupérer les messages non lus
    $stmt = $pdo->prepare("SELECT COUNT(*) as nb_messages_non_lus FROM messages WHERE id_client = ? AND lu = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $messages_non_lus = $stmt->fetch()['nb_messages_non_lus'];
    
    // Statistiques
    $stmt = $pdo->prepare("SELECT 
                          SUM(CASE WHEN type_transaction = 'virement' THEN 1 ELSE 0 END) as virements,
                          SUM(CASE WHEN type_transaction = 'paiement' THEN 1 ELSE 0 END) as paiements
                          FROM transactions
                          WHERE id_compte_emetteur IN (SELECT id_compte FROM comptes WHERE id_client = ?)
                          AND date_transaction >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
    
    $total_transactions = count($transactions);
    
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Banque - Tableau de Bord</title>
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
            cursor: pointer;
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .nav-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: rgba(139, 75, 107, 0.7);
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(139, 75, 107, 1);
            transform: translateY(-2px);
        }

        /* Account Balance Card */
        .balance-card {
            background: linear-gradient(135deg, #2C5F41 0%, #1B3B28 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .balance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        }

        .balance-title {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .balance-amount {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .balance-subtitle {
            font-size: 12px;
            opacity: 0.8;
        }

        /* Statistics Card */
        .stats-card {
            background: linear-gradient(135deg, #6B1B3D 0%, #4A1228 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stats-title {
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .stat-label {
            font-size: 10px;
            opacity: 0.8;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 12px;
            font-weight: 600;
        }

        /* Recent Transactions */
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

        .view-all-btn {
            background: #8B4B6B;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: #6B1B3D;
            transform: translateY(-1px);
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
            color: white;
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

        .transaction-date {
            font-size: 12px;
            color: #666;
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

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .service-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(139, 75, 107, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .service-card:hover::before {
            left: 100%;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .service-icon {
            font-size: 40px;
            margin-bottom: 15px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .service-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .service-description {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .service-btn {
            background: #8B4B6B;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .service-btn:hover {
            background: #6B1B3D;
        }

        /* Navigation */
        .nav-menu {
            display: flex;
            gap: 15px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .badge {
            background: #f44336;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 10px;
            }

            body {
                padding: 10px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
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

        .balance-card,
        .stats-card,
        .transactions-section,
        .service-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .service-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .service-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            font-weight: 500;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
            max-width: 350px;
            word-wrap: break-word;
        }

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
           .nav-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: rgba(139, 75, 107, 0.7);
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(139, 75, 107, 1);
            transform: translateY(-2px);
        }

    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
        <div class="user-info">
            <div class="welcome-text">Bienvenue, <?php echo htmlspecialchars($client['prenom']); ?></div>
            <div class="user-avatar"><?php echo strtoupper(substr($client['prenom'], 0, 1)); ?></div>
        </div>
    </header>

    <nav class="nav-menu">
        <a href="home.php" class="nav-link active">Tableau de bord</a>
        <a href="comptes.php" class="nav-link">Mes comptes</a>
        <a href="transactions.php" class="nav-link">Transactions</a>
        <a href="virements.php" class="nav-link">Virements</a>
        <a href="messages.php" class="nav-link">Messages <?php if ($messages_non_lus > 0): ?><span class="badge"><?php echo $messages_non_lus; ?></span><?php endif; ?></a>
        <a href="logout.php" class="nav-link">Déconnexion</a>
    </nav>

    <div class="main-grid">
        <div class="balance-card">
            <div class="balance-title">
                💳 Solde Total
            </div>
            <div class="balance-amount"><?php echo number_format($solde_total, 2, ',', ' '); ?> DH</div>
            <div class="balance-subtitle">Sur <?php echo count($comptes); ?> compte(s)</div>
        </div>

        <div class="stats-card">
            <div class="stats-title">
                📊 Statistiques du mois
            </div>
            <div class="stats-grid">
                <div class="stat-item" onclick="showStatDetail('transactions')">
                    <div class="stat-label">Transactions</div>
                    <div class="stat-value"><?php echo $total_transactions; ?></div>
                </div>
                <div class="stat-item" onclick="showStatDetail('virements')">
                    <div class="stat-label">Virements</div>
                    <div class="stat-value"><?php echo $stats['virements'] ?? 0; ?></div>
                </div>
                <div class="stat-item" onclick="showStatDetail('paiements')">
                    <div class="stat-label">Paiements</div>
                    <div class="stat-value"><?php echo $stats['paiements'] ?? 0; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="transactions-section">
        <div class="section-header">
            <h2 class="section-title">Dernières Transactions</h2>
            <button class="view-all-btn" onclick="window.location.href='transactions.php'">Voir tout</button>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="transaction-item">
                <div class="transaction-details">
                    <div class="transaction-title">Aucune transaction récente</div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $transaction): ?>
                <?php 
                $is_negative = ($transaction['id_compte_emetteur'] == $transaction['id_compte_destinataire'] || $transaction['type_transaction'] === 'retrait');
                $icon_bg = $is_negative ? '#FFEBEE' : '#E8F5E9';
                $icon = $is_negative ? '➖' : '➕';
                
                if ($transaction['type_transaction'] === 'depot') {
                    $icon = '💰';
                    $icon_bg = '#E3F2FD';
                } elseif ($transaction['type_transaction'] === 'virement') {
                    $icon = '💸';
                    $icon_bg = '#FFF3E0';
                } elseif ($transaction['type_transaction'] === 'paiement') {
                    $icon = '💳';
                    $icon_bg = '#F3E5F5';
                }
                ?>
                <div class="transaction-item" onclick="showTransactionDetail(<?php echo $transaction['id_transaction']; ?>)">
                    <div class="transaction-icon" style="background: <?php echo $icon_bg; ?>"><?php echo $icon; ?></div>
                    <div class="transaction-details">
                        <div class="transaction-title"><?php echo ucfirst($transaction['type_transaction']); ?></div>
                        <div class="transaction-date"><?php echo date('d/m/Y H:i', strtotime($transaction['date_transaction'])); ?></div>
                    </div>
                    <div class="transaction-amount <?php echo $is_negative ? 'amount-negative' : 'amount-positive'; ?>">
                        <?php echo ($is_negative ? '-' : '+') . number_format($transaction['montant'], 2, ',', ' '); ?> DH
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="services-grid">
        <div class="service-card" onclick="window.location.href='virements.php'">
            <div class="service-icon">📤</div>
            <div class="service-title">Virements</div>
            <div class="service-description">Effectuer des virements nationaux et internationaux</div>
            <button class="service-btn">Nouveau Virement</button>
        </div>

        <div class="service-card" onclick="window.location.href='paiements.php'">
            <div class="service-icon">💳</div>
            <div class="service-title">Paiements</div>
            <div class="service-description">Payer vos factures et services en ligne</div>
            <button class="service-btn">Effectuer un Paiement</button>
        </div>

        <div class="service-card" onclick="window.location.href='comptes.php'">
            <div class="service-icon">💰</div>
            <div class="service-title">Épargne</div>
            <div class="service-description">Gérer vos comptes d'épargne et investissements</div>
            <button class="service-btn">Gérer l'Épargne</button>
        </div>
    </div>

    <script>
        // Transaction detail handler
        function showTransactionDetail(id) {
            // Ici vous pourriez faire une requête AJAX pour plus de détails
            // Pour l'exemple, nous allons simplement montrer une notification
            showNotification('Chargement des détails de la transaction #' + id + '...', 'info');
        }

        // Statistics detail handler
        function showStatDetail(stat) {
            const statDetails = {
                'transactions': 'Total des transactions ce mois: <?php echo $total_transactions; ?> opérations',
                'virements': 'Virements effectués ce mois: <?php echo $stats['virements'] ?? 0; ?> opérations',
                'paiements': 'Paiements effectués ce mois: <?php echo $stats['paiements'] ?? 0; ?> factures'
            };
            
            showNotification(statDetails[stat], 'info');
        }

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
                case 'error':
                    notification.style.background = 'linear-gradient(135deg, #f44336, #da190b)';
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

        // Welcome message on load
        window.addEventListener('load', () => {
            setTimeout(() => {
                showNotification('Bienvenue dans votre espace bancaire, <?php echo htmlspecialchars($client['prenom']); ?>!', 'success');
            }, 1000);
        });

        // Real-time balance animation
        function animateBalance() {
            const balanceElement = document.querySelector('.balance-amount');
            const currentBalance = <?php echo $solde_total; ?>;
            let displayBalance = 0;
            const increment = currentBalance / 50;
            
            const animation = setInterval(() => {
                displayBalance += increment;
                if (displayBalance >= currentBalance) {
                    displayBalance = currentBalance;
                    clearInterval(animation);
                }
                balanceElement.textContent = displayBalance.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DH';
            }, 30);
        }

        // Start balance animation on load
        window.addEventListener('load', () => {
            setTimeout(animateBalance, 500);
        });
    </script>
</body>
</html>