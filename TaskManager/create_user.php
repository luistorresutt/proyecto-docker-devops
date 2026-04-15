<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || $_SESSION['RoleName'] !== 'Administrativo') {
    $_SESSION['ErrorMessage'] = "No tienes permisos para acceder a esta página.";
    header("Location: index.php");
    exit;
}

$errores = [];
$usuarioCreado = null;
$passwordGenerada = null;

$deptos = $pdo->query("SELECT Id, Name FROM departments WHERE IsDeleted = 0")->fetchAll();
$roles = $pdo->query("SELECT Id, Name FROM roles")->fetchAll();
$turnos = $pdo->query("SELECT Id, Name FROM shifts WHERE IsActive = 1 ORDER BY StartTime")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['FullName'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $jobTitle = trim($_POST['JobTitle'] ?? ''); 
    $roleId = $_POST['RoleId'] ?? '';
    $deptoId = $_POST['DepartmentId'] ?? '';
    
    $supervisorId = (!empty($_POST['SupervisorId']) && $_POST['SupervisorId'] !== 'NULL') ? $_POST['SupervisorId'] : null;
    $shiftId = (!empty($_POST['ShiftId']) && $_POST['ShiftId'] !== 'NULL') ? $_POST['ShiftId'] : null;
    
    $stmtRole = $pdo->prepare("SELECT Name FROM roles WHERE Id = ?");
    $stmtRole->execute([$roleId]);
    $selectedRoleName = $stmtRole->fetchColumn();

    if ($selectedRoleName === 'Tecnico' && empty($supervisorId)) {
        $errores[] = "Un usuario con rol Técnico debe tener asignado un Supervisor obligatoriamente.";
    }

    if ($selectedRoleName === 'Tecnico' && empty($shiftId)) {
        $errores[] = "Un usuario con rol Técnico debe tener asignado un Turno de trabajo.";
    }

    if (empty($jobTitle)) {
        $errores[] = "El campo de Puesto es obligatorio.";
    }

    $stmtEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
    $stmtEmail->execute([$email]);
    if ($stmtEmail->fetchColumn() > 0) {
        $errores[] = "Ya existe un usuario con ese correo.";
    }

    if (empty($errores)) {
        $defaultPassword = "innovitech2026*";
        $hashedPassword = password_hash($defaultPassword, PASSWORD_BCRYPT);
        $newId = generar_uuid(); 
        
        $sql = "INSERT INTO users (Id, DepartmentId, RoleId, SupervisorId, ShiftId, JobTitle, FullName, Email, PasswordHash, IsActive) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([$newId, $deptoId, $roleId, $supervisorId, $shiftId, $jobTitle, $fullName, $email, $hashedPassword]);

            $usuarioCreado = [
                'FullName' => $fullName,
                'Email' => $email,
                'JobTitle' => $jobTitle
            ];
            $passwordGenerada = $defaultPassword;
        } catch (Exception $e) {
            $errores[] = "Error al crear el usuario: " . $e->getMessage();
        }
    }
}

require 'layout.php';
?>

<div class="text-center mt-4 mb-4">
    <h5 class="text-uppercase text-muted">Gestión de Cuentas</h5>
    <h2 class="text-uppercase fw-bold">Creación de Usuario</h2>
</div>

