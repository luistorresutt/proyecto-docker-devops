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

$errors = [];
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Obtener datos del usuario
    $query = "SELECT nombres, apellido_paterno, apellido_materno, email, tipo_usuario FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($nombres, $apellido_paterno, $apellido_materno, $email, $tipo_usuario);
    $stmt->fetch();
    $stmt->close();

    // Actualizar datos del usuario
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombres = $_POST['nombres'];
        $apellido_paterno = $_POST['apellido_paterno'];
        $apellido_materno = $_POST['apellido_materno'];
        $email = $_POST['email'];
        $tipo_usuario = $_POST['tipo_usuario'];
        $password = $_POST['password'];

        // Validar correo electrónico
        if (strpos($email, '@ut-tijuana.edu.mx') === false) {
            $errors[] = 'El correo electrónico debe ser del dominio @ut-tijuana.edu.mx';
        } else {
            // Verificar si el correo electrónico ya está en uso por otro usuario
            $query = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            if ($count > 0) {
                $errors[] = 'El correo electrónico ya está en uso por otro usuario.';
            }
        }

        // Validar contraseña
        if (!empty($password)) {
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.';
            } else {
                // Hash de la contraseña
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
            }
        }

        if (empty($errors)) {
            // Actualizar datos del usuario
            $query = "UPDATE usuarios SET nombres = ?, apellido_paterno = ?, apellido_materno = ?, email = ?, tipo_usuario = ?" . (!empty($password) ? ", password = ?" : "") . " WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            if (!empty($password)) {
                $stmt->bind_param("ssssssi", $nombres, $apellido_paterno, $apellido_materno, $email, $tipo_usuario, $password_hash, $user_id);
            } else {
                $stmt->bind_param("sssssi", $nombres, $apellido_paterno, $apellido_materno, $email, $tipo_usuario, $user_id);
            }
            $stmt->execute();
            $stmt->close();
            header("Location: usuarios.php");
            exit();
        }
    }
} else {
    header("Location: usuarios.php");
    exit();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f7f9;
        }

        form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
        }

        .btn-link {
            text-decoration: none;
        }
    </style>
</head>
<body>
<form method="post">
    <h2>Editar Usuario</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <label for="nombres">Nombres:</label>
    <input type="text" id="nombres" name="nombres" value="<?php echo htmlspecialchars($nombres); ?>" required>

    <label for="apellido_paterno">Apellido Paterno:</label>
    <input type="text" id="apellido_paterno" name="apellido_paterno" value="<?php echo htmlspecialchars($apellido_paterno); ?>" required>

    <label for="apellido_materno">Apellido Materno:</label>
    <input type="text" id="apellido_materno" name="apellido_materno" value="<?php echo htmlspecialchars($apellido_materno); ?>" required>

    <label for="email">Correo Electrónico:</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

    <label for="tipo_usuario">Tipo de Usuario:</label>
    <select id="tipo_usuario" name="tipo_usuario" required>
        <option value="lider" <?php echo $tipo_usuario == 'lider' ? 'selected' : ''; ?>>Líder</option>
        <option value="administrador" <?php echo $tipo_usuario == 'administrador' ? 'selected' : ''; ?>>Administrador</option>
        <option value="trabajador" <?php echo $tipo_usuario == 'trabajador' ? 'selected' : ''; ?>>Trabajador</option>
    </select>

    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="password" placeholder="Ingrese nueva contraseña (mínimo 8 caracteres, 1 mayúscula y 1 número)">

    <div class="btn-container">
        <button type="submit" class="btn">Guardar Cambios</button>
        <a href="usuarios.php" class="btn btn-link">Regresar</a>
    </div>
</form>
</body>
</html>
