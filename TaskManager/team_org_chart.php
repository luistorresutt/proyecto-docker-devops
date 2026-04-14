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

$stmtDepto = $pdo->prepare("SELECT Name FROM Departments WHERE Id = ?");
$stmtDepto->execute([$deptoId]);
$deptoName = $stmtDepto->fetchColumn() ?: 'Tu Departamento';

$sql = "SELECT u.Id, u.FullName, u.JobTitle, u.SupervisorId, r.Name as RoleName,
               CASE WHEN u.Id = ? THEN 1 ELSE 0 END as IsCurrentUser
        FROM Users u
        JOIN Roles r ON u.RoleId = r.Id
        WHERE u.DepartmentId = ? AND u.IsActive = 1
        ORDER BY r.Id ASC, u.FullName ASC"; 

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $deptoId]);
$usuarios = $stmt->fetchAll();

$orgData = [];

foreach ($usuarios as $u) {
    $id = $u['Id'];
    $parentId = $u['SupervisorId'] ? $u['SupervisorId'] : ''; 
    
    $tooltip = addslashes(htmlspecialchars($u['RoleName']));
    $nombre = addslashes(htmlspecialchars($u['FullName']));
    $puesto = addslashes(htmlspecialchars($u['JobTitle'] ?? 'Sin puesto asignado'));
    
    $borderColor = 'border-secondary';
    $bgColor = 'bg-light';
    $icon = 'fa-user-hard-hat text-secondary';

    if ($u['RoleName'] === 'Administrativo') {
        $borderColor = 'border-primary';
        $icon = 'fa-user-tie text-primary';
    }
    
    $highlightClass = '';
    if ($u['IsCurrentUser']) {
        $borderColor = 'border-success';
        $bgColor = 'bg-success bg-opacity-10';
        $highlightClass = 'shadow-lg transform-scale';
    }

    $htmlNode = "<div class=\"org-card border border-3 $borderColor $bgColor $highlightClass rounded p-3 text-center\" style=\"min-width: 200px;\">" . 
                "<div class=\"mb-2\"><i class=\"fas $icon fa-2x\"></i></div>" . 
                "<h6 class=\"fw-bold text-dark mb-1\">$nombre</h6>" . 
                "<span class=\"badge bg-dark text-white mb-0\">$puesto</span>" . 
                "</div>";

    $orgData[] = "[{v: '$id', f: '$htmlNode'}, '$parentId', '$tooltip']";
}

$hasOrgData = count($orgData) > 0;

require 'layout.php';
?>

<style>
    .google-visualization-orgchart-node {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .google-visualization-orgchart-table {
        border-collapse: separate !important;
        border-spacing: 15px !important;
    }
    .google-visualization-orgchart-lineleft, 
    .google-visualization-orgchart-lineright, 
    .google-visualization-orgchart-linebottom {
        border-color: #adb5bd !important;
        border-width: 2px !important;
    }
    .transform-scale {
        transform: scale(1.05);
        border-width: 4px !important;
    }
    .org-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .org-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1) !important;
        cursor: default;
    }
</style>

<div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold text-dark"><i class="fas fa-sitemap me-2 text-primary"></i> Estructura Organizacional</h2>
        <p class="text-muted mb-0">Organigrama oficial de tu departamento (<b><?= htmlspecialchars($deptoName) ?></b>).</p>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body overflow-auto text-center" style="min-height: 500px; background-color: #f8f9fa;">
                
                <?php if ($hasOrgData): ?>
                    <div id="orgchart_div" class="d-inline-block mt-4 mb-4"></div>

                    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
                    <script type="text/javascript">
                        google.charts.load('current', {packages:["orgchart"]});
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            var data = new google.visualization.DataTable();
                            data.addColumn('string', 'Name');
                            data.addColumn('string', 'Manager');
                            data.addColumn('string', 'ToolTip');

                            data.addRows([
                                <?= implode(",\n                                ", $orgData) ?>
                            ]);

                            var chart = new google.visualization.OrgChart(document.getElementById('orgchart_div'));
                            
                            chart.draw(data, {
                                allowHtml: true,
                                size: 'large',
                                allowCollapse: true
                            });
                        }
                    </script>
                <?php else: ?>
                    <div class="py-5 text-muted">
                        <i class="fas fa-users-slash fa-4x mb-3 opacity-25"></i>
                        <h5>No se encontraron usuarios</h5>
                        <p>No hay personal activo registrado en este departamento.</p>
                    </div>
                <?php endif; ?>

            </div>
            <div class="card-footer bg-white border-top-0 pt-0 pb-3">
                <div class="d-flex justify-content-center gap-4 small fw-bold">
                    <span class="text-muted"><i class="fas fa-square text-success me-1"></i> Tú</span>
                    <span class="text-muted"><i class="fas fa-square text-primary me-1"></i> Administrativo</span>
                    <span class="text-muted"><i class="fas fa-square text-secondary me-1"></i> Operativo</span>
                </div>
                <div class="text-center small text-muted mt-2">
                    <i>Tip: Puedes hacer doble clic sobre un líder para expandir o contraer a su equipo.</i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>