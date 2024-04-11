<?php
// Fonction pour afficher les consultations.
function afficher_consultations($linkpdo, $id) {
    try { 
        if ($id === null) { // Afficher toutes les consultations car aucun ID précisé
            $query = "SELECT * FROM rendezvous";
        } else {
            // Afficher la consultation avec l'identifiant spécifié
            $query = "SELECT * FROM rendezvous WHERE id_rendezvous = :id";
        }
        
        // Préparation et exécution de la requête
        $statement = $linkpdo->prepare($query);
        if ($id !== null) {
            $statement->bindParam(':id', $id);
        }
        $statement->execute();
       
        // Récupération des résultats
        $consultations = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Si l'identifiant est spécifié et aucune consultation correspondante n'est trouvée, renvoyer une erreur 400
        if ($id !== null && empty($consultations)) {
            deliver_response(404, "Aucune consultation trouvée pour l'identifiant spécifié");
            return null; // Retourner null pour indiquer une erreur
        }
        
        // Retourner les consultations récupérées
        deliver_response(200, "Succès", $consultations);
    } catch (PDOException $e) {
        // En cas d'erreur, renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
        deliver_response(500, "Erreur lors de la récupération des consultations: " . $e->getMessage());
        return null; // Retourner null pour indiquer une erreur
    }
}

function afficher_erreur(PDOException $e) {
    // Reformulation de l'erreur SQL pour une meilleure lisibilité
    $status_code = 404;
    $errorMessage = "Erreur lors de la mise à jour de la consultation : ";
    if ($e->getCode() == '45002') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "La date de rendez-vous ne peut pas être antérieure à la date du jour.";
    } elseif ($e->getCode() == '45003') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Un rendez-vous ne peut pas durer moins de 15 min.";
    } elseif ($e->getCode() == '45001') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Ce n'est pas une date de rendez-vous valable (samedi ou dimanche).";
    } elseif ($e->getCode() == '45004') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Un rendez-vous ne peut pas dépasser une durée d'une heure.";
    } elseif ($e->getCode() == '45000') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Ce n'est pas une heure de rendez-vous valable.";
    } elseif ($e->getCode() == '45005') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Un rendez-vous ne peut pas dépasser une durée d'une heure.";
    } elseif ($e->getCode() == '45007') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $status_code = 409;
        $errorMessage .="Impossible d''ajouter le rendez-vous. Un autre rendez-vous est déjà prévu pour ce médecin à cette date et heure.";
    } else {
        $status_code = 500;
        // Si l'erreur n'est pas une erreur connue, afficher le message d'erreur SQL original
        $errorMessage .= $e->getMessage();
    }
    // Renvoyer un code d'erreur HTTP 500 avec le message d'erreur reformulé
    deliver_response($status_code, $errorMessage);
}

