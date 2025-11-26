// ========================================
// INK & SOUND - Enhanced JavaScript
// ========================================

// Sample data - En una app real, vendría de una API
const sampleBooks = [
    { 
        id: 1, 
        title: "Cien años de soledad", 
        author: "Gabriel García Márquez", 
        rating: 4.5, 
        category: "book",
        likes: 245,
        comments: 32,
        cover: "https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 2, 
        title: "1984", 
        author: "George Orwell", 
        rating: 4.7, 
        category: "book",
        likes: 389,
        comments: 56,
        cover: "https://images.unsplash.com/photo-1512820790803-83ca734da794?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 3, 
        title: "El principito", 
        author: "Antoine de Saint-Exupéry", 
        rating: 4.8, 
        category: "book",
        likes: 512,
        comments: 78,
        cover: "https://images.unsplash.com/photo-1621351183012-e2f9972dd9bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 4, 
        title: "Don Quijote", 
        author: "Miguel de Cervantes", 
        rating: 4.3, 
        category: "book",
        likes: 178,
        comments: 23,
        cover: "https://images.unsplash.com/photo-1532012197267-da84d127e765?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 5, 
        title: "Harry Potter", 
        author: "J.K. Rowling", 
        rating: 4.9, 
        category: "book",
        likes: 892,
        comments: 134,
        cover: "https://images.unsplash.com/photo-1621351183012-e2f9972dd9bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 6, 
        title: "Bohemian Rhapsody", 
        author: "Queen", 
        rating: 5.0, 
        category: "music",
        likes: 1205,
        comments: 201,
        cover: "https://images.unsplash.com/photo-1511379938547-c1f69419868d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 7, 
        title: "Imagine", 
        author: "John Lennon", 
        rating: 4.9, 
        category: "music",
        likes: 967,
        comments: 156,
        cover: "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    },
    { 
        id: 8, 
        title: "La Noche Estrellada", 
        author: "Vincent van Gogh", 
        rating: 5.0, 
        category: "art",
        likes: 1534,
        comments: 267,
        cover: "https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
    }
];

const sampleActivity = [
    { user: "María González", action: "calificó", item: "Cien años de soledad", rating: 5, time: "Hace 2 horas", avatar: "MG" },
    { user: "Carlos López", action: "reseñó", item: "Bohemian Rhapsody", rating: 4, time: "Hace 5 horas", avatar: "CL" },
    { user: "Ana Martínez", action: "agregó", item: "La noche estrellada a su colección", time: "Hace 1 día", avatar: "AM" },
    { user: "David Rodríguez", action: "comentó en", item: "tu reseña de 1984", time: "Hace 2 días", avatar: "DR" }
];

// ========================================
// App State Management
// ========================================
const AppState = {
    currentRating: 0,
    currentCategory: 'all',
    likedItems: new Set(JSON.parse(localStorage.getItem('likedItems') || '[]')),
    savedItems: new Set(JSON.parse(localStorage.getItem('savedItems') || '[]')),
    userReviews: JSON.parse(localStorage.getItem('userReviews') || '[]'),
    
    saveLikedItems() {
        localStorage.setItem('likedItems', JSON.stringify([...this.likedItems]));
    },
    
    saveSavedItems() {
        localStorage.setItem('savedItems', JSON.stringify([...this.savedItems]));
    },
    
    saveUserReviews() {
        localStorage.setItem('userReviews', JSON.stringify(this.userReviews));
    }
};

// ========================================
// DOM Elements Cache
// ========================================
const DOM = {
    // Modals
    loginBtn: document.getElementById('loginBtn'),
    registerBtn: document.getElementById('registerBtn'),
    loginModal: document.getElementById('loginModal'),
    registerModal: document.getElementById('registerModal'),
    closeModalButtons: document.querySelectorAll('.close-modal'),
    
    // Forms
    reviewForm: document.getElementById('reviewForm'),
    loginForm: document.getElementById('loginForm'),
    registerForm: document.getElementById('registerForm'),
    
    // Rating
    stars: document.querySelectorAll('.star'),
    ratingContainer: document.querySelector('.rating-stars'),
    
    // Content
    activityFeed: document.getElementById('activityFeed'),
    popularBooks: document.getElementById('popularBooks'),
    recommendations: document.getElementById('recommendations'),
    
    // Navigation
    tabs: document.querySelectorAll('.tab'),
    tabContents: document.querySelectorAll('.tab-content'),
    categoryTags: document.querySelectorAll('.category-tag'),
    
    // Theme
    themeOptions: document.querySelectorAll('.theme-option'),
    
    // Search
    searchInput: document.querySelector('.search-container input'),
    
    // Stats
    reviewsCount: document.getElementById('reviewsCount'),
    booksCount: document.getElementById('booksCount'),
    friendsCount: document.getElementById('friendsCount')
};

// ========================================
// Notification System
// ========================================
const Notifications = {
    container: null,
    
    init() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    },
    
    show(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;
        
        this.container.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};

// ========================================
// Modal Management
// ========================================
const ModalManager = {
    open(modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(() => modal.classList.add('modal-show'), 10);
    },
    
    close(modal) {
        modal.classList.remove('modal-show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    },
    
    init() {
        // Open modals
        DOM.loginBtn?.addEventListener('click', () => this.open(DOM.loginModal));
        DOM.registerBtn?.addEventListener('click', () => this.open(DOM.registerModal));
        
        // Close modals
        DOM.closeModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.close(DOM.loginModal);
                this.close(DOM.registerModal);
            });
        });
        
        // Close on outside click
        window.addEventListener('click', (e) => {
            if (e.target === DOM.loginModal) this.close(DOM.loginModal);
            if (e.target === DOM.registerModal) this.close(DOM.registerModal);
        });
        
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.close(DOM.loginModal);
                this.close(DOM.registerModal);
            }
        });
    }
};

