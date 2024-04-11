<?php
// Inclure le fichier de connexion à la base de données
include '../connexion_bd_cabinet_medical.php';
// Inclure le fichier de fonctions
include 'fonctions_usagers.php';

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
    // Erreur lors de la requête vers l'API d'authentification
    deliver_response(500, "Erreur lors de la vérification du jeton JWT", 'INTERNAL_SERVER_ERROR');
    exit;
} else {
    // Convertir la réponse de l'API d'authentification en tableau associatif
    $response = json_decode($result, true);
    
    // Vérifier si la réponse indique que le jeton est valide
    if (isset($response['statut'])) {
        if ($response['statut'] === 200) {
            // Identifier le type de méthode HTTP envoyée par le client
            $http_method = $_SERVER['REQUEST_METHOD'];   
                
            switch ($http_method){
                case "GET" :
                    // Récupérer des données dans l’URL
                    // Si un id est renseigné, afficher l'usager avec cet identifiant
                    // Si aucun id n'est fourni, afficher tous les usagers
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
                    // Récupérer toutes les usagers ou un usager spécifique
                    $resultat = afficher_usagers($linkpdo, $id);
                break;
            
                case "POST" :
                    // Récupérer les données du corps de la requête
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);

                    // Créer l'usager seulement si les données ne sont pas vides
                    $resultat = creer_usager($linkpdo, $data['civilite'], $data['nom'], $data['prenom'], $data['sexe'], $data['adresse'], $data['code_postal'], $data['ville'], $data['date_nais'], $data['lieu_nais'], $data['num_secu'], $data['id_medecin']);
                break;

                case "PATCH":
                    // Extraire l'identifiant de l'usager de l'URL
                    // Le segment de l'URL contenant l'identifiant de l'usager est /v3/index.php/44
                    // On extrait donc le dernier segment après le dernier "/"
                    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                    $last_url_part = end($url_parts);
                    
                    // Si le dernier segment est un nombre, considérez-le comme l'identifiant de l'usager
                    if (is_numeric($last_url_part)) {
                        $id = intval($last_url_part);
                    } else {
                        deliver_response(400, "L'identifiant de l'usager à mettre à jour est manquant ou invalide.");
                        break;
                    }

                    // Récupérer les données du corps de la requête
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                    
                    // S'assurer que des données valides ont été fournies dans la requête
                    if (empty($data)) {
                        deliver_response(400, "Les données à mettre à jour sont manquantes ou invalides.");
                        break;
                    }
                    
                    // Appeler la fonction pour effectuer la mise à jour partielle de l'usager
                    $resultat = maj_patch_usager($linkpdo, $id, $data);
                break;        

                case "PUT":
                    // Extraire l'identifiant de l'usager de l'URL
                    // Si le dernier segment de l'URL est un nombre, considérez-le comme l'identifiant
                    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                    $last_url_part = end($url_parts);
                    $id = null;
                    if (is_numeric($last_url_part)) {
                        $id = intval($last_url_part);
                    } else {
                        deliver_response(400, "L'identifiant de l'usager à mettre à jour est manquant.");
                        break;
                    }
                
                    // Récupérer les données du corps de la requête
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                
                    // Vérifier que toutes les données requises sont présentes
                    $required_params = array('civilite', 'nom', 'prenom','sexe', 'adresse', 'ville', 'code_postal', 'date_naissance', 'lieu_naissance', 'num_secu_sociale', 'id_medecin');
                    foreach ($required_params as $param) {
                        if (!array_key_exists($param, $data) || empty($data[$param])) {
                            deliver_response(400, "Le paramètre '$param' est manquant ou vide.");
                            break 2; // Sortir de la boucle externe et du switch
                        }
                    }
                
                    // Appeler la fonction pour mettre à jour entièrement l'usager
                    $resultat = maj_put_usager($linkpdo, $id, $data);
                break;
                
                    
                case "DELETE":
                    // Extraire l'identifiant de l'usager de l'URL après le dernier "/"
                    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
                    $last_url_part = end($url_parts);
                    
                    // Si le dernier segment est un nombre, considérez-le comme l'identifiant de l'usager
                    if (is_numeric($last_url_part)) {
                        $id = intval($last_url_part);
                    } else {
                        deliver_response(400, "L'identifiant de l'usager à supprimer est manquant ou invalide.");
                        break;
                    }
                    
                    // Appeler la fonction pour supprimer l'usager
                    $resultat = supprimer_usager($linkpdo, $id);
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