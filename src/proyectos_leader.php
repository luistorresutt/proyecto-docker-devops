<?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtén el ID del usuario desde la sesión
$user_id = $_SESSION['user_id'];

// Conectar a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'usuarios');

if ($mysqli->connect_error) {
    die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Consultar datos del usuario
$query = "SELECT nombres, apellido_paterno, apellido_materno FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre, $apellido_paterno, $apellido_materno);
$stmt->fetch();
$stmt->close();
$full_name = "{$nombre} {$apellido_paterno} {$apellido_materno}";

// Consultar número de proyectos por prioridad
$prioridad_query = "SELECT prioridad, COUNT(*) AS total FROM proyectos GROUP BY prioridad";
$prioridad_result = $mysqli->query($prioridad_query);

if (!$prioridad_result) {
    die('Error en la consulta: ' . $mysqli->error);
}

// Consultar todos los proyectos
$proyectos_query = "SELECT id_proyecto, nombre, descripcion, lider_proyecto, trabajadores, prioridad, fecha_inicio, fecha_termino FROM proyectos";
$proyectos_result = $mysqli->query($proyectos_query);

if (!$proyectos_result) {
    die('Error en la consulta: ' . $mysqli->error);
}

// Calcular el progreso promedio de tareas por proyecto
$progreso_query = "SELECT id_proyecto, AVG(progreso) as progreso_promedio FROM tareas GROUP BY id_proyecto";
$progreso_result = $mysqli->query($progreso_query);

$progreso_promedio = [];
if ($progreso_result) {
    while ($row = $progreso_result->fetch_assoc()) {
        $progreso_promedio[$row['id_proyecto']] = round($row['progreso_promedio']);
    }
    $progreso_result->close();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Proyectos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
            background-color: #f4f7f9;
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
        }

        header .logo {
            display: flex;
            align-items: center;
            font-size: 28px;
            font-weight: bold;
        }

        header .logo img {
            height: 45px;
            margin-right: 15px;
        }

        header .nav-links {
            display: flex;
            gap: 20px;
        }

        header .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        header .nav-links a img {
            height: 24px;
            margin-right: 8px;
        }

        header .nav-links a:hover {
            color: #1abc9c;
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
            margin-top: 5px;
            transition: color 0.3s;
        }

        header .profile a:hover {
            color: #2980b9;
        }

        main {
            margin-top: 80px;
            margin-left: 0;
            padding: 20px;
            flex-grow: 1;
            background-color: white;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-y: auto;
        }

        .widgets-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .widget {
            background-color: #ecf0f1;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            flex: 1;
            min-width: 250px;
            max-width: 33%;
        }

        .widget h3 {
            margin-top: 0;
        }

        .widget span {
            font-size: 20px;
            font-weight: bold;
        }

        .project-table {
            width: 100%;
            border-collapse: collapse;
        }

        .project-table th, .project-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .project-table th {
            background-color: #f2f2f2;
        }

        .actions-button a {
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .actions-button a.edit {
            background-color: #2ecc71;
        }

        .actions-button a.delete {
            background-color: #e74c3c;
        }

        .actions-button a:hover {
            background-color: #2980b9;
        }

        .actions-button a.edit:hover {
            background-color: #27ae60;
        }

        .actions-button a.delete:hover {
            background-color: #c0392b;
        }

        .add-project-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #3498db;
            color: white;
            font-size: 24px;
            text-align: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .add-project-button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="img/CP.png" alt="Logo">
            <span>Panel de Proyectos</span>
        </div>
        <div class="nav-links">
            <a href="dashboard_leader.php">
                <img src="img/hogar.png" alt="Inicio">
                Inicio
            </a>
            <a href="proyectos_leader.php">
                <img src="img/proyectos.png" alt="Proyectos">
                Proyectos
            </a>
            <a href="reportes_leader.php">
                <img src="img/reportes.png" alt="Reportes">
                Reportes
            </a>
        </div>
        <div class="profile">
            <img src="img/admin.jpg" alt="Foto de perfil">
            <div class="profile-info">
                <span><?php echo htmlspecialchars($full_name); ?></span>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <main>
        <div class="widgets-container">
            <div class="widget">
                <h3>Proyectos Baja Prioridad</h3>
                <span>
                    <?php
                    // Obtener el conteo de proyectos de baja prioridad
                    $baja_count = 0;
                    $prioridad_result->data_seek(0);
                    while ($row = $prioridad_result->fetch_assoc()) {
                        if (strtolower($row['prioridad']) === 'baja') {
                            $baja_count = $row['total'];
                            break;
                        }
                    }
                    echo htmlspecialchars($baja_count);
                    ?>
                </span>
            </div>
            <div class="widget">
                <h3>Proyectos Media Prioridad</h3>
                <span>
                    <?php
                    // Obtener el conteo de proyectos de media prioridad
                    $media_count = 0;
                    $prioridad_result->data_seek(0);
                    while ($row = $prioridad_result->fetch_assoc()) {
                        if (strtolower($row['prioridad']) === 'media') {
                            $media_count = $row['total'];
                            break;
                        }
                    }
                    echo htmlspecialchars($media_count);
                    ?>
                </span>
            </div>
            <div class="widget">
                <h3>Proyectos Alta Prioridad</h3>
                <span>
                    <?php
                    // Obtener el conteo de proyectos de alta prioridad
                    $alta_count = 0;
                    $prioridad_result->data_seek(0);
                    while ($row = $prioridad_result->fetch_assoc()) {
                        if (strtolower($row['prioridad']) === 'alta') {
                            $alta_count = $row['total'];
                            break;
                        }
                    }
                    echo htmlspecialchars($alta_count);
                    ?>
                </span>
            </div>
        </div>

        <table class="project-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Líder del Proyecto</th>
                    <th>Trabajadores</th>
                    <th>Prioridad</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Fin</th>
                    <th>Progreso (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $proyectos_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id_proyecto']); ?></td>
                        <td>
                            <a href="tareas_leader.php?id_proyecto=<?php echo htmlspecialchars($row['id_proyecto']); ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($row['lider_proyecto']); ?></td>
                        <td><?php echo htmlspecialchars($row['trabajadores']); ?></td>
                        <td><?php echo htmlspecialchars($row['prioridad']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_inicio']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_termino']); ?></td>
                        <td>
                            <?php 
                            // Mostrar el progreso promedio o "No hay tareas" si no hay progreso
                            echo isset($progreso_promedio[$row['id_proyecto']]) ? htmlspecialchars($progreso_promedio[$row['id_proyecto']]) . '%' : 'No hay tareas';
                            ?>
                        </td>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>

