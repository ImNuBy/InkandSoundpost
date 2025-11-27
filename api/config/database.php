<?php

/**
 * ========================================
 * INK & SOUND - DATABASE CONFIGURATION
 * ========================================
 */

// Iniciar sesión SOLO si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ... resto del código existente

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'ink_and_sound');
define('DB_USER', 'root');
define('DB_PASS', ''); // Dejar vacío para XAMPP por defecto
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Ink & Sound');
define('APP_URL', 'http://localhost/ink-and-sound');
define('APP_VERSION', '2.0.0');

// Configuración de sesiones
define('SESSION_LIFETIME', 3600 * 24 * 30); // 30 días
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

// Configuración de uploads
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('AVATAR_PATH', UPLOAD_PATH . 'avatars/');
define('COVER_PATH', UPLOAD_PATH . 'covers/');
define('CONTENT_PATH', UPLOAD_PATH . 'content/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Crear directorios si no existen
if (!file_exists(AVATAR_PATH)) {
    mkdir(AVATAR_PATH, 0755, true);
}
if (!file_exists(COVER_PATH)) {
    mkdir(COVER_PATH, 0755, true);
}
if (!file_exists(CONTENT_PATH)) {
    mkdir(CONTENT_PATH, 0755, true);
}

// Configuración de zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Configuración de errores (cambiar a false en producción)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Clase de conexión a la base de datos
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Error de conexión: " . $e->getMessage());
            } else {
                die("Error de conexión a la base de datos");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación del objeto
    private function __clone() {}
    
    // Prevenir deserialización del objeto
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Funciones auxiliares globales
 */

// Función para escapar HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Función para sanitizar entrada
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit;
}

// Función para verificar si el usuario está autenticado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para requerir autenticación
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login.php');
    }
}

// Función para obtener el usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para formatear fecha
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Función para calcular tiempo relativo
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Hace ' . $difference . ' segundo' . ($difference != 1 ? 's' : '');
    }
    
    $difference = floor($difference / 60);
    if ($difference < 60) {
        return 'Hace ' . $difference . ' minuto' . ($difference != 1 ? 's' : '');
    }
    
    $difference = floor($difference / 60);
    if ($difference < 24) {
        return 'Hace ' . $difference . ' hora' . ($difference != 1 ? 's' : '');
    }
    
    $difference = floor($difference / 24);
    if ($difference < 7) {
        return 'Hace ' . $difference . ' día' . ($difference != 1 ? 's' : '');
    }
    
    $difference = floor($difference / 7);
    if ($difference < 4) {
        return 'Hace ' . $difference . ' semana' . ($difference != 1 ? 's' : '');
    }
    
    return formatDate($datetime);
}

// Función para subir archivo
function uploadFile($file, $type = 'avatar') {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    $upload_path = $type === 'avatar' ? AVATAR_PATH : ($type === 'cover' ? COVER_PATH : CONTENT_PATH);
    $destination = $upload_path . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Error al subir el archivo'];
}

// Función para eliminar archivo
function deleteFile($filename, $type = 'avatar') {
    $upload_path = $type === 'avatar' ? AVATAR_PATH : ($type === 'cover' ? COVER_PATH : CONTENT_PATH);
    $filepath = $upload_path . $filename;
    
    if (file_exists($filepath) && !in_array($filename, ['default-avatar.png', 'default-cover.jpg'])) {
        return unlink($filepath);
    }
    
    return false;
}

// Función para enviar email (placeholder - implementar con librería real)
function sendEmail($to, $subject, $message) {
    // Implementar con PHPMailer o similar
    return true;
}

// Función para crear notificación
function createNotification($user_id, $actor_id, $type, $content, $related_id = null) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, actor_id, type, content, related_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $actor_id, $type, $content, $related_id]);
}

// Función para registrar actividad
function logActivity($user_id, $action_type, $action_description, $related_type = null, $related_id = null) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO activity_log (user_id, action_type, action_description, related_type, related_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $action_type, $action_description, $related_type, $related_id]);
}

// Función para obtener URL de avatar
function getAvatarUrl($avatar) {
    if (empty($avatar) || $avatar === 'default-avatar.png') {
        return APP_URL . '/assets/images/default-avatar.png';
    }
    return APP_URL . '/uploads/avatars/' . $avatar;
}

// Función para obtener URL de cover
function getCoverUrl($cover) {
    if (empty($cover) || $cover === 'default-cover.jpg') {
        return APP_URL . '/assets/images/default-cover.jpg';
    }
    return APP_URL . '/uploads/covers/' . $cover;
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para generar slug
function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID de sesión periódicamente
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // cada 5 minutos
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>