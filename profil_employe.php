<?php
session_start();

// Vérifie si l'utilisateur est bien connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employé') {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Récupération des informations de l'employé connecté
$employe_id = $_SESSION['user_id'];
$sql = "SELECT nom, prenom, email, telephone, cin, adresse, sexe, age, poste, fonction, date_embauche, photo_profil FROM employes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();
$employe = $result->fetch_assoc();

// S'il n'y a pas d'utilisateur trouvé
if (!$employe) {
    die("Erreur : Informations introuvables.");
}

// Photo de profil (default si vide)
$photo = !empty($employe['photo_profil']) ? $employe['photo_profil'] : "images/default-profile.png";

// Définition des variables utilisées dans le template
$image_path = $photo;
$nom_complet = $employe['prenom'] . ' ' . $employe['nom'];


// traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employe_id = $_SESSION['user_id'];

    // Récupération des champs non modifiables
    $stmt_current = $conn->prepare("SELECT poste, fonction, date_embauche FROM employes WHERE id = ?");
    if ($stmt_current) {
        $stmt_current->bind_param("i", $employe_id);
        $stmt_current->execute();
        $result = $stmt_current->get_result();
        $currentData = $result->fetch_assoc();
        $stmt_current->close();
    } else {
        die("Erreur lors de la préparation de la requête.");
    }

    // Champs modifiables (sécurisation simple avec trim)
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $cin = trim($_POST['cin']);
    $adresse = trim($_POST['adresse']);
    $sexe = trim($_POST['sexe']);
    $age = intval($_POST['age']); // age doit être un entier

    $poste = $currentData['poste'];
    $fonction = $currentData['fonction'];
    $date_embauche = $currentData['date_embauche'];

    // Validation des champs
    $error = '';
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $nom)) {
        $error = "Le nom ne doit contenir que des lettres.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $prenom)) {
        $error = "Le prénom ne doit contenir que des lettres.";
    } elseif (!preg_match("/^\d{8}$/", $cin)) {
        $error = "Le CIN doit contenir exactement 8 chiffres.";
    } elseif (!preg_match("/^\d{8}$/", $telephone)) {
        $error = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'email n'est pas valide.";
    } elseif ($age < 18 || $age > 65) {
        $error = "L'âge doit être compris entre 18 et 65 ans.";
    }

    if (empty($error)) {
        // Vérification unicité
        $stmt = $conn->prepare("SELECT id FROM employes WHERE cin = ? AND id != ?");
        $stmt->bind_param("si", $cin, $employe_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Ce CIN est déjà utilisé.";
        }

        $stmt = $conn->prepare("SELECT id FROM employes WHERE telephone = ? AND id != ?");
        $stmt->bind_param("si", $telephone, $employe_id);
        $stmt->execute();
        if (empty($error) && $stmt->get_result()->num_rows > 0) {
            $error = "Ce numéro de téléphone est déjà utilisé.";
        }

        $stmt = $conn->prepare("SELECT id FROM employes WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $employe_id);
        $stmt->execute();
        if (empty($error) && $stmt->get_result()->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        }
    }

    // Si erreur, afficher le message d'erreur avec SweetAlert
    if (!empty($error)) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "Erreur",
                    text: "' . $error . '",
                    icon: "error",
                    confirmButtonText: "OK"
                });
            });
        </script>';
    } else {
        // Photo de profil
        $photo_path = $_POST['current_photo'];
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $photo_tmp = $_FILES['photo_profil']['tmp_name'];
            $photo_name = basename($_FILES['photo_profil']['name']);
            $upload_dir = 'uploads/';
            $photo_path = $upload_dir . $photo_name;

            move_uploaded_file($photo_tmp, $photo_path);
        }

        // Mise à jour dans la base
        $sql = "UPDATE employes SET nom = ?, prenom = ?, email = ?, telephone = ?, cin = ?, adresse = ?, sexe = ?, age = ?, poste = ?, fonction = ?, date_embauche = ?, photo_profil = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssssssssi", $nom, $prenom, $email, $telephone, $cin, $adresse, $sexe, $age, $poste, $fonction, $date_embauche, $photo_path, $employe_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            title: "Succès",
                            text: "Votre profil a été mis à jour avec succès.",
                            icon: "success",
                            confirmButtonText: "OK"
                        });
                    });
                </script>';
            } else {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            title: "Erreur",
                            text: "Une erreur s\'est produite lors de la mise à jour.",
                            icon: "error",
                            confirmButtonText: "OK"
                        });
                    });
                </script>';
            }
        } else {
            die("Erreur lors de la préparation de la mise à jour.");
        }
    }
}

