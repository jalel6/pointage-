<?php
// Pas d'espace ni de ligne vide avant ce tag
session_start();
include 'db.php';
// Vérification du rôle
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'secrétaire'])) {
    header("Location: index.php");
    exit();
}
$user_role = $_SESSION['user_role'];

// Query to fetch the list of approved leaves
$sql = "SELECT c.*, e.nom, e.prenom 
        FROM conge c 
        JOIN employes e ON c.employe_id = e.id 
        ORDER BY c.date_ajout DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Congés Approuvés</title>
    <style>
       * {
    box-sizing: border-box;
}

body {
    margin: 0;
    
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    
    background-size: cover;
    color: #333;
}



.top-bar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-back {
    background-color: #007BFF;
    color: white;
}

.btn-back:hover {
    background-color: #0056b3;
}

.btn-print {
    background-color: #28a745;
    color: white;
    font-weight: bold;
}

.btn-print:hover {
    background-color: #1e7e34;
}

.search-container {
    flex: 1;
    text-align: center;
}

.search-container input {
    width: 100%;
    max-width: 350px;
    padding: 12px 15px;
    font-size: 16px;
    border: 2px solid #ced4da;
    border-radius: 8px;
    outline: none;
    transition: 0.3s;
}

.search-container input:focus {
    border-color: #007BFF;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
}

.table-container {
    overflow-x: auto;
    background-color: rgba(255, 255, 255, 0.95);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 14px 16px;
    text-align: center;
    border-bottom: 1px solid #ccc;
}

th {
    background-color: #007BFF;
    color: white;
    text-transform: uppercase;
    font-size: 14px;
}

tr:nth-child(even) {
    background-color: #f8f9fa;
}

tr:hover {
    background-color: #e2e6ea;
}

.description {
    max-width: 300px;
    word-wrap: break-word;
    white-space: pre-wrap;
    text-align: left;
}

@media print {
    .top-bar, .search-container {
        display: none;
    }

    .table-container {
        box-shadow: none;
        background: none;
        padding: 0;
    }

    table {
        font-size: 12px;
    }

    th, td {
        padding: 8px;
    }
}

    </style>
</head>
<body>

<div class="top-bar">
    
    
    <!-- Search bar (same as before) -->
    <div class="search-container">
        <input type="text" id="search" placeholder="Rechercher un employé...">
    </div>

    <button onclick="window.print()" class="btn btn-print">🖨️ Imprimer</button>
</div>

<h2>📅 Liste des Congés Approuvés</h2>

<div class="table-container">
    <table id="leave-table">
        <thead>
            <tr>
                <th>Nom de l'employé</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Type de congé</th>
                <th>Description</th>
                <th>Date d'ajout</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?></td>
                        <td><?= htmlspecialchars($row['dateDebut']) ?></td>
                        <td><?= htmlspecialchars($row['dateFin']) ?></td>
                        <td><?= htmlspecialchars($row['type_conge']) ?></td>
                        <td class="description"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                        <td><?= htmlspecialchars($row['date_ajout']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">Aucun congé approuvé trouvé.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('search').addEventListener('keyup', function () {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#leave-table tbody tr');

        rows.forEach(row => {
            const nameCell = row.querySelector('td');
            if (nameCell) {
                const nameText = nameCell.textContent.toLowerCase();
                row.style.display = nameText.includes(searchValue) ? '' : 'none';
            }
        });
    });
</script>

</body>
</html>
