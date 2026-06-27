
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Mobile sidebar toggle for admin layout
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.querySelector('[data-sidebar-toggle]');
    var sidebar = document.querySelector('[data-sidebar]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');

    if (!toggleBtn || !sidebar || !backdrop) {
        return;
    }

    function openSidebar() {
        sidebar.classList.remove('hidden');
        backdrop.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('hidden');
        backdrop.classList.add('hidden');
    }

    toggleBtn.addEventListener('click', openSidebar);
    backdrop.addEventListener('click', closeSidebar);
});

