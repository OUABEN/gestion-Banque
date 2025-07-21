<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '3307'); // Le port utilisé par MySQL dans XAMPP
define('DB_NAME', 'banque');
define('DB_USER', 'root');
define('DB_PASS', '');

// Initialisation de la session
session_start();

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour sécuriser les entrées utilisateur
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Rediriger si non connecté
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: firstpage.php");
        exit();
    }
}
?>