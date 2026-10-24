(() => {
    const checkAccountSession = async () => {
        try {
            const response = await fetch('session_status.php', {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            const result = await response.json();
            if (!result.active) {
                window.location.replace(result.redirect || 'login.php?account_removed=1');
            }
        } catch (_) {
            // A temporary network issue should not sign the user out.
        }
    };

    window.setInterval(checkAccountSession, 5000);
    window.addEventListener('focus', checkAccountSession);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkAccountSession();
    });
})();
