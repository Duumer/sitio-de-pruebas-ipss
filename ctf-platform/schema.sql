-- CTF Educational Platform Database Schema for IPSS
-- Compatible with MySQL/MariaDB on shared hosting

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database creation (run separately if needed)
-- CREATE DATABASE IF NOT EXISTS `ctf_ipss` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `ctf_ipss`;

-- --------------------------------------------------------
-- Table: usuarios
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('estudiante', 'docente', 'admin') NOT NULL DEFAULT 'estudiante',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_rol` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categorias
-- --------------------------------------------------------
CREATE TABLE `categorias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` TEXT,
  `icono` VARCHAR(50) DEFAULT 'fas fa-flag',
  `color` VARCHAR(7) DEFAULT '#0066cc',
  `orden` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: desafios
-- --------------------------------------------------------
CREATE TABLE `desafios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categoria_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `descripcion` TEXT NOT NULL,
  `enunciado` LONGTEXT NOT NULL,
  `dificultad` ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
  `puntos` INT UNSIGNED NOT NULL DEFAULT 100,
  `flag_hash` VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of the flag',
  `archivo_adjunto` VARCHAR(255) DEFAULT NULL COMMENT 'Path to downloadable file',
  `archivo_nombre` VARCHAR(255) DEFAULT NULL COMMENT 'Original filename for download',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_categoria` (`categoria_id`),
  INDEX `idx_activo` (`activo`),
  INDEX `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: pistas
-- --------------------------------------------------------
CREATE TABLE `pistas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `desafio_id` INT UNSIGNED NOT NULL,
  `texto_pista` TEXT NOT NULL,
  `penalizacion_puntos` INT UNSIGNED NOT NULL DEFAULT 0,
  `orden` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`desafio_id`) REFERENCES `desafios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_desafio` (`desafio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: soluciones_usuarios