// Récupération des infos actuelles
$employe_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nom, prenom, email, telephone, cin, adresse, sexe, age, poste, fonction, date_embauche, photo_profil FROM employes WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $employe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employe = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Erreur lors de la préparation de la requête SELECT.");
}

if (!$employe) {
    die("Erreur : Employé introuvable.");
}

$photo = !empty($employe['photo_profil']) ? $employe['photo_profil'] : "images/default-profile.png";
   


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
    <title>Mon Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Vous pouvez ajouter ici votre CSS existant */
        
        /* Style pour cacher le modal par défaut */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            display: block;
            opacity: 1;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 700px;
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .modal-content.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        /* Loader */
        .loader {
            display: none;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid #3498db;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <header class="header">
        <label for="checkbox" class="menu-icon">
            <i class="fa fa-bars" aria-hidden="true"></i>
        </label>
        <h2 class="u-name">AHWA <b>SOLUTIONS</b></h2>
        
        <!-- Icône de notification -->
        <div class="header-icon" id="notification-icon">
            <i class="fa fa-bell"></i>
            <?php
                if (isset($notif_count) && $notif_count > 0) {
                    echo "<span class='badge'>$notif_count</span>";
                }
            ?>
            
         



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



    </header>
    <div class="body">
        <nav class="side-bar">
            <div class="user-p">
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Photo de profil" id="sidebar-user-img">
                <h4 id="sidebar-user-name"><?php echo htmlspecialchars($nom_complet); ?></h4>
            </div>
            <ul>
                <li><a href="employee_dashboard.php"><i class="fa fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profil_employe.php"><i class="fa fa-id-badge"></i><span>Profile</span></a></li>
                <li><a href="historique.php"><i class="fa fa-history"></i><span>Historique</span></a></li>
                <li><a href="logout.php" class="logout-btn"> <i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>
            </ul>
        </nav>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Photo de profil" id="profile-img">
                </div>

                <div class="profile-name">
                    <h1 id="profile-name-display"><?php echo htmlspecialchars($employe['prenom']) . ' ' . htmlspecialchars($employe['nom']); ?></h1>
                </div>
            </div>
            <div class="profile-info">
                <p><strong>Email :</strong> <span id="profile-email"><?php echo htmlspecialchars($employe['email']); ?></span></p>
                <p><strong>Téléphone :</strong> <span id="profile-telephone"><?php echo htmlspecialchars($employe['telephone']); ?></span></p>
                <p><strong>CIN :</strong> <span id="profile-cin"><?php echo htmlspecialchars($employe['cin']); ?></span></p>
                <p><strong>Adresse :</strong> <span id="profile-adresse"><?php echo htmlspecialchars($employe['adresse']); ?></span></p>
                <p><strong>Sexe :</strong> <span id="profile-sexe"><?php echo htmlspecialchars($employe['sexe']); ?></span></p>
                <p><strong>Âge :</strong> <span id="profile-age"><?php echo htmlspecialchars($employe['age']); ?></span> ans</p>
                <p><strong>Poste :</strong> <?php echo htmlspecialchars($employe['poste']); ?></p>
                <p><strong>Fonction :</strong> <?php echo htmlspecialchars($employe['fonction']); ?></p>
                <p><strong>Date d'Embauche :</strong> <?php echo htmlspecialchars($employe['date_embauche']); ?></p>
            </div>
            <div class="profile-footer">
                <button class="btn" onclick="openModal()">Modifier</button>
            </div>
        </div>

<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Modifier le profil</h2>
     <form method="POST" enctype="multipart/form-data">

    <div class="input-group">
        <div>
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($employe['nom']); ?>" required>
        </div>
        <div>
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($employe['prenom']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employe['email']); ?>" required>
        </div>
        <div>
            <label for="telephone">Téléphone</label>
            <input type="number" id="telephone" name="telephone" value="<?php echo htmlspecialchars($employe['telephone']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="cin">CIN</label>
            <input type="text" id="cin" name="cin" value="<?php echo htmlspecialchars($employe['cin']); ?>" required>
        </div>
        <div>
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($employe['adresse']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="sexe">Sexe</label>
            <select id="sexe" name="sexe" required>
                <option value="Homme" <?php if($employe['sexe'] == 'Homme') echo 'selected'; ?>>Homme</option>
                <option value="Femme" <?php if($employe['sexe'] == 'Femme') echo 'selected'; ?>>Femme</option>
            </select>
        </div>
        <div>
            <label for="age">Âge</label>
            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($employe['age']); ?>" required>
        </div>
    </div>

    <div class="input-group">
    <div>
    <label for="poste">Poste</label>
    <input type="text" id="poste" name="poste" value="<?php echo htmlspecialchars($employe['poste']); ?>" disabled>
</div>
<div>
    <label for="fonction">Fonction</label>
    <input type="text" id="fonction" name="fonction" value="<?php echo htmlspecialchars($employe['fonction']); ?>" disabled>
</div>
    </div>

    <div class="input-group">
    <div>
    <label for="date_embauche">Date d'embauche</label>
    <input type="date" id="date_embauche" name="date_embauche" value="<?php echo htmlspecialchars($employe['date_embauche']); ?>" disabled>
</div>

        <div>
            <label for="photo_profil">Photo de profil</label>
            <input type="file" id="photo_profil" name="photo_profil">
            <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($employe['photo_profil']); ?>">
        </div>
    </div>
    <button type="submit" class="btn">Mettre à jour</button>
</form>
</div>
</div>

    </div>
</div>
    
    <!-- Scripts -->
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
    
    <script>
        function openModal() {
            document.getElementById('modal').classList.add('show');
            document.querySelector('.modal-content').classList.add('show');
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('show');
            document.querySelector('.modal-content').classList.remove('show');
        }
        
        // Soumission du formulaire via AJAX
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('submit-btn');
            const loader = document.getElementById('submit-loader');
            
            // Afficher le loader et désactiver le bouton
            submitBtn.disabled = true;
            loader.style.display = 'block';
            
            fetch('profil_employe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Cacher le loader et réactiver le bouton
                submitBtn.disabled = false;
                loader.style.display = 'none';
                
                if (data.status === 'success') {
                    // Afficher SweetAlert
                    Swal.fire({
                        title: "Bravo!",
                        text: data.message,
                        icon: "success"
                    });
                    
                    // Mettre à jour l'interface utilisateur avec les nouvelles données
                    document.getElementById('profile-name-display').textContent = data.data.prenom + ' ' + data.data.nom;
                    document.getElementById('profile-email').textContent = data.data.email;
                    document.getElementById('profile-telephone').textContent = data.data.telephone;
                    document.getElementById('profile-cin').textContent = data.data.cin;
                    document.getElementById('profile-adresse').textContent = data.data.adresse;
                    document.getElementById('profile-sexe').textContent = data.data.sexe;
                    document.getElementById('profile-age').textContent = data.data.age;
                    
                    // Mettre à jour la photo de profil si nécessaire
                    if (data.data.photo) {
                        document.getElementById('profile-img').src = data.data.photo;
                        document.getElementById('sidebar-user-img').src = data.data.photo;
                    }
                    
                    // Mettre à jour le nom dans la barre latérale
                    document.getElementById('sidebar-user-name').textContent = data.data.nom_complet;
                    
                    // Fermer le modal après le succès
                    closeModal();
                } else {
                    // Afficher une erreur
                    Swal.fire({
                        title: "Erreur!",
                        text: data.message,
                        icon: "error"
                    });
                }
            })
            .catch(error => {
                // Cacher le loader et réactiver le bouton
                submitBtn.disabled = false;
                loader.style.display = 'none';
                
                // Afficher une erreur
                Swal.fire({
                    title: "Erreur!",
                    text: "Une erreur est survenue lors de la mise à jour du profil.",
                    icon: "error"
                });
                console.error('Erreur:', error);
            });
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
    /* Modal */
.modal {
    opacity: 0;
    visibility: hidden;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.modal.show {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.98));
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    max-width: 650px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    transform: scale(0.8) translateY(-20px);
    opacity: 0;
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    border: 1px solid rgba(0, 86, 179, 0.1);
    position: relative;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.modal-content.show {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text-light);
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius-full);
    background: rgba(0, 0, 0, 0.05);
    z-index: 2;
}

