<?php
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$taskId = $_GET['id'] ?? '';
$userId = $_SESSION['UserID'];
$roleName = $_SESSION['RoleName'];
$errores = [];

$sql = "SELECT a.*, p.Name as Prioridad, s.Name as Estado, d.Name as DeptoOrigen, u.FullName as Solicitante, 
               t.FullName as Tecnico,
               dep.Folio as DepFolio, dep.Name as DepName, dep.ProgressPercentage as DepProgress, 
               dep.StatusId as DepStatusId, dt.FullName as DepTechName
        FROM activities a
        JOIN priorities p ON a.PriorityId = p.Id
        JOIN statuses s ON a.StatusId = s.Id
        LEFT JOIN departments d ON a.RequesterDepartmentId = d.Id
        LEFT JOIN users u ON a.RequesterId = u.Id
        LEFT JOIN users t ON a.ResponsibleId = t.Id
        LEFT JOIN activities dep ON a.DependsOnActivityId = dep.Id
        LEFT JOIN users dt ON dep.ResponsibleId = dt.Id
        WHERE a.Id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['ErrorMessage'] = "Tarea no encontrada.";
    header("Location: index.php");
    exit;
}

$dependenciaBloqueada = false;
if (!empty($task['DependsOnActivityId'])) {
    if ($task['DepStatusId'] != 5 && $task['DepProgress'] < 100) {
        $dependenciaBloqueada = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress']) && $task['ResponsibleId'] === $userId && !$dependenciaBloqueada) {
    $nuevoProgreso = (int)$_POST['ProgressPercentage'];
    $comentario = trim($_POST['CommentText']);
    $imagePath = null; 

    if (empty($comentario)) {
        $errores[] = "Es obligatorio agregar un comentario.";
    }

    if (isset($_FILES['EvidenceImage']) && $_FILES['EvidenceImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['EvidenceImage']['tmp_name'];
        $fileName = $_FILES['EvidenceImage']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExts)) {
            $newFileName = uniqid('evidencia_') . '.' . $fileExtension;
            $destPath = 'uploads/' . $newFileName;
            
            if(move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $destPath;
            } else {
                $errores[] = "Hubo un error al guardar la imagen en el servidor.";
            }
        } else {
            $errores[] = "Solo se permiten imágenes (JPG, JPEG, PNG, GIF).";
        }
    }

    if (empty($errores)) {
        try {
            $nuevoStatus = 3; 
            if ($nuevoProgreso == 0) $nuevoStatus = 2; 
            if ($nuevoProgreso == 100) $nuevoStatus = 4; 

            $sqlUpd = "UPDATE activities SET ProgressPercentage = ?, StatusId = ? WHERE Id = ?";
            $pdo->prepare($sqlUpd)->execute([$nuevoProgreso, $nuevoStatus, $taskId]);

            $commentId = generar_uuid();
            $sqlComm = "INSERT INTO activitycomments (Id, ActivityId, UserId, CommentText, ImagePath) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sqlComm)->execute([$commentId, $taskId, $userId, "AVANCE $nuevoProgreso%: " . $comentario, $imagePath]);

            if (!empty($task['ProjectId'])) {
                $pdo->prepare("UPDATE projectstages ps SET ProgressPercentage = (SELECT COALESCE(AVG(ProgressPercentage), 0) FROM activities WHERE StageId = ps.Id AND IsDeleted = 0) WHERE Id = ?")->execute([$task['StageId']]);
                $pdo->prepare("UPDATE projects p SET ProgressPercentage = (SELECT COALESCE(AVG(ProgressPercentage), 0) FROM projectstages WHERE ProjectId = p.Id AND IsDeleted = 0) WHERE Id = ?")->execute([$task['ProjectId']]);
            }

            $_SESSION['SuccessMessage'] = "Avance reportado correctamente.";
            header("Location: task_details.php?id=" . $taskId);
            exit;
        } catch (Exception $e) {
            $errores[] = "Error al actualizar: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_task']) && $roleName === 'Administrativo') {
    try {
        $pdo->prepare("UPDATE activities SET StatusId = 5, CompletedDate = NOW() WHERE Id = ?")->execute([$taskId]);
        $commentId = generar_uuid();
        $pdo->prepare("INSERT INTO activitycomments (Id, ActivityId, UserId, CommentText) VALUES (?, ?, ?, ?)")
            ->execute([$commentId, $taskId, $userId, "VISTO BUENO: Tarea aprobada y finalizada."]);

        $_SESSION['SuccessMessage'] = "La tarea ha sido cerrada.";
        header("Location: task_details.php?id=" . $taskId);
        exit;
    } catch (Exception $e) { $errores[] = "Error al aprobar."; }
}

$comments = $pdo->prepare("SELECT c.*, u.FullName, r.Name as RoleName FROM activitycomments c LEFT JOIN users u ON c.UserId = u.Id LEFT JOIN roles r ON u.RoleId = r.Id WHERE c.ActivityId = ? ORDER BY c.CreatedAt DESC");
$comments->execute([$taskId]);
$historial = $comments->fetchAll();

$badgeClass = 'bg-secondary';
if ($task['Estado'] == 'En revisión') $badgeClass = 'bg-info text-dark';
if ($task['Estado'] == 'En proceso') $badgeClass = 'bg-primary';
if ($task['Estado'] == 'Finalizado') $badgeClass = 'bg-success';

require 'layout.php';
?>

<div class="row mt-4 mb-5">
    <div class="col-md-8">
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger shadow-sm"><?= implode("<br>", array_map('htmlspecialchars', $errores)) ?></div>
        <?php endif; ?>

        <?php if ($dependenciaBloqueada && $task['ResponsibleId'] === $userId): ?>
            <div class="alert alert-warning shadow-sm border-start border-4 border-warning d-flex align-items-center mb-4">
                <i class="fas fa-lock fa-3x me-3 text-warning"></i>
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Actividad bloqueada temporalmente</h5>
                    <p class="mb-0 text-dark">
                        No puedes iniciar ni reportar avances hasta que la tarea <b><?= htmlspecialchars($task['DepFolio'] . ' - ' . $task['DepName']) ?></b> 
                        (actualmente al <?= $task['DepProgress'] ?>%) se haya completado.
                    </p>
                    <hr class="border-warning my-2">
                    <small class="fw-bold">
                        <i class="fas fa-user-hard-hat me-1"></i> Coordínate con: <span class="text-primary"><?= htmlspecialchars($task['DepTechName'] ?? 'Tarea en pizarra (Sin asignar)') ?></span>
                    </small>
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4 border-top border-4 <?= $task['Estado'] == 'Finalizado' ? 'border-success' : 'border-primary' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-dark mb-2"><?= $task['Folio'] ?></span>
                        <h3 class="fw-bold mb-1"><?= htmlspecialchars($task['Name']) ?></h3>
                        <p class="text-muted small mb-0">
                            Solicita/Líder: <b><?= htmlspecialchars($task['Solicitante'] ?? 'Sistema (Rutina)') ?></b>
                            <?php if($task['ProjectId']): ?>
                                | <span class="badge bg-info text-dark ms-1"><i class="fas fa-project-diagram me-1"></i> Tarea de Proyecto</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge <?= $badgeClass ?> p-2 fs-6 mb-1"><?= $task['Estado'] ?> (<?= $task['ProgressPercentage'] ?>%)</span>
                    </div>
                </div>
                
                <div class="d-flex gap-3 mb-3 small fw-bold">
                    <?php if($task['StartDate']): ?>
                        <span class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Inicio: <span class="text-dark"><?= date('d/m/Y', strtotime($task['StartDate'])) ?></span></span>
                    <?php endif; ?>
                    <?php if($task['CommitmentDate']): ?>
                        <span class="text-muted"><i class="fas fa-flag-checkered me-1"></i> Compromiso: <span class="text-danger"><?= date('d/m/Y', strtotime($task['CommitmentDate'])) ?></span></span>
                    <?php endif; ?>
                </div>
                
                <div class="bg-light p-3 rounded mb-0">
                    <h6 class="fw-bold text-dark"><i class="fas fa-clipboard-check me-2"></i> Instrucciones técnicas:</h6>
                    <p class="text-secondary mb-0" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($task['SpecificActionPlan'] ?? 'Sin instrucciones especiales.')) ?></p>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3"><i class="fas fa-history me-2 text-muted"></i> Bitácora de Actividad</h5>
        <div class="timeline-container">
            <?php foreach($historial as $c): ?>
                <div class="card mb-3 border-0 shadow-sm border-start border-3 <?= strpos($c['CommentText'], 'PASO DE ESTAFETA') !== false ? 'border-secondary' : ($c['RoleName'] == 'Administrativo' ? 'border-warning' : 'border-primary') ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($c['FullName'] ?? 'Sistema Automático') ?></span>
                                <span class="badge bg-light text-muted ms-1"><?= $c['RoleName'] ?? 'Bot' ?></span>
                            </div>
                            <small class="text-muted"><i class="far fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($c['CreatedAt'])) ?></small>
                        </div>
                        <p class="mb-2 text-secondary" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($c['CommentText'])) ?></p>
                        
                        <?php if(!empty($c['ImagePath'])): ?>
                            <div class="mt-2 text-center bg-light p-2 rounded border">
                                <a href="<?= htmlspecialchars($c['ImagePath']) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($c['ImagePath']) ?>" alt="Evidencia" class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
                                </a>
                                <div class="small text-muted mt-1"><i class="fas fa-search-plus me-1"></i> Clic para ampliar</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($historial)): ?>
                <p class="text-muted fst-italic">No hay comentarios ni avances reportados aún.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <?php if ($task['StatusId'] == 4 && $roleName === 'Administrativo'): ?>
            <div class="card shadow-sm border-0 border-info mb-4">
                <div class="card-header bg-info text-white fw-bold">
                    <i class="fas fa-search-dollar me-2"></i> Acción Requerida
                </div>
                <div class="card-body text-center p-4">
                    <h5 class="fw-bold">El técnico reportó 100%</h5>
                    <p class="text-muted small">Revisa la evidencia. Si el trabajo está completado, cierra el ticket.</p>
                    <form method="POST">
                        <button type="submit" name="approve_task" class="btn btn-success fw-bold w-100 mb-2">
                            <i class="fas fa-check-double me-2"></i> Aprobar y Finalizar
                        </button>
                    </form>
                </div>
            </div>
            
        <?php elseif ($task['StatusId'] == 5 || $task['StatusId'] == 6): ?>
            <div class="card shadow-sm border-0 bg-light mb-4 text-center p-4">
                <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold text-dark">Tarea Cerrada</h5>
                <p class="text-muted mb-0">Esta solicitud ha sido finalizada.</p>
            </div>
            
        <?php elseif ($task['StatusId'] == 4): ?>
             <div class="card shadow-sm border-0 bg-info bg-opacity-10 mb-4 text-center p-4 border border-info">
                <i class="fas fa-user-clock fa-3x text-info mb-3"></i>
                <h5 class="fw-bold text-dark">En Revisión</h5>
                <p class="text-info-emphasis mb-0">Has reportado el 100%. Esperando el visto bueno de tu supervisor.</p>
            </div>

        <?php elseif ($task['ResponsibleId'] === $userId && !$dependenciaBloqueada): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="fas fa-camera me-2"></i> Reportar Avance
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Progreso Actual</label>
                            <select name="ProgressPercentage" class="form-select bg-light border-secondary">
                                <?php for($i=0; $i<=100; $i+=10): ?>
                                    <option value="<?= $i ?>" <?= $task['ProgressPercentage'] == $i ? 'selected' : '' ?>><?= $i ?>%</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Comentario</label>
                            <textarea name="CommentText" class="form-control" rows="3" required placeholder="Describe los avances..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary"><i class="fas fa-image me-1"></i> Adjuntar evidencia (Opcional)</label>
                            <input type="file" name="EvidenceImage" class="form-control form-control-sm" accept="image/*">
                        </div>

                        <button type="submit" name="update_progress" class="btn btn-primary w-100 fw-bold">
                            Guardar Avance
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'footer.php'; ?>