<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || !in_array($_SESSION['RoleName'], ['Administrativo', 'Auditor'])) {
    $_SESSION['ErrorMessage'] = "Acceso denegado. Visibilidad restringida a gerencia y auditoría.";
    header("Location: index.php");
    exit;
}

$deptoId = $_SESSION['DepartmentID'];
$errores = [];

$sqlProjects = "SELECT p.*, pr.Name as Prioridad, s.Name as Estado, d.Name as DepartamentoPropietario,
                (SELECT COUNT(*) FROM projectstages WHERE ProjectId = p.Id) as TotalEtapas
                FROM projects p
                JOIN priorities pr ON p.PriorityId = pr.Id
                JOIN statuses s ON p.StatusId = s.Id
                JOIN departments d ON p.PrimaryDepartmentId = d.Id
                WHERE p.PrimaryDepartmentId != ? AND p.IsDeleted = 0
                ORDER BY d.Name ASC, p.ProgressPercentage ASC";
$stmt = $pdo->prepare($sqlProjects);
$stmt->execute([$deptoId]);
$proyectos = $stmt->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h2 class="fw-bold text-dark"><i class="fas fa-globe me-2 text-primary"></i> Portafolio de la Planta</h2>
        <p class="text-muted mb-0">Visibilidad global de las iniciativas estratégicas en ejecución por otros departamentos.</p>
    </div>
    </div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <?php if (count($proyectos) > 0): ?>
        <?php foreach ($proyectos as $p): ?>
            <div class="col-md-6 col-xl-4">
                <a href="project_dashboard.php?id=<?= $p['Id'] ?>" class="text-decoration-none">
                    <div class="card shadow-sm border-0 border-top border-4 border-secondary h-100 transition-hover">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-dark fs-6"><?= $p['Folio'] ?></span>
                                <span class="badge <?= $p['Prioridad'] == 'Crítica' ? 'bg-danger' : ($p['Prioridad'] == 'Alta' ? 'bg-warning text-dark' : 'bg-secondary') ?> px-2 py-1">
                                    <?= $p['Prioridad'] ?>
                                </span>
                            </div>
                            
                            <h5 class="fw-bold text-dark mt-2 mb-1 text-truncate" title="<?= htmlspecialchars($p['Name']) ?>">
                                <?= htmlspecialchars($p['Name']) ?>
                            </h5>
                            
                            <div class="mb-3">
                                <span class="badge bg-light text-secondary border border-secondary">
                                    <i class="fas fa-building me-1"></i> Dueño: <?= htmlspecialchars($p['DepartamentoPropietario']) ?>
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-3 text-truncate" style="max-height: 40px; overflow: hidden;">
                                <?= htmlspecialchars($p['Objective'] ?? 'Sin objetivo definido.') ?>
                            </p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark">Progreso global</span>
                                    <span class="small fw-bold <?= $p['ProgressPercentage'] == 100 ? 'text-success' : 'text-primary' ?>"><?= $p['ProgressPercentage'] ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= $p['ProgressPercentage'] == 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $p['ProgressPercentage'] ?>%;"></div>
                                </div>
                            </div>

                            <hr class="text-muted opacity-25 my-3">

                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-secondary fw-bold">
                                    <i class="fas fa-layer-group me-1"></i> <?= $p['TotalEtapas'] ?> Etapas
                                </span>
                                <span class="text-muted">
                                    <i class="far fa-calendar-check me-1"></i> Compromiso: <?= $p['CommitmentDate'] ? date('d/m/Y', strtotime($p['CommitmentDate'])) : 'N/A' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <div class="bg-white p-5 rounded shadow-sm border">
                <i class="fas fa-search-location fa-4x text-muted mb-3 opacity-50"></i>
                <h3 class="fw-bold text-dark">Sin proyectos externos</h3>
                <p class="text-muted fs-5">No hay otros departamentos ejecutando proyectos en este momento.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
    .transition-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require 'footer.php'; ?>