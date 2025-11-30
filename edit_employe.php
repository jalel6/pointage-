<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['empId'])) {
        $empId = intval($_POST['empId']);

        // Récupérer les valeurs actuelles de l'employé
        $sql = "SELECT fonction, poste, date_embauche FROM employes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $empId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo "Erreur : Employé introuvable.";
            exit;
        }

        // Vérifier et conserver les anciennes valeurs si les champs sont vides
        $new_fonction = isset($_POST['fonction']) && trim($_POST['fonction']) !== '' ? trim($_POST['fonction']) : $row['fonction'];
        $new_poste = isset($_POST['poste']) && trim($_POST['poste']) !== '' ? trim($_POST['poste']) : $row['poste'];
        $new_date_embauche = isset($_POST['date_embauche']) && trim($_POST['date_embauche']) !== '' ? trim($_POST['date_embauche']) : $row['date_embauche'];

        // Construire dynamiquement la requête SQL en fonction des champs modifiés
        $fields = [];
        $params = [];
        $types = "";

        if ($new_fonction !== $row['fonction']) {
            $fields[] = "fonction = ?";
            $params[] = $new_fonction;
            $types .= "s";
        }

        if ($new_poste !== $row['poste']) {
            $fields[] = "poste = ?";
            $params[] = $new_poste;
            $types .= "s";
        }

        if ($new_date_embauche !== $row['date_embauche']) {
            $fields[] = "date_embauche = ?";
            $params[] = $new_date_embauche;
            $types .= "s";
        }

        // Vérifier si au moins un champ a changé
        if (!empty($fields)) {
            $params[] = $empId;
            $types .= "i";

            $sql = "UPDATE employes SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                echo "Les informations de l'employé ont été mises à jour avec succès.";
            } else {
                echo "Erreur lors de la mise à jour : " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Aucune modification détectée.";
        }
    } else {
        echo "Erreur : Données manquantes.";
    }
}

$conn->close();
?>