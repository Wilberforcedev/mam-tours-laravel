import './bootstrap';
import Alpine from 'alpinejs';
import { createApp } from 'vue';

// Make Alpine available globally
window.Alpine = Alpine;
Alpine.start();

// Vue components
import BookingForm from './components/BookingForm.vue';
import PaymentProcessor from './components/PaymentProcessor.vue';
import NotificationCenter from './components/NotificationCenter.vue';

// Create Vue app if there are Vue components on the page
const vueElements = document.querySelectorAll('[data-vue-component]');
if (vueElements.length > 0) {
    const app = createApp({});
    
    app.component('booking-form', BookingForm);
    app.component('payment-processor', PaymentProcessor);
    app.component('notification-center', NotificationCenter);
    
    app.mount('#app');
}

// Global utilities
window.utils = {
    // Format currency
    formatCurrency(amount, currency = 'UGX') {
        return new Intl.NumberFormat('en-UG', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
        }).format(amount);
    },
    
    // Format date
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        };
        return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
    },
    
    // Show notification
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg animate-fade-in alert alert-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    },
    
    // Copy to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showNotification('Copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy: ', err);
            this.showNotification('Failed to copy to clipboard', 'error');
        }
    }
};

// Enhanced form handling
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save form data
    const forms = document.querySelectorAll('[data-auto-save]');
    forms.forEach(form => {
        const formId = form.dataset.autoSave;
        
        // Load saved data
        const savedData = localStorage.getItem(`form_${formId}`);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) input.value = data[key];
            });
        }
        
        // Save on input
        const saveData = utils.debounce(() => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem(`form_${formId}`, JSON.stringify(data));
        }, 1000);
        
        form.addEventListener('input', saveData);
        
        // Clear on submit
        form.addEventListener('submit', () => {
            localStorage.removeItem(`form_${formId}`);
        });
    });
    
    // Enhanced loading states
    const loadingButtons = document.querySelectorAll('[data-loading]');
    loadingButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.form && !this.form.checkValidity()) return;
            
            this.disabled = true;
            this.classList.add('loading');
            
            const originalText = this.textContent;
            this.textContent = this.dataset.loading || 'Loading...';
            
            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                this.disabled = false;
                this.classList.remove('loading');
                this.textContent = originalText;
            }, 10000);
        });
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Image lazy loading fallback
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('loading');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.classList.add('loading');
            imageObserver.observe(img);
        });
    }
});