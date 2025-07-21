<?php
require_once 'config.php';

// Détruire la session
$_SESSION = array();
session_destroy();

// Rediriger vers la page de connexion
header("Location: firstpage.php");
exit();
?>