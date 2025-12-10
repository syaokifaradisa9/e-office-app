import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function ThemeToggle() {
    const [isDark, setIsDark] = useState(false);

    useEffect(() => {
        const darkMode =
            localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
        setIsDark(darkMode);
        document.documentElement.classList.toggle('dark', darkMode);
    }, []);

    const toggleTheme = () => {
        setIsDark(!isDark);
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', !isDark ? 'dark' : 'light');
    };

    return (
        <button
            onClick={toggleTheme}
            className="fixed bottom-6 right-6 z-40 rounded-full border border-gray-200 bg-white/80 p-2 text-gray-700 shadow-sm backdrop-blur-sm transition-all hover:bg-gray-50 dark:border-slate-700/50 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:bg-slate-700/50"
        >
            {isDark ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
        </button>
    );
}
