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
if (!isset($_GET['id_proyecto']) || !is_numeric($_GET['id_proyecto'])) {
    header('Location: proyectos.php?error=ID del proyecto no especificado.');
    exit;
}

$id_proyecto = (int)$_GET['id_proyecto'];

// Consultar datos del proyecto
$query = "SELECT nombre, descripcion, lider_proyecto, trabajadores, fecha_inicio, fecha_termino, prioridad FROM proyectos WHERE id_proyecto = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_proyecto);
$stmt->execute();
$stmt->bind_result($nombre, $descripcion, $lider_proyecto, $trabajadores, $fecha_inicio, $fecha_termino, $prioridad);
$stmt->fetch();
$stmt->close();

if (!$nombre) {
    header('Location: proyectos.php?error=Proyecto no encontrado.');
    exit;
}

// Obtener líderes de proyecto
$lideres_query = "SELECT id, email FROM usuarios WHERE tipo_usuario = 'lider'";
$result_lideres = $mysqli->query($lideres_query);

// Obtener trabajadores
$trabajadores_query = "SELECT id, email FROM usuarios WHERE tipo_usuario = 'trabajador'";
$result_trabajadores = $mysqli->query($trabajadores_query);

// Actualizar los datos del proyecto si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $lider_proyecto = $_POST['lider_proyecto'];
    $trabajadores = $_POST['trabajadores'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_termino = $_POST['fecha_termino'];
    $prioridad = $_POST['prioridad'];

    $update_query = "UPDATE proyectos SET nombre = ?, descripcion = ?, lider_proyecto = ?, trabajadores = ?, fecha_inicio = ?, fecha_termino = ?, prioridad = ? WHERE id_proyecto = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param('sssssssi', $nombre, $descripcion, $lider_proyecto, $trabajadores, $fecha_inicio, $fecha_termino, $prioridad, $id_proyecto);

    if ($stmt->execute()) {
        header('Location: proyectos.php?success=Proyecto actualizado correctamente.');
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
    <title>Editar Proyecto</title>
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

        .selected-worker {
            margin-top: 15px;
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 50px;
            background-color: #f1f1f1;
            border-radius: 4px;
        }

        .selected-worker-item {
            margin: 5px 0;
            padding: 5px;
            background-color: #e1e1e1;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: flex;
            align-items: center;
        }

        .remove-worker {
            cursor: pointer;
            color: red;
            font-weight: bold;
            margin-left: 10px;
        }

        .remove-worker:hover {
            color: darkred;
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
            <h1>Editar Proyecto</h1>
        </div>
        <form method="post" action="">
            <label for="nombre">Nombre del Proyecto:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required><br>

            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($descripcion); ?></textarea><br>

            <label for="lider_proyecto">Líder del Proyecto:</label>
            <select id="lider_proyecto" name="lider_proyecto" required>
                <option value="">Seleccione un líder</option>
                <?php while($row = $result_lideres->fetch_assoc()): ?>
                    <option value="<?php echo $row['email']; ?>" <?php echo ($row['email'] == $lider_proyecto) ? 'selected' : ''; ?>><?php echo $row['email']; ?></option>
                <?php endwhile; ?>
            </select><br>

            <label for="trabajadores">Trabajador:</label>
            <select id="trabajadores" onchange="addWorker()">
                <option value="">Seleccione un trabajador</option>
                <?php while($row = $result_trabajadores->fetch_assoc()): ?>
                    <option value="<?php echo $row['email']; ?>"><?php echo $row['email']; ?></option>
                <?php endwhile; ?>
            </select><br>

            <div id="selected-worker" class="selected-worker">
                <?php if ($trabajadores): ?>
                    <?php foreach (explode(',', $trabajadores) as $worker): ?>
                        <div class="selected-worker-item" id="worker-<?php echo htmlspecialchars($worker); ?>">
                            <?php echo htmlspecialchars($worker); ?> <span class="remove-worker" onclick="removeWorker('<?php echo htmlspecialchars($worker); ?>')">x</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <input type="hidden" id="trabajadores-input" name="trabajadores" value="<?php echo htmlspecialchars($trabajadores); ?>"><br>

            <label for="fecha_inicio">Fecha de Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required><br>

            <label for="fecha_termino">Fecha de Término:</label>
            <input type="date" id="fecha_termino" name="fecha_termino" value="<?php echo htmlspecialchars($fecha_termino); ?>" required><br>

            <label for="prioridad">Prioridad:</label>
            <select id="prioridad" name="prioridad" required>
                <option value="">Seleccione la prioridad</option>
                <option value="Alta" <?php echo ($prioridad == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                <option value="Media" <?php echo ($prioridad == 'Media') ? 'selected' : ''; ?>>Media</option>
                <option value="Baja" <?php echo ($prioridad == 'Baja') ? 'selected' : ''; ?>>Baja</option>
            </select><br>

            <div class="buttons-container">
                <button type="button" class="back-button" onclick="window.location.href='proyectos.php'">Regresar</button>
                <button type="submit">Actualizar Proyecto</button>
            </div>
        </form>
    </div>

    <script>
        function addWorker() {
            var workerSelect = document.getElementById('trabajadores');
            var selectedWorker = workerSelect.value;
            var workerInput = document.getElementById('trabajadores-input');

            if (selectedWorker && !document.getElementById('worker-' + selectedWorker)) {
                var workerDiv = document.createElement('div');
                workerDiv.className = 'selected-worker-item';
                workerDiv.id = 'worker-' + selectedWorker;
                workerDiv.innerHTML = selectedWorker + ' <span class="remove-worker" onclick="removeWorker(\'' + selectedWorker + '\')">x</span>';

                document.getElementById('selected-worker').appendChild(workerDiv);

                var workers = workerInput.value.split(',');
                if (workers[0] === '') workers = [];
                workers.push(selectedWorker);
                workerInput.value = workers.join(',');
            }
        }

        function removeWorker(worker) {
            var workerDiv = document.getElementById('worker-' + worker);
            if (workerDiv) {
                workerDiv.parentNode.removeChild(workerDiv);

                var workerInput = document.getElementById('trabajadores-input');
                var workers = workerInput.value.split(',');
                workers = workers.filter(function(item) {
                    return item !== worker;
                });
                workerInput.value = workers.join(',');
            }
        }
    </script>
</body>
</html>

