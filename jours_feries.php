<?php
session_start();

// Vérification de la connexion
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['secrétaire', 'admin'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Ajout jour férié
if (isset($_POST['date_ferie'], $_POST['nom_ferie'], $_POST['type_ferie'])) {
    $date = $_POST['date_ferie'];
    $nom_ferie = $_POST['nom_ferie'];
    $type_ferie = $_POST['type_ferie'];

    // Vérification si la date existe déjà pour un jour férié standard
    if ($type_ferie == 'standard') {
        $day_month = date('m-d', strtotime($date));
    
        // Modifier ici les années souhaitées
        $startYear = 2025;
        $endYear = 2035;
    
        // Vérification des doublons avant l'insertion
        for ($y = $startYear; $y <= $endYear; $y++) {
            $full_date = "$y-$day_month";
    
            // Vérifier si ce jour standard existe déjà
            $stmt = $conn->prepare("SELECT * FROM jours_feries WHERE DATE(date_ferie) = DATE(?) AND type_ferie = 'standard'");
            $stmt->bind_param("s", $full_date);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $_SESSION['duplicate'] = true;
                break;
            }
        }

        if (!isset($_SESSION['duplicate'])) {
            // Insérer le jour férié si aucun doublon
            for ($y = $startYear; $y <= $endYear; $y++) {
                $full_date = "$y-$day_month";
                $insert = $conn->prepare("INSERT INTO jours_feries (date_ferie, nom_ferie, type_ferie) VALUES (?, ?, 'standard')");
                $insert->bind_param("ss", $full_date, $nom_ferie);
                $insert->execute();
            }
            $_SESSION['added'] = true;
        }
    } else {
        // Vérification des doublons pour un jour personnalisé
        $stmt = $conn->prepare("SELECT * FROM jours_feries WHERE DATE(date_ferie) = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['duplicate'] = true;
        } else {
            $stmt = $conn->prepare("INSERT INTO jours_feries (date_ferie, nom_ferie, type_ferie) VALUES (?, ?, 'personnalise')");
            $stmt->bind_param("ss", $date, $nom_ferie);
            $stmt->execute();
            $_SESSION['added'] = true;
        }
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Récupération des jours fériés
$year = date('Y');
if (isset($_POST['search_year'])) {
    $year = $_POST['search_year'];
}

// Récupération des jours fériés
$stmt = $conn->prepare("SELECT * FROM jours_feries WHERE YEAR(date_ferie) = ? ORDER BY date_ferie DESC");
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();
$jours = $result->fetch_all(MYSQLI_ASSOC);

// Années disponibles
$years_result = $conn->query("SELECT DISTINCT YEAR(date_ferie) AS year FROM jours_feries ORDER BY year DESC");
$years = $years_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Jours Fériés</title>
    <link rel="stylesheet" href="joufer.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background-image: url('images/im3.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.container {
    background-image: url('images/da.jpg');
    width: 100%;
    max-width: 1100px;
    background-color: rgba(255, 255, 255, 0.95);
    padding: 40px 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    position: relative;
    border: 2px solid #007bff; /* Bordure ajoutée */
}

h2 {
    text-align: center;
    color: #004c99;
    font-weight: 600;
    font-size: 28px;
    margin: 0 auto 30px auto; /* Centré avec auto */
    letter-spacing: 1px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    display: inline-block;
    
}


form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
    border: 1px solid #ccc; /* Bordure autour du form */
    padding: 20px;
    border-radius: 12px;
}

input[type="text"],
input[type="date"],
select {
    padding: 14px 18px;
    width: 250px;
    border: 2px solid #007bff; /* Bordure bleue pro */
    border-radius: 12px;
    font-size: 16px;
    background-color: #fff;
    color: #333;
    transition: all 0.3s ease;
}

input:focus,
select:focus {
    border-color: #0056b3;
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.25);
    outline: none;
}

