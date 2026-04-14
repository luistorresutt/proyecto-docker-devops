<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['CurrentPassword'] ?? '';
    $newPassword = $_POST['NewPassword'] ?? '';
    $confirmPassword = $_POST['ConfirmPassword'] ?? '';
    $userId = $_SESSION['UserID'];
    if ($newPassword !== $confirmPassword) {
        $errores[] = "La nueva contraseña y la confirmación no coinciden.";
    }
    if (strlen($newPassword) < 6) {
        $errores[] = "La nueva contraseña debe tener al menos 6 caracteres.";
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT PasswordHash FROM Users WHERE Id = ? AND IsActive = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($currentPassword, $user['PasswordHash'])) {
                $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE Users SET PasswordHash = ? WHERE Id = ?");
                
                try {
                    $updateStmt->execute([$newHashedPassword, $userId]);
                    $_SESSION['SuccessMessage'] = "La contraseña fue cambiada exitosamente.";
                    header("Location: index.php");
                    exit;
                    
                } catch (Exception $e) {
                    $errores[] = "Ocurrió un error al guardar la contraseña: " . $e->getMessage();
                }

            } else {
                $errores[] = "La contraseña actual es incorrecta.";
            }
        } else {
            $errores[] = "Usuario no encontrado o inactivo.";
        }
    }
}

require 'layout.php';
?>

<div class="text-center mt-4 mb-4">
    <h5 class="text-uppercase text-muted">Gestión de Cuentas</h5>
    <h2 class="text-uppercase fw-bold">Cambiar Contraseña</h2>
</div>

<div class="container d-flex justify-content-center">
    <div class="card bg-dark text-white p-4 shadow" style="width: 100%; max-width: 500px; border-radius: 15px;">
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <?= implode("<br>", array_map('htmlspecialchars', $errores)) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="change_password.php">
            
            <div class="mb-3">
                <label class="form-label fw-bold d-block text-center">Contraseña Actual</label>
                <div class="input-group mx-auto" style="max-width: 350px;">
                    <input type="password" name="CurrentPassword" class="form-control" required>
                    <span class="input-group-text bg-light" style="cursor: pointer;">
                        <i class="fa fa-eye toggle-password text-dark"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold d-block text-center">Nueva Contraseña</label>
                <div class="input-group mx-auto" style="max-width: 350px;">
                    <input type="password" name="NewPassword" class="form-control" required>
                    <span class="input-group-text bg-light" style="cursor: pointer;">
                        <i class="fa fa-eye toggle-password text-dark"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold d-block text-center">Confirmar Nueva Contraseña</label>
                <div class="input-group mx-auto" style="max-width: 350px;">
                    <input type="password" name="ConfirmPassword" class="form-control" required>
                    <span class="input-group-text bg-light" style="cursor: pointer;">
                        <i class="fa fa-eye toggle-password text-dark"></i>
                    </span>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-light px-4 fw-bold">Cambiar Contraseña</button>
            </div>
        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePasswordIcons = document.querySelectorAll('.toggle-password');

        togglePasswordIcons.forEach(icon => {
            icon.addEventListener('click', function () {
                const passwordField = this.closest('.input-group').querySelector('input');
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    });
</script>

<?php 
require 'footer.php'; 
?>