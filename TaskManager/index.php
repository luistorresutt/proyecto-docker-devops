<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserID'];
$roleName = $_SESSION['RoleName'];
$deptoId = $_SESSION['DepartmentID'];

$datosTecnico = [];
$datosAdmin = [];
$datosAuditor = [];

try {
    if ($roleName === 'Tecnico') {
        $sqlTecnico = "
            SELECT a.Id, a.Folio, a.Name as Tarea, p.Name as Prioridad, s.Name as Estado, a.CommitmentDate
            FROM activities a
            JOIN priorities p ON a.PriorityId = p.Id
            JOIN statuses s ON a.StatusId = s.Id
            WHERE a.ResponsibleId = ? AND s.Name NOT IN ('Finalizado', 'Cancelado')
            ORDER BY a.CommitmentDate ASC, p.Id DESC
            LIMIT 5
        ";
        $stmt = $pdo->prepare($sqlTecnico);
        $stmt->execute([$userId]);
        $datosTecnico = $stmt->fetchAll();

    } elseif ($roleName === 'Administrativo') {
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE PrimaryDepartmentId = ? AND StatusId NOT IN (4, 5)"); 
        $stmt->execute([$deptoId]);
        $datosAdmin['ProyectosActivos'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE PrimaryDepartmentId = ? AND ResponsibleId IS NULL AND StatusId != 5");
        $stmt->execute([$deptoId]);
        $datosAdmin['PendientesAsignar'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE PrimaryDepartmentId = ? AND CommitmentDate < NOW() AND StatusId NOT IN (4, 5)");
        $stmt->execute([$deptoId]);
        $datosAdmin['TareasAtrasadas'] = $stmt->fetchColumn();

        $sqlDistribucion = "
            SELECT COALESCE(tt.Name, 'Sin clasificar') as Tipo, COUNT(a.Id) as Total
            FROM activities a
            LEFT JOIN taskTypes tt ON a.TaskTypeId = tt.Id
            WHERE a.PrimaryDepartmentId = ? AND a.StatusId != 5 AND a.IsDeleted = 0
            GROUP BY tt.Name
        ";
        $stmt = $pdo->prepare($sqlDistribucion);
        $stmt->execute([$deptoId]);
        $datosAdmin['DistribucionTipos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($roleName === 'Auditor') {
        $stmt = $pdo->prepare("SELECT PlantId FROM departments WHERE Id = ?");
        $stmt->execute([$deptoId]);
        $plantId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(p.Id) FROM projects p 
            JOIN departments d ON p.PrimaryDepartmentId = d.Id 
            WHERE d.PlantId = ? AND p.IsDeleted = 0
        ");
        $stmt->execute([$plantId]);
        $datosAuditor['TotalProyectos'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT s.Name as Estado, COUNT(a.Id) as Total
            FROM activities a
            JOIN statuses s ON a.StatusId = s.Id
            JOIN departments d ON a.PrimaryDepartmentId = d.Id
            WHERE d.PlantId = ? AND a.IsDeleted = 0
            GROUP BY s.Name
        ");
        $stmt->execute([$plantId]);
        $datosAuditor['TareasPorEstado'] = $stmt->fetchAll();

        $sqlDistGlobal = "
            SELECT COALESCE(tt.Name, 'Sin clasificar') as Tipo, COUNT(a.Id) as Total
            FROM activities a
            LEFT JOIN taskTypes tt ON a.TaskTypeId = tt.Id
            JOIN departments d ON a.PrimaryDepartmentId = d.Id
            WHERE d.PlantId = ? AND a.StatusId != 5 AND a.IsDeleted = 0
            GROUP BY tt.Name
        ";
        $stmt = $pdo->prepare($sqlDistGlobal);
        $stmt->execute([$plantId]);
        $datosAuditor['DistribucionTipos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $errorDashboard = "Error al cargar los datos: " . $e->getMessage();
}

$estadoSalud = ['mensaje' => 'No hay suficientes datos', 'color' => 'text-muted', 'icono' => 'fa-minus-circle'];
if ($roleName === 'Administrativo' && !empty($datosAdmin['DistribucionTipos'])) {
    $totalTareas = 0;
    $correctivos = 0;
    $preventivos = 0;
    
    foreach($datosAdmin['DistribucionTipos'] as $d) {
        $totalTareas += $d['Total'];
        if ($d['Tipo'] == 'Mantenimiento Correctivo') $correctivos = $d['Total'];
        if ($d['Tipo'] == 'Mantenimiento Preventivo') $preventivos = $d['Total'];
    }

    if ($totalTareas > 0) {
        $porcentajeCorrectivo = ($correctivos / $totalTareas) * 100;
        $porcentajePreventivo = ($preventivos / $totalTareas) * 100;

        if ($porcentajeCorrectivo >= 50) {
            $estadoSalud = ['mensaje' => 'Estado Reactivo (Demasiados correctivos)', 'color' => 'text-danger', 'icono' => 'fa-fire'];
        } elseif ($porcentajePreventivo >= 60) {
            $estadoSalud = ['mensaje' => 'Estado Saludable (Altamente proactivo)', 'color' => 'text-success', 'icono' => 'fa-heartbeat'];
        } else {
            $estadoSalud = ['mensaje' => 'Estado Estable', 'color' => 'text-primary', 'icono' => 'fa-check-circle'];
        }
    }
}

require 'layout.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .widget-card { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
    .widget-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold text-dark">
            <i class="fas fa-hand-peace text-primary me-2"></i> Hola, <?= htmlspecialchars($_SESSION['FullName']) ?>
        </h2>
        <p class="text-muted mb-0">
            <?php 
                if($roleName === 'Tecnico') echo "Aquí tienes un resumen de tus asignaciones operativas.";
                elseif($roleName === 'Administrativo') echo "Resumen ejecutivo del desempeño de tu departamento.";
                elseif($roleName === 'Auditor') echo "Métricas globales de la planta.";
            ?>
        </p>
    </div>
</div>

<?php if (isset($errorDashboard)): ?>
    <div class="alert alert-danger shadow-sm border-start border-4 border-danger"><i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($errorDashboard) ?></div>
<?php endif; ?>

<?php if ($roleName === 'Tecnico'): ?>
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header bg-white text-dark fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-clipboard-list me-2 text-primary"></i> Mis Tareas Urgentes</span>
                    <a href="my_tasks.php" class="btn btn-sm btn-outline-primary fw-bold">Ver Todo</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($datosTecnico) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-secondary">
                                    <tr>
                                        <th class="ps-4">Folio / Actividad</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Compromiso</th>
                                        <th class="text-center pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($datosTecnico as $tarea): ?>
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($tarea['Folio']) ?></div>
                                                <div class="text-muted small text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($tarea['Tarea']) ?>">
                                                    <?= htmlspecialchars($tarea['Tarea']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $tarea['Prioridad'] == 'Crítica' ? 'bg-danger' : ($tarea['Prioridad'] == 'Alta' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                    <?= htmlspecialchars($tarea['Prioridad']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-uppercase small fw-bold text-muted">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> <?= htmlspecialchars($tarea['Estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="small fw-bold <?= strtotime($tarea['CommitmentDate']) < time() ? 'text-danger' : 'text-dark' ?>">
                                                    <?= $tarea['CommitmentDate'] ? date('d/m/Y', strtotime($tarea['CommitmentDate'])) : 'Sin fecha' ?>
                                                </span>
                                            </td>
                                            <td class="text-center pe-4">
                                                <a href="task_details.php?id=<?= $tarea['Id'] ?>" class="btn btn-sm btn-dark px-3 fw-bold shadow-sm">
                                                    Atender <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-check-circle fa-4x mb-3 text-success opacity-50"></i>
                            <h4 class="text-dark fw-bold">¡Excelente trabajo!</h4>
                            <p>No tienes tareas urgentes pendientes en este momento.</p>
                            <a href="shift_tasks_board.php" class="btn btn-primary mt-2">Tomar tareas de la pizarra</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($roleName === 'Administrativo'): ?>
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <a href="manage_projects.php" class="text-decoration-none">
                <div class="card widget-card shadow-sm border-0 border-start border-primary border-4 h-100 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase fw-bold mb-2">Proyectos Activos</h6>
                                <h2 class="mb-0 fw-bold text-dark"><?= $datosAdmin['ProyectosActivos'] ?></h2>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fas fa-project-diagram fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="shift_tasks_board.php" class="text-decoration-none">
                <div class="card widget-card shadow-sm border-0 border-start border-warning border-4 h-100 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-warning-emphasis text-uppercase fw-bold mb-2">Bandeja (Sin Asignar)</h6>
                                <h2 class="mb-0 fw-bold text-dark"><?= $datosAdmin['PendientesAsignar'] ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fas fa-chalkboard-teacher fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="team_followup.php" class="text-decoration-none">
                <div class="card widget-card shadow-sm border-0 border-start border-danger border-4 h-100 <?= $datosAdmin['TareasAtrasadas'] > 0 ? 'bg-danger bg-opacity-10' : 'bg-white' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-danger text-uppercase fw-bold mb-2">Tareas Atrasadas</h6>
                                <h2 class="mb-0 fw-bold text-danger"><?= $datosAdmin['TareasAtrasadas'] ?></h2>
                            </div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fas fa-exclamation-triangle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-3">
                    <span><i class="fas fa-chart-pie me-2 text-secondary"></i> Distribución de Carga</span>
                    <span class="badge bg-light <?= $estadoSalud['color'] ?> border"><i class="fas <?= $estadoSalud['icono'] ?> me-1"></i> <?= $estadoSalud['mensaje'] ?></span>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center" style="height: 300px;">
                    <?php if(!empty($datosAdmin['DistribucionTipos'])): ?>
                        <canvas id="adminChart"></canvas>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-bar fa-3x opacity-25 mb-2"></i>
                            <p class="fst-italic mb-0">No hay tareas activas para analizar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <a href="manage_projects.php" class="text-decoration-none">
                <div class="card widget-card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #212529 0%, #343a40 100%);">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-5">
                        <i class="fas fa-rocket fa-4x text-white mb-4 opacity-75"></i>
                        <h3 class="fw-bold text-white mb-2">Módulo de Proyectos</h3>
                        <p class="text-white opacity-75 mb-4 px-3">Administra iniciativas mayores, mejoras de infraestructura y gestión de tu WBS operativo.</p>
                        <span class="btn btn-light fw-bold px-4 py-2 text-dark rounded-pill">
                            Ingresar <i class="fas fa-arrow-right ms-2"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script>
        const adminData = <?= json_encode($datosAdmin['DistribucionTipos']) ?>;
        if(adminData && adminData.length > 0) {
            const labels = adminData.map(d => d.Tipo);
            const values = adminData.map(d => d.Total);
            
            const bgColors = labels.map(tipo => {
                if(tipo.includes('Preventivo')) return '#0d6efd'; 
                if(tipo.includes('Correctivo')) return '#dc3545'; 
                if(tipo.includes('Proyecto')) return '#198754'; 
                return '#6c757d';
            });

            new Chart(document.getElementById('adminChart'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: values, backgroundColor: bgColors, borderWidth: 0 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    },
                    cutout: '70%'
                }
            });
        }
    </script>
<?php endif; ?>

<?php if ($roleName === 'Auditor'): ?>
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <a href="site_projects.php" class="text-decoration-none">
                <div class="card widget-card shadow-sm border-0 bg-dark text-white h-100" style="background: linear-gradient(135deg, #191c1f 0%, #2c3237 100%);">
                    <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                        <i class="fas fa-city fa-2x text-secondary mb-2"></i>
                        <h6 class="text-uppercase fw-bold mb-3 text-warning">Proyectos Activos (Planta)</h6>
                        <h1 class="display-3 fw-bold mb-0 text-white"><?= $datosAuditor['TotalProyectos'] ?></h1>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-tasks me-2 text-primary"></i> Termómetro de Actividades
                </div>
                <div class="card-body">
                    <div class="row text-center h-100 align-items-center">
                        <?php foreach ($datosAuditor['TareasPorEstado'] as $estado): ?>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded bg-light border">
                                    <h2 class="fw-bold text-dark mb-1"><?= $estado['Total'] ?></h2>
                                    <span class="text-muted small text-uppercase fw-bold"><i class="fas fa-circle me-1 text-secondary" style="font-size:0.5rem;"></i> <?= htmlspecialchars($estado['Estado']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($datosAuditor['TareasPorEstado'])): ?>
                            <p class="text-muted w-100">No hay actividades registradas en la planta.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-chart-pie me-2 text-info"></i> Distribución Estratégica del Mantenimiento (Global)
                </div>
                <div class="card-body d-flex justify-content-center" style="height: 350px;">
                    <?php if(!empty($datosAuditor['DistribucionTipos'])): ?>
                        <canvas id="auditorChart"></canvas>
                    <?php else: ?>
                        <div class="d-flex flex-column justify-content-center align-items-center text-muted">
                            <i class="fas fa-chart-pie fa-3x opacity-25 mb-3"></i>
                            <p class="mb-0">Sin datos suficientes para graficar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const auditorData = <?= json_encode($datosAuditor['DistribucionTipos']) ?>;
        if(auditorData && auditorData.length > 0) {
            const labels = auditorData.map(d => d.Tipo);
            const values = auditorData.map(d => d.Total);
            
            const bgColors = labels.map(tipo => {
                if(tipo.includes('Preventivo')) return '#0d6efd';
                if(tipo.includes('Correctivo')) return '#dc3545';
                if(tipo.includes('Proyecto')) return '#198754';
                return '#6c757d';
            });

            new Chart(document.getElementById('auditorChart'), {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{ data: values, backgroundColor: bgColors, borderWidth: 1, borderColor: '#fff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right' } }
                }
            });
        }
    </script>
<?php endif; ?>

<?php require 'footer.php'; ?>