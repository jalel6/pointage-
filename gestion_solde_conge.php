<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$alerte = "";
$edit_mode = false;
$edit_id = $edit_poste = $edit_solde = "";

// Gestion ajout / modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $poste = $_POST['poste'];
    $solde = $_POST['solde'];

    if ($solde <= 0) {
        echo "<script>alert('Le solde doit être positif.');</script>";
    } else {
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = $_POST['edit_id'];
            $sql_update = "UPDATE soldeconge SET solde = ?, poste = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("isi", $solde, $poste, $edit_id);
            if ($stmt->execute()) {
                $alerte = "update";
            }
        } else {
            $sql_check = "SELECT * FROM soldeconge WHERE poste = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $poste);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $alerte = "deja_existe";
            } else {
                $sql_insert = "INSERT INTO soldeconge (poste, solde) VALUES (?, ?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bind_param("si", $poste, $solde);
                if ($stmt->execute()) {
                    $alerte = "insert";
                }
            }
        }
    }
}

// Récupération pour modification
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit_id'];
    $sql_get = "SELECT * FROM soldeconge WHERE id = ?";
    $stmt = $conn->prepare($sql_get);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row_edit = $result->fetch_assoc();
        $edit_poste = $row_edit['poste'];
        $edit_solde = $row_edit['solde'];
    }
}

// Suppression
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM soldeconge WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $alerte = "delete";
    }
}

// Récupérer tous les soldes
$sql_get_soldes = "SELECT * FROM soldeconge";
$result_soldes = $conn->query($sql_get_soldes);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Soldes</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Reset & Police */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Arrière-plan global */
body {
    background: linear-gradient(to right, #e3f2fd, #bbdefb);
    min-height: 100vh;
    padding: 100px 15px;
    position: relative;
}

/* Image de fond floutée */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('images/im3.jpg') no-repeat center center fixed;
    background-size: cover;
    filter: blur(6px);
    z-index: -1;
}

/* Container principal */
.container {
    max-width: 950px;
    margin: auto;
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    padding: 45px;
}



h2 {
    color: #0d47a1;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}

/* Formulaire */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 40px;
}

form label {
    font-weight: 600;
    color: #0d47a1;
}

form input {
    padding: 10px 14px;
    border: 1px solid #90caf9;
    border-radius: 8px;
    font-size: 16px;
    background-color: #e3f2fd;
    color: #0d47a1;
    transition: all 0.3s;
}

form input:focus {
    border-color: #1565c0;
    outline: none;
    background-color: #ffffff;
}

form button {
    width: fit-content;
    padding: 12px 24px;
    background-color: #007bff;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 16px;
    transition: background-color 0.3s ease;
    cursor: pointer;
}

form button:hover {
    background-color: #0d47a1;
}

/* Tableau */
h3 {
    color: #0d47a1;
    margin-bottom: 15px;
    font-size: 24px;
    border-bottom: 2px solid #90caf9;
    padding-bottom: 8px;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 12px;
    overflow: hidden;
    background-color: rgba(255, 255, 255, 0.95);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

thead {
    background-color: #0056b3;
    color: white;
}

thead th {
    text-align: left;
    padding: 16px 20px;
    font-size: 16px;
}

tbody tr {
    transition: background-color 0.3s ease;
}

tbody tr:hover {
    background-color: #e3f2fd;
}

tbody td {
    padding: 14px 20px;
    font-size: 15px;
    color: #424242;
    border-bottom: 1px solid #e0e0e0;
}

tbody td a {
    background-color:#007bff;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s;
}

tbody td a:hover {
    background-color: #0d47a1;
}


/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 25px;
    }

    form input,
    form button {
        width: 100%;
    }

    table,
    thead,
    tbody,
    th,
    td,
    tr {
        display: block;
        width: 100%;
    }

    thead {
        display: none;
    }

    tbody tr {
        margin-bottom: 15px;
        border: 1px solid #90caf9;
        border-radius: 10px;
        background-color: white;
        padding: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    tbody td {
        padding: 10px 15px;
        text-align: left;
        position: relative;
    }

    tbody td::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
        color: #1565c0;
        margin-bottom: 4px;
    }
}

    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 999;
    }

    .modal.active {
        display: flex;
    }

    .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 10px;
    }

    .modal-content {
        background: #fff;
        padding: 30px 25px;
        border-radius: 15px;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        position: relative;
        animation: fadeIn 0.4s ease-in-out;
    }

    .modal-content h3 {
        margin-bottom: 20px;
        color: #007BFF;
        font-size: 22px;
        text-align: center;
    }

    .modal-content label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .modal-content input {
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
    }

    .modal-content button {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
        margin-right: 10px;
        transition: background-color 0.3s ease;
    }

    .modal-content button[type="submit"] {
        background-color: #007BFF;
        color: white;
    }

    .modal-content button[type="submit"]:hover {
        background-color: #0056b3;
    }

    .modal-content button[type="button"] {
        background-color: #f1f1f1;
        color: #333;
    }

    .modal-content button[type="button"]:hover {
        background-color: #ddd;
    }/* Conteneur pour les boutons d'action */
