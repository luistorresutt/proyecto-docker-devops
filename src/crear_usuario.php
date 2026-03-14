<?php
// Conectar a la base de datos
$servername = "db";
$username = "usuario";
$password = "password";
$dbname = "usuarios";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar variables para mantener los valores del formulario
$nombres = $apellido_paterno = $apellido_materno = $correo_electronico = $tipo_usuario = '';
$message = '';
$message_class = '';

// Manejar la solicitud POST para agregar un nuevo usuario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres = $_POST['nombres'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $correo_electronico = $_POST['correo_electronico'];
    $password = $_POST['password'];
    $tipo_usuario = $_POST['tipo_usuario'];

    // Validar que el correo electrónico tenga el dominio adecuado
    if (!preg_match('/@ut-tijuana.edu.mx$/', $correo_electronico)) {
        $message = "El correo electrónico debe tener el dominio @ut-tijuana.edu.mx.";
        $message_class = "error";
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        // Validar la contraseña
        $message = "La contraseña debe tener al menos 8 caracteres, incluyendo una mayúscula y un número.";
        $message_class = "error";
    } else {
        // Verificar si el correo electrónico ya existe en la base de datos
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo_electronico);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "El correo electrónico ya está registrado.";
            $message_class = "error";
        } else {
            // Generar una contraseña encriptada
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insertar el nuevo usuario en la base de datos
            $sql = "INSERT INTO usuarios (nombres, apellido_paterno, apellido_materno, email, password, tipo_usuario)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $nombres, $apellido_paterno, $apellido_materno, $correo_electronico, $hashed_password, $tipo_usuario);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header("Location: usuarios.php"); // Redirigir a usuarios.php
                exit();
            } else {
                $message = "Error al agregar el usuario: " . $stmt->error;
                $message_class = "error";
            }
        }

        $stmt->close();
    }
}

// Cerrar la conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario</title>
    <style>
        /* Estilo general del cuerpo */
        body {
            font-family: Arial, sans-serif;
            background-color: #ecf0f1;
            color: #2c3e50;
            margin: 0;
            padding: 20px;
        }

        /* Estilo para el contenedor del formulario */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            color: #3498db;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        /* Estilo para los mensajes de éxito o error */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
            border: 1px solid transparent;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Estilo para los campos del formulario */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #34495e;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-group input[type="submit"] {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .form-group input[type="submit"]:hover {
            background-color: #2980b9;
        }

        /* Estilo para el botón de regresar */
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Agregar Nuevo Usuario</h2>

        <!-- Mensajes de éxito o error -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_class); ?>">
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario para agregar usuario -->
        <form action="" method="post">
            <div class="form-group">
                <label for="nombres">Nombre:</label>
                <input type="text" id="nombres" name="nombres" value="<?php echo htmlspecialchars($nombres); ?>" required>
            </div>
            <div class="form-group">
                <label for="apellido_paterno">Apellido Paterno:</label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" value="<?php echo htmlspecialchars($apellido_paterno); ?>" required>
            </div>
            <div class="form-group">
                <label for="apellido_materno">Apellido Materno:</label>
                <input type="text" id="apellido_materno" name="apellido_materno" value="<?php echo htmlspecialchars($apellido_materno); ?>" required>
            </div>
            <div class="form-group">
                <label for="correo_electronico">Correo Electrónico:</label>
                <input type="text" id="correo_electronico" name="correo_electronico" value="<?php echo htmlspecialchars($correo_electronico); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="tipo_usuario">Tipo de Usuario:</label>
                <select id="tipo_usuario" name="tipo_usuario" required>
                    <option value="lider" <?php echo ($tipo_usuario == 'lider') ? 'selected' : ''; ?>>Líder</option>
                    <option value="administrador" <?php echo ($tipo_usuario == 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="trabajador" <?php echo ($tipo_usuario == 'trabajador') ? 'selected' : ''; ?>>Trabajador</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="add_user" value="Agregar Usuario">
            </div>
        </form>

        <!-- Botón para regresar -->
        <a href="usuarios.php" class="back-btn">Regresar</a>
    </div>
</body>
</html>
