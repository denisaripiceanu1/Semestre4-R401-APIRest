// L'URL de base de l'API
const baseUrl = 'http://beaujouripiceanu.alwaysdata.net';
const resource = '/api_cabinet_medical/api_usagers/api_usagers.php';

// Méthode pour effectuer un appel API GET pour récupérer tous les usagers
function getAllUsagers() {
    // Effectuer une requête GET pour récupérer tous les usagers
    fetch(`${baseUrl}${resource}`)
    .then(response => {
        // Vérifier si la requête a réussi
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json(); // Convertir la réponse en JSON
    })
    .then(data => {
        console.log(data);
        displayData(data.data); // Afficher les usagers récupérés dans la page HTML
    })
    .catch(error => console.error('Erreur Fetch:', error));
    // Affichage d'un message dans une boîte de dialogue pour l'exemple
    alert('J\'affiche les informations de la réponse HTTP dans la zone en dessous du bouton \n et toutes les phrases dans la zone en bas de page');
}

// Méthode pour récupérer un usager spécifique
function getUsager() {
    var usagerID = document.getElementById('usagerID').value;
    // Construire le message à afficher
    var message = 'Le contenu de la balise est : ' + valeurDeLaBalise;
    // Afficher un message dans une boîte de dialogue pour l'exemple
    alert(message);
    fetch(`${baseUrl}${resource}/${usagerID}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        // Afficher les détails de l'usager récupéré
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour ajouter un nouvel usager
function addUsager() {
    // Récupérer les valeurs des champs de saisie
    var civilite = document.getElementById('newUsagerCivilite').value;
    var nom = document.getElementById('newUsagerNom').value;
    var prenom = document.getElementById('newUsagerPrenom').value;
    var sexe = document.getElementById('newUsagerSexe').value;
    var adresse = document.getElementById('newUsagerAdresse').value;
    var ville = document.getElementById('newUsagerVille').value;
    var codePostal = document.getElementById('newUsagerCodePostal').value;
    var dateNaissance = document.getElementById('newUsagerDateNaissance').value;
    var lieuNaissance = document.getElementById('newUsagerLieuNaissance').value;
    var numSecu = document.getElementById('newUsagerNumSecu').value;
    var medecinReferent = document.getElementById('newUsagerMedecinReferent').value;

    // Construire l'objet de données à envoyer
    var newUsagerData = {
        civilite: civilite,
        nom: nom,
        prenom: prenom,
        sexe: sexe,
        adresse: adresse,
        ville: ville,
        code_postal: codePostal,
        date_naissance: dateNaissance,
        lieu_naissance: lieuNaissance,
        num_secu_sociale: numSecu,
        medecin_referent: medecinReferent
    };

    // Options de requête
    const requestOptions = {
        method: 'POST', // Méthode HTTP POST pour ajouter un nouvel usager
        headers: { 'Content-Type': 'application/json' }, // Type de contenu
        body: JSON.stringify(newUsagerData) // Corps de la requête (les données du nouvel usager à ajouter)
    };

    // Effectuer la requête pour ajouter un nouvel usager
    fetch(`${baseUrl}${resource}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur de réseau (' + response.status + ')');
        }
        return response.json(); // Convertir la réponse en JSON
    })
    .then(data => {
        console.log(data); // Afficher en console les données récupérées
        // Afficher un message indiquant que l'usager a été ajouté avec succès
        alert('Le nouvel usager a été ajouté avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error)); // Gérer les erreurs
}

// Méthode pour mettre à jour un usager
function updateUsager() {
    // Récupérer les valeurs des champs de saisie
    var usagerID = document.getElementById('updateUsagerID').value;
    var newCivilite = document.getElementById('updateUsagerCivilite').value;
    var newNom = document.getElementById('updateUsagerNom').value;
    var newPrenom = document.getElementById('updateUsagerPrenom').value;
    var newSexe = document.getElementById('updateUsagerSexe').value;
    var newAdresse = document.getElementById('updateUsagerAdresse').value;
    var newVille = document.getElementById('updateUsagerVille').value;
    var newCodePostal = document.getElementById('updateUsagerCodePostal').value;
    var newDateNaissance = document.getElementById('updateUsagerDateNaissance').value;
    var newLieuNaissance = document.getElementById('updateUsagerLieuNaissance').value;
    var newNumSecu = document.getElementById('updateUsagerNumSecu').value;
    var newMedecinReferent = document.getElementById('updateUsagerMedecinReferent').value;

    // Récupérer la méthode de mise à jour sélectionnée (PATCH ou PUT)
    var method = document.querySelector('input[name="updateMethod"]:checked').value;

    // Construire l'objet de données à envoyer
    var updateUsagerData = {
        civilite: newCivilite,
        nom: newNom,
        prenom: newPrenom,
        sexe: newSexe,
        adresse: newAdresse,
        ville: newVille,
        code_postal: newCodePostal,
        date_naissance: newDateNaissance,
        lieu_naissance: newLieuNaissance,
        num_secu_sociale: newNumSecu,
        medecin_referent: newMedecinReferent
    };

    // Options de requête
    const requestOptions = {
        method: method, // Méthode HTTP sélectionnée (PATCH ou PUT)
        headers: { 'Content-Type': 'application/json' }, // Type de contenu
        body: JSON.stringify(updateUsagerData) // Corps de la requête (les données à mettre à jour)
    };

    // Effectuer la requête pour mettre à jour un usager
    fetch(`${baseUrl}${resource}/${usagerID}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur de réseau (' + response.status + ')');
        }
        return response.json(); // Convertir la réponse en JSON
    })
    .then(data => {
        console.log(data); // Afficher en console les données récupérées
        // Afficher un message indiquant que l'usager a été mis à jour avec succès
        alert('L\'usager a été mis à jour avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error)); // Gérer les erreurs
}

// Méthode pour supprimer un usager
function deleteUsager() {
    var usagerID = document.getElementById('deleteUsagerID').value;
    // Options de requête
    const requestOptions = {
        method: 'DELETE' // Utilisation de la méthode DELETE
    };
    fetch(`${baseUrl}${resource}/${usagerID}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        // Afficher un message de confirmation de suppression
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour afficher les données des usagers dans le tableau HTML
function displayData(usagers) {
    const tableBody = document.getElementById('responseTableBody');
    tableBody.innerHTML = ''; // Nettoyer le tableau avant de le remplir

    usagers.forEach(usager => {
        const row = tableBody.insertRow();
        row.insertCell(0).textContent = usager.ID_Usager;
        row.insertCell(1).textContent = usager.civilite;
        row.insertCell(2).textContent = usager.nom;
        row.insertCell(3).textContent = usager.prenom;
        row.insertCell(4).textContent = usager.sexe;
        row.insertCell(5).textContent = usager.adresse;
        row.insertCell(6).textContent = usager.ville;
        row.insertCell(7).textContent = usager.code_postal;
        row.insertCell(8).textContent = usager.date_naissance;
        row.insertCell(9).textContent = usager.lieu_naissance;
        row.insertCell(10).textContent = usager.num_secu_sociale;
        row.insertCell(11).textContent = usager.medecin_referent;
    });
}
// Mise à jour de la fonction pour afficher les informations de réponse
function displayInfoResponse(baliseInfo, info) {
    if (info) {
        baliseInfo.textContent = `Statut: ${info.status}, Code: ${info.status_code}, Message: ${info.status_message}`;
        baliseInfo.style.display = 'block';
    } else {
        baliseInfo.style.display = 'none';
    }
}
// Attacher les événements aux boutons
document.getElementById('addUsager').addEventListener('click', addUsager);
document.getElementById('updateUsager').addEventListener('click', updateUsager);
document.getElementById('getAllUsagers').addEventListener('click', getAllUsagers);
document.getElementById('getUsager').addEventListener('click', getUsager);
document.getElementById('deleteUsager').addEventListener('click', deleteUsager);
