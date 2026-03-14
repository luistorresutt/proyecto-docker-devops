<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Conectar a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'usuarios');

if ($mysqli->connect_error) {
    die('Error de Conexión: ' . $mysqli->connect_error);
}

// Obtener el ID del proyecto
$id_proyecto = isset($_GET['id_proyecto']) ? (int)$_GET['id_proyecto'] : 0;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Formulario recibido.<br>"; // Mensaje de prueba

    // Recibir datos del formulario
    $nombre_t = $_POST['nombre_t'];
    $descripcion_t = $_POST['descripcion_t'];
    $progreso = 0; // Por defecto 0
    $fecha_inicio_t = $_POST['fecha_inicio_t'];
    $fecha_fin_t = $_POST['fecha_fin_t'];
    $prioridad = $_POST['prioridad'];
    $id_proyecto = (int)$_POST['id_proyecto']; // Asegúrate de que sea un entero

    // Validar datos
    if (!empty($nombre_t) && !empty($id_proyecto)) {
        // Preparar la consulta
        $insert_query = "INSERT INTO tareas (nombre_t, descripcion_t, progreso, fecha_inicio_t, fecha_fin_t, prioridad, id_proyecto) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("ssisssi", $nombre_t, $descripcion_t, $progreso, $fecha_inicio_t, $fecha_fin_t, $prioridad, $id_proyecto);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            header("Location: tareas.php?id_proyecto=$id_proyecto");
            exit;
        } else {
            echo "Error al agregar la tarea: " . $stmt->error; // Muestra el error
        }

        $stmt->close();
    } else {
        echo "Por favor, completa todos los campos requeridos.";
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Tarea</title>
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
            border-bottom: 2px solid #0056b3; /* Azul similar al de proyectos.php */
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
            background-color: #0056b3; /* Azul similar al de proyectos.php */
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
            color: white;
        }

        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Crear Nueva Tarea</h1>
        </div>
        <form method="POST" action="crear_tarea.php?id_proyecto=<?php echo htmlspecialchars($id_proyecto); ?>">
            <label for="nombre_t">Nombre de la Tarea:</label>
            <input type="text" id="nombre_t" name="nombre_t" required>

            <label for="descripcion_t">Descripción:</label>
            <textarea id="descripcion_t" name="descripcion_t"></textarea>

            <label for="fecha_inicio_t">Fecha de Inicio:</label>
            <input type="date" id="fecha_inicio_t" name="fecha_inicio_t" required>

            <label for="fecha_fin_t">Fecha de Fin:</label>
            <input type="date" id="fecha_fin_t" name="fecha_fin_t" required>

            <label for="prioridad">Prioridad:</label>
            <select id="prioridad" name="prioridad">
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
            </select>

            <input type="hidden" name="id_proyecto" value="<?php echo htmlspecialchars($id_proyecto); ?>">

            <div class="buttons-container">
                <button type="submit">Crear Tarea</button>
                <a href="tareas.php?id_proyecto=<?php echo htmlspecialchars($id_proyecto); ?>" class="back-button">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>