.close:hover {
    color: var(--primary-light);
    background: rgba(0, 128, 255, 0.1);
    transform: rotate(90deg);
}

.modal-content h2 {
    text-align: center;
    margin: 0 0 1.8rem 0;
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--primary-color);
    position: relative;
    padding-bottom: 1rem;
}

.modal-content h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    border-radius: 3px;
}

/* Formulaire */
.modal-content form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-top: 1.5rem;
}

.input-group {
    display: contents;
}

.input-group > div {
    position: relative;
}

.modal-content label {
    font-size: 0.9rem;
    font-weight: 500;
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    transition: all 0.3s ease;
    transform-origin: left;
}

.modal-content input,
.modal-content select {
    padding: 0.9rem 1rem;
    font-size: 0.95rem;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: var(--border-radius-md);
    width: 100%;
    background-color: rgba(249, 249, 249, 0.9);
    transition: all 0.3s ease;
    color: var(--text-color);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05) inset;
}

.modal-content input:focus,
.modal-content select:focus {
    border-color: var(--primary-light);
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 128, 255, 0.15);
    background-color: var(--white);
}

.modal-content input[type="file"] {
    padding: 0.8rem;
    background-color: rgba(249, 249, 249, 0.7);
    cursor: pointer;
}

.modal-content input[type="file"]:hover {
    background-color: rgba(0, 128, 255, 0.05);
}

