<?php
session_start();

if (!isset($_SESSION['user_name'])) {
    header("Location: index.html");
    exit();
}

$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Determinar la URL de redirección en función del tipo de usuario
if ($user_type === 'administrador') {
    $redirect_url = 'dashboard_admin.php';
} elseif ($user_type === 'lider') {
    $redirect_url = 'dashboard_leader.php';
} else {
    $redirect_url = 'dashboard_user.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <style>
        /* Estilos generales */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
            background: #f0f0f0;
            position: relative;
        }

        /* Fondo animado */
        .area {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(to left, #8f94fb, #4e54c8);
        }

        .circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .circles li {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }

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

        /* Contenedor de bienvenida */
        .welcome-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            text-align: center;
            animation: fadeInUp 0.6s ease-in-out;
            max-width: 500px;
            width: 90%;
            box-sizing: border-box;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            margin-top: 0;
            font-size: 28px;
            color: #333;
        }

        p {
            font-size: 18px;
            color: #555;
            margin-top: 15px;
        }

        /* Media Queries para responsividad */
        @media (max-width: 768px) {
            .welcome-container {
                padding: 30px 25px;
            }
            h2 {
                font-size: 24px;
            }
            p {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .welcome-container {
                padding: 25px 20px;
            }
            h2 {
                font-size: 22px;
            }
            p {
                font-size: 14px;
            }
        }
    </style>
    <script>
        // Redirigir después de 3 segundos
        setTimeout(function() {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 3000);
    </script>
</head>
<body>
    <div class="area">
        <ul class="circles">
            <li></li><li></li><li></li><li></li><li></li>
            <li></li><li></li><li></li><li></li><li></li>
        </ul>
    </div>

    <div class="welcome-container">
        <h2>Bienvenido, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>Serás redirigido al dashboard en breve...</p>
    </div>
</body>
</html>
