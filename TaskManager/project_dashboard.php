<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$projectId = $_GET['id'] ?? '';
$userId = $_SESSION['UserID'];
$roleName = $_SESSION['RoleName'];
$deptoId = $_SESSION['DepartmentID'];
$errores = [];

function sincronizarProyecto($pdo, $projectId) {
    $pdo->prepare("UPDATE projectstages ps SET 
        StartDate = (SELECT MIN(StartDate) FROM activities WHERE StageId = ps.Id AND IsDeleted = 0),
        CommitmentDate = (SELECT MAX(CommitmentDate) FROM activities WHERE StageId = ps.Id AND IsDeleted = 0),
        ProgressPercentage = COALESCE((SELECT CAST(AVG(ProgressPercentage) AS UNSIGNED) FROM activities WHERE StageId = ps.Id AND IsDeleted = 0), 0)
        WHERE ProjectId = ?")->execute([$projectId]);
        
    $pdo->prepare("UPDATE projectstages SET StatusId = CASE WHEN ProgressPercentage = 100 THEN 5 WHEN ProgressPercentage > 0 THEN 3 ELSE 2 END WHERE ProjectId = ?")->execute([$projectId]);

    $pdo->prepare("UPDATE projects SET 
        StartDate = (SELECT MIN(StartDate) FROM projectstages WHERE ProjectId = ? AND IsDeleted = 0),
        CommitmentDate = (SELECT MAX(CommitmentDate) FROM projectstages WHERE ProjectId = ? AND IsDeleted = 0),
        ProgressPercentage = COALESCE((SELECT CAST(AVG(ProgressPercentage) AS UNSIGNED) FROM projectstages WHERE ProjectId = ? AND IsDeleted = 0), 0)
        WHERE Id = ?")->execute([$projectId, $projectId, $projectId, $projectId]);

    $pdo->prepare("UPDATE projects SET StatusId = CASE WHEN ProgressPercentage = 100 THEN 5 WHEN ProgressPercentage > 0 THEN 3 ELSE 2 END WHERE Id = ?")->execute([$projectId]);
}

if ($projectId) {
    sincronizarProyecto($pdo, $projectId);
}

$sqlProject = "SELECT p.*, s.Name as Estado, d.Name as Departamento, pr.Name as Prioridad 
               FROM projects p 
               JOIN statuses s ON p.StatusId = s.Id
               JOIN departments d ON p.PrimaryDepartmentId = d.Id
               JOIN priorities pr ON p.PriorityId = pr.Id
               WHERE p.Id = ? AND p.IsDeleted = 0";
$stmt = $pdo->prepare($sqlProject);
$stmt->execute([$projectId]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    $_SESSION['ErrorMessage'] = "Proyecto no encontrado.";
    header("Location: manage_projects.php");
    exit;
}

$stmtCheckLeader = $pdo->prepare("SELECT 1 FROM projectleaders WHERE ProjectId = ? AND UserId = ?");
$stmtCheckLeader->execute([$projectId, $userId]);
$isProjectLeader = $stmtCheckLeader->fetchColumn() ? true : false;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isProjectLeader) {

    if (isset($_POST['create_stage'])) {
        $name = trim($_POST['Name']);
        $desc = trim($_POST['Description']);
        $criteria = trim($_POST['AcceptanceCriteria']);
        
        $stmtOrder = $pdo->prepare("SELECT COALESCE(MAX(OrderIndex), 0) + 1 FROM projectstages WHERE ProjectId = ?");
        $stmtOrder->execute([$projectId]);
        $nextOrder = $stmtOrder->fetchColumn();

        try {
            $newStageId = generar_uuid();
            $sqlStage = "INSERT INTO projectstages (Id, ProjectId, OrderIndex, Name, Description, AcceptanceCriteria) VALUES (?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sqlStage)->execute([$newStageId, $projectId, $nextOrder, $name, $desc, $criteria]);
            header("Location: project_dashboard.php?id=" . $projectId);
            exit;
        } catch (Exception $e) { $errores[] = "Error al crear etapa: " . $e->getMessage(); }
    }

    if (isset($_POST['edit_stage'])) {
        $stageId = $_POST['StageId'];
        $name = trim($_POST['Name']);
        $desc = trim($_POST['Description']);
        $criteria = trim($_POST['AcceptanceCriteria']);

        try {
            $sqlUpd = "UPDATE projectstages SET Name = ?, Description = ?, AcceptanceCriteria = ? WHERE Id = ? AND ProjectId = ?";
            $pdo->prepare($sqlUpd)->execute([$name, $desc, $criteria, $stageId, $projectId]);
            header("Location: project_dashboard.php?id=" . $projectId);
            exit;
        } catch (Exception $e) { $errores[] = "Error al editar etapa."; }
    }

    if (isset($_POST['create_task'])) {
        $stageId = $_POST['StageId'];
        $plan = trim($_POST['SpecificActionPlan']);
        $priorityId = $_POST['PriorityId'];
        $dependsOn = !empty($_POST['DependsOnActivityId']) ? $_POST['DependsOnActivityId'] : null;
        $responsibleId = (!empty($_POST['ResponsibleId']) && $_POST['ResponsibleId'] !== 'NULL') ? $_POST['ResponsibleId'] : null;
        
        $startDate = !empty($_POST['StartDate']) ? $_POST['StartDate'] : null;
        $commitmentDate = !empty($_POST['CommitmentDate']) ? $_POST['CommitmentDate'] : null;

        if(empty($startDate) || empty($commitmentDate)) {
            $errores[] = "Las fechas de Inicio y Compromiso son obligatorias para el diagrama de Gantt.";
        } else {
            try {
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE ProjectId = ? AND IsDeleted = 0");
                $stmtCount->execute([$projectId]);
                $numeroTarea = $stmtCount->fetchColumn() + 1;
                
                $name = "Tarea " . $numeroTarea . ": " . trim($_POST['TaskName']);
                $newTaskId = generar_uuid();
                $folio = 'PRJ-TSK-' . rand(10000, 99999);
                
                $sqlTask = "INSERT INTO activities (Id, Folio, RequesterId, ResponsibleId, PrimaryDepartmentId, RequesterDepartmentId, ProjectId, StageId, DependsOnActivityId, Name, SpecificActionPlan, PriorityId, TaskTypeId, StatusId, StartDate, CommitmentDate) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT Id FROM tasktypes WHERE Name = 'Mejora / Proyecto' LIMIT 1), 2, ?, ?)";
                
                $pdo->prepare($sqlTask)->execute([
                    $newTaskId, $folio, $userId, $responsibleId, $proyecto['PrimaryDepartmentId'], $proyecto['PrimaryDepartmentId'], 
                    $projectId, $stageId, $dependsOn, $name, $plan, $priorityId, $startDate, $commitmentDate
                ]);
                
                header("Location: project_dashboard.php?id=" . $projectId);
                exit;
            } catch (Exception $e) { $errores[] = "Error al crear tarea: " . $e->getMessage(); }
        }
    }

    if (isset($_POST['edit_task'])) {
        $taskId = $_POST['TaskId'];
        $name = trim($_POST['TaskName']); 
        $priorityId = $_POST['PriorityId'];
        $responsibleId = (!empty($_POST['ResponsibleId']) && $_POST['ResponsibleId'] !== 'NULL') ? $_POST['ResponsibleId'] : null;
        $startDate = !empty($_POST['StartDate']) ? $_POST['StartDate'] : null;
        $commitmentDate = !empty($_POST['CommitmentDate']) ? $_POST['CommitmentDate'] : null;

        if(empty($startDate) || empty($commitmentDate)) {
            $errores[] = "Las fechas de Inicio y Compromiso son obligatorias.";
        } else {
            try {
                $sqlUpd = "UPDATE activities SET Name = ?, PriorityId = ?, ResponsibleId = ?, StartDate = ?, CommitmentDate = ? WHERE Id = ? AND ProjectId = ?";
                $pdo->prepare($sqlUpd)->execute([$name, $priorityId, $responsibleId, $startDate, $commitmentDate, $taskId, $projectId]);
                
                $stageId = $pdo->query("SELECT StageId FROM activities WHERE Id = '$taskId'")->fetchColumn();

                header("Location: project_dashboard.php?id=" . $projectId);
                exit;
            } catch (Exception $e) { $errores[] = "Error al editar tarea."; }
        }
    }

    if (isset($_POST['invite_leader'])) {
        $newLeaderId = $_POST['NewLeaderId'];
        try {
            $pdo->prepare("INSERT IGNORE INTO projectleaders (ProjectId, UserId) VALUES (?, ?)")->execute([$projectId, $newLeaderId]);
            header("Location: project_dashboard.php?id=" . $projectId);
            exit;
        } catch (Exception $e) { $errores[] = "Error al invitar líder."; }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isProjectLeader) {
    $errores[] = "Acción denegada. Solo los miembros del Comité del Proyecto pueden hacer modificaciones.";
}

$stmt->execute([$projectId]);
$proyecto = $stmt->fetch();

$stmtLeaders = $pdo->prepare("SELECT u.Id, u.FullName FROM projectleaders pl JOIN users u ON pl.UserId = u.Id WHERE pl.ProjectId = ?");
$stmtLeaders->execute([$projectId]);
$lideresObj = $stmtLeaders->fetchAll();
$lideresNames = array_column($lideresObj, 'FullName');

$stmtAllAdmins = $pdo->prepare("SELECT u.Id, u.FullName, d.Name as Depto FROM users u JOIN roles r ON u.RoleId = r.Id JOIN departments d ON u.DepartmentId = d.Id WHERE r.Name = 'Administrativo' AND u.IsActive = 1 AND u.Id NOT IN (SELECT UserId FROM projectleaders WHERE ProjectId = ?)");
$stmtAllAdmins->execute([$projectId]);
$jefesDisponibles = $stmtAllAdmins->fetchAll();

$stmtStages = $pdo->prepare("SELECT * FROM ProjectStages WHERE ProjectId = ? ORDER BY OrderIndex ASC");
$stmtStages->execute([$projectId]);
$etapas = $stmtStages->fetchAll();

$stmtTasks = $pdo->prepare("
    SELECT a.*, s.Name as Estado, u.FullName as Responsable, p.Name as Prioridad, dep.Name as DependeDeNombre
    FROM activities a
    JOIN statuses s ON a.StatusId = s.Id
    JOIN priorities p ON a.PriorityId = p.Id
    LEFT JOIN users u ON a.ResponsibleId = u.Id
    LEFT JOIN activities dep ON a.DependsOnActivityId = dep.Id
    WHERE a.ProjectId = ? AND a.IsDeleted = 0
    ORDER BY a.StartDate ASC, a.RowVersion ASC
");
$stmtTasks->execute([$projectId]);
$todasLasTareas = $stmtTasks->fetchAll();

$tareasPorEtapa = [];
foreach ($todasLasTareas as $t) {
    $tareasPorEtapa[$t['StageId']][] = $t;
}

$prioridades = $pdo->query("SELECT Id, Name FROM priorities ORDER BY Id")->fetchAll();

$stmtTechs = $pdo->prepare("SELECT u.Id, u.FullName FROM users u JOIN roles r ON u.RoleId = r.Id WHERE u.DepartmentId = ? AND r.Name = 'Tecnico' AND u.IsActive = 1 ORDER BY u.FullName ASC");
$stmtTechs->execute([$proyecto['PrimaryDepartmentId']]);
$tecnicosDisponibles = $stmtTechs->fetchAll();

$ganttData = [];
$validTasks = [];

foreach ($todasLasTareas as $t) {
    if (!empty($t['StartDate']) && !empty($t['CommitmentDate'])) {
        if (strtotime($t['StartDate']) <= strtotime($t['CommitmentDate'])) {
            $validTasks[$t['Id']] = $t;
        }
    }
}

foreach ($validTasks as $id => $t) {
    $name = addslashes(substr($t['Name'], 0, 50)); 
    
    $stageName = 'Etapa';
    foreach($etapas as $e) { if($e['Id'] == $t['StageId']) $stageName = 'Fase ' . $e['OrderIndex'] . ': ' . $e['Name']; }
    $resource = addslashes($stageName); 
    
    $start = strtotime($t['StartDate']);
    $end = strtotime($t['CommitmentDate']);
    if ($start == $end) { $end = strtotime('+1 day', $end); }

    $startYear = date('Y', $start);
    $startMonth = date('n', $start) - 1; 
    $startDay = date('j', $start);
    
    $endYear = date('Y', $end);
    $endMonth = date('n', $end) - 1;
    $endDay = date('j', $end);
    
    $progress = (int)$t['ProgressPercentage'];
    
    $depends = 'null';
    if ($t['DependsOnActivityId'] && isset($validTasks[$t['DependsOnActivityId']])) {
        $parentEnd = strtotime($validTasks[$t['DependsOnActivityId']]['CommitmentDate']);
        if ($start >= $parentEnd) {
            $depends = "'" . $t['DependsOnActivityId'] . "'";
        }
    }

    $ganttData[] = "['$id', '$name', '$resource', new Date($startYear, $startMonth, $startDay), new Date($endYear, $endMonth, $endDay), null, $progress, $depends]";
}
$hasGanttData = count($ganttData) > 0;

require 'layout.php';
?>

<div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
    <div>
        <a href="manage_projects.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="fas fa-arrow-left me-1"></i> Volver a Proyectos</a>
        <h2 class="fw-bold text-dark mb-0"><i class="fas fa-project-diagram me-2 text-primary"></i> <?= htmlspecialchars($proyecto['Folio']) ?>: <?= htmlspecialchars($proyecto['Name']) ?></h2>
    </div>
    <div class="text-end">
        <span class="badge <?= $proyecto['Estado'] == 'Finalizado' ? 'bg-success' : ($proyecto['Estado'] == 'No iniciado' ? 'bg-secondary' : 'bg-primary') ?> fs-5 mb-1"><?= htmlspecialchars($proyecto['Estado']) ?></span><br>
        <small class="text-muted fw-bold">Dueño: <?= htmlspecialchars($proyecto['Departamento']) ?></small>
    </div>
</div>

<?php if (!$isProjectLeader): ?>
    <div class="alert alert-secondary border-start border-4 border-secondary d-flex align-items-center mb-4 shadow-sm">
        <i class="fas fa-eye fa-2x me-3 text-secondary"></i>
        <div>
            <h6 class="fw-bold mb-0">Modo Lectura</h6>
            <small>Estás viendo este proyecto porque eres administrador de tu departamento, pero no formas parte del Comité. Solo los líderes pueden hacer modificaciones.</small>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-stream me-2 text-primary"></i> Cronograma de Actividades (Gantt)</span>
                <?php if(!$hasGanttData): ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Faltan fechas en las tareas</span>
                <?php endif; ?>
            </div>
            <div class="card-body overflow-auto">
                <?php if ($hasGanttData): ?>
                    <div id="gantt_chart_div" style="width: 100%; min-height: 250px;"></div>
                    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
                    <script type="text/javascript">
                        google.charts.load('current', {'packages':['gantt'], 'language': 'es'});
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            var data = new google.visualization.DataTable();
                            data.addColumn('string', 'ID de Tarea');
                            data.addColumn('string', 'Nombre de Tarea');
                            data.addColumn('string', 'Etapa');
                            data.addColumn('date', 'Fecha Inicio');
                            data.addColumn('date', 'Fecha Fin');
                            data.addColumn('number', 'Duración');
                            data.addColumn('number', 'Progreso (%)');
                            data.addColumn('string', 'Dependencias');

                            data.addRows([
                                <?= implode(",\n                                ", $ganttData) ?>
                            ]);

                            var options = {
                                height: data.getNumberOfRows() * 45 + 50,
                                gantt: {
                                    trackHeight: 30,
                                    percentEnabled: true,
                                    shadowEnabled: true,
                                    barCornerRadius: 4,
                                    arrow: { angle: 100, width: 2, color: '#6c757d', radius: 0 }
                                }
                            };

                            var chart = new google.visualization.Gantt(document.getElementById('gantt_chart_div'));
                            chart.draw(data, options);
                        }
                    </script>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="far fa-calendar-times fa-3x mb-3 opacity-25"></i>
                        <h5>No hay tareas listas para graficar</h5>
                        <p class="mb-0">Asegúrate de que las tareas tengan Fecha de Inicio y Compromiso lógicas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 border-top border-4 border-info mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users-cog me-2"></i> Comité del Proyecto</h6>
                <?php if ($isProjectLeader): ?>
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#inviteLeaderModal"><i class="fas fa-user-plus"></i></button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($lideresObj as $l): ?>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-user-tie text-info me-3"></i> <?= htmlspecialchars($l['FullName']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 border-top border-4 border-dark h-100">
            <div class="card-body">
                <h5 class="fw-bold border-bottom pb-2 mb-3">Resumen del Proyecto</h5>
                
                <div class="mb-3">
                    <label class="text-muted small fw-bold text-uppercase">Objetivo</label>
                    <p class="text-dark mb-0"><?= nl2br(htmlspecialchars($proyecto['Objective'] ?? 'N/A')) ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small fw-bold text-uppercase">Resultado esperado</label>
                    <p class="text-success mb-0 fw-bold"><?= nl2br(htmlspecialchars($proyecto['ExpectedResult'] ?? 'N/A')) ?></p>
                </div>

                <div class="bg-light p-3 rounded border mt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small fw-bold"><i class="fas fa-calendar-alt me-1"></i> Inicio Gral:</span>
                        <span class="text-dark small"><?= $proyecto['StartDate'] ? date('d/m/Y', strtotime($proyecto['StartDate'])) : 'Calculando...' ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small fw-bold"><i class="fas fa-flag-checkered me-1"></i> Cierre Gral:</span>
                        <span class="text-danger fw-bold small"><?= $proyecto['CommitmentDate'] ? date('d/m/Y', strtotime($proyecto['CommitmentDate'])) : 'Calculando...' ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold">Progreso global:</span>
                        <span class="badge bg-primary rounded-pill"><?= $proyecto['ProgressPercentage'] ?>%</span>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: <?= $proyecto['ProgressPercentage'] ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-dark m-0">Estructura de desglose (WBS)</h4>
            <?php if ($isProjectLeader): ?>
                <button type="button" class="btn btn-dark btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newStageModal">
                    <i class="fas fa-plus-layer me-1"></i> Agregar etapa
                </button>
            <?php endif; ?>
        </div>

        <?php if (count($etapas) > 0): ?>
            <div class="accordion shadow-sm" id="accordionStages">
                <?php foreach ($etapas as $index => $etapa): ?>
                    <?php $tareas = $tareasPorEtapa[$etapa['Id']] ?? []; ?>
                    
                    <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                        <h2 class="accordion-header" id="heading<?= $etapa['Id'] ?>">
                            <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?> bg-white fw-bold text-dark border-start border-4 border-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $etapa['Id'] ?>">
                                <div class="d-flex justify-content-between w-100 pe-3 align-items-center">
                                    <span>
                                        <span class="badge bg-primary me-2">Fase <?= $etapa['OrderIndex'] ?></span> 
                                        <?= htmlspecialchars($etapa['Name']) ?>
                                    </span>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="small text-muted d-none d-md-block">
                                            <i class="far fa-calendar-alt"></i> <?= $etapa['StartDate'] ? date('d/m', strtotime($etapa['StartDate'])) : '--' ?> al <?= $etapa['CommitmentDate'] ? date('d/m', strtotime($etapa['CommitmentDate'])) : '--' ?>
                                        </span>
                                        <span class="badge <?= $etapa['ProgressPercentage'] == 100 ? 'bg-success' : 'bg-primary' ?>"><?= $etapa['ProgressPercentage'] ?>%</span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?= $etapa['Id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#accordionStages">
                            <div class="accordion-body bg-light">
                                
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                                    <p class="text-muted small mb-0">
                                        <i class="fas fa-bullseye me-1 text-success"></i> Criterio: <b><?= htmlspecialchars($etapa['AcceptanceCriteria'] ?? 'No definido') ?></b>
                                    </p>
                                    <?php if ($isProjectLeader): ?>
                                        <button class="btn btn-link btn-sm text-secondary p-0" onclick="editStageModal('<?= $etapa['Id'] ?>', '<?= addslashes($etapa['Name']) ?>', '<?= addslashes($etapa['Description']) ?>', '<?= addslashes($etapa['AcceptanceCriteria']) ?>')"><i class="fas fa-edit"></i> Editar Etapa</button>
                                    <?php endif; ?>
                                </div>

                                <?php if (count($tareas) > 0): ?>
                                    <div class="list-group mb-3 shadow-sm">
                                        <?php foreach ($tareas as $t): ?>
                                            <div class="list-group-item list-group-item-action p-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="d-flex align-items-center mb-1">
                                                            <span class="badge bg-dark me-2" style="font-size: 0.65rem;"><?= $t['Folio'] ?></span>
                                                            <a href="task_details.php?id=<?= $t['Id'] ?>" class="fw-bold text-decoration-none text-dark"><?= htmlspecialchars($t['Name']) ?></a>
                                                            <?php if ($isProjectLeader): ?>
                                                                <button class="btn btn-link btn-sm text-secondary p-0 ms-2" onclick="editTaskModal('<?= $t['Id'] ?>', '<?= addslashes($t['Name']) ?>', '<?= $t['ResponsibleId'] ?>', '<?= $t['StartDate'] ?>', '<?= $t['CommitmentDate'] ?>', '<?= $t['PriorityId'] ?>')"><i class="fas fa-pencil-alt"></i></button>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="small text-muted">
                                                            <?php if($t['Responsable']): ?>
                                                                <i class="fas fa-user-check me-1 text-success"></i> <?= htmlspecialchars($t['Responsable']) ?>
                                                            <?php else: ?>
                                                                <i class="fas fa-users text-warning me-1"></i> <span class="text-warning-emphasis">En pizarra</span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if($t['DependsOnActivityId']): ?>
                                                                <span class="ms-3 text-warning-emphasis bg-warning bg-opacity-10 px-2 py-1 rounded border border-warning-subtle">
                                                                    <i class="fas fa-link me-1"></i> Dep: <?= htmlspecialchars($t['DependeDeNombre']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge <?= $t['ProgressPercentage'] == 100 ? 'bg-success' : 'bg-secondary' ?> mb-1">
                                                            <?= $t['ProgressPercentage'] ?>%
                                                        </span><br>
                                                        <span class="badge <?= $t['Prioridad'] == 'Crítica' ? 'bg-danger' : 'bg-light text-dark border' ?>" style="font-size: 0.7rem;">
                                                            <?= $t['Prioridad'] ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-3 text-muted border rounded bg-white mb-3">
                                        <p class="mb-0 small">Aún no hay tareas definidas para esta fase.</p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isProjectLeader): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="openTaskModal('<?= $etapa['Id'] ?>', '<?= htmlspecialchars($etapa['Name'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-plus me-1"></i> Nueva tarea
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 border-start border-4 border-warning text-center py-5 bg-white">
                <i class="fas fa-layer-group fa-4x text-muted mb-3 opacity-25"></i>
                <h4 class="text-dark fw-bold">Estructura vacía</h4>
                <p class="text-muted">El proyecto no tiene etapas. Comienza creando la "Fase 1".</p>
                <?php if ($isProjectLeader): ?>
                <div>
                    <button type="button" class="btn btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newStageModal">
                        Crear primera etapa
                    </button>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isProjectLeader): ?>
    <div class="modal fade" id="inviteLeaderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Invitar al Comité</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="invite_leader" value="1">
                        <p class="small text-muted">Los líderes invitados podrán ver y aprobar tareas de este proyecto.</p>
                        <label class="form-label fw-bold">Seleccionar Líder</label>
                        <select name="NewLeaderId" class="form-select" required>
                            <option value="">-- Seleccionar Jefe --</option>
                            <?php foreach($jefesDisponibles as $jefe): ?>
                                <option value="<?= $jefe['Id'] ?>"><?= htmlspecialchars($jefe['FullName']) ?> (<?= htmlspecialchars($jefe['Depto']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info text-white fw-bold">Invitar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newStageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="stageForm">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold" id="stageModalTitle">Agregar nueva etapa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="create_stage" id="stageActionInput" value="1">
                        <input type="hidden" name="StageId" id="editStageId">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre de la Etapa</label>
                            <input type="text" name="Name" id="editStageName" class="form-control" required placeholder="Ej. Levantamiento de requerimientos">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Criterio de aceptación</label>
                            <textarea name="AcceptanceCriteria" id="editStageCriteria" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea name="Description" id="editStageDesc" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-dark fw-bold">Guardar etapa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">Nueva tarea para: <span id="modalStageNameDisplay"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="create_task" value="1">
                        <input type="hidden" name="StageId" id="modalStageIdInput">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="TaskName" class="form-control" required placeholder="Ej. Instalar cableado">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha de inicio <span class="text-danger">*</span></label>
                                <input type="date" name="StartDate" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-danger">Fecha de compromiso <span class="text-danger">*</span></label>
                                <input type="date" name="CommitmentDate" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Prioridad <span class="text-danger">*</span></label>
                                <select name="PriorityId" class="form-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($prioridades as $p): ?>
                                        <option value="<?= $p['Id'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-success"><i class="fas fa-user-check me-1"></i> Asignar a</label>
                                <select name="ResponsibleId" class="form-select border-success">
                                    <option value="NULL" class="fw-bold">-- Dejar en pizarra (Sin asignar) --</option>
                                    <optgroup label="Comité del Proyecto (Líderes)">
                                        <?php foreach ($lideresObj as $lider): ?>
                                            <option value="<?= $lider['Id'] ?>"><?= htmlspecialchars($lider['FullName']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Técnicos Operativos">
                                        <?php foreach ($tecnicosDisponibles as $tech): ?>
                                            <option value="<?= $tech['Id'] ?>"><?= htmlspecialchars($tech['FullName']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-warning-emphasis"><i class="fas fa-link me-1"></i> Dependencia (Opcional)</label>
                            <select name="DependsOnActivityId" class="form-select">
                                <option value="">-- No depende de ninguna --</option>
                                <?php foreach ($todasLasTareas as $t): ?>
                                    <option value="<?= $t['Id'] ?>">Bloqueada por: <?= htmlspecialchars($t['Folio'] . ' - ' . $t['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Plan de acción</label>
                            <textarea name="SpecificActionPlan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Crear Tarea</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-pencil-alt me-2"></i> Ajustar Tarea</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="edit_task" value="1">
                        <input type="hidden" name="TaskId" id="editTaskId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre</label>
                            <input type="text" name="TaskName" id="editTaskName" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="StartDate" id="editTaskStart" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Fin <span class="text-danger">*</span></label>
                                <input type="date" name="CommitmentDate" id="editTaskEnd" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prioridad</label>
                            <select name="PriorityId" id="editTaskPriority" class="form-select" required>
                                <?php foreach ($prioridades as $p): ?>
                                    <option value="<?= $p['Id'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Responsable</label>
                            <select name="ResponsibleId" id="editTaskResp" class="form-select">
                                <option value="NULL" class="fw-bold">-- En Pizarra --</option>
                                <optgroup label="Comité del Proyecto (Líderes)">
                                    <?php foreach ($lideresObj as $lider): ?>
                                        <option value="<?= $lider['Id'] ?>"><?= htmlspecialchars($lider['FullName']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Técnicos Operativos">
                                    <?php foreach ($tecnicosDisponibles as $tech): ?>
                                        <option value="<?= $tech['Id'] ?>"><?= htmlspecialchars($tech['FullName']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-secondary fw-bold">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openTaskModal(stageId, stageName) {
        document.getElementById('modalStageIdInput').value = stageId;
        document.getElementById('modalStageNameDisplay').innerText = stageName;
        new bootstrap.Modal(document.getElementById('newTaskModal')).show();
    }

    function editStageModal(id, name, desc, criteria) {
        document.getElementById('stageActionInput').name = "edit_stage";
        document.getElementById('stageModalTitle').innerText = "Editar Etapa";
        document.getElementById('editStageId').value = id;
        document.getElementById('editStageName').value = name;
        document.getElementById('editStageDesc').value = desc;
        document.getElementById('editStageCriteria').value = criteria;
        new bootstrap.Modal(document.getElementById('newStageModal')).show();
    }

    function editTaskModal(id, name, respId, start, end, prioId) {
        document.getElementById('editTaskId').value = id;
        document.getElementById('editTaskName').value = name;
        document.getElementById('editTaskStart').value = start;
        document.getElementById('editTaskEnd').value = end;
        document.getElementById('editTaskPriority').value = prioId;
        document.getElementById('editTaskResp').value = respId ? respId : 'NULL';
        new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    }
    </script>
<?php endif; ?>

<?php require 'footer.php'; ?>