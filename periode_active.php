<?php
include 'db.php';
$date_auj = date('Y-m-d');

$sql = "SELECT * FROM periode WHERE '$date_auj' BETWEEN date_debut AND date_fin LIMIT 1";
$result = $conn->query($sql);

$periode = null;
$horaires = [];

if ($result->num_rows > 0) {
    $periode = $result->fetch_assoc();
    $periode_id = $periode['id'];

    $sql_horaires = "SELECT * FROM horaire WHERE id_periode = $periode_id";
    $result_horaires = $conn->query($sql_horaires);

    if ($result_horaires->num_rows > 0) {
        while ($row = $result_horaires->fetch_assoc()) {
            $horaires[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Période Active</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('images/not.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 18px;
            background: #0d47a1;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #08306b;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            margin-top: 80px;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        h2 {
            text-align: center;
            color: #0d47a1;
            margin-bottom: 25px;
            font-size: 30px;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
        }

        p {
            font-size: 18px;
            margin: 10px 0;
        }

        h4 {
            font-size: 22px;
            margin-top: 20px;
            color: #ff5722;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .horaire-block {
            margin: 8px 0;
            padding: 12px;
            background: #f0f4f7;
            border-left: 4px solid #0d47a1;
            border-radius: 8px;
        }

        .no-data {
            text-align: center;
            font-size: 18px;
            color: #d32f2f;
        }
    </style>
</head>
<body>

    <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Retour</a>

    <div class="container">
        <h2><i class="fa fa-calendar-alt"></i> Période Active</h2>

        <?php if ($periode): ?>
            <p><strong>Nom :</strong> <?= $periode['nom_periode'] ?></p>
            <p><strong>Du :</strong> <?= $periode['date_debut'] ?> <strong>au</strong> <?= $periode['date_fin'] ?></p>

            <?php if (!empty($horaires)): ?>
                <h4>Horaires :</h4>
                <?php foreach ($horaires as $horaire): ?>
                    <?php $type = ($horaire['type'] == 'matin') ? 'Matin' : 'Après-midi'; ?>
                    <div class="horaire-block">
                        <strong><?= $type ?> :</strong>
                        <?= $horaire['heure_debut'] ?> - <?= $horaire['heure_fin'] ?>
                        (Limite de retard : <?= $horaire['limite_retard'] ?> min)
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">Aucun horaire défini pour cette période.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="no-data">Aucune période active pour aujourd'hui (<?= $date_auj ?>).</p>
        <?php endif; ?>
    </div>

</body>
</html>
