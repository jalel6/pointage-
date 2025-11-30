<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("Non autorisé");
}

include 'db.php';

$employe_id = $_SESSION['user_id'];

$sql = "UPDATE demandeconge 
        SET notification_vue = 1 
        WHERE employe_id = ? AND (statut = 'approuvé' OR statut = 'rejeté')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employe_id);

if ($stmt->execute()) {
    echo "Succès";
} else {
    echo "Erreur";
}
?>
