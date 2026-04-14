<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['RoleName']) || $_SESSION['RoleName'] !== 'Administrativo') {
    $_SESSION['ErrorMessage'] = "Acceso denegado. Solo jefes pueden gestionar proyectos.";
    header("Location: index.php");
    exit;
}

$deptoId = $_SESSION['DepartmentID'];
$userId = $_SESSION['UserID'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $name = trim($_POST['Name'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $objective = trim($_POST['Objective'] ?? '');
    $expectedResult = trim($_POST['ExpectedResult'] ?? '');
    $priorityId = $_POST['PriorityId'] ?? '';
    $startDate = !empty($_POST['StartDate']) ? $_POST['StartDate'] : null;
    $commitmentDate = !empty($_POST['CommitmentDate']) ? $_POST['CommitmentDate'] : null;

    if (empty($name) || empty($priorityId)) {
        $errores[] = "El nombre y la prioridad son obligatorios.";
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $newProjectId = generar_uuid();
            $folio = 'PRJ-' . date('ym') . '-' . rand(1000, 9999);
            
            $sqlProject = "INSERT INTO Projects (Id, Folio, Name, Description, Objective, ExpectedResult, PrimaryDepartmentId, PriorityId, StatusId, StartDate, CommitmentDate) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2, ?, ?)";
            $pdo->prepare($sqlProject)->execute([$newProjectId, $folio, $name, $description, $objective, $expectedResult, $deptoId, $priorityId, $startDate, $commitmentDate]);

            $sqlLeader = "INSERT INTO ProjectLeaders (ProjectId, UserId) VALUES (?, ?)";
            $pdo->prepare($sqlLeader)->execute([$newProjectId, $userId]);

            $pdo->commit();
            $_SESSION['SuccessMessage'] = "Proyecto '$folio' creado exitosamente.";
            header("Location: manage_projects.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error al crear el proyecto: " . $e->getMessage();
        }
    }
}

$prioridades = $pdo->query("SELECT Id, Name FROM Priorities ORDER BY Id")->fetchAll();

$sqlProjects = "SELECT p.*, pr.Name as Prioridad, s.Name as Estado,
                (SELECT COUNT(*) FROM ProjectStages WHERE ProjectId = p.Id) as TotalEtapas
                FROM Projects p
                JOIN Priorities pr ON p.PriorityId = pr.Id
                JOIN Statuses s ON p.StatusId = s.Id
                WHERE p.PrimaryDepartmentId = ? AND p.IsDeleted = 0
                ORDER BY p.RowVersion DESC";
$stmt = $pdo->prepare($sqlProjects);
$stmt->execute([$deptoId]);
$proyectos = $stmt->fetchAll();

require 'layout.php';
?>

<div class="mt-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h2 class="fw-bold text-dark"><i class="fas fa-rocket me-2"></i> Portafolio de Proyectos</h2>
        <p class="text-muted mb-0">Gestiona iniciativas, mejoras de infraestructura y proyectos estratégicos.</p>
    </div>
    <button type="button" class="btn btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newProjectModal">
        <i class="fas fa-plus me-1"></i> Nuevo proyecto
    </button>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <?php if (count($proyectos) > 0): ?>
        <?php foreach ($proyectos as $p): ?>
            <div class="col-md-6 col-xl-4">
                <a href="project_dashboard.php?id=<?= $p['Id'] ?>" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 transition-hover">
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
                            
                            <p class="text-muted small mb-3 text-truncate" style="max-height: 40px; overflow: hidden;">
                                <?= htmlspecialchars($p['Objective'] ?? 'Sin objetivo definido.') ?>
                            </p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark">Progreso global</span>
                                    <span class="small fw-bold text-primary"><?= $p['ProgressPercentage'] ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $p['ProgressPercentage'] ?>%;"></div>
                                </div>
                            </div>

                            <hr class="text-muted opacity-25 my-3">

                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-secondary fw-bold">
                                    <i class="fas fa-layer-group me-1"></i> <?= $p['TotalEtapas'] ?> Etapas
                                </span>
                                <span class="text-muted">
                                    <i class="far fa-calendar-check me-1"></i> Fecha de compromiso: <?= $p['CommitmentDate'] ? date('d/m/Y', strtotime($p['CommitmentDate'])) : 'N/A' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <div class="bg-white p-5 rounded shadow-sm">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h3 class="fw-bold text-dark">Sin proyectos activos</h3>
                <p class="text-muted fs-5">Tu departamento no está ejecutando ningún proyecto actualmente.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="newProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="manage_projects.php">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-folder-plus me-2"></i> Crear proyecto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="create_project" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Proyecto <span class="text-danger">*</span></label>
                        <input type="text" name="Name" class="form-control" required placeholder="Ej. Instalación de Línea 4, Migración de Servidores">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Prioridad <span class="text-danger">*</span></label>
                            <select name="PriorityId" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($prioridades as $p): ?>
                                    <option value="<?= $p['Id'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Fecha inicio</label>
                            <input type="date" name="StartDate" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-danger">Fecha de compromiso</label>
                            <input type="date" name="CommitmentDate" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Objetivo (¿Para qué se hace?)</label>
                        <textarea name="Objective" class="form-control" rows="2" placeholder="Ej. Reducir el tiempo de inactividad de la máquina en un 15%..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Resultado Esperado (¿Cómo saber que se termino?)</label>
                        <textarea name="ExpectedResult" class="form-control" rows="2" placeholder="Ej. Línea 4 operando a 120 piezas por minuto sin fallas..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción general</label>
                        <textarea name="Description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark fw-bold">Crear proyecto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
    .transition-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require 'footer.php'; ?>