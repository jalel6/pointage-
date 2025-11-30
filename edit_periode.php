<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Récupérer l'ID de la période à modifier
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Récupérer les informations de la période
    $stmt = $conn->prepare("SELECT * FROM periode WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $periode = $stmt->get_result()->fetch_assoc();
}

// Modifier la période
if (isset($_POST['nom_periode'], $_POST['date_debut'], $_POST['date_fin'])) {
    $nom = $_POST['nom_periode'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];

    $stmt = $conn->prepare("UPDATE periode SET nom_periode = ?, date_debut = ?, date_fin = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nom, $date_debut, $date_fin, $id);
    $stmt->execute();

    // Redirection avec un paramètre de succès
    $_SESSION['success_message'] = "Période modifiée avec succès!";
    header("Location: edit_periode.php?id=" . $id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Période</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: url('images/timm.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-width: 95%;
        }

        h2 {
            text-align: center;
            color: #083b71;
            margin-bottom: 25px;
            font-weight: 700;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #083b71;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            outline: none;
            transition: border 0.3s ease;
        }

        .form-group input:focus {
            border-color: #083b71;
        }

        button {
            padding: 14px;
            background-color: #083b71;
            color: #fff;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #055085;
        }

        .back-btn {
            position: absolute;
            top: 25px;
            left: 25px;
            background-color: #083b71;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .back-btn:hover {
            background-color: #055085;
        }

        @media (max-width: 500px) {
            .container {
                padding: 30px 20px;
                width: 90%;
            }

            h2 {
                font-size: 20px;
            }

            button {
                font-size: 14px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <a class="back-btn" href="horaires_config.php">← Retour</a>
    <div class="container">
        <h2>Modifier la Période</h2>
        <form method="POST" id="edit-form">
            <div class="form-group">
                <label for="nom_periode">Nom de la Période</label>
                <input type="text" name="nom_periode" value="<?= htmlspecialchars($periode['nom_periode']) ?>" required>
            </div>
            <div class="form-group">
                <label for="date_debut">Date de Début</label>
                <input type="date" name="date_debut" value="<?= $periode['date_debut'] ?>" required>
            </div>
            <div class="form-group">
                <label for="date_fin">Date de Fin</label>
                <input type="date" name="date_fin" value="<?= $periode['date_fin'] ?>" required>
            </div>
            <button type="submit" id="save-button">Sauvegarder</button>
        </form>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            Swal.fire({
                title: 'Succès!',
                text: '<?= $_SESSION['success_message'] ?>',
                icon: 'success'
            }).then(function() {
                window.location.href = 'horaires_config.php'; // Redirection après le message
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</body>
</html>
