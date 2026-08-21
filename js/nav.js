document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.querySelector('.menu-btn');
    const menuToggle = document.getElementById('menu-toggle');
    if (!menuBtn || !menuToggle) return;

    menuBtn.setAttribute('tabindex', '0');
    menuBtn.setAttribute('role', 'button');
    menuBtn.setAttribute('aria-expanded', 'false');

    menuBtn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            menuToggle.checked = !menuToggle.checked;
            menuBtn.setAttribute('aria-expanded', String(menuToggle.checked));
        }
    });

    menuToggle.addEventListener('change', () => {
        menuBtn.setAttribute('aria-expanded', String(menuToggle.checked));
    });
});
