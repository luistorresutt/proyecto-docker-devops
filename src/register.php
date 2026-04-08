<?php
$servername = "db";
$username = "usuario";
$password = "password";
$dbname = "proyecto";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar mensaje y valores por defecto
$message = "";
$nombres = $apellido_paterno = $apellido_materno = $email = "";

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $nombres = $_POST['nombres'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validar dominio del correo electrónico
    if (!preg_match('/@ut-tijuana\.edu\.mx$/', $email)) {
        $message = 'El correo debe de ser institucional @ut-tijuana.edu.mx.';
    } else {
        // Verificar si el correo electrónico ya existe
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = 'El correo electrónico ya está registrado.';
        } else {
            // Validar contraseña
            if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
                $message = 'La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.';
            } else {
                // Hashear la contraseña
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Insertar datos en la base de datos
                $sql = "INSERT INTO usuarios (nombres, apellido_paterno, apellido_materno, email, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $nombres, $apellido_paterno, $apellido_materno, $email, $password_hash);

                if ($stmt->execute()) {
                    // Redirigir a una página de éxito
                    header("Location: register_success.html");
                    exit();
                } else {
                    $message = "Error: " . $stmt->error;
                }
            }
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <style>
        /* Estilos generales */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
            background: #f0f0f0;
            position: relative;
        }

        /* Fondo animado */
        .area {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(to left, #8f94fb, #4e54c8);
        }

        .circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .circles li {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }

        .circles li:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .circles li:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .circles li:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .circles li:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .circles li:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .circles li:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .circles li:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .circles li:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .circles li:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .circles li:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

        @keyframes animate {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; border-radius: 0; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Contenedor principal */
        .content-container {
            position: relative;
            z-index: 10; /* por encima de los círculos */
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .image-container {
            width: 80%;
            max-width: 300px;
            height: 150px;
            background: url('img/CP.png') no-repeat center center;
            background-size: contain;
            margin: 20px 0;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
            animation: fadeInUp 0.6s ease-in-out;
            box-sizing: border-box;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            font-size: 28px;
            color: #333;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s, transform 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        a {
            color: #ff6f00;
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: #e64a19;
            text-decoration: underline;
        }

        p {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .form-container {
                width: 90%;
            }
            .image-container {
                height: 120px;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                width: 100%;
                padding: 20px;
            }
            .image-container {
                height: 100px;
            }
            h2 { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="area"></div>
    <ul class="circles">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul>

    <div class="content-container">
        <div class="image-container"></div>

        <div class="form-container">
            <h2>Registro de Usuario</h2>
            <?php if (!empty($message)) : ?>
                <p class="error"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <label for="nombres">Nombres:</label>
                <input type="text" id="nombres" name="nombres" value="<?php echo htmlspecialchars($nombres); ?>" required>

                <label for="apellido_paterno">Apellido Paterno:</label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" value="<?php echo htmlspecialchars($apellido_paterno); ?>" required>

                <label for="apellido_materno">Apellido Materno:</label>
                <input type="text" id="apellido_materno" name="apellido_materno" value="<?php echo htmlspecialchars($apellido_materno); ?>" required>

                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" pattern="[a-zA-Z0-9._%+-]+@ut-tijuana\.edu\.mx" title="Debe ser un correo electrónico válido con dominio @ut-tijuana.edu.mx" value="<?php echo htmlspecialchars($email); ?>" required>

                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>

                <input type="submit" value="Registrar">
            </form>
            <p>¿Ya tienes una cuenta? <a href="index.html">Inicia sesión aquí</a></p>
        </div>
    </div>
</body>
</html>
