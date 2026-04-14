<?php
require_once 'conexion.php';

header('Content-Type: text/plain');

echo "============================================\n";
echo " INICIANDO MOTOR DE TAREAS PROGRAMADAS \n";
echo " Fecha de ejecución: " . date('Y-m-d H:i:s') . "\n";
echo "============================================\n\n";

try {
    $pdo->beginTransaction();

    /* ---------------------------------------------------------
       FASE 1: AUTO-CANCELAR RUTINAS NO ATENDIDAS
       --------------------------------------------------------- */
    echo "FASE 1: Limpieza de rutinas vencidas...\n";
    
    $sqlLimpieza = "SELECT Id, Folio FROM Activities 
                    WHERE Folio LIKE 'RUT-%' 
                    AND StatusId = 2 
                    AND DATE(RowVersion) < CURDATE()";
    $vencidas = $pdo->query($sqlLimpieza)->fetchAll();

    $canceladas = 0;
    foreach ($vencidas as $v) {
        $pdo->prepare("UPDATE Activities SET StatusId = 5 WHERE Id = ?")->execute([$v['Id']]);
        
        $commentId = generar_uuid();
        $pdo->prepare("INSERT INTO ActivityComments (Id, ActivityId, UserId, CommentText) VALUES (?, ?, NULL, ?)")
            ->execute([$commentId, $v['Id'], "SISTEMA AUTOMÁTICO: Rutina cancelada por caducidad (Posible día inhábil o falta de personal en el turno)."]);
        
        $canceladas++;
        echo " - Cancelada: {$v['Folio']} \n";
    }
    echo " Total canceladas: $canceladas\n\n";


    /* ---------------------------------------------------------
       FASE 2: GENERAR RUTINAS DEL DÍA CON MULTIPLICADOR DE TURNO
       --------------------------------------------------------- */
    echo "FASE 2: Generación de nuevas rutinas...\n";

    $sqlTemplates = "SELECT * FROM ActivityTemplates WHERE IsActive = 1 AND NextRunDate <= CURDATE()";
    $templates = $pdo->query($sqlTemplates)->fetchAll();

    $creadas = 0;
    foreach ($templates as $tpl) {
        
        $sqlBoss = "SELECT u.Id FROM Users u 
                    JOIN Roles r ON u.RoleId = r.Id 
                    WHERE u.DepartmentId = ? AND r.Name = 'Administrativo' AND u.IsActive = 1 
                    LIMIT 1";
        $stmtBoss = $pdo->prepare($sqlBoss);
        $stmtBoss->execute([$tpl['PrimaryDepartmentId']]);
        $jefeId = $stmtBoss->fetchColumn();

        if (!$jefeId) {
            $jefeId = $pdo->query("SELECT u.Id FROM Users u JOIN Roles r ON u.RoleId = r.Id WHERE r.Name = 'Administrativo' LIMIT 1")->fetchColumn();
        }

        $turnosAGenerar = [];
        $opcionTurno = $tpl['TargetShifts'];

        if ($opcionTurno === 'Todos los turnos' || $opcionTurno === 'Turnos 1 y 2' || strpos(strtolower($opcionTurno), 'ambos') !== false) {
            $sqlActiveShifts = "SELECT Name FROM Shifts WHERE IsActive = 1 ORDER BY StartTime ASC";
            $turnosAGenerar = $pdo->query($sqlActiveShifts)->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($turnosAGenerar)) {
                $turnosAGenerar = ['Turno Único'];
            }
        } else {
            $turnosAGenerar = [$opcionTurno]; 
        }

        foreach ($turnosAGenerar as $nombreTurno) {
            $newActivityId = generar_uuid();
            $folio = 'RUT-' . date('ym') . '-' . rand(1000, 9999);
            $statusId = 2; 
            $hoy = date('Y-m-d');
            
            $tituloConTurno = $tpl['Name'] . ' [' . $nombreTurno . ']';

        $insertActivity = "INSERT INTO Activities (
            Id, Folio, RequesterId, PrimaryDepartmentId, RequesterDepartmentId, Name, 
            SpecificActionPlan, PriorityId, TaskTypeId, StatusId, CommitmentDate
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $pdo->prepare($insertActivity)->execute([
            $newActivityId, 
            $folio, 
            $jefeId,
            $tpl['PrimaryDepartmentId'], 
            $tpl['PrimaryDepartmentId'], 
            $tituloConTurno,
            $tpl['SpecificActionPlan'], 
            $tpl['PriorityId'], 
            $tpl['DefaultTaskTypeId'],
            $statusId,
            $hoy 
        ]);

            $creadas++;
            echo " + Generada: {$folio} ({$tituloConTurno})\n";
        }

        $nextRun = new DateTime($tpl['NextRunDate']);
        if ($tpl['RecurrenceType'] == 'Diaria') {
            $nextRun->modify('+1 day');
        } elseif ($tpl['RecurrenceType'] == 'Semanal') {
            $nextRun->modify('+7 days');
        } elseif ($tpl['RecurrenceType'] == 'Mensual') {
            $nextRun->modify('+1 month');
        }

        $updateTpl = "UPDATE ActivityTemplates SET NextRunDate = ? WHERE Id = ?";
        $pdo->prepare($updateTpl)->execute([$nextRun->format('Y-m-d'), $tpl['Id']]);
        
        echo "   -> Plantilla actualizada para próxima ejecución: {$nextRun->format('Y-m-d')}\n";
    }
    
    echo "\n Total de tickets generados hoy: $creadas\n\n";

    $pdo->commit();
    echo "PROCESO FINALIZADO CON ÉXITO.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR CRÍTICO DURANTE LA EJECUCIÓN: " . $e->getMessage() . "\n";
}
?>