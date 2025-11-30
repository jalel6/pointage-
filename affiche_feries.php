<?php
session_start();


include 'db.php';

$year = date('Y');
if (isset($_POST['search_year'])) {
    $year = $_POST['search_year'];
}

// Récupérer tous les jours fériés pour le calendrier
$all_stmt = $conn->prepare("SELECT * FROM jours_feries ORDER BY date_ferie ASC");
$all_stmt->execute();
$all_result = $all_stmt->get_result();
$tous_les_feries = $all_result->fetch_all(MYSQLI_ASSOC);

// Récupérer les jours fériés pour l'année sélectionnée (affichage dans la liste)
$stmt = $conn->prepare("SELECT * FROM jours_feries WHERE YEAR(date_ferie) = ? ORDER BY date_ferie DESC");
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();
$jours = $result->fetch_all(MYSQLI_ASSOC);

// Récupération des années disponibles
$years_result = $conn->query("SELECT DISTINCT YEAR(date_ferie) AS year FROM jours_feries ORDER BY year DESC");
$years = $years_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Jours Fériés</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


</head>
<body>
<a href="javascript:history.back()" class="back-btn">
    <i class="fa fa-arrow-left"></i> Dashboard
</a>

<div class="container">

<!-- Liste des jours fériés de l'année sélectionnée -->
<h1>Liste des jours fériés de l'année <?= $year ?></h1> <!-- Titre ajouté -->

<div id="ferie-list" class="feries-list" style="display: none;">
    <?php foreach ($jours as $jour): ?>
        <p><strong><?= htmlspecialchars($jour['date_ferie']) ?></strong>: <?= htmlspecialchars($jour['nom_ferie']) ?></p>
    <?php endforeach; ?>
</div>

<form method="post" class="form-inline">
    <div class="form-group">
        <select name="search_year">
            <?php foreach ($years as $yr): ?>
                <option value="<?= $yr['year'] ?>" <?= $year == $yr['year'] ? 'selected' : '' ?>><?= $yr['year'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-search"></i> Rechercher
    </button>
</form>


    <div id='calendar'></div>

    <!-- Liste des jours fériés de l'année sélectionnée -->
    <div id="ferie-list" class="feries-list" style="display: none;">
        <?php foreach ($jours as $jour): ?>
            <p><strong><?= htmlspecialchars($jour['date_ferie']) ?>:</strong> <?= htmlspecialchars($jour['nom_ferie']) ?></p>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var ferieList = document.getElementById('ferie-list');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            locale: 'fr',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },
            events: [
                <?php foreach ($tous_les_feries as $jour): ?>
                {
                    title: "<?= addslashes($jour['nom_ferie']) ?>",
                    start: "<?= $jour['date_ferie'] ?>",
                    allDay: true,
                    color: '#007BFF'
                },
                <?php endforeach; ?>
            ],
            viewDidMount: function (info) {
                if (info.view.type === 'listMonth') {
                    ferieList.style.display = 'flex';
                } else {
                    ferieList.style.display = 'none';
                }
            },
            datesSet: function (info) {
                if (info.view.type === 'listMonth') {
                    ferieList.style.display = 'flex';
                } else {
                    ferieList.style.display = 'none';
                }
            }
        });

        calendar.render();
    });
</script>
</body>
</html>

<style>

/* Enhanced Business Application CSS */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
/* Accentuer les bordures et l'effet bleu */
.card,
.feries-list p,
.table-container,
input,
select,
textarea {
  border: 1px solid rgba(37, 99, 235, 0.2); /* Bleu clair */
  border-radius: var(--radius-md);
  transition: all 0.3s ease;
}

/* Hover amélioré */
.card:hover,
.feries-list p:hover {
  border-color: rgba(37, 99, 235, 0.4);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.1);
}

/* Animation douce sur les boutons */
.btn {
  transition: all 0.3s ease, transform 0.2s ease;
  box-shadow: var(--shadow-sm);
}

.btn:hover {
  background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
  color: var(--white);
  transform: scale(1.03);
}

/* Sélection améliorée */
select {
  border-color: rgba(37, 99, 235, 0.3);
}

