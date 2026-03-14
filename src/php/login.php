<?php
session_start();
$servername = "db";
$username = "usuario";
$password = "password";
$dbname = "usuarios";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos del formulario
$email = $_POST['email'];
$password = $_POST['password'];

// Buscar el usuario en la base de datos
$sql = "SELECT * FROM usuarios WHERE email='$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['nombres'];
        $_SESSION['user_type'] = $row['tipo_usuario']; // Agregar tipo de usuario a la sesión
        // Redirigir a la página de bienvenida
        header("Location: ../welcome.php");
        exit();
    } else {
        // Redirigir a la página de inicio de sesión con un mensaje de error
        header("Location: ../login.html?error=incorrect_password");
        exit();
    }
} else {
    // Redirigir a la página de inicio de sesión con un mensaje de error
    header("Location: ../login.html?error=user_not_found");
    exit();
}

$conn->close();
?>
