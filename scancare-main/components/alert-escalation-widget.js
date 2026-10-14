/**
 * ScanCare Alert Escalation Widget
 * A flexible, accessible, multi-mode status indicator and escalation timeline component
 * 
 * Usage:
 *   const widget = new AlertEscalationWidget({
 *     containerId: 'alert-widget',
 *     mode: 'badge', // or 'timeline'
 *     activeLevel: 2,
 *     onLevelChange: (level) => console.log(level)
 *   });
 *   widget.render();
 */

class AlertEscalationWidget {
  constructor(options = {}) {
    this.containerId = options.containerId || 'alert-escalation-widget';
    this.mode = options.mode || 'badge'; // 'badge' or 'timeline'
    this.activeLevel = Math.max(1, Math.min(4, options.activeLevel || 1));
    this.onLevelChange = options.onLevelChange || (() => {});
    this.isInteractive = options.isInteractive !== false;
    this.orientation = options.orientation || 'horizontal'; // 'horizontal' or 'vertical'

    this.escalationLevels = [
      {
        level: 1,
        label: 'Baseline',
        status: 'Normal disease occurrence',
        description: 'Data logged for trend analysis.',
        color: 'green',
        bgColor: 'bg-green-50',
        borderColor: 'border-green-200',
        statusColor: 'text-green-600',
        badgeColor: '#16a34a',
        icon: '🟢',
        ariaLabel: 'Alert Level 1: Baseline - Normal disease occurrence'
      },
      {
        level: 2,
        label: 'Watch',
        status: 'Unusual cluster detected',
        description: 'Officials notified for verification and light interventions.',
        color: 'yellow',
        bgColor: 'bg-yellow-50',
        borderColor: 'border-yellow-200',
        statusColor: 'text-yellow-600',
        badgeColor: '#eab308',
        icon: '🟡',
        ariaLabel: 'Alert Level 2: Watch - Unusual cluster detected'
      },
      {
        level: 3,
        label: 'Alert',
        status: 'Confirmed outbreak',
        description: 'SMS broadcast to affected puroks + staff mobilization.',
        color: 'orange',
        bgColor: 'bg-orange-50',
        borderColor: 'border-orange-200',
        statusColor: 'text-orange-600',
        badgeColor: '#f97316',
        icon: '🟠',
        ariaLabel: 'Alert Level 3: Alert - Confirmed outbreak'
      },
      {
        level: 4,
        label: 'Emergency',
        status: 'Major public health threat',
        description: 'Provincial/municipal officials contacted. Mass SMS alerts issued.',
        color: 'red',
        bgColor: 'bg-red-50',
        borderColor: 'border-red-200',
        statusColor: 'text-red-600',
        badgeColor: '#dc2626',
        icon: '🔴',
        ariaLabel: 'Alert Level 4: Emergency - Major public health threat'
      }
    ];

    this.container = null;
    this.popoverElement = null;
    this.isPopoverOpen = false;
  }

  /**
   * Get current level data
   */
  getCurrentLevel() {
    return this.escalationLevels.find(l => l.level === this.activeLevel) || this.escalationLevels[0];
  }

  /**
   * Set active level and trigger callback
   */
  setActiveLevel(level) {
    const validLevel = Math.max(1, Math.min(4, level));
    if (validLevel !== this.activeLevel) {
      this.activeLevel = validLevel;
      this.onLevelChange(this.activeLevel);
      this.render();
    }
  }

  /**
   * Render Badge Mode (Compact Status Indicator)
   */
  renderBadgeMode() {
    const currentLevel = this.getCurrentLevel();
    
    return `
      <div 
        class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-white border border-slate-200 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
        role="button"
        aria-label="${currentLevel.ariaLabel}"
        aria-pressed="${this.isPopoverOpen}"
        tabindex="0"
        data-testid="alert-badge-trigger"
      >
        <!-- Status Dot with Pulse -->
        <div class="relative flex items-center justify-center">
          <div 
            class="w-3 h-3 rounded-full animate-pulse"
            style="background-color: ${currentLevel.badgeColor};"
            aria-hidden="true"
          ></div>
          <div 
            class="absolute w-3 h-3 rounded-full opacity-30"
            style="background-color: ${currentLevel.badgeColor}; animation: pulse-ring 2s infinite;"
            aria-hidden="true"
          ></div>
        </div>

        <!-- Status Text -->
        <div class="flex flex-col">
          <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Alert Status</span>
          <span class="text-sm font-semibold text-slate-900">
            Level ${currentLevel.level}: ${currentLevel.label}
          </span>
        </div>

        <!-- Dropdown Indicator -->
        <i 
          class="fa-solid fa-chevron-down text-slate-400 text-xs ml-2 transition-transform duration-200"
          id="badge-chevron"
          aria-hidden="true"
        ></i>
      </div>

      <!-- Popover Content (Hidden by default) -->
      <div 
        id="escalation-popover"
        role="tooltip"
        aria-hidden="true"
        class="absolute z-50 mt-2 bg-white border border-slate-200 rounded-lg shadow-lg p-6 w-80 hidden"
        style="visibility: hidden; opacity: 0; transform: translateY(-4px); transition: all 0.2s ease;"
      >
        <div class="space-y-4">
          ${this.escalationLevels.map(level => `
            <button
              class="w-full text-left p-3 rounded-lg border-2 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2"
              style="border-color: ${level.badgeColor}; background-color: ${level.bgColor};"
              data-level="${level.level}"
              aria-label="Set alert level to ${level.label}"
              tabindex="0"
            >
              <div class="flex items-center gap-2 mb-1">
                <span class="text-lg" aria-hidden="true">${level.icon}</span>
                <span class="font-bold text-sm text-slate-900">Level ${level.level}: ${level.label}</span>
                ${this.activeLevel === level.level ? '<i class="fa-solid fa-check ml-auto" style="color: #454B1B;"></i>' : ''}
              </div>
              <p class="text-xs text-slate-600 ml-6">${level.status}</p>
              <p class="text-xs text-slate-500 ml-6 mt-1">${level.description}</p>
            </button>
          `).join('')}
        </div>
      </div>

      <style>
        @keyframes pulse-ring {
          0% { box-shadow: 0 0 0 0 rgba(${this.hexToRgb(this.getCurrentLevel().badgeColor)}, 0.7); }
          70% { box-shadow: 0 0 0 8px rgba(${this.hexToRgb(this.getCurrentLevel().badgeColor)}, 0); }
          100% { box-shadow: 0 0 0 0 rgba(${this.hexToRgb(this.getCurrentLevel().badgeColor)}, 0); }
        }
      </style>
    `;
  }

