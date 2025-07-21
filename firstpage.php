<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Bank - Tableau de Bord</title>
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
            backdrop-filter: blur(10px);
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 60px 40px;
        }

        .welcome-section {
            text-align: center;
            color: white;
            margin-bottom: 60px;
            animation: fadeInDown 0.8s ease-out;
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 300;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .welcome-subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1000px;
            width: 100%;
        }

        .service-card {
            background: white;
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .service-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .service-card:nth-child(3) {
            animation-delay: 0.4s;
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
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .service-icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .service-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .service-description {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        /* Specific card styling */
        .card-gestion {
            border-top: 4px solid #2196F3;
        }

        .card-gestion .service-icon {
            color: #2196F3;
        }

        .card-securite {
            border-top: 4px solid #FF9800;
        }

        .card-securite .service-icon {
            color: #FF9800;
        }

        .card-mobile {
            border-top: 4px solid #9C27B0;
        }

        .card-mobile .service-icon {
            color: #9C27B0;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .main-content {
                padding: 40px 20px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .service-card {
                padding: 30px 20px;
            }

            .header-buttons {
                width: 100%;
                justify-content: center;
            }
        }

        /* Loading states */
        .service-card.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .service-card.loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #8B4B6B;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .notification.success {
            background: linear-gradient(135deg, #4CAF50, #45a049);
        }

        .notification.info {
            background: linear-gradient(135deg, #2196F3, #0b7dda);
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
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Ma Banque</div>
       <div class="header-buttons">
       <button class="header-btn" onclick="showNotification('Inscription disponible bientôt', 'info')">S'inscrire</button>
       <button class="header-btn" onclick="window.location.href='login.php'">Se connecter</button>
</div>
    </header>

    <main class="main-content">
        <div class="welcome-section">
            <h1 class="welcome-title">Bienvenue sur E-Bank</h1>
            <p class="welcome-subtitle">Votre espace bancaire en ligne</p>
        </div>

        <div class="services-grid">
           <div class="service-card card-gestion" onclick="window.location.href='login.php'">
                <div class="service-icon">💳</div>
                <h3 class="service-title">Gestion de Compte</h3>
                <p class="service-description">Accédez à vos comptes 24/7</p>
            </div>

            <div class="service-card card-securite" onclick="handleServiceClick('securite')">
                <div class="service-icon">🔒</div>
                <h3 class="service-title">Sécurité Maximale</h3>
                <p class="service-description">Protection de vos données</p>
            </div>

            <div class="service-card card-mobile" onclick="handleServiceClick('mobile')">
                <div class="service-icon">📱</div>
                <h3 class="service-title">Service Mobile</h3>
                <p class="service-description">Gérez vos finances partout</p>
            </div>
        </div>
    </main>

    <script>
        // Service card click handler
        function handleServiceClick(service) {
            const card = event.currentTarget;
            card.classList.add('loading');
            
            setTimeout(() => {
                card.classList.remove('loading');
                
                switch(service) {
                    case 'gestion':
                        showNotification('Ouverture du module de gestion de compte...', 'success');
                        setTimeout(() => {
                            showAccountDetails();
                        }, 1500);
                        break;
                    case 'securite':
                        showNotification('Accès aux paramètres de sécurité...', 'success');
                        setTimeout(() => {
                            showSecuritySettings();
                        }, 1500);
                        break;
                    case 'mobile':
                        showNotification('Redirection vers l\'application mobile...', 'success');
                        setTimeout(() => {
                            showMobileInfo();
                        }, 1500);
                        break;
                }
            }, 1500);
        }

        // Show different sections based on service
        function showAccountDetails() {
            showNotification('Module de gestion: Consultez vos soldes, historique des transactions, et effectuez des virements.', 'info');
        }

        function showSecuritySettings() {
            showNotification('Sécurité: Modifiez vos codes d\'accès, activez la double authentification.', 'info');
        }

        function showMobileInfo() {
            showNotification('Application mobile disponible sur iOS et Android. Toutes vos opérations bancaires dans votre poche!', 'info');
        }

        // Logout function
        function logout() {
            showNotification('Déconnexion en cours...', 'info');
            setTimeout(() => {
                showNotification('Vous avez été déconnecté avec succès', 'success');
                // In a real app, this would redirect to login page
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }, 1500);
        }

        // Notification system
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existing = document.querySelector('.notification');
            if (existing) {
                existing.remove();
            }

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
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

        // Add subtle parallax effect
        let ticking = false;
        
        function updateParallax() {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.service-card');
            
            parallaxElements.forEach((element, index) => {
                const speed = (index + 1) * 0.1;
                const yPos = -(scrolled * speed);
                element.style.transform = `translateY(${yPos}px)`;
            });
            
            ticking = false;
        }

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        });

        // Add mouse tracking effect for background
        document.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX;
            const mouseY = e.clientY;
            
            const body = document.body;
            const moveX = (mouseX / window.innerWidth) * 20 - 10;
            const moveY = (mouseY / window.innerHeight) * 20 - 10;
            
            body.style.backgroundPosition = `${moveX}px ${moveY}px`;
        });

        // Welcome animation on load
        window.addEventListener('load', () => {
            showNotification('Bienvenue dans votre espace E-Bank!', 'success');
        });

        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const focused = document.activeElement;
                if (focused.classList.contains('service-card')) {
                    focused.click();
                }
            }
        });

        // Make service cards focusable for accessibility
        document.querySelectorAll('.service-card').forEach(card => {
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.setAttribute('aria-label', card.querySelector('.service-title').textContent);
        });
    </script>
</body>
</html>
