<?php
$host = "db"; // ou localhost
$username = "user";
$password = "password";
$dbname = "pointage_saboura";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// ❌ Supprimez cette ligne
// echo "Connexion réussie !";
?>