  /**
   * Render Timeline/Stepper Mode (Full Escalation Visualization)
   */
  renderTimelineMode() {
    const stepperClass = this.orientation === 'vertical' 
      ? 'flex flex-col space-y-4' 
      : 'grid grid-cols-1 md:grid-cols-4 gap-4';

    return `
      <div 
        class="${stepperClass}"
        role="group"
        aria-label="Alert escalation levels"
      >
        ${this.escalationLevels.map((level, index) => {
          const isActive = this.activeLevel >= level.level;
          const isCurrent = this.activeLevel === level.level;
          const nextLevel = index < this.escalationLevels.length - 1 ? this.escalationLevels[index + 1] : null;

          return `
            <div class="flex flex-col items-center">
              <!-- Step Connector (Horizontal) -->
              ${index > 0 && this.orientation === 'horizontal' ? `
                <div 
                  class="absolute left-0 right-0 top-6 h-1 -mx-2"
                  style="background-color: ${isActive ? level.badgeColor : '#e2e8f0'};"
                  aria-hidden="true"
                ></div>
              ` : ''}

              <!-- Step Connector (Vertical) -->
              ${index > 0 && this.orientation === 'vertical' ? `
                <div 
                  class="absolute left-6 top-0 bottom-full w-1 -my-2"
                  style="background-color: ${isActive ? level.badgeColor : '#e2e8f0'};"
                  aria-hidden="true"
                ></div>
              ` : ''}

              <!-- Step Circle/Button -->
              <button
                class="relative z-10 w-12 h-12 rounded-full border-2 flex items-center justify-center font-bold text-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white hover:scale-110"
                style="${isCurrent ? `background-color: ${level.badgeColor}; color: white; border-color: ${level.badgeColor};` : isActive ? `background-color: ${level.badgeColor}20; border-color: ${level.badgeColor}; color: ${level.badgeColor};` : `background-color: #f1f5f9; border-color: #cbd5e1; color: #94a3b8;`}"
                data-level="${level.level}"
                aria-label="${level.ariaLabel}"
                aria-pressed="${isCurrent}"
                aria-selected="${isCurrent}"
                tabindex="${isCurrent ? '0' : '-1'}"
              >
                <span aria-hidden="true">${level.level}</span>
              </button>

              <!-- Label -->
              <div class="mt-3 text-center">
                <p class="font-semibold text-sm text-slate-900">${level.label}</p>
              </div>

              <!-- Info Card -->
              <div 
                class="mt-4 p-4 rounded-lg border-2 w-full transition-all duration-200"
                style="border-color: ${level.badgeColor}20; background-color: ${level.bgColor};"
              >
                <div class="flex items-start gap-2">
                  <span aria-hidden="true" class="text-xl mt-0.5">${level.icon}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                      Level ${level.level}
                    </p>
                    <p class="text-sm font-semibold text-slate-900 mt-1">
                      ${level.status}
                    </p>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                      ${level.description}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          `;
        }).join('')}
      </div>

      <!-- Interactive Controls (Optional) -->
      ${this.isInteractive ? `
        <div class="mt-8 pt-6 border-t border-slate-200">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-900">Manual Level Adjustment</p>
              <p class="text-xs text-slate-500 mt-1">For testing and demonstration purposes</p>
            </div>
            <div class="flex gap-2">
              ${[1, 2, 3, 4].map(level => `
                <button
                  class="px-3 py-2 rounded-lg border-2 font-semibold text-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white"
                  style="border-color: ${this.escalationLevels[level - 1].badgeColor}; background-color: ${this.activeLevel === level ? this.escalationLevels[level - 1].badgeColor : 'white'}; color: ${this.activeLevel === level ? 'white' : this.escalationLevels[level - 1].badgeColor};"
                  data-set-level="${level}"
                  aria-label="Set alert level to ${level}"
                  aria-pressed="${this.activeLevel === level}"
                >
                  L${level}
                </button>
              `).join('')}
            </div>
          </div>
        </div>
      ` : ''}
    `;
  }

