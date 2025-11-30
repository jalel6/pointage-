<?php


// Vérification de la session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Connexion à la base de données
include 'db.php';


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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="admin_layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <meta http-equiv="refresh" content="300"> <!-- Refresh toutes les 5 minutes -->

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
                    <a href="rapport_global.php"><i class="fa fa-download"></i> Exporter Rapport Global</a>
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
                <li><a href="logout.php" class="logout-btn"> <i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>
            </ul>
        </nav>
       
        <div class="content">
                
                </body>
                </html>