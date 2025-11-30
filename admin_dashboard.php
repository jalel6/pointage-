<?php
session_start();

// Vérification de la session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Connexion à la base de données
include 'db.php';
include 'verifier_retards_absences.php';

// Récupérer le nombre de notifications de demandes de congé en attente
$sql_notif = "SELECT COUNT(*) AS total FROM demandeconge WHERE statut = 'en attente'";
$result_notif = $conn->query($sql_notif);
$row_notif = $result_notif->fetch_assoc();
$notif_count = $row_notif['total'];

// Récupérer les informations de l'admin connecté
$admin_id = $_SESSION['user_id'];
$query = "SELECT nom, prenom, photo_profil FROM employes WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    die("Erreur : Impossible de récupérer les informations de l'utilisateur.");
}

$nom_complet = htmlspecialchars($admin['prenom'] . " " . $admin['nom']);
$image_path = !empty($admin['photo_profil']) ? htmlspecialchars($admin['photo_profil']) : "images/default.jpg";

// Lire le fichier de notifications (notifications_journalieres.txt)
$file = 'notifications_journalieres.txt';
$notifications = file($file, FILE_IGNORE_NEW_LINES);

// Filtrer uniquement les notifications d'aujourd'hui pour les retards et absences
$retard_absence_count = 0;
$today = date('Y-m-d');

foreach ($notifications as $line) {
    // Vérifie que la ligne contient la date d'aujourd'hui et soit "Absent" soit "Retard"
    if (strpos($line, $today) !== false && (strpos($line, 'Absent') !== false || strpos($line, 'Retard') !== false)) {
        $retard_absence_count++;
    }
}
// Nombre total d'employés
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM employes");
$row = mysqli_fetch_assoc($result);
$total_employes = $row['total'];

// Nombre de demandes de congé en attente
$result = mysqli_query($conn, "SELECT COUNT(*) as attente FROM demandeconge WHERE statut = 'En attente'");
$row = mysqli_fetch_assoc($result);
$conges_attente = $row['attente'];

// Nombre de congés approuvés
$result = mysqli_query($conn, "SELECT COUNT(*) as approuves FROM demandeconge WHERE statut = 'Approuvé'");
$row = mysqli_fetch_assoc($result);
$conges_approuves = $row['approuves'];

// Nombre d'employés présents aujourd'hui à partir du fichier notifications_journalieres.txt
$present_aujourdhui = 0;
$today = date("Y-m-d");

$filepath = 'notifications_journalieres.txt';
if (file_exists($filepath)) {
    $lines = file($filepath);
    foreach ($lines as $line) {
        // Exemple de ligne : [2025-05-10] Présence: Employé 5 présent
        if (strpos($line, $today) !== false && strpos($line, 'Présence:') !== false) {
            $present_aujourdhui++;
        }
    }
}






// Récupération des pointages du jour
$aujourdhui = date("Y-m-d");
$sql = "SELECT e.nom, e.prenom, DATE(p.heure_arrivee) AS date_pointage,
               p.heure_arrivee, p.heure_depart, p.statut, p.photo
        FROM pointages p
        JOIN employes e ON p.employe_id = e.id
        WHERE DATE(p.heure_arrivee) = ?
        ORDER BY e.nom ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $aujourdhui);
$stmt->execute();
$result_pointages = $stmt->get_result();

// Récupération des statistiques d'heures de travail
$mois = isset($_GET['mois']) ? $_GET['mois'] : date('m');
$annee = isset($_GET['annee']) ? $_GET['annee'] : date('Y');

$whereClause = "p.heure_arrivee IS NOT NULL AND p.heure_depart IS NOT NULL";

if ($mois && $annee) {
    $whereClause .= " AND MONTH(p.heure_arrivee) = '$mois' AND YEAR(p.heure_arrivee) = '$annee'";
}

$sql_stats = "
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

$result_stats = $conn->query($sql_stats);

$dataPoints = [];
$maxHeures = 0;

while ($row = $result_stats->fetch_assoc()) {
    list($h, $m, $s) = explode(':', $row['total_heures_travail']);
    $heuresDec = $h + ($m / 60) + ($s / 3600);
    $dataPoints[] = [
        'nom' => $row['nom_complet'], 
        'heures' => round($heuresDec, 2)
    ];
    if ($heuresDec > $maxHeures) $maxHeures = $heuresDec;
}
?>       

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <meta http-equiv="refresh" content="300"> <!-- Refresh toutes les 5 minutes au lieu de 10 secondes -->


