<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserID'];

$sql = "
    SELECT a.Id, a.Folio, a.Name, p.Name as Prioridad, s.Name as Estado, 
           d.Name as DeptoDestino, a.RowVersion as CreatedAt, a.CommitmentDate,
           a.ProgressPercentage,
           (SELECT CommentText FROM activitycomments WHERE ActivityId = a.Id ORDER BY CreatedAt DESC LIMIT 1) as UltimoComentario
    FROM activities a
    JOIN priorities p ON a.PriorityId = p.Id
    JOIN statuses s ON a.StatusId = s.Id
    JOIN departments d ON a.PrimaryDepartmentId = d.Id
    WHERE a.RequesterId = ?
    ORDER BY a.RowVersion DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$solicitudes = $stmt->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-search me-2"></i> Rastrear mis solicitudes</h2>
    <p class="text-muted">Da seguimiento al estado de los tickets que has enviado a otros departamentos.</p>
</div>

<div class="row">
    <?php if (count($solicitudes) > 0): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Folio / Asunto</th>
                                <th>Departamento destino</th>
                                <th>Estado y Avance</th>
                                <th>Último Reporte</th>
                                <th>Fechas (Envío / Límite)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $req): ?>
                                <tr>
                                    <td class="ps-4" style="max-width: 200px;">
                                        <a href="task_details.php?id=<?= $req['Id'] ?>" class="fw-bold text-primary text-decoration-none">
                                            <?= $req['Folio'] ?> <i class="fas fa-external-link-alt ms-1" style="font-size: 0.75rem;"></i>
                                        </a>
                                        <div class="text-muted small text-truncate" title="<?= htmlspecialchars($req['Name']) ?>">
                                            <?= htmlspecialchars($req['Name']) ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="fw-semibold text-secondary"><?= htmlspecialchars($req['DeptoDestino']) ?></span>
                                    </td>
                                    
                                    <td style="min-width: 150px;">
                                        <?php 
                                            $estado = $req['Estado'];
                                            $badgeClass = 'bg-secondary';
                                            $barColor = 'bg-primary';
                                            
                                            if ($estado === 'Pendiente de Aprobación') { $badgeClass = 'bg-warning text-dark'; $barColor = 'bg-warning'; }
                                            elseif ($estado === 'No iniciado') { $badgeClass = 'bg-info text-dark'; $barColor = 'bg-info'; }
                                            elseif ($estado === 'En proceso') { $badgeClass = 'bg-primary'; $barColor = 'bg-primary'; }
                                            elseif ($estado === 'En revisión') { $badgeClass = 'bg-primary bg-opacity-75'; $barColor = 'bg-info'; }
                                            elseif ($estado === 'Finalizado') { $badgeClass = 'bg-success'; $barColor = 'bg-success'; }
                                            elseif ($estado === 'Cancelado') { $badgeClass = 'bg-danger'; $barColor = 'bg-danger'; }
                                        ?>
                                        <span class="badge <?= $badgeClass ?> px-2 py-1 mb-1" style="font-size: 0.75rem;">
                                            <?= $estado ?>
                                        </span>
                                        
                                        <div class="d-flex align-items-center mt-1">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= $req['ProgressPercentage'] ?>%;"></div>
                                            </div>
                                            <span class="small text-muted ms-2 fw-bold" style="font-size: 0.75rem;"><?= $req['ProgressPercentage'] ?>%</span>
                                        </div>
                                    </td>

                                    <td style="max-width: 250px;">
                                        <?php if ($req['UltimoComentario']): ?>
                                            <div class="small text-secondary text-truncate" title="<?= htmlspecialchars($req['UltimoComentario']) ?>">
                                                <i class="far fa-comment-dots me-1"></i> <?= htmlspecialchars($req['UltimoComentario']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-muted fst-italic">Sin reportes aún</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="small text-muted mb-1">
                                            <i class="far fa-calendar-plus me-1" title="Fecha de Envío"></i> <?= date('d/m/y', strtotime($req['CreatedAt'])) ?>
                                        </div>
                                        
                                        <?php if ($req['CommitmentDate']): ?>
                                            <div class="small fw-bold <?= strtotime($req['CommitmentDate']) < time() && !in_array($estado, ['Finalizado', 'Cancelado']) ? 'text-danger' : 'text-dark' ?>">
                                                <i class="far fa-calendar-check me-1" title="Fecha Compromiso"></i> <?= date('d/m/y', strtotime($req['CommitmentDate'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <?php if ($estado === 'Pendiente de Aprobación'): ?>
                                                <span class="text-muted small fst-italic">En evaluación</span>
                                            <?php else: ?>
                                                <span class="text-muted small">Límite sin definir</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
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
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4>Sin solicitudes activas</h4>
                <p class="text-muted">Aún no has solicitado apoyo a ningún departamento.</p>
                <a href="request_task.php" class="btn btn-primary mt-2"><i class="fas fa-plus-circle me-1"></i> Crear mi primera solicitud</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>