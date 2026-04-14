<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if (isset($_GET['deptId']) && !empty($_GET['deptId'])) {
    $deptId = $_GET['deptId'];

    $stmt = $pdo->prepare("
        SELECT u.Id, u.FullName 
        FROM Users u 
        JOIN Roles r ON u.RoleId = r.Id 
        WHERE r.Name = 'Administrativo' AND u.IsActive = 1 AND u.DepartmentId = ?
    ");
    $stmt->execute([$deptId]);
    $supervisores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($supervisores);
} else {
    echo json_encode([]);
}