</head>
<body>
    <input type="checkbox" id="checkbox">
    <header class="header">
        <label for="checkbox" class="menu-icon"><i class="fa fa-bars"></i></label>
        <h2 class="u-name">AHWA <b>SOLUTIONS</b></h2>

        <div class="header-actions">
            <div class="dropdown">
                <a href="#" class="btn"><i class="fa fa-users"></i> GESTION EMPLOYÉS</a>
                <div class="dropdown-content">
                    <a href="liste_employes.php"><i class="fa fa-users"></i> Consulter la liste</a>
                    <a href="inscription1.php"><i class="fa fa-user-plus"></i> Ajouter un employé</a>
                    <a href="modifier_employe.php"><i class="fa fa-pencil-square-o"></i> Modifier un employé</a>
                    <a href="supprimer_employe.php"><i class="fa fa-user-times"></i> Supprimer un employé</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#" class="btn"><i class="fa fa-cog"></i> OUTILS ADMIN</a>
                <div class="dropdown-content">
                    <a href="gestion_solde_conge.php"><i class="fa fa-calendar-check-o"></i> Solde_config</a>
                    <a href="horaires_config.php"><i class="fa fa-clock-o"></i> Horaires_config</a>
                    <a href="jours_feries.php"><i class="fa fa-calendar-plus-o"></i> Jours Fériés_config</a>
                    
                </div>
            </div>

            <!-- Pointage -->
            <a href="pointages.php" class="pointage-icon">
                <i class="fa fa-camera"></i>
            </a>

            <!-- Notifications -->
            <a href="notifications.php" class="notif-icon">
                <i class="fa fa-bell"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="notif-count"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>

            <!-- Icône pour retards et absences -->
            <a href="notification_retard_absent.php" class="notif-icon">
                <i class="fa fa-exclamation-circle"></i>
                <?php if ($retard_absence_count > 0): ?>
                    <span class="notif-count" style="background: #FFC107;"><?= $retard_absence_count ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="body">
        <nav class="side-bar">
            <div class="user-p">
                <img src="<?php echo $image_path; ?>" alt="Photo de profil">
                <h4><?php echo $nom_complet; ?></h4>
            </div>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fa fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profil_admin.php"><i class="fa fa-id-badge"></i><span>Mon Profil</span></a></li>
                <li><a href="horaires.php"><i class="fa fa-calendar"></i><span>Horaires du Travail</span></a></li>
                <li><a href="lconge.php"><i class="fa fa-calendar-check-o"></i><span>Liste de congés</span></a></li>
                <li><a href="affiche_feries.php"><i class="fa fa-flag"></i><span>Jours Fériés</span></a></li>
                <li><a href="historique.php"><i class="fa fa-history"></i><span>Historique Personnel</span></a></li>
                <li><a href="historique_employe.php"><i class="fa fa-history"></i><span>Historique Employé</span></a></li>
                <li><a href="#" class="logout-btn"> <i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>


            </ul>
        </nav>
       
        <div class="content">
        
    <div class="dashboard-stats-container">
        <div class="stat-card employees">
            <i class="fa fa-users stat-card-icon"></i>
            <h3 class="stat-card-title">Nombre total d'employés</h3>
            <p class="stat-card-value"><?= $total_employes ?></p>
        </div>
        <div class="stat-card pending-leave">
            <i class="fa fa-calendar-o stat-card-icon"></i>
            <h3 class="stat-card-title">Demandes de congé en attente</h3>
            <p class="stat-card-value"><?= $conges_attente ?></p>
        </div>
        <div class="stat-card approved-leave">
            <i class="fa fa-calendar-check-o stat-card-icon"></i>
            <h3 class="stat-card-title">Congés approuvés</h3>
            <p class="stat-card-value"><?= $conges_approuves ?></p>
        </div>
        <div class="stat-card present-today">
            <i class="fa fa-clock-o stat-card-icon"></i>
            <h3 class="stat-card-title">Employés présents aujourd'hui</h3>
            <p class="stat-card-value"><?= $present_aujourdhui ?></p>
        </div>
    </div> 
     <div class="content">
            <!-- Ajout des boutons centraux -->
            <div class="central-buttons">
                <a href="horaires.php" class="central-button button-horaires">
                    <div class="icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <h3>Horaires du Travail</h3>
                </a>

                <a href="affiche_feries.php" class="central-button button-conge">
                    <div class="icon">
                        <i class="fa fa-calendar-plus-o"></i>
                    </div>
                    <h3>Jours Fériés</h3>
                </a>

                <a href="historique.php" class="central-button button-feries">
                    <div class="icon">
                        <i class="fa fa-calendar-check-o"></i>
                    </div>
                    <h3>Historique Personnel</h3>
                </a>

                <a href="historique_employe.php" class="central-button button-liste-conges">
                    <div class="icon">
                        <i class="fa fa-list-alt"></i>
                    </div>
                    <h3>Historique Employé</h3>
                </a>
            </div>

            <!-- Section statistiques -->
            <div class="section-container">
                <div class="section-header">
                    <span>Statistiques des heures de travail</span>
                    <button id="exportBtn" style="background:transparent; border:none; color:white; cursor:pointer;">
                        <i class="fa fa-download"></i> Exporter PDF
                    </button>
                </div>
                <div class="section-body">
                    <form method="get" class="stats-filters">
                        <label>
                            <span>Mois :</span>
                            <select name="mois">
                                <option value="">Tous</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($m == $mois) ? 'selected' : '' ?>>
                                        <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </label>

                        <label>
                            <span>Année :</span>
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
                    </form>
                    <div id="chart_div"></div>
                </div>
            </div>

            <!-- Section pointages -->
            <div class="section-container">
                <div class="section-header">
                    <span>Pointages d'aujourd'hui</span>
                    <span><?= date('l, d F Y') ?></span>
                </div>
                <div class="section-body">
                    <?php if ($result_pointages->num_rows > 0): ?>
                        <div class="card-container">
                            <?php while($row = $result_pointages->fetch_assoc()): 
                                $status_class = 'status-present';
                                if ($row['statut'] == 'Retard') {
                                    $status_class = 'status-retard';
                                } elseif ($row['statut'] == 'Absent') {
                                    $status_class = 'status-absent';
                                }
                            ?>
                                <div class="card">
                                    <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" class="card-img">
                                    <div class="card-body">
                                        <h3><?php echo htmlspecialchars($row['prenom']) . ' ' . htmlspecialchars($row['nom']); ?></h3>
                                        <p><strong>Arrivée:</strong> <?php echo date('H:i', strtotime($row['heure_arrivee'])); ?></p>
                                        <p><strong>Départ:</strong> <?php echo !empty($row['heure_depart']) ? date('H:i', strtotime($row['heure_depart'])) : '-- : --'; ?></p>
                                        <p><span class="status-tag <?= $status_class ?>"><?php echo htmlspecialchars($row['statut']); ?></span></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align:center; padding:20px; color:#666;">Aucun pointage enregistré pour aujourd'hui.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        google.charts.load('current', {'packages':['bar']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            const data = google.visualization.arrayToDataTable([
                ['Employé', 'Heures', { role: 'style' }],
                <?php
                $colors = ['#0056b3', '#00a32a', '#d97706', '#7e3af2', '#0096c7', '#f43f5e', '#0891b2'];
                $colorIndex = 0;

                foreach ($dataPoints as $point) {
                    $color = $colors[$colorIndex % count($colors)];
                    echo "['{$point['nom']}', {$point['heures']}, 'color: $color'],";
                    $colorIndex++;
                }
                ?>
            ]);
            const options = {
                chart: {
                    title: '',
                    subtitle: ''
                },
                bars: 'horizontal',
                height: 350,
                legend: { position: 'none' },
                hAxis: {
                    title: 'Heures (h)',
                    minValue: 0
                },
                bar: {
                    groupWidth: "65%"
                },
                colors: ['#0056b3']
            };

            const chart = new google.charts.Bar(document.getElementById('chart_div'));
            chart.draw(data, google.charts.Bar.convertOptions(options));
        }

        document.getElementById("exportBtn").addEventListener("click", function () {
            const chartContent = document.querySelector('.section-container:first-child');
            
            html2pdf().from(chartContent).set({
                margin: 0.5,
                filename: 'statistiques_heures_travail.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            }).save();
        });

        // Ajout de l'effet de survol pour les cartes de pointage
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
                this.style.boxShadow = '0 15px 30px rgba(0, 86, 179, 0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.05)';
            });
        });

        // Animation pour le menu latéral
        const menuIcon = document.querySelector('.menu-icon');
        menuIcon.addEventListener('click', function() {
            this.classList.toggle('active');
            if (this.classList.contains('active')) {
                this.innerHTML = '<i class="fa fa-times"></i>';
            } else {
                this.innerHTML = '<i class="fa fa-bars"></i>';
            }
        });


       
