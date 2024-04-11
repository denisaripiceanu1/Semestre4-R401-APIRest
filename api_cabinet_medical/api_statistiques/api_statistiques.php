<?php
// Inclure le fichier de connexion à la base de données
include '../connexion_bd_cabinet_medical.php';
// Inclure le fichier de fonctions
include 'fonctions_statistiques.php';

$headers = apache_request_headers(); // Récupérer tous les en-têtes de la requête

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader); // Supprimer le préfixe 'Bearer ' pour obtenir le jeton uniquement
} else {
    $token = null;
}

// URL de l'API d'authentification
$auth_api_url = 'http://api-authentification.alwaysdata.net/api_authentification/api_authentification.php';

// Configuration de la requête POST vers l'API d'authentification
$options = array(
    'http' => array(
        'method' => 'GET',
        'header' => array (
            'Content-type: application/json',
            'Authorization: Bearer ' . $token
        )
    )
);

// Créer le contexte de la requête
$context = stream_context_create($options);

// Effectuer la requête POST vers l'API d'authentification
$result = file_get_contents($auth_api_url, false, $context);

// Vérifier la réponse de l'API d'authentification
if ($result === false) {
    // En cas d'erreur, renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
    deliver_response(500, "Erreur lors de la vérification du jeton JWT", 'INTERNAL_SERVER_ERROR');
    exit;
} else {
    // Convertir la réponse de l'API d'authentification en tableau associatif
    $response = json_decode($result, true);
    
    // Vérifier si la réponse indique que le jeton est valide
    if (isset($response['statut'])) {
        if ($response['statut'] === 200) {
            // Identification du type de méthode HTTP envoyée par le client
            $http_method = $_SERVER['REQUEST_METHOD'];
            if ($http_method == "GET") {
                
                $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                $last_url_part = end($url_parts);
                
                // Si le dernier segment de l'URL est un nombre, considérez-le comme l'identifiant
                if ($last_url_part=="usagers") {
                    lire_statistiques_usagers_sexe_age($linkpdo);
                } elseif ($last_url_part=="medecins") {
                    lire_statistiques_medecins($linkpdo);
                }
                /*
                lire_statistiques_usagers_sexe_age($linkpdo);
                lire_statistiques_medecins($linkpdo);
                */

            } else {
                // Méthode non autorisée
                deliver_response(405, "Méthode non autorisée");
                exit;
            }
        } else {
            // Si le jeton est invalide, renvoyer une réponse avec un code d'erreur 401
            deliver_response(401, "Le jeton JWT est invalide", 'UNAUTHORIZED');
            exit;
        }

    } else {
        // Si la réponse de l'API d'authentification est invalide, renvoyer une réponse avec un code d'erreur 500
        deliver_response(500, "Réponse invalide de l'API d'authentification", 'INTERNAL_SERVER_ERROR');
        exit;
    }
}
?>
