// L'URL de base de l'API
const baseUrl = 'http://beaujouripiceanu.alwaysdata.net';
const resource = '/api_cabinet_medical/api_medecins/api_medecins.php';

// Méthode pour récupérer tous les médecins
function getAllMedecins() {
    fetch(`${baseUrl}/api_cabinet_medical/api_medecins/api_medecins.php`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        displayData(data.data); // Afficher les médecins récupérés dans la page HTML
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour récupérer un médecin spécifique
function getMedecin() {
    var medecinID = document.getElementById('medecinID').value;

    fetch(`${baseUrl}/api_cabinet_medical/api_medecins/api_medecins.php/${medecinID}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        // Afficher les détails du médecin récupéré
        // Vous pouvez implémenter ici le code pour afficher les détails du médecin dans votre interface utilisateur
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour ajouter un nouveau médecin
function addMedecin() {
    var civilite = document.getElementById('newMedecinCivilite').value;
    var nom = document.getElementById('newMedecinNom').value;
    var prenom = document.getElementById('newMedecinPrenom').value;

    var newMedecinData = {
        civilite: civilite,
        nom: nom,
        prenom: prenom
    };

    const requestOptions = {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newMedecinData)
    };

    fetch(`${baseUrl}/api_cabinet_medical/api_medecins/api_medecins.php`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur de réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        alert('Le nouveau médecin a été ajouté avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour mettre à jour un médecin
function updateMedecin() {
    var medecinID = document.getElementById('updateMedecinID').value;
    var newCivilite = document.getElementById('updateMedecinCivilite').value;
    var newNom = document.getElementById('updateMedecinNom').value;
    var newPrenom = document.getElementById('updateMedecinPrenom').value;

    var updateMedecinData = {
        civilite: newCivilite,
        nom: newNom,
        prenom: newPrenom
    };

    const requestOptions = {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updateMedecinData)
    };

    fetch(`${baseUrl}/api_cabinet_medical/api_medecins/api_medecins.php/${medecinID}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur de réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        alert('Le médecin a été mis à jour avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour supprimer un médecin
function deleteMedecin() {
    var medecinID = document.getElementById('deleteMedecinID').value;

    const requestOptions = {
        method: 'DELETE'
    };

    fetch(`${baseUrl}/api_cabinet_medical/api_medecins/api_medecins.php/${medecinID}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        alert('Le médecin a été supprimé avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Attacher les événements aux boutons
document.getElementById('addMedecin').addEventListener('click', addMedecin);
document.getElementById('updateMedecin').addEventListener('click', updateMedecin);
document.getElementById('getAllMedecins').addEventListener('click', getAllMedecins);
document.getElementById('getMedecin').addEventListener('click', getMedecin);
document.getElementById('deleteMedecin').addEventListener('click', deleteMedecin);
