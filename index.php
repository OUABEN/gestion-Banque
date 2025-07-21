<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: home.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>