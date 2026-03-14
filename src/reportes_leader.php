<?php
// Conexión a la base de datos
$host = 'localhost';
$db = 'usuarios';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Iniciar sesión y verificar autenticación del usuario
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Consulta para obtener los datos del usuario
$query = "SELECT nombres, apellido_paterno, apellido_materno FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre, $apellido_paterno, $apellido_materno);
$stmt->fetch();
$stmt->close();
$full_name = "{$nombre} {$apellido_paterno} {$apellido_materno}";

// Consultas para obtener el número de usuarios registrados por tipo
$types = ['lider', 'administrador', 'trabajador'];
$user_counts = [];

foreach ($types as $type) {
    $query = "SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $user_counts[$type] = $count;
    $stmt->close();
}

// Obtener los 5 usuarios más recientes
$query = "SELECT id, nombres, apellido_paterno, apellido_materno, email, tipo_usuario FROM usuarios ORDER BY id DESC LIMIT 5";
$result = $mysqli->query($query);
$recent_users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
    $result->free();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Reportes</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        header {
            background: #2c3e50;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
            box-sizing: border-box;
        }

        header .logo {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
        }

        header .logo img {
            height: 45px;
            margin-right: 15px;
        }

        header .logo span {
            font-size: 24px;
        }

        header .nav-links {
            display: flex;
            gap: 20px;
        }

        header .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        header .nav-links a img {
            height: 24px;
            margin-right: 8px;
        }

        header .profile {
            display: flex;
            align-items: center;
        }

        header .profile img {
            border-radius: 50%;
            height: 45px;
            width: 45px;
            margin-right: 15px;
            border: 2px solid #fff;
        }

        header .profile-info {
            display: flex;
            flex-direction: column;
        }

        header .profile-info span {
            font-size: 18px;
            margin: 0;
        }

        header .profile a {
            color: #3498db;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s;
        }

        header .profile a:hover {
            color: #2980b9;
        }

        .sidebar {
            background-color: #34495e;
            color: white;
            padding: 20px;
            width: 250px;
            height: calc(100vh - 60px);
            position: fixed;
            left: 0;
            top: 60px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            overflow-y: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            transform: translateX(-100%);
            z-index: 9;
        }

        .sidebar.show {
            transform: translateX(0);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
        }

        .sidebar .toggle {
            background-color: #2c3e50;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            font-size: 20px;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }

        .sidebar .toggle:hover {
            background-color: #1abc9c;
        }

        .sidebar button {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 15px;
            text-align: left;
            cursor: pointer;
            width: 100%;
            outline: none;
            font-size: 18px;
            border-radius: 5px;
            margin: 10px 0;
            transition: background-color 0.3s;
        }

        .sidebar button:hover {
            background-color: #1abc9c;
        }

        main {
            margin-left: 0;
            margin-top: 60px;
            padding: 20px;
            flex-grow: 1;
            background-color: white;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            box-sizing: border-box;
            transition: margin-left 0.3s ease;
        }

        .table-container {
            margin-top: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #2c3e50;
            color: white;
        }

        td {
            background-color: #f9f9f9;
        }

        tr:nth-child(even) td {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="img/CP.png" alt="Logo">
            <span>Panel de Usuarios</span>
        </div>
        <div class="nav-links">
            <a href="dashboard_leader.php">
                <img src="img/hogar.png" alt="Inicio" style="height: 24px; vertical-align: middle;">
                Inicio
            </a>
            <a href="proyectos_leader.php">
                <img src="img/proyectos.png" alt="Proyectos" style="height: 24px; vertical-align: middle;">
                Proyectos
            </a>
            <a href="reportes_leader.php">
                <img src="img/reportes.png" alt="Reportes" style="height: 24px; vertical-align: middle;">
                Reportes
            </a>
        </div>
        <div class="profile">
            <img src="img/admin.jpg" alt="Avatar">
            <div class="profile-info">
                <span><?php echo htmlspecialchars($full_name); ?></span>
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    <div class="sidebar">
        <button class="toggle">&#9776;</button>
        <button onclick="window.location.href='usuarios.php'">Gestionar Usuarios</button>
        <button onclick="window.location.href='reportes.php'">Generar Reportes</button>
    </div>
    <main>
        <h1>Reportes</h1>
        <div class="table-container">
            <h2>Usuarios Registrados por Tipo</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo de Usuario</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_counts as $type => $count): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(ucfirst($type)); ?></td>
                            <td><?php echo htmlspecialchars($count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-container">
            <h2>Usuarios Disponibles</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido Paterno</th>
                        <th>Apellido Materno</th>
                        <th>Correo Electrónico</th>
                        <th>Tipo de Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['nombres']); ?></td>
                            <td><?php echo htmlspecialchars($user['apellido_paterno']); ?></td>
                            <td><?php echo htmlspecialchars($user['apellido_materno']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['tipo_usuario'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        document.querySelector('.sidebar .toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>
