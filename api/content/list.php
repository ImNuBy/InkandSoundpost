<?php
/**
 * ========================================
 * INK & SOUND - API GET CONTENT
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Permitir GET
ApiResponse::validateMethod(['GET']);

// Obtener parámetros
$type = $_GET['type'] ?? 'all'; // all, book, music, art
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'recent'; // recent, popular, rating
$limit = (int)($_GET['limit'] ?? 20);
$offset = (int)($_GET['offset'] ?? 0);

// Validar límite
if ($limit > 100) $limit = 100;
if ($limit < 1) $limit = 20;

try {
    $db = Database::getInstance()->getConnection();
    
    // Construir query base
    $where_clauses = [];
    $params = [];
    
    // Filtro por tipo
    if ($type !== 'all') {
        $where_clauses[] = "c.type = ?";
        $params[] = $type;
    }
    
    // Filtro por categoría
    if ($category) {
        $where_clauses[] = "c.category = ?";
        $params[] = $category;
    }
    
    // Búsqueda por texto
    if ($search) {
        $where_clauses[] = "(MATCH(c.title, c.author_artist, c.description) AGAINST(? IN NATURAL LANGUAGE MODE) 
                            OR c.title LIKE ? 
                            OR c.author_artist LIKE ?)";
        $params[] = $search;
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Ordenamiento
    $order_sql = match($sort) {
        'popular' => 'ORDER BY c.total_likes DESC, c.total_ratings DESC',
        'rating' => 'ORDER BY c.avg_rating DESC, c.total_ratings DESC',
        default => 'ORDER BY c.created_at DESC'
    };
    
    // Contar total
    $count_sql = "SELECT COUNT(*) as total FROM content c {$where_sql}";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // Obtener contenido
    $sql = "
        SELECT c.*, u.username as creator_username, u.avatar as creator_avatar
        FROM content c
        JOIN users u ON c.user_id = u.id
        {$where_sql}
        {$order_sql}
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $content_items = $stmt->fetchAll();
    
    // Formatear respuesta
    $formatted_items = array_map(function($item) {
        return [
            'id' => $item['id'],
            'type' => $item['type'],
            'title' => $item['title'],
            'author_artist' => $item['author_artist'],
            'description' => $item['description'],
            'cover_image' => $item['cover_image'],
            'category' => $item['category'],
            'year' => $item['year'],
            'genre' => $item['genre'],
            'avg_rating' => (float)$item['avg_rating'],
            'total_ratings' => (int)$item['total_ratings'],
            'total_reviews' => (int)$item['total_reviews'],
            'total_likes' => (int)$item['total_likes'],
            'is_featured' => (bool)$item['is_featured'],
            'created_at' => $item['created_at'],
            'creator' => [
                'username' => $item['creator_username'],
                'avatar' => getAvatarUrl($item['creator_avatar'])
            ]
        ];
    }, $content_items);
    
    ApiResponse::success([
        'items' => $formatted_items,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ],
        'filters' => [
            'type' => $type,
            'category' => $category,
            'search' => $search,
            'sort' => $sort
        ]
    ], 'Contenido obtenido exitosamente');
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error de base de datos: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al obtener el contenido', 500);
    }
}
?>