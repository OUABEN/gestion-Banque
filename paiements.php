<?php
require_once 'config.php';
redirectIfNotLoggedIn();

$error = '';
$success = '';

// Récupérer les comptes du client
try {
    $stmt = $pdo->prepare("SELECT * FROM comptes WHERE id_client = ? AND statut = 'actif'");
    $stmt->execute([$_SESSION['user_id']]);
    $comptes = $stmt->fetchAll();
    
    // Essayer de récupérer les services depuis la base de données
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE actif = 1");
        $stmt->execute();
        $services = $stmt->fetchAll();
        $use_db_services = true;
    } catch (PDOException $e) {
        // Si la table n'existe pas, utiliser des services prédéfinis
        $services = [
            ['id_service' => 1, 'nom' => 'Facture Eau', 'montant_fixe' => 0.00],
            ['id_service' => 2, 'nom' => 'Facture Électricité', 'montant_fixe' => 0.00],
            ['id_service' => 3, 'nom' => 'Abonnement Mobile', 'montant_fixe' => 150.00],
            ['id_service' => 4, 'nom' => 'Internet', 'montant_fixe' => 200.00],
            ['id_service' => 5, 'nom' => 'Taxe Municipale', 'montant_fixe' => 0.00]
        ];
        $use_db_services = false;
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}

