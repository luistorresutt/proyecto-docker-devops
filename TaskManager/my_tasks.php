<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserID'];

$sqlPendientes = "
    SELECT a.Id, a.Folio, a.Name, p.Name as Prioridad, s.Name as Estado, 
           a.CommitmentDate, d.Name as DeptoOrigen
    FROM activities a
    JOIN priorities p ON a.PriorityId = p.Id
    JOIN statuses s ON a.StatusId = s.Id
    JOIN departments d ON a.RequesterDepartmentId = d.Id
    WHERE a.ResponsibleId = ? AND s.Name NOT IN ('Finalizado', 'Cancelado')
    ORDER BY p.Id DESC, a.CommitmentDate ASC
";
$stmt = $pdo->prepare($sqlPendientes);
$stmt->execute([$userId]);
$tareas = $stmt->fetchAll();

$sqlHistorial = "
    SELECT a.Id, a.Folio, a.Name, p.Name as Prioridad, s.Name as Estado, 
           a.CommitmentDate, a.CompletedDate, d.Name as DeptoOrigen
    FROM activities a
    JOIN priorities p ON a.PriorityId = p.Id
    JOIN statuses s ON a.StatusId = s.Id
    JOIN departments d ON a.RequesterDepartmentId = d.Id
    WHERE a.ResponsibleId = ? AND s.Name IN ('Finalizado', 'Cancelado')
    ORDER BY a.CompletedDate DESC, a.CommitmentDate DESC
    LIMIT 50 -- Límite razonable para no sobrecargar la vista si el técnico tiene años de antigüedad
";
$stmtHist = $pdo->prepare($sqlHistorial);
$stmtHist->execute([$userId]);
$historial = $stmtHist->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-4">
    <div>
        <h2 class="fw-bold text-dark"><i class="fas fa-clipboard-list me-2"></i>Mis Tareas</h2>
        <p class="text-muted">Listado de actividades asignadas a tu cargo para ejecución.</p>
    </div>
    <div class="text-start">
        <span class="badge bg-primary px-3 py-2"><?= count($tareas) ?> Pendientes</span>
    </div>
</div>

<div class="row mb-5">
    <?php if (count($tareas) > 0): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Folio / Tarea</th>
                                <th>Origen</th>
                                <th>Prioridad</th>
                                <th>Estado Actual</th>
                                <th>Compromiso</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tareas as $t): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-primary"><?= $t['Folio'] ?></div>
                                        <div class="text-dark fw-semibold"><?= htmlspecialchars($t['Name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= htmlspecialchars($t['DeptoOrigen']) ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $badgeClass = 'bg-secondary';
                                            if($t['Prioridad'] == 'Crítica') $badgeClass = 'bg-danger';
                                            elseif($t['Prioridad'] == 'Alta') $badgeClass = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $t['Prioridad'] ?></span>
                                    </td>
                                    <td>
                                        <span class="text-uppercase small fw-bold text-muted">
                                            <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i> <?= $t['Estado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($t['CommitmentDate']): ?>
                                            <div class="small <?= strtotime($t['CommitmentDate']) < time() ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d/m/Y', strtotime($t['CommitmentDate'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin fecha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="task_details.php?id=<?= $t['Id'] ?>" class="btn btn-sm btn-outline-dark px-3">
                                            Abrir <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <div class="bg-white p-5 rounded shadow-sm">
                <i class="fas fa-check-double fa-4x text-success mb-3"></i>
                <h4>¡Todo al día!</h4>
                <p class="text-muted">No tienes tareas pendientes de ejecución asignadas a tu nombre.</p>
                <a href="index.php" class="btn btn-primary mt-2">Volver al inicio</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="mt-5 mb-4 border-top pt-4">
    <h4 class="fw-bold text-secondary"><i class="fas fa-history me-2"></i>Historial de Tareas Cerradas</h4>
    <p class="text-muted">Actividades que ya has completado o han sido canceladas (Últimos 50 registros).</p>
</div>

<div class="row mb-5">
    <?php if (count($historial) > 0): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="ps-4">Folio / Tarea</th>
                                <th>Origen</th>
                                <th>Estado</th>
                                <th>Fecha de Cierre</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $h): ?>
                                <tr class="bg-light bg-opacity-50">
                                    <td class="ps-4">
                                        <div class="fw-bold text-secondary"><?= $h['Folio'] ?></div>
                                        <div class="text-dark opacity-75"><?= htmlspecialchars($h['Name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= htmlspecialchars($h['DeptoOrigen']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $h['Estado'] == 'Finalizado' ? 'bg-success' : 'bg-dark' ?> bg-opacity-75">
                                            <?= $h['Estado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($h['CompletedDate']): ?>
                                            <div class="small text-muted fw-bold">
                                                <?= date('d/m/Y H:i', strtotime($h['CompletedDate'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="task_details.php?id=<?= $h['Id'] ?>" class="btn btn-sm btn-outline-secondary px-3">
                                            Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12 text-center py-4">
            <i class="fas fa-archive fa-3x text-muted mb-3 opacity-25"></i>
            <h6 class="text-muted">Aún no tienes historial</h6>
            <p class="text-muted small">Las tareas que completes aparecerán aquí.</p>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>