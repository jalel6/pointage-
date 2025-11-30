<?php 
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['employé', 'secrétaire'])) {
    header('Location: index.php');
    exit;
}


$employe_id = $_SESSION['employe_id'];
// Déterminer la page de retour selon le rôle de l'utilisateur
$returnPage = ($_SESSION['user_role'] === 'secrétaire') ? 'secretary_dashboard.php' : 'employee_dashboard.php';

// Récupérer le poste de l'employé
$sqlPoste = "SELECT poste FROM employes WHERE id = ?";
$stmt = $conn->prepare($sqlPoste);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$poste = $row['poste'];

// Récupérer le solde autorisé annuel
$sqlSolde = "SELECT solde FROM soldeconge WHERE poste = ?";
$stmt = $conn->prepare($sqlSolde);
$stmt->bind_param("s", $poste);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$soldeTotal = $row['solde'];

// Calculer les jours déjà pris
$sqlPris = "SELECT SUM(DATEDIFF(dateFin, dateDebut) + 1) as joursPris 
            FROM conge 
            WHERE employe_id = ?";
$stmt = $conn->prepare($sqlPris);
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$joursPris = $row['joursPris'] ?? 0;

// Calcul du solde restant
$reste = $soldeTotal - $joursPris;

$alert_script = ""; // Pour injecter les alerts JS plus tard

// Vérification de la soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dateSoumission = date('Y-m-d');
    $dateDebut = $_POST['dateDebut'];
    $dateFin = $_POST['dateFin'];
    $statut = 'en attente';
    $type_conge = $_POST['type_conge'];
    $description = $_POST['description'];

    // Vérification des dates (ne pas permettre une date passée)
    if (strtotime($dateDebut) < strtotime($dateSoumission) || strtotime($dateFin) < strtotime($dateSoumission)) {
        $alert_script = "
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Dates invalides',
                    text: 'Les dates de congé ne peuvent pas être dans le passé.'
                }).then(() => {
                    window.location.href = 'demande_conge.php';
                });
            </script>";
    } elseif (strtotime($dateFin) <= strtotime($dateDebut)) {
        $alert_script = "
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Dates invalides',
                    text: 'La date de fin doit être supérieure à la date de début.'
                }).then(() => {
                    window.location.href = 'demande_conge.php';
                });
            </script>";
    } else {
        // Vérifier s'il y a déjà une demande en attente
        $sqlCheckPending = "SELECT COUNT(*) as nb FROM demandeconge WHERE employe_id = ? AND statut = 'en attente'";
        $stmt = $conn->prepare($sqlCheckPending);
        $stmt->bind_param("i", $employe_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['nb'] > 0) {
            $alert_script = "
                <script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Demande déjà en attente',
                        text: 'Vous avez déjà une demande de congé en attente.'
                    }).then(() => {
                        window.location.href = 'demande_conge.php';
                    });
                </script>";
        } else {
            $nbJoursDemandes = (strtotime($dateFin) - strtotime($dateDebut)) / (60 * 60 * 24) + 1;

            if ($nbJoursDemandes > $reste) {
                $alert_script = "
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Solde insuffisant',
                            text: 'Votre solde restant est de $reste jours. Vous avez demandé $nbJoursDemandes jours.'
                        }).then(() => {
                            window.location.href = 'demande_conge.php';
                        });
                    </script>";
            } else {
                $sql = "INSERT INTO demandeconge (employe_id, dateSoumission, dateDebut, dateFin, statut, type_conge, description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssss", $employe_id, $dateSoumission, $dateDebut, $dateFin, $statut, $type_conge, $description);
                if ($stmt->execute()) {
                    $alert_script = "
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Demande envoyée',
                                text: 'Votre demande de congé a été envoyée avec succès.'
                            }).then(() => {
                                window.location.href = 'demande_conge.php';
                            });
                        </script>";
                } else {
                    $alert_script = "
                        <script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: 'Une erreur s\\'est produite lors de l\\'envoi de la demande.'
                            });
                        </script>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de Congé</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</head>
<style>
   @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

/* Reset et base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ee 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
}

/* Container principal */
.container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0, 86, 179, 0.15);
    width: 95%;
    max-width: 1200px;
    display: flex;
    overflow: hidden;
    margin: 30px auto;
    position: relative;
    transition: all 0.3s ease;
   
    padding: 0; /* Évite les marges internes inutiles */
    margin: 15px auto; /* Réduit l'espacement autour */
}

.container:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 86, 179, 0.2);
}

/* Contenu du formulaire */
.form-content {
    flex: 1;
    padding: 50px 40px;
    position: relative;
    z-index: 1;
    padding: 20px 25px; /* Réduction du padding vertical */
}

.form-content h2 {
    color: #0056b3;
    font-weight: 600;
    position: relative;
    font-size: 22px;
    margin-bottom: 15px;
    padding-bottom: 5px;
    text-align: center; /* Ajout pour centrer le texte */
}


