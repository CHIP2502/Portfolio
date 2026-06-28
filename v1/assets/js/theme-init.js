(function () {
    const isTouchDevice = window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches;

    if (!isTouchDevice) {
        document.documentElement.classList.add('is-loading');
    }

    document.documentElement.setAttribute('data-loader-enabled', isTouchDevice ? 'false' : 'true');

    const themeStorageKey = 'portfolio-theme';
    let activeTheme = 'dark';
    let themeSource = 'default';

    try {
        const storedTheme = localStorage.getItem(themeStorageKey);

        if (storedTheme === 'light' || storedTheme === 'dark') {
            activeTheme = storedTheme;
            themeSource = 'user';
        }
    } catch (_) {
    }

    document.documentElement.setAttribute('data-theme', activeTheme);
    document.documentElement.setAttribute('data-theme-source', themeSource);

    const themeMeta = document.getElementById('theme-color-meta');

    if (themeMeta) {
        themeMeta.setAttribute('content', activeTheme === 'dark' ? '#0b1220' : '#f5efe4');
    }
})();
