<?php
// Fonction pour récupérer tous les usagers
function afficher_usagers($linkpdo, $id) {
    try { 
        if ($id === null) { // afficher tous les usagers car aucun id précisé
            $query = "SELECT * FROM usagers ORDER BY nom";
        } else {
            // afficher l'usager avec l'identifiant spécifié
            $query = "SELECT * FROM usagers WHERE id_usager = :id ORDER BY nom";
        }
        
        // Préparation et exécution de la requête
        $statement = $linkpdo->prepare($query);
        if ($id !== null) {
            $statement->bindParam(':id', $id);
        }
        $statement->execute();
       
        // Récupération des résultats
        $usagers = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Si l'identifiant est spécifié et aucune phrase correspondante n'est trouvée, renvoyer une erreur 400
        if ($id !== null && empty($usagers)) {
            deliver_response(404, "Aucun usager trouvé pour l'identifiant spécifié");
            return null; // Retourner null pour indiquer une erreur
        }
        // Retourner les phrases récupérées
        deliver_response(200, "Succès", $usagers);
    } catch (PDOException $e) {
        // En cas d'erreur, renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
        deliver_response(500, "Erreur lors de la récupération des usagers: " . $e->getMessage());
        return null; // Retourner null pour indiquer une erreur
    }
}

function afficher_erreur_sql(PDOException $e) {
    // Reformulation de l'erreur SQL pour une meilleure lisibilité
    $status_code = 404;
    $errorMessage = "Erreur : ";
    if ($e->getCode() == '45000') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "La civilité doit être M ou Mme.";
    } elseif ($e->getCode() == '45001') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Le numéro de sécurité sociale doit avoir 15 caractères.";
    } elseif ($e->getCode() == '45002') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Le sexe doit être F ou H.";
    } elseif ($e->getCode() == '45003') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "Un usager ne peut pas etre son propre medecin referent.";
    } elseif ($e->getCode() == '45004') {
        // Code d'erreur spécifique à MySQL pour la violation de contrainte CHECK
        $errorMessage .= "La date de naissance ne peut pas être dans le futur.";
    } else {
        $status_code = 500;
        // Si l'erreur n'est pas une erreur connue, afficher le message d'erreur SQL original
        $errorMessage .= $e->getMessage();
    }
    // Renvoyer un code d'erreur HTTP 500 avec le message d'erreur reformulé
    deliver_response($status_code, $errorMessage);
}

// Fonction pour vérifier l'existence d'un médecin
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

// Fonction pour la création d'un usager
function creer_usager($linkpdo, $civilite, $nom, $prenom, $sexe, $adresse, $code_postal, $ville, $date_nais, $lieu_nais, $num_secu, $id_medecin) {
    // Vérifiez si tous les champs requis sont fournis
    if (empty($civilite) || empty($nom) || empty($prenom) || empty($adresse) || empty($code_postal) || empty($ville) || empty($date_nais) || empty($lieu_nais) || empty($num_secu) || empty($id_medecin)) {
        deliver_response(400, "Tous les champs sont obligatoires.");
        return;
    }

    try {
        // Convertir la date de naissance dans le bon format
        $date_naissance = DateTime::createFromFormat('d/m/Y', $date_nais);
        if (!$date_naissance) {
            throw new Exception("Format de date de naissance invalide. Utilisez JJ/MM/AAAA");
        }
        $date_naissance_formatted = $date_naissance->format('Y-m-d');

        // Vérifier si le médecin référent existe
        if (!medecin_existe($linkpdo, $id_medecin)) {
            deliver_response(404, "Le médecin référent spécifié n'existe pas");
            return;
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUserQuery = $linkpdo->prepare('SELECT COUNT(*) FROM usagers WHERE num_secu_sociale = :num_secu_sociale');
        $existingUserQuery->execute(array(':num_secu_sociale' => $num_secu));
        $rowCount = $existingUserQuery->fetchColumn();

        if ($rowCount > 0) {
            // L'utilisateur existe déjà, renvoyer un message d'erreur avec le code 409
            deliver_response(409, "L'utilisateur avec le numéro de sécurité sociale $num_secu existe déjà dans la base de données");
            return;
        }

        // L'utilisateur n'existe pas encore, procéder à l'insertion
        $query = "INSERT INTO usagers(civilite, nom, prenom, sexe, adresse, code_postal, ville, date_naissance, lieu_naissance, num_secu_sociale, id_medecin) 
                VALUES(:civilite, :nom, :prenom, :sexe, :adresse, :code_postal, :ville, :date_naissance, :lieu_naissance, :num_secu_sociale, :id_medecin)";
        
        $sth = $linkpdo->prepare($query);
        if (!$sth) {
            throw new PDOException("Erreur lors de la préparation de la requête");
        }
        
        $sth->bindParam(':civilite', $civilite, PDO::PARAM_STR);
        $sth->bindParam(':nom', $nom, PDO::PARAM_STR);
        $sth->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        $sth->bindParam(':sexe', $sexe, PDO::PARAM_STR);
        $sth->bindParam(':adresse', $adresse, PDO::PARAM_STR);
        $sth->bindParam(':code_postal', $code_postal, PDO::PARAM_STR); 
        $sth->bindParam(':ville', $ville, PDO::PARAM_STR);
        $sth->bindParam(':date_naissance', $date_naissance_formatted, PDO::PARAM_STR); // Utiliser la date formatée
        $sth->bindParam(':lieu_naissance', $lieu_nais, PDO::PARAM_STR);
        $sth->bindParam(':num_secu_sociale', $num_secu, PDO::PARAM_STR);
        $sth->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);

        $linkpdo->beginTransaction();
        $resExec = $sth->execute();
        if (!$resExec) {
            throw new PDOException("Erreur lors de l'exécution de la requête");
        }

        $newId = $linkpdo->lastInsertId(); // Récupération de l'id du nouvel usager

        $linkpdo->commit(); // Fin de la transaction et application des données
       
        // Affichage l'usager (pour vérification)
        echo "L'utilisateur qu'on vient de créer : \n";
        afficher_usagers($linkpdo, $newId);
        deliver_response(201, "Created");
        
    }catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur_sql($e);
        return false;        
    } 
}

