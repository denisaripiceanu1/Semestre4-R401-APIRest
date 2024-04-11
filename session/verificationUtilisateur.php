<?php
    include 'connexion_bd_utilisateurs.php'; // Connexion à la BD
    session_start(); // Début de la session

    if (!isset($_SESSION['id_utilisateur'])) { // si l'utilisateur ne s'est pas authentifié
        header('Location: login.php'); // le redirigez vers la page de connexion
        exit();
    }
?>
