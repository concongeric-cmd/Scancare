

class OfflineIndicator {
    constructor() {
        this.isOnline = navigator.onLine;
        this.banner = null;
        this.init();
    }

    init() {
        this.createBanner();
        
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        if (!this.isOnline) {
            this.handleOffline();
        }
    }

    
    createBanner() {
        this.banner = document.createElement('div');
        this.banner.id = 'offline-indicator';
        this.banner.className = 'offline-banner hidden';
        this.banner.innerHTML = `
            <div class="offline-banner-content">
                <div class="offline-banner-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M16.72 4.02a9 9 0 0 1 2.7 9"></path>
                        <path d="M9.6 4.2A9 9 0 0 0 5.64 6.64"></path>
                        <path d="M22.65 13A16.06 16.06 0 0 0 12.82 1.25"></path>
                        <path d="M1 1v8h8"></path>
                    </svg>
                </div>
                <div class="offline-banner-text">
                    <strong>You're Offline</strong>
                    <span>Changes will sync automatically when connection returns</span>
                </div>
                <div class="offline-banner-close">
                    <button onclick="document.getElementById('offline-indicator').classList.add('hidden')" aria-label="Dismiss offline notification">
                        ×
                    </button>
                </div>
            </div>
        `;
        
        
        if (!document.getElementById('offline-indicator-styles')) {
            const style = document.createElement('style');
            style.id = 'offline-indicator-styles';
            style.textContent = `
                .offline-banner {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(135deg, #FF6B6B 0%, #FF8C42 100%);
                    color: white;
                    padding: 0;
                    z-index: 9999;
                    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
                    animation: slideDown 0.3s ease;
                    font-size: 14px;
                }

                .offline-banner.hidden {
                    display: none;
                }

                @keyframes slideDown {
                    from {
                        transform: translateY(-100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                .offline-banner-content {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 16px;
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .offline-banner-icon {
                    flex-shrink: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .offline-banner-icon svg {
                    color: white;
                    filter: drop-shadow(0 0 2px rgba(255,255,255,0.3));
                }

                .offline-banner-text {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                }

                .offline-banner-text strong {
                    font-weight: 600;
                    display: block;
                }

                .offline-banner-text span {
                    font-size: 12px;
                    opacity: 0.9;
                }

                .offline-banner-close button {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 0 8px;
                    opacity: 0.8;
                    transition: opacity 0.2s;
                }

                .offline-banner-close button:hover {
                    opacity: 1;
                }

                
                .notification {
                    position: fixed;
                    bottom: -100px;
                    left: 50%;
                    transform: translateX(-50%);
                    padding: 12px 20px;
                    border-radius: 6px;
                    background: #333;
                    color: white;
                    font-size: 14px;
                    z-index: 10000;
                    transition: bottom 0.3s ease;
                }

                .notification.show {
                    bottom: 20px;
                }

                .notification-success {
                    background: #51CF66;
                    color: #1a3a1a;
                }

                .notification-warning {
                    background: #FFD93D;
                    color: #3d3d1a;
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.insertBefore(this.banner, document.body.firstChild);
    }

    
    handleOnline() {
        this.isOnline = true;
        if (this.banner) {
            this.banner.classList.add('hidden');
        }
        
        this.showNotification('✓ Back online! Syncing changes...', 'success');
        
        
        const event = new CustomEvent('scancare:online');
        window.dispatchEvent(event);
    }

    
    handleOffline() {
        this.isOnline = false;
        if (this.banner) {
            this.banner.classList.remove('hidden');
        }
        
        this.showNotification('⚠ You are offline', 'warning');
        
        
        const event = new CustomEvent('scancare:offline');
        window.dispatchEvent(event);
    }

    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'polite');
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}


if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new OfflineIndicator();
    });
} else {
    new OfflineIndicator();
}

