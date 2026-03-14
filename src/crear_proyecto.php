<?php
// Conectar a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'usuarios');

if ($mysqli->connect_error) {
    die('Error de Conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Obtener líderes de proyecto
$lideres_query = "SELECT id, email FROM usuarios WHERE tipo_usuario = 'lider'";
$result_lideres = $mysqli->query($lideres_query);

// Obtener trabajadores
$trabajadores_query = "SELECT id, email FROM usuarios WHERE tipo_usuario = 'trabajador'";
$result_trabajadores = $mysqli->query($trabajadores_query);

// Procesar el formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $lider_proyecto = $_POST['lider_proyecto'];
    $trabajadores = $_POST['trabajadores'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_termino = $_POST['fecha_termino'];
    $prioridad = $_POST['prioridad'];

    $stmt = $mysqli->prepare("INSERT INTO proyectos (nombre, descripcion, lider_proyecto, trabajadores, fecha_inicio, fecha_termino, prioridad) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssss', $nombre, $descripcion, $lider_proyecto, $trabajadores, $fecha_inicio, $fecha_termino, $prioridad);

    if ($stmt->execute()) {
        header('Location: proyectos.php?success=Proyecto creado correctamente.');
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
    <title>Crear Proyecto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f9f9f9;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        form {
            max-width: 700px;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
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
        }
    </style>
</head>
<body>
    <h1>Crear Proyecto</h1>
    <form method="post" action="">
        <label for="nombre">Nombre del Proyecto:</label>
        <input type="text" id="nombre" name="nombre" required><br>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" required></textarea><br>

        <label for="lider_proyecto">Líder del Proyecto:</label>
        <select id="lider_proyecto" name="lider_proyecto" required>
            <option value="">Seleccione un líder</option>
            <?php while($row = $result_lideres->fetch_assoc()): ?>
                <option value="<?php echo $row['email']; ?>"><?php echo $row['email']; ?></option>
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
            <!-- Trabajadores seleccionados se mostrarán aquí -->
        </div>
        <input type="hidden" id="trabajadores-input" name="trabajadores" value=""><br>

        <label for="fecha_inicio">Fecha de Inicio:</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" required><br>

        <label for="fecha_termino">Fecha de Término:</label>
        <input type="date" id="fecha_termino" name="fecha_termino" required><br>

        <label for="prioridad">Prioridad:</label>
        <select id="prioridad" name="prioridad" required>
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
        </select><br>

        <div class="buttons-container">
            <button type="submit">Crear Proyecto</button>
            <a href="proyectos.php" style="text-decoration: none;">
                <button type="button" style="background-color: #6c757d; border: none;">Regresar</button>
            </a>
        </div>
    </form>

    <script>
        function addWorker() {
            const select = document.getElementById('trabajadores');
            const selectedWorkerEmail = select.value;
            
            if (selectedWorkerEmail) {
                const selectedWorkerDiv = document.getElementById('selected-worker');
                
                // Check if the worker is already in the list
                if (!document.getElementById(`worker-${selectedWorkerEmail}`)) {
                    const workerDiv = document.createElement('div');
                    workerDiv.className = 'selected-worker-item';
                    workerDiv.id = `worker-${selectedWorkerEmail}`;
                    
                    workerDiv.innerHTML = `${selectedWorkerEmail} <span class="remove-worker" onclick="removeWorker('${selectedWorkerEmail}')">x</span>`;
                    selectedWorkerDiv.appendChild(workerDiv);
                }

                // Reset the select field
                select.value = '';
                updateHiddenField();
            }
        }

        function removeWorker(workerEmail) {
            const workerDiv = document.getElementById(`worker-${workerEmail}`);
            if (workerDiv) {
                workerDiv.remove();
                updateHiddenField();
            }
        }

        function updateHiddenField() {
            const selectedWorkerDiv = document.getElementById('selected-worker');
            const workers = Array.from(selectedWorkerDiv.getElementsByClassName('selected-worker-item'))
                                 .map(worker => worker.id.replace('worker-', ''));
            document.getElementById('trabajadores-input').value = workers.join(',');
        }
    </script>
</body>
</html>
