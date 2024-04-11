<?php
// Inclure le fichier de connexion à la base de données
include '../connexion_bd_cabinet_medical.php';
// Inclure le fichier de fonctions
include 'fonctions_medecins.php';

// Récupérer le jeton JWT depuis l'en-tête Authorization
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
            switch ($http_method){
                case "GET":
                    // Récupération de l'identifiant du médecin depuis l'URL
                    $id = null;
            
                    // Vérifier s'il y a un paramètre 'id' dans la chaîne de requête
                    if (isset($_GET['id'])) {
                        $id = htmlspecialchars($_GET['id']);
                    } else {
                        // Si 'id' n'est pas présent dans la chaîne de requête, vérifier l'URL
                        $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                        $last_url_part = end($url_parts);
                        
                        // Si le dernier segment de l'URL est un nombre, considérez-le comme l'identifiant
                        if (is_numeric($last_url_part)) {
                            $id = intval($last_url_part);
                        }
                    }
                            
                    // Récupérer toutes les informations des médecins ou d'un médecin spécifique
                    $phrases = afficher_medecins($linkpdo, $id);
                    break;
                        
                case "POST":
                    // Récupération des données du corps de la requête
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
            
                    // Vérification si les données nécessaires sont présentes
                    $required_params = array('civilite', 'nom', 'prenom');
                    foreach ($required_params as $param) {
                        if (!isset($data[$param]) || empty($data[$param])) {
                            deliver_response(400, "Le paramètre '$param' est manquant ou vide.", 'BAD_REQUEST');
                            return;
                        }
                    }
            
                    // Création d'un nouveau médecin
                    $phrases = creer_medecin($linkpdo, $data['civilite'], $data['nom'], $data['prenom']);
                    break;
                
                case "PATCH":
                    // Récupération de l'identifiant du médecin depuis l'URL
                    $id = null;
            
                    // Vérifier s'il y a un paramètre 'id' dans l'URL
                    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                    $last_url_part = end($url_parts);
                    
                    // Si le dernier segment de l'URL est un nombre, considérez-le comme l'identifiant
                    if (is_numeric($last_url_part)) {
                        $id = intval($last_url_part);
                    } else {
                        deliver_response(400, "L'identifiant du médecin à mettre à jour est manquant ou invalide.", 'BAD_REQUEST');
                        return;
                    }
            
                    // Récupération des données du corps de la requête
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
            
                    // Vérification si des données valides ont été fournies dans la requête
                    if (empty($data)) {
                        deliver_response(400, "Les données à mettre à jour sont manquantes ou invalides.", 'BAD_REQUEST');
                        return;
                    }
            
                    // Mise à jour partielle du médecin
                    $resultat = maj_patch_medecin($linkpdo, $id, $data);
                    break;
            
                case "PUT":
                    // Récupération de l'identifiant du médecin depuis l'URL
                    $id = null;
            
                    // Vérifier s'il y a un paramètre 'id' dans l'URL
                    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                    $last_url_part = end($url_parts);
            
                    // Si le dernier segment de l'URL est un nombre, considérez-le comme l'identifiant
                    if (is_numeric($last_url_part)) {
                        $id = intval($last_url_part);
                    } else {
                        deliver_response(400, "L'identifiant du médecin à mettre à jour est manquant ou invalide.", 'BAD_REQUEST');
                        return;
                    }
            
                    // Récupération des données du corps de la requête
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
            
                    // Vérification que toutes les données requises sont présentes
                    $required_params = array('civilite', 'nom', 'prenom');
                    foreach ($required_params as $param) {
                        if (!array_key_exists($param, $data) || empty($data[$param])) {
                            deliver_response(400, "Le paramètre '$param' est manquant ou vide.", 'BAD_REQUEST');
                            return;
                        }
                    }
            
                    // Mise à jour totale du médecin
                    $resultat = maj_put_medecin($linkpdo, $id, $data);
                    break;
                                
                case "DELETE":
                    // Récupération de l'identifiant du médecin depuis l'URL
                    $id = null;
            
                    // Vérifier s'il y a un paramètre 'id' dans l'URL
                    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                    $last_url_part = end($url_parts);
            
                    // Si le dernier segment de l'URL est un nombre, considérez-le comme l'identifiant
                    if (is_numeric($last_url_part)) {
                        $id = intval($last_url_part);
                    } else {
                        deliver_response(400, "L'identifiant du médecin à mettre à jour est manquant ou invalide.", 'BAD_REQUEST');
                        return;
                    }
            
                    // Suppression du médecin
                    $resultat = supprimer_medecin($linkpdo, $id);
                    break;
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
