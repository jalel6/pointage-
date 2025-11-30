<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Récupération des périodes et leurs horaires
$searchYear = isset($_GET['year']) ? $_GET['year'] : '';

$query = "SELECT p.*, h.type, h.heure_debut, h.heure_fin, h.limite_retard
          FROM periode p 
          LEFT JOIN horaire h ON p.id = h.id_periode";

if ($searchYear !== '') {
    $query .= " WHERE YEAR(p.date_debut) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $searchYear);
} else {
    $query .= " ORDER BY p.date_debut DESC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

$periodes_horaires = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    if (!isset($periodes_horaires[$id])) {
        $periodes_horaires[$id] = [
            'nom_periode' => $row['nom_periode'],
            'date_debut' => $row['date_debut'],
            'date_fin' => $row['date_fin'],
            'horaires' => []
        ];
    }

    if ($row['type']) {
        $periodes_horaires[$id]['horaires'][$row['type']] = [
            'heure_debut' => $row['heure_debut'],
            'heure_fin' => $row['heure_fin'],
            'limite_retard' => $row['limite_retard']
        ];
    }
}

// Récupérer les années distinctes pour le filtre
$yearsResult = $conn->query("SELECT DISTINCT YEAR(date_debut) as annee FROM periode ORDER BY annee DESC");
$years = [];
while ($year = $yearsResult->fetch_assoc()) {
    $years[] = $year['annee'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horaires de Travail</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
</head>
<body>
    <button class="back-btn" onclick="history.back()">
        <i class="fas fa-arrow-left"></i> Dashboard
    </button>
    
    <div class="container">
        <h2>Liste des Horaires de Travail</h2>

        <!-- Barre de recherche par année -->
        <div class="filter-bar">
            <label for="year">Filtrer par année :</label>
            <select name="year" id="year" onchange="this.form.submit()">
                <option value="">-- Toutes les années --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= ($year == $searchYear) ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Liste des périodes et horaires -->
        <?php if (count($periodes_horaires) > 0): ?>
            <?php foreach ($periodes_horaires as $periode): ?>
                <div class="periode">
                    <h3><?= htmlspecialchars($periode['nom_periode']) ?></h3>
                    <p>Du <strong><?= $periode['date_debut'] ?></strong> au <strong><?= $periode['date_fin'] ?></strong></p>
                    <ul>
                        <?php if (isset($periode['horaires']['matin'])): ?>
                            <li>
                                <strong>Matin</strong>
                                <div class="horaire-details">
                                    <div class="horaire-temps">
                                        <i class="fas fa-clock"></i>
                                        <?= $periode['horaires']['matin']['heure_debut'] ?> - <?= $periode['horaires']['matin']['heure_fin'] ?>
                                    </div>
                                    <div class="limite-retard">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Limite de retard: <?= $periode['horaires']['matin']['limite_retard'] ?>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if (isset($periode['horaires']['apres_midi'])): ?>
                            <li>
                                <strong>Après-midi</strong>
                                <div class="horaire-details">
                                    <div class="horaire-temps">
                                        <i class="fas fa-clock"></i>
                                        <?= $periode['horaires']['apres_midi']['heure_debut'] ?> - <?= $periode['horaires']['apres_midi']['heure_fin'] ?>
                                    </div>
                                    <div class="limite-retard">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Limite de retard: <?= $periode['horaires']['apres_midi']['limite_retard'] ?>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if (empty($periode['horaires'])): ?>
                            <li>Aucun horaire défini.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <p>Aucun horaire trouvé pour cette année.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>













<style>
        /* Base & Polices */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #f0f9ff;
            --accent: #0ea5e9;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
    background: linear-gradient(to right, #e3f2fd, #bbdefb);
    min-height: 100vh;
    padding: 100px 15px;
    position: relative;
}

/* Image de fond floutée */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('images/tt.jpg') no-repeat center center fixed;
    background-size: cover;
    filter: blur(6px);
    z-index: -1;
}
     
   /* Conteneur principal */
.container {
    max-width: 1000px;
    margin: 0 auto;
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md), 0 0 15px rgba(37, 99, 235, 0.3); /* Lueur légère autour du conteneur */
    padding: 40px;
    border: 2px solid rgba(37, 99, 235, 0.5); /* Bordure avec une couleur claire pour un effet lumineux */
}

        
        /* Titre principal */
        h2 {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary);
            font-size: 32px;
            font-weight: 600;
            position: relative;
            padding-bottom: 15px;
        }
        
        /* Barre de filtre */
        .filter-bar {
            margin-bottom: 35px;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .filter-bar label {
            margin-right: 15px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .filter-bar select {
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            background-color: var(--card-bg);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            color: var(--text-dark);
            min-width: 200px;
            outline: none;
        }
        
        .filter-bar select:hover,
        .filter-bar select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }
        
        /* Carte période */
        .periode {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }
        
        .periode:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .periode h3 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .periode h3:before {
            content: '\f017';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 12px;
            color: var(--primary-light);
            font-size: 20px;
        }
        
        .periode p {
            font-size: 16px;
            margin-bottom: 20px;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }
        
        .periode p:before {
            content: '\f073';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 12px;
            color: var(--accent);
            font-size: 16px;
        }
        
        .periode ul {
            list-style: none;
            padding-left: 0;
        }
        
        .periode li {
            background-color: var(--secondary);
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 12px;
            font-size: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: var(--transition);
            border-left: 4px solid var(--accent);
        }
        
        .periode li:hover {
            background-color: #e0f2fe;
            transform: translateX(5px);
        }
        
        .periode li strong {
            color: var(--primary-dark);
        }
        
        .horaire-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
        }
        
        .horaire-temps {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .horaire-temps i {
            color: var(--accent);
        }
        
        .limite-retard {
            background-color: rgba(37, 99, 235, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: var(--primary-dark);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Bouton Retour */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
            z-index: 100;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        
        .back-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
        }
        
        /* Message "Aucun horaire" */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            background-color: rgba(248, 250, 252, 0.8);
            border-radius: var(--border-radius);
            border: 1px dashed #cbd5e1;
            color: var(--text-light);
            font-size: 16px;
            margin-top: 20px;
        }
        
        .no-data i {
            font-size: 32px;
            margin-bottom: 15px;
            color: #94a3b8;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 70px 15px 30px;
            }
            
            .container {
                padding: 25px 15px;
            }
            
            h2 {
                font-size: 24px;
                margin-bottom: 30px;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 25px;
            }
            
            .filter-bar label {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .filter-bar select {
                width: 100%;
            }
            
            .periode {
                padding: 20px 15px;
            }
            
            .periode h3 {
                font-size: 18px;
            }
            
            .periode p, .periode li {
                font-size: 14px;
            }
            
            .back-btn {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .filter-bar select {
                padding: 10px 15px;
            }
            
            .periode li {
                padding: 12px 15px;
            }
            
            .horaire-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
