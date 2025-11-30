<?php include 'db.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Employés</title>
    <link rel="stylesheet" href="liste_employers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
   
</head>
<style>
    
    .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease-in-out;
}

.modal-content {
    background: linear-gradient(145deg, #ffffff, #f0f0f0);
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    width: 90%;
    max-width: 550px;
    padding: 30px 40px;
    position: relative;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
    transition: color 0.2s ease-in-out;
}

.close:hover {
    color: #000;
    transform: scale(1.2);
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



</style>
<body>

<!-- Barre de navigation -->
<div class="top-bar">
<a href="javascript:history.back()" class="back-btn">
    <i class="fa fa-arrow-left"></i> Retour
</a>


    <div class="search-container">
        <input type="text" id="search" placeholder="Rechercher un employé...">
    </div>
    <button onclick="window.print()" class="btn-print">🖨️ Imprimer</button>
</div>

<!-- Contenu principal -->
<div class="content">
    <div class="container">
        <?php
        $sql = "SELECT id, nom, prenom, email, telephone, cin, photo_profil FROM employes";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $photo = (!empty($row["photo_profil"])) ? $row["photo_profil"] : "images/default-profile.png";
                $searchData = strtolower($row["nom"] . " " . $row["prenom"] . " " . $row["email"] . " " . $row["telephone"] . " " . $row["cin"]);

                echo "<div class='card' data-info='" . htmlspecialchars($searchData, ENT_QUOTES) . "'>
                        <div class='card-header'>
                            <img src='" . htmlspecialchars($photo) . "' alt='Photo de Profil'>
                        </div>
                        <div class='card-body'>
                            <h3>" . htmlspecialchars($row["nom"]) . " " . htmlspecialchars($row["prenom"]) . "</h3>
                            <p>Email : " . htmlspecialchars($row["email"]) . "</p>
                            <p>Téléphone : " . htmlspecialchars($row["telephone"]) . "</p>
                            <p>CIN : " . htmlspecialchars($row["cin"]) . "</p>
                        </div>
                        <div class='card-footer'>
                            <button class='voir-details' data-id='" . $row["id"] . "'>Voir Détails</button>
                        </div>
                    </div>";
            }
        } else {
            echo "<p>Aucun employé trouvé.</p>";
        }

        $conn->close();
        ?>
    </div>
</div>

<!-- Modal pour afficher les détails -->
<div id="modal-details" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modal-body">Chargement...</div>
    </div>
</div>

<!-- JavaScript -->
<script>
    // Recherche des employés en temps réel
    document.getElementById('search').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.card');

        cards.forEach(card => {
            const info = card.getAttribute('data-info');
            card.style.display = info.includes(searchValue) ? 'block' : 'none';
        });
    });

    // Afficher les détails dans une modal
    document.querySelectorAll('.voir-details').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const modal = document.getElementById("modal-details");
            const body = document.getElementById("modal-body");

            modal.style.display = "flex";
            body.innerHTML = "Chargement...";

            fetch('details_employe.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    body.innerHTML = data;
                })
                .catch(err => {
                    body.innerHTML = "<p>Erreur lors du chargement.</p>";
                });
        });
    });

    // Fermer la modal
    document.querySelector(".close").addEventListener("click", () => {
        document.getElementById("modal-details").style.display = "none";
    });

    // Fermer si clic en dehors de la modal
    window.addEventListener("click", e => {
        const modal = document.getElementById("modal-details");
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });
</script>

</body>
</html>