.modal-content input[readonly] {
    background-color: #f5f5f5;
    cursor: not-allowed;
    opacity: 0.7;
    border-color: rgba(0, 0, 0, 0.1);
}

.modal-content .btn {
    grid-column: span 2;
    justify-self: center;
    min-width: 50%;
    margin-top: 2rem;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes borderAnimation {
    0% {
        border-image-source: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    }
    50% {
        border-image-source: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    }
    100% {
        border-image-source: linear-gradient(225deg, var(--primary-color), var(--secondary-color));
    }
}

/* Responsive Design */
@media (max-width: 992px) {
    .profile-info {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .profile-info {
        grid-template-columns: repeat(1, 1fr);
    }
    
    .profile-header {
        padding: 2rem 1.5rem 1.5rem;
        margin: -2rem -2rem 1.5rem -2rem;
    }
    
    .profile-picture {
        width: 120px;
        height: 120px;
        margin-bottom: 1rem;
    }
    
    .profile-name h1 {
        font-size: 1.6rem;
    }
    
    .profile-container {
        padding: 2rem;
        margin: 1.5rem auto;
    }
    
    .modal-content {
        padding: 2rem;
    }
    
    .modal-content form {
        grid-template-columns: 1fr;
    }
    
    .modal-content .btn {
        grid-column: span 1;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .profile-container {
        margin: 1rem auto;
        padding: 1.5rem;
        width: 95%;
    }
    
    .profile-header {
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
        padding: 1.5rem 1rem;
    }
    
    .btn {
        padding: 0.8rem 1.5rem;
        font-size: 0.9rem;
    }
    
    .profile-picture {
        width: 100px;
        height: 100px;
    }
    
    .profile-name h1 {
        font-size: 1.4rem;
    }
}

/* Enhanced Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.03);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, var(--primary-light), var(--secondary-color));
    border-radius: 10px;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
}
```



    </style>


<style>
    .logout-btn {
    background-color: #8f2630;
    color: #ff4d4d;
    font-weight: bold;
    transition: all 0.3s ease;
}

    * {
    padding: 0;
    margin: 0;
    box-sizing: border-box;
    font-family: 'Poppins', 'Segoe UI', sans-serif;
    transition: all 0.3s ease;
}

:root {
    --primary-color: #0056b3;
    --primary-light: #0080ff;
    --primary-dark: #003d82;
    --secondary-color: #26d0ce;
    --text-color: #333;
    --text-light: #666;
    --white: #fff;
    --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 10px 20px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 15px 30px rgba(0, 0, 0, 0.18);
    --border-radius-sm: 8px;
    --border-radius-md: 12px;
    --border-radius-lg: 20px;
    --border-radius-full: 50%;
}

body {
    background: linear-gradient(135deg, #f6f9fc, #e3eeff);
    background-size: cover;
    min-height: 100vh;
    overflow-x: hidden;
    position: relative;
    font-size: 16px;
    color: var(--text-color);
    line-height: 1.5;
}

/* Enhanced background with multiple layers */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('images/tt.jpg') no-repeat center center fixed;
    background-size: cover;
    filter: blur(3px) saturate(1.2);
    opacity: 0.15;
    z-index: -2;
}

body::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at top left, rgba(0, 128, 255, 0.05), transparent 30%),
        radial-gradient(circle at bottom right, rgba(38, 208, 206, 0.07), transparent 40%);
    z-index: -1;
}