// ========================================
// Theme Management
// ========================================
const ThemeManager = {
    init() {
        // Load saved theme
        const savedTheme = localStorage.getItem('ink-sound-theme') || 'dark';
        this.applyTheme(savedTheme);
        
        // Theme switcher
        DOM.themeOptions.forEach(option => {
            option.addEventListener('click', () => {
                const theme = option.getAttribute('data-theme');
                this.applyTheme(theme);
                localStorage.setItem('ink-sound-theme', theme);
                Notifications.show(`Tema ${theme} aplicado`, 'info', 2000);
            });
        });
    },
    
    applyTheme(theme) {
        document.body.classList.remove('theme-dark', 'theme-light', 'theme-blue', 'theme-green');
        document.body.classList.add(`theme-${theme}`);
        
        DOM.themeOptions.forEach(opt => opt.classList.remove('active'));
        document.querySelector(`[data-theme="${theme}"]`)?.classList.add('active');
    }
};

// ========================================
// Rating System
// ========================================
const RatingSystem = {
    init() {
        DOM.stars.forEach(star => {
            star.addEventListener('click', () => {
                AppState.currentRating = parseInt(star.getAttribute('data-rating'));
                this.updateStars(AppState.currentRating);
                Notifications.show(`${AppState.currentRating} estrella${AppState.currentRating > 1 ? 's' : ''} seleccionada${AppState.currentRating > 1 ? 's' : ''}`, 'info', 1500);
            });
            
            star.addEventListener('mouseover', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                this.updateStars(rating, false);
            });
        });
        
        DOM.ratingContainer?.addEventListener('mouseout', () => {
            this.updateStars(AppState.currentRating);
        });
    },
    
    updateStars(rating, permanent = true) {
        DOM.stars.forEach(star => {
            const starRating = parseInt(star.getAttribute('data-rating'));
            star.classList.toggle('active', starRating <= rating);
        });
        
        if (permanent) {
            AppState.currentRating = rating;
        }
    },
    
    reset() {
        this.updateStars(0);
        AppState.currentRating = 0;
    }
};

// ========================================
// Tab Management
// ========================================
const TabManager = {
    init() {
        DOM.tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                this.switchTab(tabId);
            });
        });
    },
    
    switchTab(tabId) {
        // Update active tab
        DOM.tabs.forEach(t => t.classList.remove('active'));
        document.querySelector(`[data-tab="${tabId}"]`)?.classList.add('active');
        
        // Show active content
        DOM.tabContents.forEach(content => {
            content.classList.remove('active');
            if (content.id === tabId) {
                content.classList.add('active');
                // Trigger fade-in animation
                content.style.animation = 'fadeIn 0.3s ease';
            }
        });
    }
};

