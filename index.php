<?php
session_start();
include('db.php');

$erreur = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login']);
    $password = trim($_POST['password']); // ❗ SANS md5

    // Vérifier login + password en clair
    $query = "SELECT id, login, fonction FROM employes WHERE login = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $login, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_role'] = $user['fonction'];

        // Redirection selon le rôle
        if ($user['fonction'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['fonction'] === 'secrétaire') {
            header("Location: secretary_dashboard.php");
        } else {
            header("Location: employee_dashboard.php");
        }
        exit;
    } else {
        $erreur = true;
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Système de Pointage | Ahwa Solutions</title>
    <link rel="stylesheet" href="ind.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="welcome-section">
            <h2>Système de Pointage</h2>
            <p class="company-name">Ahwa Solutions</p>
        </div>
        <div class="login-section">
            <h2>Connexion</h2>
            <form action="index.php" method="POST">
                <div class="input-group">
                    <input type="text" name="login" placeholder="Nom d'utilisateur" required>
                    <i class="fas fa-user"></i>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <i class="fas fa-lock"></i>
                </div>
                <button class="login-btn" type="submit">Se connecter</button>
            </form>
 

        </div>
    </div>

</body>

</html>
<?php if ($erreur): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Erreur de connexion',
        text: 'Login ou mot de passe incorrect!',
        confirmButtonColor: '#d33'
    });
</script>
<?php endif; ?>