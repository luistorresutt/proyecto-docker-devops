<?php
// Conexión a la base de datos
$host = 'db';
$db = 'usuarios';
$user = 'usuario';
$pass = 'password';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Sesión y validación
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Datos del usuario activo
$query = "SELECT nombres, apellido_paterno, apellido_materno FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre, $apellido_paterno, $apellido_materno);
$stmt->fetch();
$stmt->close();

$full_name = "{$nombre} {$apellido_paterno} {$apellido_materno}";

// Obtener lista de usuarios
$query = "SELECT id, nombres, apellido_paterno, apellido_materno, email, tipo_usuario FROM usuarios";
$result = $mysqli->query($query);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$result->free();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Usuarios</title>

<style>
/* ---------------- BASE ---------------- */
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    margin: 0;
    background: #f4f7f9;
}

/* ---------------- HEADER ---------------- */
header {
    background: #2c3e50;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 10;
}

/* IZQUIERDA */
.left-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

.left-section img.logo-img {
    height: 45px;
}

.left-section .title {
    font-size: 22px;
    font-weight: bold;
}

/* BOTÓN INICIO */
.home-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #3498db;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-size: 15px;
    font-weight: bold;
}

.home-btn img {
    height: 22px;
}

/* SOLO ICONO */
.home-btn.icon-only {
    width: 45px;
    padding: 8px 10px;
    justify-content: center;
}

/* DERECHA */
.profile {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-right: 40px;
}

.profile img {
    height: 45px;
    width: 45px;
    border-radius: 50%;
    border: 2px solid #fff;
}

/* BOTÓN ROJO X */
.logout-x {
    background: #e74c3c;
    color: white;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 22px;
    font-weight: bold;
    text-decoration: none;
    transition: background 0.3s;
}

.logout-x:hover {
    background: #c0392b;
}

/* ---------------- MAIN ---------------- */
main {
    margin-top: 130px;
    padding: 20px;
}

/* TABLA */
.table-container {
    margin-top: 20px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table, th, td {
    border: 1px solid #ccc;
}

th {
    background: #2c3e50;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    background: #fafafa;
}

tr:nth-child(even) td {
    background: #f1f1f1;
}

/* BOTONES ACCIONES */
.actions-button {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.actions-button a {
    padding: 8px 12px;
    text-decoration: none;
    color: white;
    font-size: 14px;
    border-radius: 5px;
}

.actions-button .edit {
    background: #2ecc71;
}

.actions-button .delete {
    background: #e74c3c;
}

/* BOTÓN FLOTANTE + */
.add-user-button {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 60px;
    height: 60px;
    background: #3498db;
    color: white;
    font-size: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    text-decoration: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

/* ---------------- RESPONSIVE ---------------- */
@media (max-width: 480px) {
    header {
        padding: 10px 15px;
    }

    .left-section img.logo-img {
        height: 35px;
    }

    .title {
        font-size: 18px;
    }

    .profile img {
        height: 35px;
        width: 35px;
    }

    .logout-x {
        width: 32px;
        height: 32px;
        font-size: 18px;
    }
}
</style>
</head>

<body>

<header>
    <div class="left-section">
        <img src="img/CP.png" class="logo-img" alt="Logo">
        <span class="title">Panel de Usuarios</span>

        <!-- BOTÓN DE INICIO SOLO ICONO -->
        <a href="dashboard_admin.php" class="home-btn icon-only">
            <img src="img/hogar.png" alt="Inicio">
        </a>
    </div>

    <div class="profile">
        <img src="img/admin.jpg" alt="Admin">
        <a href="logout.php" class="logout-x">✕</a>
    </div>
</header>

<main>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Apellido Paterno</th>
                    <th>Apellido Materno</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombres']) ?></td>
                    <td><?= htmlspecialchars($u['apellido_paterno']) ?></td>
                    <td><?= htmlspecialchars($u['apellido_materno']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['tipo_usuario']) ?></td>
                    <td class="actions-button">
                        <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="edit">Editar</a>

                        <?php if ($u['id'] != $user_id): ?>
                            <a href="eliminar_usuario.php?id=<?= $u['id'] ?>" class="delete">Eliminar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <a href="crear_usuario.php" class="add-user-button">+</a>
</main>

</body>
</html>
