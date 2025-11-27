<?php
/**
 * ========================================
 * INK & SOUND - API TOGGLE LIKE
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Solo permitir POST
ApiResponse::validateMethod(['POST']);

// Verificar autenticación
$user_id = ApiResponse::requireAuth();

// Obtener datos JSON
$data = ApiResponse::getJsonInput();

// Validar campos requeridos
if (empty($data['likeable_type']) || empty($data['likeable_id'])) {
    ApiResponse::error('Faltan campos obligatorios', 400);
}

$likeable_type = sanitize($data['likeable_type']);
$likeable_id = (int)$data['likeable_id'];

// Validar tipo
$allowed_types = ['review', 'content', 'comment'];
if (!in_array($likeable_type, $allowed_types)) {
    ApiResponse::error('Tipo de like inválido', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si el objeto existe
    $table_map = [
        'review' => 'reviews',
        'content' => 'content',
        'comment' => 'comments'
    ];
    
    $table = $table_map[$likeable_type];
    $stmt = $db->prepare("SELECT id FROM {$table} WHERE id = ?");
    $stmt->execute([$likeable_id]);
    
    if (!$stmt->fetch()) {
        ApiResponse::error('El objeto no existe', 404);
    }
    
    // Verificar si ya existe el like
    $stmt = $db->prepare("
        SELECT id FROM likes 
        WHERE user_id = ? AND likeable_type = ? AND likeable_id = ?
    ");
    $stmt->execute([$user_id, $likeable_type, $likeable_id]);
    $existing_like = $stmt->fetch();
    
    if ($existing_like) {
        // Eliminar like (unlike)
        $stmt = $db->prepare("
            DELETE FROM likes 
            WHERE user_id = ? AND likeable_type = ? AND likeable_id = ?
        ");
        $stmt->execute([$user_id, $likeable_type, $likeable_id]);
        
        $action = 'removed';
        $message = 'Like eliminado';
    } else {
        // Agregar like
        $stmt = $db->prepare("
            INSERT INTO likes (user_id, likeable_type, likeable_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $likeable_type, $likeable_id]);
        
        $action = 'added';
        $message = 'Like agregado';
        
        // Registrar actividad
        logActivity($user_id, 'like', "dio like a un {$likeable_type}", $likeable_type, $likeable_id);
    }
    
    // El trigger actualizará automáticamente los contadores
    
    // Obtener el nuevo conteo de likes
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_likes 
        FROM likes 
        WHERE likeable_type = ? AND likeable_id = ?
    ");
    $stmt->execute([$likeable_type, $likeable_id]);
    $result = $stmt->fetch();
    
    ApiResponse::success([
        'action' => $action,
        'likeable_type' => $likeable_type,
        'likeable_id' => $likeable_id,
        'total_likes' => (int)$result['total_likes'],
        'is_liked' => $action === 'added'
    ], $message);
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al procesar el like', 500);
    }
}
?>