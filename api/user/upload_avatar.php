<?php
/**
 * ========================================
 * INK & SOUND - API UPLOAD AVATAR
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Solo permitir POST
ApiResponse::validateMethod(['POST']);

// Verificar autenticación
$user_id = ApiResponse::requireAuth();

// Verificar si se subió un archivo
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    ApiResponse::error('No se subió ningún archivo o hubo un error', 400);
}

$file = $_FILES['avatar'];

// Validar tipo de archivo
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    ApiResponse::error('Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WEBP)', 400);
}

// Validar tamaño
if ($file['size'] > MAX_FILE_SIZE) {
    ApiResponse::error('El archivo es demasiado grande. Máximo ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener avatar actual
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_avatar = $stmt->fetchColumn();
    
    // Subir nuevo avatar
    $result = uploadFile($file, 'avatar');
    
    if (!$result['success']) {
        ApiResponse::error($result['message'], 500);
    }
    
    // Actualizar en base de datos
    $stmt = $db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$result['filename'], $user_id]);
    
    // Eliminar avatar anterior si no es el default
    if ($current_avatar && $current_avatar !== 'default-avatar.png') {
        deleteFile($current_avatar, 'avatar');
    }
    
    // Registrar actividad
    logActivity($user_id, 'update', 'actualizó su avatar');
    
    ApiResponse::success([
        'avatar' => getAvatarUrl($result['filename']),
        'filename' => $result['filename']
    ], 'Avatar actualizado exitosamente');
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al subir el avatar', 500);
    }
}
?>