/* Modern Navigation Bar */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background: linear-gradient(90deg, var(--primary-dark), var(--primary-light));
    color: var(--white);
    position: sticky;
    top: 0;
    box-shadow: var(--shadow-md);
    z-index: 100;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.menu-icon {
    font-size: 1.4rem;
    color: var(--white);
    cursor: pointer;
    position: relative;
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius-full);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.menu-icon:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.1) rotate(5deg);
}

.u-name {
    font-size: 1.4rem;
    font-weight: 500;
    letter-spacing: 0.5px;
    padding-left: 1.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
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
    gap: 1.25rem;
}

.header-actions a {
    color: var(--white);
    text-decoration: none;
    opacity: 0.9;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.header-actions a:hover {
    opacity: 1;
    transform: translateY(-2px);
}

.header-actions i {
    font-size: 1.4rem;
}

/* Modern Sidebar */
.body {
    display: flex;
    flex: 1;
    position: relative;
    min-height: calc(100vh - 70px);
}

.side-bar {
    width: 250px;
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
    padding: 2rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    margin-bottom: 1.5rem;
    position: relative;
}

.user-p img {
    width: 100px;
    height: 100px;
    border-radius: var(--border-radius-full);
    object-fit: cover;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.1));
}

.user-p img:hover {
    transform: scale(1.05) translateY(-5px);
    border-color: rgba(255, 255, 255, 0.7);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}

.user-p h4 {
    margin-top: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: black
    6;
}

.side-bar ul {
    list-style: none;
    padding: 0 1rem;
}