select:focus {
  border-color: var(--primary-dark);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --primary-light: #dbeafe;
  --accent: #0284c7;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --dark: #1e293b;
  --gray: #64748b;
  --light-gray: #e2e8f0;
  --white: #ffffff;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 18px;
}

/* Base styles */
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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


@keyframes gradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.container {
  max-width: 1200px;
  margin: 30px auto; /* Réduire l'espace au-dessus et en dessous du conteneur */
  padding: 20px 25px; /* Réduire l'espace intérieur du conteneur */
  background-color: var(--white);
  box-shadow: var(--shadow-lg);
  border-radius: var(--radius-lg);
  border: 2px solid rgba(37, 99, 235, 0.3);
  position: relative;
  overflow: hidden;
}

.container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--primary-dark);
}

h2 {
  text-align: center;
  font-size: 1.75rem; /* Réduire la taille du titre */
  margin-bottom: 1.5rem; /* Réduire l'espacement sous le titre */
  padding-bottom: 0.5rem; /* Réduire l'espace en bas du titre */
  position: relative;
}

h2::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
  border-radius: 2px;
}
h1{
  text-align: center;
  font-size: 1.75rem; /* Réduire la taille du titre */
  margin-bottom: 1.5rem; /* Réduire l'espacement sous le titre */
  padding-bottom: 0.5rem; /* Réduire l'espace en bas du titre */
  position: relative;
}

p {
  margin-bottom: 1rem;
}

/* Forms */
form {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-bottom: 40px;
  flex-wrap: wrap;
  padding: 25px;
  background: rgba(240, 247, 255, 0.5);
  border-radius: var(--radius-md);
  border: 1px solid var(--light-gray);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px; /* Réduire l'espacement entre les éléments de formulaire */
  min-width: 200px;
}

label {
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--dark);
}

input, 
select, 
textarea {
  padding: 8px 12px; /* Réduire les paddings internes */
  font-size: 0.9rem; /* Réduire la taille de la police */
  border: 1px solid var(--light-gray);
  border-radius: var(--radius-sm);
  background: var(--white);
  color: var(--dark);
  transition: all 0.3s ease;
  font-family: inherit;
  box-shadow: var(--shadow-sm);
  width: 100%;
}

input:focus, 
select:focus, 
textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%232563eb' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: calc(100% - 12px) center;
  padding-right: 40px;
}

/* Buttons */
.btn {
    padding: 8px 16px; /* Réduire la taille des boutons */
  font-size: 0.875rem; /* Réduire la taille de la police */
  min-width: 100px; /* Réduire la largeur minimale des boutons */
  background-color: var(--primary);
  color: var(--white);
  border: none;
  border-radius: var(--radius-sm);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
  text-align: center;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 1rem;
  min-width: 120px;
}

