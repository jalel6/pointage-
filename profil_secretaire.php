<?php
session_start();

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'secrétaire') {
    header("Location: index.php");
    exit();
}

include 'db.php';
$secretaire_id = $_SESSION['user_id'];

// Gestion de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validation des données
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $cin = trim($_POST['cin']);
    $adresse = trim($_POST['adresse']);
    $sexe = $_POST['sexe'];
    $age = intval($_POST['age']);

    // Validation des champs
    $errors = [];
    
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $nom)) {
        $errors[] = "Le nom ne doit contenir que des lettres.";
    }
    
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $prenom)) {
        $errors[] = "Le prénom ne doit contenir que des lettres.";
    }
    
    if (!preg_match("/^\d{8}$/", $cin)) {
        $errors[] = "Le CIN doit contenir exactement 8 chiffres.";
    }
    
    if (!preg_match("/^\d{8}$/", $telephone)) {
        $errors[] = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }
    
    if ($age < 18 || $age > 65) {
        $errors[] = "L'âge doit être compris entre 18 et 65 ans.";
    }

    // Vérification unicité
    $checks = [
        ['field' => 'cin', 'message' => 'Ce CIN est déjà utilisé.'],
        ['field' => 'telephone', 'message' => 'Ce numéro de téléphone est déjà utilisé.'],
        ['field' => 'email', 'message' => 'Cet email est déjà utilisé.']
    ];

    foreach ($checks as $check) {
        $stmt = $conn->prepare("SELECT id FROM employes WHERE {$check['field']} = ? AND id != ?");
        $stmt->bind_param("si", $_POST[$check['field']], $secretaire_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = $check['message'];
        }
    }

    // Gestion de la photo
    $photo_path = $_POST['current_photo'];
    if (!empty($_FILES['photo_profil']['name'])) {
        $photo_profil = $_FILES['photo_profil']['name'];
        $photo_tmp = $_FILES['photo_profil']['tmp_name'];
        $photo_path = 'uploads/' . $photo_profil;
        move_uploaded_file($photo_tmp, $photo_path);
    }

    // Récupération des champs non modifiables
    $query = "SELECT poste, fonction, date_embauche FROM employes WHERE id = ?";
    $stmt_select = $conn->prepare($query);
    $stmt_select->bind_param("i", $secretaire_id);
    $stmt_select->execute();
    $stmt_select->bind_result($poste, $fonction, $date_embauche);
    $stmt_select->fetch();
    $stmt_select->close();

    // Si pas d'erreurs, mise à jour du profil
    if (empty($errors)) {
        $sql = "UPDATE employes SET 
                nom = ?, 
                prenom = ?, 
                email = ?, 
                telephone = ?, 
                cin = ?, 
                adresse = ?, 
                sexe = ?, 
                age = ?, 
                poste = ?, 
                fonction = ?, 
                date_embauche = ?, 
                photo_profil = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssssssi", 
            $nom, $prenom, $email, $telephone, $cin, $adresse, 
            $sexe, $age, $poste, $fonction, $date_embauche, 
            $photo_path, $secretaire_id
        );

        if ($stmt->execute()) {
            $success_message = "Votre profil a été mis à jour avec succès.";
        } else {
            $errors[] = "Une erreur s'est produite. Veuillez réessayer.";
        }
    }
}

// Récupération des informations du secrétaire
$stmt = $conn->prepare("SELECT * FROM employes WHERE id = ?");
$stmt->bind_param("i", $secretaire_id);
$stmt->execute();
$result = $stmt->get_result();
$secretaire = $result->fetch_assoc();

if (!$secretaire) {
    die("Impossible de récupérer les informations.");
}

// Chemin de l'image de profil
$image_path = !empty($secretaire['photo_profil']) 
    ? htmlspecialchars($secretaire['photo_profil']) 
    : "images/default-profile.png";

// Récupération du nom complet
$nom_complet = htmlspecialchars($secretaire['prenom'] . " " . $secretaire['nom']);

