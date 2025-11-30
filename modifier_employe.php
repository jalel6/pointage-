<?php

session_start(); // Démarre la session pour pouvoir utiliser $_SESSION
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'secrétaire'])) {
    header("Location: index.php"); // Rediriger l'utilisateur vers la page d'accueil s'il n'est ni admin ni secrétaire
    exit(); // Terminer l'exécution du script
}
include 'db.php'; // Connexion à la base de données

// Récupérer les postes de la base de données
$postes_sql = "SELECT DISTINCT poste FROM employes";
$result_postes = $conn->query($postes_sql);


// Récupérer le rôle de l'utilisateur
$role = $_SESSION['user_role'];




?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Employé</title>
    <link rel="stylesheet" href="modifier_emp.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: url('images/tt.jpg') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
}


.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: #0056b3;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.back-btn {
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s;
}

.back-btn:hover {
    background-color: #003d80;
}

.search-container input {
    padding: 10px;
    font-size: 16px;
    border: 2px solid #ccc;
    border-radius: 8px;
    width: 300px;
}

.content {
    padding: 30px;
}

.container {
display: flex;
flex-wrap: wrap;
gap: 30px;
justify-content: center; /* Centrage horizontal des cartes */
padding: 0 40px; /* Padding gauche et droite identique */
margin-top: 20px;
}


.card {
    background: #ffffff;
    border-radius: 12px;
    width: 300px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: scale(1.05);
}

.card-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    height: 120px;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.card-header img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #fff;
    position: absolute;
    bottom: -40px;
}

.card-body {
    text-align: center;
    padding: 20px;
    margin-top: 40px;
}

.card-body h3 {
    font-size: 18px;
    font-weight: 600;
    color: #222;
}

.card-body p {
    font-size: 14px;
    color: #555;
    margin: 5px 0;
}

.card-footer {
    text-align: center;
    padding: 15px;
    background: #f1f1f1;
}

.btn-modifier {
    background-color: #007bff;
    color: white;
    padding: 8px 14px;
    font-size: 14px;
    border-radius: 6px;
    border: none;
    transition: background-color 0.3s;
}

.btn-modifier:hover {
    background-color: #0056b3;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
    justify-content: center;
    align-items: center;
    border: 1.5px solid #007bff; /* ✅ Nouvelle bordure bleue fine et moderne */
}

.modal-content {
    border: 1.5px solid #007bff; /* ✅ Nouvelle bordure bleue fine et moderne */
    background: url('images/da.jpg') no-repeat center center / cover;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.close {
    color: #aaa;
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.modal-content h2 {
    color: #0056b3;
    text-align: center;
    margin-bottom: 20px;
}

.input-group {
    margin-bottom: 15px;
}

.input-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
}

.input-group input,
.input-group select {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.modal-content button[type="submit"] {
    background-color: #0056b3;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    width: 100%;
    margin-top: 15px;
    font-size: 16px;
}

.modal-content button[type="submit"]:hover {
    background-color: #0056b3;
}

.profile-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 20px;
    display: block;
    border: 3px solid #007bff;
}

@media (max-width: 768px) {
    .container {
        flex-direction: column;
        align-items: center;
    }

    .card {
        width: 100%;
    }

.container {
flex-direction: column;
align-items: center;
padding: 0 20px; /* Padding réduit sur petit écran */
}
}

</style>
<body>

<div class="top-bar">
<a href="javascript:history.back()" class="back-btn">
    <i class="fa fa-arrow-left"></i> Retour
</a>
    <div class="search-container">
        <input type="text" id="search" placeholder="Rechercher un employé...">
    </div>
</div>

<div class="content">
    <div class="container">
        <?php
        $sql = "SELECT id, nom, prenom, email, telephone, cin, fonction, poste, date_embauche, photo_profil FROM employes";
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
                        <p>Poste : <?= htmlspecialchars($row["poste"]); ?></p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-modifier" 
                            onclick="openModal(
                                <?= $row['id'] ?>, 
                                '<?= htmlspecialchars($row['nom']) ?>',
                                '<?= htmlspecialchars($row['prenom']) ?>',
                                '<?= htmlspecialchars($row['fonction']) ?>',
                                '<?= htmlspecialchars($row['poste']) ?>',
                                '<?= htmlspecialchars($photo); ?>'
                            )">
                            Modifier
                        </button>
                    </div>
                </div>
        <?php
            }
        } else {
            echo "<p>Aucun employé trouvé.</p>";
        }
        ?>
    </div>
</div>

<!-- Modal de modification -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Modifier Employé</h2>
        <img id="modalProfileImg" class="profile-img" src="images/default-profile.png" alt="Photo de Profil">
        <form id="editForm">
            <input type="hidden" id="empId" name="empId">

            <div class="input-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" disabled>
            </div>

            <div class="input-group">
                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" disabled>
            </div>

            <div class="input-group">
    <label for="fonction">Fonction :</label>
    <select name="fonction" id="fonction" required>
        <option value="" disabled selected>Choisir une fonction</option>
        <option value="admin">admin</option>
        <option value="secrétaire">secrétaire</option>
        <option value="employé">employé</option>
    </select>
</div>


            <div class="input-group">
                <label for="poste">Poste :</label>
                <select name="poste" id="poste" required>
                    <option value="" disabled selected>Choisir un poste</option>
                    <?php
                    if ($result_postes->num_rows > 0) {
                        while ($row_postes = $result_postes->fetch_assoc()) {
                            echo "<option value='" . $row_postes['poste'] . "'>" . $row_postes['poste'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit">Enregistrer</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal");

    // Supposons que $role vient de la session PHP
    const role = "<?php echo $role; ?>";  // Récupérer le rôle de la session PHP

    window.openModal = function(id, nom, prenom, fonction, poste, photo) {
        document.getElementById("empId").value = id;
        document.getElementById("nom").value = nom;
        document.getElementById("prenom").value = prenom;
        document.getElementById("fonction").value = fonction;

        // Si l'utilisateur est une secrétaire, désactiver le champ 'fonction'
        if (role === 'secrétaire') {
            document.getElementById("fonction").disabled = true; // Désactiver le champ fonction
        } else {
            document.getElementById("fonction").disabled = false; // Laisser modifiable pour admin
        }

        document.getElementById("poste").value = poste;
        document.getElementById("modalProfileImg").src = photo || 'images/default-profile.png';
        modal.style.display = "flex";
    };

    function closeModal() {
        modal.style.display = "none";
    }

    document.querySelector(".close").addEventListener("click", closeModal);
    window.addEventListener("click", function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.getElementById("editForm").addEventListener("submit", function(event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);

        Swal.fire({
            title: 'Voulez-vous enregistrer les modifications ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, enregistrer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('edit_employe.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Modifications enregistrées',
                        text: 'Les informations ont été mises à jour avec succès.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Une erreur est survenue lors de la mise à jour.',
                        confirmButtonText: 'Fermer'
                    });
                });
            }
        });
    });

    document.getElementById('search').addEventListener('input', function () {
        const value = this.value.toLowerCase().trim();
        document.querySelectorAll('.card').forEach(card => {
            const info = card.getAttribute('data-info');
            card.style.display = info.includes(value) ? 'block' : 'none';
        });
    });
});

</script>

</body>
</html>
