import './bootstrap';
import Alpine from 'alpinejs';
import {
    Bookmark,
    BookOpenCheck,
    createIcons,
    LogOut,
    Menu,
    Moon,
    Sun,
    UserRound,
} from 'lucide';

window.Alpine = Alpine;

Alpine.data('appShell', () => ({
    sidebarOpen: false,
    dark: document.documentElement.classList.contains('dark'),
    toggleTheme() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('lyrary-theme', this.dark ? 'dark' : 'light');
    },
}));

Alpine.start();

createIcons({
    icons: {
        Bookmark,
        BookOpenCheck,
        LogOut,
        Menu,
        Moon,
        Sun,
        UserRound,
    },
});