//  Fonction pour mettre à jour partiellement les informations d'un usager.
function maj_patch_usager($linkpdo, $id, $data) {
    try {
        // Construction de la requête SQL pour la mise à jour partielle
        $query = "UPDATE usagers SET ";
        $setStatements = [];
        $bindParams = [];
        
        foreach ($data as $key => $value) {
            // Vérifier si la clé est 'date_naissance'
            if ($key === 'date_naissance') {
                // Formater la date au format Y-m-d
                $value = date('Y-m-d', strtotime($value));
            }
            
            // Ajouter chaque attribut à mettre à jour à la liste des SET statements
            $setStatements[] = "$key = :$key";
            // Ajouter chaque paramètre à binder à la liste des bindParams
            $bindParams[":$key"] = $value;
        }
        
        // Vérifier si le médecin référent doit être mis à jour
        if (isset($data['id_medecin'])) {
            // Vérifier si le médecin référent existe
            $medecin_referent_id = $data['id_medecin'];
            if (!medecin_existe($linkpdo, $medecin_referent_id)) {
                deliver_response(404, "Le médecin référent spécifié n'existe pas");
                return false;
            }
        }
        
        // Concaténation des SET statements dans la requête SQL
        $query .= implode(", ", $setStatements);
        
        // Ajout de la clause WHERE pour identifier l'usager à mettre à jour
        $query .= " WHERE id_usager = :id";
        
        // Ajout de l'identifiant comme paramètre à binder
        $bindParams[':id'] = $id;
        
        // Préparation et exécution de la requête
        $statement = $linkpdo->prepare($query);
        $linkpdo->beginTransaction();
        $statement->execute($bindParams);
        
        $linkpdo->commit(); // Fin de la transaction et application des données
        
        // Envoi de la réponse au client pour indiquer le succès de la mise à jour et les informations mises à jour de l'usager
        afficher_usagers($linkpdo, $id);
    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur_sql($e);
        return false; 
    }
}