  /**
   * Attach event listeners
   */
  attachEventListeners() {
    if (this.mode === 'badge') {
      const trigger = this.container.querySelector('[data-testid="alert-badge-trigger"]');
      const popover = this.container.querySelector('#escalation-popover');
      const chevron = this.container.querySelector('#badge-chevron');

      if (trigger) {
        // Toggle popover on click
        trigger.addEventListener('click', () => this.togglePopover());

        // Toggle popover on Enter/Space key
        trigger.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.togglePopover();
          }
        });

        // Close popover on Escape key
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && this.isPopoverOpen) {
            this.closePopover();
          }
        });

        // Close popover when clicking outside
        document.addEventListener('click', (e) => {
          if (this.isPopoverOpen && !this.container.contains(e.target)) {
            this.closePopover();
          }
        });
      }

      // Level buttons in popover
      this.container.querySelectorAll('#escalation-popover [data-level]').forEach(button => {
        button.addEventListener('click', () => {
          const level = parseInt(button.dataset.level);
          this.setActiveLevel(level);
          this.closePopover();
        });

        button.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const level = parseInt(button.dataset.level);
            this.setActiveLevel(level);
            this.closePopover();
          }
        });
      });
    }

    if (this.mode === 'timeline') {
      // Timeline level buttons
      this.container.querySelectorAll('[data-level]').forEach(button => {
        button.addEventListener('click', () => {
          const level = parseInt(button.dataset.level);
          this.setActiveLevel(level);
        });

        button.addEventListener('keydown', (e) => {
          if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            this.setActiveLevel(this.activeLevel - 1);
          } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            this.setActiveLevel(this.activeLevel + 1);
          } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const level = parseInt(button.dataset.level);
            this.setActiveLevel(level);
          }
        });
      });

      // Manual level adjustment buttons (if present)
      this.container.querySelectorAll('[data-set-level]').forEach(button => {
        button.addEventListener('click', () => {
          const level = parseInt(button.dataset.setLevel);
          this.setActiveLevel(level);
        });
      });
    }
  }

  /**
   * Toggle popover visibility (Badge mode only)
   */
  togglePopover() {
    if (this.isPopoverOpen) {
      this.closePopover();
    } else {
      this.openPopover();
    }
  }

  /**
   * Open popover
   */
  openPopover() {
    const popover = this.container.querySelector('#escalation-popover');
    const trigger = this.container.querySelector('[data-testid="alert-badge-trigger"]');
    const chevron = this.container.querySelector('#badge-chevron');

    if (popover) {
      popover.classList.remove('hidden');
      popover.style.visibility = 'visible';
      popover.style.opacity = '1';
      popover.style.transform = 'translateY(0)';
      this.isPopoverOpen = true;

      if (trigger) {
        trigger.setAttribute('aria-pressed', 'true');
      }

      if (chevron) {
        chevron.style.transform = 'rotate(180deg)';
      }

      // Focus first button in popover
      const firstButton = popover.querySelector('[data-level]');
      if (firstButton) {
        firstButton.focus();
      }
    }
  }

  /**
   * Close popover
   */
  closePopover() {
    const popover = this.container.querySelector('#escalation-popover');
    const trigger = this.container.querySelector('[data-testid="alert-badge-trigger"]');
    const chevron = this.container.querySelector('#badge-chevron');

    if (popover) {
      popover.classList.add('hidden');
      popover.style.visibility = 'hidden';
      popover.style.opacity = '0';
      popover.style.transform = 'translateY(-4px)';
      this.isPopoverOpen = false;

      if (trigger) {
        trigger.setAttribute('aria-pressed', 'false');
        trigger.focus();
      }

      if (chevron) {
        chevron.style.transform = 'rotate(0)';
      }
    }
  }

  /**
   * Convert hex color to RGB
   */
  hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result 
      ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}`
      : '69, 75, 27'; // Fallback to ScanCare brand color
  }

  /**
   * Main render method
   */
  render() {
    this.container = document.getElementById(this.containerId);

    if (!this.container) {
      console.error(`Container with ID "${this.containerId}" not found`);
      return;
    }

    // Clear previous content
    this.container.innerHTML = '';

    // Render based on mode
    const html = this.mode === 'badge' ? this.renderBadgeMode() : this.renderTimelineMode();

    this.container.innerHTML = html;

    // Attach event listeners
    this.attachEventListeners();
  }

  /**
   * Destroy component and clean up
   */
  destroy() {
    if (this.container) {
      this.container.innerHTML = '';
    }
  }
}

// Export for use in modules or as global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = AlertEscalationWidget;
}
