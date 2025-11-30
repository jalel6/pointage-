<?php
include('db.php'); // Connexion à la base de données

if (isset($_POST['idDemande']) && isset($_POST['statut'])) {
    $idDemande = $_POST['idDemande'];
    $statut = $_POST['statut'];

    // Mettre à jour le statut de la demande
    $updateQuery = "UPDATE demandeconge SET statut = ?, notification_vue = 0 WHERE idDemande = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $statut, $idDemande);

    if ($stmt->execute()) {
        // Si le statut est "approuvé", on insère dans la table conge
        if ($statut === 'approuvé') {
            // Récupérer les infos de la demande
            $selectQuery = "SELECT employe_id, dateDebut, dateFin, type_conge, description FROM demandeconge WHERE idDemande = ?";
            $stmt = $conn->prepare($selectQuery);
            $stmt->bind_param("i", $idDemande);
            $stmt->execute();
            $result = $stmt->get_result();
            $demande = $result->fetch_assoc();

            if ($demande) {
                // Insertion dans la table conge si la demande est approuvée
                $insertQuery = "INSERT INTO conge (employe_id, dateDebut, dateFin, type_conge, description)
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("issss", 
                    $demande['employe_id'], 
                    $demande['dateDebut'], 
                    $demande['dateFin'], 
                    $demande['type_conge'], 
                    $demande['description']
                );
                $stmt->execute();
            }
        }

        // Rediriger vers la page de notifications après avoir mis à jour le statut
        header("Location: notifications.php");
        exit;
    } else {
        echo "Erreur lors de la mise à jour du statut.";
    }
}
?>