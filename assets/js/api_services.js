// ========================================
// INK & SOUND - API SERVICE
// ========================================

const API_BASE_URL = '/ink-and-sound/api';

/**
 * Clase para manejar todas las llamadas a la API
 */
class ApiService {
    /**
     * Realizar petición HTTP
     */
    static async request(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers,
            },
        };

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * GET request
     */
    static async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, {
            method: 'GET',
        });
    }

    /**
     * POST request
     */
    static async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    /**
     * PUT request
     */
    static async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    /**
     * DELETE request
     */
    static async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE',
        });
    }

    /**
     * Upload file
     */
    static async upload(endpoint, formData) {
        return fetch(`${API_BASE_URL}${endpoint}`, {
            method: 'POST',
            body: formData,
            // No establecer Content-Type para multipart/form-data
        }).then(response => response.json());
    }

    // ========================================
    // AUTH ENDPOINTS
    // ========================================

    /**
     * Registrar usuario
     */
    static async register(userData) {
        return this.post('/auth/register.php', userData);
    }

    /**
     * Iniciar sesión
     */
    static async login(credentials) {
        return this.post('/auth/login.php', credentials);
    }

    /**
     * Cerrar sesión
     */
    static async logout() {
        return this.post('/auth/logout.php');
    }

    // ========================================
    // USER ENDPOINTS
    // ========================================

    /**
     * Obtener perfil de usuario
     */
    static async getProfile(params) {
        return this.get('/user/profile.php', params);
    }

    /**
     * Actualizar perfil
     */
    static async updateProfile(data) {
        return this.put('/user/update.php', data);
    }

    /**
     * Subir avatar
     */
    static async uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        return this.upload('/user/upload-avatar.php', formData);
    }

    /**
     * Subir cover
     */
    static async uploadCover(file) {
        const formData = new FormData();
        formData.append('cover', file);
        return this.upload('/user/upload-cover.php', formData);
    }

    // ========================================
    // CONTENT ENDPOINTS
    // ========================================

    /**
     * Obtener lista de contenido
     */
    static async getContent(filters = {}) {
        return this.get('/content/list.php', filters);
    }

    /**
     * Obtener detalles de contenido
     */
    static async getContentDetails(id) {
        return this.get('/content/details.php', { id });
    }

    /**
     * Crear reseña
     */
    static async createReview(reviewData) {
        return this.post('/content/create-review.php', reviewData);
    }

    /**
     * Dar/quitar like
     */
    static async toggleLike(likeableType, likeableId) {
        return this.post('/content/like.php', {
            likeable_type: likeableType,
            likeable_id: likeableId
        });
    }

    /**
     * Crear comentario
     */
    static async createComment(commentData) {
        return this.post('/content/create-comment.php', commentData);
    }

    /**
     * Buscar contenido
     */
    static async search(query, filters = {}) {
        return this.get('/content/list.php', {
            search: query,
            ...filters
        });
    }

    // ========================================
    // ACTIVITY ENDPOINTS
    // ========================================

    /**
     * Obtener actividad reciente
     */
    static async getRecentActivity(params = {}) {
        return this.get('/activity/recent.php', params);
    }

    /**
     * Obtener actividad de amigos
     */
    static async getFriendsActivity(params = {}) {
        return this.get('/activity/friends.php', params);
    }

    // ========================================
    // SOCIAL ENDPOINTS
    // ========================================

    /**
     * Seguir usuario
     */
    static async followUser(userId) {
        return this.post('/social/follow.php', { user_id: userId });
    }

    /**
     * Dejar de seguir
     */
    static async unfollowUser(userId) {
        return this.post('/social/unfollow.php', { user_id: userId });
    }

    /**
     * Enviar solicitud de amistad
     */
    static async sendFriendRequest(userId) {
        return this.post('/social/friend-request.php', { user_id: userId });
    }

    /**
     * Aceptar solicitud de amistad
     */
    static async acceptFriendRequest(requestId) {
        return this.post('/social/accept-friend.php', { request_id: requestId });
    }
}

// Exportar para uso global
window.ApiService = ApiService;