<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT * FROM employes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $poste = $row['poste'];

// 1. Solde annuel autorisé
$sqlSolde = "SELECT solde FROM soldeconge WHERE poste = ?";
$stmtSolde = $conn->prepare($sqlSolde);
$stmtSolde->bind_param("s", $poste);
$stmtSolde->execute();
$resultSolde = $stmtSolde->get_result();
$soldeData = $resultSolde->fetch_assoc();
$soldeTotal = $soldeData['solde'] ?? 0;

// 2. Jours de congé déjà pris
$sqlPris = "SELECT SUM(DATEDIFF(dateFin, dateDebut) + 1) as joursPris 
            FROM conge 
            WHERE employe_id = ?";
$stmtPris = $conn->prepare($sqlPris);
$stmtPris->bind_param("i", $id);
$stmtPris->execute();
$resultPris = $stmtPris->get_result();
$prisData = $resultPris->fetch_assoc();
$joursPris = $prisData['joursPris'] ?? 0;

// 3. Calcul du solde restant
$reste = $soldeTotal - $joursPris;





    } else {
        echo "<p>Employé introuvable.</p>";
        exit;
    }
} else {
    echo "<p>ID manquant.</p>";
    exit;
}

$photo = !empty($row["photo_profil"]) ? $row["photo_profil"] : "images/default-profile.png";
?>

<style>
.details-card {
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    max-width: 800px;
    margin: 30px auto;
    overflow: hidden;
}

.details-card .card-header {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px 20px;
    background: linear-gradient(to bottom, #0056b3, #3399ff);
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
}

.details-card .card-header img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    object-fit: cover;
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
    background: white;
    margin-bottom: 15px;
    z-index: 1;
}

.details-card .card-header h2 {
    font-size: 22px;          /* Taille de la police */
    font-weight: 700;         /* Poids de la police pour donner un effet "fort" */
    color: #0056b3;            /* Couleur blanche pour contraster avec l'arrière-plan bleu */
    margin: 0;               /* Enlever la marge */
    padding-top: 180px;       /* Espace entre la photo et le nom/prénom */
    text-align: center;      /* Centrer le texte */
    text-transform: capitalize; /* Mettre la première lettre en majuscule */
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); /* Ombre portée pour rendre le texte plus visible */
    letter-spacing: 0.5px;   /* Espacement des lettres pour un effet plus aérien */
}


.details-card .card-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px 25px;
    font-size: 16px;
    color: #333;
    padding: 20px;
}

.details-card .card-body p {
    margin: 0;
    background: #f9fbfe;
    border-left: 4px solid #007bff;
    padding: 12px 15px;
    border-radius: 10px;
    font-weight: 500;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
}

.details-card .card-body p i {
    margin-right: 10px;
    color: #007bff;
    font-size: 18px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

@media (max-width: 600px) {
    .details-card .card-body {
        grid-template-columns: 1fr;
    }

    .details-card {
        padding: 20px;
    }
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>

<div class="details-card">
    <div class="card-header">
        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Photo de Profil">
        <h2><?php echo htmlspecialchars($row["nom"]) . " " . htmlspecialchars($row["prenom"]); ?></h2>
    </div>

    <div class="card-body">
        <p><i class="fa fa-envelope"></i> Email : <?php echo htmlspecialchars($row["email"]); ?></p>
        <p><i class="fa fa-phone"></i> Téléphone : <?php echo htmlspecialchars($row["telephone"]); ?></p>
        <p><i class="fa fa-id-card"></i> CIN : <?php echo htmlspecialchars($row["cin"]); ?></p>
        <p><i class="fa fa-map-marker"></i> Adresse : <?php echo htmlspecialchars($row["adresse"]); ?></p>
        <p><i class="fa fa-venus-mars"></i> Sexe : <?php echo htmlspecialchars($row["sexe"]); ?></p>
        <p><i class="fa fa-birthday-cake"></i> Âge : <?php echo htmlspecialchars($row["age"]); ?> ans</p>
        <p><i class="fa fa-briefcase"></i> Fonction : <?php echo htmlspecialchars($row["fonction"]); ?></p>
        <p><i class="fa fa-building"></i> Poste : <?php echo htmlspecialchars($row["poste"]); ?></p>
        <p><i class="fa fa-calendar"></i> Date d'embauche : <?php echo htmlspecialchars($row["date_embauche"]); ?></p>
        <p><i class="fa fa-suitcase"></i> Solde congé : <?php echo htmlspecialchars($reste); ?> jours</p>

    </div>
</div>
