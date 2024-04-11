<?php
// Fonction pour récupérer toutes les médecins
function afficher_medecins($linkpdo, $id) {
    try {
        if ($id === null) {
            // Requête SQL pour récupérer toutes les médecins
            $query = "SELECT * FROM medecins";
        } else {
            // Requête SQL pour récupérer le médecin avec l'identifiant spécifié
            $query = "SELECT * FROM medecins WHERE id_medecin = :id";
        }
        // Préparation et exécution de la requête
        $statement = $linkpdo->prepare($query);
        if ($id !== null) {
            $statement->bindParam(':id', $id);
        }
        $statement->execute();
       
        // Récupération des résultats
        $medecins = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Si l'identifiant est spécifié et aucun médecin correspondant n'est trouvé, renvoyer une erreur 400
        if ($id !== null && empty($medecins)) {
            deliver_response(404, "Aucun médecin trouvé pour l'identifiant spécifié");
            return null; // Retourner null pour indiquer une erreur
        }
        // Retourner les médecins récupérés
        deliver_response(200, "Succès", $medecins);
    } catch (PDOException $e) {
        // En cas d'erreur, renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
        deliver_response(500, "Erreur lors de la récupération des médecins: " . $e->getMessage());
        return null; // Retourner null pour indiquer une erreur
    }
}

// Fonction pour afficher les erreurs
function afficher_erreur(PDOException $e) {
    // Reformulation de l'erreur SQL pour une meilleure lisibilité
    $status_code = 404;
    $errorMessage = "Erreur : ";
    if ($e->getCode() == '45000') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "La civilité doit être M. ou Mme.";
    } else {
        $status_code = 500;
        // Si l'erreur n'est pas une erreur connue, afficher le message d'erreur SQL original
        $errorMessage .= $e->getMessage();
    }
    // Renvoyer un code d'erreur HTTP 500 avec le message d'erreur reformulé
    deliver_response($status_code, $errorMessage);
}

// Fonction pour créer un médecin
function creer_medecin($linkpdo, $civilite, $nom, $prenom) {
    try {
        // Vérification si tous les champs requis sont fournis
        if (empty($civilite) || empty($nom) || empty($prenom)) {
            deliver_response(400, "Tous les champs sont obligatoires et ne peuvent pas être vides.", 'BAD_REQUEST');
            return;
        }

        // Vérification si le médecin existe déjà dans la base de données
        $checkQuery = $linkpdo->prepare('SELECT COUNT(*) AS count FROM medecins WHERE civilite = :civilite AND nom = :nom AND prenom = :prenom');
        $checkQuery->execute(array(
            'civilite' => $civilite,
            'nom' => $nom,
            'prenom' => $prenom
        ));
        $result = $checkQuery->fetch(PDO::FETCH_ASSOC);

        // Si le médecin existe déjà, renvoie un message d'erreur
        if ($result['count'] > 0) {
            deliver_response(409, "Le médecin existe déjà dans la base de données.", 'CONFLICT');
            return; // Arrête l'exécution de la fonction
        }

        // Préparation de la requête d'insertion
        $insertQuery = $linkpdo->prepare('INSERT INTO medecins (civilite, nom, prenom) VALUES (:civilite, :nom, :prenom)');

        // Exécution de la requête d'insertion avec les données fournies
        $insertResult = $insertQuery->execute(array(
            'civilite' => $civilite,
            'nom' => $nom,
            'prenom' => $prenom
        ));

        // Vérification du succès de l'insertion
        if (!$insertResult) {
            throw new PDOException("Erreur lors de l'exécution de la requête d'insertion.");
        }

        // Récupération de l'ID du nouveau médecin
        $newId = $linkpdo->lastInsertId();

        // Affichage le médecin (pour vérification)
        echo "Le médecin qu'on vient de créer : \n";
        afficher_medecins($linkpdo, $newId);
        deliver_response(201, "Created");

    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur($e);
        return false;        
    } 
}