/* Style pour le titre "Action" au-dessus des boutons */
.action-header 
{
    font-weight: bold;
    color: white; /* Couleur du texte */
    text-align: center; /* Centrer le texte */
    font-size: 16px;
    padding-bottom: 5px; /* Ajouter un petit espace sous le texte */
}

/* Conteneur pour les boutons d'action */
.action-buttons {
    display: flex;
    justify-content: center; /* Centrer les boutons */
    gap: 10px; /* Espace entre les boutons */
}

/* Style du bouton Modifier */
.modifier-btn {
    background-color: #007bff;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.modifier-btn:hover {
    background-color: #0d47a1;
}

/* Style du bouton Supprimer */
.supprimer-btn {
    background-color: #8f2630;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.supprimer-btn:hover {
    background-color: #c0392b;
}
.back-btn {
        position: fixed;
        top: 20px;
        left: 20px;
        background: #083b71;
        color: #fff;
        padding: 12px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        z-index: 999;
    }
    .back-btn:hover {
        background-color: #055085;
        transform: translateY(-2px);
    }


    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
</head>
<body>
<a href="admin_dashboard.php" class="back-btn" >
    Retour
</a>

<div class="container">
    <h2>Gestion des Soldes de Congé par Poste</h2>

    <!-- Formulaire d'ajout -->
    <form method="POST" action="gestion_solde_conge.php">
        <label for="poste">Poste :</label>
        <input type="text" id="poste" name="poste" placeholder="Ex: RH, Développeur..." required>
        <label for="solde">Solde (jours) :</label>
        <input type="number" name="solde" min="1" required>
        <button type="submit">Ajouter</button>
    </form>

    <h3>Soldes existants</h3>
    <table>
    <thead>
        <tr>
            <th>Poste</th>
            <th>Solde</th>
            <th>
                <div class="action-header">Action</div> <!-- Titre de la colonne Action -->
            </th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result_soldes->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['poste']); ?></td>
            <td><?php echo htmlspecialchars($row['solde']); ?> jours</td>
            <td>
                <!-- Conteneur pour les boutons -->
                <div class="action-buttons">
                    <a href="gestion_solde_conge.php?edit_id=<?php echo $row['id']; ?>" class="modifier-btn">Modifier</a>
                    <a href="#" class="supprimer-btn" onclick="confirmerSuppression(<?php echo $row['id']; ?>)">Supprimer</a>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>


</div>

<!-- Modale de modification -->
<?php if ($edit_mode): ?>
<div class="modal active" id="editModal">
    <div class="modal-content">
        <h3>Modifier Solde</h3>
        <form method="POST" action="gestion_solde_conge.php">
            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
            <label>Poste :</label>
            <input type="text" name="poste" value="<?php echo htmlspecialchars($edit_poste); ?>" required>
            <label>Solde :</label>
            <input type="number" name="solde" value="<?php echo $edit_solde; ?>" min="1" required>
            <div class="modal-buttons">
                <button type="submit">Enregistrer</button>
                <button type="button" onclick="closeModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>
<script>
function closeModal() {
    window.location.href = "gestion_solde_conge.php";
}
</script>
<?php endif; ?>

<!-- SweetAlert -->
<?php if ($alerte == "insert"): ?>
<script>
Swal.fire({ title: "Ajout réussi", text: "Le solde a été ajouté.", icon: "success" });
</script>
<?php elseif ($alerte == "update"): ?>
<script>
Swal.fire({ title: "Modification réussie", text: "Le solde a été mis à jour.", icon: "success" });
</script>
<?php elseif ($alerte == "deja_existe"): ?>
<script>
Swal.fire({ title: "Attention", text: "Ce poste existe déjà !", icon: "warning" });
</script>
<?php elseif ($alerte == "delete"): ?>
<script>
Swal.fire({ title: "Suppression réussie", text: "Le solde a été supprimé.", icon: "success" });
</script>
<?php endif; ?>

<!-- Confirmation JS pour suppression -->
<script>
function confirmerSuppression(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "gestion_solde_conge.php?delete_id=" + id;
        }
    });
}
</script>

</body>
</html>
