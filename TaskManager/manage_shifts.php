<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || $_SESSION['RoleName'] !== 'Administrativo') {
    $_SESSION['ErrorMessage'] = "Acceso denegado. Solo jefes pueden gestionar turnos.";
    header("Location: index.php");
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_shift'])) {
    $name = trim($_POST['Name'] ?? '');
    $startTime = $_POST['StartTime'] ?? '';
    $endTime = $_POST['EndTime'] ?? '';
    $pattern = trim($_POST['PatternDescription'] ?? '');

    if (empty($name) || empty($startTime) || empty($endTime)) {
        $errores[] = "El nombre y los horarios son obligatorios.";
    }

    if (empty($errores)) {
        try {
            $newId = generar_uuid();
            $sql = "INSERT INTO Shifts (Id, Name, StartTime, EndTime, PatternDescription, IsActive) VALUES (?, ?, ?, ?, ?, 1)";
            $pdo->prepare($sql)->execute([$newId, $name, $startTime, $endTime, $pattern]);
            
            $_SESSION['SuccessMessage'] = "Turno creado exitosamente.";
            header("Location: manage_shifts.php");
            exit;
        } catch (Exception $e) {
            $errores[] = "Error al guardar el turno: " . $e->getMessage();
        }
    }
}

$sqlShifts = "SELECT s.*, 
             (SELECT COUNT(*) FROM Users u WHERE u.ShiftId = s.Id AND u.IsActive = 1) as TotalUsers 
             FROM Shifts s ORDER BY s.StartTime ASC";
$turnos = $pdo->query($sqlShifts)->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-dark"><i class="fas fa-clock me-2"></i> Gestión de Turnos</h2>
            <p class="text-muted mb-0">Administra los horarios oficiales de la planta y visualiza el personal asignado.</p>
        </div>
        <button type="button" class="btn btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newShiftModal">
            <i class="fas fa-plus me-1"></i> Nuevo Turno
        </button>
    </div>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <?php foreach ($turnos as $turno): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100 border-start border-4 <?= $turno['IsActive'] ? 'border-dark' : 'border-secondary' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($turno['Name']) ?></h5>
                        <span class="badge <?= $turno['IsActive'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $turno['IsActive'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-light text-dark border fs-6">
                            <i class="fas fa-sun me-1 text-warning"></i> <?= date('h:i A', strtotime($turno['StartTime'])) ?>
                        </span>
                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                        <span class="badge bg-light text-dark border fs-6">
                            <i class="fas fa-moon me-1 text-primary"></i> <?= date('h:i A', strtotime($turno['EndTime'])) ?>
                        </span>
                    </div>

                    <p class="small text-muted mb-4" style="height: 40px;">
                        <?= htmlspecialchars($turno['PatternDescription'] ?? 'Sin esquema definido.') ?>
                    </p>

                    <hr class="text-muted opacity-25">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary fw-bold small">
                            <i class="fas fa-users me-1"></i> Personal asignado:
                        </span>
                        <span class="badge <?= $turno['TotalUsers'] > 0 ? 'bg-primary' : 'bg-secondary' ?> fs-6 rounded-pill px-3">
                            <?= $turno['TotalUsers'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="newShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_shifts.php">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Crear Nuevo Turno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="create_shift" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Turno</label>
                        <input type="text" name="Name" class="form-control" required placeholder="Ej. Turno Administrativo">
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Hora Entrada</label>
                            <input type="time" name="StartTime" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Hora Salida</label>
                            <input type="time" name="EndTime" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Esquema / Descripción</label>
                        <input type="text" name="PatternDescription" class="form-control" placeholder="Ej. Lunes a Viernes de 8am a 5pm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark fw-bold">Guardar Turno</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>