<?php
require_once 'config.php';
redirectIfNotLoggedIn();

// Récupérer les comptes du client
try {
    $stmt = $pdo->prepare("SELECT c.*, t.libelle as type_compte 
                          FROM comptes c 
                          JOIN types_compte t ON c.id_type = t.id_type 
                          WHERE c.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $comptes = $stmt->fetchAll();
    
    // Calculer le solde total
    $solde_total = 0;
    foreach ($comptes as $compte) {
        $solde_total += $compte['solde'];
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des comptes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Comptes - MS Banque</title>
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
        }

        .btn:hover {
            background: #6B1B3D;
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
        <div class="user-info">
            <div class="welcome-text">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?><a href="compte_details.php?id=<?php echo $_SESSION['user_id']; ?>"></a></div>
        </div>
    </header>

    <nav class="nav-menu">
        <a href="home.php" class="nav-link">Accueil</a>
        <a href="comptes.php" class="nav-link active">Comptes</a>
        <a href="transactions.php" class="nav-link">Transactions</a>
        <a href="virements.php" class="nav-link">Virements</a>
        <a href="messages.php" class="nav-link">Messages</span></a>
        <a href="logout.php" class="nav-link">Déconnexion</a>
    </nav>

    <div class="container">
        <!-- Carte de solde total -->
        <div class="card">
            <div class="transaction-header">
                <h3>Solde Total</h3>
                <div class="transaction-amount <?php echo ($solde_total >= 0) ? 'amount-positive' : 'amount-negative'; ?>">
                    <?php echo number_format($solde_total, 2, ',', ' '); ?> DHS
                </div>
            </div>
        </div>

        <!-- Liste des comptes -->
        <h2 style="color: white; margin: 20px 0 10px;">Vos Comptes</h2>
        
        <?php foreach ($comptes as $compte): ?>
        <div class="card transaction-card" onclick="window.location='compte_details.php?id=<?php echo $compte['id_compte']; ?>'">
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
        </div>
        <?php endforeach; ?>
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