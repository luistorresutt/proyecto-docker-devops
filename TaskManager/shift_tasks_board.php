<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserID'];
$deptoId = $_SESSION['DepartmentID'];
$roleName = $_SESSION['RoleName'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['take_task'])) {
    $taskId = $_POST['TaskId'];

    try {
        $pdo->beginTransaction();

        $sqlTake = "UPDATE Activities SET ResponsibleId = ?, StatusId = 3 WHERE Id = ? AND ResponsibleId IS NULL";
        $stmt = $pdo->prepare($sqlTake);
        $stmt->execute([$userId, $taskId]);

        if ($stmt->rowCount() > 0) {
            $commentId = generar_uuid();
            $pdo->prepare("INSERT INTO ActivityComments (Id, ActivityId, UserId, CommentText) VALUES (?, ?, ?, ?)")
                ->execute([$commentId, $taskId, $userId, "ACCIÓN: El técnico tomó esta tarea desde la Pizarra del Turno."]);
            
            $pdo->commit();
            $_SESSION['SuccessMessage'] = "¡Tarea asignada con éxito! Ya puedes reportar tus avances.";
            
            header("Location: task_details.php?id=" . $taskId);
            exit;
        } else {
            $pdo->rollBack();
            $errores[] = "Lo sentimos, otro compañero acaba de tomar esta tarea.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $errores[] = "Error al tomar la tarea: " . $e->getMessage();
    }
}

$sqlCurrentShift = "SELECT Name, StartTime, EndTime FROM Shifts 
                    WHERE IsActive = 1 
                    AND (
                        (StartTime < EndTime AND CURTIME() BETWEEN StartTime AND EndTime)
                        OR 
                        (StartTime >= EndTime AND (CURTIME() >= StartTime OR CURTIME() <= EndTime))
                    ) LIMIT 1";
$turnoActual = $pdo->query($sqlCurrentShift)->fetch();

$sqlTasks = "SELECT a.Id, a.Folio, a.Name, a.RowVersion as CreatedAt, p.Name as Prioridad, d.Name as DeptoSolicitante
             FROM Activities a
             JOIN Priorities p ON a.PriorityId = p.Id
             LEFT JOIN Departments d ON a.RequesterDepartmentId = d.Id
             WHERE a.PrimaryDepartmentId = ? AND a.ResponsibleId IS NULL AND a.StatusId = 2
             ORDER BY p.Id DESC, a.RowVersion ASC";
$stmtTasks = $pdo->prepare($sqlTasks);
$stmtTasks->execute([$deptoId]);
$tareasDisponibles = $stmtTasks->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark"><i class="fas fa-clipboard-list me-2"></i> Pizarra del Turno</h2>
            <p class="text-muted mb-0">Tareas pendientes en tu departamento listas para ser tomadas.</p>
        </div>
        
        <?php if ($turnoActual): ?>
            <div class="bg-dark text-white px-4 py-2 rounded shadow-sm border-start border-4 border-success d-flex align-items-center">
                <i class="fas fa-clock fa-2x me-3 text-success"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-uppercase"><?= htmlspecialchars($turnoActual['Name']) ?></h6>
                    <small class="text-light opacity-75">
                        <?= date('h:i A', strtotime($turnoActual['StartTime'])) ?> - <?= date('h:i A', strtotime($turnoActual['EndTime'])) ?>
                    </small>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-light text-muted px-4 py-2 rounded border-start border-4 border-secondary d-flex align-items-center">
                <i class="fas fa-power-off fa-2x me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">Fuera de Horario</h6>
                    <small>No hay un turno configurado a esta hora.</small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row g-4 mb-5 mt-2">
    <?php if (count($tareasDisponibles) > 0): ?>
        <?php foreach ($tareasDisponibles as $t): ?>
            
            <?php 
                $esRutina = strpos($t['Folio'], 'RUT-') === 0;
                $borderColor = $esRutina ? 'border-primary' : 'border-warning';
                $badgeColor = $t['Prioridad'] == 'Crítica' ? 'bg-danger' : ($t['Prioridad'] == 'Alta' ? 'bg-warning text-dark' : 'bg-secondary');
            ?>

            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm border-0 h-100 border-top border-4 <?= $borderColor ?> transition-hover">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-dark fs-6"><?= $t['Folio'] ?></span>
                            <span class="badge <?= $badgeColor ?> px-2 py-1"><?= $t['Prioridad'] ?></span>
                        </div>
                        
                        <h5 class="fw-bold text-dark mt-2 mb-1"><?= htmlspecialchars($t['Name']) ?></h5>
                        
                        <div class="mb-3 mt-2">
                            <?php if($esRutina): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                    <i class="fas fa-calendar-check me-1"></i> Mantenimiento Preventivo
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle">
                                    <i class="fas fa-user-tag me-1"></i> Solicitud de: <?= htmlspecialchars($t['DeptoSolicitante']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center text-muted small mt-auto mb-3">
                            <i class="far fa-clock me-2"></i> Generada: <?= date('d/m/Y H:i', strtotime($t['CreatedAt'])) ?>
                        </div>

                        <form method="POST" action="shift_tasks_board.php">
                            <input type="hidden" name="take_task" value="1">
                            <input type="hidden" name="TaskId" value="<?= $t['Id'] ?>">
                            <button type="submit" class="btn btn-outline-dark fw-bold w-100 btn-lg shadow-sm">
                                <i class="fas fa-hand-paper me-2"></i> Tomar Tarea
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <div class="bg-white p-5 rounded shadow-sm">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-mug-hot fa-2x"></i>
                </div>
                <h3 class="fw-bold text-dark">Pizarra limpia</h3>
                <p class="text-muted fs-5">No hay tareas pendientes en la bandeja de tu departamento.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .transition-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require 'footer.php'; ?>