.btn:hover {
  background-color: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn:active {
  transform: translateY(0);
}

.btn-primary {
  background-color: var(--primary);
}

.btn-success {
  background-color: var(--success);
}

.btn-warning {
  background-color: var(--warning);
}

.btn-danger {
  background-color: var(--danger);
}

.btn-outline {
  background-color: transparent;
  border: 1px solid var(--primary);
  color: var(--primary);
}

.btn-outline:hover {
  background-color: var(--primary);
  color: var(--white);
}

.btn-sm {
  padding: 8px 16px;
  font-size: 0.875rem;
  min-width: 80px;
}

.btn-lg {
  padding: 14px 28px;
  font-size: 1.125rem;
  min-width: 150px;
}

/* Card styles */
.card {
  border: 2px solid var(--light-gray);
  background: var(--white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  margin-bottom: 20px;
  border: 1px solid var(--light-gray);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  padding: 20px;
  background: var(--primary-light);
  border-bottom: 1px solid var(--light-gray);
}

.card-body {
  padding: 20px;
}

.card-footer {
  padding: 15px 20px;
  background: rgba(240, 247, 255, 0.5);
  border-top: 1px solid var(--light-gray);
}

/* Tables */
.table-container {
  margin-bottom: 20px;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--light-gray);
}

table {
  width: 100%;
  border-collapse: collapse;
  background: var(--white);
}

th, td {
  padding: 10px; /* Réduire les paddings dans les cellules du tableau */
  font-size: 0.875rem; /* Réduire la taille de la police */
}

th {
  background-color: var(--primary-light);
  color: var(--primary-dark);
  font-weight: 600;
  white-space: nowrap;
}

tr:last-child td {
  border-bottom: none;
}

tr:hover {
  background-color: rgba(240, 247, 255, 0.5);
}

/* Grid system */
.row {
  display: flex;
  flex-wrap: wrap;
  margin: -15px;
}

.col {
  padding: 15px;
  flex: 1;
}

.col-2 { flex: 0 0 16.66%; max-width: 16.66%; }
.col-3 { flex: 0 0 25%; max-width: 25%; }
.col-4 { flex: 0 0 33.33%; max-width: 33.33%; }
.col-6 { flex: 0 0 50%; max-width: 50%; }
.col-8 { flex: 0 0 66.66%; max-width: 66.66%; }
.col-9 { flex: 0 0 75%; max-width: 75%; }
.col-12 { flex: 0 0 100%; max-width: 100%; }

/* Utilities */
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-primary { color: var(--primary); }
.text-success { color: var(--success); }
.text-warning { color: var(--warning); }
.text-danger { color: var(--danger); }
.text-muted { color: var(--gray); }

.bg-light { background-color: var(--primary-light); }
.bg-white { background-color: var(--white); }

.p-0 { padding: 0; }
.p-1 { padding: 0.5rem; }
.p-2 { padding: 1rem; }
.p-3 { padding: 1.5rem; }
.p-4 { padding: 2rem; }

.m-0 { margin: 0; }
.m-1 { margin: 0.5rem; }
.m-2 { margin: 1rem; }
.m-3 { margin: 1.5rem; }
.m-4 { margin: 2rem; }

.mb-1 { margin-bottom: 0.5rem; }
.mb-2 { margin-bottom: 1rem; }
.mb-3 { margin-bottom: 1.5rem; }
.mb-4 { margin-bottom: 2rem; }

.mt-1 { margin-top: 0.5rem; }
.mt-2 { margin-top: 1rem; }
.mt-3 { margin-top: 1.5rem; }
.mt-4 { margin-top: 2rem; }

.d-flex { display: flex; }
.flex-wrap { flex-wrap: wrap; }
.justify-content-between { justify-content: space-between; }
.justify-content-center { justify-content: center; }
.align-items-center { align-items: center; }
.gap-1 { gap: 0.5rem; }
.gap-2 { gap: 1rem; }
.gap-3 { gap: 1.5rem; }

* Lists */
.list {
  list-style: none;
  margin-bottom: 20px;
}

.list-item {
  padding: 15px 20px;
  border-bottom: 1px solid var(--light-gray);
  display: flex;
  align-items: center;
  gap: 15px;
  transition: background-color 0.2s ease;
}

.list-item:last-child {
  border-bottom: none;
}

.list-item:hover {
  background-color: rgba(240, 247, 255, 0.5);
}

.list-card {
  background: var(--white);
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--light-gray);
  box-shadow: var(--shadow-md);
}

/* Holiday listings styling (from your original code) */
.feries-list {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  justify-content: space-between;
  margin-bottom: 30px;
}

.feries-list p {
  flex: 1 1 calc(33.33% - 10px); /* Réduire l'espace entre les éléments */
  min-width: 220px; /* Réduire la largeur minimale */
  padding: 12px 15px; /* Réduire le padding interne */
  font-size: 14px; /* Réduire la taille de la police */
  display: flex;
  align-items: flex-start;
  gap: 8px; /* Réduire l'espacement entre les éléments à l'intérieur du paragraphe */
  margin-bottom: 0;
}


.feries-list p:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-lg);
}

.feries-list p::before {
  content: "📅";
  font-size: 18px;
  margin-top: 2px;
}

/* Calendar */
#calendar {
  margin-bottom: 50px;
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--light-gray);
}

/* Navigation */
.nav {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
}

