<?php
function lire_statistiques_usagers_sexe_age($linkpdo) {
    try {
        $querySexeAge = $linkpdo->query('
            SELECT 
                CASE
                    WHEN YEAR(CURDATE()) - YEAR(date_naissance) < 25 THEN "Moins de 25 ans"
                    WHEN YEAR(CURDATE()) - YEAR(date_naissance) BETWEEN 25 AND 50 THEN "Entre 25 et 50 ans"
                    ELSE "Plus de 50 ans"
                END AS tranche_age,
                COUNT(*) AS nb_usagers,
                civilite
            FROM usagers
            GROUP BY tranche_age, civilite
        ');

        $statistiquesSexeAge = [];

        // Collecter les statistiques sur la répartition des usagers par sexe et âge
        while ($row = $querySexeAge->fetch()) {
            $trancheAge = $row['tranche_age'];
            $sexe = $row['civilite'];
            $nbUsagers = $row['nb_usagers'];

            if (!isset($statistiquesSexeAge[$trancheAge])) {
                $statistiquesSexeAge[$trancheAge] = [0, 0];
            }
            $statistiquesSexeAge[$trancheAge][$sexe == 'F' ? 1 : 0] = $nbUsagers;
        }
            
        deliver_response(200, "Succès", $statistiquesSexeAge);
    
    } catch (PDOException $e) {
            // En cas d'erreur, renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
            deliver_response(500, "Erreur lors de la récupération des statistiques sur les usagers : " . $e->getMessage());
            return null; // Retourner null pour indiquer une erreur
    }
}

function lire_statistiques_medecins($linkpdo) {
    try {
        // Durée totale des consultations par médecin
        $queryDureeConsultation = $linkpdo->query('
            SELECT 
                medecins.Nom AS nom, medecins.prenom AS prenom, 
                SUM(rendezvous.duree_consultation) AS duree_totale
            FROM rendezvous
            JOIN medecins ON rendezvous.id_medecin = medecins.id_medecin
            GROUP BY medecins.nom, medecins.prenom 
        ');

        $statistiquesDureeConsultation = [];

        // Collecter les statistiques sur la durée totale des consultations par médecin
        while ($row = $queryDureeConsultation->fetch()) {
            $medecinNom = $row['nom'];
            $medecinPrenom = $row['prenom'];
            $dureeTotale = $row['duree_totale'] / 60;
            $statistiquesDureeConsultation[] = [$medecinNom, $medecinPrenom, $dureeTotale];
        }
                
        deliver_response(200, "Succès", $statistiquesDureeConsultation);
    
    } catch (PDOException $e) {
        // En cas d'erreur, renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
        deliver_response(500, "Erreur lors de la récupération des statistiques sur les usagers : " . $e->getMessage());
        return null; // Retourner null pour indiquer une erreur
    }
}

// Envoi de la réponse au Client
function deliver_response($status_code, $status_message, $data=null){
    // Paramétrage de l'entête HTTP
    http_response_code($status_code); // Utilise un message standardisé en fonction du code HTTP
    header("Content-Type: application/json; charset=utf-8"); // Indique au client le format de la réponse
    header("Access-Control-Allow-Origin: *"); // Autorise toutes les origines, y compris null
    
    // Si la méthode de la requête est OPTIONS, ajouter les entêtes préliminaires preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE");
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