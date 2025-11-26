<?php
/**
 * ========================================
 * INK & SOUND - API RESPONSE HANDLER
 * ========================================
 */

class ApiResponse {
    /**
     * Enviar respuesta JSON exitosa
     */
    public static function success($data = [], $message = 'Operación exitosa', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
    }
    
    /**
     * Enviar respuesta JSON de error
     */
    public static function error($message = 'Error en la operación', $code = 400, $errors = []) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
    }
    
    /**
     * Validar método HTTP
     */
    public static function validateMethod($allowedMethods = ['POST']) {
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            self::error('Método HTTP no permitido', 405);
        }
    }
    
    /**
     * Obtener datos JSON del body
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::error('JSON inválido', 400);
        }
        
        return $data ?? [];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            self::error('Token CSRF inválido', 403);
        }
    }
    
    /**
     * Verificar autenticación
     */
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            self::error('No autenticado', 401);
        }
        return $_SESSION['user_id'];
    }
}

// Configurar headers CORS (ajustar según necesidad)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>