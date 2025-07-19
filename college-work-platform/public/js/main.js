// Main JavaScript file for College Work Platform

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize application
function initializeApp() {
    // Initialize form validation
    initFormValidation();
    
    // Initialize price calculator
    initPriceCalculator();
    
    // Initialize file upload
    initFileUpload();
    
    // Initialize notifications
    initNotifications();
    
    // Initialize mobile menu
    initMobileMenu();
    
    // Initialize tooltips and popovers
    initTooltips();
}

// Form validation
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    // Clear previous validation
    field.classList.remove('is-valid', 'is-invalid');
    
    // Required field validation
    if (required && !value) {
        setFieldInvalid(field, 'This field is required');
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setFieldInvalid(field, 'Please enter a valid email address');
            return false;
        }
    }
    
    // Password validation
    if (field.name === 'password' && value) {
        if (value.length < 8) {
            setFieldInvalid(field, 'Password must be at least 8 characters long');
            return false;
        }
    }
    
    // Password confirmation
    if (field.name === 'confirm_password' && value) {
        const password = document.querySelector('input[name="password"]');
        if (password && value !== password.value) {
            setFieldInvalid(field, 'Passwords do not match');
            return false;
        }
    }
    
    // Phone validation
    if (type === 'tel' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
            setFieldInvalid(field, 'Please enter a valid phone number');
            return false;
        }
    }
    
    // Set field as valid
    setFieldValid(field);
    return true;
}

// Set field as invalid
function setFieldInvalid(field, message) {
    field.classList.add('is-invalid');
    
    let feedback = field.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.appendChild(feedback);
    }
    feedback.textContent = message;
}

// Set field as valid
function setFieldValid(field) {
    field.classList.add('is-valid');
    
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.remove();
    }
}

// Price calculator
function initPriceCalculator() {
    const categorySelect = document.getElementById('category_id');
    const wordCountInput = document.getElementById('word_count');
    const deadlineInput = document.getElementById('deadline');
    const academicLevelSelect = document.getElementById('academic_level');
    const priceDisplay = document.getElementById('price_display');
    
    if (!categorySelect || !wordCountInput || !deadlineInput || !priceDisplay) {
        return;
    }
    
    // Base prices for categories (per 250 words)
    const basePrices = {
        '1': 15.00, // Essays
        '2': 20.00, // Research Papers
        '3': 25.00, // Coding Projects
        '4': 18.00, // Case Studies
        '5': 12.00, // Presentations
        '6': 16.00  // Lab Reports
    };
    
    // Academic level multipliers
    const levelMultipliers = {
        'high_school': 1.0,
        'undergraduate': 1.2,
        'graduate': 1.5,
        'phd': 2.0
    };
    
    function calculatePrice() {
        const categoryId = categorySelect.value;
        const wordCount = parseInt(wordCountInput.value) || 0;
        const deadline = new Date(deadlineInput.value);
        const academicLevel = academicLevelSelect ? academicLevelSelect.value : 'undergraduate';
        
        if (!categoryId || wordCount < 100 || !deadline) {
            priceDisplay.textContent = '$0.00';
            return;
        }
        
        const basePrice = basePrices[categoryId] || 15.00;
        const pricePerWord = basePrice / 250;
        let totalPrice = pricePerWord * wordCount;
        
        // Apply academic level multiplier
        totalPrice *= levelMultipliers[academicLevel] || 1.0;
        
        // Apply deadline urgency multiplier
        const now = new Date();
        const timeDiff = deadline.getTime() - now.getTime();
        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
        
        if (daysDiff <= 1) {
            totalPrice *= 2.0; // 24 hours or less
        } else if (daysDiff <= 3) {
            totalPrice *= 1.5; // 3 days or less
        } else if (daysDiff <= 7) {
            totalPrice *= 1.2; // 1 week or less
        }
        
        priceDisplay.textContent = '$' + totalPrice.toFixed(2);
    }
    
    // Add event listeners
    [categorySelect, wordCountInput, deadlineInput, academicLevelSelect].forEach(element => {
        if (element) {
            element.addEventListener('change', calculatePrice);
            element.addEventListener('input', calculatePrice);
        }
    });
    
    // Initial calculation
    calculatePrice();
}

// File upload handling
function initFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            handleFileUpload(this);
        });
    });
}

function handleFileUpload(input) {
    const files = input.files;
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Check file size
        if (file.size > maxSize) {
            showAlert('File size must be less than 10MB', 'danger');
            input.value = '';
            return;
        }
        
        // Check file type
        const extension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(extension)) {
            showAlert('File type not allowed. Please upload PDF, DOC, DOCX, TXT, JPG, or PNG files.', 'danger');
            input.value = '';
            return;
        }
    }
    
    // Display selected files
    displaySelectedFiles(input);
}

function displaySelectedFiles(input) {
    const files = input.files;
    const container = input.parentNode.querySelector('.file-list') || createFileListContainer(input);
    
    container.innerHTML = '';
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2';
        fileItem.innerHTML = `
            <span>${file.name} (${formatFileSize(file.size)})</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(this, ${i})">Remove</button>
        `;
        container.appendChild(fileItem);
    }
}

function createFileListContainer(input) {
    const container = document.createElement('div');
    container.className = 'file-list mt-2';
    input.parentNode.appendChild(container);
    return container;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function removeFile(button, index) {
    // This is a simplified version - in a real implementation,
    // you'd need to handle file removal from the input
    button.parentNode.remove();
}

// Notifications
function initNotifications() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            fadeOut(alert);
        }, 5000);
    });
    
    // Check for new notifications periodically
    if (document.body.classList.contains('logged-in')) {
        setInterval(checkNotifications, 30000); // Check every 30 seconds
    }
}

function checkNotifications() {
    fetch('/api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                updateNotificationBadge(data.unread_count);
                // You can add more notification handling here
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

// Mobile menu
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    }
}

// Tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const text = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.position = 'absolute';
    tooltip.style.background = '#333';
    tooltip.style.color = 'white';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '1000';
    tooltip.style.pointerEvents = 'none';
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element._tooltip = tooltip;
}

function hideTooltip(event) {
    const element = event.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        fadeOut(alert);
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.maxWidth = '400px';
    document.body.appendChild(container);
    return container;
}

function fadeOut(element) {
    element.style.opacity = '0';
    element.style.transition = 'opacity 0.3s ease';
    setTimeout(() => {
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }, 300);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
    if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + ' months ago';
    
    return Math.floor(diffInSeconds / 31536000) + ' years ago';
}

// AJAX helper function
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    // Add CSRF token for POST requests
    if (finalOptions.method === 'POST') {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            finalOptions.headers['X-CSRF-Token'] = csrfToken.getAttribute('content');
        }
    }
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            showAlert('An error occurred. Please try again.', 'danger');
            throw error;
        });
}

// Export functions for global use
window.showAlert = showAlert;
window.confirmAction = confirmAction;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.timeAgo = timeAgo;
window.makeRequest = makeRequest;