// ========================================
// Category Filter
// ========================================
const CategoryFilter = {
    init() {
        DOM.categoryTags.forEach(tag => {
            tag.addEventListener('click', () => {
                const category = tag.textContent.toLowerCase();
                this.filterByCategory(category);
                
                // Update active tag
                DOM.categoryTags.forEach(t => t.classList.remove('active'));
                tag.classList.add('active');
            });
        });
    },
    
    filterByCategory(category) {
        AppState.currentCategory = category;
        
        const filteredBooks = category === 'todo' 
            ? sampleBooks 
            : sampleBooks.filter(book => {
                if (category === 'libros') return book.category === 'book';
                if (category === 'música') return book.category === 'music';
                if (category === 'arte') return book.category === 'art';
                if (category === 'populares') return book.likes > 500;
                return true;
            });
        
        ContentRenderer.renderBooks(filteredBooks, DOM.popularBooks);
        Notifications.show(`Mostrando: ${category}`, 'info', 2000);
    }
};

// ========================================
// Search Functionality
// ========================================
const SearchManager = {
    init() {
        if (!DOM.searchInput) return;
        
        let searchTimeout;
        DOM.searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });
    },
    
    performSearch(query) {
        if (!query.trim()) {
            CategoryFilter.filterByCategory(AppState.currentCategory);
            return;
        }
        
        const results = sampleBooks.filter(book => 
            book.title.toLowerCase().includes(query.toLowerCase()) ||
            book.author.toLowerCase().includes(query.toLowerCase())
        );
        
        ContentRenderer.renderBooks(results, DOM.popularBooks);
        
        if (results.length === 0) {
            DOM.popularBooks.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <p>No se encontraron resultados para "${query}"</p>
                </div>
            `;
        }
    }
};

// ========================================
// Content Renderer
// ========================================
const ContentRenderer = {
    renderBooks(books, container) {
        if (!container) return;
        
        container.innerHTML = '';
        books.forEach(book => {
            const card = document.createElement('div');
            card.className = 'card';
            card.style.animation = 'fadeInUp 0.5s ease';
            
            const isLiked = AppState.likedItems.has(book.id);
            const isSaved = AppState.savedItems.has(book.id);
            
            card.innerHTML = `
                <img src="${book.cover}" alt="${book.title}" class="card-img" loading="lazy">
                <div class="card-content">
                    <div class="card-title">${book.title}</div>
                    <div class="card-meta">${book.author}</div>
                    <div class="card-rating">
                        ${'★'.repeat(Math.floor(book.rating))}${book.rating % 1 >= 0.5 ? '½' : ''}${'☆'.repeat(5 - Math.ceil(book.rating))} 
                        ${book.rating}
                    </div>
                    <div class="card-stats">
                        <span><i class="fas fa-heart"></i> ${book.likes}</span>
                        <span><i class="fas fa-comment"></i> ${book.comments}</span>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn ${isLiked ? 'active' : ''}" data-action="like" data-id="${book.id}">
                            <i class="fa${isLiked ? 's' : 'r'} fa-heart"></i>
                        </button>
                        <button class="card-btn" data-action="comment" data-id="${book.id}">
                            <i class="far fa-comment"></i>
                        </button>
                        <button class="card-btn ${isSaved ? 'active' : ''}" data-action="save" data-id="${book.id}">
                            <i class="fa${isSaved ? 's' : 'r'} fa-bookmark"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(card);
        });
        
        // Add event listeners to card buttons
        this.attachCardListeners(container);
    },
    
    attachCardListeners(container) {
        const buttons = container.querySelectorAll('.card-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = btn.getAttribute('data-action');
                const id = parseInt(btn.getAttribute('data-id'));
                
                InteractionManager.handleCardAction(action, id, btn);
            });
        });
    },
    
    addActivityItem(user, action, item, rating, time, avatar) {
        if (!DOM.activityFeed) return;
        
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        activityItem.style.animation = 'slideInLeft 0.5s ease';
        
        activityItem.innerHTML = `
            <div class="activity-avatar">${avatar || user.charAt(0)}</div>
            <div class="activity-content">
                <div>
                    <span class="activity-user">${user}</span> 
                    <span class="activity-action">${action}</span> 
                    <span class="activity-item-title">${item}</span>
                </div>
                ${rating ? `<div class="card-rating">${'★'.repeat(rating)}${'☆'.repeat(5-rating)}</div>` : ''}
                <div class="activity-time">${time}</div>
            </div>
        `;
        
        DOM.activityFeed.insertBefore(activityItem, DOM.activityFeed.firstChild);
    }
};

