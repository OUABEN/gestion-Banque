<?php
require_once 'config.php';
redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header("Location: comptes.php");
    exit();
}

$id_compte = sanitizeInput($_GET['id']);

// Vérifier que le compte appartient bien au client
try {
    $stmt = $pdo->prepare("SELECT c.*, t.libelle as type_compte 
                          FROM comptes c
                          JOIN types_compte t ON c.id_type = t.id_type
                          WHERE c.id_compte = ? AND c.id_client = ?");
    $stmt->execute([$id_compte, $_SESSION['user_id']]);
    $compte = $stmt->fetch();
    
    if (!$compte) {
        header("Location: comptes.php");
        exit();
    }
    
    // Récupérer les transactions de ce compte
    $stmt = $pdo->prepare("SELECT t.*, 
                          ce.numero_compte as compte_emetteur, 
                          cd.numero_compte as compte_destinataire 
                          FROM transactions t
                          LEFT JOIN comptes ce ON t.id_compte_emetteur = ce.id_compte
                          LEFT JOIN comptes cd ON t.id_compte_destinataire = cd.id_compte
                          WHERE t.id_compte_emetteur = ? OR t.id_compte_destinataire = ?
                          ORDER BY t.date_transaction DESC LIMIT 10");
    $stmt->execute([$id_compte, $id_compte]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données du compte: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Compte - MS Banque</title>
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

        .badge {
            background: #f44336;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        /* Transaction Card */
        .transaction-card {
            margin-bottom: 15px;
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .transaction-header h3 {
            color: #333;
            font-size: 18px;
        }

        .transaction-amount {
            font-size: 20px;
            font-weight: 700;
        }

        .amount-positive {
            color: #4CAF50;
        }

        .amount-negative {
            color: #f44336;
        }

        .transaction-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .transaction-details p {
            margin: 5px 0;
            font-size: 14px;
        }

        .transaction-details span:first-child {
            font-weight: 500;
            color: #666;
        }

        /* Buttons */
        .btn {
            background: #8B4B6B;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
            width: 100%;
        }

        .btn:hover {
            background: #6B1B3D;
            transform: translateY(-2px);
        }

        .btn-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
            }

            .nav-menu {
                justify-content: center;
            }

            .transaction-details {
                grid-template-columns: 1fr;
            }

            .btn-grid {
                grid-template-columns: 1fr;
            }

            body {
                padding: 10px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Transaction items */
        .transaction-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .transaction-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .transaction-content {
            flex: 1;
        }

        .transaction-title {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .transaction-date {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
        <div class="user-info">
            <div class="welcome-text">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
        </div>
    </header>

    <nav class="nav-menu">
        <a href="home.php" class="nav-link">Accueil</a>
        <a href="comptes.php" class="nav-link">Comptes</a>
        <a href="transactions.php" class="nav-link">Transactions</a>
        <a href="virements.php" class="nav-link">Virements</a>
        <a href="messages.php" class="nav-link">Messages</a>
        <a href="logout.php" class="nav-link">Déconnexion</a>
    </nav>

    <div class="container">
        <!-- Résumé du compte -->
        <div class="card">
            <div class="transaction-header">
                <h3><?php echo htmlspecialchars($compte['type_compte']); ?></h3>
                <div class="transaction-amount <?php echo ($compte['solde'] >= 0) ? 'amount-positive' : 'amount-negative'; ?>">
                    <?php echo number_format($compte['solde'], 2, ',', ' '); ?> DHS
                </div>
            </div>
            <div class="transaction-details">
                <p>
                    <span>Numéro de compte:</span>
                    <span><?php echo htmlspecialchars($compte['numero_compte']); ?></span>
                </p>
                <p>
                    <span>Date d'ouverture:</span>
                    <span><?php echo date('d/m/Y', strtotime($compte['date_ouverture'])); ?></span>
                </p>
                <p>
                    <span>Statut:</span>
                    <span class="<?php echo ($compte['statut'] === 'actif') ? 'amount-positive' : 'amount-negative'; ?>">
                        <?php echo htmlspecialchars(ucfirst($compte['statut'])); ?>
                    </span>
                </p>
            </div>

            <!-- Actions rapides -->
            <div class="btn-grid">
                <a style="text-decoration: none;" href="virements.php?from=<?php echo $id_compte; ?>" class="btn">Effectuer un virement</a>
                <a style="text-decoration: none;" href="#" class="btn" onclick="showNotification('Votre relevé sera disponible dans votre espace messages', 'info')">Demander un relevé</a>
            </div>
        </div>

        <!-- Historique des transactions -->
        <h2 style="color: white; margin: 20px 0 10px;">Dernières Transactions</h2>
        
        <?php if (empty($transactions)): ?>
            <div class="card">
                <p style="text-align: center;">Aucune transaction trouvée pour ce compte</p>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $transaction): ?>
                <?php 
                $is_negative = ($transaction['id_compte_emetteur'] == $id_compte || $transaction['type_transaction'] === 'retrait');
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
                    <div class="transaction-content">
                        <div class="transaction-title"><?php echo ucfirst($transaction['type_transaction']); ?></div>
                        <div class="transaction-date"><?php echo date('d/m/Y H:i', strtotime($transaction['date_transaction'])); ?></div>
                    </div>
                    <div class="transaction-amount <?php echo $is_negative ? 'amount-negative' : 'amount-positive'; ?>">
                        <?php echo ($is_negative ? '-' : '+') . number_format($transaction['montant'], 2, ',', ' '); ?> DHS
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

        // Transaction detail handler
        function showTransactionDetail(id) {
            showNotification('Chargement des détails de la transaction #' + id + '...', 'info');
            // Ici vous pourriez faire une requête AJAX pour plus de détails
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

        // Check for messages in URL
        window.addEventListener('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            const error = urlParams.get('error');
            
            if (success) {
                showNotification(success, 'success');
            }
            
            if (error) {
                showNotification(error, 'error');
            }
        });
    </script>
</body>
</html>