<?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Conectar a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'usuarios');

if ($mysqli->connect_error) {
    die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Verifica si se ha pasado un ID de tarea en la URL
if (!isset($_GET['id_tarea']) || !is_numeric($_GET['id_tarea'])) {
    header('Location: tareas_leader.php?error=ID de tarea no especificado.');
    exit;
}

$id_tarea = (int)$_GET['id_tarea'];

// Consultar datos de la tarea
$query = "SELECT nombre_t, descripcion_t, progreso, fecha_inicio_t, fecha_fin_t, prioridad, id_proyecto FROM tareas WHERE id_tarea = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$stmt->bind_result($nombre_t, $descripcion_t, $progreso, $fecha_inicio_t, $fecha_fin_t, $prioridad, $id_proyecto);
$stmt->fetch();
$stmt->close();

if (!$nombre_t) {
    header('Location: tareas_leader.php?error=Tarea no encontrada.');
    exit;
}

// Actualizar los datos de la tarea si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_t = $_POST['nombre_t'];
    $descripcion_t = $_POST['descripcion_t'];
    $progreso = $_POST['progreso'];
    $fecha_inicio_t = $_POST['fecha_inicio_t'];
    $fecha_fin_t = $_POST['fecha_fin_t'];
    $prioridad = $_POST['prioridad'];

    $update_query = "UPDATE tareas SET nombre_t = ?, descripcion_t = ?, progreso = ?, fecha_inicio_t = ?, fecha_fin_t = ?, prioridad = ? WHERE id_tarea = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param('ssisssi', $nombre_t, $descripcion_t, $progreso, $fecha_inicio_t, $fecha_fin_t, $prioridad, $id_tarea);

    if ($stmt->execute()) {
        header('Location: tareas_leader.php?id_proyecto=' . $id_proyecto . '&success=Tarea actualizada correctamente.');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tarea</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        .container {
            width: 80%;
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
        }

        .header h1 {
            font-size: 28px;
            color: #0056b3;
            margin: 0;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin: 10px 0 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        button {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #004494;
        }

        .buttons-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .back-button {
            background-color: #6c757d;
        }

        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Editar Tarea</h1>
        </div>
        <form method="post" action="">
            <label for="nombre_t">Nombre de la Tarea:</label>
            <input type="text" id="nombre_t" name="nombre_t" value="<?php echo htmlspecialchars($nombre_t); ?>" required><br>

            <label for="descripcion_t">Descripción:</label>
            <textarea id="descripcion_t" name="descripcion_t" required><?php echo htmlspecialchars($descripcion_t); ?></textarea><br>

            <label for="progreso">Progreso (%):</label>
            <input type="number" id="progreso" name="progreso" value="<?php echo htmlspecialchars($progreso); ?>" min="0" max="100" required><br>

            <label for="fecha_inicio_t">Fecha de Inicio:</label>
            <input type="date" id="fecha_inicio_t" name="fecha_inicio_t" value="<?php echo htmlspecialchars($fecha_inicio_t); ?>" required><br>

            <label for="fecha_fin_t">Fecha de Término:</label>
            <input type="date" id="fecha_fin_t" name="fecha_fin_t" value="<?php echo htmlspecialchars($fecha_fin_t); ?>" required><br>

            <label for="prioridad">Prioridad:</label>
            <select id="prioridad" name="prioridad" required>
                <option value="baja" <?php echo ($prioridad === 'baja') ? 'selected' : ''; ?>>Baja</option>
                <option value="media" <?php echo ($prioridad === 'media') ? 'selected' : ''; ?>>Media</option>
                <option value="alta" <?php echo ($prioridad === 'alta') ? 'selected' : ''; ?>>Alta</option>
            </select><br>

            <div class="buttons-container">
                <button type="submit">Actualizar Tarea</button>
                <a href="tareas_leader.php?id_proyecto=<?php echo htmlspecialchars($id_proyecto); ?>" class="back-button"><button type="button">Volver</button></a>
            </div>
        </form>
    </div>
</body>
</html>