.side-bar ul li {
    margin: 0.8rem 0;
    border-radius: var(--border-radius-md);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.side-bar ul li:hover {
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
}

.side-bar ul li a {
    color: var(--white);
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    z-index: 1;
    overflow: hidden;
}

.side-bar ul li a::before {
    content: '';
    position: absolute;
    left: -100%;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: all 0.5s ease;
    z-index: -1;
}

.side-bar ul li a:hover::before {
    left: 100%;
}

.side-bar ul li a i {
    margin-right: 1rem;
    font-size: 1.2rem;
    width: 1.5rem;
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
    font-size: 1.4rem;
    padding-right: 0;
    width: 100%;
}

/* Modern Header Icons */
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

/* Enhanced Badge for notifications */
.badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ff3e55, #ff5e3a);
    color: var(--white);
    font-size: 0.7rem;
    font-weight: bold;
    border-radius: var(--border-radius-full);
    padding: 0.25rem;
    min-width: 1.5rem;
    height: 1.5rem;
    text-align: center;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--white);
    z-index: 2;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 62, 85, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 62, 85, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 62, 85, 0);
    }
}

/* Modern Notification List */
.notification-list {
    display: none;
    position: absolute;
    top: 60px;
    right: 10px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: var(--border-radius-md);
    width: 350px;
   max-height: 450px;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    z-index: 100;
    animation: fadeInDown 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: 1px solid rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
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
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    color: var(--text-color);
    font-size: 0.9rem;
    line-height: 1.5;
    transition: all 0.3s ease;
    position: relative;
    padding-left: 3rem;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.notification-item:hover {
    background-color: rgba(0, 128, 255, 0.05);
    border-left: 4px solid var(--primary-light);
    padding-left: 3.25rem;
}


.notification-item::before {
    content: '\f0f3';
    font-family: 'FontAwesome';
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 1.2rem;
}

.notification-item.approved::before {
    content: '\f00c';
    color: #27ae60;
}

.notification-item.rejected::before {
    content: '\f00d';
    color: #e74c3c;
}

/* Enhanced arrow for notification list */
.notification-list::before {
    content: "";
    position: absolute;
    top: -10px;
    right: 15px;
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid rgba(255, 255, 255, 0.95);
    filter: drop-shadow(0 -2px 2px rgba(0, 0, 0, 0.05));
}

/* Empty notifications state */
.notification-list:empty::after {
    content: "No notifications at the moment";
    display: block;
    padding: 2rem;
    text-align: center;
    color: var(--text-light);
    font-style: italic;
    font-size: 0.95rem;
}
/* === Modern Profile Container (Compact) === */
.profile-container {
    flex: 1;
    padding: 1.5rem; /* Réduit l'espacement intérieur */
    background-color: rgba(255, 255, 255, 0.85);
    border-radius: var(--border-radius-md); /* Coins légèrement moins arrondis */
    box-shadow: var(--shadow-md); /* Ombre plus discrète */
    margin: 1.5rem auto; /* Moins de marge extérieure */
    max-width: 900px;
    width: 90%;
    border: 1px solid rgba(0, 86, 179, 0.1);
    backdrop-filter: blur(15px); /* Flou plus léger */
    -webkit-backdrop-filter: blur(15px);
    position: relative;
    overflow: hidden;
}

/* Cercles décoratifs (inchangés ou légèrement réduits si tu veux) */
.profile-container::before {
    width: 150px;
    height: 150px;
    top: -40px;
    right: -40px;
}

.profile-container::after {
    width: 120px;
    height: 120px;
    bottom: -20px;
    left: -20px;
}

/* === Modern Profile Header (Compact) === */
.profile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    margin: -2rem -2rem 1rem -2rem; /* Réduit les marges */
    padding: 2rem 1.5rem 1.25rem; /* Moins d’espace vertical */
    border-bottom: none;
    position: relative;
    border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
    box-shadow: var(--shadow-sm);
    color: var(--white);
}

/* Bande blanche transparente arrondie en bas du header */
.profile-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 50px;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.9), transparent); /* Dégradé vers le haut */
    border-radius: 0 0 50% 50%; /* Bord bas arrondi */
}

