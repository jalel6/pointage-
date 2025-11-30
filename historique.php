<?php
session_start(); // Vérifier si l'utilisateur est connecté

// Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Récupérer l'ID de l'employé connecté
$employe_id = $_SESSION['user_id'];

try {
    // 🔥 IMPORTANT : dans Docker, le host = "db" et password = "root"
    $pdo = new PDO(
        'mysql:host=db;dbname=pointage_saboura;charset=utf8mb4',
        'root',
        'root'
    );

    // Options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupérer l'historique des pointages de l'employé
$stmt = $pdo->prepare("SELECT * FROM pointages WHERE employe_id = ? ORDER BY heure_arrivee DESC");
$stmt->execute([$employe_id]);
$pointages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique de Pointage</title>
    <link rel="stylesheet" href="historique_employe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body {
            background: url('images/tt.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1100px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(15px);
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #0d47a1;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            color: #0d47a1;
            font-size: 32px;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 18px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s ease;
            font-size: 14px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .back-btn i { margin-right: 6px; }
        .back-btn:hover { background-color: #1e88e5; transform: scale(1.05); }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-print:hover { background-color: #1e7e34; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border: 2px solid #0d47a1;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 14px 10px;
            text-align: center;
            border: 1px solid #ccc;
        }

        th {
            background-color: #0d47a1;
            color: white;
            text-transform: uppercase;
            font-size: 14px;
        }

        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #eef3fa; }

        img.photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #0d47a1;
        }
    </style>
</head>

<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa fa-arrow-left"></i> Retour
</a>

<button class="btn-print" onclick="window.print();">
    <i class="fa fa-print"></i> Imprimer
</button>

<div class="container">
    <h2>Historique de Pointage</h2>

    <?php if (!empty($pointages)): ?>
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Heure d'Arrivée</th>
                    <th>Heure de Départ</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pointages as $pointage): ?>
                    <tr>
                        <td>
                            <?php if (!empty($pointage['photo'])): ?>
                                <img class="photo" src="uploads/<?= htmlspecialchars($pointage['photo']) ?>" alt="Photo">
                            <?php else: ?>
                                Aucune photo
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($pointage['heure_arrivee'] ?? '') ?></td>
                        <td><?= htmlspecialchars($pointage['heure_depart'] ?? 'Non encore parti') ?></td>
                        <td><?= htmlspecialchars($pointage['statut'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun pointage trouvé.</p>
    <?php endif; ?>

</div>

</body>
</html>
