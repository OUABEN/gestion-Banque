<?php
require_once 'config.php';
redirectIfNotLoggedIn();

$error = '';
$success = '';

// Récupérer les comptes du client et ses bénéficiaires
try {
    // Comptes du client
    $stmt = $pdo->prepare("SELECT * FROM comptes WHERE id_client = ? AND statut = 'actif'");
    $stmt->execute([$_SESSION['user_id']]);
    $comptes = $stmt->fetchAll();
    
    // Bénéficiaires
    $stmt = $pdo->prepare("SELECT b.*, c.numero_compte 
                          FROM beneficiaires b
                          JOIN comptes c ON b.id_compte_beneficiaire = c.id_compte
                          WHERE b.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $beneficiaires = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}

// Traitement du formulaire de virement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_compte_emetteur = sanitizeInput($_POST['compte_emetteur']);
    $id_compte_destinataire = sanitizeInput($_POST['compte_destinataire']);
    $montant = sanitizeInput($_POST['montant']);
    $description = sanitizeInput($_POST['description']);
    
    try {
        // Vérifier le solde du compte émetteur
        $stmt = $pdo->prepare("SELECT solde FROM comptes WHERE id_compte = ?");
        $stmt->execute([$id_compte_emetteur]);
        $solde = $stmt->fetch()['solde'];
        
        if ($montant <= 0) {
            $error = "Le montant doit être positif.";
        } elseif ($solde < $montant) {
            $error = "Solde insuffisant pour effectuer ce virement.";
        } else {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Débiter le compte émetteur
            $stmt = $pdo->prepare("UPDATE comptes SET solde = solde - ? WHERE id_compte = ?");
            $stmt->execute([$montant, $id_compte_emetteur]);
            
            // Créditer le compte destinataire
            $stmt = $pdo->prepare("UPDATE comptes SET solde = solde + ? WHERE id_compte = ?");
            $stmt->execute([$montant, $id_compte_destinataire]);
            
            // Enregistrer la transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (id_compte_emetteur, id_compte_destinataire, montant, type_transaction, description) 
                                  VALUES (?, ?, ?, 'virement', ?)");
            $stmt->execute([$id_compte_emetteur, $id_compte_destinataire, $montant, $description]);
            
            // Valider la transaction
            $pdo->commit();
            
            $success = "Virement effectué avec succès!";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors du virement: " . $e->getMessage();
    }
}

// Récupérer les virements récents
try {
    $stmt = $pdo->prepare("SELECT t.*, c.numero_compte as compte_dest 
                          FROM transactions t
                          JOIN comptes c ON t.id_compte_destinataire = c.id_compte
                          WHERE t.type_transaction = 'virement'
                          AND t.id_compte_emetteur IN (SELECT id_compte FROM comptes WHERE id_client = ?)
                          ORDER BY t.date_transaction DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $virements = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des virements: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virements - Ma Banque</title>
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

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #8B4B6B;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group select, 
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group select:focus, 
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #8B4B6B;
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 75, 107, 0.2);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Button Styles */
        .btn {
            background: #8B4B6B;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            background: #6B1B3D;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .link-btn {
            color: #8B4B6B;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .link-btn:hover {
            text-decoration: underline;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
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
            background: #E3F2FD;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
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

        .card,
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
        <a href="transactions.php" class="nav-link">Transactions</a>
        <a href="virements.php" class="nav-link active">Virements</a>
        <a href="logout.php" class="nav-link">Déconnexion</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Effectuer un Virement</h1>
        <p class="page-subtitle">Transférer de l'argent vers vos bénéficiaires</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 class="card-title">📤 Nouveau Virement</h2>
        <form action="virements.php" method="post" id="virement-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="compte_emetteur">Compte émetteur</label>
                    <select id="compte_emetteur" name="compte_emetteur" required>
                        <option value="">Sélectionnez un compte</option>
                        <?php foreach ($comptes as $compte): ?>
                        <option value="<?php echo $compte['id_compte']; ?>">
                            <?php echo htmlspecialchars($compte['numero_compte']); ?> - 
                            Solde: <?php echo number_format($compte['solde'], 2, ',', ' '); ?> DH
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="compte_destinataire">Compte destinataire</label>
                    <select id="compte_destinataire" name="compte_destinataire" required>
                        <option value="">Sélectionnez un bénéficiaire</option>
                        <?php foreach ($beneficiaires as $beneficiaire): ?>
                        <option value="<?php echo $beneficiaire['id_compte_beneficiaire']; ?>">
                            <?php echo htmlspecialchars($beneficiaire['nom']); ?> - 
                            <?php echo htmlspecialchars($beneficiaire['numero_compte']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="ajouter_beneficiaire.php" class="link-btn">
                        <span>+</span> Ajouter un bénéficiaire
                    </a>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="montant">Montant (DH)</label>
                    <input type="number" id="montant" name="montant" min="0.01" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" placeholder="Motif du virement">
                </div>
            </div>
            
            <button type="submit" class="btn">Effectuer le virement</button>
        </form>
    </div>

    <div class="transactions-section">
        <div class="section-header">
            <h2 class="section-title">Vos Virements Récents</h2>
        </div>
        
        <?php if (empty($virements)): ?>
            <div class="empty-state">
                <p>Aucun virement trouvé</p>
            </div>
        <?php else: ?>
            <?php foreach ($virements as $virement): ?>
            <div class="transaction-item" onclick="showTransactionDetail(<?php echo $virement['id_transaction']; ?>)">
                <div class="transaction-icon">📤</div>
                <div class="transaction-details">
                    <div class="transaction-title">
                        Virement vers <?php echo htmlspecialchars($virement['compte_dest']); ?>
                    </div>
                    <div class="transaction-info">
                        <div class="transaction-info-item">
                            <span>Date:</span> <?php echo date('d/m/Y H:i', strtotime($virement['date_transaction'])); ?>
                        </div>
                        <?php if (!empty($virement['description'])): ?>
                        <div class="transaction-info-item">
                            <span>Motif:</span> <?php echo htmlspecialchars($virement['description']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="transaction-amount">
                    -<?php echo number_format($virement['montant'], 2, ',', ' '); ?> DH
                    <span class="transaction-status status-complete">
                        <?php echo ucfirst($virement['statut']); ?>
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
            showNotification('Chargement des détails du virement #' + id + '...', 'info');
        }

        // Form validation
        document.getElementById('virement-form').addEventListener('submit', function(e) {
            const montant = parseFloat(document.getElementById('montant').value);
            if (montant <= 0) {
                showNotification('Le montant doit être supérieur à zéro', 'warning');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>