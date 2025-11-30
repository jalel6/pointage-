<?php
include 'db.php';

$aujourdhui = date("Y-m-d");

$sql = "SELECT e.nom, e.prenom, DATE(p.heure_arrivee) AS date_pointage,
               p.heure_arrivee, p.heure_depart, p.statut, p.photo
        FROM pointages p
        JOIN employes e ON p.employe_id = e.id
        WHERE DATE(p.heure_arrivee) = ?
        ORDER BY e.nom ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $aujourdhui);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pointages d'aujourd'hui</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background: #f1f1f1;
        }

        h2 {
            text-align: center;
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: white;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        img {
            max-width: 50px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h2>Pointages du <?php echo date("d/m/Y"); ?></h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Heure d'arrivée</th>
                <th>Heure de départ</th>
                <th>Statut</th>
                <th>Photo</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['nom']); ?></td>
                <td><?php echo htmlspecialchars($row['prenom']); ?></td>
                <td><?php echo htmlspecialchars($row['heure_arrivee']); ?></td>
                <td><?php echo htmlspecialchars($row['heure_depart']); ?></td>
                <td><?php echo htmlspecialchars($row['statut']); ?></td>
                <td>
                    <?php if (!empty($row['photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" width="50" height="50">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align:center; font-size:18px;">Aucun pointage pour aujourd'hui.</p>
    <?php endif; ?>
</body>
</html>
