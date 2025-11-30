<?php
session_start();

// Vérifier si c'est une secrétaire
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'secrétaire') {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'verifier_retards_absences.php';

$secretaire_id = $_SESSION['user_id'];
$_SESSION['employe_id'] = $secretaire_id;

// --------------------------------------------------
// Récupération des infos de la secrétaire
// --------------------------------------------------
$query = "SELECT nom, prenom, photo_profil FROM employes WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $secretaire_id);
$stmt->execute();
$result = $stmt->get_result();
$secretaire = $result->fetch_assoc();

if (!$secretaire) {
    die("Erreur : Impossible de récupérer les informations de l'utilisateur.");
}

$nom_complet = htmlspecialchars($secretaire['prenom'] . " " . $secretaire['nom']);
$image_path = !empty($secretaire['photo_profil']) ? htmlspecialchars($secretaire['photo_profil']) : "images/default.jpg";


// --------------------------------------------------
// Notifications congés non vues
// --------------------------------------------------
$notif_count = 0;

$notif_query = "SELECT COUNT(*) AS count FROM demandeconge
                WHERE employe_id = ?
                AND notification_vue = 0
                AND (statut = 'approuvé' OR statut = 'rejeté')";

$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $secretaire_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();

if ($notif_row = $notif_result->fetch_assoc()) {
    $notif_count = $notif_row['count'];
}


// --------------------------------------------------
// Retards + Absences du jour (via fichier texte)
// --------------------------------------------------
$retard_absence_count = 0;
$today = date('Y-m-d');

$file = 'notifications_journalieres.txt';

if (file_exists($file)) {
    $notifications = file($file, FILE_IGNORE_NEW_LINES);

    foreach ($notifications as $line) {
        if (
            strpos($line, $today) !== false &&
            (strpos($line, 'Absent') !== false || strpos($line, 'Retard') !== false)
        ) {
            $retard_absence_count++;
        }
    }
}


// --------------------------------------------------
// Liste des pointages du jour
// --------------------------------------------------
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
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="secritarrio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Ajout de SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
<input type="checkbox" id="checkbox">
<header class="header">
    <label for="checkbox" class="menu-icon">
        <i class="fa fa-bars" aria-hidden="true"></i>
    </label>
    <h2 class="u-name">AHWA <b>SOLUTIONS</b></h2>


    <div class="header-actions">
        <div class="dropdown">
            <a href="#" class="btn">GESTION DES EMPLOYÉS</a>
            <div class="dropdown-content">
                <a href="liste_employes.php"><i class="fa fa-users"></i> Consulter la liste</a>
                <a href="inscription1.php"><i class="fa fa-user-plus"></i> Ajouter un employé</a>
                <a href="modifier_employe.php"><i class="fa fa-pencil-square-o"></i> Modifier un employé</a>
            </div>
        </div>
        <a href="pointages.php" class="pointage-icon">
            <i class="fa fa-camera"></i>
        </a>
        
        <!-- Icône de notification avec menu déroulant -->
            <div class="header-icon" id="notification-icon">
            <i class="fa fa-bell"></i>
            <?php
             if ($notif_count > 0) {
            echo "<span class='badge'>$notif_count</span>";
                 }
             ?>
            </div>
        <!-- Liste des notifications -->
        <div id="notification-list" class="notification-list">
            <div class="notification-header">
                <span class="notification-header-title">Notifications</span>
                <?php if ($notif_count > 0): ?>
                <span class="notification-header-actions" id="mark-all-read">Tout marquer comme lu</span>
                <?php endif; ?>
            </div>
            <div id="notification-content">
                <?php
                // Récupérer les notifications non vues
                $notif_query = "SELECT idDemande, dateSoumission, statut FROM demandeconge 
                                WHERE employe_id = ? AND notification_vue = 0 AND (statut = 'approuvé' OR statut = 'rejeté')";
                $notif_stmt = $conn->prepare($notif_query);
                $notif_stmt->bind_param("i", $secretaire_id);
                $notif_stmt->execute();
                $notif_result = $notif_stmt->get_result();

                if ($notif_result->num_rows > 0) {
                    while ($row = $notif_result->fetch_assoc()) {
                        echo "<div class='notification-item new-notification'>";
                        echo "<div class='notification-icon'>" . ($row['statut'] === 'approuvé' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>') . "</div>";
                        echo "<div class='notification-content'>";
                        if ($row['statut'] === 'approuvé') {
                            echo "<div class='notification-message'>Votre demande de congé du " . htmlspecialchars($row['dateSoumission']) . " a été <strong>approuvée</strong>.</div>";
                        } else {
                            echo "<div class='notification-message'>Votre demande de congé du " . htmlspecialchars($row['dateSoumission']) . " a été <strong>rejetée</strong>.</div>";
                        }
                        echo "</div></div>";
                    }
                } else {
                    echo "<div class='no-notifications'>";
                    echo "<div class='no-notifications-icon'><i class='fa fa-bell-slash'></i></div>";
                    echo "<div class='no-notifications-text'>Aucune notification</div>";
                    echo "<div class='no-notifications-subtext'>Vous n'avez pas de nouvelles notifications</div>";
                    echo "</div>";
                }
                ?>
            </div>  
        </div>
        <a href="notification_retard_absent.php" class="notif-icon">
    <i class="fa fa-exclamation-circle"></i>
    <?php if ($retard_absence_count > 0): ?>
        <span class="notif-count">
            <?= $retard_absence_count ?>
        </span>
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
                <li><a href="secretary_dashboard.php"><i class="fa fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profil_secretaire.php"><i class="fa fa-id-badge"></i><span>Mon Profil</span></a></li>
               
                <li><a href="historique.php"><i class="fa fa-history"></i><span>Historique Personnel</span></a></li>
                <li><a href="historique_employe.php"><i class="fa fa-users"></i><span>Historique Employé</span></a></li>
               
                <li><a href="#" class="logout-btn" id="logout-btn"> <i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>
            </ul>
        </nav>
        <div class="content">
            <!-- Ajout des boutons centraux -->
            <div class="central-buttons">
                <a href="horaires.php" class="central-button button-horaires">
                    <div class="icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <h3>Horaires du Travail</h3>
                </a>
                
                <a href="demande_conge.php" class="central-button button-conge">
                    <div class="icon">
                        <i class="fa fa-calendar-plus-o"></i>
                    </div>
                    <h3>Demande de Congé</h3>
                </a>
                
                <a href="affiche_feries.php" class="central-button button-feries">
                    <div class="icon">
                        <i class="fa fa-calendar-check-o"></i>
                    </div>
                    <h3>Jours Fériés</h3>
                </a>
                
                <a href="lconge.php" class="central-button button-liste-conges">
                    <div class="icon">
                        <i class="fa fa-list-alt"></i>
                    </div>
                    <h3>Liste Congés</h3>
                </a>
            </div>

        <div class="content">
            <h2>Pointages d'aujourd'hui</h2>
            <?php if ($result_pointages->num_rows > 0): ?>
                <div class="card-container">
                    <?php while($row = $result_pointages->fetch_assoc()): ?>
                        <div class="card">
                            <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" class="card-img">
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($row['prenom']) . ' ' . htmlspecialchars($row['nom']); ?></h3>
                                <p><strong>Arrivée:</strong> <?php echo htmlspecialchars($row['heure_arrivee']); ?></p>
                                <p><strong>Départ:</strong> <?php echo htmlspecialchars($row['heure_depart']); ?></p>
                                <p><strong>Statut:</strong> <?php echo htmlspecialchars($row['statut']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:white;">Aucun pointage pour aujourd'hui.</p>
            <?php endif; ?>
        </div>
    </div>
 
</div>
<!-- Script pour la confirmation de déconnexion et la gestion des notifications -->
<script>
document.getElementById('logout-btn').addEventListener('click', function(e) {
    e.preventDefault();
    
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

// Gestion des notifications
const notifIcon = document.getElementById('notification-icon');
const notifList = document.getElementById('notification-list');
let notificationsViewed = false;

notifIcon.addEventListener('click', () => {
    notifList.classList.toggle('active');
    
    // Si on ouvre les notifications et qu'elles n'ont pas encore été marquées comme vues
    if (notifList.classList.contains('active') && !notificationsViewed) {
        // Attendez 2 secondes pour que l'utilisateur puisse voir les notifications avant de les marquer comme lues
        setTimeout(() => {
            markNotificationsAsRead();
        }, 2000);
    }
});

// Marquer comme lu au clic sur "Tout marquer comme lu"
const markAllReadBtn = document.getElementById('mark-all-read');
if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', () => {
        markNotificationsAsRead();
    });
}

function markNotificationsAsRead() {
    fetch('marquer_notifications_lues.php')
        .then(response => response.text())
        .then(data => {
            console.log("Notifications marquées comme lues.");
            notificationsViewed = true;
            // Optionnel : masquer la pastille rouge
            const badge = document.querySelector('.badge');
            if (badge) {
                badge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error("Erreur lors du marquage des notifications :", error);
        });
}


// Fermer la liste si on clique ailleurs
window.addEventListener('click', (e) => {
    if (!notifIcon.contains(e.target) && !notifList.contains(e.target)) {
        notifList.classList.remove('active');
    }
});
</script>
</body>
</html>
<style> 
.header-icon {
        position: relative;
        cursor: pointer;
        margin-left: 5px;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        backdrop-filter: blur(5px);
    
    }
    
    .header-icon:hover {
        background: rgba(235, 238, 255, 0.9);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }

    /* Ultra-Modern Notification System with Glass Morphism & Advanced Effects */
    :root {
        --primary-color: #6C5CE7;
        --secondary-color: #5649D6;
        --accent-color: #00CEC9;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --glass-bg: rgba(255, 255, 255, 0.92);
        --glass-border: rgba(255, 255, 255, 0.3);
        --text-color: #2D3436;
        --text-light: #636E72;
        --border-radius: 20px;
        --transition-speed: 0.35s;
        --blur-intensity: 20px;
    }
    
    /* Notification Container - Advanced Glass Morphism */
    .notification-list {
        display: none;
        position: absolute;
        top: 80px;
        right: 25px;
        background: linear-gradient(
            135deg, 
            rgba(255, 255, 255, 0.78), 
            rgba(245, 247, 255, 0.88)
        );
        width: 380px;
        max-height: 600px;
        box-shadow: 
            0 25px 50px -12px rgba(0, 0, 0, 0.08),
            inset 0 1px 1px rgba(255, 255, 255, 0.7),
            0 0 0 1px rgba(255, 255, 255, 0.3);
        border-radius: var(--border-radius);
        padding: 0;
        z-index: 1000;
        opacity: 0;
        transform: translateY(15px);
        transform-origin: top right;
        transition: all var(--transition-speed) cubic-bezier(0.2, 0.9, 0.3, 1.1);
        backdrop-filter: blur(var(--blur-intensity));
        -webkit-backdrop-filter: blur(var(--blur-intensity));
        border: 1px solid var(--glass-border);
        overflow: hidden;
    }
    
    .notification-list.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
        animation: fadeInUp 0.4s ease-out;
    }
    
    /* Header with Subtle Gradient */
    .notification-header {
        padding: 20px;
        font-weight: 600;
        color: var(--text-color);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        background: linear-gradient(
            to right, 
            rgba(245, 245, 255, 0.8), 
            rgba(250, 252, 255, 0.9)
        );
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 2;
    }
    
    .notification-header-title {
        font-size: 16px;
        letter-spacing: 0.2px;
        font-weight: 700;
        color: var(--text-color);
    }
    
    .notification-header-actions {
        font-size: 13px;
        color: var(--primary-color);
        cursor: pointer;
        opacity: 0.9;
        transition: all 0.2s ease;
        font-weight: 500;
        padding: 4px 8px;
        border-radius: 8px;
    }
    
    .notification-header-actions:hover {
        opacity: 1;
        background: rgba(108, 92, 231, 0.08);
    }
    
    /* Notification Items with Hover Effects */
    .notification-item {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        color: var(--text-color);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        background: rgba(255, 255, 255, 0.4);
    }
    
    .notification-item:hover {
        background: rgba(245, 248, 255, 0.7);
        transform: translateX(0);
    }
    

    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    /* Advanced Glass Effect */
    .notification-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            135deg, 
            rgba(255, 255, 255, 0.5) 0%, 
            rgba(255, 255, 255, 0) 60%
        );
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .notification-item:hover::before {
        opacity: 1;
    }
    
    /* Content Styling */
    .notification-icon {
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
    
    .notification-content {
        flex-grow: 1;
        overflow: hidden;
    }
    
    .notification-title {
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 14px;
        color: var(--text-color);
    }
    
    .notification-message {
        font-size: 13px;
        color: var(--text-light);
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .notification-time {
        font-size: 11px;
        color: rgba(99, 110, 114, 0.6);
        margin-top: 6px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Badge with Glow Effect */
    .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: linear-gradient(135deg, #FF7676, #F54B4B);
        color: white;
        border-radius: 12px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 
            0 2px 8px rgba(245, 75, 75, 0.3),
            0 0 0 2px rgba(255, 255, 255, 0.9);
        z-index: 2;
    }
    
    /* Header Icon with Modern Style */
    .header-icon {
        position: relative;
        cursor: pointer;
        margin-left: 5px;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        backdrop-filter: blur(5px);
    
    }
    
    .header-icon:hover {
        background: rgba(235, 238, 255, 0.9);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    
    /* Empty State Design */
    .no-notifications {
        padding: 50px 25px;
        text-align: center;
        color: #A0AEC0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 18px;
    }
    
    .no-notifications-icon {
        width: 70px;
        height: 70px;
        border-radius: 22px;
        background: linear-gradient(
            135deg, 
            rgba(240, 242, 250, 0.7), 
            rgba(245, 248, 255, 0.9)
        );
        display: flex;
        align-items: center;
        justify-content: center;
        color: #CBD5E0;
        font-size: 28px;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    
    .no-notifications-text {
        font-weight: 600;
        color: #718096;
        font-size: 15px;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        0% { opacity: 0; transform: translateY(15px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes float {
        0% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
        100% { transform: translateY(0); }
    }
    
    /* Arrow pointer with glass effect */
    .notification-list::before {
        content: '';
        position: absolute;
        top: -10px;
        right: 24px;
        width: 20px;
        height: 20px;
        background: var(--glass-bg);
        transform: rotate(45deg);
        border-top: 1px solid var(--glass-border);
        border-left: 1px solid var(--glass-border);
        z-index: -1;
        box-shadow: -5px -5px 10px rgba(0, 0, 0, 0.03);
    }
    
    /* Responsive adjustments */
    @media (max-width: 480px) {
        .notification-list {
            width: 94vw;
            right: 3vw;
            max-height: 70vh;
        }
    }
</style>
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
</style>
<style> 
@media (max-width: 768px) {
    /* ... autres styles mobiles existants ... */

    /* Ajoutez ces nouvelles règles */
    #checkbox:checked ~ .body .content {
        margin-left: 0;
        width: 100%;
    }

    #checkbox:checked ~ .body .side-bar {
        width: 0;
        overflow: hidden;
        padding: 0;
        transition: all 0.3s ease;
    }

    .side-bar {
        position: fixed;
        height: 100vh;
        z-index: 100;
        transition: all 0.3s ease;
    }

    .content {
        width: 100%;
        margin-left: 0;
        padding: 20px;
    }

    /* Assurez-vous que le menu ne pousse pas le contenu vers le bas */
    #checkbox:not(:checked) ~ .body {
        padding-top: 70px; /* Hauteur de votre header */
    }

    #checkbox:not(:checked) ~ .body .side-bar {
        position: fixed;
        top: 70px; /* Sous le header */
        left: 0;
        bottom: 0;
    }

    #checkbox:not(:checked) ~ .body .content {
        margin-left: 250px; /* Largeur de votre sidebar */
    }
}

.header-icon {
        position: relative;
        cursor: pointer;
        margin-left: 5px;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        backdrop-filter: blur(5px);
    
    }
    
    .header-icon:hover {
        background: rgba(235, 238, 255, 0.9);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }

    /* Ultra-Modern Notification System with Glass Morphism & Advanced Effects */
    :root {
        --primary-color: #6C5CE7;
        --secondary-color: #5649D6;
        --accent-color: #00CEC9;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --glass-bg: rgba(255, 255, 255, 0.92);
        --glass-border: rgba(255, 255, 255, 0.3);
        --text-color: #2D3436;
        --text-light: #636E72;
        --border-radius: 20px;
        --transition-speed: 0.35s;
        --blur-intensity: 20px;
    }
    
    /* Notification Container - Advanced Glass Morphism */
    .notification-list {
        display: none;
        position: absolute;
        top: 80px;
        right: 25px;
        background: linear-gradient(
            135deg, 
            rgba(255, 255, 255, 0.78), 
            rgba(245, 247, 255, 0.88)
        );
        width: 380px;
        max-height: 600px;
        box-shadow: 
            0 25px 50px -12px rgba(0, 0, 0, 0.08),
            inset 0 1px 1px rgba(255, 255, 255, 0.7),
            0 0 0 1px rgba(255, 255, 255, 0.3);
        border-radius: var(--border-radius);
        padding: 0;
        z-index: 1000;
        opacity: 0;
        transform: translateY(15px);
        transform-origin: top right;
        transition: all var(--transition-speed) cubic-bezier(0.2, 0.9, 0.3, 1.1);
        backdrop-filter: blur(var(--blur-intensity));
        -webkit-backdrop-filter: blur(var(--blur-intensity));
        border: 1px solid var(--glass-border);
        overflow: hidden;
    }
    
    .notification-list.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
        animation: fadeInUp 0.4s ease-out;
    }
    
    /* Header with Subtle Gradient */
    .notification-header {
        padding: 20px;
        font-weight: 600;
        color: var(--text-color);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        background: linear-gradient(
            to right, 
            rgba(245, 245, 255, 0.8), 
            rgba(250, 252, 255, 0.9)
        );
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 2;
    }
    
    .notification-header-title {
        font-size: 16px;
        letter-spacing: 0.2px;
        font-weight: 700;
        color: var(--text-color);
    }
    
    .notification-header-actions {
        font-size: 13px;
        color: var(--primary-color);
        cursor: pointer;
        opacity: 0.9;
        transition: all 0.2s ease;
        font-weight: 500;
        padding: 4px 8px;
        border-radius: 8px;
    }
    
    .notification-header-actions:hover {
        opacity: 1;
        background: rgba(108, 92, 231, 0.08);
    }
    
    /* Notification Items with Hover Effects */
    .notification-item {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        color: var(--text-color);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        background: rgba(255, 255, 255, 0.4);
    }
    
    .notification-item:hover {
        background: rgba(245, 248, 255, 0.7);
        transform: translateX(0);
    }
    

    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    /* Advanced Glass Effect */
    .notification-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            135deg, 
            rgba(255, 255, 255, 0.5) 0%, 
            rgba(255, 255, 255, 0) 60%
        );
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .notification-item:hover::before {
        opacity: 1;
    }
    
    /* Content Styling */
    .notification-icon {
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
    
    .notification-content {
        flex-grow: 1;
        overflow: hidden;
    }
    
    .notification-title {
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 14px;
        color: var(--text-color);
    }
    
    .notification-message {
        font-size: 13px;
        color: var(--text-light);
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .notification-time {
        font-size: 11px;
        color: rgba(99, 110, 114, 0.6);
        margin-top: 6px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Badge with Glow Effect */
    .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: linear-gradient(135deg, #FF7676, #F54B4B);
        color: white;
        border-radius: 12px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 
            0 2px 8px rgba(245, 75, 75, 0.3),
            0 0 0 2px rgba(255, 255, 255, 0.9);
        z-index: 2;
    }
    
    /* Header Icon with Modern Style */
    .header-icon {
        position: relative;
        cursor: pointer;
        margin-left: 5px;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        backdrop-filter: blur(5px);
    
    }
    
    .header-icon:hover {
        background: rgba(235, 238, 255, 0.9);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    
    /* Empty State Design */
    .no-notifications {
        padding: 50px 25px;
        text-align: center;
        color: #A0AEC0;
        display: flex;
       flex-direction: column;
        align-items: center;
        gap: 18px;
    }
    
    .no-notifications-icon {
        width: 70px;
        height: 70px;
        border-radius: 22px;
        background: linear-gradient(
            135deg, 
            rgba(240, 242, 250, 0.7), 
            rgba(245, 248, 255, 0.9)
        );
        display: flex;
        align-items: center;
        justify-content: center;
        color: #CBD5E0;
        font-size: 28px;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    
    .no-notifications-text {
        font-weight: 600;
        color: #718096;
        font-size: 15px;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        0% { opacity: 0; transform: translateY(15px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes float {
        0% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
        100% { transform: translateY(0); }
    }
    
    /* Arrow pointer with glass effect */
    .notification-list::before {
        content: '';
        position: absolute;
        top: -10px;
        right: 24px;
        width: 20px;
        height: 20px;
        background: var(--glass-bg);
        transform: rotate(45deg);
        border-top: 1px solid var(--glass-border);
        border-left: 1px solid var(--glass-border);
        z-index: -1;
        box-shadow: -5px -5px 10px rgba(0, 0, 0, 0.03);
    }
    
    /* Responsive adjustments */
    @media (max-width: 480px) {
        .notification-list {
            width: 94vw;
            right: 3vw;
            max-height: 70vh;
        }
    }

/* Styles pour les boutons centraux */
.central-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 25px;
        margin: 40px 0;
    }

    .central-button {
        width: 240px;
        height: 180px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #333;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        border: 1px solid #f0f0f0;
    }

    .central-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 6px;
        background: linear-gradient(90deg, #0056b3, #00c6ff);
    }

    .central-button .icon {
        font-size: 42px;
        margin-bottom: 16px;
        color: #0056b3;
        background: rgba(0, 86, 179, 0.08);
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s ease;
    }

    .central-button h3 {
        font-size: 16px;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        margin: 0;
    }

    .central-button:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 35px rgba(0, 86, 179, 0.15);
        border-color: rgba(0, 86, 179, 0.2);
    }

    .central-button:hover .icon {
        transform: scale(1.1) rotate(5deg);
        background: rgba(0, 86, 179, 0.12);
        color: #0088ff;
    }

    .central-button:hover h3 {
        color: #0088ff;
    }

    /* Couleurs spécifiques pour chaque bouton */
    .button-horaires::before {
        background: linear-gradient(90deg, #0056b3, #00c6ff);
    }
    
    .button-conge::before {
        background: linear-gradient(90deg, #2ecc71, #27ae60);
    }
    
    .button-feries::before {
        background: linear-gradient(90deg, #f39c12, #f1c40f);
    }
    
    .button-liste-conges::before {
        background: linear-gradient(90deg, #9b59b6, #8e44ad);
    }

    .button-horaires .icon {
        color: #0056b3;
        background: rgba(0, 86, 179, 0.08);
    }
    
    .button-conge .icon {
        color: #2ecc71;
        background: rgba(46, 204, 113, 0.08);
    }
    
    .button-feries .icon {
        color: #f39c12;
        background: rgba(243, 156, 18, 0.08);
    }
    
    .button-liste-conges .icon {
        color: #9b59b6;
        background: rgba(155, 89, 182, 0.08);
    }

    .button-horaires:hover .icon {
        color: #00c6ff;
    }
    
    .button-conge:hover .icon {
        color: #27ae60;
    }
    
    .button-feries:hover .icon {
        color: #f1c40f;
    }
    
    .button-liste-conges:hover .icon {
        color: #8e44ad;
    }
    /*icone notif retard absent*/
  

</style>
