<?php
// Génère un JSON Web Token (JWT) à partir des en-têtes, des données (payload) et du secret fournis.
function generate_jwt($headers, $payload, $secret) {
	$headers_encoded = base64url_encode(json_encode($headers));

	$payload_encoded = base64url_encode(json_encode($payload));

	$signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
	$signature_encoded = base64url_encode($signature);

	$jwt = "$headers_encoded.$payload_encoded.$signature_encoded";

	return $jwt;
}

// Vérifie la validité d'un JSON Web Token (JWT) en le comparant avec un secret donné.
function is_jwt_valid($jwt, $secret) {
	// Divise le JWT en parties
	$tokenParts = explode('.', $jwt);
	$header = base64_decode($tokenParts[0]);
	$payload = base64_decode($tokenParts[1]);
	$signature_provided = $tokenParts[2];

	// Vérifie le temps d'expiration
	$expiration = json_decode($payload)->exp;
	$is_token_expired = ($expiration - time()) < 0;

	// Construit une signature basée sur l'en-tête et le payload en utilisant le secret
	$base64_url_header = base64url_encode($header);
	$base64_url_payload = base64url_encode($payload);
	$signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
	$base64_url_signature = base64url_encode($signature);

	// Vérifie si la signature correspond à celle fournie dans le JWT
	$is_signature_valid = ($base64_url_signature === $signature_provided);

	if ($is_token_expired || !$is_signature_valid) {
		return FALSE;
	} else {
		return TRUE;
	}
}

// Encode une chaîne en base64 URL-safe.
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Récupère l'en-tête Authorization de la requête HTTP.
function get_authorization_header(){
	$headers = null;

	if (isset($_SERVER['Authorization'])) {
		$headers = trim($_SERVER["Authorization"]);
	} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
		$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	} else if (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		if (isset($requestHeaders['Authorization'])) {
			$headers = trim($requestHeaders['Authorization']);
		}
	}

	return $headers;
}

// Récupère le jeton Bearer de l'en-tête Authorization.
function get_bearer_token() {
    $headers = get_authorization_header();
    
    // Obtient le jeton d'accès de l'en-tête
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            if($matches[1]=='null') //$matches[1] est de type string et peut contenir 'null'
                return null;
            else
                return $matches[1];
        }
    }
    return null;
}

// ------------------------------------------ AUTRES FONCTIONS --------------------------------------------------

// Envoi de la réponse au Client
function deliver_response($status_code, $status_message, $data=null){
    // Paramétrage de l'entête HTTP
    http_response_code($status_code); // Utilise un message standardisé en fonction du code HTTP
    header("Content-Type: application/json; charset=utf-8"); // Indique au client le format de la réponse
    header("Access-Control-Allow-Origin: *"); // Autorise toutes les origines, y compris null
    
    // Si la méthode de la requête est OPTIONS, ajouter les entêtes préliminaires preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Methods: GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        http_response_code(204); // Pas de contenu pour une requête OPTIONS
        exit(); // Pas besoin de continuer, la réponse a été envoyée
    }

    // Configuration de la réponse
    $response['status_code'] = $status_code;
    $response['status_message'] = $status_message;
    $response['data'] = $data;
    
    // Mapping de la réponse au format JSON
    $json_response = json_encode($response);
    if($json_response === false) {
        die('Erreur d\'encodage JSON : '.json_last_error_msg());
    }
    
    // Affichage de la réponse (Retourné au client)
    echo $json_response;
}
?>
