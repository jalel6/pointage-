<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'secrétaire') {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Traitement de la recherche
$historique = [];
if (isset($_POST['search'])) {
    $recherche = '%' . $_POST['search'] . '%';
    $sql = "SELECT p.*, e.nom, e.prenom 
            FROM pointage p 
            JOIN employes e ON p.employe_id = e.id 
            WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.cin LIKE ?
            ORDER BY p.date_pointage DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $recherche, $recherche, $recherche);
    $stmt->execute();
    $result = $stmt->get_result();
    $historique = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique Employé</title>
    <link rel="stylesheet" href="secritar.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px;
        }

        form {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"] {
            width: 300px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover {
            background-color: #eef3fc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Historique de Pointage d'un Employé</h2>

        <form method="post">
            <input type="text" name="search" placeholder="Nom, Prénom ou CIN" required>
            <button type="submit"><i class="fa fa-search"></i> Rechercher</button>
        </form>

        <?php if (!empty($historique)): ?>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Date</th>
                    <th>Heure d'entrée</th>
                    <th>Heure de sortie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historique as $ligne): ?>
                <tr>
                    <td><?= htmlspecialchars($ligne['nom']) ?></td>
                    <td><?= htmlspecialchars($ligne['prenom']) ?></td>
                    <td><?= htmlspecialchars($ligne['date_pointage']) ?></td>
                    <td><?= htmlspecialchars($ligne['heure_entree']) ?></td>
                    <td><?= htmlspecialchars($ligne['heure_sortie']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php elseif (isset($_POST['search'])): ?>
            <p style="text-align:center; color: red;">Aucun résultat trouvé.</p>
        <?php endif; ?>
    </div>
</body>
</html>
