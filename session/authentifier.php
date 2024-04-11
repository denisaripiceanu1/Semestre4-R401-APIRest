<?php
// Inclure la connexion à la base de données
include 'connexion_bd_utilisateurs.php';

// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom_utilisateur = $_POST['nom_utilisateur'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Vérifier les informations d'authentification
    $verifierUtilisateur = $linkpdo->prepare('SELECT * FROM user WHERE login = :nom_utilisateur AND password = :mot_de_passe');
    $verifierUtilisateur->execute(['nom_utilisateur' => $nom_utilisateur, 'mot_de_passe' => $mot_de_passe]);

    // Si l'utilisateur est authentifié
    if ($verifierUtilisateur->rowCount() > 0) {
        // Démarrer la session
        session_start();

        // Enregistrer l'ID de l'utilisateur dans la session
        $_SESSION['id_utilisateur'] = $verifierUtilisateur->fetch()['ID_Utilisateur'];

        // Effectuer une demande de jeton JWT à l'API d'authentification
        $auth_api_url = 'http://api-authentification.alwaysdata.net/api_authentification/api_authentification.php';

        // Préparer les données pour la demande de jeton
        $donnees_demande_token = array(
            'nom_utilisateur' => $nom_utilisateur,
            'mot_de_passe' => $mot_de_passe
        );

        // Configuration de la requête POST vers l'API d'authentification
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => json_encode($donnees_demande_token)
            )
        );

        // Créer le contexte de la requête
        $contexte = stream_context_create($options);

        // Effectuer la requête POST vers l'API d'authentification
        $resultat = file_get_contents($auth_api_url, false, $contexte);

        // Vérifier si la demande de jeton a réussi
        if ($resultat !== false) {
            // Convertir la réponse en tableau associatif
            $reponse_api = json_decode($resultat, true);

            // Vérifier si le jeton JWT a été reçu avec succès
            if (isset($reponse_api['token'])) {
                // Stocker le jeton JWT dans la session
                $_SESSION['jwt_token'] = $reponse_api['token'];

                // Rediriger vers la page protégée ou vers une autre page
                header('Location: ../index.html');
                exit();
            } else {
                // Afficher un message d'erreur si la demande de jeton a échoué
                echo 'Échec de la demande de jeton JWT.';
            }
        } else {
            // Afficher un message d'erreur si la demande de jeton a échoué
            echo 'Échec de la demande de jeton JWT.';
        }
    } else {
        // Afficher un message d'erreur si l'authentification échoue
        echo 'Nom d\'utilisateur ou mot de passe incorrect.';
    }
}
?>