-- --------------------------------------------------------
CREATE TABLE `soluciones_usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `desafio_id` INT UNSIGNED NOT NULL,
  `flag_enviada_hash` VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of submitted flag',
  `es_correcta` TINYINT(1) NOT NULL DEFAULT 0,
  `evidencia_texto` LONGTEXT DEFAULT NULL,
  `pista_usada_id` INT UNSIGNED DEFAULT NULL,
  `puntos_obtenidos` INT NOT NULL DEFAULT 0,
  `fecha_intento` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`desafio_id`) REFERENCES `desafios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`pista_usada_id`) REFERENCES `pistas`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY `unique_usuario_desafio_correct` (`usuario_id`, `desafio_id`, `es_correcta`),
  INDEX `idx_usuario` (`usuario_id`),
  INDEX `idx_desafio` (`desafio_id`),
  INDEX `idx_correcta` (`es_correcta`),
  INDEX `idx_fecha_resolucion` (`fecha_resolucion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: configuracion
-- --------------------------------------------------------
CREATE TABLE `configuracion` (
  `clave` VARCHAR(50) NOT NULL,
  `valor` TEXT NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- View: ranking_estudiantes
-- --------------------------------------------------------
CREATE OR REPLACE VIEW `ranking_estudiantes` AS
SELECT 
  u.`id` AS `usuario_id`,
  u.`nombre`,
  u.`email`,
  COALESCE(SUM(CASE WHEN su.`es_correcta` = 1 THEN su.`puntos_obtenidos` ELSE 0 END), 0) AS `puntaje_total`,
  COUNT(CASE WHEN su.`es_correcta` = 1 THEN 1 END) AS `desafios_completados`,
  MAX(CASE WHEN su.`es_correcta` = 1 THEN su.`fecha_resolucion` END) AS `ultima_resolucion`
FROM `usuarios` u
LEFT JOIN `soluciones_usuarios` su ON u.`id` = su.`usuario_id`
WHERE u.`rol` = 'estudiante' AND u.`activo` = 1
GROUP BY u.`id`, u.`nombre`, u.`email`
ORDER BY `puntaje_total` DESC, `ultima_resolucion` ASC;

-- --------------------------------------------------------
-- Insert default configuration
-- --------------------------------------------------------
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('site_name', 'CTF IPSS - Hacking Game', 'Nombre de la plataforma'),
('site_description', 'Plataforma educativa Capture The Flag para Instituto Profesional San Sebastián', 'Descripción de la plataforma'),
('max_flag_attempts', '10', 'Máximo intentos de flag por desafío por hora'),
('hint_penalty_enabled', '1', 'Habilitar penalización por uso de pistas'),
('ctf_start_date', '2026-01-01 00:00:00', 'Fecha de inicio del CTF'),
('ctf_end_date', '2026-12-31 23:59:59', 'Fecha de fin del CTF'),
('registration_open', '1', 'Permitir registro de nuevos usuarios');

-- --------------------------------------------------------
-- Insert categories
-- --------------------------------------------------------
INSERT INTO `categorias` (`id`, `nombre`, `slug`, `descripcion`, `icono`, `color`, `orden`) VALUES
(1, 'Análisis Web', 'analisis-web', 'Simulaciones seguras de vulnerabilidades web comunes (IDOR, SQLi) sin exponer el servidor real.', 'fas fa-globe', '#e74c3c', 1),
(2, 'Análisis de Vulnerabilidades', 'analisis-vulnerabilidades', 'Revisión de código fuente, configuraciones y reportes de escaneo para hallar fallos de seguridad.', 'fas fa-code', '#f39c12', 2),
(3, 'Criptografía Aplicada', 'criptografia', 'Desafíos de decodificación, análisis de hash débiles y criptografía clásica.', 'fas fa-lock', '#9b59b6', 3),
(4, 'Análisis Forense Digital', 'analisis-forense', 'Extracción de metadatos, esteganografía básica y análisis de logs de servidor.', 'fas fa-search', '#27ae60', 4);

-- --------------------------------------------------------
-- Insert challenges (flags are pre-hashed with SHA256)
-- --------------------------------------------------------

-- Challenge 1: IDOR Simulado
INSERT INTO `desafios` (`id`, `categoria_id`, `titulo`, `slug`, `descripcion`, `enunciado`, `dificultad`, `puntos`, `flag_hash`, `archivo_adjunto`, `archivo_nombre`, `activo`, `orden`) VALUES
(1, 1, 'Evasión de Control de Acceso (IDOR Simulado)', 'idor-simulado',
'Interfaz simulada de consulta de perfil donde el parámetro identificador puede manipularse de forma controlada sin exponer datos reales del servidor.',
'<h3>Desafío 1: Evasión de Control de Acceso (IDOR Simulado)</h3>
<p><strong>Categoría:</strong> Análisis Web | <strong>Dificultad:</strong> Baja | <strong>Puntaje:</strong> 100 pts</p>
<hr>
<h4>Escenario</h4>
<p>Has descubierto una aplicación web interna del IPSS que permite consultar perfiles de estudiantes mediante un parámetro <code>user_id</code> en la URL. La aplicación no verifica correctamente si el usuario autenticado tiene permiso para ver el perfil solicitado.</p>
<h4>Objetivo</h4>
<p>Accede al simulador a continuación. Manipula el parámetro <code>user_id</code> para acceder al perfil del usuario administrativo (ID 1) y obtener la bandera (flag).</p>
<h4>Formato de la Flag</h4>
<p><code>IPSS{idor_access_bypass_demo}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Validación de sesión en backend</li>
<li>Control de acceso basado en atributos (ABAC/RBAC)</li>
<li>Verificación de propiedad del recurso antes de mostrarlo</li>
</ul>',
'baja', 100, SHA2('IPSS{idor_access_bypass_demo}', 256), NULL, NULL, 1, 1),

-- Challenge 2: SQL Injection Simulada
(2, 1, 'Inyección SQL Simulada (Bypass de Autenticación)', 'sqli-simulada',
'Formulario de login falso aislado donde se evalúa la lógica booleana de inyección clásica sobre un conjunto de datos ficticio en memoria.',
'<h3>Desafío 2: Inyección SQL Simulada (Bypass de Autenticación)</h3>
<p><strong>Categoría:</strong> Análisis Web | <strong>Dificultad:</strong> Media | <strong>Puntaje:</strong> 150 pts</p>
<hr>
<h4>Escenario</h4>
<p>Se ha detectado un panel de administración legado vulnerable a inyección SQL en el campo de usuario. El query original es:</p>
<pre><code>SELECT * FROM usuarios WHERE usuario = \'$input\' AND password = \'$pass\';</code></pre>
<h4>Objetivo</h4>
<p>Utiliza el simulador de login a continuación. Inyecta una payload clásica (\'<code> OR \'1\'=\'1</code>) en el campo de usuario para bypass el login y obtener la bandera.</p>
<h4>Formato de la Flag</h4>
<p><code>IPSS{sqli_simulated_bypass}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Sentencias preparadas (Prepared Statements)</li>
<li>Parametrización de consultas</li>
<li>Validación y sanitización de entrada</li>
<li>Principio de menor privilegio en BD</li>
</ul>',
'media', 150, SHA2('IPSS{sqli_simulated_bypass}', 256), NULL, NULL, 1, 2),

-- Challenge 3: Auditoría de Código Fuente
(3, 2, 'Auditoría de Código Fuente Inseguro (Code Review)', 'auditoria-codigo',
'Inspección de un fragmento de código descargable (PHP de muestra) para identificar deserialización insegura y credenciales hardcodeadas.',
'<h3>Desafío 3: Auditoría de Código Fuente Inseguro (Code Review)</h3>
<p><strong>Categoría:</strong> Análisis de Vulnerabilidades | <strong>Dificultad:</strong> Baja | <strong>Puntaje:</strong> 100 pts</p>
<hr>
<h4>Escenario</h4>
<p>Se ha filtrado un fragmento de código fuente de una aplicación interna del IPSS. Tu tarea es revisar el código y encontrar las vulnerabilidades.</p>
<h4>Objetivo</h4>
<p><strong>Descarga el archivo de código fuente</strong> adjunto y analízalo para encontrar:</p>
<ol>
<li>Credenciales hardcodeadas (hardcoded secrets)</li>
<li>Deserialización insegura (<code>unserialize()</code> sin validación)</li>
</ol>
<p>La bandera está oculta en las credenciales hardcodeadas.</p>
<h4>Formato de la Flag</h4>
<p><code>IPSS{hardcoded_credentials_found}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Uso de variables de entorno para secretos (<code>$_ENV</code>, <code>getenv()</code>)</li>
<li>Sanitización y validación antes de deserializar</li>
<li>Uso de <code>json_decode()</code> en lugar de <code>unserialize()</code></li>
<li>Escaneo de secretos en pipeline CI/CD</li>
</ul>',
'baja', 100, SHA2('IPSS{hardcoded_credentials_found}', 256), 'challenges/files/vulnerable_code.php', 'vulnerable_code.php', 1, 1),

-- Challenge 4: Análisis de Configuración Errónea
(4, 2, 'Análisis de Reporte de Escaneo y Configuración Errónea', 'analisis-config',
'Archivo de configuración nginx.conf ficticio donde el estudiante debe identificar cabeceras de seguridad faltantes que revelan una llave en un header de prueba.',
'<h3>Desafío 4: Análisis de Configuración Errónea (Misconfiguration)</h3>
<p><strong>Categoría:</strong> Análisis de Vulnerabilidades | <strong>Dificultad:</strong> Media | <strong>Puntaje:</strong> 150 pts</p>
<hr>
<h4>Escenario</h4>
<p>Se ha realizado un escaneo de seguridad al servidor web del IPSS. El reporte muestra una configuración de <code>nginx.conf</code> con cabeceras de seguridad faltantes y un header de prueba que filtra información sensible.</p>
<h4>Objetivo</h4>
<p><strong>Descarga el archivo de configuración</strong> adjunto (<code>nginx.conf</code>) y analízalo para identificar:</p>
<ol>
<li>Cabeceras de seguridad faltantes: CSP, HSTS, X-Frame-Options, etc.</li>
<li>Configuración CORS con wildcard (<code>*</code>)</li>
<li>Un header de prueba <code>X-Debug-Token</code> que contiene la bandera</li>
</ol>
<h4>Formato de la Flag</h4>
<p><code>IPSS{misconfig_cors_wildcard}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Endurecimiento de cabeceras HTTP (Security Headers)</li>
<li>Políticas CORS restrictivas (orígenes específicos)</li>
<li>Implementación de Content Security Policy (CSP)</li>
<li>HSTS y eliminación de headers de depuración en producción</li>
</ul>',
'media', 150, SHA2('IPSS{misconfig_cors_wildcard}', 256), 'challenges/files/nginx.conf', 'nginx.conf', 1, 2),

-- Challenge 5: Debilidad en Cifrado Clásico
(5, 3, 'Debilidad en Cifrado Clásico y Codificación por Capas', 'cripto-capas',
'Cadena ofuscada con múltiples capas (Base64 -> Hexadecimal -> Cifrado César/Vigenère con pista contextual).',
'<h3>Desafío 5: Debilidad en Cifrado Clásico y Codificación por Capas</h3>
<p><strong>Categoría:</strong> Criptografía Aplicada | <strong>Dificultad:</strong> Baja | <strong>Puntaje:</strong> 100 pts</p>
<hr>
<h4>Escenario</h4>
<p>Se ha interceptado un mensaje cifrado con múltiples capas de codificación y cifrado clásico. El mensaje es:</p>
<pre><code>U2FsdGVkX1+5Q2VzYXJDbGF2ZQ==</code></pre>
<h4>Pista</h4>
<p><em>\"La historia del IPSS guarda el secreto: fundada en 1989, la clave está en sus siglas.\"</em></p>
<h4>Objetivo</h4>
<p>Decodifica la cadena capa por capa:</p>
<ol>
<li>Base64</li>
<li>Hexadecimal</li>
<li>Cifrado César/Vigenère (clave: IPSS)</li>
</ol>
<h4>Formato de la Flag</h4>
<p><code>IPSS{crypto_multi_layer_decode}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Diferenciación entre codificación (encoding) y cifrado (encryption)</li>
<li>Adopción de algoritmos modernos autenticados (AES-GCM, ChaCha20-Poly1305)</li>
<li>No confiar en seguridad por oscuridad</li>
<li>Gestión segura de claves (KMS, HSM)</li>
</ul>',
'baja', 100, SHA2('IPSS{crypto_multi_layer_decode}', 256), NULL, NULL, 1, 1),

-- Challenge 6: Ruptura de Hash Débil
(6, 3, 'Ruptura de Hash Débil y Reutilización de Salt', 'hash-debil',
'Fichero descargable con lista de hashes débiles (MD5 sin salt) recuperables mediante análisis de diccionario.',
'<h3>Desafío 6: Ruptura de Hash Débil y Reutilización de Salt</h3>
<p><strong>Categoría:</strong> Criptografía Aplicada | <strong>Dificultad:</strong> Media | <strong>Puntaje:</strong> 150 pts</p>
<hr>
<h4>Escenario</h4>
<p>Se ha filtrado una base de datos de hashes de contraseñas del sistema legado del IPSS. Los hashes utilizan MD5 sin salt (unsalted).</p>
<h4>Objetivo</h4>
<p><strong>Descarga el archivo de hashes</strong> adjunto (<code>hashes.txt</code>) y recupera la contraseña del usuario <code>admin</code> usando ataques de diccionario o tablas rainbow. La bandera es la contraseña recuperada envuelta en el formato estándar.</p>
<h4>Formato de la Flag</h4>
<p><code>IPSS{broken_md5_hash_salted}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Implementación de funciones de derivación de claves adaptativas: <strong>Argon2id</strong> (recomendado) o <strong>bcrypt</strong></li>
<li>Uso de salt único por contraseña (mínimo 16 bytes)</li>
<li>Factor de trabajo configurable (cost factor)</li>
<li>Migración proactiva de hashes legacy</li>
</ul>',
'media', 150, SHA2('IPSS{broken_md5_hash_salted}', 256), 'challenges/files/hashes.txt', 'hashes.txt', 1, 2),

-- Challenge 7: Análisis Forense de Metadatos
(7, 4, 'Análisis Forense de Archivos y Metadatos (EXIF)', 'forense-metadatos',
'Imagen educativa descargable con metadatos EXIF ocultos que contienen la bandera.',
'<h3>Desafío 7: Análisis Forense de Archivos y Metadatos (EXIF/Esteganografía)</h3>
<p><strong>Categoría:</strong> Análisis Forense Digital | <strong>Dificultad:</strong> Baja | <strong>Puntaje:</strong> 100 pts</p>
<hr>
<h4>Escenario</h4>
<p>Se ha encontrado una imagen oficial del IPSS publicada en la web institucional. Los metadatos EXIF podrían contener información sensible.</p>
<h4>Objetivo</h4>
<p><strong>Descarga la imagen</strong> adjunta (<code>campus_ipss.jpg</code>) y analízala usando:</p>
<ul>
<li>Herramientas de metadatos EXIF (<code>exiftool</code>, <code>identify -verbose</code>)</li>
<li>Inspección hexadecimal (<code>xxd</code>, <code>hexdump</code>)</li>
<li>Comando <code>strings</code> para buscar cadenas al final del archivo</li>
</ul>
<p>La bandera está oculta en los metadatos EXIF (campo <code>UserComment</code> o <code>ImageDescription</code>).</p>
<h4>Formato de la Flag</h4>
<p><code>IPSS{metadata_exif_extracted}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Sanitización de metadatos antes de publicación pública (<code>exiftool -all=</code>)</li>
<li>Políticas de revisión de activos digitales</li>
<li>Eliminación de GPS, timestamps, device info, software tags</li>
</ul>',
'baja', 100, SHA2('IPSS{metadata_exif_extracted}', 256), 'challenges/files/campus_ipss.jpg', 'campus_ipss.jpg', 1, 1),

-- Challenge 8: Análisis de Logs
(8, 4, 'Análisis de Logs de Servidor Web', 'forense-logs',
'Archivo de log ficticio (access.log) con trazas de ataque de fuerza bruta. El participante debe analizar IPs, rutas y parámetros para extraer la bandera.',
'<h3>Desafío 8: Análisis de Logs de Servidor Web</h3>
<p><strong>Categoría:</strong> Análisis Forense Digital | <strong>Dificultad:</strong> Media | <strong>Puntaje:</strong> 150 pts</p>
<hr>
<h4>Escenario</h4>
<p>El servidor web del IPSS ha sido objetivo de un ataque de fuerza bruta y escaneo automatizado. Se ha capturado el <code>access.log</code> del incidente.</p>
<h4>Objetivo</h4>
<p><strong>Descarga el archivo de logs</strong> adjunto (<code>access.log</code>) y analízalo para encontrar:</p>
<ol>
<li>IP del atacante</li>
<li>Rutas escaneadas (directorio admin, phpmyadmin, etc.)</li>
<li>Parámetros de inyección intentados</li>
<li>La petición <strong>exitosa</strong> que obtuvo acceso (código 200) y contiene la bandera en el User-Agent o parámetro</li>
</ol>
<h4>Formato de la Flag</h4>
<p><code>IPSS{forensic_log_trace_identified}</code></p>
<h4>Mitigación Esperada</h4>
<ul>
<li>Centralización de logs (SIEM: ELK, Splunk, Wazuh)</li>
<li>Correlación de eventos y alertas automáticas</li>
<li>Reglas de bloqueo WAF / Fail2Ban para fuerza bruta</li>
<li>Rate limiting y CAPTCHA en formularios sensibles</li>
</ul>',
'media', 150, SHA2('IPSS{forensic_log_trace_identified}', 256), 'challenges/files/access.log', 'access.log', 1, 2);

-- --------------------------------------------------------
-- Insert hints for challenges
-- --------------------------------------------------------
INSERT INTO `pistas` (`desafio_id`, `texto_pista`, `penalizacion_puntos`, `orden`) VALUES
(1, 'El parámetro user_id en la URL controla qué perfil se muestra. Prueba cambiarlo a 1.', 10, 1),
(1, 'El perfil con ID 1 corresponde al usuario administrador del sistema.', 15, 2),
(2, 'El campo de usuario es vulnerable. Inyecta: \' OR \'1\'=\'1', 15, 1),
(2, 'El comentario SQL (--) puede ignorar la verificación de contraseña.', 20, 2),
(3, 'Busca en el código: define(\"DB_PASSWORD\", ...)', 10, 1),
(3, 'La función unserialize() se llama con datos controlados por el usuario.', 15, 2),
(4, 'Revisa la directiva add_header en la configuración del servidor.', 10, 1),
(4, 'El header X-Debug-Token está presente en la configuración de prueba.', 15, 2),
(5, 'Decodifica Base64 primero, luego Hex, luego aplica Vigenère con clave IPSS.', 10, 1),
(5, 'IPSS fue fundada en 1989. La clave Vigenère es IPSS.', 15, 2),
(6, 'Los hashes son MD5 sin salt. Usa rockyou.txt o tablas rainbow online.', 15, 1),
(6, 'El hash del admin es: 5f4dcc3b5aa765d61d8327deb882cf99 (password: password)', 25, 2),
(7, 'Usa exiftool en la imagen. Revisa el campo UserComment.', 10, 1),
(7, 'También revisa el final del archivo con strings o xxd.', 15, 2),
(8, 'Busca líneas con código de respuesta 200 (éxito) en lugar de 404/403.', 10, 1),
(8, 'El User-Agent de la petición exitosa contiene la bandera.', 20, 2);

-- --------------------------------------------------------
-- Insert default admin user (password: admin123 - change in production!)
-- Hash generated with password_hash('admin123', PASSWORD_DEFAULT)
-- --------------------------------------------------------
INSERT INTO `usuarios` (`nombre`, `email`, `password_hash`, `rol`) VALUES
('Administrador IPSS', 'admin@ipss.cl', '$2y$10$v8mYG03ObCaXLaEs/BLOn.YpD9jaZDTBpr4N4AUvnI6A/wdxd7T82', 'admin'),
('Docente Demo', 'docente@ipss.cl', '$2y$10$v8mYG03ObCaXLaEs/BLOn.YpD9jaZDTBpr4N4AUvnI6A/wdxd7T82', 'docente'),
('Estudiante Demo', 'estudiante@ipss.cl', '$2y$10$v8mYG03ObCaXLaEs/BLOn.YpD9jaZDTBpr4N4AUvnI6A/wdxd7T82', 'estudiante');

COMMIT;