<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            if ($user['statut'] === 'actif') {
                $_SESSION['user_id'] = $user['id_client'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                
                header("Location: home.php");
                exit();
            } else {
                $error = "Votre compte est inactif ou bloqué.";
            }
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la connexion: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Banque - Connexion</title>
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

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
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

        .login-title {
            text-align: center;
            color: #4A4A4A;
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .email-input {
            background-color: #E3F2FD;
            border-color: #BBDEFB;
        }

        .password-input {
            background-color: #FFF8E1;
            border-color: #FFE082;
        }

        .login-btn {
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
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 75, 107, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #4A4A4A;
        }

        .register-link a {
            color: #8B4B6B;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
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

            .login-container {
                padding: 30px 20px;
            }

            .header-buttons {
                width: 100%;
                justify-content: center;
            }
        }

        /* Loading animation for button */
        .login-btn.loading {
            position: relative;
            color: transparent;
        }

        .login-btn.loading::after {
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
        <div class="login-container">
            <h1 class="login-title">Connexion</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form id="loginForm" method="post" action="login.php">
                <div class="form-group">
                    <input 
                        type="email" 
                        class="form-input email-input" 
                        placeholder="Adresse email"
                        name="email"
                        required
                        id="email"
                    >
                </div>
                <div class="form-group">
                    <input 
                        type="password" 
                        class="form-input password-input" 
                        placeholder="Mot de passe"
                        name="password"
                        required
                        id="password"
                    >
                </div>
                <button type="submit" class="login-btn" id="loginButton">
                    Se connecter
                </button>
            </form>
            
            <div class="register-link">
                Pas encore de compte? <a href="register.php">S'inscrire</a>
            </div>
        </div>
    </main>

    <script>
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            
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