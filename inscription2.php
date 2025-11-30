<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté et a un rôle d'admin ou de secrétaire
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'secrétaire'])) {
    header("Location: index.php"); // Rediriger vers la page d'accueil si l'utilisateur n'est pas admin ou secrétaire
    exit(); // Terminer l'exécution du script
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['fonction'] = htmlspecialchars($_POST['fonction']);
    $_SESSION['poste'] = htmlspecialchars($_POST['poste']);
    $_SESSION['date_embauche'] = $_POST['date_embauche'];

    header('Location: inscription3.php');
    exit();
}

$sql_get_postes = "SELECT DISTINCT poste FROM soldeconge";
$result_postes = $conn->query($sql_get_postes);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étape 2 - Inscription</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="inscri2.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <form action="" method="post" onsubmit="return validerFormulaire()">
            <h2>Informations professionnelles</h2>
            
            <div class="progress-steps">
                <div class="step active">1</div>
                <div class="step active">2</div>
                <div class="step">3</div>
            </div>
            
            <div class="input-wrapper">
                <label for="fonction"><i class="fas fa-briefcase"></i> Fonction :</label>
                <div class="input-wrapper">
                    <select name="fonction" id="fonction" required>
                        <option value="" disabled selected>Choisir une fonction</option>
                        <?php
                        // Vérifier le rôle de l'utilisateur pour afficher les options
                        if ($_SESSION['user_role'] == 'admin') {
                            echo '<option value="Secrétaire">Secrétaire</option>';
                        }
                        echo '<option value="Employé">Employé</option>';
                        ?>
                    </select>
                </div>
            </div>

            <div class="input-wrapper">
                <label for="poste"><i class="fas fa-user-tie"></i> Poste :</label>
                <div class="input-wrapper">
             
                    <select name="poste" id="poste" required>
                        <option value="" disabled selected>Choisir un poste</option>
                        <?php
                        while ($row = $result_postes->fetch_assoc()) {
                            $poste = htmlspecialchars($row['poste']);
                            echo "<option value='$poste'>$poste</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="input-wrapper">
                <label for="date_embauche"><i class="fas fa-calendar-alt"></i> Date d'embauche :</label>
                <div class="input-wrapper">
                    <input type="date" id="date_embauche" name="date_embauche" required max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="button-group">
                <button type="button" onclick="history.back()"><i class="fas fa-arrow-left"></i> Retour</button>
                <button type="submit"><i class="fas fa-arrow-right"></i> Suivant</button>
            </div>
        </form>
    </div>

    <script>
    function validerFormulaire() {
        const dateEmbauche = document.getElementById("date_embauche").value;
        const today = new Date().toISOString().split('T')[0];

        if (dateEmbauche > today) {
            Swal.fire({
                icon: 'error',
                title: 'Date invalide',
                text: "La date d'embauche ne peut pas être dans le futur."
            });
            return false;
        }

        return true;
    }
    </script>
</body>
</html>
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
select, input[type="date"] {
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
select:focus, input[type="date"]:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(57, 132, 255, 0.1);
    outline: none;
    transform: translateY(-2px);
}

/* Style pour le select */
select {
    appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%23083b71" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>');
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 15px;
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

/* Responsive */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 20px;
    }
    
    h2 {
        font-size: 24px;
    }
    
    select, input[type="date"] {
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

/* Validation visuelle */
select:valid, input[type="date"]:valid {
    border-color: #ddd;
}

select:invalid:not(:focus), input[type="date"]:invalid:not(:focus) {
    border-color: var(--error-color);
}

/* Message d'erreur visuel */
.input-error-message {
    color: var(--error-color);
    font-size: 12px;
    margin-top: -15px;
    margin-bottom: 15px;
    display: none;
}

select:invalid:not(:focus) ~ .input-error-message,
input[type="date"]:invalid:not(:focus) ~ .input-error-message {
    display: block;
    animation: shakeError 0.6s;
}

@keyframes shakeError {
    0%, 100% {transform: translateX(0);}
    20%, 60% {transform: translateX(-5px);}
    40%, 80% {transform: translateX(5px);}
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
</style>