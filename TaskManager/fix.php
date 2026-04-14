<?php
require_once 'conexion.php';

$passwordPlana = "innovitech2026*"; 

$passwordEncriptada = password_hash($passwordPlana, PASSWORD_BCRYPT);

try {
    $sql = "UPDATE Users SET PasswordHash = ? WHERE PasswordHash IS NULL OR PasswordHash = ''";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$passwordEncriptada]);

    echo "<h2>¡Éxito!</h2>";
    echo "Las contraseñas fueron actualizadas.<br>";
    echo "Ahora puedes iniciar sesión con los usuarios sin contraseña.<br>";
    echo "La contraseña es: <b>" . $passwordPlana . "</b>";

} catch (Exception $e) {
    echo "Hubo un error: " . $e->getMessage();
}
?>