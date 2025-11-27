<?php
/**
 * ========================================
 * INK & SOUND - API UPDATE PROFILE
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Solo permitir POST/PUT
ApiResponse::validateMethod(['POST', 'PUT']);

// Verificar autenticación
$user_id = ApiResponse::requireAuth();

// Obtener datos JSON
$data = ApiResponse::getJsonInput();

try {
    $db = Database::getInstance()->getConnection();
    
    // Preparar arrays para actualizar
    $user_updates = [];
    $profile_updates = [];
    $user_params = [];
    $profile_params = [];
    
    // Campos de users que se pueden actualizar
    $allowed_user_fields = ['full_name', 'bio', 'location', 'website', 'interests', 'theme_preference'];
    
    foreach ($allowed_user_fields as $field) {
        if (isset($data[$field])) {
            $user_updates[] = "{$field} = ?";
            $user_params[] = sanitize($data[$field]);
        }
    }
    
    // Campos de user_profiles que se pueden actualizar
    $allowed_profile_fields = [
        'favorite_book', 'favorite_music', 'favorite_artist', 
        'reading_goal', 'books_read',
        'social_facebook', 'social_twitter', 'social_instagram', 'social_youtube'
    ];
    
    foreach ($allowed_profile_fields as $field) {
        if (isset($data[$field])) {
            $profile_updates[] = "{$field} = ?";
            $profile_params[] = sanitize($data[$field]);
        }
    }
    
    // Actualizar tabla users
    if (!empty($user_updates)) {
        $sql = "UPDATE users SET " . implode(', ', $user_updates) . ", updated_at = NOW() WHERE id = ?";
        $user_params[] = $user_id;
        $stmt = $db->prepare($sql);
        $stmt->execute($user_params);
    }
    
    // Actualizar tabla user_profiles
    if (!empty($profile_updates)) {
        $sql = "UPDATE user_profiles SET " . implode(', ', $profile_updates) . ", updated_at = NOW() WHERE user_id = ?";
        $profile_params[] = $user_id;
        $stmt = $db->prepare($sql);
        $stmt->execute($profile_params);
    }
    
    // Registrar actividad
    logActivity($user_id, 'update', 'actualizó su perfil');
    
    // Obtener datos actualizados
    $stmt = $db->prepare("
        SELECT u.*, up.*
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'bio' => $user['bio'],
            'avatar' => getAvatarUrl($user['avatar']),
            'cover_image' => getCoverUrl($user['cover_image']),
            'location' => $user['location'],
            'website' => $user['website'],
            'interests' => $user['interests'],
            'theme_preference' => $user['theme_preference'],
            'updated_at' => $user['updated_at']
        ],
        'profile' => [
            'favorite_book' => $user['favorite_book'],
            'favorite_music' => $user['favorite_music'],
            'favorite_artist' => $user['favorite_artist'],
            'reading_goal' => (int)$user['reading_goal'],
            'books_read' => (int)$user['books_read'],
            'level' => (int)$user['level'],
            'experience_points' => (int)$user['experience_points'],
            'social_facebook' => $user['social_facebook'],
            'social_twitter' => $user['social_twitter'],
            'social_instagram' => $user['social_instagram'],
            'social_youtube' => $user['social_youtube'],
            'updated_at' => $user['updated_at']
        ]
    ], 'Perfil actualizado exitosamente');
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al actualizar el perfil', 500);
    }
}
?>