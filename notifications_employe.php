<?php
include 'db.php';
session_start();
$employe_id = $_SESSION['employe_id'];

$sql = "SELECT idDemande, dateSoumission, statut FROM demandeconge 
        WHERE employe_id = ? AND notification_vue = 0 AND (statut = 'approuvé' OR statut = 'rejeté')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<div class='notification'>";
    if ($row['statut'] === 'approuvé') {
        echo "Votre demande de congé du " . $row['dateSoumission'] . " a été <strong>approuvée</strong>.";
    } else {
        echo "Votre demande de congé du " . $row['dateSoumission'] . " a été <strong>rejetée</strong>.";
    }
    echo "</div>";
}

// Marquer comme vu après affichage
$update = "UPDATE demandeconge SET notification_vue = 1 
           WHERE employe_id = ? AND notification_vue = 0 AND (statut = 'approuvé' OR statut = 'rejeté')";
$stmt = $conn->prepare($update);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
?>