// ========================================
// Interaction Manager
// ========================================
const InteractionManager = {
    handleCardAction(action, id, btn) {
        const book = sampleBooks.find(b => b.id === id);
        if (!book) return;
        
        switch(action) {
            case 'like':
                this.toggleLike(id, btn, book);
                break;
            case 'comment':
                this.showCommentModal(book);
                break;
            case 'save':
                this.toggleSave(id, btn, book);
                break;
        }
    },
    
    toggleLike(id, btn, book) {
        const icon = btn.querySelector('i');
        
        if (AppState.likedItems.has(id)) {
            AppState.likedItems.delete(id);
            btn.classList.remove('active');
            icon.classList.replace('fas', 'far');
            book.likes--;
            Notifications.show('Me gusta eliminado', 'info', 2000);
        } else {
            AppState.likedItems.add(id);
            btn.classList.add('active');
            icon.classList.replace('far', 'fas');
            book.likes++;
            Notifications.show(`¡Te gusta "${book.title}"!`, 'success', 2000);
            
            // Animate button
            btn.style.animation = 'heartBeat 0.5s ease';
            setTimeout(() => btn.style.animation = '', 500);
        }
        
        AppState.saveLikedItems();
        this.updateStats(book);
    },
    
    toggleSave(id, btn, book) {
        const icon = btn.querySelector('i');
        
        if (AppState.savedItems.has(id)) {
            AppState.savedItems.delete(id);
            btn.classList.remove('active');
            icon.classList.replace('fas', 'far');
            Notifications.show('Eliminado de guardados', 'info', 2000);
        } else {
            AppState.savedItems.add(id);
            btn.classList.add('active');
            icon.classList.replace('far', 'fas');
            Notifications.show(`"${book.title}" guardado`, 'success', 2000);
        }
        
        AppState.saveSavedItems();
    },
    
    showCommentModal(book) {
        Notifications.show(`Comentar en "${book.title}" (próximamente)`, 'info', 2500);
    },
    
    updateStats(book) {
        // Update likes count in card
        const statsElement = document.querySelector(`[data-id="${book.id}"]`)
            ?.closest('.card')
            ?.querySelector('.card-stats span:first-child');
        
        if (statsElement) {
            statsElement.innerHTML = `<i class="fas fa-heart"></i> ${book.likes}`;
        }
    }
};

