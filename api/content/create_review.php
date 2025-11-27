<?php
/**
 * ========================================
 * INK & SOUND - API CREATE REVIEW
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
$required_fields = ['content_id', 'rating', 'review_text'];
$errors = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        $errors[$field] = "El campo {$field} es obligatorio";
    }
}

if (!empty($errors)) {
    ApiResponse::error('Faltan campos obligatorios', 400, $errors);
}

$content_id = (int)$data['content_id'];
$rating = (int)$data['rating'];
$review_text = sanitize($data['review_text']);
$title = sanitize($data['title'] ?? '');
$is_spoiler = (bool)($data['is_spoiler'] ?? false);

// Validaciones
if ($rating < 1 || $rating > 5) {
    $errors['rating'] = 'La calificación debe estar entre 1 y 5';
}

if (strlen($review_text) < 10) {
    $errors['review_text'] = 'La reseña debe tener al menos 10 caracteres';
}

if (!empty($errors)) {
    ApiResponse::error('Errores de validación', 400, $errors);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si el contenido existe
    $stmt = $db->prepare("SELECT id, title, type FROM content WHERE id = ?");
    $stmt->execute([$content_id]);
    $content = $stmt->fetch();
    
    if (!$content) {
        ApiResponse::error('El contenido no existe', 404);
    }
    
    // Verificar si el usuario ya reseñó este contenido
    $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND content_id = ?");
    $stmt->execute([$user_id, $content_id]);
    
    if ($stmt->fetch()) {
        ApiResponse::error('Ya has reseñado este contenido', 409, [
            'review' => 'Solo puedes hacer una reseña por contenido'
        ]);
    }
    
    // Crear la reseña
    $stmt = $db->prepare("
        INSERT INTO reviews (user_id, content_id, rating, title, review_text, is_spoiler, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$user_id, $content_id, $rating, $title, $review_text, $is_spoiler])) {
        $review_id = $db->lastInsertId();
        
        // El trigger actualizará automáticamente el rating promedio del contenido
        // y registrará la actividad
        
        // Obtener la reseña creada con información del contenido
        $stmt = $db->prepare("
            SELECT r.*, c.title as content_title, c.type as content_type, 
                   c.author_artist, c.cover_image, u.username, u.avatar
            FROM reviews r
            JOIN content c ON r.content_id = c.id
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();
        
        // Verificar y otorgar badges
        $db->prepare("CALL check_and_award_badges(?)")->execute([$user_id]);
        
        ApiResponse::success([
            'review' => [
                'id' => $review['id'],
                'rating' => (int)$review['rating'],
                'title' => $review['title'],
                'review_text' => $review['review_text'],
                'is_spoiler' => (bool)$review['is_spoiler'],
                'total_likes' => (int)$review['total_likes'],
                'total_comments' => (int)$review['total_comments'],
                'created_at' => $review['created_at']
            ],
            'content' => [
                'id' => $content_id,
                'title' => $review['content_title'],
                'type' => $review['content_type'],
                'author_artist' => $review['author_artist'],
                'cover_image' => $review['cover_image']
            ],
            'user' => [
                'id' => $user_id,
                'username' => $review['username'],
                'avatar' => getAvatarUrl($review['avatar'])
            ]
        ], 'Reseña creada exitosamente', 201);
    } else {
        ApiResponse::error('Error al crear la reseña', 500);
    }
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al crear la reseña', 500);
    }
}
?>