<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Ajouter une nouvelle période
if (!empty($_POST['nom_periode']) && !empty($_POST['date_debut']) && !empty($_POST['date_fin'])) {
    $nom = htmlspecialchars(trim($_POST['nom_periode']));
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];

    $stmt = $conn->prepare("SELECT id FROM periode WHERE nom_periode = ? AND date_debut = ? AND date_fin = ?");
    $stmt->bind_param("sss", $nom, $date_debut, $date_fin);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();

    if (!$check) {
        $stmt = $conn->prepare("INSERT INTO periode (nom_periode, date_debut, date_fin) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nom, $date_debut, $date_fin);
        $stmt->execute();

        header("Location: horaires_config.php?success=periode");
        exit();
    } else {
        $error = "Cette période existe déjà.";
    }
}

// Ajouter un horaire
if (!empty($_POST['id_periode']) && !empty($_POST['type']) && !empty($_POST['heure_debut']) && !empty($_POST['heure_fin']) && !empty($_POST['limite_retard'])) {
    $id_periode = intval($_POST['id_periode']);
    $type = $_POST['type'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $limite_retard = $_POST['limite_retard'];

    $stmt = $conn->prepare("SELECT id FROM horaire WHERE id_periode = ? AND type = ?");
    $stmt->bind_param("is", $id_periode, $type);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $error = "Un horaire pour le type '$type' existe déjà pour cette période.";
    } else {
        $stmt = $conn->prepare("INSERT INTO horaire (id_periode, type, heure_debut, heure_fin, limite_retard) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id_periode, $type, $heure_debut, $heure_fin, $limite_retard);
        $stmt->execute();

        header("Location: horaires_config.php?success=horaire");
        exit();
    }
}

// Suppression période
if (isset($_GET['delete_periode'])) {
    $id = intval($_GET['delete_periode']);
    $stmt = $conn->prepare("DELETE FROM periode WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: horaires_config.php");
    exit();
}

// Suppression horaire
if (isset($_GET['delete_horaire'])) {
    $id = intval($_GET['delete_horaire']);
    $stmt = $conn->prepare("DELETE FROM horaire WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: horaires_config.php");
    exit();
}

// Récupération des périodes
$periodes = $conn->query("SELECT * FROM periode ORDER BY date_debut DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration des Horaires</title>
    <link rel="stylesheet" href="heurs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>
<style>
      
* {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    /* Général */
    body {
        background-image: url('images/im3.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 90px 0;
    }

    /* Container principal */
    .container {
       background-color: white;
        width: 100%;
        max-width: 1100px;
        background-color: rgba(255, 255, 255, 0.95);
        padding: 40px 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        position: relative;
        border: 2px solid #007bff; /* Bordure ajoutée */
    }

    /* Titres */
    h2, h3 {
        text-align: center;
        color: #083b71;
    }
    h2 {
        font-weight: 600;
        font-size: 28px;
        letter-spacing: 1px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
        display: inline-block;
    }
    h3 {
        font-size: 1.6rem;
        margin-top: 40px;
    }

    /* Formulaires */
    .form-container {
        display: flex;
        justify-content: space-between;
        gap: 30px;
    }

    .form-section {
        background-color: #f9f9f9;
        padding: 30px;
        border: 2px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        flex: 1; /* Permet à chaque formulaire d'occuper une largeur égale */
        max-width: 500px;
    }

    input, select {
        width: 100%;
        padding: 12px 15px;
        margin-top: 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 1rem;
        background-color: #fefefe;
        transition: 0.2s;
    }

    input:focus, select:focus {
        border-color: #3498db;
        background-color: #fff;
        outline: none;
    }

    /* Boutons */
    button {
        background: #083b71;
        color: white;
        padding: 12px 25px;
        margin-top: 20px;
        font-size: 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s ease;
    }
    button:hover {
        background: #055085;
    }

    /* Messages d'erreur */
    .error {
        background-color: #fce4e4;
        color: #c0392b;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 6px;
    }

  

    /* Liste horaires */
    .periode-block ul {
        padding-left: 20px;
        margin-top: 10px;
    }
    .periode-block li {
        margin-bottom: 8px;
        list-style: disc;
    }

    

    /* Retour */
    .back-btn {
        position: fixed;
        top: 20px;
        left: 20px;
        background: #083b71;
        color: #fff;
        padding: 12px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        z-index: 999;
    }
    .back-btn:hover {
        background-color: #055085;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .container {
            padding: 20px;
        }

        .form-container {
            flex-direction: column;
        }

        .form-section {
            max-width: 100%;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 1.7rem;
        }

        h3 {
            font-size: 1.3rem;
        }

        input, select {
            font-size: 0.95rem;
        }

        button {
            width: 100%;
        }
    }
    .list-section {
        padding: 20px;
        background-color: #fdfdfd;
        border-radius: 10px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    
    .periode-block {
        border: 1px solid #e0e0e0;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        background-color: #ffffff;
    }
    
    .periode-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .periode-dates {
        font-style: italic;
        color: #666;
    }
    
    .periode-actions, .horaire-actions {
        display: flex;
        gap: 10px;
    }
    
  /* Style général des icônes */
.btn-icon {
    font-size: 18px;
    text-decoration: none;
    transition: color 0.3s ease;
}

/* Supprimer — rouge direct, plus foncé au hover */
.btn-icon.delete {
    color: #dc3545; /* Rouge */
}
.btn-icon.delete:hover {
    color: #a71d2a; /* Rouge foncé */
}

/* Modifier — vert direct, plus foncé au hover */
.btn-icon.edit {
    color: #28a745; /* Vert */
}
.btn-icon.edit:hover {
    color: #1e7e34; /* Vert foncé */
}

    
    .horaire-list {
        list-style: none;
        padding-left: 15px;
        margin-top: 10px;
    }
    
    .horaire-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    
    .horaire-info {
        font-size: 15px;
    }
    

</style>


<body>
<div class="container">
    <a href="admin_dashboard.php" class="back-btn" >
    Retour
</a>
    <div style="text-align: center;">
        <h2>Configuration des Horaires</h2>
    </div>

    <!-- Formulaires côte à côte -->
    <div class="form-container">
        <!-- Formulaire ajout période -->
        <div class="form-section">
            <h3>Ajouter une Période</h3>
            <form method="POST">
                <input type="text" name="nom_periode" placeholder="Ex : Ramadan" required>
                <input type="date" name="date_debut" required>
                <input type="date" name="date_fin" required>
                <button type="submit">Ajouter Période</button>
            </form>
        </div>

        <!-- Formulaire ajout horaire -->
        <div class="form-section">
            <h3>Ajouter un Horaire</h3>
            <form method="POST">
                <select name="id_periode" required>
                    <option value="">Choisir une période</option>
                    <?php foreach ($periodes as $periode): ?>
                        <option value="<?= $periode['id'] ?>"><?= htmlspecialchars($periode['nom_periode']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type" required>
                    <option value="matin">Matin</option>
                    <option value="apres_midi">Après-midi</option>
                </select>
                <input type="time" name="heure_debut" required>
                <input type="time" name="heure_fin" required>
                <input type="number" name="limite_retard" min="0" max="99" required placeholder="Limite de retard (en minutes)">
                <button type="submit">Ajouter Horaire</button>
            </form>
        </div>
    </div>

    <!-- Liste des périodes et horaires -->
   <div class="list-section">
    <h3>Périodes et Horaires</h3>
    <?php foreach ($periodes as $periode): ?>
        <div class="periode-block">
            <div class="periode-header">
                <strong><?= htmlspecialchars($periode['nom_periode']) ?></strong>
                <span class="periode-dates">(du <?= $periode['date_debut'] ?> au <?= $periode['date_fin'] ?>)</span>
                <div class="periode-actions">
                <a href="#" class="btn-icon delete" onclick="deletePeriode(<?= $periode['id'] ?>)">
                 <i class="fas fa-trash-alt"></i>
                </a>

                    </a>
                    <a class="btn-icon edit" href="edit_periode.php?id=<?= $periode['id'] ?>" title="Modifier">
                        <i class="fas fa-pen"></i>
                    </a>
                </div>
            </div>

            <ul class="horaire-list">
                <?php
                $stmt = $conn->prepare("SELECT * FROM horaire WHERE id_periode = ?");
                $stmt->bind_param("i", $periode['id']);
                $stmt->execute();
                $horaires = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                foreach ($horaires as $horaire):
                    $type_label = ($horaire['type'] === 'matin') ? 'Matin' : 'Après-midi';
                ?>
                    <li>
                        <span class="horaire-info">
                            <?= $type_label ?> : <?= $horaire['heure_debut'] ?> - <?= $horaire['heure_fin'] ?> | Retard: <?= $horaire['limite_retard'] ?>
                        </span>
                        <div class="horaire-actions">
                        <a href="#" class="btn-icon delete" onclick="deleteHoraire(<?= $horaire['id'] ?>)">
                        <i class="fas fa-trash-alt"></i>
                        </a>


                            <a class="btn-icon edit" href="edit_horaire.php?id=<?= $horaire['id'] ?>" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>

</div>
<?php if (isset($error)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: <?= json_encode($error) ?>,
        confirmButtonText: 'OK'
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<script>
    let successType = <?= json_encode($_GET['success']) ?>;
    let message = "";

    switch (successType) {
        case "periode":
            message = "Période ajoutée avec succès !";
            break;
        case "horaire":
            message = "Horaire ajouté avec succès !";
            break;
        default:
            message = "Opération réussie.";
    }

    Swal.fire({
        icon: 'success',
        title: 'Succès',
        text: message,
        timer: 2500,
        showConfirmButton: false
    });

    // Supprimer le paramètre de l'URL après affichage
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url);
    }
</script>
<?php endif; ?>

</body>

</html>
<script>
function deletePeriode(id) {
    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Cette action supprimera la période et ses horaires.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete_periode=" + id;
        }
    });
}

function deleteHoraire(id) {
    Swal.fire({
        title: "Supprimer cet horaire ?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Supprimer",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete_horaire=" + id;
        }
    });
}
</script>

