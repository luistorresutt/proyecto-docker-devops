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

// Obtener el ID del proyecto
$id_proyecto = isset($_GET['id_proyecto']) ? (int)$_GET['id_proyecto'] : 0;

// Consultar datos del proyecto
$proyecto_query = "SELECT nombre, descripcion FROM proyectos WHERE id_proyecto = ?";
$stmt = $mysqli->prepare($proyecto_query);
$stmt->bind_param("i", $id_proyecto);
$stmt->execute();
$stmt->bind_result($nombre_proyecto, $descripcion_proyecto);
$stmt->fetch();
$stmt->close();

// Consultar las tareas del proyecto
$tareas_query = "SELECT id_tarea, nombre_t, descripcion_t, progreso, fecha_inicio_t, fecha_fin_t, prioridad FROM tareas WHERE id_proyecto = ?";
$stmt = $mysqli->prepare($tareas_query);
$stmt->bind_param("i", $id_proyecto);
$stmt->execute();
$tareas_result = $stmt->get_result();
$stmt->close();

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas del Proyecto</title>
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

        .tareas-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tareas-table th, .tareas-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .tareas-table th {
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

        .add-task-button {
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .add-task-button:hover {
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
            <a href="dashboard_admin.php">
                <img src="img/hogar.png" alt="Inicio">
                Inicio
            </a>
            <a href="usuarios.php">
                <img src="img/usuarios.png" alt="Usuarios">
                Usuarios
            </a>
            <a href="proyectos.php">
                <img src="img/proyectos.png" alt="Proyectos">
                Proyectos
            </a>
            <a href="reportes.php">
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
        <h1><?php echo htmlspecialchars($nombre_proyecto); ?></h1>
        <p><?php echo htmlspecialchars($descripcion_proyecto); ?></p>

        <h2>Tareas</h2>
        <table class="tareas-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Progreso</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Prioridad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $tareas_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre_t']); ?></td>
                        <td><?php echo htmlspecialchars($row['descripcion_t']); ?></td>
                        <td><?php echo htmlspecialchars($row['progreso']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_inicio_t']); ?></td>
                        <td><?php echo htmlspecialchars($row['fecha_fin_t']); ?></td>
                        <td><?php echo htmlspecialchars($row['prioridad']); ?></td>
                        <td class="actions-button">
                            <a href="editar_tarea.php?id_tarea=<?php echo htmlspecialchars($row['id_tarea']); ?>" class="edit">Editar</a>
                            <a href="eliminar_tarea.php?id_tarea=<?php echo htmlspecialchars($row['id_tarea']); ?>" class="delete">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="crear_tarea.php?id_proyecto=<?php echo htmlspecialchars($id_proyecto); ?>" class="add-task-button">Agregar Tarea</a>
    </main>
</body>
</html>

