<?php
/**
 * ========================================
 * INK & SOUND - API LOGIN
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Solo permitir POST
ApiResponse::validateMethod(['POST']);

// Obtener datos JSON
$data = ApiResponse::getJsonInput();

// Validar campos requeridos
$required_fields = ['username_email', 'password'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $errors[$field] = "El campo {$field} es obligatorio";
    }
}

if (!empty($errors)) {
    ApiResponse::error('Faltan campos obligatorios', 400, $errors);
}

$username_email = sanitize($data['username_email']);
$password = $data['password'];
$remember = $data['remember'] ?? false;

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar usuario por username o email
    $stmt = $db->prepare("
        SELECT u.*, up.level, up.experience_points 
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.username = ? OR u.email = ?
    ");
    $stmt->execute([$username_email, $username_email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        ApiResponse::error('Credenciales incorrectas', 401, [
            'auth' => 'Usuario o contraseña incorrectos'
        ]);
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        ApiResponse::error('Credenciales incorrectas', 401, [
            'auth' => 'Usuario o contraseña incorrectos'
        ]);
    }
    
    // Verificar si la cuenta está activa
    if (!$user['is_active']) {
        ApiResponse::error('Cuenta desactivada', 403, [
            'account' => 'Tu cuenta ha sido desactivada. Contacta al soporte.'
        ]);
    }
    
    // Actualizar último login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Configurar sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // Si seleccionó "recordarme", extender la sesión
    if ($remember) {
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_set_cookie_params(SESSION_LIFETIME);
    }
    
    // Obtener estadísticas del usuario
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT r.id) as total_reviews,
            COUNT(DISTINCT c.id) as total_collections,
            COUNT(DISTINCT f.id) as friends_count
        FROM users u
        LEFT JOIN reviews r ON u.id = r.user_id
        LEFT JOIN collections c ON u.id = c.user_id
        LEFT JOIN friendships f ON u.id = f.user_id AND f.status = 'accepted'
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch() ?: ['total_reviews' => 0, 'total_collections' => 0, 'friends_count' => 0];
    
    // Registrar actividad
    logActivity($user['id'], 'login', 'inició sesión');
    
    // Responder con éxito
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'avatar' => getAvatarUrl($user['avatar']),
            'cover_image' => getCoverUrl($user['cover_image']),
            'bio' => $user['bio'],
            'location' => $user['location'],
            'website' => $user['website'],
            'interests' => $user['interests'],
            'theme_preference' => $user['theme_preference'],
            'level' => $user['level'] ?? 1,
            'experience_points' => $user['experience_points'] ?? 0,
            'is_verified' => (bool)$user['is_verified'],
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login']
        ],
        'stats' => [
            'reviews' => (int)$stats['total_reviews'],
            'collections' => (int)$stats['total_collections'],
            'friends' => (int)$stats['friends_count']
        ],
        'session' => [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'expires_at' => date('Y-m-d H:i:s', time() + ($remember ? SESSION_LIFETIME : 3600))
        ]
    ], 'Inicio de sesión exitoso');
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al iniciar sesión', 500);
    }
}
?>