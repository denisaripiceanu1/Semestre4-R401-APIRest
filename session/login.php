<?php
// Inclure le fichier de connexion à la base de données 
include 'connexion_bd_utilisateurs.php';

// Vérifier si l'utilisateur est déjà connecté en vérifiant la présence de la variable de session
session_start();
if (isset($_SESSION['id_utilisateur'])) {
    // Si l'utilisateur est déjà connecté, rediriger vers index.php
    header('Location: index.html');
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Appeler votre script d'authentification (authentifier.php ou autre)
    include 'authentifier.php'; 
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" type="text/css" href="../styles_css/connexion.css">
</head>
<body>
    <!-- Titre de la page -->
    <h1>Connexion</h1>

    <!-- Formulaire de connexion -->
    <form action="login.php" method="post">
        <!-- Champ pour le nom d'utilisateur -->
        <label for="nom_utilisateur">Nom d'utilisateur :</label>
        <input type="text" id="nom_utilisateur" name="nom_utilisateur" required><br>

        <!-- Champ pour le mot de passe -->
        <label for="mot_de_passe">Mot de passe :</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required><br>

        <!-- Bouton de soumission du formulaire -->
        <input type="submit" value="Se connecter">
    </form>
</body>
</html>
