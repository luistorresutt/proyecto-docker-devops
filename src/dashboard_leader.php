<?php
// Conexión a la base de datos
$host = 'localhost';
$db = 'usuarios';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Iniciar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener datos del usuario
$query = "SELECT nombres, apellido_paterno, apellido_materno FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre, $apellido_paterno, $apellido_materno);
$stmt->fetch();
$stmt->close();

$full_name = "{$nombre} {$apellido_paterno} {$apellido_materno}";
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Líder</title>

<style>
/* ---------------- BASE ---------------- */
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    margin: 0;
    background: #f4f7f9;
}

/* ---------------- HEADER ---------------- */
header {
    background: #2c3e50;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 10;
}

/* LOGO */
header .logo {
    display: flex;
    align-items: center;
}

header .logo img {
    height: 45px;
    margin-right: 0px;
}

/* No texto en el logo */
header .logo span {
    display: none;
}

/* ---------- PERFIL ---------- */
.profile {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-right: 40px;
}

.profile img {
    height: 45px;
    width: 45px;
    border-radius: 50%;
    border: 2px solid #fff;
}

/* 🔴 Botón rojo de cierre con X */
.logout-x {
    background: #e74c3c;
    color: white;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 22px;
    font-weight: bold;
    text-decoration: none;
    transition: background 0.3s;
}

.logout-x:hover {
    background: #c0392b;
}

/* ---------------- MAIN ---------------- */
main {
    margin-top: 130px;
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 25px;
}

.welcome {
    width: 100%;
    text-align: center;
}

.welcome h1 {
    margin: 0;
    color: #2c3e50;
    font-size: 28px;
}

/* ---------------- WIDGETS ---------------- */
.widget {
    background: #ecf0f1;
    width: 260px;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    text-decoration: none;
    color: #2c3e50;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform .3s, box-shadow .3s;
}

.widget:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
}

.widget img {
    width: 90px;
    margin-bottom: 15px;
}

/* ---------------- RESPONSIVE ---------------- */
@media (max-width: 768px) {
    .widget {
        width: 45%;
        min-width: 240px;
    }
}

@media (max-width: 480px) {
    header {
        padding: 10px 15px;
    }

    header .logo img {
        height: 35px;
    }

    .widget {
        width: 90%;
    }

    .widget img {
        width: 75px;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">
        <img src="img/CP.png" alt="Logo">
        <span></span>
    </div>

    <div class="profile">
        <img src="img/admin.jpg" alt="Admin">
        <a href="logout.php" class="logout-x">✕</a>
    </div>
</header>

<main>
    <div class="welcome">
        <h1>Bienvenido, <?php echo htmlspecialchars($full_name); ?></h1>
    </div>

    <a href="proyectos_leader.php" class="widget">
        <img src="img/proyectos.png" alt="Proyectos">
        <h3>Proyectos</h3>
        <p>Administración de tus proyectos</p>
    </a>

    <a href="reportes_leader.php" class="widget">
        <img src="img/reportes.png" alt="Reportes">
        <h3>Reportes</h3>
        <p>Generación de reportes</p>
    </a>
</main>

</body>
</html>