document.addEventListener('DOMContentLoaded', function () {
    const logoutBtn = document.querySelector('.logout-btn');

    logoutBtn.addEventListener('click', function (e) {
        e.preventDefault(); // Empêche le lien de fonctionner immédiatement

        Swal.fire({
        title: 'Confirmation',
        text: 'Êtes-vous sûr de vouloir vous déconnecter?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, je confirme',
        cancelButtonText: 'Annuler'
    }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    });
});




    </script>
</body>
</html>
<style>
        .logout-btn {
    background-color: #8f2630;
    color: #ff4d4d;
    font-weight: bold;
    transition: all 0.3s ease;
}

.logout-btn i {
    color:rgb(244, 244, 244);
}

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f0f2f5;
            min-height: 100vh;
            color: #333;
        }

        #checkbox {
            display: none;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: linear-gradient(135deg, #0056b3 0%, #007bff 100%);
            color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        .menu-icon {
            font-size: 24px;
            color: white;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .menu-icon:hover {
            transform: scale(1.1);
        }

        .u-name {
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-left: -25%;
        }

        .u-name b {
            font-weight: 800;
            color: #ffe100;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-actions .btn {
            padding: 8px 15px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions .btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .dropdown {
            position: relative;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 220px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            z-index: 20;
            border-radius: 8px;
            top: 100%;
            right: 0;
            margin-top: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(10px);
        }

        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 15px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
            font-weight: 500;
        }

        .dropdown-content a:hover {
            background-color: #f5f7fa;
            color: #0056b3;
        }

        .dropdown-content a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .notif-icon, .pointage-icon {
            position: relative;
            cursor: pointer;
            font-size: 22px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .notif-icon:hover, .pointage-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .notif-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff3e55;
            color: white;
            font-size: 12px;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .body {
            display: flex;
            position: relative;
            min-height: calc(100vh - 70px);
        }

        .side-bar {
            width: 250px;
            background: linear-gradient(180deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            transition: all 0.5s ease;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 5;
        }

        #checkbox:checked ~ .body .side-bar {
            width: 70px;
        }

        #checkbox:checked ~ .body .side-bar .user-p,
        #checkbox:checked ~ .body .side-bar a span {
            display: none;
        }

        #checkbox:checked ~ .body .side-bar ul li a i {
            margin-right: 0;
        }

        #checkbox:checked ~ .body .content {
            margin-left: 70px;
        }

        .user-p {
            text-align: center;
            padding: 30px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .user-p img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .user-p img:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .user-p h4 {
            margin-top: 15px;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .side-bar ul {
            list-style: none;
            padding: 0 15px;
        }

        .side-bar ul li {
            margin: 10px 0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .side-bar ul li:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .side-bar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .side-bar ul li a i {
            margin-right: 15px;
            font-size: 18px;
            width: 22px;
            text-align: center;
        }

        .content {
            flex: 1;
            padding: 30px;
            transition: margin-left 0.5s ease;
            background: #f0f2f5;
            position: relative;
        }

        .content h2 {
            color: #0056b3;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 10px;
        }

        .content h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, #0056b3, #00c6ff);
            border-radius: 3px;
        }

        .section-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(to right, #0056b3, #0088ff);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-body {
            padding: 20px;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            width: 260px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #eaeaea;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #0056b3, #00c6ff);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 86, 179, 0.1);
        }

        .card-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto 15px;
            border: 3px solid #f0f2f5;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover .card-img {
            transform: scale(1.1);
            border-color: #0056b3;
        }

        .card-body h3 {
            margin: 10px 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .card-body p {
            font-size: 14px;
            margin: 5px 0;
            color: #666;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .card-body p strong {
            color: #333;
            font-weight: 600;
        }

        .stats-container {
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stats-title {
            font-size: 20px;
            font-weight: 600;
            color: #0056b3;
        }

        .stats-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .stats-filters select, .stats-filters button {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-size: 14px;
            background: #fff;
            transition: all 0.3s ease;
        }

        .stats-filters select:focus {
            border-color: #0056b3;
            outline: none;
        }

        .stats-filters button {
            background: #0056b3;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .stats-filters button:hover {
            background: #004494;
        }

        #chart_div {
            width: 100%;
            height: 400px;
            margin-top: 20px;
        }

        .status-tag {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-present {
            background-color: #e3f7e8;
            color: #00a32a;
        }

        .status-absent {
            background-color: #fcecef;
            color: #d63638;
        }

        .status-retard {
            background-color: #fff8e5;
            color: #d97706;
        }

        @media (max-width: 992px) {
            .u-name {
                margin-left: 0;
                font-size: 18px;
            }
            
            .header-actions {
                gap: 10px;
            }
            
            .card {
                width: calc(50% - 15px);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }

            .u-name {
                margin-bottom: 15px;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .body {
                flex-direction: column;
            }

            .side-bar {
                width: 100%;
                height: auto;
            }

            .side-bar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }

            .side-bar ul li {
                margin: 5px;
            }

            .content {
                margin-left: 0 !important;
                padding: 20px;
            }

            .card {
                width: 100%;
            }

            .stats-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stats-filters {
                width: 100%;
                flex-wrap: wrap;
            }
        }



        /* Style général du conteneur principal */
.dashboard-stats-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 20px;
  width: 100%;
  margin-bottom: 30px;
}

        /* Style des cartes statistiques */
.stat-card {
  flex: 1 1 200px;
  min-width: 200px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  padding: 20px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  border-top: 4px solid #0056b3;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
}

/* Variations de couleurs pour les différentes cartes */
.stat-card.employees {
  border-top-color: #0056b3;
}

.stat-card.pending-leave {
  border-top-color: #ffa000;
}

.stat-card.approved-leave {
  border-top-color: #00a32a;
}

.stat-card.present-today {
  border-top-color: #7e3af2;
}

/* Style de l'icône */
.stat-card-icon {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 24px;
  opacity: 0.7;
  color: #555;
}

/* Style du titre */
.stat-card-title {
  font-size: 14px;
  font-weight: 600;
  color: #555;
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Style de la valeur */
.stat-card-value {
  font-size: 32px;
  font-weight: 700;
  margin: 0;
  color: #333;
  display: flex;
  align-items: center;
}

/* Animation légère au chargement */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-card:nth-child(1) {
  animation: fadeInUp 0.4s ease forwards;
}

.stat-card:nth-child(2) {
  animation: fadeInUp 0.5s ease forwards;
}

.stat-card:nth-child(3) {
  animation: fadeInUp 0.6s ease forwards;
}

.stat-card:nth-child(4) {
  animation: fadeInUp 0.7s ease forwards;
}

/* Responsive design */
@media screen and (max-width: 992px) {
  .dashboard-stats-container {
    gap: 15px;
  }
  
  .stat-card {
    flex: 1 1 calc(50% - 15px);
  }
}

@media screen and (max-width: 576px) {
  .dashboard-stats-container {
    flex-direction: column;
  }
  
  .stat-card {
    width: 100%;
  }
  
  .stat-card-value {
    font-size: 28px;
  }
}
    </style>

<style>
    /* Style du conteneur des boutons centraux */
.central-buttons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin: 20px 0;
    width: 100%;
}

/* Style de base pour les boutons centraux */
.central-button {
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 15px 10px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    color: white;
    height: 75px;
}

/* Effet de survol pour les boutons */
.central-button:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* Couleurs spécifiques pour chaque bouton */
.button-horaires {
    background: linear-gradient(135deg, #0056b3, #0088ff);
}
.button-horaires:hover {
    background: linear-gradient(135deg, #004494, #0074e0);
}

.button-conge {
    background: linear-gradient(135deg, #ffa000, #ffbe41);
}
.button-conge:hover {
    background: linear-gradient(135deg, #e69100, #f0a000);
}

.button-feries {
    background: linear-gradient(135deg, #00a32a, #2ed160);
}
.button-feries:hover {
    background: linear-gradient(135deg, #008a24, #00b12c);
}

.button-liste-conges {
    background: linear-gradient(135deg, #7e3af2, #a168fa);
}
.button-liste-conges:hover {
    background: linear-gradient(135deg, #6a2bd9, #8446f5);
}

/* Style des icônes */
.central-button .icon {
    width: 45px;
    height: 45px;
    min-width: 45px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    transition: all 0.3s ease;
}

.central-button:hover .icon {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 0.3);
}

/* Style de l'icône Font Awesome */
.central-button .icon i {
    font-size: 22px;
    color: white;
}

/* Style du texte du bouton */
.central-button h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0;
    color: white;
    text-align: left;
    line-height: 1.3;
}

/* Animation légère au chargement */
@keyframes buttonFadeIn {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.central-button:nth-child(1) {
    animation: buttonFadeIn 0.3s ease forwards;
}
.central-button:nth-child(2) {
    animation: buttonFadeIn 0.4s ease forwards;
}
.central-button:nth-child(3) {
    animation: buttonFadeIn 0.5s ease forwards;
}
.central-button:nth-child(4) {
    animation: buttonFadeIn 0.6s ease forwards;
}

/* Design responsive pour les boutons centraux */
@media screen and (max-width: 992px) {
    .central-buttons {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}

@media screen and (max-width: 576px) {
    .central-buttons {
        grid-template-columns: 1fr;
    }
    
    .central-button {
        height: 65px;
    }
    
    .central-button .icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
    }
    
    .central-button .icon i {
        font-size: 20px;
    }
}
</style>