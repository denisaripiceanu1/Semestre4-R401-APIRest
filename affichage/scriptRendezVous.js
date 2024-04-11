// L'URL de base de l'API
const baseUrl = 'http://beaujouripiceanu.alwaysdata.net';
const resource = '/api_cabinet_medical/api_consultations/api_consultations.php';

// Méthode pour effectuer un appel API GET pour récupérer toutes les consultations
function getAllConsultations() {
    fetch(`${baseUrl}${resource}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        displayData(data.data);
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour récupérer une consultation spécifique par son ID
function getConsultation() {
    var consultationID = document.getElementById('consultationID').value;
    
    fetch(`${baseUrl}${resource}/${consultationID}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        // Afficher les détails de la consultation récupérée
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour ajouter une nouvelle consultation
function addConsultation() {
    // Récupérer les valeurs des champs de saisie
    var usagerID = document.getElementById('consultationUsagerID').value;
    var medecinID = document.getElementById('consultationMedecinID').value;
    var date = document.getElementById('consultationDate').value;
    var heure = document.getElementById('consultationHeure').value;
    var duree = document.getElementById('consultationDuree').value;

    var newConsultationData = {
        usager_id: usagerID,
        medecin_id: medecinID,
        date: date,
        heure: heure,
        duree: duree
    };

    const requestOptions = {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newConsultationData)
    };

    fetch(`${baseUrl}${resource}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur de réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        alert('La nouvelle consultation a été ajoutée avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour mettre à jour une consultation
function updateConsultation() {
    var consultationID = document.getElementById('updateConsultationID').value;
    var newUsagerID = document.getElementById('updateConsultationUsagerID').value;
    var newMedecinID = document.getElementById('updateConsultationMedecinID').value;
    var newDate = document.getElementById('updateConsultationDate').value;
    var newHeure = document.getElementById('updateConsultationHeure').value;
    var newDuree = document.getElementById('updateConsultationDuree').value;

    var updateConsultationData = {
        usager_id: newUsagerID,
        medecin_id: newMedecinID,
        date: newDate,
        heure: newHeure,
        duree: newDuree
    };

    var method = document.querySelector('input[name="updateConsultationMethod"]:checked').value;

    const requestOptions = {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updateConsultationData)
    };

    fetch(`${baseUrl}${resource}/${consultationID}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur de réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        alert('La consultation a été mise à jour avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour supprimer une consultation
function deleteConsultation() {
    var consultationID = document.getElementById('deleteConsultationID').value;

    const requestOptions = {
        method: 'DELETE'
    };

    fetch(`${baseUrl}${resource}/${consultationID}`, requestOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau (' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log(data);
        alert('La consultation a été supprimée avec succès');
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

// Méthode pour afficher les données des consultations dans le tableau HTML
function displayConsultations(consultations) {
    const tableBody = document.getElementById('consultationTableBody');
    tableBody.innerHTML = '';

    consultations.forEach(consultation => {
        const row = tableBody.insertRow();
        row.insertCell(0).textContent = consultation.ID_Consultation;
        row.insertCell(1).textContent = consultation.Usager;
        row.insertCell(2).textContent = consultation.Medecin;
        row.insertCell(3).textContent = consultation.Date;
        row.insertCell(4).textContent = consultation.Heure;
        row.insertCell(5).textContent = consultation.Durée;
    });
}

// Attacher les événements aux boutons
document.getElementById('getAllConsultations').addEventListener('click', getAllConsultations);
document.getElementById('getConsultation').addEventListener('click', getConsultation);
document.getElementById('addConsultation').addEventListener('click', addConsultation);
document.getElementById('updateConsultation').addEventListener('click', updateConsultation);
document.getElementById('deleteConsultation').addEventListener('click', deleteConsultation);
