<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'admin_layout.php';
$admin_id = $_SESSION['user_id'];

// Traitement de la mise à jour
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_SESSION['user_id'];

    // Récupération des champs non modifiables
    $stmt_current = $conn->prepare("SELECT poste, fonction, date_embauche FROM employes WHERE id = ?");
    $stmt_current->bind_param("i", $admin_id);
    $stmt_current->execute();
    $result = $stmt_current->get_result();
    $currentData = $result->fetch_assoc();

    // Champs modifiables
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $cin = trim($_POST['cin']);
    $adresse = trim($_POST['adresse']);
    $sexe = $_POST['sexe'];
    $age = intval($_POST['age']);

    // Contrôles de saisie
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/", $nom)) {
        $error = "Le nom ne doit contenir que des lettres.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/", $prenom)) {
        $error = "Le prénom ne doit contenir que des lettres.";
    } elseif (!preg_match("/^\d{8}$/", $cin)) {
        $error = "Le CIN doit contenir exactement 8 chiffres.";
    } elseif (!preg_match("/^\d{8}$/", $telephone)) {
        $error = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'email n'est pas valide.";
    } elseif ($age < 18 || $age > 65) {
        $error = "L'âge doit être compris entre 18 et 65 ans.";
    } else {
        // Vérifier unicité du CIN
        $stmt = $conn->prepare("SELECT id FROM employes WHERE cin = ? AND id != ?");
        $stmt->bind_param("si", $cin, $admin_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Ce CIN est déjà utilisé.";
        }

        // Vérifier unicité téléphone
        $stmt = $conn->prepare("SELECT id FROM employes WHERE telephone = ? AND id != ?");
        $stmt->bind_param("si", $telephone, $admin_id);
        $stmt->execute();
        if (empty($error) && $stmt->get_result()->num_rows > 0) {
            $error = "Ce numéro de téléphone est déjà utilisé.";
        }

        // Vérifier unicité email
        $stmt = $conn->prepare("SELECT id FROM employes WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        if (empty($error) && $stmt->get_result()->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        }
    }

    // Affichage de l'erreur si elle existe
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
        // Champs non modifiables
        $poste = $currentData['poste'];
        $fonction = $currentData['fonction'];
        $date_embauche = $currentData['date_embauche'];

        // Photo de profil
        $photo_profil = $_FILES['photo_profil']['name'];
        if ($photo_profil) {
            $photo_tmp = $_FILES['photo_profil']['tmp_name'];
            $photo_path = 'uploads/' . $photo_profil;
            move_uploaded_file($photo_tmp, $photo_path);
        } else {
            $photo_path = $_POST['current_photo'];
        }

        // Mise à jour
        $sql = "UPDATE employes SET nom = ?, prenom = ?, email = ?, telephone = ?, cin = ?, adresse = ?, sexe = ?, age = ?, poste = ?, fonction = ?, date_embauche = ?, photo_profil = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssi", $nom, $prenom, $email, $telephone, $cin, $adresse, $sexe, $age, $poste, $fonction, $date_embauche, $photo_path, $admin_id);

        if ($stmt->execute()) {
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
    }
}



$stmt = $conn->prepare("SELECT * FROM employes WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    die("Impossible de récupérer les informations.");
}

$image_path = !empty($admin['photo_profil']) ? htmlspecialchars($admin['photo_profil']) : "images/default-profile.png";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Profil Administrateur</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.js"></script>
    <style>/* CSS pour la page de profil */

    </style>
</head>
<body>
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-picture">
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Photo de profil">
        </div>
        <div class="profile-name">
            <h1><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></h1>
        </div>
    </div>
    <div class="profile-info">
        <div><strong>Email :</strong> <?php echo htmlspecialchars($admin['email']); ?></div>
        <div><strong>Téléphone :</strong> <?php echo htmlspecialchars($admin['telephone']); ?></div>
        <div><strong>CIN :</strong> <?php echo htmlspecialchars($admin['cin']); ?></div>
        <div><strong>Adresse :</strong> <?php echo htmlspecialchars($admin['adresse']); ?></div>
        <div><strong>Sexe :</strong> <?php echo htmlspecialchars($admin['sexe']); ?></div>
        <div><strong>Âge :</strong> <?php echo htmlspecialchars($admin['age']); ?></div>
        <div><strong>Poste :</strong> <?php echo htmlspecialchars($admin['poste']); ?></div>
        <div><strong>Fonction :</strong> <?php echo htmlspecialchars($admin['fonction']); ?></div>
        <div><strong>Date d'embauche :</strong> <?php echo htmlspecialchars($admin['date_embauche']); ?></div>
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
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($admin['nom']); ?>" required>
        </div>
        <div>
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($admin['prenom']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
        </div>
        <div>
            <label for="telephone">Téléphone</label>
            <input type="number" id="telephone" name="telephone" value="<?php echo htmlspecialchars($admin['telephone']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="cin">CIN</label>
            <input type="text" id="cin" name="cin" value="<?php echo htmlspecialchars($admin['cin']); ?>" required>
        </div>
        <div>
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($admin['adresse']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="sexe">Sexe</label>
            <select id="sexe" name="sexe" required>
                <option value="Homme" <?php if($admin['sexe'] == 'Homme') echo 'selected'; ?>>Homme</option>
                <option value="Femme" <?php if($admin['sexe'] == 'Femme') echo 'selected'; ?>>Femme</option>
            </select>
        </div>
        <div>
            <label for="age">Âge</label>
            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($admin['age']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="poste">Poste</label>
            <input type="text" id="poste" name="poste" value="<?php echo htmlspecialchars($admin['poste']); ?>" required>
        </div>
        <div>
            <label for="fonction">Fonction</label>
            <input type="text" id="fonction" name="fonction" value="<?php echo htmlspecialchars($admin['fonction']); ?>" required>
        </div>
    </div>

    <div class="input-group">
        <div>
            <label for="date_embauche">Date d'embauche</label>
            <input type="date" id="date_embauche" name="date_embauche" value="<?php echo htmlspecialchars($admin['date_embauche']); ?>" readonly>
        </div>
        <div>
            <label for="photo_profil">Photo de profil</label>
            <input type="file" id="photo_profil" name="photo_profil">
            <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($admin['photo_profil']); ?>">
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


</script>

</body>
</html>
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
    border: 5px solid;
    border-image-slice: 1;
   
    box-shadow: var(--shadow-md);
   
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
 
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
```
    </style>