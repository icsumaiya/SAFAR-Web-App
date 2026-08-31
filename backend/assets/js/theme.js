(function () {
    const saved = localStorage.getItem('safar_theme');
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();

function toggleSafarTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';

    if (isDark) {
        html.removeAttribute('data-theme');
        localStorage.setItem('safar_theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('safar_theme', 'dark');
    }

    const icon = document.getElementById('theme-toggle-icon');
    if (icon) {
        icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
    }
}