// 🔴 Récupérer le nombre de notifications non vues
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Secrétaire</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles.css"> <!-- Supposant que vous externalisez le CSS -->
    <?php if (!empty($errors)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php foreach ($errors as $error): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: <?php echo json_encode($error); ?>
                });
            <?php endforeach; ?>
        });
    </script>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: <?php echo json_encode($success_message); ?>
            });
        });
    </script>
<?php endif; ?>

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
                <li><a href="#" class="logout-btn" id="logout-btn"><i class="fa fa-sign-out"></i><span>Se déconnecter</span></a></li>
            </ul>
        </nav>

     

            <div class="profile-container">
    <div class="profile-header">
        <div class="profile-picture">
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Photo de profil">
        </div>
        <div class="profile-name">
            <h1><?php echo htmlspecialchars($secretaire['prenom'] . ' ' . $secretaire['nom']); ?></h1>
        </div>
    </div>
    <div class="profile-info">
        <p><strong>Email :</strong> <?php echo htmlspecialchars($secretaire['email']); ?></p>
        <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($secretaire['telephone']); ?></p>
        <p><strong>CIN :</strong> <?php echo htmlspecialchars($secretaire['cin']); ?></p>
        <p><strong>Adresse :</strong> <?php echo htmlspecialchars($secretaire['adresse']); ?></p>
        <p><strong>Sexe :</strong> <?php echo htmlspecialchars($secretaire['sexe']); ?></p>
        <p><strong>Âge :</strong> <?php echo htmlspecialchars($secretaire['age']); ?> ans</p>
        <p><strong>Poste :</strong> <?php echo htmlspecialchars($secretaire['poste']); ?></p>
        <p><strong>Fonction :</strong> <?php echo htmlspecialchars($secretaire['fonction']); ?></p>
        <p><strong>Date d'embauche :</strong> <?php echo htmlspecialchars($secretaire['date_embauche']); ?></p>
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
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($secretaire['nom']); ?>" required>
        </div>
        <div>
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($secretaire['prenom']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($secretaire['email']); ?>" required>
        </div>
        <div>
            <label for="telephone">Téléphone</label>
            <input type="number" id="telephone" name="telephone" value="<?php echo htmlspecialchars($secretaire['telephone']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="cin">CIN</label>
            <input type="text" id="cin" name="cin" value="<?php echo htmlspecialchars($secretaire['cin']); ?>" required>
        </div>
        <div>
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($secretaire['adresse']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="sexe">Sexe</label>
            <select id="sexe" name="sexe" required>
                <option value="Homme" <?php if($secretaire['sexe'] == 'Homme') echo 'selected'; ?>>Homme</option>
                <option value="Femme" <?php if($secretaire['sexe'] == 'Femme') echo 'selected'; ?>>Femme</option>
            </select>
        </div>
        <div>
            <label for="age">Âge</label>
            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($secretaire['age']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="poste">Poste</label>
            <input type="text" id="poste" name="poste" value="<?php echo htmlspecialchars($secretaire['poste']); ?>" disabled>
        </div>
        <div>
    <label for="fonction">Fonction</label>
    <input type="text" id="fonction" name="fonction" value="<?php echo htmlspecialchars($secretaire['fonction']); ?>" disabled>
</div>

    </div>

    <div class="input-group">
    <div>
    <label for="date_embauche">Date d'embauche</label>
    <input type="date" id="date_embauche" name="date_embauche" value="<?php echo htmlspecialchars($secretaire['date_embauche']); ?>" disabled>
</div>


        <div>
            <label for="photo_profil">Photo de profil</label>
            <input type="file" id="photo_profil" name="photo_profil">
            <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($secretaire['photo_profil']); ?>">
        </div>
    </div>
    <button type="submit" class="btn">Mettre à jour</button>
</form>
</div>
</div>

<script>
   function openModal() {
    document.getElementById('modal').classList.add('show');
    document.querySelector('.modal-content').classList.add('show');
}

function closeModal() {
    document.getElementById('modal').classList.remove('show');
    document.querySelector('.modal-content').classList.remove('show');
}

<!-- Script pour la confirmation de déconnexion et la gestion des notifications -->

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
        ```css
/* CSS de base */
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

/* Fond de page avec effet de flou */
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

/* Container du profil */
.profile-container {
    flex: 1;
    padding: 1.5rem;
    background-color: rgba(255, 255, 255, 0.85);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-md);
    margin: 1.5rem auto;
    max-width: 900px;
    width: 90%;
    border: 1px solid rgba(0, 86, 179, 0.1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    position: relative;
    overflow: hidden;
    animation: fadeIn 0.8s ease forwards;
}

/* Cercles décoratifs */
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

/* Header du profil */
.profile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    margin: -2rem -2rem 1rem -2rem;
    padding: 2rem 1.5rem 1.25rem;
    border-bottom: none;
    position: relative;
    border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
    box-shadow: var(--shadow-sm);
    color: var(--white);
}

.profile-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 50px;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.9), transparent);
    border-radius: 0 0 50% 50%;
}

/* Photo de profil */
.profile-picture {
    width: 100px;
    height: 100px;
    border-radius: var(--border-radius-full);
   
 
   
    box-shadow: var(--shadow-md);
   
    margin-bottom: 1rem;
    position: relative;
   

}

.profile-picture:hover {
    transform: scale(1.05) translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
}

.profile-picture img {
    width: 100%;
    height: 100%;
     border-radius: var(--border-radius-full);
    border: none;
   
    transition: all 0.4s ease;
}

.profile-picture:hover img {
    transform: scale(1.1);
}

/* Nom du profil */
.profile-name h1 {
    font-size: 2rem;
    color: var(--white);
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: 0.5px;
}

/* Informations du profil */
.profile-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 0.5rem;
    position: relative;
    z-index: 1;
}

.profile-info div {
    margin: 0;
    padding: 0.75rem 1rem;
    background-color: rgba(255, 255, 255, 0.9);
    border-left: 3px solid var(--primary-light);
    border-radius: var(--border-radius-md);
    box-shadow: 
        5px 5px 15px rgba(0, 0, 0, 0.05), 
        -5px -5px 15px rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.7);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
    animation: slideInUp 0.5s ease forwards;
    animation-delay: calc(var(--i, 0) * 0.1s);
}

.profile-info div::before {
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

.profile-info div:hover {
    transform: translateY(-8px);
    box-shadow: 
        8px 8px 20px rgba(0, 0, 0, 0.07), 
        -8px -8px 20px rgba(255, 255, 255, 0.8);
    border-color: rgba(0, 128, 255, 0.2);
    background-color: var(--white);
}

.profile-info div:hover::before {
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

/* Footer du profil */
.profile-footer {
    text-align: center;
    margin-top: 2.5rem;
    position: relative;
    z-index: 1;
}

/* Bouton */
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
<style> 

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
    /* Variables CSS communes */
    :root {
        --primary-color: #0056b3;
        --primary-light: #007bff;
        --secondary-color: #26d0ce;
        --danger-color: #ff3e55;
        --success-color: #2ecc71;
        --warning-color: #f39c12;
        --info-color: #9b59b6;
        --text-color: #333;
        --text-light: #666;
        --bg-color: #f0f2f5;
        --card-bg: #fff;
        --sidebar-bg: linear-gradient(180deg, #1a2980 0%, #26d0ce 100%);
        --header-bg: linear-gradient(135deg, #0056b3 0%, #007bff 100%);
        --border-radius: 12px;
        --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }

    /* Reset et styles de base */
    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }

    body {
        background: var(--bg-color);
        min-height: 100vh;
        color: var(--text-color);
    }

    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 30px;
        background: var(--header-bg);
        color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 10;
    }

    .menu-icon {
        font-size: 24px;
        color: white;
        cursor: pointer;
        transition: var(--transition);
    }

    .menu-icon:hover {
        transform: scale(1.1);
    }

    .u-name {
        font-size: 22px;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .u-name b {
        font-weight: 800;
        color: #ffe100;
    }

    /* Contenu principal */
    .body {
        display: flex;
        position: relative;
        min-height: calc(100vh - 70px);
    }

    /* Sidebar */
    .side-bar {
        width: 250px;
        background: var(--sidebar-bg);
        color: white;
        transition: var(--transition);
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        z-index: 5;
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
        transition: var(--transition);
    }

    .side-bar ul {
        list-style: none;
        padding: 0 15px;
    }

    .side-bar ul li {
        margin: 10px 0;
        border-radius: 8px;
        overflow: hidden;
        transition: var(--transition);
    }

    .side-bar ul li a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 12px 15px;
        font-size: 16px;
        transition: var(--transition);
        font-weight: 500;
    }

    .side-bar ul li a i {
        margin-right: 15px;
        font-size: 18px;
        width: 22px;
        text-align: center;
    }

    /* Contenu */
    .content {
        flex: 1;
        padding: 30px;
        transition: var(--transition);
        background: var(--bg-color);
        position: relative;
    }


    .info-item strong {
        display: block;
        color: var(--primary-color);
        margin-bottom: 5px;
        font-size: 14px;
    }

    .info-item p {
        font-size: 16px;
        color: var(--text-color);
    }

    .profile-actions {
        margin-top: 30px;
        text-align: center;
    }

    /* Bouton */
    .btn {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: var(--box-shadow);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 86, 179, 0.2);
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .profile-info {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-picture {
            margin-right: 0;
            margin-bottom: 20px;
        }
        
        .side-bar {
            width: 100%;
            height: auto;
            position: fixed;
            top: 70px;
            left: -100%;
            z-index: 100;
        }
        
        #checkbox:checked ~ .body .side-bar {
            left: 0;
        }
        
        .content {
            margin-left: 0;
            padding: 20px;
            margin-top: 70px;
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
    ```css
/* CSS de base */
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

/* Fond de page avec effet de flou */
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

/* Container du profil */
.profile-container {
    flex: 1;
    padding: 1.5rem;
    background-color: rgba(255, 255, 255, 0.85);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-md);
    margin: 1.5rem auto;
    max-width: 900px;
    width: 90%;
    border: 1px solid rgba(0, 86, 179, 0.1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    position: relative;
    overflow: hidden;
    animation: fadeIn 0.8s ease forwards;
}

/* Cercles décoratifs */
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

/* Header du profil */
.profile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    margin: -2rem -2rem 1rem -2rem;
    padding: 2rem 1.5rem 1.25rem;
    border-bottom: none;
    position: relative;
    border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
    box-shadow: var(--shadow-sm);
    color: var(--white);
}

.profile-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 50px;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.9), transparent);
    border-radius: 0 0 50% 50%;
}

.profile-info div {
    margin: 0;
    padding: 0.75rem 1rem;
    background-color: rgba(255, 255, 255, 0.9);
    border-left: 3px solid var(--primary-light);
    border-radius: var(--border-radius-md);
    box-shadow: 
        5px 5px 15px rgba(0, 0, 0, 0.05), 
        -5px -5px 15px rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.7);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
    animation: slideInUp 0.5s ease forwards;
    animation-delay: calc(var(--i, 0) * 0.1s);
}

.profile-info div::before {
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

.profile-info div:hover {
    transform: translateY(-8px);
    box-shadow: 
        8px 8px 20px rgba(0, 0, 0, 0.07), 
        -8px -8px 20px rgba(255, 255, 255, 0.8);
    border-color: rgba(0, 128, 255, 0.2);
    background-color: var(--white);
}

.profile-info div:hover::before {
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

/* Footer du profil */
.profile-footer {
    text-align: center;
    margin-top: 2.5rem;
    position: relative;
    z-index: 1;
}

/* Bouton */
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

    </style>