<?php
session_start();
session_unset();
session_destroy();

// Configurar encabezados para evitar el caché
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1.
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
header("Location: index.html"); // Redirigir al inicio de sesión
exit();
?>
