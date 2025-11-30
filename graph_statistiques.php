<?php
include_once 'db.php';

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

while ($row = $result->fetch_assoc()) {
    list($h, $m, $s) = explode(':', $row['total_heures_travail']);
    $heuresDec = $h + ($m / 60) + ($s / 3600);
    $dataPoints[] = [$row['nom_complet'], round($heuresDec, 2)];
}
$conn->close();
?>
  <h2>Statistiques des heures de travail</h2>
<div id="stats-section" >
  

    <form method="get" >
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
        <button id="exportBtn" type="button">Exporter PDF</button>
    </form>

    <div id="chart_div" style="width: 100%; height: 500px;"></div>
</div>

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
google.charts.load('current', {'packages':['bar']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
    const data = google.visualization.arrayToDataTable([
        ['Employé', 'Heures', { role: 'style' }],
        <?php
        $colors = ['#9C27B0', '#FF4081', '#03A9F4', '#4CAF50', '#FFC107', '#E91E63', '#00BCD4'];
        $i = 0;
        foreach ($dataPoints as $point) {
            $color = $colors[$i % count($colors)];
            echo "['{$point[0]}', {$point[1]}, 'color: $color'],";
            $i++;
        }
        ?>
    ]);

    const options = {
        chart: {
            title: 'Heures de travail par employé'
        },
        bars: 'horizontal',
        legend: { position: 'none' },
        height: 500,
        hAxis: {
            title: 'Heures (h)',
            minValue: 0
        },
        bar: { groupWidth: "70%" }
    };

    const chart = new google.charts.Bar(document.getElementById('chart_div'));
    chart.draw(data, google.charts.Bar.convertOptions(options));
}

document.getElementById("exportBtn").addEventListener("click", function () {
    html2pdf().from(document.getElementById('stats-section')).set({
        margin: 0.5,
        filename: 'statistiques_heures.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
    }).save();
});
</script>
