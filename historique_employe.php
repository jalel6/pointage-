<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['secrétaire', 'admin'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$historique = [];

if (isset($_POST['search'])) {
    $recherche = '%' . $_POST['search'] . '%';
    $sql = "SELECT p.*, e.nom, e.prenom, e.cin, e.photo_profil 
            FROM pointages p 
            JOIN employes e ON p.employe_id = e.id 
            WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.cin LIKE ?
            ORDER BY p.heure_arrivee DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $recherche, $recherche, $recherche);
    $stmt->execute();
    $result = $stmt->get_result();
    $historique = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $sql = "SELECT p.*, e.nom, e.prenom, e.cin, e.photo_profil 
            FROM pointages p 
            JOIN employes e ON p.employe_id = e.id 
            ORDER BY p.heure_arrivee DESC";
    $result = $conn->query($sql);
    $historique = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Pointages</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
   <!-- Remplace entièrement la balise <style> existante par celle-ci : -->
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

    .back-btn i {
        margin-right: 6px;
    }

    .back-btn:hover {
        background-color: #1e88e5;
        transform: scale(1.05);
    }

    form {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }

    form input[type="text"] {
        padding: 10px 15px;
        font-size: 16px;
        border: 2px solid #0d47a1;
        border-radius: 8px;
        width: 300px;
        margin-right: 10px;
        outline: none;
        transition: 0.3s ease;
    }

    form input[type="text"]:focus {
        border-color: #1e88e5;
        box-shadow: 0 0 6px rgba(30, 136, 229, 0.5);
    }

    form button {
        padding: 10px 20px;
        background-color: #0056b3;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease, transform 0.2s;
    }

    form button:hover {
        background-color: #1e88e5;
        transform: scale(1.05);
    }

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
        letter-spacing: 0.5px;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #eef3fa;
    }

    img.photo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #0d47a1;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }
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

.btn-print:hover {
    background-color: #1e7e34;
}


    @media screen and (max-width: 768px) {
        .container {
            padding: 20px 15px;
            margin: 30px 10px;
        }

        table {
            font-size: 13px;
        }

        img.photo {
            width: 45px;
            height: 45px;
        }

        .back-btn {
            top: 10px;
            left: 10px;
            padding: 8px 12px;
        }
    }
</style>


</head>
<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa fa-arrow-left"></i> Retour
</a>
<div >
    <button onclick="window.print()" class="btn-print">🖨️ Imprimer</button>
</div>

<div class="container">
    <h2>Historique de Pointage des Employés</h2>

    <form method="post">
        <input type="text" name="search" placeholder="Nom, Prénom ou CIN" required>
        <button type="submit"><i class="fa fa-search"></i> Rechercher</button>
    </form>

    <?php if (!empty($historique)): ?>
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>CIN</th>
                    <th>Heure d'arrivée</th>
                    <th>Heure de départ</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historique as $ligne): ?>
                    <tr>
                        <td>
                            <?php if (!empty($ligne['photo'])): ?>
                                <img class="photo" src="uploads/<?= htmlspecialchars($ligne['photo']) ?>" alt="photo">
                            <?php else: ?>
                                <img class="photo" src="default-photo.png" alt="photo">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ligne['nom']) ?></td>
                        <td><?= htmlspecialchars($ligne['prenom']) ?></td>
                        <td><?= htmlspecialchars($ligne['cin']) ?></td>
                        <td><?= htmlspecialchars($ligne['heure_arrivee']) ?></td>
                        <td><?= $ligne['heure_depart'] ? htmlspecialchars($ligne['heure_depart']) : '---' ?></td>
                        <td><?= htmlspecialchars($ligne['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($_POST['search'])): ?>
        <p style="text-align:center; color: red; margin-top: 20px;">Aucun résultat trouvé.</p>
    <?php endif; ?>
</div>

</body>
</html>