/* === Photo de profil === */
.profile-picture {
    width: 100px; /* Réduit la largeur */
    height: 100px; /* Réduit la hauteur */
    border-radius: var(--border-radius-full);
    border: none;
    box-shadow: var(--shadow-md); /* Ombre plus légère adaptée à la taille */
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    margin-bottom: 1rem; /* Réduit l'espacement dessous */
    position: relative;
    z-index: 1;
}

/* Effet au survol de la photo */
.profile-picture:hover {
    transform: scale(1.05) translateY(-5px); /* Zoom + déplacement vers le haut */
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25); /* Ombre plus marquée */
}

/* Image à l'intérieur de la photo de profil */
.profile-picture img {
    width: 100%;
    height: 100%;
    border-radius: var(--border-radius-full);
    border: none;
    object-fit: cover; /* Recadrer l’image sans la déformer */
    transition: all 0.4s ease; /* Animation au survol */
}

/* Zoom supplémentaire de l’image au survol */
.profile-picture:hover img {
    transform: scale(1.1);
}

/* === Nom du profil === */
.profile-name h1 {
    font-size: 2rem; /* Taille du texte */
    color: var(--white); /* Couleur blanche */
    margin: 0 0 0.5rem 0; /* Marge basse */
    font-weight: 700; /* Texte en gras */
    text-align: center; /* Centré horizontalement */
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* Légère ombre pour lisibilité */
    letter-spacing: 0.5px; /* Espacement entre les lettres */
}

/* Modern Profile Info Grid */
.profile-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem; /* Réduit l'espace entre les colonnes */
    margin-top: 0.5rem; /* Réduit l’espace au-dessus */
    position: relative;
    z-index: 1;
}

.profile-info p {
    margin: 0;
    padding: 0.75rem 1rem; /* Moins de padding pour réduire la hauteur */
    background-color: rgba(255, 255, 255, 0.9);
    border-left: 3px solid var(--primary-light); /* Optionnel : un peu plus fin */
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-size: 0.9rem; /* Taille de texte légèrement réduite */
    position: relative;
    overflow: hidden;
}


.profile-info p::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0, 128, 255, 0.05), transparent);
    opacity: 0;
    transition: all 0.4s ease;
}

.profile-info p:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-md);
    background-color: var(--white);
}

.profile-info p:hover::before {
    opacity: 1;
}

.profile-info strong {
    color: var(--primary-color);
    font-weight: 600;
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    position: relative;
}

.profile-info strong::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 30px;
    height: 2px;
    background: var(--primary-light);
    border-radius: 2px;
}

.profile-footer {
    text-align: center;
    margin-top: 2.5rem;
    position: relative;
    z-index: 1;
}

/* Modern Button Styling */
.btn {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: var(--white);
    border: none;
    padding: 0.9rem 2.5rem;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    letter-spacing: 0.5px;
    gap: 0.5rem;
}

.btn:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
    transform: translateY(-5px) scale(1.03);
    box-shadow: 0 10px 30px rgba(0, 128, 255, 0.3);
}

.btn:active {
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 5px 15px rgba(0, 128, 255, 0.2);
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius-full);
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: all 0.6s ease;
}

.btn:hover::before {
    width: 300%;
    height: 300%;
    opacity: 0.4;
}

/* Modern Modal Styling */
.modal {
    opacity: 0;
    visibility: hidden;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.modal.show {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.98));
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    max-width: 650px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    transform: scale(0.8) translateY(-20px);
    opacity: 0;
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    border: 1px solid rgba(0, 86, 179, 0.1);
    position: relative;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.modal-content.show {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text-light);
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius-full);
    background: rgba(0, 0, 0, 0.05);
    z-index: 2;
}

.close:hover {
    color: var(--primary-light);
    background: rgba(0, 128, 255, 0.1);
    transform: rotate}
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