<div class="container d-flex justify-content-center">
    <div class="card bg-dark text-white p-4 shadow" style="width: 100%; max-width: 500px; border-radius: 15px;">
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <?= implode("<br>", array_map('htmlspecialchars', $errores)) ?>
            </div>
        <?php endif; ?>

        <?php if ($usuarioCreado): ?>
            <div class="text-center">
                <h4 class="text-success mb-4">Usuario creado exitosamente</h4>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($usuarioCreado['FullName']) ?></p>
                <p><strong>Puesto:</strong> <?= htmlspecialchars($usuarioCreado['JobTitle']) ?></p>
                <p><strong>Correo:</strong> <?= htmlspecialchars($usuarioCreado['Email']) ?></p>
                <p><strong>Contraseña temporal:</strong> <span class="text-warning fs-5 fw-bold"><?= $passwordGenerada ?></span></p>
                <a href="create_user.php" class="btn btn-light mt-3">Crear otro usuario</a>
            </div>
        <?php else: ?>
            <form method="POST" action="create_user.php">
                <div class="mb-3 text-center">
                    <label class="form-label fw-bold">Nombre completo</label>
                    <input type="text" name="FullName" class="form-control"  placeholder="Nombre(s) Apellidos" required value="<?= isset($_POST['FullName']) ? htmlspecialchars($_POST['FullName']) : '' ?>">
                </div>
                
                <div class="mb-3 text-center">
                    <label class="form-label fw-bold">Puesto</label>
                    <input type="text" name="JobTitle" class="form-control" placeholder="Ej. Senior IT, Técnico Mecánico A, Gerente de RH" required value="<?= isset($_POST['JobTitle']) ? htmlspecialchars($_POST['JobTitle']) : '' ?>">
                </div>

                <div class="mb-3 text-center">
                    <label class="form-label fw-bold">Correo electrónico</label>
                    <input type="email" name="Email" class="form-control" required value="<?= isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : '' ?>">
                </div>

                <div class="mb-3 text-center">
                    <label class="form-label fw-bold">Departamento</label>
                    <select name="DepartmentId" id="DepartmentSelect" class="form-select" required onchange="loadSupervisors()">
                        <option value="">--Seleccione departamento--</option>
                        <?php foreach($deptos as $d): ?>
                            <option value="<?= $d['Id'] ?>" <?= (isset($_POST['DepartmentId']) && $_POST['DepartmentId'] == $d['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4 text-center" id="SupervisorDiv">
                    <label class="form-label fw-bold">Asignar supervisor <span class="text-danger">*</span></label>
                    <select name="SupervisorId" id="SupervisorSelect" class="form-select" required>
                        <option value="">--Seleccione departamento primero--</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3 text-center">
                        <label class="form-label fw-bold">Rol en el Sistema</label>
                        <select name="RoleId" id="RoleSelect" class="form-select" required onchange="toggleShiftRequirement()">
                            <option value="">--Seleccione rol--</option>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['Id'] ?>" data-rolename="<?= htmlspecialchars($r['Name']) ?>" <?= (isset($_POST['RoleId']) && $_POST['RoleId'] == $r['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3 text-center">
                        <label class="form-label fw-bold">Turno de Trabajo</label>
                        <select name="ShiftId" id="ShiftSelect" class="form-select">
                            <option value="NULL" class="text-muted">-- Sin turno fijo / N/A --</option>
                            <?php foreach($turnos as $t): ?>
                                <option value="<?= $t['Id'] ?>" <?= (isset($_POST['ShiftId']) && $_POST['ShiftId'] == $t['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-secondary w-100 mt-2">Crear usuario</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    
    function loadSupervisors() {
        var deptId = document.getElementById('DepartmentSelect').value;
        var supervisorSelect = document.getElementById('SupervisorSelect');
        
        supervisorSelect.innerHTML = '<option value="">-- Seleccione Supervisor --</option>';
        supervisorSelect.innerHTML += '<option value="NULL" class="fw-bold text-primary">-- Sin supervisor (Gerencia / N/A) --</option>';
        
        if (deptId === "") return;

        fetch('get_supervisors.php?deptId=' + encodeURIComponent(deptId))
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(function(sup) {
                        var option = document.createElement('option');
                        option.value = sup.Id;
                        option.textContent = sup.FullName;
                        supervisorSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error fetching supervisors:', error));
    }

    function toggleShiftRequirement() {
        var roleSelect = document.getElementById('RoleSelect');
        var shiftSelect = document.getElementById('ShiftSelect');
        var selectedOption = roleSelect.options[roleSelect.selectedIndex];
        
        if (selectedOption && selectedOption.getAttribute('data-rolename') === 'Tecnico') {
            shiftSelect.required = true;
            if (shiftSelect.value === 'NULL') {
                shiftSelect.value = ''; 
            }
        } else {
            shiftSelect.required = false;
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('DepartmentSelect').value !== "") {
            loadSupervisors();
        }
        if(document.getElementById('RoleSelect').value !== "") {
            toggleShiftRequirement();
        }
    });
</script>

<?php require 'footer.php'; ?>