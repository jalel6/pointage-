<?php
include 'db.php';

$mois = isset($_GET['mois']) ? $_GET['mois'] : '';
$annee = isset($_GET['annee']) ? $_GET['annee'] : '';

$whereClause = "p.heure_arrivee IS NOT NULL AND p.heure_depart IS NOT NULL";

if ($mois && $annee) {
    $whereClause .= " AND MONTH(p.heure_arrivee) = '$mois' AND YEAR(p.heure_arrivee) = '$annee'";
}

$sql = "
SELECT 
  CONCAT(e.nom, ' ', e.prenom) AS nom_complet,
  SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, p.heure_arrivee, p.heure_depart))) AS total_heures_travail
FROM 
  pointages p
JOIN 
  employes e ON p.employe_id = e.id
WHERE 
  $whereClause
GROUP BY 
  p.employe_id
";

$result = $conn->query($sql);

$dataPoints = [];
$maxHeures = 0;

while ($row = $result->fetch_assoc()) {
    list($h, $m, $s) = explode(':', $row['total_heures_travail']);
    $heuresDec = $h + ($m / 60) + ($s / 3600);
    $dataPoints[] = [$row['nom_complet'], round($heuresDec, 2)];
    if ($heuresDec > $maxHeures) $maxHeures = $heuresDec;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques des heures de travail</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
            padding: 30px;
            text-align: center;
        }
        #chart_div {
            width: 90%;
            height: 500px;
            margin: auto;
        }
        form {
            margin-bottom: 20px;
        }
        select, button {
            padding: 8px 12px;
            margin: 0 5px;
            font-size: 14px;
        }
        #exportBtn {
            background-color: #2c3e50;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2>Statistiques des heures de travail</h2>

    <form method="get">
        <label>Mois :
            <select name="mois">
                <option value="">Tous</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($m == $mois) ? 'selected' : '' ?>>
                        <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </label>

        <label>Année :
            <select name="annee">
                <option value="">Toutes</option>
                <?php
                $yearNow = date('Y');
                for ($y = $yearNow; $y >= $yearNow - 5; $y--) {
                    echo "<option value=\"$y\" " . ($y == $annee ? 'selected' : '') . ">$y</option>";
                }
                ?>
            </select>
        </label>

        <button type="submit">Rechercher</button>
        <button id="exportBtn" type="button">Exporter en PDF</button>
    </form>

    <div id="chart_div"></div>

    <script>
        google.charts.load('current', {'packages':['bar']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            const data = google.visualization.arrayToDataTable([
                ['Employé', 'Heures', { role: 'style' }],
                <?php
                $colors = ['#9C27B0', '#FF4081', '#03A9F4', '#4CAF50', '#FFC107', '#E91E63', '#00BCD4'];
                $colorIndex = 0;

                foreach ($dataPoints as $point) {
                    $color = $colors[$colorIndex % count($colors)];
                    echo "['{$point[0]}', {$point[1]}, 'color: $color'],";
                    $colorIndex++;
                }
                ?>
            ]);

            const options = {
                chart: {
                    title: 'Heures de travail par employé'
                },
                bars: 'horizontal',
                height: 500,
                legend: { position: 'none' },
                hAxis: {
                    title: 'Heures (h)',
                    minValue: 0
                },
                bar: {
                    groupWidth: "70%"
                }
            };

            const chart = new google.charts.Bar(document.getElementById('chart_div'));
            chart.draw(data, google.charts.Bar.convertOptions(options));
        }

        document.getElementById("exportBtn").addEventListener("click", function () {
            html2pdf().from(document.body).set({
                margin: 0.5,
                filename: 'statistiques_heures.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            }).save();
        });
    </script>
</body>
</html>
