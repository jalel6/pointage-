<?php
session_start();
include 'db.php'; // Connexion à la base de données

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['employé', 'secrétaire','admin'])) {
    header('Location: index.php');
    exit;
}



$employe_id = $_SESSION['user_id'];
$message = "";

// Déterminer le bon lien de dashboard selon le rôle
$dashboardLink = '#'; // valeur par défaut
switch ($_SESSION['user_role']) {
    case 'admin':
        $dashboardLink = 'admin_dashboard.php';
        break;
    case 'secrétaire':
        $dashboardLink = 'secretary_dashboard.php';
        break;
    case 'employé':
        $dashboardLink = 'employee_dashboard.php';
        break;
}

// Récupérer les pointages du jour
$stmt = $conn->prepare("SELECT * FROM pointages WHERE employe_id = ? AND DATE(heure_arrivee) = CURDATE() ORDER BY heure_arrivee ASC");
$stmt->bind_param("i", $employe_id);
$stmt->execute();
$result = $stmt->get_result();
$pointages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Déterminer l'état actuel en fonction des pointages existants
$etat_pointage = "entree_matin"; // Par défaut, l'état est "entrée matin" si aucun pointage n'a été effectué.
if (count($pointages) > 0) {
    // Récupérer le dernier pointage
    $dernier_pointage = $pointages[count($pointages) - 1];
    
    if ($dernier_pointage['statut'] == 'arrivé_matin' && !$dernier_pointage['heure_depart']) {
        $etat_pointage = "sortie_matin";
    } elseif ($dernier_pointage['statut'] == 'parti_matin') {
        $etat_pointage = "entree_soir";
    } elseif ($dernier_pointage['statut'] == 'arrivé_soir' && !$dernier_pointage['heure_depart']) {
        $etat_pointage = "sortie_soir";
    } elseif ($dernier_pointage['statut'] == 'parti_soir') {
        $etat_pointage = "complet";
    }
}

// Sauvegarder l'état du pointage dans la session pour persister après la reconnexion
$_SESSION['etat_pointage'] = $etat_pointage;

