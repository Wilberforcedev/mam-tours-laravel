import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token setup
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Request interceptor for loading states
axios.interceptors.request.use(
    config => {
        // Show global loading indicator
        document.body.classList.add('loading-request');
        return config;
    },
    error => {
        document.body.classList.remove('loading-request');
        return Promise.reject(error);
    }
);

// Response interceptor for error handling
axios.interceptors.response.use(
    response => {
        document.body.classList.remove('loading-request');
        return response;
    },
    error => {
        document.body.classList.remove('loading-request');
        
        // Handle common errors
        if (error.response) {
            switch (error.response.status) {
                case 401:
                    window.utils?.showNotification('Session expired. Please log in again.', 'error');
                    setTimeout(() => window.location.href = '/login', 2000);
                    break;
                case 403:
                    window.utils?.showNotification('Access denied.', 'error');
                    break;
                case 422:
                    // Validation errors - handled by individual forms
                    break;
                case 500:
                    window.utils?.showNotification('Server error. Please try again later.', 'error');
                    break;
                default:
                    window.utils?.showNotification('An error occurred. Please try again.', 'error');
            }
        } else if (error.request) {
            window.utils?.showNotification('Network error. Please check your connection.', 'error');
        }
        
        return Promise.reject(error);
    }
);