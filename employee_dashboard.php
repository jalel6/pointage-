<?php
session_start();

// Vérifier si l'utilisateur est un employé
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'employé') {
    header("Location: index.php");
    exit();
}

$employe_id = $_SESSION['user_id'];
$_SESSION['employe_id'] = $employe_id;

include 'db.php';

// Récupération infos employé
$query = "SELECT nom, prenom, photo_profil FROM employes WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();
$employe = $result->fetch_assoc();

if (!$employe) {
    die("Erreur : Impossible de récupérer les informations de l'utilisateur.");
}

$nom_complet = htmlspecialchars($employe['prenom'] . " " . $employe['nom']);
$image_path = !empty($employe['photo_profil']) ? htmlspecialchars($employe['photo_profil']) : "images/default.jpg";

// Compter les notifications non vues
$notif_count = 0;
$notif_query = "SELECT COUNT(*) AS count 
                FROM demandeconge 
                WHERE employe_id = ? 
                AND notification_vue = 0 
                AND (statut = 'approuvé' OR statut = 'rejeté')";

$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $employe_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
if ($notif_row = $notif_result->fetch_assoc()) {
    $notif_count = $notif_row['count'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>


<body>
    <input type="checkbox" id="checkbox">
    <header class="header">
    <label for="checkbox" class="menu-icon">
            <i class="fa fa-bars" aria-hidden="true"></i>
        </label>
        <h2 class="u-name">AHWA <b>SOLUTIONS</b></h2>
        
        <!-- Icônes de messagerie et notifications -->
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
                $notif_stmt->bind_param("i", $employe_id);
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
    </div>
</header>


    <div class="body">
        <div class="main-content">
            <nav class="side-bar">
                <div class="user-p">
                    <img src="<?php echo $image_path; ?>" alt="Photo de profil">
                    <h4><?php echo $nom_complet; ?></h4>
                </div>
                <ul>
                <li><a href="employee_dashboard.php"><i class="fa fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="profil_employe.php"><i class="fa fa-id-badge"></i><span>Profile</span></a></li>
                    <li><a href="historique.php"><i class="fa fa-history"></i><span>Historique</span></a></li>
                    <li><a href="#" id="logout-link" class="logout-btn"><i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>


                </ul>
            </nav>

            <!-- Section des boutons principaux -->
            <div class="btn-container">
                <div class="btn-item">
                    <a href="horaires.php" class="btn-primary"><i class="fa fa-calendar"></i> Horaires du Travail</a>
                </div>
                <div class="btn-item">
                    <a href="pointages.php" class="btn-primary"><i class="fa fa-check-circle"></i> Se Pointer</a>
                </div>
                <div class="btn-item">
                <a href="demande_conge.php" class="btn-primary">
                <i class="fa fa-pencil-alt"></i>
                📝 Demander Congé
            </a>
                </div>
                <div class="btn-item">
                    <a href="affiche_feries.php" class="btn-primary"><i class="fa fa-flag"></i> Jours Fériés</a>
                </div>
            </div>
        </div>
    </div>

<script>
   // Gestion des notifications
document.addEventListener('DOMContentLoaded', function() {
    const notifIcon = document.getElementById('notification-icon');
    const notifList = document.getElementById('notification-list');
    
    // Fonction pour marquer les notifications comme lues
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


    // Gérer le clic sur l'icône de notification
    notifIcon.addEventListener('click', function() {
        notifList.classList.toggle('active');
        
        // Si on ouvre les notifications, marquer comme lues immédiatement
        if (notifList.classList.contains('active')) {
            markNotificationsAsRead();
        }
    });

    // Marquer comme lu au clic sur "Tout marquer comme lu"
    const markAllReadBtn = document.getElementById('mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markNotificationsAsRead();
        });
    }

    // Fermer la liste si on clique ailleurs
    window.addEventListener('click', function(e) {
        if (!notifIcon.contains(e.target) && !notifList.contains(e.target)) {
            notifList.classList.remove('active');
        }
    });
});

document.getElementById('logout-link').addEventListener('click', function(e) {
    e.preventDefault(); // Empêche la redirection immédiate

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
            window.location.href = 'logout.php'; // Redirection vers la déconnexion
        }
    });
});


</script>

</body>
</html>
<style>
    /* Correction du style de l'icône de notification */
