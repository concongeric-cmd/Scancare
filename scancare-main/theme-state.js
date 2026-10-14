(function () {
    'use strict';

    var savedTheme = localStorage.getItem('theme') || localStorage.getItem('scancare_theme');
    if (!savedTheme && localStorage.getItem('darkMode') === 'enabled') {
        savedTheme = 'dark';
    }

    var isDark = savedTheme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);

    function applyTheme(dark) {
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.classList.toggle('dark-mode', dark);
        if (document.body) {
            document.body.classList.toggle('dark-mode', dark);
        }
    }

    window.ScanCareTheme = {
        set: function (theme) {
            var dark = theme === 'dark';
            localStorage.setItem('theme', dark ? 'dark' : 'light');
            localStorage.setItem('scancare_theme', dark ? 'dark' : 'light');
            localStorage.setItem('darkMode', dark ? 'enabled' : 'disabled');
            applyTheme(dark);
        },
        apply: applyTheme,
        isDark: function () {
            return document.documentElement.classList.contains('dark');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            applyTheme(isDark);
        }, { once: true });
    } else {
        applyTheme(isDark);
    }
}());