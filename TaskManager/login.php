<?php
require_once 'conexion.php';
session_start();

if (isset($_SESSION['UserID'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['Email'] ?? '');
    $password = $_POST['Password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT u.*, r.Name as RoleName 
        FROM users u 
        JOIN roles r ON u.RoleId = r.Id 
        WHERE u.Email = ? AND u.IsActive = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['PasswordHash'])) {
        $_SESSION['UserID'] = $user['Id'];
        $_SESSION['FullName'] = $user['FullName'];
        $_SESSION['Email'] = $user['Email'];
        $_SESSION['JobTitle'] = $user['JobTitle'];
        $_SESSION['RoleID'] = $user['RoleId'];
        $_SESSION['RoleName'] = $user['RoleName'];
        $_SESSION['DepartmentID'] = $user['DepartmentId'];

        header("Location: index.php");
        exit;
    } else {
        $error = "Credenciales incorrectas o usuario inactivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InnoviTech TM</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; background-color: #2c3e50; }
        
        .area { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background: linear-gradient(to left, #343a40, #212529); }
        .circles { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; margin: 0; padding: 0; }
        .circles li { position: absolute; display: block; list-style: none; width: 20px; height: 20px; background: rgba(255, 255, 255, 0.05); animation: animate 25s linear infinite; bottom: -150px; }
        .circles li:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .circles li:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .circles li:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .circles li:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .circles li:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .circles li:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .circles li:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .circles li:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .circles li:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .circles li:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

        @keyframes animate {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; border-radius: 0; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
        }

        .login-box { 
            background-color: rgba(33, 37, 41, 0.95); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6); 
            text-align: center; 
            width: 100%; 
            max-width: 350px;
            box-sizing: border-box;
            color: white;
            z-index: 1;
        }

        .login-box img { max-width: 80%; margin-bottom: 20px; background: transparent; padding: 10px; border-radius: 10px;}
        .login-box input { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 8px; box-sizing: border-box; font-size: 15px;}
        .login-box button { padding: 12px; border: none; border-radius: 8px; background-color: #007bff; color: white; font-size: 16px; cursor: pointer; margin-top: 15px; width: 100%; font-weight: bold; transition: background-color 0.3s;}
        .login-box button:hover { background-color: #0056b3; }
        .error-msg { color: #dc3545; font-size: 14px; margin-bottom: 10px; background: rgba(220, 53, 69, 0.1); padding: 10px; border-radius: 5px;}

        @media (max-width: 480px) {
            .login-box { padding: 30px 20px; width: 90%; }
            .login-box h2 { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="area">
        <ul class="circles">
            <li></li><li></li><li></li><li></li><li></li>
            <li></li><li></li><li></li><li></li><li></li>
        </ul>
    </div>

    <div class="login-box">
        <img src="assets/img/innovitech.png" alt="Logo" />
        <h2>Inicio de Sesión</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="email" name="Email" placeholder="Correo electrónico" required />
            <input type="password" name="Password" placeholder="Contraseña" required />
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>