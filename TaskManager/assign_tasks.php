<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || $_SESSION['RoleName'] !== 'Administrativo') {
    $_SESSION['ErrorMessage'] = "Acceso denegado. Esta oficina es solo para jefes de departamento.";
    header("Location: index.php");
    exit;
}

$deptoId = $_SESSION['DepartmentID'];
$userId = $_SESSION['UserID'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activityId = $_POST['ActivityId'] ?? '';

    if ($action === 'assign') {
        $techId = $_POST['TechId'] ?? '';
        $newName = trim($_POST['Name'] ?? '');
        $newPlan = trim($_POST['SpecificActionPlan'] ?? '');
        $commitmentDate = !empty($_POST['CommitmentDate']) ? $_POST['CommitmentDate'] : null;

        if (empty($techId)) {
            $errores[] = "Debes seleccionar un técnico para asignar la tarea.";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE Activities SET Name = ?, SpecificActionPlan = ?, ResponsibleId = ?, CommitmentDate = ?, StatusId = 2 WHERE Id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$newName, $newPlan, $techId, $commitmentDate, $activityId]);

                $_SESSION['SuccessMessage'] = "Tarea refinada y asignada correctamente.";
                header("Location: assign_tasks.php");
                exit;
            } catch (Exception $e) {
                $errores[] = "Error al asignar: " . $e->getMessage();
            }
        }
    } elseif ($action === 'reject') {
        $rejectReason = trim($_POST['RejectReason'] ?? '');
        
        if (empty($rejectReason)) {
            $errores[] = "Debes proporcionar un motivo para rechazar la solicitud.";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE Activities SET StatusId = 6 WHERE Id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$activityId]);

                $commentId = generar_uuid();
                $sqlComment = "INSERT INTO ActivityComments (Id, ActivityId, UserId, CommentText) VALUES (?, ?, ?, ?)";
                $stmtComment = $pdo->prepare($sqlComment);
                $stmtComment->execute([$commentId, $activityId, $userId, "SOLICITUD RECHAZADA: " . $rejectReason]);

                $_SESSION['SuccessMessage'] = "La solicitud ha sido rechazada y archivada.";
                header("Location: assign_tasks.php");
                exit;
            } catch (Exception $e) {
                $errores[] = "Error al rechazar: " . $e->getMessage();
            }
        }
    }
}

$stmtDept = $pdo->prepare("SELECT Name FROM Departments WHERE Id = ?");
$stmtDept->execute([$deptoId]);
$nombreDepartamento = $stmtDept->fetchColumn();

$sqlPending = "
    SELECT a.Id, a.Folio, a.Name, a.SpecificActionPlan, a.CommitmentDate, p.Name as Prioridad, u.FullName as Solicitante, d.Name as DeptoSolicitante, a.RowVersion as CreatedAt
    FROM Activities a
    JOIN Priorities p ON a.PriorityId = p.Id
    JOIN Users u ON a.RequesterId = u.Id
    JOIN Departments d ON a.RequesterDepartmentId = d.Id
    WHERE a.PrimaryDepartmentId = ? AND a.ResponsibleId IS NULL AND a.StatusId = 1
    ORDER BY p.Id DESC, a.RowVersion ASC
";
$stmt = $pdo->prepare($sqlPending);
$stmt->execute([$deptoId]);
$pendientes = $stmt->fetchAll();

$sqlTechs = "
    SELECT Id, FullName 
    FROM Users 
    WHERE DepartmentId = ? AND (RoleId = 2 OR Id = ?) AND IsActive = 1
