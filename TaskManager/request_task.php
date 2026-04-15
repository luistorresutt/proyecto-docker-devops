<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$errores = [];

$deptos = $pdo->query("SELECT Id, Name FROM separtments WHERE IsDeleted = 0 ORDER BY Name")->fetchAll();
$prioridades = $pdo->query("SELECT Id, Name FROM priorities ORDER BY Id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['Name'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $targetDeptId = $_POST['TargetDepartmentId'] ?? '';
    $priorityId = $_POST['PriorityId'] ?? '';
    $commitmentDate = !empty($_POST['CommitmentDate']) ? $_POST['CommitmentDate'] : null;

    if (empty($name) || empty($targetDeptId) || empty($priorityId)) {
        $errores[] = "Por favor, completa todos los campos obligatorios.";
    }

    if (empty($errores)) {
        $newId = generar_uuid();
        $folio = 'REQ-' . date('ym') . '-' . rand(1000, 9999);
        
        $requesterId = $_SESSION['UserID'];
        $requesterDeptId = $_SESSION['DepartmentID'];
        $statusId = 1;
        $sql = "INSERT INTO activities (
                    Id, Folio, RequesterId, RequesterDepartmentId, 
                    PrimaryDepartmentId, Name, SpecificActionPlan, 
                    StatusId, PriorityId, CommitmentDate
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([
                $newId, $folio, $requesterId, $requesterDeptId,
                $targetDeptId, $name, $description, $statusId, $priorityId, $commitmentDate
            ]);

            $_SESSION['SuccessMessage'] = "¡Solicitud enviada con éxito! Tu folio de seguimiento es: <b>" . $folio . "</b>";
            header("Location: index.php");
            exit;
            
        } catch (Exception $e) {
            $errores[] = "Error al crear la solicitud: " . $e->getMessage();
        }
    }
}

require 'layout.php';
?>

<div class="mt-4 mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-plus-circle me-2"></i> Solicitud de Tarea</h2>
    <p class="text-muted">Genera un ticket para solicitar apoyo a otro departamento.</p>
</div>

<div class="container d-flex justify-content-center">
    <div class="card bg-white p-4 shadow-sm" style="width: 100%; max-width: 700px; border-radius: 15px; border-top: 5px solid #007bff;">
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <?= implode("<br>", array_map('htmlspecialchars', $errores)) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="request_task.php">
            
            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Título de la solicitud <span class="text-danger">*</span></label>
                <input type="text" name="Name" class="form-control" placeholder="Ej. Reparación de aire acondicionado en Sala A" required value="<?= isset($_POST['Name']) ? htmlspecialchars($_POST['Name']) : '' ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-dark">Departamento destino <span class="text-danger">*</span></label>
                    <select name="TargetDepartmentId" class="form-select" required>
                        <option value="">-- ¿A quién le pides ayuda? --</option>
                        <?php foreach($deptos as $d): ?>
                            <option value="<?= $d['Id'] ?>" <?= (isset($_POST['TargetDepartmentId']) && $_POST['TargetDepartmentId'] == $d['Id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-dark">Prioridad <span class="text-danger">*</span></label>
                    <select name="PriorityId" class="form-select" required>
                        <option value="">-- Nivel de urgencia --</option>
                        <?php foreach($prioridades as $p): ?>
                            <option value="<?= $p['Id'] ?>" <?= (isset($_POST['PriorityId']) && $_POST['PriorityId'] == $p['Id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Requerimiento</label>
                    <select name="TaskTypeId" class="form-select" required>
                        <?php 
                        $tipos = $pdo->query("SELECT * FROM tasktypes")->fetchAll();
                        foreach($tipos as $tipo): 
                        ?>
                            <option value="<?= $tipo['Id'] ?>" <?= $tipo['Name'] == 'Mantenimiento Correctivo' ? 'selected' : '' ?>>
                                <?= $tipo['Name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Fecha deseada de cumplimiento</label>
                <input type="date" name="CommitmentDate" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= isset($_POST['CommitmentDate']) ? htmlspecialchars($_POST['CommitmentDate']) : '' ?>">
                <div class="form-text">Indica para cuándo necesitas idealmente que este ticket esté resuelto.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-dark">Descripción del problema o petición</label>
                <textarea name="Description" class="form-control" rows="4" placeholder="Describe detalladamente lo que necesitas..."><?= isset($_POST['Description']) ? htmlspecialchars($_POST['Description']) : '' ?></textarea>
            </div>

            <hr>

            <div class="text-end">
                <a href="index.php" class="btn btn-outline-secondary px-4 me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-paper-plane me-2"></i> Enviar solicitud</button>
            </div>
        </form>

    </div>
</div>

<?php require 'footer.php'; ?>