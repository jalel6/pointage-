<?php

// Vérifier si l'utilisateur est bien un employé
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'employé') {
    // Si ce n'est pas un employé, rediriger vers la page de login
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'employé
$employe_id = $_SESSION['user_id'];
$_SESSION['employe_id'] = $_SESSION['user_id'];

include 'db.php';

$query = "SELECT nom, prenom, photo_profil FROM employes WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();
$employe = $result->fetch_assoc();

// Vérifier si les données sont bien récupérées
if (!$employe) {
    die("Erreur : Impossible de récupérer les informations de l'utilisateur.");
}

// Récupérer le chemin de l'image de profil
$nom_complet = htmlspecialchars($employe['prenom'] . " " . $employe['nom']);
$image_path = !empty($employe['photo_profil']) ? htmlspecialchars($employe['photo_profil']) : "images/default.jpg";

// Récupérer le nombre de notifications non vues
$notif_count = 0;
$notif_query = "SELECT COUNT(*) AS count FROM demandeconge WHERE employe_id = ? AND notification_vue = 0 AND (statut = 'approuvé' OR statut = 'rejeté')";
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
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="employe_layout.css">
   </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <header class="header">
        <label for="checkbox" class="menu-icon">
            <i class="fa fa-bars" aria-hidden="true"></i>
        </label>
        <h2 class="u-name">AHWA <b>SOLUTIONS</b></h2>
        
        <!-- Icône de notifications -->
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
            <?php
            // Récupérer les notifications non vues
            $notif_query = "SELECT idDemande, dateSoumission, statut FROM demandeconge 
                            WHERE employe_id = ? AND notification_vue = 0 AND (statut = 'approuvé' OR statut = 'rejeté')";
            $notif_stmt = $conn->prepare($notif_query);
            $notif_stmt->bind_param("i", $employe_id);
            $notif_stmt->execute();
            $notif_result = $notif_stmt->get_result();

            while ($row = $notif_result->fetch_assoc()) {
                $class = $row['statut'] === 'approuvé' ? 'approved' : 'rejected';
                echo "<div class='notification-item $class'>";
                if ($row['statut'] === 'approuvé') {
                    echo "Votre demande de congé du " . $row['dateSoumission'] . " a été <strong>approuvée</strong>.";
                } else {
                    echo "Votre demande de congé du " . $row['dateSoumission'] . " a été <strong>rejetée</strong>.";
                }
                echo "</div>";
            }

            // Marquer comme vu après affichage
            $update = "UPDATE demandeconge SET notification_vue = 1 
                      WHERE employe_id = ? AND notification_vue = 0 AND (statut = 'approuvé' OR statut = 'rejeté')";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("i", $employe_id);
            $stmt->execute();
            ?>
        </div>
    </header>

    <div class="body">
        <!-- Sidebar -->
        <nav class="side-bar">
            <div class="user-p">
                <img src="<?php echo $image_path; ?>" alt="Photo de profil">
                <h4><?php echo $nom_complet; ?></h4>
            </div>
            <ul>
                <li><a href="employee_dashboard.php"><i class="fa fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profil_employe.php"><i class="fa fa-id-badge"></i><span>Profile</span></a></li>
                <li><a href="historique.php"><i class="fa fa-history"></i><span>Historique</span></a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>
            </ul>
        </nav>

        <!-- Contenu principal (sera rempli par les pages qui incluent ce layout) -->
        <div class="main-content">
            <?php if(isset($content_title)): ?>
                <h2><?php echo $content_title; ?></h2>
            <?php endif; ?>
            
            <!-- Le contenu spécifique de chaque page sera inséré ici -->
            <div id="content">
                <!-- Contenu principal à remplacer par chaque page -->
            </div>
        </div>
    </div>

    <script>
        // Script pour gérer l'affichage des notifications
        const notifIcon = document.getElementById('notification-icon');
        const notifList = document.getElementById('notification-list');

        notifIcon.addEventListener('click', () => {
            notifList.classList.toggle('active');
        });

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
    <style>
    /* Ajoutez ce code dans la partie <style> de votre layout_employer.php */

    /* === Modern Profile Container === */
    .profile-container {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin: 2rem auto;
        max-width: 900px;
        width: 90%;
        border: 1px solid rgba(0, 86, 179, 0.1);
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    .profile-container::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: radial-gradient(ellipse at center, rgba(0, 128, 255, 0.15) 0%, rgba(0, 128, 255, 0) 70%);
        z-index: 0;
    }

    .profile-container::after {
        content: '';
        position: absolute;
        bottom: -30px;
        left: -30px;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: radial-gradient(ellipse at center, rgba(38, 208, 206, 0.15) 0%, rgba(38, 208, 206, 0) 70%);
        z-index: 0;
    }

    /* === Profile Header === */
    .profile-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2rem;
        position: relative;
    }

    .profile-picture {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.4s ease;
        margin-bottom: 1rem;
    }

    .profile-picture:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .profile-picture img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .profile-name h1 {
        font-size: 1.8rem;
        color: #0056b3;
        margin: 0.5rem 0;
        font-weight: 600;
        text-align: center;
    }

    /* === Profile Info === */
    .profile-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .profile-info p {
        background-color: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        border-left: 3px solid #0080ff;
    }

    .profile-info p:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .profile-info strong {
        color: #0056b3;
        font-weight: 600;
    }

    /* === Profile Footer === */
    .profile-footer {
        text-align: center;
        margin-top: 2rem;
    }

    .btn {
        background: linear-gradient(135deg, #0056b3, #0080ff);
        color: white;
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    }

    .btn:hover {
        background: linear-gradient(135deg, #004a99, #0073e6);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 86, 179, 0.4);
    }

    /* === Modal Styles === */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal.show {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background-color: white;
        border-radius: 12px;
        padding: 2rem;
        width: 90%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        position: relative;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        color: #666;
        transition: all 0.3s ease;
    }

    .close:hover {
        color: #0056b3;
        transform: rotate(90deg);
    }

    .modal-content h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #0056b3;
        position: relative;
        padding-bottom: 0.5rem;
    }

    .modal-content h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #0056b3, #0080ff);
        border-radius: 3px;
    }

    /* Form Styles */
    .input-group {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .modal-content label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #444;
    }

    .modal-content input,
    .modal-content select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .modal-content input:focus,
    .modal-content select:focus {
        border-color: #0080ff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 128, 255, 0.1);
    }

    .modal-content input[disabled] {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .profile-info {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .input-group {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .profile-info {
            grid-template-columns: 1fr;
        }
        
        .profile-container {
            padding: 1.5rem;
        }
    }
</style>