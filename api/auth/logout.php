<?php
/**
 * ========================================
 * INK & SOUND - API LOGOUT
 * ========================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Response.php';

// Solo permitir POST
ApiResponse::validateMethod(['POST']);

// Verificar autenticación
$user_id = ApiResponse::requireAuth();

try {
    // Registrar actividad antes de cerrar sesión
    logActivity($user_id, 'logout', 'cerró sesión');
    
    // Destruir sesión
    session_destroy();
    
    // Limpiar cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    ApiResponse::success([], 'Sesión cerrada exitosamente');
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        ApiResponse::error('Error: ' . $e->getMessage(), 500);
    } else {
        ApiResponse::error('Error al cerrar sesión', 500);
    }
}
?>