<?php
include('db.php'); // Connexion à la base de données

// Vérifier si l'ID est passé en paramètre
if (isset($_GET['id'])) {
    $idDemande = intval($_GET['id']); // Sécuriser l'ID

    // Récupérer les détails de la demande
    $sql = "SELECT * FROM demandeconge WHERE idDemande = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDemande);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
    } else {
        echo "<p>Demande introuvable.</p>";
        exit;
    }
} else {
    echo "<p>ID manquant.</p>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la Demande de Congé</title>
    <link rel="stylesheet" href="detnotif.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
/* Reset de base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: url('images/da.jpg') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px;
    color: #333;
}

/* Container principal */
.notification-details {
    border-radius: 20px;
    padding: 40px 30px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    animation: fadeIn 1s ease;
    overflow: hidden;
    border: 2px solid #0d47a1; /* Bordure moderne */
    background: rgba(255, 255, 255, 0.85); /* Fond transparent avec légère opacité */
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Bouton Retour */
.btn-retour {
    position: fixed;
    top: 20px;
    left: 20px;
    background: #0d47a1;
    color: #fff;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    z-index: 999;
}
.btn-retour:hover {
    background-color: #1565c0;
    transform: translateY(-2px);
}

/* Titre */
.notification-details h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 36px;
    color: #0d47a1;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

/* Paragraphes */
.notification-details p {
    margin-bottom: 20px;
    font-size: 18px;
    color: #555;
    line-height: 1.6;
}

.notification-details p strong {
    color: #0d47a1;
    font-weight: bold;
}

/* Formulaire */
form {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 40px;
}

form button {
    padding: 14px 28px;
    font-size: 18px;
    font-weight: bold;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 150px;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Bouton Accepter */
form button[name="statut"][value="approuvé"] {
    background: #4caf50;
    color: white;
}

form button[name="statut"][value="approuvé"]:hover {
    background: #388e3c;
    transform: translateY(-3px);
}

/* Bouton Refuser */
form button[name="statut"][value="rejeté"] {
    background: #f44336;
    color: white;
}

form button[name="statut"][value="rejeté"]:hover {
    background: #d32f2f;
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 600px) {
    .notification-details {
        padding: 30px 20px;
    }

    .notification-details h2 {
        font-size: 28px;
    }

    form {
        flex-direction: column;
        gap: 15px;
    }

    form button {
        width: 100%;
    }
}

</style>
<body>
<button class="btn-retour" onclick="window.location.href='notifications.php'">← Retour</button>

    <div class="notification-details">
        <h2>Détails de la Demande de Congé</h2>

        <?php if ($row): ?>
            <p><strong>Employé ID:</strong> <?php echo htmlspecialchars($row['employe_id']); ?></p>
            <p><strong>Date de Soumission:</strong> <?php echo htmlspecialchars($row['dateSoumission']); ?></p>
            <p><strong>Type de Congé:</strong> <?php echo htmlspecialchars($row['type_conge']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
            <p><strong>Du:</strong> <?php echo htmlspecialchars($row['dateDebut']); ?> <strong>Au:</strong> <?php echo htmlspecialchars($row['dateFin']); ?></p>
            <p><strong>Statut:</strong> <?php echo htmlspecialchars($row['statut']); ?></p>

            <!-- Formulaire pour accepter ou refuser la demande -->
            <form action="traitement_demande.php" method="POST">
                <input type="hidden" name="idDemande" value="<?php echo $row['idDemande']; ?>">
                <button type="submit" name="statut" value="approuvé">Accepter</button>
                <button type="submit" name="statut" value="rejeté">Refuser</button>
            </form>
        <?php else: ?>
            <p>Demande introuvable.</p>
        <?php endif; ?>
    </div>
</body>
</html>

