<?php

include 'db.php';
date_default_timezone_set('Africa/Tunis');
$aujourdhui = date('Y-m-d');

$notifications_existantes = [];
$fichier_path = "notifications_journalieres.txt";

if (file_exists($fichier_path)) {
    $lignes = file($fichier_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lignes as $ligne) {
        if (preg_match("/^$aujourdhui - (.+?) (.+?) - (.+)$/", $ligne, $matches)) {
            $cle = $matches[1] . '_' . $matches[2];
            $statut = $matches[3];
            if (strpos($statut, 'matin') !== false) {
                $notifications_existantes[$cle]['matin'] = $ligne;
            } elseif (strpos($statut, 'soir') !== false || strpos($statut, 'après-midi') !== false) {
                $notifications_existantes[$cle]['apres_midi'] = $ligne;
            }
        }
    }
}

// Vérifie si aujourd'hui est un jour férié
$checkFerie = $conn->prepare("SELECT COUNT(*) FROM jours_feries WHERE date_ferie = ?");
$checkFerie->bind_param("s", $aujourdhui);
$checkFerie->execute();
$checkFerie->bind_result($isFerie);
$checkFerie->fetch();
$checkFerie->close();

if ($isFerie > 0) {
    $notifications_existantes["ferie"] = ["matin" => "Jour férié - Aucun pointage requis pour le $aujourdhui"];
    file_put_contents($fichier_path, implode("\n", array_merge(...array_values($notifications_existantes))) . "\n");
    return;
}

$employes = $conn->query("SELECT * FROM employes");
if (!$employes) {
    exit;
}

while ($employe = $employes->fetch_assoc()) {
    $employe_id = $employe['id'];
    $nom = htmlspecialchars($employe['nom']);
    $prenom = htmlspecialchars($employe['prenom']);
    $cle_employe = $prenom . '_' . $nom;

    $checkConge = $conn->prepare("SELECT COUNT(*) FROM conge WHERE employe_id = ? AND ? BETWEEN dateDebut AND dateFin");
    $checkConge->bind_param("is", $employe_id, $aujourdhui);
    $checkConge->execute();
    $checkConge->bind_result($enConge);
    $checkConge->fetch();
    $checkConge->close();

    if ($enConge > 0) continue;

    foreach (["matin" => ["fin" => "12:00:00"], "apres_midi" => ["debut_apm" => "12:00:01", "fin" => "16:00:00"]] as $type => $periode) {
        $stmt = $conn->prepare("
            SELECT h.heure_debut, h.limite_retard 
            FROM horaire h
            JOIN periode p ON h.id_periode = p.id
            WHERE p.date_debut <= ? AND p.date_fin >= ? AND h.type = ?
            LIMIT 1
        ");
        $stmt->bind_param("sss", $aujourdhui, $aujourdhui, $type);
        $stmt->execute();
        $resHoraire = $stmt->get_result();
        $stmt->close();

        if ($resHoraire->num_rows === 0) continue;

        $data = $resHoraire->fetch_assoc();
        $heure_debut = $data['heure_debut'];
        $limite_retard = $data['limite_retard'];
        $heure_limite = date("H:i:s", strtotime("+{$limite_retard} minutes", strtotime($heure_debut)));

        $heure_min = $type === 'matin' ? "00:00:00" : $heure_debut;
        $heure_max = $periode['fin'];

        $stmt = $conn->prepare("
            SELECT heure_arrivee FROM pointages
            WHERE employe_id = ? AND DATE(heure_arrivee) = ?
            AND TIME(heure_arrivee) >= ? AND TIME(heure_arrivee) <= ?
            ORDER BY heure_arrivee ASC LIMIT 1
        ");
        $stmt->bind_param("isss", $employe_id, $aujourdhui, $heure_min, $heure_max);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        $nouvelle_notification = "";

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $heure = date("H:i:s", strtotime($row['heure_arrivee']));

            if ($heure < $heure_debut) {
                $nouvelle_notification = "✅ Présent $type (Arrivée à $heure)";
            } elseif ($heure <= $heure_limite) {
                $nouvelle_notification = "⏰ Retard $type (Arrivée à $heure)";
            } else {
                $nouvelle_notification = "❌ Absent $type (Arrivée à $heure après $heure_limite)";
            }
        } else {
            if ($type === 'apres_midi' && date("H:i:s") < $heure_debut) {
                $nouvelle_notification = "🔜 Après-midi non encore commencé";
            } else {
                $nouvelle_notification = "❌ Absent $type (Non pointé)";
            }
        }

        $notifications_existantes[$cle_employe][$type] = "$aujourdhui - $prenom $nom - $nouvelle_notification";
    }
}

$fichier_content = "";
foreach ($notifications_existantes as $parts) {
    foreach ($parts as $notif) {
        $fichier_content .= $notif . "\n";
    }
}
file_put_contents($fichier_path, $fichier_content);
?>
