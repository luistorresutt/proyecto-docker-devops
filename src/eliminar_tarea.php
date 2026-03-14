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
if (!isset($_GET['id_tarea'])) {
    die('ID de la tarea no especificado.');
}

$id_tarea = (int)$_GET['id_tarea'];

// Consultar datos de la tarea para obtener id_proyecto
$query = "SELECT nombre_t, id_proyecto FROM tareas WHERE id_tarea = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$stmt->bind_result($nombre_tarea, $id_proyecto);
$stmt->fetch();
$stmt->close();

if (!$nombre_tarea) {
    die('Tarea no encontrada.');
}

// Eliminar la tarea si se ha confirmado
if (isset($_POST['confirm'])) {
    $delete_query = "DELETE FROM tareas WHERE id_tarea = ?";
    $stmt = $mysqli->prepare($delete_query);
    $stmt->bind_param("i", $id_tarea);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    header('Location: tareas_leader.php?id_proyecto=' . $id_proyecto . '&success=Tarea eliminada correctamente.');
    exit;
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .confirmation-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 400px;
            text-align: center;
        }

        .confirmation-box h2 {
            margin-top: 0;
        }

        .confirmation-box p {
            margin: 20px 0;
        }

        .confirmation-box button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 5px;
            transition: background-color 0.3s;
        }

        .confirmation-box button:hover {
            background-color: #2980b9;
        }

        .confirmation-box .cancel {
            background-color: #e74c3c;
        }

        .confirmation-box .cancel:hover {
            background-color: #c0392b;
        }
    </style>
    <script>
        function confirmDelete(event) {
            event.preventDefault();
            if (confirm("¿Estás seguro de que deseas eliminar esta tarea?")) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</head>
<body>
    <div class="confirmation-box">
        <h2>Confirmar Eliminación</h2>
        <p>¿Estás seguro de que deseas eliminar la tarea "<?php echo htmlspecialchars($nombre_tarea); ?>"?</p>
        <form id="delete-form" method="post">
            <button type="submit" name="confirm">Eliminar</button>
            <a href="tareas_leader.php?id_proyecto=<?php echo htmlspecialchars($id_proyecto); ?>">
                <button type="button" class="cancel">Cancelar</button>
            </a>
        </form>
    </div>
</body>
</html>

