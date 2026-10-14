

class AccessibilityManager {
    constructor() {
        this.storageKey = 'scancare-accessibility-prefs';
        this.preferences = this.loadPreferences();
        this.init();
    }

    init() {
        this.applyPreferences();
        this.setupToggleButtons();
        this.setupKeyboardShortcuts();
        this.injectStyles();
    }

    
    loadPreferences() {
        const saved = localStorage.getItem(this.storageKey);
        return saved ? JSON.parse(saved) : {
            highContrast: false,
            fontSize: 100,
            reduceMotion: false
        };
    }

    
    savePreferences() {
        localStorage.setItem(this.storageKey, JSON.stringify(this.preferences));
    }

    
    applyPreferences() {
        const html = document.documentElement;
        
        
        if (this.preferences.highContrast) {
            html.classList.add('high-contrast-mode');
        } else {
            html.classList.remove('high-contrast-mode');
        }

        
        html.style.fontSize = `${this.preferences.fontSize}%`;

        
        if (this.preferences.reduceMotion) {
            html.classList.add('reduce-motion');
        } else {
            html.classList.remove('reduce-motion');
        }

        
        const contrastCheckbox = document.getElementById('btn-high-contrast');
        if (contrastCheckbox) {
            contrastCheckbox.checked = this.preferences.highContrast;
        }
    }

    
    toggleHighContrast() {
        this.preferences.highContrast = !this.preferences.highContrast;
        this.savePreferences();
        this.applyPreferences();
        
        const status = this.preferences.highContrast ? 'enabled' : 'disabled';
        this.announceToScreenReaders(`High contrast ${status}`);
    }

    
    increaseFontSize() {
        if (this.preferences.fontSize < 150) {
            this.preferences.fontSize += 10;
            this.savePreferences();
            this.applyPreferences();
            this.announceToScreenReaders(`Text size increased to ${this.preferences.fontSize}%`);
        }
    }

    
    decreaseFontSize() {
        if (this.preferences.fontSize > 80) {
            this.preferences.fontSize -= 10;
            this.savePreferences();
            this.applyPreferences();
            this.announceToScreenReaders(`Text size decreased to ${this.preferences.fontSize}%`);
        }
    }

    
    resetAccessibility() {
        this.preferences = {
            highContrast: false,
            fontSize: 100,
            reduceMotion: false
        };
        this.savePreferences();
        this.applyPreferences();
        this.announceToScreenReaders('Accessibility settings reset to defaults');
    }

    
    announceToScreenReaders(message) {
        const announcement = document.createElement('div');
        announcement.className = 'sr-only';
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.textContent = message;
        document.body.appendChild(announcement);
        setTimeout(() => announcement.remove(), 1000);
    }

    
    setupToggleButtons() {
        const contrastBtn = document.getElementById('btn-high-contrast');
        const increaseFontBtn = document.getElementById('btn-increase-font');
        const decreaseFontBtn = document.getElementById('btn-decrease-font');
        const resetBtn = document.getElementById('btn-reset-a11y');
        const accessibilityToggle = document.getElementById('btn-accessibility-toggle');
        const accessibilityMenu = document.getElementById('accessibility-menu');
        const closeAccessibilityBtn = document.getElementById('btn-close-a11y');
        const fontSizeDisplay = document.getElementById('font-size-display');

        if (contrastBtn) {
            contrastBtn.addEventListener('change', () => this.toggleHighContrast());
        }

        if (increaseFontBtn) {
            increaseFontBtn.addEventListener('click', () => this.increaseFontSize());
        }

        if (decreaseFontBtn) {
            decreaseFontBtn.addEventListener('click', () => this.decreaseFontSize());
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetAccessibility());
        }

        
        if (accessibilityToggle && accessibilityMenu) {
            accessibilityToggle.addEventListener('click', () => {
                accessibilityMenu.classList.toggle('hidden');
                if (!accessibilityMenu.classList.contains('hidden')) {
                    accessibilityToggle.setAttribute('aria-expanded', 'true');
                    accessibilityMenu.focus();
                } else {
                    accessibilityToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (closeAccessibilityBtn && accessibilityMenu) {
            closeAccessibilityBtn.addEventListener('click', () => {
                accessibilityMenu.classList.add('hidden');
                accessibilityToggle.setAttribute('aria-expanded', 'false');
            });
        }

        
        if (fontSizeDisplay) {
            setInterval(() => {
                fontSizeDisplay.textContent = this.preferences.fontSize + '%';
            }, 100);
        }
    }

    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (!e.altKey) return;

            if (e.key === 'c' || e.key === 'C') {
                e.preventDefault();
                this.toggleHighContrast();
            }

            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.increaseFontSize();
            }

            if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.decreaseFontSize();
            }
        });
    }

    
    injectStyles() {
        if (document.getElementById('accessibility-styles')) return;

        const style = document.createElement('style');
        style.id = 'accessibility-styles';
        style.textContent = `
            
            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border-width: 0;
            }

            
            html.high-contrast-mode {
                
                
                
                
                
            }

            html.high-contrast-mode body {
                background-color: #000 !important;
                color: #fff !important;
            }

            html.high-contrast-mode * {
                border-color: #fff !important;
                color: #fff !important;
                background-color: #1a1a1a !important;
            }

            html.high-contrast-mode button,
            html.high-contrast-mode a {
                outline: 2px solid #ffff00;
                outline-offset: 2px;
                color: #ffff00 !important;
            }

            html.high-contrast-mode input,
            html.high-contrast-mode textarea,
            html.high-contrast-mode select {
                background-color: #1a1a1a !important;
                color: #ffff00 !important;
                border: 2px solid #ffff00 !important;
            }

            
            html.reduce-motion * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            
            :focus-visible {
                outline: 3px solid #454B1B;
                outline-offset: 2px;
            }

            button:focus-visible,
            a:focus-visible,
            input:focus-visible,
            select:focus-visible,
            textarea:focus-visible {
                outline: 3px solid #454B1B;
                outline-offset: 2px;
            }

            html.high-contrast-mode :focus-visible {
                outline: 3px solid #ffff00;
                outline-offset: 2px;
            }
        `;
        document.head.appendChild(style);
    }
}


if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AccessibilityManager();
    });
} else {
    new AccessibilityManager();
}

