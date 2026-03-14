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

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Consultar información del usuario para mostrar en la confirmación
    $query = "SELECT nombres FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['confirm'])) {
            // Eliminar el usuario de la base de datos
            $delete_query = "DELETE FROM usuarios WHERE id = ?";
            $delete_stmt = $mysqli->prepare($delete_query);
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();

            // Redirigir a usuarios.php después de eliminar
            header("Location: usuarios.php");
            exit();
        }
    } else {
        die('Usuario no encontrado.');
    }
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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f7f9;
        }

        .modal {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
        }

        .modal h2 {
            margin-top: 0;
        }

        .modal button {
            margin: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .modal .confirm {
            background-color: #e74c3c;
            color: white;
        }

        .modal .cancel {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="modal">
        <h2>¿Estás seguro de que quieres eliminar a <strong><?php echo htmlspecialchars($user['nombres']); ?></strong>?</h2>
        <form action="eliminar_usuario.php" method="GET">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_id); ?>">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="confirm">Sí, eliminar</button>
            <a href="usuarios.php"><button type="button" class="cancel">Cancelar</button></a>
        </form>
    </div>
</body>
</html>
