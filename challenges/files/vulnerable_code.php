<?php
/**
 * Archivo vulnerable para Desafío 3: Auditoría de Código Fuente Inseguro
 * CONTIENE VULNERABILIDADES INTENCIONALES PARA FINES EDUCATIVOS
 * NO USAR EN PRODUCCIÓN
 */

// VULNERABILIDAD 1: Credenciales Hardcodeadas
define('DB_HOST', 'localhost');
define('DB_NAME', 'ipss_intranet');
define('DB_USER', 'intranet_user');
define('DB_PASSWORD', 'IPSS{hardcoded_credentials_found}');  // FLAG AQUI - Secret en codigo
define('API_KEY_EXTERNA', 'sk_live_51H7x...');  // API Key real expuesta
define('JWT_SECRET', 'super_secret_jwt_key_123');  // Clave JWT hardcodeada

// VULNERABILIDAD 2: Deserializacion Insegura
function procesarPerfilUsuario($datosSerializados) {
    // PELIGROSO: unserialize() con datos no confiables
    // Permite inyeccion de objetos y RCE via gadget chains
    $perfil = @unserialize($datosSerializados);
    
    if ($perfil instanceof PerfilUsuario) {
        return $perfil;
    }
    return null;
}

// VULNERABILIDAD 3: Consulta SQL sin parametrizar
function obtenerUsuarioPorId($conexion, $userId) {
    // PELIGROSO: Concatenacion directa - SQL Injection
    $query = "SELECT * FROM usuarios WHERE id = $userId";
    return $conexion->query($query);
}

// VULNERABILIDAD 4: Configuracion insegura de sesion
ini_set('session.cookie_httponly', 0);  // Permite acceso JS a cookies
ini_set('session.cookie_secure', 0);    // Permite transmision por HTTP
ini_set('session.use_strict_mode', 0);  // No usa modo estricto

// Clase vulnerable para demostracion de gadget chain
class PerfilUsuario {
    public $nombre;
    public $email;
    public $rol;
    
    public function __construct($nombre, $email, $rol = 'estudiante') {
        $this->nombre = $nombre;
        $this->email = $email;
        $this->rol = $rol;
    }
    
    // Gadget chain: __wakeup se ejecuta al deserializar
    public function __wakeup() {
        // En un exploit real, esto podria ejecutar codigo arbitrario
        error_log("Deserializacion de perfil: " . $this->nombre);
    }
    
    // Gadget chain: __destruct se ejecuta al destruir objeto
    public function __destruct() {
        // Podria usarse para borrar archivos, etc.
    }
}

class LogWriter {
    public $archivo;
    public $contenido;
    
    public function __destruct() {
        // GADGET PELIGROSO: Escritura arbitraria de archivos
        if ($this->archivo && $this->contenido) {
            file_put_contents($this->archivo, $this->contenido);
        }
    }
}

// VULNERABILIDAD 5: Informacion de debug expuesta
if (isset($_GET['debug'])) {
    phpinfo();  // Expone toda la configuracion del servidor
}

// VULNERABILIDAD 6: Path Traversal en include
if (isset($_GET['page'])) {
    include($_GET['page'] . '.php');  // LFI/RFI posible
}

// MITIGACIONES RECOMENDADAS (Comentadas para el reto)
/*
// 1. Variables de entorno para secretos
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('JWT_SECRET', getenv('JWT_SECRET'));

// 2. Validacion antes de deserializar
function procesarPerfilUsuarioSeguro($datosJson) {
    $datos = json_decode($datosJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos invalidos');
    }
    // Validar esquema esperado
    return new PerfilUsuario($datos['nombre'], $datos['email'], $datos['rol']);
}

// 3. Prepared Statements
function obtenerUsuarioPorIdSeguro($conexion, $userId) {
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

// 4. Configuracion segura de sesion
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// 5. Validacion de entrada para includes
$paginasPermitidas = ['home', 'perfil', 'configuracion'];
if (isset($_GET['page']) && in_array($_GET['page'], $paginasPermitidas)) {
    include($_GET['page'] . '.php');
}
*/

// EJEMPLO DE EXPLOIT DE DESERIALIZACION (Solo educativo)
/*
// Payload malicioso para cadena de gadgets:
// O:9:"LogWriter":2:{s:7:"archivo";s:10:"shell.php";s:8:"contenido";s:30:"<?php system($_GET['cmd']); ?>";}

// Serializado: O:9:"LogWriter":2:{s:7:"archivo";s:10:"shell.php";s:8:"contenido";s:30:"<?php system($_GET['cmd']); ?>";}
// Al deserializar y destruirse el objeto, escribe shell.php
*/

echo "<!-- Archivo de auditoria para Desafio 3 CTF IPSS -->\n";
echo "<!-- Vulnerabilidades identificadas:\n";
echo "1. Credenciales hardcodeadas (linea 14)\n";
echo "2. Deserializacion insegura (linea 23)\n";
echo "3. SQL Injection (linea 34)\n";
echo "4. Configuracion de sesion insegura (lineas 41-43)\n";
echo "5. Exposicion de phpinfo (linea 68)\n";
echo "6. Path Traversal / LFI (linea 73)\n";
echo "7. Gadget chains en __wakeup/__destruct (lineas 55-65)\n";
echo "-->\n";