// Traitement du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_compte = sanitizeInput($_POST['compte']);
    $id_service = sanitizeInput($_POST['service']);
    $montant = sanitizeInput($_POST['montant']);
    $reference = sanitizeInput($_POST['reference']);
    
    try {
        // Vérifier le solde du compte
        $stmt = $pdo->prepare("SELECT solde FROM comptes WHERE id_compte = ?");
        $stmt->execute([$id_compte]);
        $solde = $stmt->fetch()['solde'];
        
        // Vérifier le service
        if ($use_db_services) {
            $stmt = $pdo->prepare("SELECT montant_fixe FROM services WHERE id_service = ?");
            $stmt->execute([$id_service]);
            $service = $stmt->fetch();
        } else {
            $service = null;
            foreach ($services as $s) {
                if ($s['id_service'] == $id_service) {
                    $service = ['montant_fixe' => $s['montant_fixe']];
                    break;
                }
            }
        }
        
        // Si le service a un montant fixe, l'utiliser
        if ($service && $service['montant_fixe'] > 0) {
            $montant = $service['montant_fixe'];
        }
        
        if ($montant <= 0) {
            $error = "Le montant doit être positif.";
        } elseif ($solde < $montant) {
            $error = "Solde insuffisant pour effectuer ce paiement.";
        } else {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Débiter le compte
            $stmt = $pdo->prepare("UPDATE comptes SET solde = solde - ? WHERE id_compte = ?");
            $stmt->execute([$montant, $id_compte]);
            
            // Enregistrer la transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (id_compte_emetteur, montant, type_transaction, description) 
                                  VALUES (?, ?, 'paiement', ?)");
            $description = "Paiement service #$id_service - Ref: $reference";
            $stmt->execute([$id_compte, $montant, $description]);
            
            // Enregistrer le paiement (si la table existe)
            try {
                $stmt = $pdo->prepare("INSERT INTO paiements (id_client, id_service, id_compte, montant, reference) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $id_service, $id_compte, $montant, $reference]);
            } catch (PDOException $e) {
                // La table paiements n'existe peut-être pas, on continue sans erreur
            }
            
            // Valider la transaction
            $pdo->commit();
            
            $success = "Paiement effectué avec succès!";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors du paiement: " . $e->getMessage();
    }
}

// Récupérer les paiements récents (si la table existe)
$paiements = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, s.nom as service_nom 
                          FROM paiements p
                          JOIN services s ON p.id_service = s.id_service
                          WHERE p.id_client = ?
                          ORDER BY p.date_paiement DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $paiements = $stmt->fetchAll();
} catch (PDOException $e) {
    // La table n'existe pas, on utilise un tableau vide
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiements - Ma Banque</title>
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
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group select:focus, 
        .form-group input:focus {
            border-color: #8B4B6B;
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 75, 107, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

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

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            body {
                padding: 10px;
            }
        }

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
            <div class="welcome-text">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
        </div>
    </header>

    <nav class="nav-menu">
        <a href="home.php" class="nav-link">Accueil</a>
        <a href="comptes.php" class="nav-link">Comptes</a>
        <a href="transactions.php" class="nav-link">Transactions</a>
        <a href="virements.php" class="nav-link">Virements</a>
        <a href="paiements.php" class="nav-link active">Paiements</a>
        <a href="logout.php" class="nav-link">Déconnexion</a>
    </nav>

    <div class="page-header">
        <h1 class="page-title">Paiements en ligne</h1>
        <p class="page-subtitle">Payer vos factures et services en ligne</p>
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
        <h2 class="card-title">💳 Nouveau Paiement</h2>
        <form action="paiements.php" method="post" id="paiement-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="compte">Compte à débiter</label>
                    <select id="compte" name="compte" required>
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
                    <label for="service">Service</label>
                    <select id="service" name="service" required>
                        <option value="">Sélectionnez un service</option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['id_service']; ?>" 
                            data-montant="<?php echo $service['montant_fixe']; ?>">
                            <?php echo htmlspecialchars($service['nom']); ?>
                            <?php if ($service['montant_fixe'] > 0): ?>
                                (<?php echo number_format($service['montant_fixe'], 2, ',', ' '); ?> DH)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="montant">Montant (DH)</label>
                    <input type="number" id="montant" name="montant" min="0.01" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="reference">Référence</label>
                    <input type="text" id="reference" name="reference" placeholder="Numéro de facture ou référence" required>
                </div>
            </div>
            
            <button type="submit" class="btn">Effectuer le paiement</button>
        </form>
    </div>

    <div class="transactions-section">
        <div class="section-header">
            <h2 class="section-title">Vos Paiements Récents</h2>
        </div>
        
        <?php if (empty($paiements)): ?>
            <div class="empty-state">
                <p>Aucun paiement trouvé</p>
            </div>
        <?php else: ?>
            <?php foreach ($paiements as $paiement): ?>
            <div class="transaction-item" onclick="showPaiementDetail(<?php echo $paiement['id_paiement']; ?>)">
                <div class="transaction-icon">💳</div>
                <div class="transaction-details">
                    <div class="transaction-title">
                        <?php echo htmlspecialchars($paiement['service_nom']); ?>
                    </div>
                    <div class="transaction-info">
                        <div class="transaction-info-item">
                            <span>Date:</span> <?php echo date('d/m/Y H:i', strtotime($paiement['date_paiement'])); ?>
                        </div>
                        <div class="transaction-info-item">
                            <span>Référence:</span> <?php echo htmlspecialchars($paiement['reference']); ?>
                        </div>
                    </div>
                </div>
                <div class="transaction-amount">
                    -<?php echo number_format($paiement['montant'], 2, ',', ' '); ?> DH
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Gestion du changement de service
        document.getElementById('service').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fixedAmount = parseFloat(selectedOption.getAttribute('data-montant'));
            
            if (fixedAmount > 0) {
                document.getElementById('montant').value = fixedAmount.toFixed(2);
                document.getElementById('montant').readOnly = true;
            } else {
                document.getElementById('montant').value = '';
                document.getElementById('montant').readOnly = false;
            }
        });

        // Validation du formulaire
        document.getElementById('paiement-form').addEventListener('submit', function(e) {
            const montant = parseFloat(document.getElementById('montant').value);
            if (montant <= 0) {
                alert('Le montant doit être supérieur à zéro');
                e.preventDefault();
            }
        });

        function showPaiementDetail(id) {
            alert('Détails du paiement #' + id);
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            
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