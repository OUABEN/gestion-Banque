<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitizeInput($_POST['nom']);
    $prenom = sanitizeInput($_POST['prenom']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    $date_naissance = sanitizeInput($_POST['date_naissance']);
    $adresse = sanitizeInput($_POST['adresse']);
    $ville = sanitizeInput($_POST['ville']);
    $code_postal = sanitizeInput($_POST['code_postal']);
    $telephone = sanitizeInput($_POST['telephone']);
    
    // Validation
    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id_client FROM clients WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Hasher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer le nouveau client
                $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, email, mot_de_passe, date_naissance, adresse, ville, code_postal, telephone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email, $hashed_password, $date_naissance, $adresse, $ville, $code_postal, $telephone]);
                
                $success = "Inscription réussie! Vous pouvez maintenant vous connecter.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'inscription: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Banque - Inscription</title>
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
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(139, 75, 107, 0.8);
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            font-size: 18px;
            font-weight: 500;
        }

        .logo::before {
            content: "🏠";
            margin-right: 10px;
            font-size: 20px;
        }

        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .header-btn {
            background: rgba(139, 75, 107, 0.9);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .header-btn:hover {
            background: rgba(139, 75, 107, 1);
            transform: translateY(-1px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            animation: fadeInUp 0.6s ease-out;
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

        .register-title {
            text-align: center;
            color: #4A4A4A;
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4A4A4A;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #F8F9FA;
        }

        .form-input:focus {
            outline: none;
            border-color: #8B4B6B;
            background-color: white;
            box-shadow: 0 0 10px rgba(139, 75, 107, 0.1);
        }

        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #6B1B3D 0%, #8B4B6B 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 75, 107, 0.4);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #4A4A4A;
        }

        .login-link a {
            color: #8B4B6B;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .alert-danger {
            background-color: #FFEBEE;
            border: 1px solid #EF9A9A;
            color: #C62828;
        }

        .alert-success {
            background-color: #E8F5E9;
            border: 1px solid #A5D6A7;
            color: #2E7D32;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .main-content {
                padding: 20px;
            }

            .register-container {
                padding: 30px 20px;
            }

            .header-buttons {
                width: 100%;
                justify-content: center;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        /* Loading animation for button */
        .register-btn.loading {
            position: relative;
            color: transparent;
        }

        .register-btn.loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
        <div class="header-buttons">
            <button class="header-btn" onclick="window.location.href='register.php'">S'inscrire</button>
            <button class="header-btn" onclick="window.location.href='login.php'">Se connecter</button>
        </div>
    </header>

    <main class="main-content">
        <div class="register-container">
            <h1 class="register-title">Inscription</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="login-link">
                    <a href="login.php">Se connecter</a>
                </div>
            <?php else: ?>
                <form id="registerForm" method="post" action="register.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" class="form-input" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ville">Ville</label>
                            <input type="text" id="ville" name="ville" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="code_postal">Code postal</label>
                            <input type="text" id="code_postal" name="code_postal" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-input" required>
                    </div>
                    
                    <button type="submit" class="register-btn" id="registerButton">
                        S'inscrire
                    </button>
                </form>
                
                <div class="login-link">
                    Déjà inscrit? <a href="login.php">Se connecter</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Form submission handling
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const button = document.getElementById('registerButton');
            
            // Add loading state
            button.classList.add('loading');
            button.disabled = true;
        });

        // Input field animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>