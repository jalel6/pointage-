<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM horaire WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: horaires_config.php?error=Horaire non trouvé");
        exit();
    }
    $horaire = $result->fetch_assoc();
} else {
    header("Location: horaires_config.php?error=ID manquant");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_periode'], $_POST['type'], $_POST['heure_debut'], $_POST['heure_fin'], $_POST['limite_retard'])) {
    $periode_id = $_POST['id_periode'];
    $type = $_POST['type'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $limite_retard = $_POST['limite_retard'];

    $stmt = $conn->prepare("UPDATE horaire SET id_periode = ?, type = ?, heure_debut = ?, heure_fin = ?, limite_retard = ? WHERE id = ?");
    $stmt->bind_param("issssi", $periode_id, $type, $heure_debut, $heure_fin, $limite_retard, $id);
    $stmt->execute();

    // Définir une variable de session pour success
    $_SESSION['success_message'] = "Horaire modifié avec succès";

    // Redirection pour afficher SweetAlert et rediriger ensuite
    header("Location: edit_horaire.php?id=" . $id);
    exit();
}

$periodes = $conn->query("SELECT * FROM periode ORDER BY date_debut DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Horaire</title>
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            outline: none;
            transition: border 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
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
        <h2>Modifier l'horaire</h2>
        <form method="POST">
            <div class="form-group">
                <label for="id_periode">Période</label>
                <select name="id_periode" id="id_periode" required>
                    <option value="">-- Choisir une période --</option>
                    <?php foreach ($periodes as $periode): ?>
                        <option value="<?= $periode['id'] ?>" <?= $horaire['id_periode'] == $periode['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($periode['nom_periode']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="type">Type</label>
                <select name="type" id="type" required>
                    <option value="matin" <?= $horaire['type'] === 'matin' ? 'selected' : '' ?>>Matin</option>
                    <option value="apres-midi" <?= $horaire['type'] === 'apres-midi' ? 'selected' : '' ?>>Après-midi</option>
                </select>
            </div>

            <div class="form-group">
                <label for="heure_debut">Heure de début</label>
                <input type="time" name="heure_debut" id="heure_debut" value="<?= $horaire['heure_debut'] ?>" required>
            </div>

            <div class="form-group">
                <label for="heure_fin">Heure de fin</label>
                <input type="time" name="heure_fin" id="heure_fin" value="<?= $horaire['heure_fin'] ?>" required>
            </div>

            <div class="form-group">
                <label for="limite_retard">Limite de retard (en minutes)</label>
                <input type="number" name="limite_retard" id="limite_retard" min="0" max="99" value="<?= $horaire['limite_retard'] ?>" required>
            </div>

            <button type="submit">Enregistrer les modifications</button>
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
