import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, LogOut, Menu, Moon, Sun } from 'lucide';

window.Alpine = Alpine;

Alpine.data('appShell', () => ({
    sidebarOpen: false,
    dark: document.documentElement.classList.contains('dark'),
    toggleTheme() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('libraflow-theme', this.dark ? 'dark' : 'light');
    },
}));

Alpine.start();

createIcons({
    icons: {
        LogOut,
        Menu,
        Moon,
        Sun,
    },
});
