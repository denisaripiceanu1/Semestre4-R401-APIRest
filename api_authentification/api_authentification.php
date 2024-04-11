<?php
// Inclusion des bibliothèques nécessaires
include 'jwt_utils.php'; // Bibliothèque JWT
include 'connexion_bd_utilisateurs.php'; // Connexion à la base de données

// Vérification de la méthode de la requête (POST uniquement pour l'authentification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données envoyées en POST
    $postedData = file_get_contents('php://input');
    $data = json_decode($postedData, true); // Décodage des données JSON

    // Vérification de la présence des champs login et password dans les données postées
    if (isset($data['login']) && isset($data['mdp'])) {
        $login = $data['login'];
        $password = $data['mdp'];

        // Requête pour vérifier l'utilisateur dans la base de données
        $query = "SELECT id, login, role FROM user WHERE login = :login AND password = :password";
        $statement = $linkpdo->prepare($query);
        $statement->bindParam(':login', $login, PDO::PARAM_STR);
        $statement->bindParam(':password', $password, PDO::PARAM_STR);
        $statement->execute();
        
        // Vérifier si l'utilisateur existe et le mot de passe correspond
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Générer le jeton JWT avec le rôle de l'utilisateur
            $headers = ['typ' => 'JWT', 'alg' => 'HS256'];
            $payload = ['user_id' => $user['id'], 'role' => $user['role'], 'exp' => time() + 60 * 60];
            $secret = 'votre_secret'; // Clé secrète pour la signature du jeton
            $jwt = generate_jwt($headers, $payload, $secret);

            // Retourner le jeton JWT dans la réponse avec le code de succès (200)
            http_response_code(200);
            echo json_encode(['token' => $jwt]);
            exit; // Terminer l'exécution du script après avoir envoyé la réponse
        } else {
            // Utilisateur non trouvé ou mot de passe incorrect
            deliver_response(401, "Login ou mot de passe incorrect");
            exit; // Terminer l'exécution du script après avoir envoyé la réponse
        }
    } else {
        // Champs manquants dans les données postées
        deliver_response(400, "Veuillez fournir un login et un mot de passe");
        exit; // Terminer l'exécution du script après avoir envoyé la réponse
    }
}   elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = get_bearer_token();
    if ($token !== null) {

        // Vérification de la validité du jeton
        $secret = 'votre_secret'; // Clé secrète utilisée pour signer le jeton
        if (is_jwt_valid($token, $secret)) {
            // Jeton valide, retourner une réponse avec un code de succès (200)
            http_response_code(200);
            echo json_encode(['message' => 'Le jeton est valide','statut' => 200]);
            exit(); // Terminer l'exécution du script après avoir envoyé la réponse
        } else {
            deliver_response(401, "Jeton JWT invalide ");
            exit(); // Terminer l'exécution du script après avoir envoyé la réponse
        }
    } else {
        // Aucun jeton fourni dans l'en-tête Authorization
        deliver_response(400, "Aucun jeton JWT fourni");
        exit; // Terminer l'exécution du script après avoir envoyé la réponse
    }
} else {
    // Méthode non autorisée (autre que POST ou GET)
    http_response_code(405); // Method not allowed
    exit; // Terminer l'exécution du script après avoir envoyé la réponse
}
?>