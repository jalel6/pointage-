<?php
session_start();

// Vérification que l'utilisateur est connecté comme admin ou secrétaire
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'secrétaire'])) {
    header("Location: index.php");
    exit();
}


$file = 'notifications_journalieres.txt';
$notifications = file($file, FILE_IGNORE_NEW_LINES);

// Récupération des notifications d'aujourd'hui uniquement
$today = date('Y-m-d');
$notifications_aujourdhui = [];

foreach ($notifications as $line) {
    if (strpos($line, $today) !== false && (strpos($line, 'Absent') !== false || strpos($line, 'Retard') !== false)) {
        $notifications_aujourdhui[] = $line;
    }
}

// Trier les notifications par type (Absence et Retard)
$absences = [];
$retards = [];

foreach ($notifications_aujourdhui as $notif) {
    if (strpos($notif, 'Absent') !== false) {
        $absences[] = $notif;
    } elseif (strpos($notif, 'Retard') !== false) {
        $retards[] = $notif;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications Retard / Absence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <header class="header">
        <h1 class="page-title">Tableau de Bord des Notifications</h1>
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </header>

    <div class="page-container">
        <div class="dashboard-date">
            Aujourd'hui, le <?= date('d/m/Y') ?>
        </div>

        <div class="cards-container">
            <!-- Carte des absences -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title absences">
                        <i class="fas fa-user-slash"></i> Absences
                    </h2>
                    <span class="badge absences"><?= count($absences) ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($absences) > 0): ?>
                        <ul class="notification-list">
                            <?php foreach ($absences as $absence): ?>
                                <li class="notification-item absence"><?= htmlspecialchars($absence) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-message">Aucune absence signalée aujourd'hui</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Carte des retards -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title retards">
                        <i class="fas fa-clock"></i> Retards
                    </h2>
                    <span class="badge retards"><?= count($retards) ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($retards) > 0): ?>
                        <ul class="notification-list">
                            <?php foreach ($retards as $retard): ?>
                                <li class="notification-item retard"><?= htmlspecialchars($retard) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-message">Aucun retard signalé aujourd'hui</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Toutes les notifications -->
        <div class="notifications-container">
            <div class="notifications-header">
                <h2 class="notifications-title">
                    <i class="fas fa-bell"></i> Toutes les notifications
                </h2>
                <span class="badge"><?= count($notifications_aujourdhui) ?></span>
            </div>
            <div class="card-body">
                <?php if (count($notifications_aujourdhui) > 0): ?>
                    <ul class="notification-list">
                        <?php foreach ($notifications_aujourdhui as $notif): ?>
                            <?php 
                                $class = strpos($notif, 'Absent') !== false ? 'absence' : 
                                    (strpos($notif, 'Retard') !== false ? 'retard' : '');
                            ?>
                            <li class="notification-item <?= $class ?>"><?= htmlspecialchars($notif) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-message">Aucune notification de retard ou d'absence pour aujourd'hui</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Animation des notifications
        const items = document.querySelectorAll('.notification-item');
        items.forEach((item, index) => {
            item.style.animationDelay = `${0.2 + (index * 0.1)}s`;
        });
        
        // Ajouter des indicateurs visuels aux notifications
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter des icônes aux notifications pour améliorer la lisibilité
            const absenceItems = document.querySelectorAll('.notification-item.absence');
            const retardItems = document.querySelectorAll('.notification-item.retard');
            
            absenceItems.forEach(item => {
                const icon = document.createElement('i');
                icon.className = 'fas fa-user-slash';
                icon.style.marginRight = '10px';
                icon.style.color = 'var(--absence-color)';
                item.prepend(icon);
            });
            
            retardItems.forEach(item => {
                const icon = document.createElement('i');
                icon.className = 'fas fa-clock';
                icon.style.marginRight = '10px';
                icon.style.color = 'var(--retard-color)';
                item.prepend(icon);
            });
            
            // Effet de survol amélioré
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
        });
    </script>
</body>
</html>


