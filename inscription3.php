<?php
session_start();
include 'db.php';

// Vérifier si l'utilisateur est connecté et a un rôle d'admin ou de secrétaire
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'secrétaire'])) {
    header("Location: index.php"); // Rediriger vers la page d'accueil si l'utilisateur n'est pas admin ou secrétaire
    exit(); // Terminer l'exécution du script
}

$dashboardLink = ($_SESSION['user_role'] === 'admin') ? 'admin_dashboard.php' : 'secretary_dashboard.php';
$success = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = mysqli_real_escape_string($conn, $_SESSION['nom']);
    $prenom = mysqli_real_escape_string($conn, $_SESSION['prenom']);
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $telephone = mysqli_real_escape_string($conn, $_SESSION['telephone']);
    $cin = mysqli_real_escape_string($conn, $_SESSION['cin']);
    $adresse = mysqli_real_escape_string($conn, $_SESSION['adresse']);
    $sexe = mysqli_real_escape_string($conn, $_SESSION['sexe']);
    $age = mysqli_real_escape_string($conn, $_SESSION['age']);
    $fonction = mysqli_real_escape_string($conn, $_SESSION['fonction']);
    $poste = mysqli_real_escape_string($conn, $_SESSION['poste']);
    $date_embauche = mysqli_real_escape_string($conn, $_SESSION['date_embauche']);
    $login = mysqli_real_escape_string($conn, $_POST['login']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Validation du mot de passe (min 6 caractères, 1 chiffre, 1 majuscule)
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
        $errorMessage = "Le mot de passe doit contenir au moins 6 caractères, une majuscule et un chiffre.";
    } else {
        $password_hashed = md5($password);

        // Vérifier l'unicité du login
        $check_sql = "SELECT * FROM employes WHERE login='$login'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $errorMessage = "Ce nom d'utilisateur existe déjà.";
        } else {
            // Gérer l'image
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $photo_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($_FILES["photo_profil"]["name"]));
            $photo_path = $target_dir . time() . "_" . $photo_name;

            if (move_uploaded_file($_FILES["photo_profil"]["tmp_name"], $photo_path)) {
                $photo_path = mysqli_real_escape_string($conn, $photo_path);

                $sql = "INSERT INTO employes (nom, prenom, email, telephone, cin, adresse, sexe, age, fonction, poste, date_embauche, login, password, photo_profil) 
                        VALUES ('$nom', '$prenom', '$email', '$telephone', '$cin', '$adresse', '$sexe', '$age', '$fonction', '$poste', '$date_embauche', '$login', '$password_hashed', '$photo_path')";

                if ($conn->query($sql) === TRUE) {
                    $success = true;
                    
                    // Nettoyer les variables de session après succès
                    unset($_SESSION['nom']);
                    unset($_SESSION['prenom']);
                    unset($_SESSION['email']);
                    unset($_SESSION['telephone']);
                    unset($_SESSION['cin']);
                    unset($_SESSION['adresse']);
                    unset($_SESSION['sexe']);
                    unset($_SESSION['age']);
                    unset($_SESSION['fonction']);
                    unset($_SESSION['poste']);
                    unset($_SESSION['date_embauche']);
                } else {
                    $errorMessage = "Erreur SQL: " . $conn->error;
                }
            } else {
                $errorMessage = "Erreur lors du téléchargement de la photo.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étape 3 - Finalisation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php if ($success): ?>
        <script>
            setTimeout(function() {
                Swal.fire({
                    title: 'Succès',
                    text: 'Employé ajouté avec succès.',
                    icon: 'success'
                }).then(() => {
                    window.location.href = 'liste_employes.php';
                });
            }, 100);
        </script>
    <?php elseif (!empty($errorMessage)): ?>
        <script>
            setTimeout(function() {
                Swal.fire({
                    title: 'Erreur',
                    text: <?= json_encode($errorMessage) ?>,
                    icon: 'error'
                });
            }, 100);
        </script>
    <?php endif; ?>

    <div class="container">
        <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
            <h2>Compte d'accès</h2>
            
            <div class="progress-steps">
                <div class="step active">1</div>
                <div class="step active">2</div>
                <div class="step active">3</div>
            </div>
            
            <div class="input-wrapper">
                <label for="login"><i class="fas fa-user"></i> Nom d'utilisateur :</label>
                <div class="input-wrapper">
                    <input type="text" name="login" id="login" 
                           placeholder="Nom d'utilisateur"
                           pattern="[a-zA-Z0-9]{4,}" 
                           title="Minimum 4 lettres ou chiffres" required>
                </div>
            </div>
            
            <div class="input-wrapper">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe :</label>
                <div class="input-wrapper">
                  
                    <input type="password" name="password" id="password"
                           placeholder="Mot de passe"
                           pattern="(?=.*[A-Z])(?=.*\d).{6,}" 
                           title="Minimum 6 caractères, incluant une majuscule et un chiffre"
                           required>
                </div>
                <div class="password-requirements">
                    <small><i class="fas fa-info-circle"></i> Minimum 6 caractères, 1 majuscule et 1 chiffre</small>
                </div>
            </div>
            
            <div class="input-wrapper">
                <label for="photo_profil"><i class="fas fa-image"></i> Photo de profil :</label>
                <div class="input-wrapper file-input-wrapper">
                    <input type="file" name="photo_profil" id="photo_profil" 
                           accept="image/*" required>
                    <div class="file-preview" id="imagePreview"></div>
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" onclick="history.back()"><i class="fas fa-arrow-left"></i> Retour</button>
                <button type="submit"><i class="fas fa-check"></i> Enregistrer</button>
            </div>
        </form>
    </div>

    <script>
    // Prévisualisation de l'image
    document.getElementById('photo_profil').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.createElement('img');
                img.src = event.target.result;
                img.className = 'preview-image';
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    });
    </script>

