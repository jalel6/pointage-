<?php
// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "pointage_saboura");

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de connexion : " . $conn->connect_error);
}

// Récupération de la date du jour
$date_aujourdhui = date("Y-m-d");

// Requête pour récupérer tous les employés qui n'ont PAS de pointage aujourd'hui
$sql = "
    SELECT e.id FROM employes e
    WHERE NOT EXISTS (
        SELECT 1 FROM pointages p
        WHERE p.employe_id = e.id AND p.date_pointage = ?
    )
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date_aujourdhui);
$stmt->execute();
$result = $stmt->get_result();

// Préparation de l'insertion dans la table pointages
$insert = $conn->prepare("INSERT INTO pointages (employe_id, statut, date_pointage) VALUES (?, 'Absent', ?)");

// Insertion des absents
$nombre_absents = 0;
while ($row = $result->fetch_assoc()) {
    $employe_id = $row['id'];
    $insert->bind_param("is", $employe_id, $date_aujourdhui);
    $insert->execute();
    $nombre_absents++;
}

// Affichage du résultat
echo "$nombre_absents employé(s) marqué(s) comme absent(s) pour le $date_aujourdhui.";

// Fermeture des connexions
$insert->close();
$stmt->close();
$conn->close();
?>
