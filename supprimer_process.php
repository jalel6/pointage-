<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_employe'])) {
    $id = intval($_POST['id_employe']);

    // Supprimer l'employé
    $sql = "DELETE FROM employes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Employé supprimé avec succès.'); window.location.href='supprimer_employe.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de la suppression.'); window.location.href='supprimer_employe.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: supprimer_employe.php");
    exit();
}