.header-icon {
    position: relative;
    cursor: pointer;
    margin-left: 15px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(235, 238, 255, 0.6);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.header-icon:hover {
    background: rgba(235, 238, 255, 0.9);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

.header-icon i {
    font-size: 18px;
    color: #5649D6;
}

/* Badge pour le nombre de notifications */
.badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: linear-gradient(135deg, #FF7676, #F54B4B);
    color: white;
    border-radius: 12px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 
        0 2px 5px rgba(245, 75, 75, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.3);
    z-index: 2;
}

/* S'assurer que la liste de notifications apparaît correctement */
.notification-list {
    /* Styles existants */
    display: none;
    position: absolute;
    top: 60px; /* Ajusté pour s'aligner correctement */
    right: 20px;
    width: 350px;
    max-height: 500px;
    overflow-y: auto;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    opacity: 0;
    transform: translateY(15px);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.notification-list.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

/* Styles pour les notifications individuelles */
.notification-item {
    padding: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: flex-start;
    gap: 12px;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: rgba(235, 238, 255, 0.5);
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon i {
    font-size: 14px;
}

/* Styles pour les icônes d'approbation et de rejet */
.notification-icon .fa-check {
    color: #48BB78; /* vert pour approuvé */
    background-color: rgba(72, 187, 120, 0.2);
    padding: 10px;
    border-radius: 50%;
}

.notification-icon .fa-times {
    color: #F56565; /* rouge pour rejeté */
    background-color: rgba(245, 101, 101, 0.2);
    padding: 10px;
    border-radius: 50%;
}
</style>
<style>
    


/* Conteneur de boutons */
.btn-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    padding: 40px 30px;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.btn-item {
    width: calc(50% - 30px);
    margin-bottom: 30px;
    max-width: 500px;
    min-width: 280px;
    flex-grow: 1;
}

.btn-primary {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 20px;
    width: 100%;
    height: 220px;
    padding: 30px;
    font-size: 24px;
    font-weight: 500;
    color: #fff;
    background: linear-gradient(135deg, #1a73e8, #0d47a1);
    border-radius: 16px;
    text-decoration: none;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.btn-primary:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0, 86, 179, 0.3);
}

.btn-primary:hover::before {
    transform: translateX(100%);
}

.btn-primary i {
    font-size: 40px;
    margin-bottom: 10px;
    background: rgba(255, 255, 255, 0.2);
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Responsive design */
@media (max-width: 1200px) {
    .btn-item {
        width: calc(50% - 20px);
    }
    
    .btn-primary {
        height: 180px;
        font-size: 20px;
    }
    
    .btn-primary i {
        font-size: 32px;
        width: 65px;
        height: 65px;
    }
}

@media (max-width: 992px) {
    .btn-container {
        padding: 30px 20px;
    }
}

@media (max-width: 768px) {
    .header {
        padding: 15px;
    }
    
    .u-name {
        font-size: 18px;
        padding-left: 20px;
    }
    
    
    .btn-container {
        padding: 20px 15px;
        gap: 20px;
    }
    
    .btn-item {
        width: 100%;
        margin-bottom: 20px;
    }
    
    .btn-primary {
        height: 140px;
        padding: 25px;
        font-size: 18px;
    }
    
    .btn-primary i {
        font-size: 28px;
        width: 60px;
        height: 60px;
        margin-bottom: 8px;
    }
    
    .notification-list {
        width: 300px;
        right: -70px;
    }
    
    .notification-list::before {
        right: 85px;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 12px 10px;
    }
    
    .menu-icon {
        font-size: 20px;
    }
    
    .u-name {
        font-size: 16px;
        padding-left: 15px;
    }
    
    .btn-primary {
        height: 120px;
        padding: 20px;
        font-size: 16px;
    }
    
    .btn-primary i {
        font-size: 24px;
        width: 50px;
        height: 50px;
    }
    
    .header-icon {
        width: 35px;
        height: 35px;
        font-size: 18px;
    }
    
    .notification-list {
        width: 280px;
        right: -100px;
    }
    
    .notification-list::before {
        right: 110px;
    }
}

/* Animation de défilement pour la sidebar */
@keyframes gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

/* Animation pour les boutons */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
    }
}

/* Styles pour les boutons spécifiques */
.btn-container .btn-item:nth-child(1) .btn-primary {
    background: linear-gradient(135deg, #2196f3, #0d47a1);
}

.btn-container .btn-item:nth-child(2) .btn-primary {
    background: linear-gradient(135deg, #4caf50, #1b5e20);
}

.btn-container .btn-item:nth-child(3) .btn-primary {
    background: linear-gradient(135deg, #ff9800, #e65100);
}

.btn-container .btn-item:nth-child(4) .btn-primary {
    background: linear-gradient(135deg, #9c27b0, #4a148c);
}

* {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
        font-family: 'Poppins', 'Segoe UI', sans-serif;
        transition: all 0.3s ease;
    }

    body {
        background: linear-gradient(135deg, rgba(240, 245, 255, 0.8), rgba(220, 230, 255, 0.9));
        background-size: cover;
        min-height: 100vh;
        overflow-x: hidden;
        position: relative;
    }



body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('images/tt.jpg') no-repeat center center fixed;
    background-size: cover;
    filter: blur(3px);
    opacity: 0.15;
    z-index: -1;
}
.header-icon {
    color: var(--white);
    font-size: 1.4rem;
    position: relative;
    cursor: pointer;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius-full);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    transition: all 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
}

.header-icon:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}

/* Barre de navigation */
.header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 30px;
        background: linear-gradient(90deg, #0056b3, #0080ff);
        color: #fff;
        position: relative;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        z-index: 10;
    }

.menu-icon {
        font-size: 22px;
        color: white;
        cursor: pointer;
        position: relative;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px);
        transition: all 0.3s ease;
    }

    .menu-icon:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

.u-name {
    font-size: 22px;
    font-weight: 500;
    letter-spacing: 0.5px;
    padding-left: 30px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.u-name b {
    font-weight: 700;
    background: linear-gradient(90deg, #ffeb3b, #ffd54f);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    text-shadow: none;
}

.header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .header-actions a {
        color: white;
        text-decoration: none;
        opacity: 0.9;
        transition: all 0.3s ease;
    }

    .header-actions a:hover {
        opacity: 1;
        transform: translateY(-2px);
    }

    .header-actions i {
        font-size: 22px;
    }




.main-content {
    display: flex;
    flex: 1;
}
h2 {
            text-align: center;
            color:rgb(250, 250, 250);
            font-weight: 600;
            position: relative;
            padding-bottom: 12px;
    
        }
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #1a237e, #3949ab);
            border-radius: 2px;
        }
    

/* Style pour chaque icône dans l'en-tête */
.header-icon {
        color: #fff;
        font-size: 22px;
        position: relative;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px);
    }

    .header-icon:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

/* Badge pour les notifications non lues */
.badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff3e55;
    color: white;
    font-size: 11px;
    font-weight: bold;
    border-radius: 50%;
    padding: 4px;
    min-width: 20px;
    height: 20px;
    text-align: center;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
}

/* Liste des notifications */
.notification-list {
    display: none;
    position: absolute;
    top: 50px;
    right: 0;
    background: #fff;
    border-radius: 10px;
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    z-index: 100;
    animation: fadeIn 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-list.active {
    display: block;
}

.notification-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f2f2f2;
    color: #333;
    font-size: 14px;
    line-height: 1.5;
    transition: all 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item strong {
    color: #e74c3c;
    font-weight: 600;
}

.notification-item.approved strong {
    color: #27ae60;
}

.notification-item.rejected strong {
    color: #e74c3c;
}


/* Styles pour les notifications vides */
.notification-list:empty::after {
    content: "Aucune notification pour le moment";
    display: block;
    padding: 20px;
    text-align: center;
    color: #666;
    font-style: italic;
}

/* Amélioration de l'accessibilité */
.btn-primary:focus,
.header-icon:focus,

/* Loader pour le chargement des éléments */
@keyframes shimmer {
    0% {
        background-position: -468px 0;
    }
    100% {
        background-position: 468px 0;
    }
}

/* Style pour la flèche en haut de la liste des notifications */
.notification-list::before {
    content: "";
    position: absolute;
    top: -10px;
    right: 15px;
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid #fff;
}

/* Scrollbar personnalisée */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

/* Nouvelles améliorations */
/* Effet de hover plus fluide pour les boutons */
.btn-primary {
    transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Effet de pulsation pour les notifications */
@keyframes notification-pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.badge:not(:empty) {
    animation: notification-pulse 2s infinite;
}


/* Amélioration visuelle pour les boutons d'action */
.btn-primary i {
    transition: all 0.4s ease;
}

.btn-primary:hover i {
    transform: scale(1.2);
    background: rgba(255, 255, 255, 0.3);
}

/* Amélioration du header pour plus de profondeur */
.header {
    background: linear-gradient(90deg, #0056b3, #0080ff, #0056b3);
    background-size: 200% 100%;
    animation: gradient-shift 15s ease infinite;
}

@keyframes gradient-shift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
/* Notification item avec plus de style */
.notification-item {
    position: relative;
    padding-left: 40px;
}

.notification-item::before {
    content: '\f0f3';
    font-family: 'FontAwesome';
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.notification-item.approved::before {
    content: '\f00c';
    color: #27ae60;
}

.notification-item.rejected::before {
    content: '\f00d';
    color: #e74c3c;
}

/* Style pour le menu burger lorsqu'il est actif */
#checkbox:checked ~ .header .menu-icon {
    background: rgba(255, 255, 255, 0.2);
}

#checkbox:checked ~ .header .menu-icon i {
    transform: rotate(90deg);
}

/* Effet de carte pour les boutons principaux */
.btn-primary {
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}


/* Amélioration de l'effet hover sur les notifications */
.notification-item {
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.notification-item:hover {
    border-left-color: #0080ff;
    padding-left: 45px;
}

.notification-item.approved:hover {
    border-left-color: #27ae60;
}

.notification-item.rejected:hover {
    border-left-color: #e74c3c;
}

/* Style pour les boutons dans le mode sombre (préparation) */
@media (prefers-color-scheme: dark) {
    .notification-list {
        background: #2a2a2a;
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    .notification-list::before {
        border-bottom-color: #2a2a2a;
    }
    
    .notification-item {
        border-bottom-color: #333;
        color: #ddd;
    }
    
    .notification-item:hover {
        background-color: #333;
    }
}

/* Amélioration de la fluidité des animations */
* {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Ombre de texte améliorée */
.u-name, .user-p h4 {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
}

/* Ajout d'animations subtiles */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn-item {
    animation: fadeInUp 0.6s ease backwards;
}

.btn-item:nth-child(1) {
    animation-delay: 0.1s;
}

.btn-item:nth-child(2) {
    animation-delay: 0.2s;
}

.btn-item:nth-child(3) {
    animation-delay: 0.3s;
}

.btn-item:nth-child(4) {
    animation-delay: 0.4s;
}

/* Rendre les boutons principaux plus interactifs */
.btn-primary {
    transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.btn-primary:active {
    transform: scale(0.95);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
.logout-btn {
    background-color: #8f2630;
    color: #ff4d4d;
    font-weight: bold;
    transition: all 0.3s ease;
}

.logout-btn i {
    color:rgb(244, 244, 244);
}

/* Sidebar */
.body {
    display: flex;
    flex: 1;
    position: relative;
    min-height: calc(100vh - 70px);
}

.side-bar {
    width: 300px;
    background: linear-gradient(180deg, #1a2980 0%, #26d0ce 100%);
    color: var(--white);
    transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: var(--shadow-md);
    z-index: 50;
    position: relative;
    overflow: hidden;
}

.side-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="white" opacity="0.05" d="M0,0 L100,100 L0,100 Z"/></svg>');
    background-size: cover;
    pointer-events: none;
    z-index: -1;
}

#checkbox:checked ~ .body .side-bar {
    width: 80px;
}

#checkbox:checked ~ .body .side-bar .user-p,
#checkbox:checked ~ .body .side-bar a span {
    display: none;
}

#checkbox:checked ~ .body .side-bar ul li a i {
    margin-right: 0;
}
#checkbox:checked ~ .body .side-bar .user-p img {
    width: 50px;
    height: 50px;
    border-width: 2px;
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
    color: white;
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

#checkbox {
    display: none;
}

#checkbox:checked ~ .body .side-bar {
    width: 80px;
    text-align: center;
}

#checkbox:checked ~ .body .side-bar .user-p {
    visibility: hidden;
    opacity: 0;
    height: 0;
    padding: 0;
    margin: 0;
}

#checkbox:checked ~ .body .side-bar ul li a span {
    display: none;
}

#checkbox:checked ~ .body .side-bar ul li {
    text-align: center;
}

#checkbox:checked ~ .body .side-bar ul li a i {
    font-size: 22px;
    padding-right: 0;
    width: 100%;
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

</style>
