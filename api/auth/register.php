<?php
/**
 * ========================================
 * INK & SOUND - API REGISTER
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Solo permitir POST
ApiResponse::validateMethod(['POST']);

// Obtener datos JSON
$data = ApiResponse::getJsonInput();

// Validar campos requeridos
$required_fields = ['username', 'email', 'password', 'full_name'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $errors[$field] = "El campo {$field} es obligatorio";
    }
}

if (!empty($errors)) {
    ApiResponse::error('Faltan campos obligatorios', 400, $errors);
}

// Sanitizar datos
$username = sanitize($data['username']);
$email = sanitize($data['email']);
$password = $data['password'];
$full_name = sanitize($data['full_name']);
$interests = sanitize($data['interests'] ?? '');

// Validaciones adicionales
if (!isValidEmail($email)) {
    $errors['email'] = 'El email no es válido';
}

if (strlen($password) < 6) {
    $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
}

if (strlen($username) < 3 || strlen($username) > 50) {
    $errors['username'] = 'El nombre de usuario debe tener entre 3 y 50 caracteres';
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
}

if (!empty($errors)) {
    ApiResponse::error('Errores de validación', 400, $errors);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si el usuario o email ya existen
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        if ($existing_user['username'] === $username) {
            $errors['username'] = 'El nombre de usuario ya está registrado';
        }
        if ($existing_user['email'] === $email) {
            $errors['email'] = 'El email ya está registrado';
        }
        ApiResponse::error('Usuario o email ya existen', 409, $errors);
    }
    
    // Crear el usuario
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, full_name, interests, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $interests])) {
        $user_id = $db->lastInsertId();
        
        // Obtener datos del usuario creado
        $stmt = $db->prepare("
            SELECT id, username, email, full_name, avatar, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        // Iniciar sesión automáticamente
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        
        // Registrar actividad
        logActivity($user_id, 'register', 'se unió a la comunidad');
        
        // Responder con éxito
        ApiResponse::success([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'avatar' => getAvatarUrl($user['avatar']),
                'created_at' => $user['created_at']
            ],
            'session' => [
                'user_id' => $user_id,
                'username' => $username
            ]
        ], 'Usuario registrado exitosamente', 201);
    } else {
        ApiResponse::error('Error al crear la cuenta', 500);
    }
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al procesar el registro', 500);
    }
}
?>