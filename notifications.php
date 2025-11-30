<?php
include('db.php'); // Connexion à la base de données

// Récupérer toutes les notifications "en attente"
$sql = "SELECT * FROM demandeconge WHERE statut = 'en attente' ORDER BY dateSoumission DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('images/not.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #333;
        }

        /* Header */
        header {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        header h2 {
            font-size: 32px;
            color: #0d47a1;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        header h2:hover {
            color: #ff5722;
            transform: scale(1.05);
        }

        /* Back button */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 18px;
            background: #0d47a1;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #08306b;
            transform: translateY(-3px);
        }

        /* Notifications Container */
        .notifications-container {
            width: 90%;
            max-width: 800px;
            margin-top: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        /* Notification List */
        ul {
            list-style: none;
            margin-top: 20px;
        }

        .notification {
            background: linear-gradient(135deg, #f9f9f9, #e0e0e0);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification:hover {
            background: linear-gradient(135deg, #ffffff, #d4e0f0);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .notification-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-icon {
            font-size: 24px;
            color: #0d47a1;
        }

        .notification p {
            font-size: 18px;
            font-weight: 500;
            color: #333;
            margin: 0;
        }

        .notification .date {
            font-size: 14px;
            color: #666;
            margin-left: 34px;
        }

        /* No notifications */
        .notifications-container p {
            text-align: center;
            font-size: 18px;
            color: #777;
            padding: 30px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            header h2 {
                font-size: 24px;
            }

            .back-btn {
                padding: 8px 14px;
                font-size: 12px;
            }

            .notifications-container {
                padding: 20px;
            }

            .notification p {
                font-size: 16px;
            }

            .notification .date {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .notification {
                padding: 15px;
            }

            .notification-icon {
                font-size: 20px;
            }

            .notification p {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

<header>
<button class="back-btn" onclick="window.location.href='admin_dashboard.php'">
    <i class="fas fa-arrow-left"></i> Retour
</button>

    <h2><i class="fas fa-bell"></i> Notifications</h2>
</header>

<div class="notifications-container">
    <?php if ($result->num_rows > 0): ?>
        <ul>
            <?php while ($row = $result->fetch_assoc()): ?>
                <li class="notification clickable" onclick="window.location.href='details_notification.php?id=<?php echo $row['idDemande']; ?>'">
                    <div class="notification-header">
                        <i class="fas fa-bell notification-icon"></i>
                        <p><strong>Nouvelle demande de congé reçue</strong></p>
                    </div>
                    <span class="date">
                        <i class="far fa-calendar-alt"></i> 
                        <?php echo date('d/m/Y', strtotime($row['dateSoumission'])); ?>

                    </span>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Aucune notification à afficher.</p>
    <?php endif; ?>
</div>

</body>
</html>
<style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #e2e8ff;
            --secondary-color: #f8f9fa;
            --text-color: #333333;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --hover-color: #f1f3ff;
            --shadow-color: rgba(67, 97, 238, 0.15);
            --success-color: #3ccf4e;
            --warning-color: #ffc107;
            --danger-color: #ef476f;
            --white: #ffffff;
            --transition: all 0.3s ease;
            --border-radius: 12px;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        }        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', 'Roboto', sans-serif;
        }
        
      
    body {
        background: linear-gradient(135deg, rgba(240, 245, 255, 0.8), rgba(220, 230, 255, 0.9));
        background-size: cover;
        min-height: 100vh;
        overflow-x: hidden;
        position: relative;
    }



body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('images/tt.jpg') no-repeat center center fixed;
    background-size: cover;
    filter: blur(3px);
    opacity: 0.15;
    z-index: -1;
}
        
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.2);
            z-index: 100;
            border-bottom-left-radius: 1px;
            border-bottom-right-radius: 1px;
        }
        
        header h2 {
            margin: 0 auto;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            letter-spacing: 0.5px;
            font-size: 1.5rem;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: var(--white);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .back-btn:active {
            transform: translateY(0);
        }
        
        .notifications-container {
            max-width: 850px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        ul {
            list-style: none;
            display: grid;
            gap: 1.2rem;
        }
        
        .notification {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 1.2rem 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-left: 5px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }
        
        .notification::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
            opacity: 0.7;
        }
        
        .notification:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px -5px rgba(67, 97, 238, 0.25);
            background-color: var(--hover-color);
        }
        
        .clickable {
            cursor: pointer;
        }
        
        .notification-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            gap: 1rem;
        }
        
        .notification-icon {
            background-color: var(--primary-light);
            color: var(--primary-color);
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        .notification:hover .notification-icon {
            transform: scale(1.1);
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .notification-header p {
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        .notification-header p strong {
            color: var(--primary-dark);
        }
        
        .date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-left: 3.2rem;
            padding-top: 0.2rem;
            font-weight: 400;
        }
        
        .date i {
            color: var(--primary-color);
            opacity: 0.8;
        }
        
        /* Message quand il n'y a pas de notifications */
        .notifications-container > p {
            text-align: center;
            padding: 3rem 2rem;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-top: 2rem;
            border: 1px dashed var(--border-color);
        }    
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }
        
        .spinner-container {
            width: 80px;
            height: 80px;
            background-color: var(--white);
            border-radius: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: pulse-shadow 1.5s infinite;
        }
        
        .loading-spinner::after {
            content: "";
            width: 40px;
            height: 40px;
            border: 4px solid var(--primary-light);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse-shadow {
            0% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.5); }
            70% { box-shadow: 0 0 0 15px rgba(67, 97, 238, 0); }
            100% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0); }
        }
        
        /* Animation pour les nouvelles notifications */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(67, 97, 238, 0); }
            100% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0); }
        }
        
        .notification.new {
            animation: pulse 2s infinite;
            border-left: 5px solid var(--warning-color);
        }
        
        .notification.new::before {
            background: var(--warning-color);
        }
        
        .notification.new::after {
            content: "";
            position: absolute;
            top: 15px;
            right: 15px;
            width: 10px;
            height: 10px;
            background-color: var(--warning-color);
            border-radius: 50%;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            header {
                padding: 1rem 1.2rem;
                border-radius: 0;
            }
            
            header h2 {
                font-size: 1.2rem;
                margin: 0;
                margin-left: 1rem;
            }
            
            .back-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
            
            .notifications-container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }
            
            .notification {
                padding: 1rem;
            }
            
            .notification-header p {
                font-size: 0.95rem;
            }
            
            .notification-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
            
            .date {
                margin-left: 2.8rem;
                font-size: 0.8rem;
            }
            
            .custom-swal-content strong {
                min-width: 100px;
                font-size: 0.9rem;
            }
            
            .custom-swal-content p {
                font-size: 0.9rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.2rem;
            }
        }
        
        /* Animation subtile d'entrée pour les notifications */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .notification:nth-child(2) {
            animation-delay: 0.1s;
        }
        
        .notification:nth-child(3) {
            animation-delay: 0.2s;
        }
        
        .notification:nth-child(4) {
            animation-delay: 0.3s;
        }
        
        .notification:nth-child(5) {
            animation-delay: 0.4s;
        }
    </style>










