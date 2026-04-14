<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InnoviTech Management</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />
    
    <style>
    body { background-color: #E0E0E0 !important; }
    
    /* Navbar Styling */
    .navbar-custom { background-color: #212529; border-bottom: 3px solid #0d6efd; }
    .navbar-custom .nav-link { transition: color 0.2s; }
    .navbar-custom .nav-link:hover { color: #0d6efd !important; }
    
    .body-content {
        max-width: 1150px; 
        margin: 0 auto; 
        padding: 0 20px;
    }
    
    /* Dropdown animado y estilizado */
    .dropdown-menu { border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; }
    .dropdown-item { padding: 0.5rem 1.5rem; font-size: 0.95rem; transition: background-color 0.2s; }
    .dropdown-item:hover { background-color: #f8f9fa; }
    
    /* Perfil en Navbar */
    .nav-profile-text { line-height: 1.1; margin-right: 10px; }
    .nav-profile-name { display: block; font-weight: bold; font-size: 0.95rem; }
    .nav-profile-job { display: block; font-size: 0.75rem; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.5px;}

    @media (max-width: 768px) {
        .navbar-nav { margin-top: 15px; }
        .dropdown-menu { border: none; background-color: #2c3237; }
        .dropdown-item { color: white; }
        .dropdown-item:hover { background-color: #3d454d; color: white;}
        .nav-profile-text { text-align: left; margin-bottom: 10px; }
    }
    </style>
</head>
<body>
    <div id="flash-message-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1050; width: 100%; max-width: 600px; padding: 0 15px;">
        <?php if (isset($_SESSION['SuccessMessage'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow border-0 border-start border-4 border-success" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['SuccessMessage'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['SuccessMessage']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['ErrorMessage'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow border-0 border-start border-4 border-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['ErrorMessage'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['ErrorMessage']); ?>
        <?php endif; ?>
    </div>

    <nav class="navbar navbar-expand-md navbar-dark navbar-custom mb-4 shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/innovitech.png" alt="Logo" style="height: 35px; margin-right: 10px;" onerror="this.src='https://via.placeholder.com/150x40/212529/FFFFFF?text=INNOVITECH'" />
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto gap-1">
                    
                    <li class="nav-item">
                        <a class="nav-link fw-bold" href="index.php"><i class="fas fa-home me-1"></i> Inicio</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-tools me-1"></i> Operación
                        </a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item fw-bold" href="my_tasks.php"><i class="fas fa-list-check me-2 text-success"></i> Mis Tareas</a></li>
                            <li><a class="dropdown-item" href="shift_tasks_board.php"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i> Pizarra del Turno</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="team_org_chart.php"><i class="fas fa-sitemap me-2 text-info"></i> Directorio y Organigrama</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-headset me-1"></i> HelpDesk
                        </a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="request_task.php"><i class="fas fa-plus-circle me-2 text-warning"></i> Crear Nueva Solicitud</a></li>
                            <li><a class="dropdown-item" href="requested_tasks.php"><i class="fas fa-search-location me-2 text-secondary"></i> Rastrear Mis Solicitudes</a></li>
                        </ul>
                    </li>

                    <?php if (isset($_SESSION['RoleName']) && $_SESSION['RoleName'] === 'Administrativo'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-bold text-info" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-shield-alt me-1"></i> Supervisión
                            </a>
                            <ul class="dropdown-menu shadow border-info">
                                <li><a class="dropdown-item" href="assign_tasks.php"><i class="fas fa-inbox me-2 text-danger"></i> Bandeja de Entrada</a></li>
                                <li><a class="dropdown-item" href="team_followup.php"><i class="fas fa-users me-2 text-primary"></i> Desempeño del Equipo</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="manage_tasks_templates.php"><i class="fas fa-calendar-check me-2 text-success"></i> Rutinas y Preventivos</a></li>
                                <li><a class="dropdown-item" href="manage_shifts.php"><i class="fas fa-user-clock me-2 text-secondary"></i> Gestión de Turnos</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['RoleName']) && in_array($_SESSION['RoleName'], ['Administrativo', 'Auditor'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-project-diagram me-1"></i> Proyectos
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item fw-bold" href="manage_projects.php"><i class="fas fa-folder-open me-2 text-primary"></i> Mis Proyectos</a></li>
                                <li><a class="dropdown-item" href="site_projects.php"><i class="fas fa-globe me-2 text-secondary"></i> Portafolio de la Planta</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                </ul>
                
                <div class="d-flex align-items-center ms-auto">
                    <?php if (isset($_SESSION['UserID'])): ?>
                        <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle text-white d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown">
                                <div class="nav-profile-text text-end d-none d-md-block">
                                    <span class="nav-profile-name"><?= htmlspecialchars($_SESSION['FullName']) ?></span>
                                    <span class="nav-profile-job text-info"><?= htmlspecialchars($_SESSION['JobTitle'] ?? $_SESSION['RoleName']) ?></span>
                                </div>
                                <i class="fas fa-user-circle fs-2 text-secondary bg-white rounded-circle"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow mt-2 border-0">
                                <li><h6 class="dropdown-header">Configuración de Cuenta</h6></li>
                                <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2 text-secondary"></i> Cambiar contraseña</a></li>
                                
                                <?php if ($_SESSION['RoleName'] === 'Administrativo'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Ajustes del Sistema</h6></li>
                                    <li><a class="dropdown-item" href="create_user.php"><i class="fas fa-user-plus me-2 text-primary"></i> Alta de Empleados</a></li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container body-content mt-4 mb-5">