// Traitement du pointage
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['pointage']) && isset($_POST['image'])) {
        // Pointage selon l'étape
        $pointage_type = $_POST['pointage']; // 'arrivee_matin', 'depart_matin', 'arrivee_soir', 'depart_soir'

        // Capture et insertion de l'image
        $image = base64_decode(str_replace('data:image/png;base64,', '', $_POST['image']));
        $image_name = 'photo_' . time() . '.png';
        file_put_contents('uploads/' . $image_name, $image);

        // Mise à jour de l'heure selon le type de pointage
        if ($pointage_type == 'arrivee_matin') {
            $stmt = $conn->prepare("INSERT INTO pointages (employe_id, heure_arrivee, statut, photo) VALUES (?, NOW(), 'arrivé_matin', ?)");
            $stmt->bind_param("is", $employe_id, $image_name);
            $stmt->execute();
            $message = "Arrivée du matin enregistrée avec succès !";
            $etat_pointage = "sortie_matin";
        } elseif ($pointage_type == 'depart_matin') {
            $stmt = $conn->prepare("UPDATE pointages SET heure_depart = NOW(), statut = 'parti_matin' WHERE employe_id = ? AND heure_depart IS NULL AND statut = 'arrivé_matin' AND DATE(heure_arrivee) = CURDATE() ORDER BY heure_arrivee DESC LIMIT 1");
            $stmt->bind_param("i", $employe_id);
            $stmt->execute();
            $message = "Départ du matin enregistré avec succès !";
            $etat_pointage = "entree_soir";
        } elseif ($pointage_type == 'arrivee_soir') {
            $stmt = $conn->prepare("INSERT INTO pointages (employe_id, heure_arrivee, statut, photo) VALUES (?, NOW(), 'arrivé_soir', ?)");
            $stmt->bind_param("is", $employe_id, $image_name);
            $stmt->execute();
            $message = "Arrivée du soir enregistrée avec succès !";
            $etat_pointage = "sortie_soir";
        } elseif ($pointage_type == 'depart_soir') {
            $stmt = $conn->prepare("UPDATE pointages SET heure_depart = NOW(), statut = 'parti_soir' WHERE employe_id = ? AND heure_depart IS NULL AND statut = 'arrivé_soir' AND DATE(heure_arrivee) = CURDATE() ORDER BY heure_arrivee DESC LIMIT 1");
            $stmt->bind_param("i", $employe_id);
            $stmt->execute();
            $message = "Départ du soir enregistré avec succès !";
            $etat_pointage = "complet";
        }

        // Sauvegarder l'état du pointage après l'action
        $_SESSION['etat_pointage'] = $etat_pointage;

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pointage</title>
    <link rel="stylesheet" href="point.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<style>
    /* Bouton "Retour" */
.back-btn {
    position: fixed; /* Utilise fixed pour le garder en haut à gauche même lors du défilement */
    top: 20px;
    left: 20px;
    padding: 10px 15px;
    background: #0056b3;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: background-color 0.3s ease, transform 0.3s ease;
    font-size: 14px;
    width: auto;
    z-index: 10; /* Assure que le bouton est au-dessus du contenu */
}

.back-btn i {
    margin-right: 8px;
}

/* Effet au survol du bouton */
.back-btn:hover {
    background-color: #055085; /* Un bleu légèrement plus foncé pour l'effet hover */
    transform: translateY(-3px);
}

button {
    background-color: #083b71; /* Un bleu plus moderne et plus doux */
    color: white;
    border: none;
    padding: 14px 35px;
    border-radius: 50px; /* Arrondir davantage pour un look plus moderne */
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.4s ease;
    width: 100%;
    font-weight: 500;
}

button:hover {
    background-color: #0052a3;
    transform: scale(1.05); /* Ajouter un effet de mise en valeur au survol */
}
</style>
<body>

<a href="<?php echo $dashboardLink; ?>" class="back-btn">
    <i class="fa fa-arrow-left"></i> Dashboard
</a>

<div class="container">
    <h1>Se Pointer</h1>

    <div class="video-container">
        <video id="video" width="100%" height="240" autoplay></video>
        <canvas id="canvas" width="400" height="240" style="display: none;"></canvas>
    </div>

    <!-- Formulaire de pointage -->
    <form method="post" id="pointageForm">
        <input type="hidden" name="image" id="imageInput">
        <input type="hidden" name="pointage" id="pointageType">

        <!-- Affichage dynamique des boutons -->
        <?php if ($etat_pointage === 'entree_matin'): ?>
            <div class="form-group">
                <button type="button" onclick="capture('arrivee_matin')">Pointer l'arrivée du matin</button>
            </div>
        <?php elseif ($etat_pointage === 'sortie_matin'): ?>
            <div class="form-group">
                <button type="submit" name="pointage" value="depart_matin">Pointer le départ du matin</button>
            </div>
        <?php elseif ($etat_pointage === 'entree_soir'): ?>
            <div class="form-group">
                <button type="button" onclick="capture('arrivee_soir')">Pointer l'arrivée du soir</button>
            </div>
        <?php elseif ($etat_pointage === 'sortie_soir'): ?>
            <div class="form-group">
                <button type="submit" name="pointage" value="depart_soir">Pointer le départ du soir</button>
            </div>
        <?php endif; ?>

    </form>

    <!-- Message -->
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <!-- Statut -->
    <div class="message">
        <?php
        switch ($etat_pointage) {
            case 'entree_matin': echo "Veuillez pointer votre arrivée du matin."; break;
            case 'sortie_matin': echo "Veuillez pointer votre départ du matin."; break;
            case 'entree_soir': echo "Veuillez pointer votre arrivée du soir."; break;
            case 'sortie_soir': echo "Veuillez pointer votre départ du soir."; break;
            case 'complet': echo "<span style='color: green;'>Vous avez terminé tous vos pointages pour aujourd'hui.</span>"; break;
        }
        ?>
    </div>
</div>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    let stream;

    window.addEventListener("DOMContentLoaded", () => {
        if (<?= json_encode($etat_pointage === 'entree_matin' || $etat_pointage === 'entree_soir') ?>) {
            startCamera();
        }
    });

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(s => {
                stream = s;
                video.srcObject = stream;
            })
            .catch(error => {
                console.error("Erreur d'accès à la caméra :", error);
            });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            video.srcObject = null;
        }
    }

    function capture(pointageType) {
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/png');

        stopCamera();

        document.getElementById('imageInput').value = dataUrl;
        document.getElementById('pointageType').value = pointageType;
        document.getElementById('pointageForm').submit();
    }
</script>

</body>
</html>
