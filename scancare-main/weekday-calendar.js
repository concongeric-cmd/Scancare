(() => {
    const weekdayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

    function parseDate(value) {
        if (!value) return null;
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return null;
        const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function toDateValue(date) {
        return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    }

    function installThemeStyles() {
        if (document.getElementById('weekdayCalendarThemeStyles')) return;
        const style = document.createElement('style');
        style.id = 'weekdayCalendarThemeStyles';
        style.textContent = `
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-popover] {
                background-color: #0f172a !important;
                border-color: #334155 !important;
                color: #e2e8f0 !important;
            }
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-toggle] {
                background-color: #0f172a !important;
                border-color: #475569 !important;
                color: #e2e8f0 !important;
            }
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-popover] [data-calendar-month],
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-popover] [data-calendar-weekday],
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-popover] button:not(.weekday-calendar-selected) {
                color: #cbd5e1 !important;
            }
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-popover] .weekday-calendar-selected {
                background-color: #bef264 !important;
                color: #0f172a !important;
            }
            :is(html.dark, html.dark-mode, body.dark-mode) [data-calendar-popover] .weekday-calendar-disabled {
                color: #64748b !important;
            }
            [data-calendar-popover].weekday-calendar-compact {
                padding: .5rem !important;
            }
            [data-calendar-popover].weekday-calendar-compact [data-calendar-prev],
            [data-calendar-popover].weekday-calendar-compact [data-calendar-next] {
                width: 2rem !important;
                height: 2rem !important;
            }
            [data-calendar-popover].weekday-calendar-compact [data-calendar-grid] button {
                min-height: 2.25rem !important;
            }
            [data-calendar-popover].weekday-calendar-compact [data-calendar-grid] {
                gap: .125rem !important;
            }
        `;
        document.head.appendChild(style);
    }

    function initWeekdayCalendar(container) {
        const input = document.getElementById(container.dataset.target || '');
        const toggle = container.querySelector('[data-calendar-toggle]');
        const display = container.querySelector('[data-calendar-display]');
        const popover = container.querySelector('[data-calendar-popover]');
        const monthLabel = container.querySelector('[data-calendar-month]');
        const grid = container.querySelector('[data-calendar-grid]');
        const previousButton = container.querySelector('[data-calendar-prev]');
        const nextButton = container.querySelector('[data-calendar-next]');
        if (!input || !toggle || !display || !popover || !monthLabel || !grid) return;
        installThemeStyles();
        const isCompact = container.dataset.calendarCompact === 'true';
        popover.classList.toggle('weekday-calendar-compact', isCompact);
        const popoverPlaceholder = document.createComment('weekday-calendar-popover');
        popover.parentNode.insertBefore(popoverPlaceholder, popover);

        const minimumDate = () => parseDate(input.min || container.dataset.minDate || '');
        const maximumDate = () => parseDate(input.max || container.dataset.maxDate || '');
        let selectedMonth = parseDate(input.value) || minimumDate() || new Date();
        selectedMonth = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth(), 1);

        const closePopover = (restoreFocus = false) => {
            popover.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            if (popoverPlaceholder.parentNode) {
                popoverPlaceholder.parentNode.insertBefore(popover, popoverPlaceholder.nextSibling);
            }
            ['position', 'left', 'right', 'top', 'bottom', 'width', 'maxWidth', 'maxHeight', 'overflowY', 'zIndex'].forEach(property => {
                popover.style[property] = '';
            });
            if (restoreFocus) toggle.focus();
        };

        const updateDisplay = () => {
            const selectedDate = parseDate(input.value);
            display.textContent = selectedDate
                ? new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }).format(selectedDate)
                : (container.dataset.emptyLabel || 'Choose a weekday');
        };

        const renderCalendar = () => {
            grid.replaceChildren();
            monthLabel.textContent = new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(selectedMonth);

            const firstWeekday = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth(), 1).getDay();
            const leadingCells = firstWeekday >= 1 && firstWeekday <= 5 ? firstWeekday - 1 : 0;
            for (let index = 0; index < leadingCells; index += 1) {
                const spacer = document.createElement('span');
                spacer.setAttribute('aria-hidden', 'true');
                grid.appendChild(spacer);
            }

            const daysInMonth = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth() + 1, 0).getDate();
            const selectedValue = input.value;
            const earliestDate = minimumDate();
            const latestDate = maximumDate();

            for (let day = 1; day <= daysInMonth; day += 1) {
                const date = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth(), day);
                const dayOfWeek = date.getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) continue;

                const dateValue = toDateValue(date);
                const dayButton = document.createElement('button');
                dayButton.type = 'button';
                dayButton.textContent = String(day);
                dayButton.dataset.calendarDate = dateValue;
                dayButton.setAttribute('aria-label', new Intl.DateTimeFormat(undefined, { dateStyle: 'full' }).format(date));
                dayButton.className = 'flex min-h-11 min-w-0 items-center justify-center rounded-lg text-sm font-semibold text-slate-700 hover:bg-[#454B1B]/10 focus:outline-none focus:ring-2 focus:ring-[#454B1B] dark:text-slate-100 dark:hover:bg-lime-300/10';

                if ((earliestDate && date < earliestDate) || (latestDate && date > latestDate)) {
                    dayButton.disabled = true;
                    dayButton.classList.add('weekday-calendar-disabled');
                    dayButton.className += ' cursor-not-allowed text-slate-300 hover:bg-transparent dark:text-slate-600';
                } else if (dateValue === selectedValue) {
                    dayButton.classList.add('weekday-calendar-selected');
                    dayButton.className += ' bg-[#454B1B] text-white hover:bg-[#363812] dark:bg-lime-300 dark:text-slate-950';
                }

                dayButton.addEventListener('click', () => {
                    input.value = dateValue;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    updateDisplay();
                    closePopover(true);
                });
                grid.appendChild(dayButton);
            }

            while (grid.children.length % 5 !== 0) {
                const spacer = document.createElement('span');
                spacer.setAttribute('aria-hidden', 'true');
                grid.appendChild(spacer);
            }

            if (previousButton) {
                const earliestDate = minimumDate();
                const earliestMonth = earliestDate ? new Date(earliestDate.getFullYear(), earliestDate.getMonth(), 1) : null;
                previousButton.disabled = Boolean(earliestMonth && selectedMonth <= earliestMonth);
                previousButton.classList.toggle('opacity-40', previousButton.disabled);
            }
            if (nextButton) {
                const latestDate = maximumDate();
                const latestMonth = latestDate ? new Date(latestDate.getFullYear(), latestDate.getMonth(), 1) : null;
                nextButton.disabled = Boolean(latestMonth && selectedMonth >= latestMonth);
                nextButton.classList.toggle('opacity-40', nextButton.disabled);
            }
        };

        const positionPopover = () => {
            if (popover.classList.contains('hidden')) return;
            const triggerRect = toggle.getBoundingClientRect();
            const edgeGap = 12;
            const anchorGap = 8;
            const availableBelow = Math.max(0, window.innerHeight - triggerRect.bottom - anchorGap - edgeGap);
            const availableAbove = Math.max(0, triggerRect.top - anchorGap - edgeGap);
            const naturalHeight = Math.ceil(popover.getBoundingClientRect().height || popover.scrollHeight || 360);
            const idealHeight = Math.min(naturalHeight, window.innerHeight - edgeGap * 2);
            // Open downward whenever it fits. Only flip upward when downward space
            // is insufficient and the space above is genuinely better.
            const openUp = container.dataset.calendarPreferUp === 'true'
                ? availableAbove >= Math.min(idealHeight, 120)
                : availableBelow < idealHeight && availableAbove > availableBelow;
            const availableSpace = openUp ? availableAbove : availableBelow;
            const panelHeight = Math.min(idealHeight, Math.max(120, availableSpace));
            const preferredWidth = isCompact ? 288 : 320;
            const panelWidth = Math.min(
                Math.max(triggerRect.width, preferredWidth),
                window.innerWidth - edgeGap * 2,
                384
            );
            const panelLeft = Math.max(edgeGap, Math.min(triggerRect.left, window.innerWidth - panelWidth - edgeGap));
            const panelTop = openUp
                ? Math.max(edgeGap, triggerRect.top - panelHeight - anchorGap)
                : Math.min(triggerRect.bottom + anchorGap, window.innerHeight - panelHeight - edgeGap);

            popover.style.position = 'fixed';
            popover.style.left = `${panelLeft}px`;
            popover.style.right = 'auto';
            popover.style.top = `${panelTop}px`;
            popover.style.bottom = 'auto';
            popover.style.width = `${panelWidth}px`;
            popover.style.maxWidth = `calc(100vw - ${edgeGap * 2}px)`;
            popover.style.maxHeight = `${panelHeight}px`;
            popover.style.overflowY = 'auto';
            popover.style.zIndex = container.closest('#rescheduleAppointmentModal') ? '12020' : '10020';
        };

        container.querySelectorAll('[data-calendar-weekday]').forEach((label, index) => {
            label.textContent = weekdayNames[index];
        });

        toggle.addEventListener('click', () => {
            const willOpen = popover.classList.contains('hidden');
            if (willOpen) {
                const selectedDate = parseDate(input.value);
                const currentMonth = selectedDate || minimumDate() || new Date();
                selectedMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
                renderCalendar();
                document.body.appendChild(popover);
                popover.classList.remove('hidden');
                positionPopover();
            } else {
                closePopover();
            }
            if (willOpen) toggle.setAttribute('aria-expanded', 'true');
        });

        previousButton?.addEventListener('click', () => {
            selectedMonth = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth() - 1, 1);
            renderCalendar();
            positionPopover();
        });
        nextButton?.addEventListener('click', () => {
            selectedMonth = new Date(selectedMonth.getFullYear(), selectedMonth.getMonth() + 1, 1);
            renderCalendar();
            positionPopover();
        });
        input.addEventListener('change', updateDisplay);

        document.addEventListener('click', event => {
            if (!toggle.contains(event.target) && !popover.contains(event.target)) {
                closePopover();
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !popover.classList.contains('hidden')) {
                closePopover(true);
            }
        });
        window.addEventListener('resize', positionPopover);
        window.addEventListener('scroll', () => {
            if (!popover.classList.contains('hidden')) closePopover();
        }, { passive: true });

        updateDisplay();
    }

    document.querySelectorAll('[data-weekday-calendar]').forEach(initWeekdayCalendar);
})();
