<?php
include 'db.php';
session_start();

// Vérifie si c'est bien un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo 0;
    exit();
}

// Sélectionner uniquement les demandes en attente
$sql = "SELECT COUNT(*) AS nb FROM demandeconge WHERE statut = 'en attente'";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo $row['nb'];
} else {
    echo 0;
}
?>
