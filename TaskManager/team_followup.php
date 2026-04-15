<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || $_SESSION['RoleName'] !== 'Administrativo') {
    $_SESSION['ErrorMessage'] = "Acceso denegado. Esta área es exclusiva para supervisión.";
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['UserID'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shift'])) {
    $techId = $_POST['TechId'];
    $newShiftId = (!empty($_POST['ShiftId']) && $_POST['ShiftId'] !== 'NULL') ? $_POST['ShiftId'] : null;

    try {
        $pdo->prepare("UPDATE users SET ShiftId = ? WHERE Id = ? AND SupervisorId = ?")
            ->execute([$newShiftId, $techId, $userId]);
        
        $_SESSION['SuccessMessage'] = "El turno del empleado fue actualizado correctamente.";
        header("Location: team_followup.php");
        exit;
    } catch (Exception $e) {
        $errores[] = "Error al actualizar el turno: " . $e->getMessage();
    }
}

$turnosDisponibles = $pdo->query("SELECT Id, Name FROM shifts WHERE IsActive = 1 ORDER BY StartTime")->fetchAll();

$sqlTechs = "SELECT u.Id, u.FullName, u.JobTitle, u.Email, u.ShiftId, sh.Name as ShiftName
             FROM users u 
             JOIN roles r ON u.RoleId = r.Id 
             LEFT JOIN shifts sh ON u.ShiftId = sh.Id
             WHERE u.SupervisorId = ? AND u.IsActive = 1
             ORDER BY u.FullName ASC";
$stmtTechs = $pdo->prepare($sqlTechs);
$stmtTechs->execute([$userId]);
$tecnicos = $stmtTechs->fetchAll();

$equipo = [];

foreach ($tecnicos as $tech) {
    $sqlTasks = "SELECT a.Id, a.Folio, a.Name, a.ProgressPercentage, s.Name as Estado, p.Name as Prioridad,
                 (SELECT MAX(CreatedAt) FROM activitycomments WHERE ActivityId = a.Id) as LastUpdate
                 FROM activities a
                 JOIN statuses s ON a.StatusId = s.Id
                 JOIN priorities p ON a.PriorityId = p.Id
                 WHERE a.ResponsibleId = ? AND s.Name NOT IN ('Finalizado', 'Cancelado')
                 ORDER BY p.Id DESC, a.ProgressPercentage DESC";
    $stmtTasks = $pdo->prepare($sqlTasks);
    $stmtTasks->execute([$tech['Id']]);
    $tareasActivas = $stmtTasks->fetchAll();
    
    $sqlHistory = "SELECT a.Id, a.Folio, a.Name, a.CompletedDate, s.Name as Estado, p.Name as Prioridad
                   FROM activities a
                   JOIN statuses s ON a.StatusId = s.Id
                   JOIN priorities p ON a.PriorityId = p.Id
                   WHERE a.ResponsibleId = ? AND s.Name IN ('Finalizado', 'Cancelado')
                   ORDER BY a.CompletedDate DESC
                   LIMIT 20";
    $stmtHistory = $pdo->prepare($sqlHistory);
    $stmtHistory->execute([$tech['Id']]);
    $tareasHistorial = $stmtHistory->fetchAll();

    $tech['TareasActivas'] = $tareasActivas;
    $tech['TotalActivas'] = count($tareasActivas);
    $tech['Historial'] = $tareasHistorial;
    $equipo[] = $tech;
}

require 'layout.php';
?>

<div class="mt-4 mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-users me-2"></i> Seguimiento de Equipo</h2>
    <p class="text-muted">Supervisa la carga de trabajo del personal que te reporta directamente (Línea de Supervisión).</p>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <?php if (count($equipo) > 0): ?>
        <?php foreach ($equipo as $tech): ?>
            <div class="col-lg-6 col-xl-4">
                <div class="card shadow-sm border-0 h-100">
                    
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 45px; height: 45px; font-size: 1.2rem;">
                                <?= strtoupper(substr($tech['FullName'], 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($tech['FullName']) ?></h5>
                                <small class="text-muted d-block"><?= htmlspecialchars($tech['JobTitle'] ?? 'Colaborador') ?></small>
                                
                                <div class="d-flex align-items-center mt-1">
                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                        <i class="fas fa-clock text-primary me-1"></i> <?= htmlspecialchars($tech['ShiftName'] ?? 'Sin turno fijo') ?>
                                    </span>
                                    <button class="btn btn-sm btn-link text-secondary p-0 ms-2" title="Cambiar Turno" data-bs-toggle="modal" data-bs-target="#shiftModal<?= $tech['Id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge <?= $tech['TotalActivas'] > 0 ? 'bg-primary' : 'bg-success' ?> rounded-pill px-3 py-2 mb-1 d-block">
                                <?= $tech['TotalActivas'] ?> <?= $tech['TotalActivas'] == 1 ? 'Activa' : 'Activas' ?>
                            </span>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#historyModal<?= $tech['Id'] ?>" title="Ver tareas completadas">
                                <i class="fas fa-history"></i> Historial
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <hr class="text-muted opacity-25">
                        
                        <?php if ($tech['TotalActivas'] > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($tech['TareasActivas'] as $t): ?>
                                    <?php 
                                        $barColor = 'bg-primary';
                                        if ($t['ProgressPercentage'] == 0) $barColor = 'bg-secondary';
                                        if ($t['ProgressPercentage'] == 100) $barColor = 'bg-info';
                                        if ($t['Prioridad'] == 'Crítica' && $t['ProgressPercentage'] < 100) $barColor = 'bg-danger';
                                    ?>

                                    <div class="border rounded p-3 bg-light position-relative">
                                        <div class="d-flex justify-content-between mb-1">
                                            <a href="task_details.php?id=<?= $t['Id'] ?>" class="fw-bold text-decoration-none text-dark stretched-link">
                                                <?= $t['Folio'] ?>
                                            </a>
                                            <span class="small fw-bold <?= $t['ProgressPercentage'] == 100 ? 'text-info' : 'text-primary' ?>">
                                                <?= $t['ProgressPercentage'] ?>%
                                            </span>
                                        </div>
                                        
                                        <p class="small text-muted mb-2 text-truncate" title="<?= htmlspecialchars($t['Name']) ?>">
                                            <?= htmlspecialchars($t['Name']) ?>
                                        </p>
                                        
                                        <div class="progress mb-2" style="height: 6px;">
                                            <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= $t['ProgressPercentage'] ?>%;"></div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= $t['Prioridad'] == 'Crítica' ? 'bg-danger' : ($t['Prioridad'] == 'Alta' ? 'bg-warning text-dark' : 'bg-secondary') ?>" style="font-size: 0.65rem;">
                                                <?= $t['Prioridad'] ?>
                                            </span>
                                            
                                            <small class="text-muted" style="font-size: 0.7rem;">
                                                <i class="far fa-clock me-1"></i> 
                                                <?= $t['LastUpdate'] ? date('d/m H:i', strtotime($t['LastUpdate'])) : 'Sin reportes' ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                    <i class="fas fa-check fs-4"></i>
                                </div>
                                <h6 class="fw-bold text-success mb-0">Disponible</h6>
                                <p class="text-muted small">Este colaborador no tiene tareas activas. Ideal para recibir nuevas asignaciones.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="shiftModal<?= $tech['Id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="POST" action="team_followup.php">
                            <div class="modal-header bg-dark text-white">
                                <h6 class="modal-title fw-bold">Actualizar Turno</h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body bg-light text-center">
                                <p class="small text-muted mb-3">Rotación para <b><?= htmlspecialchars($tech['FullName']) ?></b></p>
                                
                                <input type="hidden" name="update_shift" value="1">
                                <input type="hidden" name="TechId" value="<?= $tech['Id'] ?>">
                                
                                <select name="ShiftId" class="form-select" required>
                                    <option value="NULL" class="text-muted">-- Sin turno fijo / N/A --</option>
                                    <?php foreach($turnosDisponibles as $t): ?>
                                        <option value="<?= $t['Id'] ?>" <?= $tech['ShiftId'] == $t['Id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="modal-footer p-2 justify-content-center">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-primary fw-bold">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="historyModal<?= $tech['Id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-secondary text-white">
                            <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i> Historial de Desempeño: <?= htmlspecialchars($tech['FullName']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-light p-0">
                            <?php if (count($tech['Historial']) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Folio / Tarea</th>
                                                <th>Prioridad</th>
                                                <th>Estado</th>
                                                <th>Fecha de Cierre</th>
                                                <th class="text-center pe-3">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tech['Historial'] as $h): ?>
                                                <tr>
                                                    <td class="ps-3">
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($h['Folio']) ?></div>
                                                        <div class="small text-muted text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($h['Name']) ?>">
                                                            <?= htmlspecialchars($h['Name']) ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $h['Prioridad'] == 'Crítica' ? 'bg-danger' : ($h['Prioridad'] == 'Alta' ? 'bg-warning text-dark' : 'bg-secondary') ?> bg-opacity-75">
                                                            <?= $h['Prioridad'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $h['Estado'] == 'Finalizado' ? 'bg-success' : 'bg-dark' ?> bg-opacity-75">
                                                            <?= $h['Estado'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($h['CompletedDate']): ?>
                                                            <span class="small fw-bold text-muted"><?= date('d/m/Y H:i', strtotime($h['CompletedDate'])) ?></span>
                                                        <?php else: ?>
                                                            <span class="small text-muted">--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center pe-3">
                                                        <a href="task_details.php?id=<?= $h['Id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Ver detalles de la tarea">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-archive fa-3x text-muted mb-3 opacity-25"></i>
                                    <h6 class="text-muted fw-bold">Sin historial</h6>
                                    <p class="text-muted small mb-0">Este colaborador aún no ha completado ninguna tarea.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer bg-light border-top-0">
                            <span class="small text-muted me-auto">* Mostrando las últimas 20 tareas finalizadas.</span>
                            <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
            <h4>Sin personal a cargo</h4>
            <p class="text-muted">No tienes colaboradores asignados bajo tu supervisión directa en este momento.</p>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>