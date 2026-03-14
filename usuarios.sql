-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 04-08-2024 a las 11:28:18
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `usuarios`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id_proyecto` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `lider_proyecto` varchar(255) NOT NULL,
  `trabajadores` text NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_termino` date NOT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proyectos`
--

INSERT INTO `proyectos` (`id_proyecto`, `nombre`, `descripcion`, `lider_proyecto`, `trabajadores`, `fecha_inicio`, `fecha_termino`, `prioridad`) VALUES
(2, 'Mantenimiento LAB 1', 'Limpieza de todos los equipos...', '0322103893@ut-tijuana.edu.mx', '0322104031@ut-tijuana.edu.mx', '2024-08-05', '2024-08-07', 'alta'),
(3, 'Mantenimiento LAB 2', 'Actualizar OS en equipo del Docente', '0322103893@ut-tijuana.edu.mx', '0322103889@ut-tijuana.edu.mx', '2024-08-12', '2024-08-13', 'media'),
(4, 'Mantenimiento LAB 3', 'Cambio de perifericos dañados', '0322103893@ut-tijuana.edu.mx', '0322103889@ut-tijuana.edu.mx', '2024-08-13', '2024-08-14', 'baja'),
(6, 'Prueba 1', 'blablablass', '0322103893@ut-tijuana.edu.mx', '0322103889@ut-tijuana.edu.mx', '2024-08-03', '2024-08-14', 'media');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id_tarea` int(11) NOT NULL,
  `nombre_t` varchar(255) NOT NULL,
  `descripcion_t` text NOT NULL,
  `progreso` int(3) NOT NULL DEFAULT 0,
  `fecha_inicio_t` date NOT NULL,
  `fecha_fin_t` date NOT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL,
  `id_proyecto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tareas`
--

INSERT INTO `tareas` (`id_tarea`, `nombre_t`, `descripcion_t`, `progreso`, `fecha_inicio_t`, `fecha_fin_t`, `prioridad`, `id_proyecto`) VALUES
(17, 'Tarea 1', 'actividades', 45, '2024-08-07', '2024-08-09', 'alta', 6),
(18, 'tarea2', 'sapokds', 45, '2024-08-04', '2024-08-08', 'baja', 6),
(19, 'tarea1', 'ñjl', 45, '2024-08-04', '2024-08-14', 'baja', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombres` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tipo_usuario` enum('lider','administrador','trabajador') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombres`, `apellido_paterno`, `apellido_materno`, `email`, `password`, `tipo_usuario`) VALUES
(24, 'Luis Alfredo', 'Torres', 'Hernandez', '0322103889@ut-tijuana.edu.mx', '$2y$10$njqQwWHSNK.z4aKBSJxZ/uCXDlMdoqOrLExIUZ2DMZ0IPB6teFzhm', 'trabajador'),
(26, 'Erick Yael', 'Apodaca', 'Montes', '0322103881@ut-tijuana.edu.mx', '$2y$10$ViPQw1K.6aTktP.LuQvNoONosneLTHv40xVtBXpaWR8nFqDLXOcNW', 'administrador'),
(28, 'Julio Cesar', 'Aguilar', 'Estolano', '0322103893@ut-tijuana.edu.mx', '$2y$10$/V9K5ATHq0qfl832G.3EsOkKg7DxUXXgfW0gid1BDGNK9hhV97bWK', 'lider'),
(30, 'Kevin', 'Cervantes', 'Fernandez', '0322103890@ut-tijuana.edu.mx', '$2y$10$c4Qm1AVx3kkz0.xPNqmrrep1Tjy8wr9J.xcNnT9RDm5hZAwpCVNSS', 'administrador'),
(31, 'Misael', 'Haro', 'Marquez', '0322104031@ut-tijuana.edu.mx', '$2y$10$Ma2hyzdlrbvbHQedLn0DwelC3ifgm0eX2wZ8i/O3qE8F3UxrQ8MAq', 'trabajador'),
(32, 'Cristiano Ronaldo', 'dos Santos', 'Aveiro', 'cr7@ut-tijuana.edu.mx', '$2y$10$3Btg888UIrSEE1/q3nxjB.lAQF3LVAbElsikJTfqBVdFsv1M2PUHS', 'administrador'),
(33, 'Antonio', 'Estrella', 'Hierro', 'tonystark@ut-tijuana.edu.mx', '$2y$10$L2adOVDCqxD.XHr.F.yCpO/GfiYBxySG6adsnzHmSFM90iZbnlddy', 'lider');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id_proyecto`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id_tarea`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id_proyecto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
