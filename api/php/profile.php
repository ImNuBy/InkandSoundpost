<?php
/**
 * ========================================
 * INK & SOUND - API GET PROFILE
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Permitir GET
ApiResponse::validateMethod(['GET']);

// Obtener parámetros
$username = $_GET['username'] ?? null;
$user_id = $_GET['user_id'] ?? null;

if (!$username && !$user_id) {
    ApiResponse::error('Debes proporcionar username o user_id', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Construir query según el parámetro
    if ($username) {
        $stmt = $db->prepare("
            SELECT u.*, up.*
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
    } else {
        $stmt = $db->prepare("
            SELECT u.*, up.*
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
    }
    
    $user = $stmt->fetch();
    
    if (!$user) {
        ApiResponse::error('Usuario no encontrado', 404);
    }
    
    // Obtener estadísticas
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT r.id) as total_reviews,
            COUNT(DISTINCT c.id) as total_collections,
            COUNT(DISTINCT f1.id) as friends_count,
            COUNT(DISTINCT f2.follower_id) as followers_count,
            COUNT(DISTINCT f3.following_id) as following_count
        FROM users u
        LEFT JOIN reviews r ON u.id = r.user_id
        LEFT JOIN collections c ON u.id = c.user_id
        LEFT JOIN friendships f1 ON u.id = f1.user_id AND f1.status = 'accepted'
        LEFT JOIN follows f2 ON u.id = f2.following_id
        LEFT JOIN follows f3 ON u.id = f3.follower_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch();
    
    // Obtener badges ganados
    $stmt = $db->prepare("
        SELECT b.*, ub.earned_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_at DESC
    ");
    $stmt->execute([$user['id']]);
    $badges = $stmt->fetchAll();
    
    // Obtener reseñas recientes
    $stmt = $db->prepare("
        SELECT r.*, c.title as content_title, c.type as content_type, c.cover_image
        FROM reviews r
        JOIN content c ON r.content_id = c.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $recent_reviews = $stmt->fetchAll();
    
    // Verificar si el usuario actual está siguiendo este perfil
    $is_following = false;
    $is_friend = false;
    
    if (isLoggedIn() && $_SESSION['user_id'] != $user['id']) {
        $stmt = $db->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$_SESSION['user_id'], $user['id']]);
        $is_following = (bool)$stmt->fetch();
        
        $stmt = $db->prepare("
            SELECT id FROM friendships 
            WHERE (user_id = ? AND friend_id = ? OR user_id = ? AND friend_id = ?) 
            AND status = 'accepted'
        ");
        $stmt->execute([$_SESSION['user_id'], $user['id'], $user['id'], $_SESSION['user_id']]);
        $is_friend = (bool)$stmt->fetch();
    }
    
    // Preparar respuesta
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => isLoggedIn() && $_SESSION['user_id'] == $user['id'] ? $user['email'] : null,
            'full_name' => $user['full_name'],
            'bio' => $user['bio'],
            'avatar' => getAvatarUrl($user['avatar']),
            'cover_image' => getCoverUrl($user['cover_image']),
            'location' => $user['location'],
            'website' => $user['website'],
            'interests' => $user['interests'],
            'theme_preference' => $user['theme_preference'],
            'is_verified' => (bool)$user['is_verified'],
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login']
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
            'social_youtube' => $user['social_youtube']
        ],
        'stats' => [
            'reviews' => (int)($stats['total_reviews'] ?? 0),
            'collections' => (int)($stats['total_collections'] ?? 0),
            'friends' => (int)($stats['friends_count'] ?? 0),
            'followers' => (int)($stats['followers_count'] ?? 0),
            'following' => (int)($stats['following_count'] ?? 0)
        ],
        'badges' => array_map(function($badge) {
            return [
                'id' => $badge['id'],
                'name' => $badge['name'],
                'description' => $badge['description'],
                'icon' => $badge['icon'],
                'earned_at' => $badge['earned_at']
            ];
        }, $badges),
        'recent_reviews' => array_map(function($review) {
            return [
                'id' => $review['id'],
                'rating' => (int)$review['rating'],
                'title' => $review['title'],
                'review_text' => substr($review['review_text'], 0, 150) . '...',
                'content_title' => $review['content_title'],
                'content_type' => $review['content_type'],
                'cover_image' => $review['cover_image'],
                'created_at' => $review['created_at']
            ];
        }, $recent_reviews),
        'relationship' => [
            'is_own_profile' => isLoggedIn() && $_SESSION['user_id'] == $user['id'],
            'is_following' => $is_following,
            'is_friend' => $is_friend
        ]
    ], 'Perfil obtenido exitosamente');
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al obtener el perfil', 500);
    }
}
?>