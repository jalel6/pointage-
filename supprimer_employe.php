<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer Employé</title>
    <link rel="stylesheet" href="supp_emp.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="top-bar">
    <a href="admin_dashboard.php" class="btn-retour">&larr; Retour</a>
    <div class="search-container">
        <input type="text" id="search" placeholder="Rechercher un employé...">
    </div>
</div>

<div class="content">
    <div class="container">
        <?php
        $sql = "SELECT id, nom, prenom, email, telephone, cin, fonction, date_embauche, photo_profil FROM employes";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $photo = !empty($row["photo_profil"]) ? $row["photo_profil"] : "images/default-profile.png";
                $searchData = strtolower($row["nom"] . " " . $row["prenom"] . " " . $row["email"] . " " . $row["telephone"] . " " . $row["cin"]);
                ?>
                <div class="card" data-info="<?= htmlspecialchars($searchData); ?>">
                    <div class="card-header">
                        <img src="<?= htmlspecialchars($photo); ?>" alt="Photo de Profil">
                    </div>
                    <div class="card-body">
                        <h3><?= htmlspecialchars($row["nom"]) . " " . htmlspecialchars($row["prenom"]); ?></h3>
                        <p>Email : <?= htmlspecialchars($row["email"]); ?></p>
                        <p>Téléphone : <?= htmlspecialchars($row["telephone"]); ?></p>
                        <p>CIN : <?= htmlspecialchars($row["cin"]); ?></p>
                        <p>Fonction : <?= htmlspecialchars($row["fonction"]); ?></p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-supprimer" onclick="confirmDelete(<?= $row['id']; ?>, '<?= addslashes($row['prenom'] . " " . $row['nom']); ?>')">Supprimer</button>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<p>Aucun employé trouvé.</p>";
        }
        $conn->close();
        ?>
    </div>
</div>

<!-- SCRIPT avec SweetAlert -->
<script>
    function confirmDelete(id, nomComplet) {
        Swal.fire({
            title: "Êtes-vous sûr ?",
            text: `Vous allez supprimer ${nomComplet}. Cette action est irréversible !`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Oui, supprimer",
            cancelButtonText: "Annuler"
        }).then((result) => {
            if (result.isConfirmed) {
                // Requête AJAX vers le fichier PHP
                fetch('supprimer_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id_employe=' + encodeURIComponent(id)
                })
                .then(response => response.text())
                .then(data => {
                    Swal.fire({
                        title: "Supprimé !",
                        text: "L'employé a été supprimé avec succès.",
                        icon: "success"
                    }).then(() => {
                        location.reload(); // Rafraîchir la page
                    });
                })
                .catch(error => {
                    Swal.fire("Erreur", "Une erreur est survenue.", "error");
                });
            }
        });
    }

    // Recherche dynamique
    document.getElementById('search').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.card');

        cards.forEach(card => {
            const info = card.getAttribute('data-info');
            card.style.display = info.includes(searchValue) ? 'block' : 'none';
        });
    });
</script>

</body>
</html>