button {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 14px 28px;
    border: 2px solid #004a99; /* Bordure bouton */
    border-radius: 12px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.35);
    transition: all 0.3s ease;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 123, 255, 0.45);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background-color: white;
    border: 2px solid #007bff; /* Bordure autour du tableau */
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

th, td {
    padding: 16px;
    text-align: center;
    font-size: 15px;
    color: #333;
    border-bottom: 1px solid #eee;
}

th {
    background-color: #0056b3;
    color: white;
    font-weight: 600;
    border-right: 1px solid #005bb5;
}

tr:hover {
    background-color: #f1f7ff;
}

tr:last-child td {
    border-bottom: none;
}

.delete {
    color: #e60023;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.delete:hover {
    color: #a30015;
    text-decoration: underline;
}

.back-link {
    position: absolute;
    top: 20px;
    left: 30px;
    text-decoration: none;
    color: #007bff;
    font-size: 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.back-link i {
    margin-right: 8px;
    font-size: 20px;
}

.back-link:hover {
    color: #004c99;
}
.back-btn {
    position: fixed;
    top: 20px;
    left: 20px;
    padding: 10px 20px;
    background: #0056b3;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 14px;
    z-index: 10;
    transition: 0.3s ease;
    display: flex;
    align-items: center;
}

.back-btn i {
    margin-right: 8px;
}

.back-btn:hover {
    background-color: #054a99;
    transform: scale(1.05);
}

/* Responsive design */
@media (max-width: 768px) {
    .container {
        padding: 30px 20px;
    }

    form {
        flex-direction: column;
        align-items: center;
    }

    input[type="text"],
    input[type="date"],
    select {
        width: 100%;
    }

    button {
        width: 100%;
        padding: 14px 0;
    }

    th, td {
        font-size: 14px;
        padding: 12px;
    }

    h2 {
        font-size: 24px;
    }

    .back-link {
        font-size: 15px;
    }
}
</style>
<body>
<div class="container">
<a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Retour</a>
<h2>Gestion des jours fériés</h2>

<!-- Formulaire ajout -->
<form method="post">
    <input type="date" name="date_ferie" required>
    <input type="text" name="nom_ferie" placeholder="Ex: Fête Nationale" required>
    <select name="type_ferie" required>
        <option value="standard">Jour férié standard</option>
        <option value="personnalise">Jour férié personnalisé</option>
    </select>
    <button type="submit">Ajouter</button>
</form>

<!-- Formulaire filtre année -->
<form method="post" style="justify-content: flex-start;">
    <select name="search_year" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?>
            <option value="<?= $y['year']; ?>" <?= ($y['year'] == $year) ? 'selected' : ''; ?>>
                <?= $y['year']; ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<!-- Tableau des jours fériés -->
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Nom</th>
            <th>Type</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($jours) > 0): ?>
            <?php foreach ($jours as $j): ?>
                <tr>
                    <td><?= htmlspecialchars($j['date_ferie']) ?></td>
                    <td><?= htmlspecialchars($j['nom_ferie']) ?></td>
                    <td><?= ucfirst($j['type_ferie']) ?></td>
                    <td><a href="javascript:void(0);" class="delete" onclick="confirmDelete(<?= $j['id'] ?>)">Supprimer</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">Aucun jour férié trouvé pour l'année <?= $year ?>.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<!-- Alertes SweetAlert -->
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cela supprimera définitivement ce jour férié.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer!',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete=" + id;
        }
    });
}

<?php if (isset($_SESSION['added'])): ?>
Swal.fire('Succès', 'Jour férié ajouté.', 'success');
<?php unset($_SESSION['added']); endif; ?>

<?php if (isset($_SESSION['duplicate'])): ?>
Swal.fire('Erreur', 'Cette date de jour férié existe déjà.', 'error');
<?php unset($_SESSION['duplicate']); endif; ?>

<?php if (isset($_SESSION['deleted'])): ?>
Swal.fire('Supprimé', 'Jour férié supprimé.', 'success');
<?php unset($_SESSION['deleted']); endif; ?>
</script>
</body>
</html>