";
$stmt = $pdo->prepare($sqlTechs);
$stmt->execute([$deptoId, $userId]);
$tecnicos = $stmt->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-inbox me-2"></i> Tareas solicitadas</h2>
    <p class="text-muted">Filtra, edita y delega las solicitudes del departamento de <b><?= htmlspecialchars($nombreDepartamento) ?></b>.</p>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row">
    <?php if (count($pendientes) > 0): ?>
        <?php foreach ($pendientes as $task): ?>
            <div class="col-md-12 mb-3">
                <div class="card shadow-sm border-0 border-start border-4 <?= $task['Prioridad'] == 'Crítica' ? 'border-danger' : 'border-primary' ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-dark me-2"><?= $task['Folio'] ?></span>
                                    <h5 class="card-title mb-0 fw-bold"><?= htmlspecialchars($task['Name']) ?></h5>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-user-edit me-1"></i> Solicitado por: <b><?= htmlspecialchars($task['Solicitante']) ?></b> (<?= htmlspecialchars($task['DeptoSolicitante']) ?>)
                                    <?php if($task['CommitmentDate']): ?>
                                        <br><i class="fas fa-calendar-alt me-1 text-warning"></i> Fecha requerida por el usuario: <b><?= date('d/m/Y', strtotime($task['CommitmentDate'])) ?></b>
                                    <?php endif; ?>
                                </p>
                                <p class="card-text text-secondary bg-light p-2 rounded border" style="font-size: 0.95rem;">
                                    <?= nl2br(htmlspecialchars($task['SpecificActionPlan'] ?? 'Sin descripción adicional.')) ?>
                                </p>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-outline-danger fw-bold mb-2 w-100" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $task['Id'] ?>">
                                    <i class="fas fa-times-circle me-1"></i> Rechazar
                                </button>
                                <button type="button" class="btn btn-primary fw-bold w-100" data-bs-toggle="modal" data-bs-target="#assignModal<?= $task['Id'] ?>">
                                    <i class="fas fa-edit me-1"></i> Procesar y asignar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-muted small">
                        Recibido el: <?= date('d/m/Y H:i', strtotime($task['CreatedAt'])) ?> | 
                        Prioridad: <span class="fw-bold <?= $task['Prioridad'] == 'Crítica' ? 'text-danger' : 'text-primary' ?>"><?= $task['Prioridad'] ?></span>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="assignModal<?= $task['Id'] ?>" tabindex="-1" aria-labelledby="assignModalLabel<?= $task['Id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="assign_tasks.php">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title fw-bold" id="assignModalLabel<?= $task['Id'] ?>">
                                    Refinar tarea: <?= $task['Folio'] ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="ActivityId" value="<?= $task['Id'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Título de tarea</label>
                                    <input type="text" name="Name" class="form-control" value="<?= htmlspecialchars($task['Name']) ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Asignar a responsable<span class="text-danger">*</span></label>
                                        <select name="TechId" class="form-select" required>
                                            <option value="">-- Selecciona responsable --</option>
                                            <?php foreach ($tecnicos as $t): ?>
                                                <option value="<?= $t['Id'] ?>"><?= htmlspecialchars($t['FullName']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold text-primary">Fecha de Compromiso<span class="text-danger">*</span></label>
                                        <input type="date" name="CommitmentDate" class="form-control border-primary" required min="<?= date('Y-m-d') ?>" value="<?= $task['CommitmentDate'] ? date('Y-m-d', strtotime($task['CommitmentDate'])) : '' ?>">
                                        <div class="form-text">Establece la fecha límite real para el técnico.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Plan de Acción</label>
                                    <textarea name="SpecificActionPlan" class="form-control" rows="4"><?= htmlspecialchars($task['SpecificActionPlan']) ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary fw-bold">Guardar y asignar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="rejectModal<?= $task['Id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="assign_tasks.php">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title fw-bold">Rechazar solicitud: <?= $task['Folio'] ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="ActivityId" value="<?= $task['Id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-danger">Motivo del rechazo</label>
                                    <textarea name="RejectReason" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger fw-bold">Confirmar rechazo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h4>Bandeja al día</h4>
            <p class="text-muted">No tienes solicitudes pendientes por procesar.</p>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>