// Fonction pour mettre à jour un usager en utilisant la méthode PUT.
function maj_put_usager($linkpdo, $id, $data) {
    // Vérification des paramètres requis
    $required_params = array('civilite', 'nom', 'prenom', 'sexe', 'adresse', 'ville', 'code_postal', 'date_naissance', 'lieu_naissance', 'num_secu_sociale', 'id_medecin');
    foreach ($required_params as $param) {
        if (!array_key_exists($param, $data)) {
            die(deliver_response(400, "Il manque le paramètre '$param' à renseigner"));
        }
    }

    // Vérifier si le médecin référent existe
    $medecin_referent_id = $data['id_medecin'];
    if (!medecin_existe($linkpdo, $medecin_referent_id)) {
        return deliver_response(404, "Le médecin référent spécifié n'existe pas");
    }

    // Vérifier si l'utilisateur existe déjà
    $num_secu = $data['num_secu_sociale'];
    $existingUserQuery = $linkpdo->prepare('SELECT COUNT(*) FROM usagers WHERE num_secu_sociale = :num_secu_sociale');
    $existingUserQuery->execute(array(':num_secu_sociale' => $num_secu));
    $rowCount = $existingUserQuery->fetchColumn();

    if ($rowCount > 0) {
        // L'utilisateur existe déjà, renvoyer un message d'erreur avec le code 409
        deliver_response(409, "L'utilisateur avec le numéro de sécurité sociale $num_secu existe déjà dans la base de données");
        return;
    }

    // Préparation de la requête SQL
    $query = "UPDATE usagers SET 
            civilite = :civilite,
            nom = :nom,
            prenom = :prenom,
            sexe = :sexe,
            adresse = :adresse,
            ville = :ville,
            code_postal = :code_postal,
            date_naissance = :date_naissance,
            lieu_naissance = :lieu_naissance,
            num_secu_sociale = :num_secu_sociale,
            id_medecin = :id_medecin
            WHERE id_usager = :id";

    // Exécution de la requête préparée
    try {
        $req = $linkpdo->prepare($query);
        $req->bindParam(':civilite', $data['civilite'], PDO::PARAM_STR);
        $req->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $req->bindParam(':prenom', $data['prenom'], PDO::PARAM_STR);
        $req->bindParam(':sexe', $data['sexe'], PDO::PARAM_STR);
        $req->bindParam(':adresse', $data['adresse'], PDO::PARAM_STR);
        $req->bindParam(':ville', $data['ville'], PDO::PARAM_STR);
        $req->bindParam(':code_postal', $data['code_postal'], PDO::PARAM_STR);
        // Formater la date de naissance au format Y-m-d
        $date_naissance = date('Y-m-d', strtotime($data['date_naissance']));
        $req->bindParam(':date_naissance', $date_naissance, PDO::PARAM_STR);
        $req->bindParam(':lieu_naissance', $data['lieu_naissance'], PDO::PARAM_STR);
        $req->bindParam(':num_secu_sociale', $data['num_secu_sociale'], PDO::PARAM_STR);
        $req->bindParam(':id_medecin', $data['id_medecin'], PDO::PARAM_INT);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        $linkpdo->beginTransaction(); // Démarage de la transaction
        $resExec = $req->execute();

        if (!$resExec) {
            die(deliver_response(500, "Erreur lors de la mise à jour de l'usager"));
        }

        $linkpdo->commit();
        afficher_usagers($linkpdo, $id); // Assurez-vous que la fonction afficher_usagers est définie pour récupérer les informations mises à jour de l'usager.
    } catch (PDOException $e) {
        // Appel de la fonction pour afficher l'erreur
        afficher_erreur_sql($e);
        return false; 
    }
}

// Fonction pour la suppression d'un usager
function supprimer_usager($linkpdo, $id) {
    try {
        // Commencer une transaction
        $linkpdo->beginTransaction();

        // Supprimer les rendez-vous associés à l'usager
        $deleteRendezVousQuery = $linkpdo->prepare('DELETE FROM rendezvous WHERE id_usager = :id');
        $deleteRendezVousQuery->execute(array('id' => $id));

        // Supprimer l'usager
        $deleteQuery = $linkpdo->prepare('DELETE FROM usagers WHERE id_usager = :id');
        $deleteQuery->execute(array('id' => $id));
        
        // Appliquer les changements
        $linkpdo->commit();

        // Vérifier si une ligne a été affectée (si l'identifiant existait)
        $rowCount = $deleteQuery->rowCount();

        if ($rowCount === 0) {
            // Si aucune ligne n'a été affectée, l'usager avec cet identifiant n'existait pas
            deliver_response(404, "Aucun usager trouvé pour l'identifiant spécifié");
            return null; // Retourner null pour indiquer une erreur
        }

        // L'usager a été supprimé avec succès
        deliver_response(200, "L'usager a été supprimé avec succès");
        return true;
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction et renvoyer un code d'erreur HTTP 500 (Erreur interne du serveur)
        $linkpdo->rollBack();
        deliver_response(500, "Erreur lors de la suppression de l'usager : " . $e->getMessage());
        return null; // Retourner null pour indiquer une erreur
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