<style>
    /* Styles généraux et réinitialisation */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

:root {
    --primary-color: #083b71;
    --secondary-color: #3984ff;
    --accent-color: #5ce1e6;
    --text-color: #333;
    --bg-color: #f5f7fa;
    --card-bg: #ffffff;
    --error-color: #e74c3c;
    --success-color: #2ecc71;
    --input-bg: #f8f9fa;
    --shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

body {
    background: var(--bg-color);
    color: var(--text-color);
    line-height: 1.6;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding: 0;
    position: relative;
    background-image: linear-gradient(135deg, rgba(8, 59, 113, 0.03) 0%, rgba(92, 225, 230, 0.03) 100%);
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="%23083b7108" fill="none" stroke-width="1"/></svg>');
    opacity: 0.4;
    z-index: -1;
}

/* Conteneur principal */
.container {
    max-width: 500px;
    width: 90%;
    margin: 30px auto;
    padding: 30px;
    background: var(--card-bg);
    border-radius: 15px;
    box-shadow: var(--shadow);
    animation: fadeIn 0.6s ease;
    position: relative;
    overflow: hidden;
}

.container::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 30%;
    height: 5px;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    border-radius: 0 0 0 10px;
}

/* Titre */
h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 30px;
    font-size: 28px;
    font-weight: 600;
    position: relative;
    padding-bottom: 10px;
}

h2::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: 0;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    border-radius: 2px;
}

/* Style des labels avec icônes */
label {
    display: block;
    margin-bottom: 8px;
    color: var(--primary-color);
    font-weight: 500;
    font-size: 16px;
    position: relative;
    padding-left: 28px;
}

label i {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-color);
    font-size: 18px;
}

/* Style des champs de saisie */
input[type="text"], input[type="password"], input[type="file"] {
    width: 100%;
    padding: 12px 15px 12px 45px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: var(--input-bg);
    font-size: 16px;
    color: var(--text-color);
    transition: all 0.3s ease;
    position: relative;
}

/* Style spécial pour l'input file */
input[type="file"] {
    padding: 10px 15px 10px 45px;
}

/* Style pour les icônes dans les champs */
.input-wrapper {
    position: relative;
    margin-bottom: 20px;
}

.input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-color);
    font-size: 18px;
    z-index: 1;
    pointer-events: none;
}

/* Style pour le focus */
input[type="text"]:focus, input[type="password"]:focus, input[type="file"]:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(57, 132, 255, 0.1);
    outline: none;
    transform: translateY(-2px);
}

/* Prévisualisation d'image */
.file-preview {
    margin-top: 10px;
    min-height: 100px;
    border: 1px dashed #ddd;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.preview-image {
    max-width: 100%;
    max-height: 200px;
    display: block;
    margin: 0 auto;
}

/* Informations sur le mot de passe */
.password-requirements {
    margin-top: -15px;
    margin-bottom: 15px;
    color: var(--primary-color);
    font-size: 12px;
    opacity: 0.8;
}

/* Groupe de boutons */
.button-group {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 30px;
}

.button-group button {
    flex: 1;
    padding: 12px 15px;
    border: none;
    border-radius: 8px;
    background-color: var(--primary-color);
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-transform: uppercase;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.button-group button:first-child {
    background-color: #6c757d;
}

.button-group button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.button-group button:first-child:hover {
    background-color: #5a6268;
}

.button-group button:last-child:hover {
    background-color: var(--secondary-color);
}

.button-group button:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Progress steps */
.progress-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.step {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #e0e0e0;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 15px;
    position: relative;
}

.step.active {
    background-color: var(--primary-color);
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    width: 40px;
    height: 2px;
    background-color: #e0e0e0;
    top: 50%;
    left: 100%;
    transform: translateY(-50%);
}

.step.active:not(:last-child)::after {
    background-color: var(--primary-color);
}

/* Style pour le bouton dashboard */
.dashboard-link {
    position: absolute;
    top: 20px;
    left: 20px;
}

.back-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 8px;
    background-color: var(--primary-color);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-btn i {
    font-size: 14px;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    background-color: var(--secondary-color);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 20px;
    }
    
    h2 {
        font-size: 24px;
    }
    
    input[type="text"], input[type="password"], input[type="file"] {
        padding: 10px 15px 10px 40px;
        font-size: 14px;
    }
    
    .input-icon {
        font-size: 16px;
    }
    
    .button-group button {
        padding: 10px;
        font-size: 14px;
    }
}
</style>
</body>
</html>