<style>
        :root {
            --primary-blue: #0a67f2;
            --primary-blue-light: #3b82f6;
            --primary-blue-dark: #0552cc;
            --secondary-blue: #dbeafe;
            --tertiary-blue: #bfdbfe;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --absence-color: #ef4444;
            --absence-bg: #fee2e2;
            --retard-color: #f59e0b;
            --retard-bg: #fef3c7;
            --gradient-blue: linear-gradient(135deg, #0552cc 0%, #3b82f6 100%);
            --gradient-header: linear-gradient(135deg, #0a67f2 0%, #2e78f5 50%, #5593f7 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            background-color: var(--gray-100);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.05'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .header {
            background: var(--gradient-header);
            box-shadow: 0 4px 20px rgba(10, 103, 242, 0.2);
            padding: 1.25rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.5px;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .page-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 24px;
            background-color: var(--white);
            border-radius: 3px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--white);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .back-btn i {
            margin-right: 0.5rem;
        }

        .page-container {
            flex: 1;
            padding: 2rem;
            max-width: 960px;
            margin: 0 auto;
            width: 100%;
        }

        .dashboard-date {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            backdrop-filter: blur(5px);
        }

        .card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
            z-index: -1;
        }

        .card:hover {
            transform: translateY(-5px) scale(1.01);
        }
        
        .card:hover::after {
            opacity: 1;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to right, var(--white), var(--gray-50));
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            position: relative;
        }

        .card-title i {
            margin-right: 0.5rem;
        }

        .card-title.absences {
            color: var(--absence-color);
        }

        .card-title.retards {
            color: var(--retard-color);
        }

        .badge {
            background-color: var(--secondary-blue);
            color: var(--primary-blue);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }

        .badge.absences {
            background-color: var(--absence-bg);
            color: var(--absence-color);
        }

        .badge.retards {
            background-color: var(--retard-bg);
            color: var(--retard-color);
        }

        .card-body {
            padding: 1rem 1.5rem;
        }

        .notification-list {
            list-style-type: none;
        }

        .notification-item {
            padding: 1rem 1.25rem;
            margin-bottom: 0.9rem;
            background-color: var(--gray-50);
            border-radius: 0.6rem;
            border-left: 4px solid var(--primary-blue);
            transition: all 0.3s ease;
            font-size: 0.9rem;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
        }

        .notification-item::before {
            content: '';
            position: absolute;
            left: -4px;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: var(--primary-blue);
            border-top-left-radius: 0.6rem;
            border-bottom-left-radius: 0.6rem;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .notification-item:last-child {
            margin-bottom: 0;
        }

        .notification-item:hover {
            background-color: var(--secondary-blue);
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(10, 103, 242, 0.1);
        }

        .notification-item:hover::before {
            opacity: 1;
            background-color: currentColor;
        }

        .notification-item.absence {
            border-left-color: var(--absence-color);
        }
        
        .notification-item.absence::before {
            background-color: var(--absence-color);
        }

        .notification-item.retard {
            border-left-color: var(--retard-color);
        }
        
        .notification-item.retard::before {
            background-color: var(--retard-color);
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            font-style: italic;
            background-color: var(--gray-50);
            border-radius: 0.5rem;
        }

        .notifications-container {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .notifications-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--gradient-blue);
            z-index: 1;
        }

        .notifications-header {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--gradient-blue);
            color: var(--white);
            position: relative;
        }
        
        .notifications-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(to bottom, rgba(10, 103, 242, 0.1), transparent);
        }

        .notifications-title {
            font-size: 1.125rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .notifications-title i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(10, 103, 242, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(10, 103, 242, 0); }
            100% { box-shadow: 0 0 0 0 rgba(10, 103, 242, 0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .cards-container .card:nth-child(1) { animation-delay: 0.2s; }
        .cards-container .card:nth-child(2) { animation-delay: 0.4s; }

        .notification-item {
            animation: slideIn 0.4s ease-out forwards;
            opacity: 0;
        }

        .notification-item:nth-child(1) { animation-delay: 0.2s; }
        .notification-item:nth-child(2) { animation-delay: 0.3s; }
        .notification-item:nth-child(3) { animation-delay: 0.4s; }
        .notification-item:nth-child(4) { animation-delay: 0.5s; }
        .notification-item:nth-child(5) { animation-delay: 0.6s; }
        .notification-item:nth-child(6) { animation-delay: 0.7s; }
        .notification-item:nth-child(7) { animation-delay: 0.8s; }
        
        .badge {
            position: relative;
            overflow: hidden;
        }
        
        .badge.absences:not(:empty),
        .badge.retards:not(:empty) {
            animation: pulse 2s infinite;
        }
/* Effet de brillance sur les cartes */
.card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: 1.5s;
            opacity: 0;
            pointer-events: none;
        }
        
        .card:hover::before {
            left: 100%;
            opacity: 0.5;
            transition: 0.8s;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .page-title {
                font-size: 1.25rem;
                padding-left: 1.25rem;
            }
            
            .page-title::before {
                height: 18px;
            }
            
            .page-container {
                padding: 1rem;
            }
            
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .card {
                margin-bottom: 1rem;
            }
        }
    </style>

