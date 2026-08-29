// GameMods - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Header scroll effect
    const header = document.querySelector('.header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
    
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
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
    
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe cards and sections
    document.querySelectorAll('.game-card, .mod-card, .category-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    // Add animate-in class styles dynamically
    const style = document.createElement('style');
    style.textContent = `
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    `;
    document.head.appendChild(style);
    
    // Search functionality
    const searchBox = document.querySelector('.search-box input');
    const searchButton = document.querySelector('.search-box button');
    
    if (searchBox && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchBox.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        function performSearch() {
            const query = searchBox.value.trim();
            if (query) {
                console.log('Searching for:', query);
                // Redirect to search page or show results
                // window.location.href = `search.html?q=${encodeURIComponent(query)}`;
                
                // Visual feedback
                searchBox.style.borderColor = 'var(--success-color)';
                setTimeout(() => {
                    searchBox.style.borderColor = '';
                }, 1000);
            }
        }
    }
    
    // Like button functionality
    document.querySelectorAll('.btn-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const icon = this.querySelector('i');
            
            if (icon.classList.contains('fa-heart')) {
                icon.classList.toggle('fas');
                icon.classList.toggle('far');
                
                if (icon.classList.contains('fas')) {
                    this.style.background = 'var(--danger-color)';
                    this.style.borderColor = 'var(--danger-color)';
                    this.style.color = 'white';
                } else {
                    this.style.background = '';
                    this.style.borderColor = '';
                    this.style.color = '';
                }
            }
        });
    });
    
    // Download counter animation
    const downloadButtons = document.querySelectorAll('.btn-primary .fa-download');
    downloadButtons.forEach(btn => {
        btn.parentElement.addEventListener('click', function(e) {
            if (!this.getAttribute('href')) {
                e.preventDefault();
                
                // Create ripple effect
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
                ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
                
                // Show download notification
                showNotification('Загрузка началась...', 'success');
            }
        });
    });
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        notification.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--${type === 'success' ? 'success-color' : type === 'error' ? 'danger-color' : 'primary-color'});
            color: var(--text-primary);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-xl);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Add notification animations
    const animStyle = document.createElement('style');
    animStyle.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(animStyle);
    
    // Stats counter animation
    const statNumbers = document.querySelectorAll('.stat-number');
    
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    statNumbers.forEach(stat => statsObserver.observe(stat));
    
    function animateCounter(element) {
        const text = element.textContent;
        const match = text.match(/([\d\.]+)([KMB+]?)/);
        
        if (!match) return;
        
        const number = parseFloat(match[1]);
        const suffix = match[2];
        const duration = 2000;
        const step = number / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= number) {
                current = number;
                clearInterval(timer);
            }
            element.textContent = formatNumber(current) + suffix;
        }, 16);
    }
    
    function formatNumber(num) {
        if (num >= 1000) {
            return Math.floor(num).toLocaleString();
        }
        return num.toFixed(1);
    }
    
    // Tooltip for icons
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: var(--bg-darker);
                color: var(--text-primary);
                padding: 0.5rem 0.75rem;
                border-radius: var(--radius-md);
                font-size: 0.75rem;
                white-space: nowrap;
                z-index: 1000;
                border: 1px solid var(--border-color);
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
    
    // Lazy loading images
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }
    
    // Filter functionality (for future use)
    window.filterMods = function(category) {
        const modCards = document.querySelectorAll('.mod-card');
        
        modCards.forEach(card => {
            const modCategory = card.querySelector('.mod-category').textContent.toLowerCase();
            
            if (category === 'all' || modCategory.includes(category)) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 100);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    };
    
    // Sort functionality (for future use)
    window.sortMods = function(criteria) {
        const modsGrid = document.querySelector('.mods-grid');
        const modCards = Array.from(modsGrid.querySelectorAll('.mod-card'));
        
        modCards.sort((a, b) => {
            const aStats = a.querySelector('.mod-stats');
            const bStats = b.querySelector('.mod-stats');
            
            switch(criteria) {
                case 'downloads':
                    const aDownloads = parseInt(aStats.children[0].textContent.replace(/[^0-9.]/g, ''));
                    const bDownloads = parseInt(bStats.children[0].textContent.replace(/[^0-9.]/g, ''));
                    return bDownloads - aDownloads;
                case 'rating':
                    const aRating = parseFloat(aStats.children[1].textContent.replace(/[^0-9.]/g, ''));
                    const bRating = parseFloat(bStats.children[1].textContent.replace(/[^0-9.]/g, ''));
                    return bRating - aRating;
                case 'views':
                    const aViews = parseInt(aStats.children[2].textContent.replace(/[^0-9.]/g, ''));
                    const bViews = parseInt(bStats.children[2].textContent.replace(/[^0-9.]/g, ''));
                    return bViews - aViews;
                default:
                    return 0;
            }
        });
        
        modCards.forEach(card => modsGrid.appendChild(card));
    };
    
    console.log('GameMods initialized successfully!');
});
