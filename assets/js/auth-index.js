// ========================================
// AUTH FUNCTIONALITY FOR INDEX.HTML
// ========================================

// Toast notification
function showToast(message, type = 'success') {
    const container = document.querySelector('.toast-container');
    if (!container) {
        const newContainer = document.createElement('div');
        newContainer.className = 'toast-container';
        document.body.appendChild(newContainer);
    }
    
    const toastContainer = document.querySelector('.toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Show alert in modal
function showModalAlert(containerId, message, type = 'error') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const icon = type === 'error' ? 'exclamation-circle' : 'check-circle';
    const bgColor = type === 'error' ? 'rgba(231, 76, 60, 0.2)' : 'rgba(39, 174, 96, 0.2)';
    const borderColor = type === 'error' ? '#E74C3C' : '#27AE60';
    const textColor = type === 'error' ? '#E74C3C' : '#27AE60';
    
    container.innerHTML = `
        <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 8px; background: ${bgColor}; border: 1px solid ${borderColor}; color: ${textColor};">
            <i class="fas fa-${icon}"></i> ${message}
        </div>
    `;
}

function clearModalAlert(containerId) {
    const container = document.getElementById(containerId);
    if (container) container.innerHTML = '';
}

function showFieldError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + 'Error');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearFieldErrors() {
    document.querySelectorAll('[id$="Error"]').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
}

// LOGIN FORM
const loginFormElement = document.getElementById('loginForm');
if (loginFormElement) {
    loginFormElement.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        console.log('Login form submitted');
        clearModalAlert('loginAlert');
        
        const submitBtn = document.getElementById('loginSubmitBtn');
        const btnText = submitBtn.querySelector('span');
        const originalText = btnText.textContent;
        
        submitBtn.disabled = true;
        btnText.textContent = 'Iniciando sesión...';
        
        try {
            const response = await ApiService.login({
                username_email: document.getElementById('loginUsernameEmail').value,
                password: document.getElementById('loginPassword').value,
                remember: document.getElementById('loginRemember').checked
            });
            
            console.log('Login response:', response);
            
            if (response.success) {
                showToast('¡Bienvenido de vuelta!', 'success');
                
                // Store user data
                localStorage.setItem('user', JSON.stringify(response.data.user));
                localStorage.setItem('ink-sound-theme', response.data.user.theme_preference || 'dark');
                
                // Close modal and reload page
                document.getElementById('loginModal').style.display = 'none';
                
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                showModalAlert('loginAlert', response.message, 'error');
                submitBtn.disabled = false;
                btnText.textContent = originalText;
            }
        } catch (error) {
            console.error('Login error:', error);
            showModalAlert('loginAlert', error.message || 'Error al iniciar sesión', 'error');
            submitBtn.disabled = false;
            btnText.textContent = originalText;
        }
    });
}

// Quick login buttons
document.querySelectorAll('.quick-login').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('loginUsernameEmail').value = btn.dataset.username;
        document.getElementById('loginPassword').value = btn.dataset.password;
    });
});

// REGISTER FORM
const registerFormElement = document.getElementById('registerForm');
if (registerFormElement) {
    registerFormElement.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        console.log('Register form submitted');
        clearModalAlert('registerAlert');
        clearFieldErrors();
        
        const submitBtn = document.getElementById('registerSubmitBtn');
        const btnText = submitBtn.querySelector('span');
        const originalText = btnText.textContent;
        
        submitBtn.disabled = true;
        btnText.textContent = 'Creando cuenta...';
        
        try {
            const response = await ApiService.register({
                full_name: document.getElementById('registerName').value,
                username: document.getElementById('registerUsername').value,
                email: document.getElementById('registerEmail').value,
                password: document.getElementById('registerPassword').value,
                interests: document.getElementById('registerInterests').value
            });
            
            console.log('Register response:', response);
            
            if (response.success) {
                showToast('¡Cuenta creada exitosamente!', 'success');
                
                // Store user data
                localStorage.setItem('user', JSON.stringify(response.data.user));
                
                // Close modal and reload page
                document.getElementById('registerModal').style.display = 'none';
                
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                // Show field-specific errors
                if (response.errors) {
                    Object.keys(response.errors).forEach(field => {
                        if (field === 'username') showFieldError('registerUsername', response.errors[field]);
                        else if (field === 'email') showFieldError('registerEmail', response.errors[field]);
                        else if (field === 'password') showFieldError('registerPassword', response.errors[field]);
                    });
                }
                showModalAlert('registerAlert', response.message, 'error');
                submitBtn.disabled = false;
                btnText.textContent = originalText;
            }
        } catch (error) {
            console.error('Register error:', error);
            showModalAlert('registerAlert', error.message || 'Error al crear la cuenta', 'error');
            submitBtn.disabled = false;
            btnText.textContent = originalText;
        }
    });
}

// Switch between login and register
const switchToRegisterBtn = document.getElementById('switchToRegister');
if (switchToRegisterBtn) {
    switchToRegisterBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('loginModal').style.display = 'none';
        document.getElementById('registerModal').style.display = 'flex';
        clearModalAlert('loginAlert');
        clearModalAlert('registerAlert');
    });
}

const switchToLoginBtn = document.getElementById('switchToLogin');
if (switchToLoginBtn) {
    switchToLoginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('registerModal').style.display = 'none';
        document.getElementById('loginModal').style.display = 'flex';
        clearModalAlert('loginAlert');
        clearModalAlert('registerAlert');
    });
}

// Check if user is logged in
window.addEventListener('DOMContentLoaded', () => {
    console.log('Checking login status...');
    const user = localStorage.getItem('user');
    if (user) {
        try {
            const userData = JSON.parse(user);
            console.log('User logged in:', userData);
            
            // Update UI for logged in user
            const userActions = document.querySelector('.user-actions');
            if (userActions) {
                userActions.innerHTML = `
                    <div class="search-container">
                        <input type="text" placeholder="Buscar arte, música, libros..." aria-label="Buscar contenido">
                        <button aria-label="Buscar"><i class="fas fa-search"></i></button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="color: var(--text-primary);">
                            <i class="fas fa-user-circle"></i> ${userData.username}
                        </span>
                        <button class="btn" onclick="logout()">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </button>
                    </div>
                `;
            }
        } catch (e) {
            console.error('Error parsing user data:', e);
            localStorage.removeItem('user');
        }
    } else {
        console.log('No user logged in');
    }
});

// Logout function
async function logout() {
    if (!confirm('¿Seguro que deseas cerrar sesión?')) return;
    
    try {
        await ApiService.logout();
        localStorage.removeItem('user');
        showToast('Sesión cerrada', 'success');
        setTimeout(() => {
            window.location.reload();
        }, 500);
    } catch (error) {
        console.error('Logout error:', error);
        // Force logout even if API fails
        localStorage.removeItem('user');
        window.location.reload();
    }
}