// Fonction pour vérifier l'existence d'un médecin dans la base de données.
function medecin_existe($linkpdo, $id_medecin) {
    $query = "SELECT COUNT(*) FROM medecins WHERE id_medecin = :id_medecin";
    $stmt = $linkpdo->prepare($query);
    $stmt->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

// Fonction pour vérifier l'existence d'un usager dans la base de données.
function usager_existe($linkpdo, $id_usager) {
    $query = "SELECT COUNT(*) FROM usagers WHERE id_usager = :id_usager";
    $stmt = $linkpdo->prepare($query);
    $stmt->bindParam(':id_usager', $id_usager, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

// Crée une nouvelle consultation dans la base de données.
function creer_consultation($linkpdo, $id_usager, $id_medecin, $date_consult, $heure_consult, $duree_consult) {
    try {
        // Vérification si tous les champs requis sont fournis
        if (empty($id_usager) || empty($id_medecin) || empty($date_consult) || empty($heure_consult)) {
            deliver_response(400, "Tous les champs sont obligatoires et ne peuvent pas être vides.");
            return;
        }
        
        // Vérifier si le médecin existe
        if (!medecin_existe($linkpdo, $id_medecin)) {
            deliver_response(404, "Le médecin spécifié n'existe pas");
            return;
        }

        // Vérifier si l'utilisateur existe
        if (!usager_existe($linkpdo, $id_usager)) {
            deliver_response(404, "L'utilisateur spécifié n'existe pas");
            return;
        }

        // Convertir la date de consultation dans le bon format
        $date_consult_formattee = DateTime::createFromFormat('d/m/y', $date_consult);
        if (!$date_consult_formattee) {
            throw new Exception("Format de date de consultation invalide. Utilisez JJ/MM/AA");
        }
        $date_consult_formattee = $date_consult_formattee->format('Y-m-d');

        // Vérifier si l'heure de consultation est entre 12h00 et 14h00
        list($heures, $minutes) = explode(':', $heure_consult);
        if ($heures >= 12 && $heures < 14) {
            deliver_response(404, "Les rendez-vous ne sont pas disponibles entre 12h00 et 14h00.");
            return;
        }

        // Le rendez-vous est disponible, insérer dans la base de données
        $insererQuery = $linkpdo->prepare('INSERT INTO rendezvous (id_usager, id_medecin, date_consultation, heure_consultation, duree_consultation) 
                                            VALUES (:id_usager, :id_medecin, :date_consultation, :heure_consultation, :duree_consultation)');

        $resultatInsertion = $insererQuery->execute(array(
            'id_usager' => $id_usager,
            'id_medecin' => $id_medecin,
            'date_consultation' => $date_consult_formattee, // Utiliser la date formatée
            'heure_consultation' => $heure_consult, // Utiliser l'heure formatée
            'duree_consultation' => $duree_consult
        ));

        if (!$resultatInsertion) {
            throw new PDOException("Erreur lors de l'insertion de la consultation");
        }

        // Récupérer l'ID de la nouvelle consultation
        $newId = $linkpdo->lastInsertId();

        // Affichage la consultation (pour vérification)
        echo "La consultation qu'on vient de créer : \n";
        afficher_consultations($linkpdo, $newId); // Afficher la nouvelle consultation
        deliver_response(201, "Created");

    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur($e);
        return false;        
    }    
}

// Fonction pour la mise à jour partielle d'une consultation
function maj_patch_consultation($linkpdo, $id, $data) {
    try {
        // Vérifier si l'identifiant de la consultation est fourni
        if (empty($id)) {
            deliver_response(400, "L'identifiant de la consultation est requis pour la mise à jour.");
            return false;
        }

        // Récupérer les valeurs de id_usager et id_medecin depuis $data
        $id_usager = isset($data['id_usager']) ? $data['id_usager'] : null;
        $id_medecin = isset($data['id_medecin']) ? $data['id_medecin'] : null;
        $date_consultation = isset($data['date_consult']) ? $data['date_consult'] : null;
        $heure_consultation = isset($data['heure_consult']) ? $data['heure_consult'] : null;
        $duree_consultation = isset($data['duree_consult']) ? $data['duree_consult'] : null;
        
        // Vérifier si le médecin existe
        if ($id_medecin != null && !medecin_existe($linkpdo, $id_medecin)) {
            deliver_response(404, "Le médecin spécifié n'existe pas");
            return;
        }

        // Vérifier si l'utilisateur existe
        if ($id_usager != null && !usager_existe($linkpdo, $id_usager)) {
            deliver_response(404, "L'utilisateur spécifié n'existe pas");
            return;
        }

        // Vérifier si l'heure de consultation est entre 12h00 et 14h00
        if ($heure_consultation != null) {
            list($heures, $minutes) = explode(':', $heure_consultation);
            if ($heures >= 12 && $heures < 14) {
                deliver_response(404, "Les rendez-vous ne sont pas disponibles entre 12h00 et 14h00.");
                return;
            }
        }

        // Construction de la requête SQL pour la mise à jour partielle
        $query = "UPDATE rendezvous SET ";
        $setStatements = [];
        $bindParams = [];
        
        foreach ($data as $key => $value) {
            // Adapter les clés pour correspondre aux noms de colonnes de la base de données
            switch ($key) {
                case 'id_usager':
                    $column = 'id_usager';
                    break;
                case 'id_medecin':
                    $column = 'id_medecin';
                    break;
                case 'date_consult':
                    $column = 'date_consultation';
                    // Formater la date au format Y-m-d
                    $value = date('Y-m-d', strtotime($value));
                    break;
                case 'heure_consult':
                    $column = 'heure_consultation';
                    break;
                case 'duree_consult':
                    $column = 'duree_consultation';
                    break;
                default:
                    // Ignorer les clés inconnues
                    continue 2;
            }
            
            // Ajouter chaque attribut à mettre à jour à la liste des SET statements
            $setStatements[] = "$column = :$column";
            // Ajouter chaque paramètre à binder à la liste des bindParams
            $bindParams[":$column"] = $value;
        }
        
        // Concaténation des SET statements dans la requête SQL
        $query .= implode(", ", $setStatements);
        
        // Ajout de la clause WHERE pour identifier la consultation à mettre à jour
        $query .= " WHERE id_rendezvous = :id";
        
        // Ajout de l'identifiant comme paramètre à binder
        $bindParams[':id'] = $id;
        
        // Préparation et exécution de la requête
        $statement = $linkpdo->prepare($query);
        $linkpdo->beginTransaction();
        $statement->execute($bindParams);
        
        $linkpdo->commit(); // Fin de la transaction et application des données
        
        // Envoi de la réponse au client pour indiquer le succès de la mise à jour et les informations mises à jour de la consultation
        afficher_consultations($linkpdo, $id);
    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur($e);
        return false;
    }      
}

// Fonction pour mettre à jour une consultation en utilisant la méthode PUT.
function maj_put_consultation($linkpdo, $id, $data) {
    try {
        // Vérification des paramètres requis
        $required_params = array('id_usager', 'id_medecin', 'date_consult', 'heure_consult', 'duree_consult');
        foreach ($required_params as $param) {
            if (!isset($data[$param]) || empty($data[$param])) {
                deliver_response(400, "Le paramètre '$param' est manquant ou vide.");
                return false;
            }
        }

        // Récupérer les valeurs de id_usager et id_medecin depuis $data
        $id_usager = $data['id_usager'];
        $id_medecin = $data['id_medecin'];

        // Vérifier si le médecin existe
        if (!medecin_existe($linkpdo, $id_medecin)) {
            deliver_response(404, "Le médecin spécifié n'existe pas");
            return false;
        }

        // Vérifier si l'utilisateur existe
        if (!usager_existe($linkpdo, $id_usager)) {
            deliver_response(404, "L'utilisateur spécifié n'existe pas");
            return false;
        }

        // Vérifier si l'heure de consultation est entre 12h00 et 14h00
        if (isset($data['heure_consult'])) {
            list($heures, $minutes) = explode(':', $data['heure_consult']);
            if ($heures >= 12 && $heures < 14) {
                deliver_response(404, "Les rendez-vous ne sont pas disponibles entre 12h00 et 14h00.");
                return false;
            }
        }

        // Vérifier si le rendez-vous est disponible
        $date_consultation = date('Y-m-d', strtotime($data['date_consult']));

        // Préparation de la requête SQL
        $query = "UPDATE rendezvous SET 
                id_usager = :id_usager,
                id_medecin = :id_medecin,
                date_consultation = :date_consultation,
                heure_consultation = :heure_consultation,
                duree_consultation = :duree_consultation
                WHERE id_rendezvous = :id";

        // Exécution de la requête préparée
        $req = $linkpdo->prepare($query);
        $req->bindParam(':id_usager', $id_usager, PDO::PARAM_INT);
        $req->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
        $req->bindParam(':date_consultation', $date_consultation, PDO::PARAM_STR); // Utilisation de la date formatée
        $req->bindParam(':heure_consultation', $data['heure_consult'], PDO::PARAM_STR);
        $req->bindParam(':duree_consultation', $data['duree_consult'], PDO::PARAM_INT);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        $linkpdo->beginTransaction(); // Démarage de la transaction
        $resExec = $req->execute();

        if (!$resExec) {
            deliver_response(500, "Erreur lors de la mise à jour de la consultation");
            return false;
        }

        $linkpdo->commit();
        afficher_consultations($linkpdo, $id); 
    }catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur($e);
        return false;
    }       
}

// Fonction pour la suppression d'une consultation
function supprimer_consultation($linkpdo, $id) {
    try {
        // Vérifier si l'identifiant est valide
        if (!is_numeric($id) || $id <= 0) {
            deliver_response(400, "L'identifiant de la consultation spécifié est invalide");
            return null;
        }

        // Vérifier si la consultation existe avant de la supprimer
        $checkQuery = $linkpdo->prepare('SELECT COUNT(*) FROM rendezvous WHERE id_rendezvous = :id');
        $checkQuery->execute(array('id' => $id));
        $rowCount = $checkQuery->fetchColumn();

        if ($rowCount === 0) {
            // Si aucune consultation n'est trouvée pour cet identifiant
            deliver_response(404, "Aucune consultation trouvée pour l'identifiant spécifié");
            return null;
        }

        // Commencer une transaction
        $linkpdo->beginTransaction();

        // Supprimer la consultation
        $deleteQuery = $linkpdo->prepare('DELETE FROM rendezvous WHERE id_rendezvous = :id');
        $deleteQuery->execute(array('id' => $id));

        // Appliquer les changements
        $linkpdo->commit();

        // Vérifier si une ligne a été affectée (si l'identifiant existait)
        $rowCount = $deleteQuery->rowCount();

        if ($rowCount === 0) {
            // Si aucune ligne n'a été affectée, la consultation avec cet identifiant n'existe pas
            deliver_response(400, "Aucune consultation trouvée pour l'identifiant spécifié");
            return null;
        }

        // La consultation a été supprimée avec succès
        deliver_response(200, "La consultation a été supprimée avec succès");
        return true;
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction et renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
        $linkpdo->rollBack();
        deliver_response(500, "Erreur lors de la suppression de la consultation : " . $e->getMessage());
        return null;
    }
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
?>