.nav-link {
  padding: 10px 15px;
  color: var(--dark);
  text-decoration: none;
  border-radius: var(--radius-sm);
  transition: all 0.3s ease;
  font-weight: 500;
}

.nav-link:hover,
.nav-link.active {
  background-color: var(--primary);
  color: var(--white);
}

.back-btn {
  position: fixed;
  top: 20px;
  left: 20px;
  padding: 10px 20px;
  background: var(--primary);
  color: var(--white);
  text-decoration: none;
  border-radius: var(--radius-sm);
  font-weight: 500;
  z-index: 1000;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: var(--shadow-md);
}

.back-btn:hover {
  background-color: var(--primary-dark);
  transform: translateY(-2px);
}

.logout-btn {
  background-color: var(--danger);
}

.logout-btn:hover {
  background-color: #dc2626;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 4px 8px;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 20px;
  text-align: center;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-primary { background-color: var(--primary-light); color: var(--primary-dark); }
.badge-success { background-color: #d1fae5; color: #065f46; }
.badge-warning { background-color: #fef3c7; color: #92400e; }
.badge-danger { background-color: #fee2e2; color: #b91c1c; }

/* Alerts */
.alert {
  padding: 15px 20px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  border-left: 4px solid transparent;
}

.alert-primary { background-color: var(--primary-light); border-left-color: var(--primary); }
.alert-success { background-color: #d1fae5; border-left-color: var(--success); }
.alert-warning { background-color: #fef3c7; border-left-color: var(--warning); }
.alert-danger { background-color: #fee2e2; border-left-color: var(--danger); }

/* Media Queries */
@media screen and (max-width: 1200px) {
  .container {
    max-width: 95%;
    margin: 40px auto;
  }
}

@media screen and (max-width: 992px) {
  .col-md-6 { flex: 0 0 50%; max-width: 50%; }
  .col-md-12 { flex: 0 0 100%; max-width: 100%; }
  
  h2 {
    font-size: 1.75rem;
  }
}

@media screen and (max-width: 768px) {
  .container {
    margin: 30px auto;
    padding: 25px 20px;
  }
  
  form {
    flex-direction: column;
    padding: 20px 15px;
  }
  
  .form-group {
    width: 100%;
  }
  
  h2 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .col-sm-6 { flex: 0 0 50%; max-width: 50%; }
  .col-sm-12 { flex: 0 0 100%; max-width: 100%; }
  
  .feries-list {
    flex-direction: column;
    gap: 15px;
  }
  
  .feries-list p {
    flex: 1 1 100%;
    min-width: auto;
  }
  
  .d-sm-none { display: none; }
  .d-sm-block { display: block; }
  .d-sm-flex { display: flex; }
}

@media screen and (max-width: 576px) {
  .container {
    margin: 20px auto;
    padding: 20px 15px;
    border-radius: var(--radius-md);
  }
  
  h2 {
    font-size: 1.25rem;
    margin-bottom: 1.25rem;
  }
  
  h2::after {
    width: 60px;
    height: 3px;
  }
  
  form {
    padding: 15px 10px;
  }
  
  input, select, textarea, .btn {
    padding: 10px 14px;
    font-size: 0.9rem;
  }
  
  .col-xs-12 { flex: 0 0 100%; max-width: 100%; }
  
  .feries-list p {
    padding: 15px;
    font-size: 14px;
  }
  
  .back-btn {
    top: 10px;
    left: 10px;
    padding: 8px 15px;
    font-size: 14px;
  }
  
  .table-container {
    margin-bottom: 20px;
  }
  
  th, td {
    padding: 10px;
    font-size: 0.875rem;
  }
  
  .d-xs-none { display: none; }
  .d-xs-block { display: block; }
  .d-xs-flex { display: flex; }
}

/* Print styles */
@media print {
  body {
    background: none;
  }
  
  body::before {
    display: none;
  }
  
  .container {
    box-shadow: none;
    margin: 0;
    padding: 10px;
    max-width: 100%;
  }
  
  .no-print {
    display: none !important;
  }
  
  .back-btn, .logout-btn {
    display: none;
  }
}</style>