//  Fonction pour mettre à jour partiellement les informations d'un médecin.
function maj_patch_medecin($linkpdo, $id, $data) {
    try {
        // Vérifier si des données ont été fournies pour la mise à jour
        if (empty($data)) {
            deliver_response(400, "Aucune donnée fournie pour la mise à jour du médecin.", 'BAD_REQUEST');
            return false;
        }

        // Construction de la requête SQL pour la mise à jour partielle
        $query = "UPDATE medecins SET ";
        $setStatements = [];
        $bindParams = [];
        
        foreach ($data as $key => $value) {
            // Vérifier si la clé de données est valide
            if (!in_array($key, array('nom', 'prenom', 'civilite'))) {
                deliver_response(400, "La clé de données '$key' n'est pas valide pour la mise à jour du médecin.", 'BAD_REQUEST');
                return false;
            }

            // Ajouter chaque attribut à mettre à jour à la liste des SET statements
            $setStatements[] = "$key = :$key";
            // Ajouter chaque paramètre à binder à la liste des bindParams
            $bindParams[":$key"] = $value;
        }
        
        // Concaténation des SET statements dans la requête SQL
        $query .= implode(", ", $setStatements);
        
        // Ajout de la clause WHERE pour identifier le médecin à mettre à jour
        $query .= " WHERE id_medecin = :id";
        
        // Ajout de l'identifiant comme paramètre à binder
        $bindParams[':id'] = $id;
        
        // Préparation et exécution de la requête
        $statement = $linkpdo->prepare($query);
        $linkpdo->beginTransaction();
        $statement->execute($bindParams);
        
        $linkpdo->commit(); // Fin de la transaction et application des données
        
        // Retourner les informations mises à jour du médecin
        return afficher_medecins($linkpdo, $id);
    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur($e);
        return false;        
    } 
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

//  Fonction pour supprimer un médecin de la base de données.
function supprimer_medecin($linkpdo, $id) {
    try {
        // Vérifier si l'ID du médecin est valide
        if (empty($id) || !is_numeric($id)) {
            deliver_response(400, "L'identifiant du médecin est invalide.");
            return null; // Retourner null pour indiquer une erreur
        }

        // Vérifier si le médecin existe
        if (!medecin_existe($linkpdo, $id)) {
            deliver_response(404, "Le médecin spécifié n'existe pas.");
            return null; // Retourner null pour indiquer une erreur
        }

        // Vérifier si le médecin a des consultations programmées
        $consultationsQuery = $linkpdo->prepare('SELECT COUNT(*) FROM rendezvous WHERE id_medecin = :id');
        $consultationsQuery->execute(array(':id' => $id));
        $consultationsCount = $consultationsQuery->fetchColumn();

        if ($consultationsCount > 0) {
            // Supprimer les consultations du médecin
            $deleteConsultationsQuery = $linkpdo->prepare('DELETE FROM rendezvous WHERE id_medecin = :id');
            $deleteConsultationsQuery->execute(array(':id' => $id));
        }

        // Vérifier si le médecin est le médecin référent d'un usager
        $usagersQuery = $linkpdo->prepare('UPDATE usagers SET id_medecin = NULL WHERE id_medecin = :id');
        $usagersQuery->execute(array(':id' => $id));

        // Suppression du médecin
        $deleteMedecinQuery = $linkpdo->prepare('DELETE FROM medecins WHERE id_medecin = :id');
        $deleteMedecinQuery->execute(array(':id' => $id));

        // Vérifier si une ligne a été affectée
        $rowCount = $deleteMedecinQuery->rowCount();

        if ($rowCount === 0) {
            // Si aucune ligne n'a été affectée, le médecin avec cet identifiant n'existait pas
            deliver_response(404, "Aucun médecin trouvé pour l'identifiant spécifié.");
            return null; // Retourner null pour indiquer une erreur
        }

        // Le médecin a été supprimé avec succès
        deliver_response(200, "Le médecin a été supprimé avec succès.");
        return true;
    } catch (PDOException $e) {
        deliver_response(500, "Erreur lors de la suppression du médecin : " . $e->getMessage());
        return null; // Retourner null pour indiquer une erreur
    }
}

// Fonction pour mettre à jour un médecin en utilisant la méthode PUT.
function maj_put_medecin($linkpdo, $id, $data) {
    try {
        // Vérification des paramètres requis
        $required_params = array('civilite', 'nom', 'prenom');
        foreach ($required_params as $param) {
            if (!isset($data[$param]) || empty($data[$param])) {
                deliver_response(400, "Le paramètre '$param' est manquant ou vide.");
                return false;
            }
        }

        // Préparation de la requête SQL
        $query = "UPDATE medecins SET 
                civilite = :civilite,
                nom = :nom,
                prenom = :prenom
                WHERE id_medecin = :id";

        // Exécution de la requête préparée
        $req = $linkpdo->prepare($query);
        $req->bindParam(':civilite', $data['civilite'], PDO::PARAM_STR);
        $req->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $req->bindParam(':prenom', $data['prenom'], PDO::PARAM_STR);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        $linkpdo->beginTransaction(); // Début de la transaction
        $resExec = $req->execute();

        if (!$resExec) {
            deliver_response(500, "Erreur lors de la mise à jour du médecin", 'INTERNAL_SERVER_ERROR');
            return false;
        }

        $linkpdo->commit();
        afficher_medecins($linkpdo, $id); // Afficher les informations mises à jour du médecin
    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur($e);
        return false;        
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