// ========================================
// Form Management
// ========================================
const FormManager = {
    init() {
        // Review form
        DOM.reviewForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleReviewSubmit();
        });
        
        // Login form
        DOM.loginForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });
        
        // Register form
        DOM.registerForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });
    },
    
    handleReviewSubmit() {
        const itemType = document.getElementById('itemSelect')?.value;
        const itemTitle = document.getElementById('itemTitle')?.value;
        const reviewText = document.getElementById('reviewText')?.value;
        
        // Validation
        if (!itemType || !itemTitle?.trim() || !reviewText?.trim() || AppState.currentRating === 0) {
            Notifications.show('Por favor, completa todos los campos y selecciona una calificación', 'error', 4000);
            return;
        }
        
        // Save review
        const review = {
            id: Date.now(),
            itemType,
            itemTitle,
            reviewText,
            rating: AppState.currentRating,
            date: new Date().toISOString()
        };
        
        AppState.userReviews.unshift(review);
        AppState.saveUserReviews();
        
        // Add to activity feed
        ContentRenderer.addActivityItem(
            'Tú', 
            'reseñó', 
            itemTitle, 
            AppState.currentRating, 
            'Ahora mismo',
            'YO'
        );
        
        // Update stats
        if (DOM.reviewsCount) {
            DOM.reviewsCount.textContent = AppState.userReviews.length;
        }
        
        // Reset form
        DOM.reviewForm.reset();
        RatingSystem.reset();
        
        Notifications.show('¡Reseña publicada con éxito!', 'success', 3000);
    },
    
    handleLogin() {
        const email = document.getElementById('loginEmail')?.value;
        const password = document.getElementById('loginPassword')?.value;
        
        if (!this.validateEmail(email)) {
            Notifications.show('Por favor, ingresa un correo válido', 'error', 3000);
            return;
        }
        
        if (!password || password.length < 6) {
            Notifications.show('La contraseña debe tener al menos 6 caracteres', 'error', 3000);
            return;
        }
        
        // Simulate login
        Notifications.show('¡Inicio de sesión exitoso!', 'success', 3000);
        ModalManager.close(DOM.loginModal);
        DOM.loginForm.reset();
    },
    
    handleRegister() {
        const name = document.getElementById('registerName')?.value;
        const email = document.getElementById('registerEmail')?.value;
        const password = document.getElementById('registerPassword')?.value;
        
        if (!name?.trim()) {
            Notifications.show('Por favor, ingresa tu nombre', 'error', 3000);
            return;
        }
        
        if (!this.validateEmail(email)) {
            Notifications.show('Por favor, ingresa un correo válido', 'error', 3000);
            return;
        }
        
        if (!password || password.length < 6) {
            Notifications.show('La contraseña debe tener al menos 6 caracteres', 'error', 3000);
            return;
        }
        
        // Simulate registration
        Notifications.show('¡Cuenta creada exitosamente!', 'success', 3000);
        ModalManager.close(DOM.registerModal);
        DOM.registerForm.reset();
    },
    
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
};

// ========================================
// Stats Manager
// ========================================
const StatsManager = {
    init() {
        this.updateStats();
        this.animateStats();
    },
    
    updateStats() {
        if (DOM.reviewsCount) {
            DOM.reviewsCount.textContent = AppState.userReviews.length;
        }
    },
    
    animateStats() {
        const stats = document.querySelectorAll('.stat-value');
        stats.forEach(stat => {
            const target = parseInt(stat.textContent);
            let current = 0;
            const increment = target / 50;
            
            const updateCount = () => {
                if (current < target) {
                    current += increment;
                    stat.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCount);
                } else {
                    stat.textContent = target;
                }
            };
            
            // Trigger animation when element is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCount();
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(stat);
        });
    }
};

// ========================================
// Smooth Scroll
// ========================================
const SmoothScroll = {
    init() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
};

// ========================================
// Lazy Loading Images
// ========================================
const LazyLoader = {
    init() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        }
    }
};

// ========================================
// Initialize Application
// ========================================
function initApp() {
    // Initialize all modules
    Notifications.init();
    ThemeManager.init();
    ModalManager.init();
    RatingSystem.init();
    TabManager.init();
    CategoryFilter.init();
    SearchManager.init();
    FormManager.init();
    StatsManager.init();
    SmoothScroll.init();
    LazyLoader.init();
    
    // Load initial content
    loadInitialContent();
    
    // Show welcome notification
    setTimeout(() => {
        Notifications.show('¡Bienvenido a Ink & Sound!', 'success', 3000);
    }, 500);
}

// ========================================
// Load Initial Content
// ========================================
function loadInitialContent() {
    // Load sample activity
    sampleActivity.forEach(activity => {
        ContentRenderer.addActivityItem(
            activity.user, 
            activity.action, 
            activity.item, 
            activity.rating, 
            activity.time,
            activity.avatar
        );
    });
    
    // Load popular books
    ContentRenderer.renderBooks(sampleBooks, DOM.popularBooks);
    
    // Load recommendations
    ContentRenderer.renderBooks(sampleBooks.slice(0, 3), DOM.recommendations);
}

// ========================================
// Start the application
// ========================================
document.addEventListener('DOMContentLoaded', initApp);

// Export for potential module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AppState, ContentRenderer, Notifications };
}