<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || $_SESSION['RoleName'] !== 'Administrativo') {
    $_SESSION['ErrorMessage'] = "Acceso denegado.";
    header("Location: index.php");
    exit;
}

$deptoId = $_SESSION['DepartmentID'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_template'])) {
    $name = trim($_POST['Name'] ?? '');
    $plan = trim($_POST['SpecificActionPlan'] ?? '');
    $priorityId = $_POST['PriorityId'] ?? '';
    $recurrence = $_POST['RecurrenceType'] ?? ''; 
    $shifts = $_POST['TargetShifts'] ?? 'Todos los turnos'; 
    $nextRun = date('Y-m-d', strtotime('+1 day')); 

    if (empty($name) || empty($priorityId) || empty($recurrence)) {
        $errores[] = "Campos obligatorios faltantes.";
    } else {
        try {
            $newId = generar_uuid();
            $sql = "INSERT INTO activitytemplates (Id, Name, SpecificActionPlan, PrimaryDepartmentId, PriorityId, RecurrenceType, TargetShifts, NextRunDate, IsActive) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $pdo->prepare($sql)->execute([$newId, $name, $plan, $deptoId, $priorityId, $recurrence, $shifts, $nextRun]);
            $_SESSION['SuccessMessage'] = "Rutina creada.";
            header("Location: manage_tasks_templates.php");
            exit;
        } catch (Exception $e) { $errores[] = "Error: " . $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_template'])) {
    $templateId = $_POST['TemplateId'];
    $name = trim($_POST['Name'] ?? '');
    $plan = trim($_POST['SpecificActionPlan'] ?? '');
    $priorityId = $_POST['PriorityId'] ?? '';
    $recurrence = $_POST['RecurrenceType'] ?? ''; 
    $shifts = $_POST['TargetShifts'] ?? '';

    if (empty($name) || empty($priorityId) || empty($recurrence)) {
        $errores[] = "No puedes dejar campos obligatorios vacíos.";
    } else {
        try {
            $sqlUpd = "UPDATE activitytemplates SET 
                       Name = ?, SpecificActionPlan = ?, PriorityId = ?, 
                       RecurrenceType = ?, TargetShifts = ? 
                       WHERE Id = ? AND PrimaryDepartmentId = ?";
            $pdo->prepare($sqlUpd)->execute([$name, $plan, $priorityId, $recurrence, $shifts, $templateId, $deptoId]);
            
            $_SESSION['SuccessMessage'] = "Rutina actualizada correctamente.";
            header("Location: manage_tasks_templates.php");
            exit;
        } catch (Exception $e) { $errores[] = "Error al actualizar: " . $e->getMessage(); }
    }
}

if (isset($_GET['toggle_id'])) {
    try {
        $pdo->prepare("UPDATE activitytemplates SET IsActive = NOT IsActive WHERE Id = ? AND PrimaryDepartmentId = ?")
            ->execute([$_GET['toggle_id'], $deptoId]);
        header("Location: manage_tasks_templates.php");
        exit;
    } catch (Exception $e) { $errores[] = "Error."; }
}

$prioridades = $pdo->query("SELECT Id, Name FROM priorities ORDER BY Id")->fetchAll();
$turnosActivos = $pdo->query("SELECT Name FROM shifts WHERE IsActive = 1 ORDER BY StartTime")->fetchAll(PDO::FETCH_COLUMN);

$sqlTemplates = "SELECT t.*, p.Name as Prioridad FROM activitytemplates t 
                 JOIN priorities p ON t.PriorityId = p.Id 
                 WHERE t.PrimaryDepartmentId = ? ORDER BY t.IsActive DESC, t.Name ASC";
$stmt = $pdo->prepare($sqlTemplates);
$stmt->execute([$deptoId]);
$plantillas = $stmt->fetchAll();

$sqlHistory = "SELECT a.Id, a.Folio, a.Name, a.CommitmentDate, a.CompletedDate, a.ProgressPercentage, s.Name as Estado, u.FullName as Responsable
               FROM activities a
               JOIN statuses s ON a.StatusId = s.Id
               LEFT JOIN users u ON a.ResponsibleId = u.Id
               WHERE a.PrimaryDepartmentId = ? 
               AND a.Name LIKE CONCAT(?, '%') 
               AND a.TaskTypeId = (SELECT Id FROM tasktypes WHERE Name = 'Mantenimiento Preventivo' LIMIT 1)
               ORDER BY a.CommitmentDate DESC LIMIT 15";
$stmtHist = $pdo->prepare($sqlHistory);

require 'layout.php';
?>

<div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold text-dark"><i class="fas fa-calendar-alt me-2"></i> Rutinas programadas</h2>
        <p class="text-muted mb-0">Gestión de mantenimientos preventivos y tareas recurrentes.</p>
    </div>
    <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newTemplateModal">
        <i class="fas fa-plus me-1"></i> Nueva rutina
    </button>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <?php foreach ($plantillas as $tpl): ?>
        <?php 
            $stmtHist->execute([$deptoId, $tpl['Name']]);
            $historialRutina = $stmtHist->fetchAll();
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100 <?= $tpl['IsActive'] ? 'border-start border-4 border-primary' : 'bg-light text-muted' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($tpl['Name']) ?></h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#historyModal<?= $tpl['Id'] ?>"><i class="fas fa-history me-2 text-info"></i> Ver historial</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editModal<?= $tpl['Id'] ?>"><i class="fas fa-edit me-2 text-primary"></i> Editar rutina</a></li>
                                <li><a class="dropdown-item" href="manage_tasks_templates.php?toggle_id=<?= $tpl['Id'] ?>"><i class="fas <?= $tpl['IsActive'] ? 'fa-pause text-warning' : 'fa-play text-success' ?> me-2"></i> <?= $tpl['IsActive'] ? 'Pausar rutina' : 'Reactivar rutina' ?></a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <span class="badge bg-light text-primary border me-1"><i class="fas fa-redo me-1"></i> <?= $tpl['RecurrenceType'] ?></span>
                        <span class="badge bg-dark text-white"><i class="fas fa-user-clock me-1"></i> <?= $tpl['TargetShifts'] ?></span>
                    </div>

                    <div class="bg-white border rounded p-2 mb-3 small" style="height: 60px; overflow-y: auto;">
                        <?= nl2br(htmlspecialchars($tpl['SpecificActionPlan'])) ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto small fw-bold">
                        <span class="text-secondary"><i class="fas fa-exclamation-circle me-1"></i> <?= $tpl['Prioridad'] ?></span>
                        <span class="text-muted">Siguiente: <?= date('d/m/Y', strtotime($tpl['NextRunDate'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal<?= $tpl['Id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold">Editar rutina: <?= htmlspecialchars($tpl['Name']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-light">
                            <input type="hidden" name="update_template" value="1">
                            <input type="hidden" name="TemplateId" value="<?= $tpl['Id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Título de la rutina</label>
                                <input type="text" name="Name" class="form-control" required value="<?= htmlspecialchars($tpl['Name']) ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Prioridad</label>
                                    <select name="PriorityId" class="form-select" required>
                                        <?php foreach ($prioridades as $p): ?>
                                            <option value="<?= $p['Id'] ?>" <?= $tpl['PriorityId'] == $p['Id'] ? 'selected' : '' ?>><?= $p['Name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Frecuencia</label>
                                    <select name="RecurrenceType" class="form-select" required>
                                        <option value="Diaria" <?= $tpl['RecurrenceType'] == 'Diaria' ? 'selected' : '' ?>>Diaria</option>
                                        <option value="Semanal" <?= $tpl['RecurrenceType'] == 'Semanal' ? 'selected' : '' ?>>Semanal</option>
                                        <option value="Mensual" <?= $tpl['RecurrenceType'] == 'Mensual' ? 'selected' : '' ?>>Mensual</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Aplicar a</label>
                                    <select name="TargetShifts" class="form-select" required>
                                        <option value="Todos los turnos" <?= $tpl['TargetShifts'] == 'Todos los turnos' ? 'selected' : '' ?>>Todos los turnos</option>
                                        <?php foreach ($turnosActivos as $tName): ?>
                                            <option value="<?= htmlspecialchars($tName) ?>" <?= $tpl['TargetShifts'] == $tName ? 'selected' : '' ?>>Solo <?= $tName ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Plan de acción</label>
                                <textarea name="SpecificActionPlan" class="form-control" rows="5"><?= htmlspecialchars($tpl['SpecificActionPlan']) ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary fw-bold">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="historyModal<?= $tpl['Id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i> Historial de: <?= htmlspecialchars($tpl['Name']) ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light p-0">
                        <?php if (count($historialRutina) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Folio</th>
                                            <th>Fecha Compromiso</th>
                                            <th>Responsable</th>
                                            <th>Estado</th>
                                            <th>Progreso</th>
                                            <th class="text-center pe-3">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historialRutina as $h): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($h['Folio']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($h['CommitmentDate'])) ?></td>
                                                <td>
                                                    <?php if($h['Responsable']): ?>
                                                        <i class="fas fa-user-check text-success me-1"></i> <?= htmlspecialchars($h['Responsable']) ?>
                                                    <?php else: ?>
                                                        <i class="fas fa-users text-warning me-1"></i> <span class="text-warning-emphasis">Pizarra</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $h['Estado'] == 'Finalizado' ? 'bg-success' : 'bg-primary' ?>"><?= $h['Estado'] ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                            <div class="progress-bar <?= $h['ProgressPercentage'] == 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $h['ProgressPercentage'] ?>%;"></div>
                                                        </div>
                                                        <small class="fw-bold"><?= $h['ProgressPercentage'] ?>%</small>
                                                    </div>
                                                </td>
                                                <td class="text-center pe-3">
                                                    <a href="task_details.php?id=<?= $h['Id'] ?>" class="btn btn-sm btn-outline-dark" target="_blank">
                                                        Abrir <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-2 text-center small text-muted border-top bg-white">
                                Mostrando los últimos <?= count($historialRutina) ?> registros generados.
                            </div>
                        <?php else: ?>
                            <div class="py-5 text-center text-muted">
                                <i class="fas fa-clipboard-check fa-3x mb-3 opacity-25"></i>
                                <h5>Sin historial</h5>
                                <p>Aún no se han ejecutado tareas preventivas bajo esta rutina.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="newTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="manage_tasks_templates.php">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i> Crear Nueva Rutina</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="create_template" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nombre de la rutina <span class="text-danger">*</span></label>
                        <input type="text" name="Name" class="form-control" required placeholder="Ej. Limpieza de filtros, Lubricación semanal...">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-dark">Prioridad <span class="text-danger">*</span></label>
                            <select name="PriorityId" class="form-select border-secondary" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($prioridades as $p): ?>
                                    <option value="<?= $p['Id'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-dark">Frecuencia <span class="text-danger">*</span></label>
                            <select name="RecurrenceType" class="form-select border-secondary" required>
                                <option value="Diaria">Diaria</option>
                                <option value="Semanal">Semanal</option>
                                <option value="Mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-dark">Turno objetivo</label>
                            <select name="TargetShifts" class="form-select border-secondary">
                                <option value="Todos los turnos">Todos los turnos</option>
                                <?php foreach ($turnosActivos as $tName): ?>
                                    <option value="<?= htmlspecialchars($tName) ?>">Solo <?= htmlspecialchars($tName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Plan de acción <span class="text-danger">*</span></label>
                        <textarea name="SpecificActionPlan" class="form-control" rows="4" required placeholder="Describe los pasos que debe seguir el técnico..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark fw-bold">Guardar Rutina</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>