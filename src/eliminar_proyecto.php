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

// Verifica si se ha pasado un ID de proyecto en la URL
if (!isset($_GET['id_proyecto'])) {
    die('ID del proyecto no especificado.');
}

$id_proyecto = (int)$_GET['id_proyecto'];

// Consultar datos del proyecto
$query = "SELECT nombre FROM proyectos WHERE id_proyecto = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_proyecto);
$stmt->execute();
$stmt->bind_result($nombre_proyecto);
$stmt->fetch();
$stmt->close();

if (!$nombre_proyecto) {
    die('Proyecto no encontrado.');
}

// Eliminar el proyecto si se ha confirmado
if (isset($_POST['confirm'])) {
    $delete_query = "DELETE FROM proyectos WHERE id_proyecto = ?";
    $stmt = $mysqli->prepare($delete_query);
    $stmt->bind_param("i", $id_proyecto);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    header('Location: proyectos.php');
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
            if (confirm("¿Estás seguro de que deseas eliminar este proyecto?")) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</head>
<body>
    <div class="confirmation-box">
        <h2>Confirmar Eliminación</h2>
        <p>¿Estás seguro de que deseas eliminar el proyecto "<?php echo htmlspecialchars($nombre_proyecto); ?>"?</p>
        <form id="delete-form" method="post">
            <button type="submit" name="confirm">Eliminar</button>
            <a href="proyectos.php">
                <button type="button" class="cancel">Cancelar</button>
            </a>
        </form>
    </div>
</body>
</html>