.form-content h2::after {
    content: '';
    position: absolute;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, #0056b3, #4da3ff);
    left: 50%;
    transform: translateX(-50%);
    bottom: 0;
    border-radius: 2px;
}


.form-content p {
    margin-bottom: 15px;
    color: #3a3a3a;
    font-size: 16px;
    line-height: 1.6;
}

/* Image content */
.image-content {
    flex: 1;
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #0056b3 0%, #1e88e5 100%);
}

.image-content img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.image-content:hover img {
    transform: scale(1.05);
}

/* Style des inputs */
.input-box {
    position: relative;
    margin-bottom: 12px;
}

.input-box label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
    font-size: 15px;
}

.input-box input,
.input-box select,
.input-box textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    color: #333;
    font-size: 14px;
    transition: all 0.3s ease;
}



.input-box input:focus,
.input-box select:focus,
.input-box textarea:focus {
    border-color: #0056b3;
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.15);
    background: #fff;
}

/* Style pour le bouton */
button[type="submit"] {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 8px;
    margin-top: 15px;
    background: linear-gradient(90deg, #0056b3, #4da3ff);
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    margin-top: 20px;
}

button[type="submit"]:hover {
    background: linear-gradient(90deg, #044a9f, #3994ff);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 86, 179, 0.4);
}

button[type="submit"]:active {
    transform: translateY(1px);
    box-shadow: 0 2px 10px rgba(0, 86, 179, 0.3);
}

/* Style pour les informations de solde */
strong {
    color: #0056b3;
    font-weight: 600;
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

.container {
    animation: fadeIn 0.5s ease-out forwards;
}

/* Responsive design */
@media (max-width: 992px) {
    .container {
        flex-direction: column;
    }
    
    .image-content {
        height: 250px;
        order: -1;
    }
    
    .form-content {
        padding: 40px 30px;
    }
}

@media (max-width: 576px) {
    .container {
        width: 100%;
        margin: 10px;
        border-radius: 15px;
    }
    
    .form-content {
        padding: 30px 20px;
    }
    
    .form-content h2 {
        font-size: 24px;
    }
    
    .back-btn {
        top: 10px;
        left: 10px;
        padding: 8px 15px;
        font-size: 13px;
    }
}

/* Style spécifique pour les dates */
input[type="date"] {
    position: relative;
    padding-right: 35px;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    filter: invert(40%) sepia(50%) saturate(800%) hue-rotate(190deg) brightness(90%) contrast(95%);
}

/* Style pour le select */
select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%230056b3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>');
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

/* Style pour le textarea */
textarea {
    resize: vertical;
    min-height: 120px;
}

/* Effet de focus pour les champs */
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: none;
}

/* Style pour le bouton de retour */
.back-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    padding: 10px 18px;
    background: linear-gradient(90deg, #0056b3, #1e88e5);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 14px;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
}

.back-btn i {
    margin-right: 6px;
}

.back-btn:hover {
    background: linear-gradient(90deg, #044a9f, #0d79da);
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}

.back-btn:active {
    transform: translateY(1px);
}

/* Animation pour le statut */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 86, 179, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(0, 86, 179, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 86, 179, 0);
    }
}

/* Effet sur le chargement de la page */
.container > * {
    opacity: 0;
    animation: fadeIn 0.5s ease-out forwards;
}

.form-content {
    animation-delay: 0.2s;
}

.image-content {
    animation-delay: 0.4s;
}

/* Effet de flottement pour le conteneur */
@keyframes float {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-5px);
    }
    100% {
        transform: translateY(0px);
    }
}

.container {
    animation: float 6s ease-in-out infinite;
}


</style>
<body>
<?= $alert_script; ?>

<a href="<?= $returnPage ?>" class="back-btn">
    <i class="fa fa-arrow-left"></i> Retour
</a>

<div class="container">
    <div class="form-content">
        <h2>Demande de Congé</h2>
        <p><strong>Solde annuel :</strong> <?php echo $soldeTotal; ?> jours</p>
        <p><strong>Solde restant :</strong> <?php echo $reste; ?> jours</p>

        <form action="demande_conge.php" method="POST">
            <div class="input-box">
                <label for="dateDebut">Date de début :</label>
                <input type="date" id="dateDebut" name="dateDebut" required>
            </div>

            <div class="input-box">
                <label for="dateFin">Date de fin :</label>
                <input type="date" id="dateFin" name="dateFin" required>
            </div>

            <div class="input-box">
                <label for="type_conge">Type de Congé :</label>
                <select id="type_conge" name="type_conge" required>
                    <option value="annuel">Annuel</option>
                    <option value="maladie">Maladie</option>
                    <option value="sans_solde">Sans Solde</option>
                </select>
            </div>

            <div class="input-box">
                <label for="description">Description :</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>

            <button type="submit">Envoyer la demande</button>
        </form>
    </div>
    <div class="image-content">
        <img src='images/de.jpg' alt="Image de congé" />
    </div>
</div>
</body>
</html>
